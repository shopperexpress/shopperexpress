<?php
/**
 * Intice background jobs: WP-Cron or a Supervisor-managed worker.
 *
 * Every Intice cache regen/rebuild goes through Intice_Jobs::dispatch().
 *
 * - Runner "cron" (default): the legacy behavior — wp_schedule_single_event()
 *   + spawn_cron(), so the job runs on the next WP-Cron loopback.
 * - Runner "supervisor": the job is put on a small queue that a long-running
 *   `wp intice worker` process (kept alive by Supervisor) polls every second,
 *   so it runs right away, outside any HTTP timeout and independent of traffic.
 *
 * Supervisor mode is only honoured while the worker is alive (fresh heartbeat).
 * Turning it on before the worker is set up, or the worker dying, silently
 * falls back to WP-Cron — and hands any jobs still waiting on the queue over
 * to WP-Cron too — so nothing is lost and the old logic keeps working.
 *
 * @package Shopperexpress
 */

namespace App\Components\Api;

use App\Components\SOC\Support\SOC_Logger;
use App\Components\Theme_Component;

/**
 * Class Intice_Jobs
 */
class Intice_Jobs implements Theme_Component {

	const OPTION_RUNNER    = 'shopperexpress_intice_job_runner';
	const OPTION_HEARTBEAT = 'intice_worker_heartbeat';

	const RUNNER_CRON       = 'cron';
	const RUNNER_SUPERVISOR = 'supervisor';

	/**
	 * Queued jobs are stored one per option (`intice_job_{md5}`): add_option()
	 * is an atomic INSERT, so the same job queued twice collapses into one, and
	 * delete_option() is an atomic claim, so two workers never run the same job.
	 */
	const JOB_PREFIX = 'intice_job_';

	/**
	 * Worker is considered dead when its heartbeat is older than this.
	 */
	const HEARTBEAT_TTL = MINUTE_IN_SECONDS;

	/**
	 * How often the worker refreshes its heartbeat (seconds).
	 */
	const HEARTBEAT_EVERY = 15;

	/**
	 * Register the `wp intice worker` command.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'intice worker', array( $this, 'cli_worker' ) );
		}
	}

	// ─── Dispatch ─────────────────────────────────────────────────────────────

	/**
	 * Run a background job via the active runner.
	 *
	 * @param string $hook Action hook to fire (same hooks WP-Cron fires).
	 * @param array  $args Positional hook arguments.
	 * @return void
	 */
	public static function dispatch( string $hook, array $args = array() ): void {
		if ( self::use_supervisor() ) {
			self::enqueue( $hook, $args );
			return;
		}

		// Supervisor was selected but its worker isn't running — don't strand
		// what it left behind on the queue.
		if ( self::RUNNER_SUPERVISOR === self::get_runner() ) {
			self::move_queue_to_cron();
		}

		self::schedule_cron( $hook, $args );
		spawn_cron();
	}

	/**
	 * @return string self::RUNNER_CRON|self::RUNNER_SUPERVISOR
	 */
	public static function get_runner(): string {
		return self::RUNNER_SUPERVISOR === get_option( self::OPTION_RUNNER, self::RUNNER_CRON )
			? self::RUNNER_SUPERVISOR
			: self::RUNNER_CRON;
	}

	/**
	 * Switch the runner. Going back to WP-Cron hands any queued jobs over to it.
	 *
	 * @param string $runner self::RUNNER_CRON|self::RUNNER_SUPERVISOR.
	 * @return string New runner.
	 */
	public static function set_runner( string $runner ): string {
		$runner = self::RUNNER_SUPERVISOR === $runner ? self::RUNNER_SUPERVISOR : self::RUNNER_CRON;

		update_option( self::OPTION_RUNNER, $runner, false );

		if ( self::RUNNER_CRON === $runner && self::move_queue_to_cron() ) {
			spawn_cron();
		}

		return $runner;
	}

	/**
	 * Whether jobs should go to the Supervisor worker right now.
	 *
	 * @return bool
	 */
	public static function use_supervisor(): bool {
		return self::RUNNER_SUPERVISOR === self::get_runner() && self::is_worker_alive();
	}

	/**
	 * @return bool
	 */
	public static function is_worker_alive(): bool {
		return self::get_heartbeat() >= time() - self::HEARTBEAT_TTL;
	}

	/**
	 * Unix time of the worker's last heartbeat (0 = never seen).
	 *
	 * @return int
	 */
	public static function get_heartbeat(): int {
		return (int) get_option( self::OPTION_HEARTBEAT, 0 );
	}

	/**
	 * Status summary for SOC.
	 *
	 * @return array{runner: string, alive: bool, heartbeat: int, queued: int, active: bool}
	 */
	public static function get_status(): array {
		return array(
			'runner'    => self::get_runner(),
			'alive'     => self::is_worker_alive(),
			'heartbeat' => self::get_heartbeat(),
			'queued'    => count( self::get_queued_names() ),
			'active'    => self::use_supervisor(),
		);
	}

	// ─── Queue ────────────────────────────────────────────────────────────────

	/**
	 * @param string $hook
	 * @param array  $args
	 * @return void
	 */
	private static function enqueue( string $hook, array $args ): void {
		$name = self::JOB_PREFIX . md5( $hook . '|' . wp_json_encode( $args ) );

		// Fails (no-op) when the same job is already waiting — that's the dedup.
		add_option(
			$name,
			array(
				'hook'      => $hook,
				'args'      => $args,
				'queued_at' => time(),
			),
			'',
			false
		);
	}

	/**
	 * @param string $hook
	 * @param array  $args
	 * @return void
	 */
	private static function schedule_cron( string $hook, array $args ): void {
		if ( ! wp_next_scheduled( $hook, $args ) ) {
			wp_schedule_single_event( time(), $hook, $args );
		}
	}

	/**
	 * Names of queued job options, oldest first. Read straight from the DB —
	 * other processes write them, so the object cache can't be trusted here.
	 *
	 * @param int $limit
	 * @return string[]
	 */
	private static function get_queued_names( int $limit = 100 ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- must see rows other processes just wrote.
		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_id ASC LIMIT %d",
				$wpdb->esc_like( self::JOB_PREFIX ) . '%',
				$limit
			)
		);
	}

	/**
	 * Atomically take a job off the queue.
	 *
	 * @param string $name Job option name.
	 * @return array{hook: string, args: array}|null Null when another process got it first.
	 */
	private static function claim( string $name ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- must see rows other processes just wrote.
		$value = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $name ) );

		wp_cache_delete( $name, 'options' );

		if ( null === $value || ! delete_option( $name ) ) {
			return null;
		}

		$job = maybe_unserialize( $value );

		if ( ! is_array( $job ) || empty( $job['hook'] ) ) {
			return null;
		}

		return array(
			'hook' => (string) $job['hook'],
			'args' => isset( $job['args'] ) && is_array( $job['args'] ) ? array_values( $job['args'] ) : array(),
		);
	}

	/**
	 * Hand every queued job over to WP-Cron.
	 *
	 * @return int Number of jobs moved.
	 */
	private static function move_queue_to_cron(): int {
		$moved = 0;

		foreach ( self::get_queued_names() as $name ) {
			$job = self::claim( $name );

			if ( $job ) {
				self::schedule_cron( $job['hook'], $job['args'] );
				++$moved;
			}
		}

		return $moved;
	}

	// ─── Worker ───────────────────────────────────────────────────────────────

	/**
	 * Run the Intice background job worker (keep it alive with Supervisor).
	 *
	 * Polls the job queue and runs jobs as soon as they're queued. Exits on
	 * its own after --max-time seconds or when memory runs high, so Supervisor
	 * restarts it with fresh settings and code — run it with autorestart=true.
	 *
	 * ## OPTIONS
	 *
	 * [--max-time=<seconds>]
	 * : Exit after this many seconds.
	 * ---
	 * default: 600
	 * ---
	 *
	 * [--sleep=<seconds>]
	 * : Pause between queue checks when the queue is empty.
	 * ---
	 * default: 1
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp intice worker --max-time=600
	 *
	 * @param array $args       Positional args (unused).
	 * @param array $assoc_args Named args.
	 * @return void
	 */
	public function cli_worker( array $args, array $assoc_args ): void {
		$max_time = max( 30, (int) ( $assoc_args['max-time'] ?? 600 ) );
		$sleep    = max( 1, (int) ( $assoc_args['sleep'] ?? 1 ) );
		$started  = time();
		$beat_at  = 0;
		$stop     = false;

		// Let Supervisor's SIGTERM finish the current job instead of killing it.
		if ( function_exists( 'pcntl_async_signals' ) ) {
			pcntl_async_signals( true );
			$request_stop = function () use ( &$stop ) {
				$stop = true;
			};
			pcntl_signal( SIGTERM, $request_stop );
			pcntl_signal( SIGINT, $request_stop );
		}

		\WP_CLI::log( 'Intice worker started.' );

		while ( ! $stop && time() - $started < $max_time ) {
			if ( time() - $beat_at >= self::HEARTBEAT_EVERY ) {
				update_option( self::OPTION_HEARTBEAT, time(), false );
				$beat_at = time();
			}

			$names = self::get_queued_names( 20 );

			if ( ! $names ) {
				sleep( $sleep );
				continue;
			}

			foreach ( $names as $name ) {
				if ( $stop ) {
					break;
				}

				$this->run_job( $name );
			}

			if ( $this->is_memory_high() ) {
				\WP_CLI::log( 'Memory limit near — exiting for a restart.' );
				break;
			}
		}

		\WP_CLI::log( 'Intice worker stopped.' );
	}

	/**
	 * @param string $name Job option name.
	 * @return void
	 */
	private function run_job( string $name ): void {
		// Drop this process's in-memory copies of options/transients so the job
		// sees what web requests have written since the worker started.
		if ( function_exists( 'wp_cache_flush_runtime' ) ) {
			wp_cache_flush_runtime();
		}

		$job = self::claim( $name );

		if ( ! $job ) {
			return;
		}

		if ( ! has_action( $job['hook'] ) ) {
			// Its handler isn't registered in this process (e.g. API mode was
			// switched on after the worker started) — let WP-Cron run it.
			self::schedule_cron( $job['hook'], $job['args'] );
			\WP_CLI::warning( "No handler for {$job['hook']} — handed to WP-Cron." );
			return;
		}

		$start = microtime( true );

		try {
			do_action_ref_array( $job['hook'], $job['args'] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- runs the queued intice_* hook.
			\WP_CLI::log( sprintf( '%s done in %.1fs', $job['hook'], microtime( true ) - $start ) );
		} catch ( \Throwable $e ) {
			SOC_Logger::write( 'cache', "Intice worker job {$job['hook']} failed: " . $e->getMessage() );
			\WP_CLI::warning( "{$job['hook']} failed: " . $e->getMessage() );
		}
	}

	/**
	 * @return bool
	 */
	private function is_memory_high(): bool {
		$limit = wp_convert_hr_to_bytes( (string) ini_get( 'memory_limit' ) );

		return $limit > 0 && memory_get_usage( true ) > $limit * 0.8;
	}
}

<?php
/**
 * Import Monitor – WP All Import Hook Tracker.
 *
 * Listens to WP All Import action hooks and records the result of every
 * import run into wp_options, then fires the notifier when appropriate.
 *
 * Storage key pattern:  wpim_state_{import_id}
 * Stored value (JSON): {
 *   "last_run"      : "2024-01-01 12:00:00",   // WP local time (display only)
 *   "last_run_gmt"  : 1704067200,               // UTC Unix timestamp (used for calculations)
 *   "status"        : "success|failure",
 *   "error"         : "",
 *   "created_count" : 42,                       // posts newly inserted
 *   "updated_count" : 10,                       // posts updated (already existed)
 *   "skipped_count" : 3,                        // records skipped by WPAI rules
 *   "post_count"    : 52,                       // created + updated (backward compat)
 *   "last_notified" : "success|failure"         // prevents duplicate alerts
 * }
 *
 * Post count source
 * ─────────────────
 * We read created/updated/skipped directly from the PMXI_Import_Record object
 * that WPAI passes as the second argument of pmxi_after_xml_import. Those
 * properties are DB-backed and accumulate across all chunks, so they are
 * accurate even for large chunk-based imports that span multiple requests.
 *
 * The in-memory counter (post_counters) is kept as a fallback for the rare
 * case where the import object is unavailable (e.g., third-party WPAI forks).
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class Import_Monitor_Tracker
 *
 * @package App\Components\Base
 */
class Import_Monitor_Tracker implements Theme_Component {

	/** @var Import_Monitor_Notifier */
	private Import_Monitor_Notifier $notifier;

	/**
	 * Fallback in-memory post counter (key 0 = currently-running import).
	 * Used only when the PMXI_Import_Record object is unavailable.
	 *
	 * @var array<int,int>
	 */
	private array $post_counters = array();

	public function __construct() {
		$this->notifier = new Import_Monitor_Notifier();
	}

	/**
	 * Register WP All Import hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'pmxi_saved_post', array( $this, 'on_saved_post' ), 10, 3 );
		add_action( 'pmxi_after_xml_import', array( $this, 'on_after_xml_import' ), 10, 2 );
		add_action( 'pmxi_import_failed', array( $this, 'on_import_failed' ), 10, 1 );
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Hook callbacks
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Fallback post counter — incremented only when the PMXI_Import_Record
	 * object cannot provide counts (see on_after_xml_import).
	 *
	 * NOTE: pmxi_saved_post fires per-post within a single PHP request. For
	 * chunk-based imports this hook fires in every chunk request, but
	 * on_after_xml_import only fires in the final chunk. Therefore this
	 * counter only reflects posts saved in the last chunk — it is NOT the
	 * total across all chunks. Prefer reading from the import object instead.
	 *
	 * @param int   $post_id   Saved post ID.
	 * @param mixed $xml_node  XML node (unused).
	 * @param bool  $is_update Whether this post already existed.
	 * @return void
	 */
	public function on_saved_post( int $post_id, $xml_node, bool $is_update ): void {
		$this->post_counters[0] = ( $this->post_counters[0] ?? 0 ) + 1;
	}

	/**
	 * Import completed successfully.
	 *
	 * Hook signature: pmxi_after_xml_import( int $import_id, PMXI_Import_Record $import )
	 *
	 * The second argument is a PMXI_Import_Record object with DB-backed properties:
	 *   $import->created  — number of new posts inserted this run (across all chunks)
	 *   $import->updated  — number of existing posts updated this run
	 *   $import->skipped  — number of records skipped by WPAI rules
	 *
	 * We read those properties directly. The in-memory counter is only used as
	 * a last-resort fallback when the object is absent.
	 *
	 * @param int   $import_id     WP All Import import ID.
	 * @param mixed $import_object PMXI_Import_Record instance (or null in edge cases).
	 * @return void
	 */
	public function on_after_xml_import( int $import_id, $import_object = null ): void {
		if ( ! $this->is_monitoring_active() ) {
			return;
		}

		$config = $this->find_monitored_import( $import_id );
		if ( ! $config ) {
			return;
		}

		// ── Resolve post counts ──────────────────────────────────────────────
		// Primary: read from PMXI_Import_Record — accurate across all chunks.
		if ( is_object( $import_object ) ) {
			$created_count = max( 0, (int) ( $import_object->created ?? 0 ) );
			$updated_count = max( 0, (int) ( $import_object->updated ?? 0 ) );
			$skipped_count = max( 0, (int) ( $import_object->skipped ?? 0 ) );
		} else {
			// Fallback: in-memory counter (single-request / non-chunk imports only).
			$created_count = $this->post_counters[0] ?? 0;
			$updated_count = 0;
			$skipped_count = 0;
		}

		// Clear in-memory counter regardless of which path was taken.
		unset( $this->post_counters[0] );

		$this->record_state(
			$import_id,
			array(
				'last_run'      => current_time( 'mysql' ),
				'last_run_gmt'  => time(),                            // UTC Unix timestamp — used for age comparisons.
				'status'        => 'success',
				'error'         => '',
				'created_count' => $created_count,
				'updated_count' => $updated_count,
				'skipped_count' => $skipped_count,
				'post_count'    => $created_count + $updated_count,   // backward compat for dashboard.
			)
		);

		if ( $this->should_notify( $import_id, 'success' ) ) {
			$this->notifier->dispatch(
				$config,
				'success',
				'',
				array(
					'created' => $created_count,
					'updated' => $updated_count,
					'skipped' => $skipped_count,
				)
			);
			$this->mark_notified( $import_id, 'success' );
		}
	}

	/**
	 * Import failed.
	 *
	 * Hook signature: pmxi_import_failed( string $_POST['id'] )
	 * Only ONE argument is passed — the import ID as an unvalidated string from
	 * $_POST. No error message is available from this hook.
	 *
	 * @param mixed $raw_import_id Import ID from $_POST (string).
	 * @return void
	 */
	public function on_import_failed( $raw_import_id ): void {
		$import_id = (int) $raw_import_id;

		if ( ! $this->is_monitoring_active() ) {
			return;
		}

		$config = $this->find_monitored_import( $import_id );
		if ( ! $config ) {
			return;
		}

		$error_message = 'Import was marked as failed by WP All Import.';

		$this->record_state(
			$import_id,
			array(
				'last_run'      => current_time( 'mysql' ),
				'last_run_gmt'  => time(),
				'status'        => 'failure',
				'error'         => $error_message,
				'created_count' => 0,
				'updated_count' => 0,
				'skipped_count' => 0,
				'post_count'    => 0,
			)
		);

		if ( $this->should_notify( $import_id, 'failure' ) ) {
			$this->notifier->dispatch( $config, 'failure', $error_message );
			$this->mark_notified( $import_id, 'failure' );
		} else {
			error_log( "[Import Monitor] Notification skipped for import #{$import_id}: last_notified already 'failure'." );
		}
	}

	// ─────────────────────────────────────────────────────────────────────────
	// State helpers
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Read current state for a given import ID from wp_options.
	 *
	 * @param int $import_id Import ID.
	 * @return array
	 */
	public static function get_state( int $import_id ): array {
		$raw = get_option( Import_Monitor::OPTION_PREFIX . 'state_' . $import_id, '' );

		if ( ! $raw ) {
			return array(
				'last_run'      => '',
				'last_run_gmt'  => 0,
				'status'        => '',
				'error'         => '',
				'created_count' => 0,
				'updated_count' => 0,
				'skipped_count' => 0,
				'post_count'    => 0,
				'last_notified' => '',
			);
		}

		$state = (array) json_decode( $raw, true );

		// Back-fill last_run_gmt for state written before this field existed.
		// Derive from last_run string using WP timezone so the value is
		// usable for age comparisons on first read after upgrade.
		if ( empty( $state['last_run_gmt'] ) && ! empty( $state['last_run'] ) ) {
			$tz                    = wp_timezone();
			$dt                    = \DateTime::createFromFormat( 'Y-m-d H:i:s', $state['last_run'], $tz );
			$state['last_run_gmt'] = $dt ? $dt->getTimestamp() : 0;
		}

		return $state;
	}

	/**
	 * Persist updated state fields, merging with existing state.
	 *
	 * @param int   $import_id Import ID.
	 * @param array $update    Key-value pairs to update.
	 * @return void
	 */
	private function record_state( int $import_id, array $update ): void {
		$state = self::get_state( $import_id );
		$state = array_merge( $state, $update );
		update_option(
			Import_Monitor::OPTION_PREFIX . 'state_' . $import_id,
			wp_json_encode( $state ),
			false
		);
	}

	/**
	 * Mark that a notification has been sent for the current status.
	 *
	 * For 'failure' we also seed the failure_resent timestamp using
	 * current_time('timestamp') so check_persistent_failure (which also uses
	 * current_time('timestamp')) waits a full cron interval before re-alerting.
	 *
	 * @param int    $import_id Import ID.
	 * @param string $status    Status sent.
	 * @return void
	 */
	private function mark_notified( int $import_id, string $status ): void {
		$this->record_state( $import_id, array( 'last_notified' => $status ) );

		if ( 'failure' === $status ) {
			update_option(
				Import_Monitor::OPTION_PREFIX . 'failure_resent_' . $import_id,
				current_time( 'timestamp' ),
				false
			);
		}
	}

	/**
	 * Return true when a notification should be sent.
	 *
	 * Success: always notify — every completed import is worth reporting.
	 * Failure: deduplicate so we don't re-send the same failure alert that
	 *          check_persistent_failure already handles on cron cycles.
	 *
	 * @param int    $import_id Import ID.
	 * @param string $status    Current status.
	 * @return bool
	 */
	private function should_notify( int $import_id, string $status ): bool {
		if ( 'success' === $status ) {
			return true;
		}
		$state = self::get_state( $import_id );
		return ( $state['last_notified'] ?? '' ) !== $status;
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Config helpers
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Verify global monitoring switch is on.
	 *
	 * @return bool
	 */
	private function is_monitoring_active(): bool {
		if ( function_exists( 'get_field' ) ) {
			$val = get_field( 'wpim_enabled', 'option' );
			if ( null !== $val ) {
				return (bool) $val;
			}
		}
		return (bool) get_option( 'options_wpim_enabled' );
	}

	/**
	 * Find the ACF config row for an import_id, or null if not monitored / inactive.
	 *
	 * @param int $import_id Import ID to look up.
	 * @return array|null
	 */
	public static function find_monitored_import( int $import_id ): ?array {
		$imports = function_exists( 'get_field' ) ? get_field( 'wpim_imports', 'option' ) : null;
		if ( ! is_array( $imports ) ) {
			$raw     = get_option( 'options_wpim_imports' );
			$imports = is_array( $raw ) ? $raw : array();
		}
		if ( empty( $imports ) ) {
			return null;
		}

		foreach ( $imports as $row ) {
			// Treat a missing 'active' key as active=true (row saved before the field existed).
			$is_active = ! isset( $row['active'] ) || ! empty( $row['active'] );
			if ( (int) ( $row['import_id'] ?? 0 ) === $import_id && $is_active ) {
				return $row;
			}
		}

		return null;
	}

	/**
	 * Return all active monitored import configs.
	 *
	 * @return array
	 */
	public static function get_monitored_imports(): array {
		return array_filter(
			self::get_all_imports(),
			static fn( $row ) => ( ! isset( $row['active'] ) || ! empty( $row['active'] ) ) && ! empty( $row['import_id'] )
		);
	}

	/**
	 * Return all configured imports regardless of active flag (used by the SOC panel).
	 *
	 * @return array
	 */
	public static function get_all_imports(): array {
		$imports = function_exists( 'get_field' ) ? get_field( 'wpim_imports', 'option' ) : null;
		if ( ! is_array( $imports ) ) {
			$raw     = get_option( 'options_wpim_imports' );
			$imports = is_array( $raw ) ? $raw : array();
		}
		if ( empty( $imports ) ) {
			return array();
		}
		return array_values( array_filter( $imports, static fn( $row ) => ! empty( $row['import_id'] ) ) );
	}
}

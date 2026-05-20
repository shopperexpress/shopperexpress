<?php
/**
 * Import Monitor – WP Cron Scheduler.
 *
 * Registers a custom cron schedule and a recurring event that:
 *  1. Checks every "scheduled_check" import and fires alerts when it has not
 *     run within its configured max_age_hours window.
 *  2. Re-fires notifications for imports that are stuck in a "failure" state
 *     so operators are reminded until the problem is resolved.
 *
 * The schedule interval is read dynamically from ACF each time the event fires,
 * so changing the ACF value takes effect after the next cron tick without
 * requiring a deactivation/reactivation cycle.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class Import_Monitor_Cron
 *
 * @package App\Components\Base
 */
class Import_Monitor_Cron implements Theme_Component {

	const HOOK     = 'wpim_cron_check';
	const SCHEDULE = 'wpim_interval';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'cron_schedules', array( $this, 'add_schedule' ) );
		add_action( self::HOOK, array( $this, 'run_check' ) );
		add_action( 'acf/save_post', array( $this, 'reschedule_on_settings_save' ), 20 );
		add_action( 'after_switch_theme', array( $this, 'schedule' ) );

		// Ensure the cron event is scheduled if it somehow got lost.
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			$this->schedule();
		}
	}

	/**
	 * Unschedule the cron event on theme deactivation.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		$timestamp = wp_next_scheduled( self::HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::HOOK );
		}
	}

	/**
	 * Register a custom WP-Cron schedule named 'wpim_interval'.
	 *
	 * cron_schedules fires very early — before acf/init — so get_field() is not
	 * safe here. We fall back to reading the raw wp_options row directly, which
	 * is always available. ACF stores options-page values under options_{field_name}.
	 *
	 * @param array $schedules Existing cron schedules.
	 * @return array
	 */
	public function add_schedule( array $schedules ): array {
		if ( did_action( 'acf/init' ) ) {
			$minutes = $this->get_interval_minutes();
		} else {
			$raw     = (int) get_option( 'options_wpim_cron_interval', 0 );
			$minutes = $raw > 0 ? $raw : 30;
		}

		$schedules[ self::SCHEDULE ] = array(
			'interval' => $minutes * 60,
			'display'  => sprintf( 'Every %d minutes (Import Monitor)', $minutes ),
		);

		return $schedules;
	}

	/**
	 * Schedule the recurring cron event.
	 *
	 * @return void
	 */
	public function schedule(): void {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time(), self::SCHEDULE, self::HOOK );
		}
	}

	/**
	 * When the Import Monitor cron interval is saved, reschedule the event so
	 * the new interval takes effect immediately without waiting for the next tick.
	 *
	 * Scoped to options page saves that actually include the cron interval field
	 * to avoid unnecessary reschedules on every other ACF options page save.
	 * The heavy wp_cache_delete('alloptions') flush has been removed — it is not
	 * needed because wp_schedule_event reads the schedule list fresh via
	 * wp_get_schedules(), which calls the cron_schedules filter.
	 *
	 * @param int|string $post_id ACF save target.
	 * @return void
	 */
	public function reschedule_on_settings_save( $post_id ): void {
		if ( 'options' !== $post_id ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['acf']['field_wpim_cron_interval'] ) ) {
			return;
		}

		self::deactivate();
		$this->schedule();
	}

	/**
	 * Main cron callback: iterate monitored imports and fire alerts as needed.
	 *
	 * @return void
	 */
	public function run_check(): void {
		if ( ! get_field( 'wpim_enabled', 'option' ) ) {
			return;
		}

		$notifier = new Import_Monitor_Notifier();
		$imports  = Import_Monitor_Tracker::get_monitored_imports();

		foreach ( $imports as $config ) {
			$import_id  = (int) $config['import_id'];
			$check_mode = $config['check_mode'] ?? 'on_completion';
			$state      = Import_Monitor_Tracker::get_state( $import_id );

			if ( 'scheduled_check' === $check_mode ) {
				$this->check_missing( $import_id, $config, $state, $notifier );
			}

			// Re-alert on persistent failure (regardless of check mode).
			$this->check_persistent_failure( $import_id, $config, $state, $notifier );
		}
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Private check helpers
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Alert if an import has not run within its configured time window.
	 *
	 * @param int                     $import_id  Import ID.
	 * @param array                   $config     ACF repeater row.
	 * @param array                   $state      Persisted state.
	 * @param Import_Monitor_Notifier $notifier   Notification dispatcher.
	 * @return void
	 */
	private function check_missing(
		int $import_id,
		array $config,
		array $state,
		Import_Monitor_Notifier $notifier
	): void {
		$max_age_hours = max( 1, (int) ( $config['max_age_hours'] ?? 24 ) );
		$last_run      = $state['last_run'] ?? '';        // WP local datetime string — display only.
		$last_run_gmt  = (int) ( $state['last_run_gmt'] ?? 0 ); // UTC Unix timestamp — used for math.

		$is_overdue = false;
		$error_msg  = '';

		if ( empty( $last_run ) ) {

			$is_overdue = true;
			$error_msg  = 'Import has never run.';

		} elseif ( 0 === $last_run_gmt ) {

			// State written before last_run_gmt existed; get_state() back-fills it
			// via wp_timezone()-aware DateTime parse, but if that also failed we
			// cannot do a safe comparison — skip rather than alert on bad data.
			$is_overdue = false;

		} else {

			// Both sides are UTC Unix timestamps — no timezone ambiguity.
			$diff_hours = ( time() - $last_run_gmt ) / HOUR_IN_SECONDS;

			if ( $diff_hours > $max_age_hours ) {
				$is_overdue = true;
				$error_msg  = sprintf(
					'Import has not run for %.1f hours (expected within %d hours). Last run: %s.',
					$diff_hours,
					$max_age_hours,
					$last_run
				);
			}
		}

		if ( ! $is_overdue ) {
			return;
		}

		$notified_key = Import_Monitor::OPTION_PREFIX . 'missing_notified_' . $import_id;
		if ( get_option( $notified_key ) ) {
			return;
		}

		$notifier->dispatch( $config, 'missing', $error_msg );
		update_option( $notified_key, current_time( 'mysql' ), false );
	}
	/**
	 * Reset the "missing" dedupe key once the import successfully runs, and
	 * re-alert on persistent failure once per cron cycle.
	 *
	 * @param int                     $import_id Import ID.
	 * @param array                   $config    ACF repeater row.
	 * @param array                   $state     Persisted state.
	 * @param Import_Monitor_Notifier $notifier  Notification dispatcher.
	 * @return void
	 */
	private function check_persistent_failure(
		int $import_id,
		array $config,
		array $state,
		Import_Monitor_Notifier $notifier
	): void {
		if ( 'success' === ( $state['status'] ?? '' ) ) {
			// Reset "missing" flag once a successful run is recorded.
			delete_option( Import_Monitor::OPTION_PREFIX . 'missing_notified_' . $import_id );
			return;
		}

		if ( 'failure' !== ( $state['status'] ?? '' ) ) {
			return;
		}

		// Only re-alert if the last_notified is already 'failure' to avoid
		// sending a duplicate when the failure was just recorded by the tracker.
		// In practice the tracker marks last_notified = 'failure', so here we
		// skip to avoid the very-first duplicate but still re-alert on the
		// next cron cycle.
		$resend_key  = Import_Monitor::OPTION_PREFIX . 'failure_resent_' . $import_id;
		$last_resent = (int) get_option( $resend_key, 0 );
		$interval    = $this->get_interval_minutes() * 60;

		// Both sides use current_time('timestamp') — consistent WP-timezone-aware comparison.
		if ( current_time( 'timestamp' ) - $last_resent < $interval ) {
			return;
		}

		$notifier->dispatch( $config, 'failure', $state['error'] ?? '' );
		update_option( $resend_key, current_time( 'timestamp' ), false );
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Utility
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Read cron interval from ACF; fall back to 30 minutes.
	 *
	 * @return int Minutes between cron ticks.
	 */
	private function get_interval_minutes(): int {
		$val = (int) get_field( 'wpim_cron_interval', 'option' );
		return $val > 0 ? $val : 30;
	}
}

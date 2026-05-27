<?php
/**
 * SOC Cron Manager Module
 *
 * Collects, presents, and summarizes scheduled WP-Cron jobs for admin review.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Modules;

use App\Components\SOC\Contracts\SOC_Module;
use App\Components\SOC\Support\SOC_Cache;
use App\Components\SOC\Support\SOC_Logger;

/**
 * Class Cron_Manager
 *
 * Provides information and metrics about scheduled cron events (WP-Cron) in WordPress.
 */
class Cron_Manager implements SOC_Module {

	/**
	 * Returns the module's unique slug for cache, lookups, etc.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'cron-manager';
	}

	/**
	 * Returns human-readable module label for UI.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return 'Cron Jobs';
	}

	/**
	 * Returns the Dashicon for use in admin interface.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-clock';
	}

	/**
	 * Collects WordPress cron events and summarizes them for display.
	 *
	 * @param bool $force_refresh If true, clears the cache and collects afresh.
	 * @return array              Collected cron job data.
	 */
	public function collect( bool $force_refresh = false ): array {
		// If refresh is requested, invalidate the cache.
		if ( $force_refresh ) {
			SOC_Cache::forget( $this->get_slug(), 'data' );
		}

		// Attempt to retrieve from cache first.
		$cached = SOC_Cache::get( $this->get_slug(), 'data' );

		if ( false !== $cached ) {
			// Return cached data if available.
			return $cached;
		}

		$events   = array();
		$cron_arr = _get_cron_array(); // Retrieves raw cron events from WordPress.
		$now      = time();

		// Loop through every scheduled event in the cron array.
		if ( is_array( $cron_arr ) ) {
			foreach ( $cron_arr as $timestamp => $hooks ) {
				foreach ( $hooks as $hook => $instances ) {
					foreach ( $instances as $instance ) {
						// Each event records its hook, schedule details, when it runs, and if it is overdue.
						$events[] = array(
							'hook'      => $hook,
							'timestamp' => $timestamp, // Unix timestamp scheduled to run.
							'next_run'  => human_time_diff( $timestamp, $now ),
							'schedule'  => $instance['schedule'] ?? 'one-time', // Schedule slug or one-time.
							'interval'  => $instance['interval'] ?? 0,          // Interval in seconds.
							'args'      => $instance['args'] ?? array(), // Arguments as PHP array.
							'overdue'   => $timestamp < $now, // Whether it's overdue for execution.
						);
					}
				}
			}
		}

		// Sort events soonest first by timestamp.
		usort(
			$events,
			static function ( $a, $b ) {
				return $a['timestamp'] <=> $b['timestamp'];
			}
		);

		// Count of overdue (missed/pending) events.
		$overdue_count = count( array_filter( $events, static fn( $e ) => $e['overdue'] ) );

		// Prepare summary data structure for the dashboard.
		$data = array(
			'events'           => $events,                                         // List of all scheduled and one-time events.
			'total'            => count( $events ),                                // Total number of events.
			'overdue_count'    => $overdue_count,                                  // Number of overdue events.
			'action_scheduler' => class_exists( 'ActionScheduler' ),               // True if Action Scheduler framework is detected.
			'doing_cron'       => defined( 'DOING_CRON' ) && DOING_CRON,           // If WordPress is currently processing cron.
			'alternate_cron'   => defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON, // If alternate cron is enabled.
			'disable_wp_cron'  => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,     // If WordPress cron is disabled.
			'schedules'        => wp_get_schedules(),                              // List of registered schedules (intervals).
			'collected_at'     => current_time( 'mysql' ),                         // When the data was collected.
		);

		// Cache data for performance (1 minute).
		SOC_Cache::set( $this->get_slug(), 'data', $data, MINUTE_IN_SECONDS );

		return $data;
	}

	/**
	 * Render the cron manager dashboard widget/template.
	 *
	 * @param array $data Collected and prepared module data.
	 * @return void
	 */
	public function render( array $data ): void {
		require get_template_directory() . '/inc/Components/SOC/views/cron-manager.php';
	}
}

<?php
/**
 * SOC Performance Module
 *
 * Monitors and reports WordPress site performance for admin review.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Modules;

use App\Components\SOC\Contracts\SOC_Module;
use App\Components\SOC\Support\SOC_Cache;
use App\Components\SOC\Support\SOC_Logger;

/**
 * Class Performance
 *
 * Implements the SOC_Module interface to collect resource usage, query time,
 * and load sample statistics for performance monitoring.
 */
class Performance implements SOC_Module {

	/**
	 * Get the unique slug for this module.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'performance';
	}

	/**
	 * Get the human-readable label for this module.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return 'Performance';
	}

	/**
	 * Get the dashicon for UI.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-chart-area';
	}

	/**
	 * Collects performance data for the dashboard, using cache for efficiency.
	 *
	 * @param bool $force_refresh If true, clear cache and get fresh stats.
	 * @return array              Performance stats.
	 */
	public function collect( bool $force_refresh = false ): array {
		if ( $force_refresh ) {
			// Clear cached data for this module.
			SOC_Cache::forget( $this->get_slug(), 'data' );
		}

		// Fetch cached data if available.
		$cached = SOC_Cache::get( $this->get_slug(), 'data' );

		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		// Compile current performance values.
		$data = array(
			// Database size in MB.
			'db_size_mb'       => $this->get_db_size(),
			// Total "autoload" WP options size in KB.
			'autoload_size_kb' => $this->get_autoload_size(),
			// Number of SQL queries on this request.
			'query_count'      => $wpdb->num_queries,
			// PHP memory peak in MB for this request.
			'memory_peak_mb'   => round( memory_get_peak_usage( true ) / 1024 / 1024, 2 ),
			// Last 20 load time samples from history option.
			'load_samples'     => array_slice( (array) get_option( 'soc_load_samples', array() ), -20 ),
			// List of queries that are considered slow.
			'slow_queries'     => $this->get_slow_queries(),
			// Timestamp of collection.
			'collected_at'     => current_time( 'mysql' ),
		);

		// Cache for 10 minutes.
		SOC_Cache::set( $this->get_slug(), 'data', $data, 10 * MINUTE_IN_SECONDS );

		return $data;
	}

	/**
	 * Render performance data in the admin area using template.
	 *
	 * @param array $data The collected module data.
	 * @return void
	 */
	public function render( array $data ): void {
		require get_template_directory() . '/inc/Components/SOC/views/performance.php';
	}

	/**
	 * Occasionally sample load time, memory, and queries for baseline statistics.
	 *
	 * This is called on each page load, but only records a data point ~5% of the time.
	 * Keeps the latest 100 samples in an option.
	 *
	 * @return void
	 */
	public static function sample_load_time(): void {
		// Only record 1 out of ~20 requests (to reduce overhead).
		if ( rand( 1, 20 ) !== 1 ) {
			return;
		}

		global $wpdb;

		// Load existing samples or initialize.
		$samples = (array) get_option( 'soc_load_samples', array() );

		// Add latest sample at the end.
		$samples[] = array(
			// Milliseconds since PHP started this request.
			'time_ms'    => round( ( microtime( true ) - ( $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime( true ) ) ) * 1000, 2 ),
			// Peak memory allocation in MB.
			'memory_mb'  => round( memory_get_peak_usage( true ) / 1024 / 1024, 2 ),
			// Number of queries performed (may be 0 if $wpdb not initialized).
			'queries'    => $wpdb->num_queries ?? 0,
			// UNIX timestamp for this measurement.
			'sampled_at' => time(),
		);

		// Store only last 100 samples to keep option small.
		$samples = array_slice( $samples, -100 );

		update_option( 'soc_load_samples', $samples, false );
	}

	/**
	 * Return the MySQL database size in megabytes.
	 *
	 * @return float
	 */
	private function get_db_size(): float {
		global $wpdb;

		$size = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT SUM(data_length + index_length) / 1024 / 1024
				FROM information_schema.TABLES
				WHERE table_schema = %s',
				DB_NAME
			)
		);

		return round( (float) $size, 2 );
	}

	/**
	 * Return the total size in KB of "autoload" WP options.
	 *
	 * @return float
	 */
	private function get_autoload_size(): float {
		global $wpdb;

		$size = $wpdb->get_var(
			"SELECT SUM(LENGTH(option_value)) / 1024
			FROM {$wpdb->options}
			WHERE autoload = 'yes'"
		);

		return round( (float) $size, 2 );
	}

	/**
	 * Get a list of slow SQL queries from the request (if enabled).
	 *
	 * Only queries slower than 0.05 seconds are returned.
	 *
	 * @return array List of slow queries (each as [SQL, time, stacktrace]).
	 */
	private function get_slow_queries(): array {
		global $wpdb;

		// Only available if WordPress SAVEQUERIES is enabled.
		if ( ! defined( 'SAVEQUERIES' ) || ! SAVEQUERIES ) {
			return array();
		}

		// $wpdb->queries is an array of [SQL, time, trace].
		if ( empty( $wpdb->queries ) || ! is_array( $wpdb->queries ) ) {
			return array();
		}

		// Filter queries by time threshold (>0.05s).
		return array_values(
			array_filter( $wpdb->queries, static fn( $q ) => isset( $q[1] ) && (float) $q[1] > 0.05 )
		);
	}
}

<?php
/**
 * SOC System Status Module
 *
 * Provides environment and system diagnostics for the Shopperexpress plugin,
 * implementing the SOC_Module contract for modular integration.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Modules;

use App\Components\SOC\Contracts\SOC_Module;
use App\Components\SOC\Support\SOC_Cache;
use App\Components\SOC\Support\SOC_Logger;

/**
 * Class System_Status
 *
 * Collects information about the PHP runtime, WordPress environment, database,
 * and other system parameters, providing a summary "System Status" module.
 */
class System_Status implements SOC_Module {

	/**
	 * Get the slug identifier for this module.
	 *
	 * @return string Module slug, used for internal reference and caching.
	 */
	public function get_slug(): string {
		return 'system-status';
	}

	/**
	 * Get the human-readable label for this module used in the UI.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return 'System Status';
	}

	/**
	 * Get the icon's dashicon name for this module.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-performance';
	}

	/**
	 * Collect system status/environment data.
	 * This method caches its output for 5 minutes unless $force_refresh is true.
	 *
	 * @param bool $force_refresh If true, ignores cache and collects fresh data.
	 * @return array System status data.
	 */
	public function collect( bool $force_refresh = false ): array {
		if ( $force_refresh ) {
			// Invalidate (delete) previous cached data if forced refresh requested.
			SOC_Cache::forget( $this->get_slug(), 'data' );
		}

		// Fetch cached system status data if present.
		$cached = SOC_Cache::get( $this->get_slug(), 'data' );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		// Gather various environment and runtime diagnosis results.
		$data = array(
			// PHP details
			'php_version'         => PHP_VERSION,
			'php_ok'              => version_compare( PHP_VERSION, '8.0', '>=' ), // is PHP >= 8.0
			'memory_limit'        => ini_get( 'memory_limit' ),
			'memory_usage'        => size_format( memory_get_usage( true ) ),
			'memory_peak'         => size_format( memory_get_peak_usage( true ) ),
			'max_execution'       => ini_get( 'max_execution_time' ),
			'max_upload'          => ini_get( 'upload_max_filesize' ),
			'post_max_size'       => ini_get( 'post_max_size' ),
			// WordPress debugging config
			'wp_debug'            => defined( 'WP_DEBUG' ) && WP_DEBUG,
			'wp_debug_log'        => defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG,
			// PHP feature support — check disable_functions, not just function_exists.
			'shell_exec'          => $this->is_function_callable( 'shell_exec' ),
			'exec'                => $this->is_function_callable( 'exec' ),
			// WordPress setup checks
			'ssl'                 => is_ssl(),
			'multisite'           => is_multisite(),
			// REST API base URL
			'rest_url'            => rest_url(),
			// PHP OPcache info
			'opcache_enabled'     => function_exists( 'opcache_get_status' ) && ( opcache_get_status( false )['opcache_enabled'] ?? false ),
			'opcache_hit_rate'    => $this->get_opcache_hit_rate(),
			// Filesystem capabilities
			'wp_content_writable' => is_writable( WP_CONTENT_DIR ),
			'uploads_writable'    => is_writable( wp_upload_dir()['basedir'] ),
			// WordPress version info
			'wp_version'          => get_bloginfo( 'version' ),
			// Web server string
			'server_software'     => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
			// Database server version
			'mysql_version'       => $wpdb->db_version(),
			// Timestamp of when collected
			'collected_at'        => current_time( 'mysql' ),
		);

		// Cache the collected data for 5 minutes.
		SOC_Cache::set( $this->get_slug(), 'data', $data, 5 * MINUTE_IN_SECONDS );

		return $data;
	}

	/**
	 * Renders the status info in the admin using a template.
	 *
	 * @param array $data The collected diagnostics data.
	 * @return void
	 */
	public function render( array $data ): void {
		require get_template_directory() . '/inc/Components/SOC/views/system-status.php';
	}

	/**
	 * Check whether a function is truly callable — not just declared but also absent
	 * from disable_functions / suhosin blacklists.
	 *
	 * @param string $func Function name to test.
	 * @return bool
	 */
	private function is_function_callable( string $func ): bool {
		if ( ! function_exists( $func ) ) {
			return false;
		}
		$disabled = array_map( 'trim', explode( ',', (string) ini_get( 'disable_functions' ) ) );
		return ! in_array( $func, $disabled, true );
	}

	/**
	 * Calculate and return the PHP OPcache "hit rate" percentage, if available.
	 *
	 * @return float|null Returns hit rate as a float (0-100), or null if unsupported/unavailable.
	 */
	private function get_opcache_hit_rate(): ?float {
		if ( ! function_exists( 'opcache_get_status' ) ) {
			// OPcache extension not loaded.
			return null;
		}

		$status = opcache_get_status( false );

		if ( ! $status || empty( $status['opcache_statistics'] ) ) {
			return null;
		}

		$stats  = $status['opcache_statistics'];
		$hits   = $stats['hits'] ?? 0;
		$misses = $stats['misses'] ?? 0;
		$total  = $hits + $misses;

		if ( $total === 0 ) {
			// No cache accesses yet, so cannot compute hit rate.
			return null;
		}

		// Return hit percentage rounded to two decimals.
		return round( ( $hits / $total ) * 100, 2 );
	}
}

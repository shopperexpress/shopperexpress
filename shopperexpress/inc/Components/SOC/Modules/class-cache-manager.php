<?php
/**
 * SOC Cache Manager Module
 *
 * Handles detection, display, and purging of various cache layers
 * (object cache, page cache, transients, PHP OPcache) for the Shopperexpress admin UI.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Modules;

use App\Components\SOC\Contracts\SOC_Module;
use App\Components\SOC\Support\SOC_Cache;
use App\Components\SOC\Support\SOC_Logger;
use App\Components\SOC\Support\Cache_Registry;

/**
 * Class Cache_Manager
 *
 * Implements caching-related UI and actions for the SOC dashboard.
 */
class Cache_Manager implements SOC_Module {

	/**
	 * Get the unique module slug for referencing in UI, cache keys, etc.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'cache-manager';
	}

	/**
	 * Human-friendly module label, shown in admin UI.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return 'Cache';
	}

	/**
	 * Module icon for the admin dashboard (dashicon name).
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-performance';
	}

	/**
	 * Gather and return information about cache layers and states, with caching.
	 *
	 * @param bool $force_refresh Ignore cache and rebuild (e.g., after purge/manual refresh).
	 * @return array
	 */
	public function collect( bool $force_refresh = false ): array {
		// Invalidate cache if forced.
		if ( $force_refresh ) {
			SOC_Cache::forget( $this->get_slug(), 'data' );
		}

		// Return cached data if present to save resources.
		$cached = SOC_Cache::get( $this->get_slug(), 'data' );

		if ( false === $cached ) {
			// Detect which page cache, if any, is active.
			$page_cache = $this->detect_page_cache();

			$cache_layers = array(
				array(
					'name'        => 'Object Cache',
					'active'      => wp_using_ext_object_cache(),
					'description' => 'External object cache (Redis, Memcached, etc.)',
				),
				array(
					'name'        => 'Page Cache',
					'active'      => $page_cache !== 'None detected',
					'description' => $page_cache,
				),
				array(
					'name'        => 'Transients',
					'active'      => true,
					'description' => 'WordPress transient API (DB or object cache)',
				),
				array(
					'name'        => 'OPcache',
					'active'      => function_exists( 'opcache_get_status' ) && ( opcache_get_status( false )['opcache_enabled'] ?? false ),
					'description' => 'PHP OPcache (bytecode cache)',
				),
			);

			$cached = array(
				'object_cache_active' => wp_using_ext_object_cache(),
				'transient_count'     => $this->count_transients(),
				'page_cache_detected' => $page_cache,
				'last_purge'          => get_option( 'soc_last_cache_purge', 'Never' ),
				'cache_layers'        => $cache_layers,
				'api_cache_enabled'   => (bool) get_field( 'cache_api', 'options' ),
				'post_type_caches'    => $this->collect_post_type_caches(),
				'collected_at'        => current_time( 'mysql' ),
			);

			SOC_Cache::set( $this->get_slug(), 'data', $cached, 2 * MINUTE_IN_SECONDS );
		}

		// Always inject a fresh log — never serve stale entries from the 2-min cache.
		$cached['cache_log'] = (array) get_option( 'soc_cache_log', array() );

		return $cached;
	}

	/**
	 * Renders the cache manager dashboard view.
	 *
	 * @param array $data Module data from collect()
	 */
	public function render( array $data ): void {
		require get_template_directory() . '/inc/Components/SOC/views/cache-manager.php';
	}

	/**
	 * Triggers a full purge of all cache layers: object cache, transients, page cache.
	 *
	 * @return array Results/metrics for each cache purge action.
	 */
	public function clear_all(): array {
		$results = array(
			'object_cache' => wp_cache_flush(),
			'transients'   => $this->clear_transients(),
			'page_cache'   => $this->clear_page_cache(),
		);

		// Track last cache purge time and log event for admins/historical reference.
		update_option( 'soc_last_cache_purge', current_time( 'mysql' ) );
		SOC_Cache::forget_module( $this->get_slug() );
		SOC_Logger::write( 'cache', 'Full cache purge by admin' );

		return $results;
	}

	/**
	 * Get the total number of transients stored in the WP options table.
	 *
	 * @return int Count of expired/pending transients
	 */
	private function count_transients(): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%'"
		);
	}

	/**
	 * Attempt to detect which, if any, mainstream page cache plugin is active.
	 *
	 * @return string Human-friendly page cache label, or "None detected"
	 */
	private function detect_page_cache(): string {
		if ( defined( 'W3TC' ) ) {
			return 'W3 Total Cache';
		}

		if ( defined( 'WP_ROCKET_VERSION' ) ) {
			return 'WP Rocket';
		}

		if ( defined( 'WPFC_MAIN_PATH' ) ) {
			return 'WP Fastest Cache';
		}

		if ( function_exists( 'rocket_clean_domain' ) ) {
			return 'WP Rocket';
		}

		if ( class_exists( 'LiteSpeed_Cache' ) ) {
			return 'LiteSpeed Cache';
		}

		return 'None detected';
	}

	/**
	 * Remove all transients (both regular and site transients) from the options table.
	 *
	 * @return int Number of deleted rows
	 */
	private function clear_transients(): int {
		global $wpdb;

		return (int) $wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%' OR option_name LIKE '_site_transient_%'"
		);
	}

	/**
	 * Attempt to force a purge of known page cache plugins, if present.
	 *
	 * @return bool True if a flushing plugin was found and flushed, false otherwise.
	 */
	private function clear_page_cache(): bool {
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
			return true;
		}

		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
			return true;
		}

		return false;
	}

	// ─── Post-type cache methods ──────────────────────────────────────────────

	/**
	 * Collect status for each registered cacheable post type.
	 * Results are NOT wrapped in a transient — they're embedded in the parent
	 * collect() result which is cached at the module level for 2 minutes.
	 */
	private function collect_post_type_caches(): array {
		$post_types = Cache_Registry::get_post_types();
		$result     = array();

		foreach ( $post_types as $post_type ) {
			$result[] = Cache_Registry::describe( $post_type );
		}

		return $result;
	}

	/**
	 * Clear all transients for a single post type and log the action.
	 *
	 * @param string $post_type
	 * @return array{deleted: int, post_type: string, cleared_by: string, cleared_at: string}
	 */
	public function clear_post_type_cache( string $post_type ): array {
		$deleted    = Cache_Registry::clear( $post_type );
		$user       = wp_get_current_user();
		$cleared_by = $user->user_email ?: 'unknown';

		// Record who triggered this build so Cache_Registry can attribute the result.
		update_option(
			'soc_pt_generated_by_' . $post_type,
			array(
				'user' => $cleared_by,
				'at'   => time(),
			),
			false
		);

		// Trigger cache regeneration via the existing REST endpoint (non-blocking).
		$regen_url = rest_url( 'v1/vehicles/' . rawurlencode( $post_type ) );
		wp_remote_get(
			$regen_url,
			array(
				'timeout'   => 0.01,
				'blocking'  => false,
				'sslverify' => false,
				'body'      => array( 'clear' => '1' ),
			)
		);

		$log = (array) get_option( 'soc_cache_log', array() );
		array_unshift(
			$log,
			array(
				'action'    => 'clear+regen',
				'post_type' => $post_type,
				'deleted'   => $deleted,
				'user'      => $cleared_by,
				'time'      => current_time( 'mysql' ),
			)
		);
		update_option( 'soc_cache_log', array_slice( $log, 0, 50 ), false );

		SOC_Logger::write(
			'cache',
			sprintf(
				'PT cache cleared+regen triggered: %s | deleted: %d | by: %s',
				$post_type,
				$deleted,
				$cleared_by
			)
		);

		SOC_Cache::forget( $this->get_slug(), 'data' );

		return array(
			'deleted'    => $deleted,
			'post_type'  => $post_type,
			'cleared_by' => $cleared_by,
			'cleared_at' => current_time( 'mysql' ),
			'regen'      => 'triggered',
		);
	}

	/**
	 * Flush only expired transients across all registered post types.
	 *
	 * @return array{deleted: int, flushed_at: string}
	 */
	public function flush_expired_transients(): array {
		$deleted = Cache_Registry::flush_all_expired();

		$user = wp_get_current_user();
		SOC_Logger::write(
			'cache',
			sprintf(
				'Expired transients flushed | deleted: %d | by: %s',
				$deleted,
				$user->user_login ?? 'unknown'
			)
		);

		SOC_Cache::forget( $this->get_slug(), 'data' );

		return array(
			'deleted'    => $deleted,
			'flushed_at' => current_time( 'mysql' ),
		);
	}

	/**
	 * Return the raw transient keys for a post type (for admin inspection).
	 *
	 * @param string $post_type
	 * @return array
	 */
	public function get_post_type_keys( string $post_type ): array {
		return Cache_Registry::list_keys( $post_type );
	}

	/**
	 * Get the cache activity log (last 50 entries).
	 */
	public function get_cache_log(): array {
		return (array) get_option( 'soc_cache_log', array() );
	}
}

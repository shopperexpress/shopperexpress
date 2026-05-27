<?php
/**
 * SOC Maintenance Module
 *
 * Provides admin-configurable maintenance (site offline) mode with IP whitelisting and logging.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Modules;

use App\Components\SOC\Contracts\SOC_Module;
use App\Components\SOC\Support\SOC_Cache;
use App\Components\SOC\Support\SOC_Logger;

/**
 * Class Maintenance
 *
 * Implements the SOC_Module interface for "maintenance mode" functionality,
 * enabling site administrators to take the site offline except for admins
 * or specified IP addresses, with UI and status caching.
 */
class Maintenance implements SOC_Module {

	/**
	 * Constructor: Register hook to check and maybe display the maintenance notice.
	 */
	public function __construct() {
		add_action( 'wp_loaded', array( $this, 'maybe_show_maintenance' ) );
	}

	/**
	 * Return the unique slug for this module.
	 *
	 * @return string Module slug for reference in UI/cache.
	 */
	public function get_slug(): string {
		return 'maintenance';
	}

	/**
	 * Return the human-friendly label for use in admin UI.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return 'Maintenance';
	}

	/**
	 * Return the Dashicon slug for the module icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-hammer';
	}

	/**
	 * Collect maintenance status & settings, caching result for 1 minute.
	 *
	 * @param bool $force_refresh Ignore cache and fetch fresh values
	 * @return array Module status data for use in UI or checks
	 */
	public function collect( bool $force_refresh = false ): array {
		if ( $force_refresh ) {
			// Force a cache invalidation when requested.
			SOC_Cache::forget( $this->get_slug(), 'data' );
		}

		// Check cached maintenance data first for performance.
		$cached = SOC_Cache::get( $this->get_slug(), 'data' );

		if ( false !== $cached ) {
			return $cached;
		}

		// Gather maintenance state and config
		$data = array(
			'active'        => (bool) get_option( 'soc_maintenance_active', false ),
			'whitelist_ips' => get_option( 'soc_maintenance_ips', '' ),
			'enabled_since' => get_option( 'soc_maintenance_since', '' ),
			'collected_at'  => current_time( 'mysql' ),
		);

		// Store in transient cache for performance (1 min).
		SOC_Cache::set( $this->get_slug(), 'data', $data, MINUTE_IN_SECONDS );

		return $data;
	}

	/**
	 * Render the admin UI for maintenance module.
	 *
	 * @param array $data Module/maintenance status data from collect()
	 */
	public function render( array $data ): void {
		require get_template_directory() . '/inc/Components/SOC/views/maintenance.php';
	}

	/**
	 * Enable or disable maintenance mode and adjust settings accordingly.
	 *
	 * @param bool   $enable        True to enable, false to disable maintenance mode
	 * @param string $whitelist_ips (Optional) Newline-separated list of IPs to allow access during maintenance
	 *
	 * @return void
	 */
	public function toggle( bool $enable, string $whitelist_ips = '' ): void {
		// Save settings/options.
		update_option( 'soc_maintenance_active', $enable );
		update_option( 'soc_maintenance_ips', sanitize_textarea_field( $whitelist_ips ) );

		if ( $enable ) {
			// Mark the maintenance start time
			update_option( 'soc_maintenance_since', current_time( 'mysql' ) );
		} else {
			// Clean up the start time if disabling
			delete_option( 'soc_maintenance_since' );
		}

		// Invalidate cache and log the change for audits/admins.
		SOC_Cache::forget( $this->get_slug(), 'data' );
		SOC_Logger::write( 'general', 'Maintenance mode ' . ( $enable ? 'enabled' : 'disabled' ) );
	}

	/**
	 * If maintenance is enabled, block access for non-admins/outside whitelisted IPs.
	 *
	 * Hooked into wp_loaded early in every request.
	 *
	 * @return void
	 */
	public function maybe_show_maintenance(): void {
		// If maintenance is NOT active, let requests proceed.
		if ( ! get_option( 'soc_maintenance_active', false ) ) {
			return;
		}

		// Allow logged-in administrators always.
		if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get the visitor's current IP (could be blank if not set).
		$current_ip = $_SERVER['REMOTE_ADDR'] ?? '';

		// Parse/clean the whitelist (one IP per line, trimmed).
		$whitelist = array_filter(
			array_map( 'trim', explode( "\n", get_option( 'soc_maintenance_ips', '' ) ) )
		);

		// If visitor's IP is on the whitelist, allow normal site view.
		if ( in_array( $current_ip, $whitelist, true ) ) {
			return;
		}

		// Check for a custom maintenance.php template in the theme.
		$custom = get_template_directory() . '/maintenance.php';

		if ( file_exists( $custom ) ) {
			// Display the custom maintenance template and exit.
			require $custom;
			exit;
		}

		// Default fallback: show a simple WP maintenance message and set 503 status.
		status_header( 503 );
		nocache_headers();
		wp_die(
			'<h1>Under Maintenance</h1><p>We will be back soon.</p>',
			'Maintenance',
			array( 'response' => 503 )
		);
	}
}

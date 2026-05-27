<?php
/**
 * SOC Security Snapshot Module
 *
 * Collects and presents WordPress security-related metrics for admin review,
 * such as admin users, SSL status, security key status, recent failed logins, and more.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Modules;

use App\Components\SOC\Contracts\SOC_Module;
use App\Components\SOC\Support\SOC_Cache;
use App\Components\SOC\Support\SOC_Logger;

/**
 * Class Security_Snapshot
 *
 * Gathers and caches security-related state from the WordPress environment.
 */
class Security_Snapshot implements SOC_Module {

	/**
	 * Register event hook for tracking failed login attempts.
	 */
	public function __construct() {
		add_action( 'wp_login_failed', array( $this, 'track_failed_login' ) );
	}

	/**
	 * Returns the module slug for internal reference.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'security-snapshot';
	}

	/**
	 * Returns the human-readable module label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return 'Security';
	}

	/**
	 * Returns the Dashicon for this module.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-shield';
	}

	/**
	 * Gathers core WordPress security status, optionally forcing a cache refresh.
	 *
	 * @param bool $force_refresh If true, ignores the cache and collects fresh data.
	 * @return array Associative array of security data.
	 */
	public function collect( bool $force_refresh = false ): array {
		if ( $force_refresh ) {
			// Clear any previously cached data on manual refresh.
			SOC_Cache::forget( $this->get_slug(), 'data' );
		}

		$cached = SOC_Cache::get( $this->get_slug(), 'data' );

		// Return cached data if available.
		if ( false !== $cached ) {
			return $cached;
		}

		// Fetch all admin users and their key properties.
		$admin_users = get_users(
			array(
				'role'   => 'administrator',
				'fields' => array( 'ID', 'user_login', 'user_email', 'user_registered' ),
			)
		);

		// Check that all required security keys are set and aren't still using their defaults.
		$required_keys     = array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY' );
		$security_keys_set = true;

		foreach ( $required_keys as $key ) {
			// If any key is not defined or still the default placeholder, mark as not set.
			if ( ! defined( $key ) || strpos( constant( $key ), 'put your unique phrase here' ) !== false ) {
				$security_keys_set = false;
				break;
			}
		}

		// Check if REST API exposes user endpoints to the public.
		$rest_api_public = $this->check_rest_api_public();

		// Get current WordPress version.
		$wp_version = get_bloginfo( 'version' );

		// Aggregate all security-related data points.
		$data = array(
			'admin_users'          => array_map( static fn( $u ) => (array) $u, $admin_users ),
			'admin_count'          => count( $admin_users ),
			'ssl_active'           => is_ssl(),
			'rest_api_public'      => $rest_api_public,
			'file_editor_disabled' => defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT,
			'debug_mode'           => defined( 'WP_DEBUG' ) && WP_DEBUG,
			'security_keys_set'    => $security_keys_set,
			'recent_failed_logins' => array_slice( (array) get_option( 'soc_failed_logins', array() ), 0, 20 ),
			'wp_version'           => $wp_version,
			'wp_version_ok'        => version_compare( $wp_version, '6.0', '>=' ),
			'active_plugins_count' => count( get_option( 'active_plugins', array() ) ),
			'collected_at'         => current_time( 'mysql' ),
		);

		// Cache snapshot data for 10 minutes for performance.
		SOC_Cache::set( $this->get_slug(), 'data', $data, 10 * MINUTE_IN_SECONDS );

		return $data;
	}

	/**
	 * Renders the module output using the template part.
	 *
	 * @param array $data Collected security snapshot.
	 * @return void
	 */
	public function render( array $data ): void {
		require get_template_directory() . '/inc/Components/SOC/views/security-snapshot.php';
	}

	/**
	 * Track a failed login attempt, storing the username, IP, and timestamp.
	 *
	 * @param string $username The attempted username.
	 * @return void
	 */
	public function track_failed_login( string $username ): void {
		$log = (array) get_option( 'soc_failed_logins', array() );

		array_unshift(
			$log,
			array(
				'username' => sanitize_user( $username ),
				// Note: IP is not specifically sanitized beyond fallback.
				'ip'       => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
				'time'     => current_time( 'mysql' ),
			)
		);

		// Limit log to the most recent 100 entries.
		$log = array_slice( $log, 0, 100 );

		update_option( 'soc_failed_logins', $log, false );
	}

	/**
	 * Check whether the REST API user endpoints are publicly accessible.
	 *
	 * @return bool True if /wp/v2/users endpoint is public, false otherwise.
	 */
	private function check_rest_api_public(): bool {
		$response = wp_remote_get(
			rest_url( '/wp/v2/users' ),
			array(
				'timeout'   => 5,
				'sslverify' => false,
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );

		// Naively check if users are disclosed by searching for user ID in JSON.
		return strpos( $body, '"id"' ) !== false;
	}
}

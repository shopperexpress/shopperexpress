<?php
/**
 * Intice Nexus API Client
 *
 * Stateless HTTP client for the Intice Nexus vehicle API.
 * All responses are cached in WordPress transients.
 *
 * Usage:
 *   $client = \App\Components\Api\Intice_Api_Client::instance();
 *   $result = $client->get_vehicles( [ 'make' => 'Toyota', 'page' => 1 ] );
 *
 * @package Shopperexpress
 */

namespace App\Components\Api;

use App\Components\SOC\Modules\Api_Settings;

/**
 * Class Intice_Api_Client
 */
class Intice_Api_Client {

	const CACHE_TTL_VEHICLES = 10 * MINUTE_IN_SECONDS;
	const CACHE_TTL_VEHICLE  = 15 * MINUTE_IN_SECONDS;
	const CACHE_TTL_META     = 30 * MINUTE_IN_SECONDS;
	const CACHE_PREFIX       = 'intice_api_';

	/**
	 * Singleton instance.
	 *
	 * @var static|null
	 */
	private static ?self $instance = null;

	/**
	 * Base API URL (without trailing slash, without /api/v1).
	 *
	 * @var string
	 */
	private string $base_url;

	/**
	 * API key for X-API-KEY header.
	 *
	 * @var string
	 */
	private string $api_key;

	/**
	 * Private constructor — use instance().
	 */
	private function __construct() {
		$raw            = get_option( Api_Settings::OPTION_API_URL, '' );
		$this->base_url = rtrim( preg_replace( '#/api/v1$#', '', rtrim( $raw, '/' ) ), '/' );
		$this->api_key  = get_option( Api_Settings::OPTION_API_KEY, '' );
	}

	/**
	 * Return (or create) the singleton instance.
	 *
	 * @return static
	 */
	public static function instance(): static {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	// ─── Public API ───────────────────────────────────────────────────────────

	/**
	 * Get a paginated list of vehicles.
	 *
	 * Supported filters: make, model, trim, condition,
	 *   year_from, year_to, price_from, price_to, mileage_max,
	 *   sold, certified, page, per_page, mode.
	 *
	 * @param array $filters Query parameters to forward to the API.
	 * @return array|WP_Error Decoded response array or WP_Error on failure.
	 */
	public function get_vehicles( array $filters = [] ) {
		$cache_key = self::CACHE_PREFIX . 'vehicles_' . md5( serialize( $filters ) );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$response = $this->request( 'GET', '/api/v1/vehicles', $filters );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		set_transient( $cache_key, $response, self::CACHE_TTL_VEHICLES );

		return $response;
	}

	/**
	 * Get a single vehicle by VIN.
	 *
	 * @param string $vin 17-character VIN.
	 * @return array|WP_Error Decoded response array or WP_Error on failure.
	 */
	public function get_vehicle( string $vin ) {
		$vin       = strtoupper( trim( $vin ) );
		$cache_key = self::CACHE_PREFIX . 'vehicle_' . $vin;
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$response = $this->request( 'GET', '/api/v1/vehicles/' . rawurlencode( $vin ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		set_transient( $cache_key, $response, self::CACHE_TTL_VEHICLE );

		return $response;
	}

	/**
	 * Get filter metadata: makes, models, year range, price range, conditions.
	 *
	 * @return array|WP_Error Decoded response array or WP_Error on failure.
	 */
	public function get_meta() {
		$cache_key = self::CACHE_PREFIX . 'meta';
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$response = $this->request( 'GET', '/api/v1/meta' );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		set_transient( $cache_key, $response, self::CACHE_TTL_META );

		return $response;
	}

	/**
	 * Clear all cached API responses for this client.
	 *
	 * @return void
	 */
	public function flush_cache(): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_' . self::CACHE_PREFIX . '%',
				'_transient_timeout_' . self::CACHE_PREFIX . '%'
			)
		);
	}

	// ─── HTTP layer ───────────────────────────────────────────────────────────

	/**
	 * Execute an HTTP request against the Intice API.
	 *
	 * @param string $method  HTTP method (GET).
	 * @param string $path    Endpoint path, e.g. '/api/v1/vehicles'.
	 * @param array  $params  Query parameters.
	 * @return array|\WP_Error Decoded JSON body or WP_Error.
	 */
	private function request( string $method, string $path, array $params = [] ) {
		if ( empty( $this->base_url ) || empty( $this->api_key ) ) {
			return new \WP_Error(
				'intice_not_configured',
				__( 'Intice API URL or key is not configured.', 'shopperexpress' )
			);
		}

		$url = $this->base_url . $path;

		if ( ! empty( $params ) ) {
			$url = add_query_arg( array_filter( $params, fn( $v ) => $v !== null && $v !== '' ), $url );
		}

		$response = wp_remote_request(
			$url,
			array(
				'method'    => $method,
				'timeout'   => 15,
				'sslverify' => false,
				'headers'   => array(
					'X-API-KEY'    => $this->api_key,
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $code < 200 || $code >= 300 ) {
			$message = $data['error'] ?? $data['message'] ?? "HTTP {$code}";

			return new \WP_Error( 'intice_api_error', $message, array( 'status' => $code ) );
		}

		if ( null === $data ) {
			return new \WP_Error( 'intice_json_error', __( 'Invalid JSON response from Intice API.', 'shopperexpress' ) );
		}

		return $data;
	}
}

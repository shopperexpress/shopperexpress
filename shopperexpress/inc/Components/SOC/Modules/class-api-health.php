<?php
/**
 * SOC API Health Module
 *
 * Collects and presents API health metrics for internal and external APIs.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Modules;

use App\Components\Base\Chromedata_Client;
use App\Components\SOC\Contracts\SOC_Module;
use App\Components\SOC\Support\SOC_Cache;
use App\Components\SOC\Support\SOC_Logger;

/**
 * Class Api_Health
 *
 * Monitors the health of internal WordPress endpoints and third-party integrations,
 * providing diagnostic data and last check timestamps.
 */
class Api_Health implements SOC_Module {

	/**
	 * Get unique slug used to identify this module.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'api-health';
	}

	/**
	 * Get the friendly display label for the module.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return 'API Health';
	}

	/**
	 * Get Dashicon identifier used as an icon for this module.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-rest-api';
	}

	/**
	 * Collect API health data, using cache for performance unless $force_refresh is true.
	 *
	 * @param bool $force_refresh Force bypass cache if true.
	 * @return array Collected health data.
	 */
	public function collect( bool $force_refresh = false ): array {
		if ( $force_refresh ) {
			SOC_Cache::forget( $this->get_slug(), 'data' );
		}

		$cached = SOC_Cache::get( $this->get_slug(), 'data' );

		if ( false !== $cached ) {
			return $cached;
		}

		$data = array(
			'internal'     => $this->check_internal_endpoints(),
			'integrations' => $this->check_integrations(),
			'failed_log'   => SOC_Logger::read( 'api', 100 ),
			'collected_at' => current_time( 'mysql' ),
		);

		SOC_Cache::set( $this->get_slug(), 'data', $data, 2 * MINUTE_IN_SECONDS );

		return $data;
	}

	/**
	 * Render the API health check results using an included PHP view template.
	 *
	 * @param array $data Collected health data to render.
	 * @return void
	 */
	public function render( array $data ): void {
		require get_template_directory() . '/inc/Components/SOC/views/api-health.php';
	}

	// ─── Internal WP endpoints ───────────────────────────────────────────────

	/**
	 * Check internal WordPress endpoints (REST, AJAX) for availability.
	 *
	 * @return array Health results for each core endpoint.
	 */
	private function check_internal_endpoints(): array {
		$endpoints = array(
			'REST API'      => rest_url( '/wp/v2/types' ),
			'WP Admin AJAX' => admin_url( 'admin-ajax.php' ),
		);

		$results = array();

		foreach ( $endpoints as $name => $url ) {
			$results[] = $this->probe( $name, $url );
		}

		return $results;
	}

	// ─── Third-party integrations ─────────────────────────────────────────────

	/**
	 * Run health checks for all tracked third-party integrations.
	 *
	 * @return array Results for each third-party API.
	 */
	private function check_integrations(): array {
		return array(
			$this->check_chrome_data(),
			$this->check_360_spin(),
			$this->check_twilio(),
			$this->check_openai(),
			$this->check_evox(),
		);
	}

	/**
	 * Check Chrome Data API health using a real authenticated PUT request.
	 *
	 * Delegates to Chromedata_Client::health_check() so auth logic, credentials,
	 * and endpoint config are all read from the single shared service class.
	 * A plain GET probe would bypass Atmosphere auth and give a false picture
	 * of whether the integration actually works.
	 *
	 * @return array Result in the standard SOC health-check shape.
	 */
	private function check_chrome_data(): array {
		$result = Chromedata_Client::health_check( 5 );

		if ( null === $result['ok'] ) {
			return $this->not_configured( 'Chrome Data' );
		}

		if ( ! $result['ok'] ) {
			SOC_Logger::write( 'api', 'Chrome Data health check failed: ' . ( $result['error'] ?? 'unknown error' ) );
		}

		return array(
			'name'             => 'Chrome Data',
			'url'              => $result['url'],
			'status'           => $result['status'],
			'response_time_ms' => $result['response_time_ms'],
			'ok'               => $result['ok'],
			'error'            => $result['error'] ?? null,
		);
	}

	/**
	 * Check 360 Spin CSV endpoint for an expected CSV resource.
	 *
	 * @return array Probe result or not configured if missing.
	 */
	private function check_360_spin(): array {
		$csv_url = get_field( 'url_for_csv_360', 'options' );

		if ( empty( $csv_url ) ) {
			return $this->not_configured( '360 Spin' );
		}

		return $this->probe( '360 Spin', $csv_url, 5 );
	}

	/**
	 * Check Twilio credentials and API health by fetching the account resource.
	 *
	 * @return array Probe result or not configured if missing.
	 */
	private function check_twilio(): array {
		$account_sid = get_field( 'twilio_account_sid', 'options' );
		$auth_token  = get_field( 'twilio_auth_token', 'options' );

		if ( empty( $account_sid ) || empty( $auth_token ) ) {
			return $this->not_configured( 'Twilio' );
		}

		$url = 'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode( $account_sid ) . '.json';

		$start    = microtime( true );
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 8,
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $account_sid . ':' . $auth_token ),
				),
			)
		);
		$ms       = round( ( microtime( true ) - $start ) * 1000, 2 );

		return $this->build_result( 'Twilio', $url, $response, $ms );
	}

	/**
	 * Check OpenAI API key and try a test call to models endpoint.
	 *
	 * @return array Probe result or not configured if missing.
	 */
	private function check_openai(): array {
		$api_key = get_field( 'ai_api_key', 'option' );

		if ( empty( $api_key ) ) {
			return $this->not_configured( 'OpenAI' );
		}

		$url = 'https://api.openai.com/v1/models';

		$start    = microtime( true );
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 8,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
			)
		);
		$ms       = round( ( microtime( true ) - $start ) * 1000, 2 );

		return $this->build_result( 'OpenAI', $url, $response, $ms );
	}

	/**
	 * Check Evox Images API for API key presence and try a minimal endpoint.
	 *
	 * @return array Probe result or not configured if missing.
	 */
	private function check_evox(): array {
		$api_key = get_field( 'evox_api_key', 'options' );

		if ( empty( $api_key ) ) {
			return $this->not_configured( 'Evox Images' );
		}

		$url = 'https://api.evoximages.com/api/v1/vehicles/?api_key=' . rawurlencode( $api_key ) . '&limit=1';

		return $this->probe( 'Evox Images', $url, 8 );
	}

	// ─── Helpers ─────────────────────────────────────────────────────────────

	/**
	 * Probe an endpoint and return normalized API health data.
	 *
	 * @param string $name    Friendly label for the API.
	 * @param string $url     Endpoint URL to probe.
	 * @param int    $timeout Request timeout in seconds.
	 * @return array Health check details (timing, status, error).
	 */
	private function probe( string $name, string $url, int $timeout = 5 ): array {
		$start    = microtime( true );
		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => $timeout,
				'sslverify' => false,
			)
		);
		$ms       = round( ( microtime( true ) - $start ) * 1000, 2 );

		return $this->build_result( $name, $url, $response, $ms );
	}

	/**
	 * Build a structured result array from a probe or remote request.
	 *
	 * @param string $name     API name.
	 * @param string $url      Probed URL.
	 * @param mixed  $response WP remote fetch response.
	 * @param float  $ms       Milliseconds timing.
	 * @return array Status and diagnostic info.
	 */
	private function build_result( string $name, string $url, $response, float $ms ): array {
		if ( is_wp_error( $response ) ) {
			SOC_Logger::write( 'api', $name . ' check failed: ' . $response->get_error_message() );

			return array(
				'name'             => $name,
				'url'              => $url,
				'status'           => 'error',
				'response_time_ms' => $ms,
				'ok'               => false,
				'error'            => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );

		return array(
			'name'             => $name,
			'url'              => $url,
			'status'           => $code,
			'response_time_ms' => $ms,
			'ok'               => ( $code >= 200 && $code < 300 ),
		);
	}

	/**
	 * Return a standard result for an integration that's not configured.
	 *
	 * @param string $name Integration name.
	 * @return array Not configured result structure.
	 */
	private function not_configured( string $name ): array {
		return array(
			'name'             => $name,
			'url'              => '',
			'status'           => 'not configured',
			'response_time_ms' => 0,
			'ok'               => null,
		);
	}

	// ─── Ad-hoc ping (AJAX test tool) ────────────────────────────────────────

	/**
	 * Perform an ad-hoc ping to a URL with validation and security checks.
	 *
	 * Blocks private (LAN) addresses and logs failures.
	 *
	 * @param string $url The remote URL to probe.
	 * @return array Result with status, timing, or error.
	 */
	public function ping( string $url ): array {
		if ( ! wp_http_validate_url( $url ) ) {
			return array(
				'ok'    => false,
				'error' => 'Invalid URL',
			);
		}

		$host = parse_url( $url, PHP_URL_HOST );

		// Block probes to private network IPs
		if ( preg_match( '/^(10\.|172\.(1[6-9]|2\d|3[01])\.|192\.168\.|127\.|localhost)/i', $host ) ) {
			return array(
				'ok'    => false,
				'error' => 'Private IP blocked',
			);
		}

		$start    = microtime( true );
		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 10,
				'sslverify' => false,
			)
		);
		$ms       = round( ( microtime( true ) - $start ) * 1000, 2 );

		if ( is_wp_error( $response ) ) {
			SOC_Logger::write( 'api', 'Ping failed: ' . $url . ' — ' . $response->get_error_message() );
			return array(
				'ok'    => false,
				'error' => $response->get_error_message(),
				'ms'    => $ms,
			);
		}

		$code = wp_remote_retrieve_response_code( $response );

		return array(
			'ok'     => ( $code >= 200 && $code < 300 ),
			'status' => $code,
			'ms'     => $ms,
			'url'    => $url,
		);
	}
}

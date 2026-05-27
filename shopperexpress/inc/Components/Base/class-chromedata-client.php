<?php
/**
 * Chromedata API Client
 *
 * Single source of truth for all Chromedata API calls:
 * credential retrieval, Atmosphere auth-token building,
 * cURL request execution, and lightweight health checks.
 *
 * Consumed by Vin_Admin (VIN lookup) and SOC Api_Health (connectivity check).
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

/**
 * Class Chromedata_Client
 *
 * Static service — no instantiation required.
 */
class Chromedata_Client {

	// ── A known, public VIN used only for health-check probes. ───────────────
	const HEALTH_CHECK_VIN = '2HGFE2F22TH590352';

	// ─── Credentials / configuration ─────────────────────────────────────────

	/**
	 * Return the Chromedata endpoint URL from ACF options.
	 *
	 * @return string Empty string when not set.
	 */
	public static function get_url(): string {
		return (string) get_field( 'url_chromedata', 'options' );
	}

	/**
	 * Return the Chromedata app_id from ACF options.
	 *
	 * @return string
	 */
	public static function get_app_id(): string {
		return (string) get_field( 'chromedata_app_id', 'options' );
	}

	/**
	 * Return the Chromedata shared secret from ACF options.
	 *
	 * @return string
	 */
	public static function get_secret(): string {
		return (string) get_field( 'shared_secret', 'options' );
	}

	/**
	 * Return true when all three required settings are non-empty.
	 *
	 * @return bool
	 */
	public static function is_configured(): bool {
		return self::get_url() !== '' && self::get_app_id() !== '' && self::get_secret() !== '';
	}

	// ─── Auth ─────────────────────────────────────────────────────────────────

	/**
	 * Build a fresh Atmosphere authorization header value.
	 *
	 * Each call generates a unique nonce + timestamp so tokens cannot be replayed.
	 *
	 * @return string Ready-to-use Authorization header value.
	 */
	public static function build_auth_header(): string {
		$mt        = explode( ' ', microtime() );
		$timestamp = ( (int) $mt[1] ) * 1000 + (int) round( $mt[0] * 1000 );

		$nonce = substr(
			str_replace( array( '+', '/', '=' ), '', base64_encode( random_bytes( 32 ) ) ),
			0,
			32
		);

		$digest = base64_encode( sha1( $nonce . $timestamp . self::get_secret(), true ) );

		return sprintf(
			'Atmosphere realm="http://chromedata.com",'
			. 'chromedata_app_id="%s",'
			. 'chromedata_nonce="%s",'
			. 'chromedata_secret_digest="%s",'
			. 'chromedata_digest_method=SHA1,'
			. 'chromedata_version=1.0,'
			. 'chromedata_timestamp="%s"',
			self::get_app_id(),
			$nonce,
			$digest,
			$timestamp
		);
	}

	// ─── Request ──────────────────────────────────────────────────────────────

	/**
	 * Execute a Chromedata API request (PUT with Atmosphere auth).
	 *
	 * @param string $url     Chromedata endpoint (from get_url()).
	 * @param array  $data    Query/body parameters (VIN, onlyDecodeUsing, …).
	 * @param int    $timeout cURL timeout in seconds. Default 15.
	 * @return array|\WP_Error Decoded response array on success, WP_Error on failure.
	 *                         Successful arrays always contain a 'ms' key (response time).
	 */
	public static function request( string $url, array $data, int $timeout = 15 ) {
		$auth = self::build_auth_header();

		$ch = curl_init();
		curl_setopt_array(
			$ch,
			array(
				CURLOPT_URL            => $url . '?' . http_build_query( $data ),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST  => 'PUT',
				CURLOPT_HTTPHEADER     => array(
					'Accept: application/json',
					'Content-Type: application/json',
					'Authorization: ' . $auth,
				),
				CURLOPT_POSTFIELDS     => json_encode( $data ),
				CURLOPT_TIMEOUT        => $timeout,
				CURLOPT_SSL_VERIFYHOST => 0,
				CURLOPT_SSL_VERIFYPEER => 0,
			)
		);

		$start = microtime( true );
		$body  = curl_exec( $ch );
		$ms    = round( ( microtime( true ) - $start ) * 1000, 2 );
		$error = curl_error( $ch );
		$code  = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		if ( $error ) {
			return new \WP_Error(
				'curl_error',
				$error,
				array(
					'ms'   => $ms,
					'code' => 0,
				)
			);
		}

		if ( 200 !== $code ) {
			return new \WP_Error(
				'http_error',
				sprintf( 'Chromedata returned HTTP %d', $code ),
				array(
					'ms'   => $ms,
					'code' => $code,
				)
			);
		}

		$result = json_decode( $body, true );

		if ( ! is_array( $result ) ) {
			return new \WP_Error(
				'parse_error',
				'Failed to parse Chromedata response',
				array(
					'ms'   => $ms,
					'code' => $code,
				)
			);
		}

		return array(
			'result' => $result,
			'ms'     => $ms,
			'code'   => $code,
		);
	}

	// ─── Health check ─────────────────────────────────────────────────────────

	/**
	 * Perform a lightweight authenticated health check against the Chromedata API.
	 *
	 * Sends a real PUT request with Atmosphere auth using a known public VIN
	 * so both network connectivity AND credential validity are verified.
	 *
	 * Returns a shape compatible with SOC Api_Health's result format:
	 *   ok               bool|null  — true = healthy, false = error, null = not configured
	 *   status           int|string — HTTP response code or descriptive string
	 *   response_time_ms float      — round-trip ms
	 *   error            string     — present only on failure
	 *   url              string     — endpoint that was probed
	 *   configured       bool       — whether credentials exist
	 *
	 * @param int $timeout cURL timeout in seconds. Default 5.
	 * @return array
	 */
	public static function health_check( int $timeout = 5 ): array {
		$url = self::get_url();

		if ( ! self::is_configured() ) {
			return array(
				'ok'               => null,
				'status'           => 'not configured',
				'response_time_ms' => 0,
				'url'              => $url,
				'configured'       => false,
			);
		}

		$response = self::request(
			$url,
			array(
				'VIN'             => self::HEALTH_CHECK_VIN,
				'onlyDecodeUsing' => 'V,E,C,S',
			),
			$timeout
		);

		if ( is_wp_error( $response ) ) {
			$data = $response->get_error_data();

			return array(
				'ok'               => false,
				'status'           => $data['code'] ?? 0,
				'response_time_ms' => $data['ms'] ?? 0,
				'error'            => $response->get_error_message(),
				'url'              => $url,
				'configured'       => true,
			);
		}

		return array(
			'ok'               => true,
			'status'           => $response['code'],
			'response_time_ms' => $response['ms'],
			'url'              => $url,
			'configured'       => true,
		);
	}
}

<?php
/**
 * ADF API Client
 *
 * Sends ADFXML lead payloads to the configured Intice IO API endpoint.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

/**
 * Class ADF_Api_Client
 *
 * Handles HTTP delivery of ADFXML lead data to the Intice IO REST endpoint.
 * Configuration is read from WP options (saved by the SOC Lead Delivery panel).
 */
class ADF_Api_Client {

	/**
	 * WP option key for API endpoint URL.
	 */
	const OPTION_ENDPOINT   = 'adf_api_endpoint';

	/**
	 * WP option key for API secret key (stored encrypted).
	 */
	const OPTION_SECRET_KEY = 'adf_api_secret_key';

	/**
	 * WP option key for request timeout in seconds.
	 */
	const OPTION_TIMEOUT    = 'adf_api_timeout';

	/**
	 * WP option key for the dealer ID included in every payload.
	 */
	const OPTION_DEALER_ID  = 'adf_api_dealer_id';

	/**
	 * Encryption salt option key.
	 */
	const OPTION_SALT       = 'adf_api_key_salt';

	/**
	 * Send an ADFXML string to the configured Intice IO endpoint.
	 *
	 * @param string $xml  The complete ADFXML payload string.
	 * @param array  $meta Optional context for logging (form_name, first_name, etc.).
	 * @return array{success: bool, response_code: int, response_body: string, error_message: string}
	 */
	public function send( string $xml, array $meta = array() ): array {
		$endpoint   = get_option( self::OPTION_ENDPOINT, '' );
		$secret_key = $this->get_secret_key();
		$timeout    = (int) get_option( self::OPTION_TIMEOUT, 10 );

		if ( empty( $endpoint ) ) {
			return $this->result( false, 0, '', 'API endpoint is not configured.' );
		}

		if ( empty( $secret_key ) ) {
			return $this->result( false, 0, '', 'API secret key is not configured.' );
		}

		$dealer_id = get_option( self::OPTION_DEALER_ID, '' );

		$body = wp_json_encode(
			array_filter(
				array(
					'payload_type' => 'adfxml',
					'source'       => 'wordpress',
					'dealer_id'    => '' !== $dealer_id ? $dealer_id : null,
					'adfxml'       => $xml,
				),
				fn( $v ) => null !== $v
			)
		);

		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout'     => max( 5, $timeout ),
				'redirection' => 3,
				'httpversion' => '1.1',
				'headers'     => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
					'X-API-Key'    => $secret_key,
				),
				'body'        => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $this->result( false, 0, '', $response->get_error_message() );
		}

		$code          = (int) wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$success       = $code >= 200 && $code < 300;

		return $this->result( $success, $code, $response_body, $success ? '' : "HTTP {$code}" );
	}

	/**
	 * Test connectivity to the configured endpoint using a minimal dummy payload.
	 *
	 * @return array{success: bool, response_code: int, response_body: string, error_message: string}
	 */
	public function test_connection(): array {
		$dummy_xml = '<?xml version="1.0" encoding="utf-8"?><?ADF version="1.0"?><adf><prospect><id source="shopperexpress-test" sequence="1"></id><requestdate>' . gmdate( 'm-d-Y' ) . '</requestdate></prospect></adf>';
		return $this->send( $dummy_xml, array( 'form_name' => 'connection-test' ) );
	}

	/**
	 * Save the API endpoint URL.
	 *
	 * @param string $endpoint URL.
	 * @return void
	 */
	public function save_endpoint( string $endpoint ): void {
		update_option( self::OPTION_ENDPOINT, esc_url_raw( $endpoint ) );
	}

	/**
	 * Save the API secret key, encrypting it before storage.
	 *
	 * @param string $key Plain-text secret key.
	 * @return void
	 */
	public function save_secret_key( string $key ): void {
		if ( '' === $key ) {
			return;
		}
		update_option( self::OPTION_SECRET_KEY, $this->encrypt( $key ) );
	}

	/**
	 * Save request timeout.
	 *
	 * @param int $seconds Timeout in seconds (min 5, max 60).
	 * @return void
	 */
	public function save_timeout( int $seconds ): void {
		update_option( self::OPTION_TIMEOUT, max( 5, min( 60, $seconds ) ) );
	}

	/**
	 * Save the dealer ID included in every API payload.
	 *
	 * @param string $dealer_id Dealer identifier.
	 * @return void
	 */
	public function save_dealer_id( string $dealer_id ): void {
		update_option( self::OPTION_DEALER_ID, sanitize_text_field( $dealer_id ) );
	}

	/**
	 * Retrieve and decrypt the stored secret key.
	 *
	 * @return string Plain-text key or empty string.
	 */
	public function get_secret_key(): string {
		$encrypted = get_option( self::OPTION_SECRET_KEY, '' );
		if ( '' === $encrypted ) {
			return '';
		}
		return $this->decrypt( $encrypted );
	}

	/**
	 * Check whether the API is fully configured (endpoint + key set).
	 *
	 * @return bool
	 */
	public function is_configured(): bool {
		return '' !== get_option( self::OPTION_ENDPOINT, '' ) && '' !== get_option( self::OPTION_SECRET_KEY, '' );
	}

	// -------------------------------------------------------------------------
	// Encryption helpers (AES-256-CBC via OpenSSL)
	// -------------------------------------------------------------------------

	/**
	 * Encrypt a string for storage.
	 *
	 * Uses a per-site salt stored in wp_options. Falls back to base64 if OpenSSL
	 * is unavailable (not recommended for production).
	 *
	 * @param string $value Plain text.
	 * @return string Encrypted, base64-encoded value prefixed with IV.
	 */
	private function encrypt( string $value ): string {
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return base64_encode( $value );
		}
		$key = $this->get_or_create_salt();
		$iv  = openssl_random_pseudo_bytes( 16 );
		$enc = openssl_encrypt( $value, 'AES-256-CBC', $key, 0, $iv );
		return base64_encode( $iv . $enc );
	}

	/**
	 * Decrypt a stored encrypted string.
	 *
	 * @param string $stored Encrypted value.
	 * @return string Plain text, or empty string on failure.
	 */
	private function decrypt( string $stored ): string {
		if ( ! function_exists( 'openssl_decrypt' ) ) {
			$decoded = base64_decode( $stored, true );
			return false !== $decoded ? $decoded : '';
		}
		$key     = $this->get_or_create_salt();
		$decoded = base64_decode( $stored, true );
		if ( false === $decoded || strlen( $decoded ) < 17 ) {
			return '';
		}
		$iv  = substr( $decoded, 0, 16 );
		$enc = substr( $decoded, 16 );
		$dec = openssl_decrypt( $enc, 'AES-256-CBC', $key, 0, $iv );
		return false !== $dec ? $dec : '';
	}

	/**
	 * Get (or generate and store) the per-site encryption salt.
	 *
	 * @return string 32-byte key.
	 */
	private function get_or_create_salt(): string {
		$salt = get_option( self::OPTION_SALT, '' );
		if ( strlen( $salt ) < 32 ) {
			$salt = wp_generate_password( 32, true, true );
			update_option( self::OPTION_SALT, $salt );
		}
		return substr( $salt, 0, 32 );
	}

	// -------------------------------------------------------------------------
	// Internal helpers
	// -------------------------------------------------------------------------

	/**
	 * Build a standardised result array.
	 *
	 * @param bool   $success       Whether the request succeeded.
	 * @param int    $response_code HTTP response code (0 if no response).
	 * @param string $response_body Raw response body.
	 * @param string $error_message Human-readable error, empty on success.
	 * @return array
	 */
	private function result( bool $success, int $response_code, string $response_body, string $error_message ): array {
		return compact( 'success', 'response_code', 'response_body', 'error_message' );
	}
}

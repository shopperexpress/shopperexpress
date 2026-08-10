<?php
/**
 * OpenAI Ads Client
 *
 * Dealer-level configuration accessors for the OpenAI Ads pixel and the
 * future server-side Conversions API phase. Non-secret values are stored
 * as plain ACF options fields (see acf-json/group_asc_settings.json); the
 * Conversions API key is always stored AES-256-CBC encrypted and is never
 * exposed back to the browser or the admin UI after saving.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

/**
 * Class OpenAI_Ads_Client
 *
 * @package App\Components\Base
 */
class OpenAI_Ads_Client {

	/**
	 * WP option key for the OpenAI Ads enabled toggle.
	 */
	const OPTION_ENABLED = 'openai_ads_enabled';

	/**
	 * WP option key for the dealer-specific Pixel ID.
	 */
	const OPTION_PIXEL_ID = 'openai_ads_pixel_id';

	/**
	 * WP option key for the dealer identifier used in shared event IDs.
	 */
	const OPTION_DEALER_ID = 'openai_ads_dealer_id';

	/**
	 * WP option key for the debug logging toggle.
	 */
	const OPTION_DEBUG = 'openai_ads_debug_mode';

	/**
	 * WP option key for the (future) Conversions API enabled toggle.
	 */
	const OPTION_CAPI_ENABLED = 'openai_ads_capi_enabled';

	/**
	 * WP option key for the encrypted Conversions API key.
	 */
	const OPTION_CAPI_KEY = 'openai_ads_capi_key';

	/**
	 * Encryption salt option key.
	 */
	const OPTION_SALT = 'openai_ads_capi_key_salt';

	/**
	 * Whether OpenAI Ads is enabled for this dealer.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return (bool) get_field( self::OPTION_ENABLED, 'option' );
	}

	/**
	 * Dealer-specific OpenAI Pixel ID.
	 *
	 * @return string
	 */
	public function get_pixel_id(): string {
		return trim( (string) get_field( self::OPTION_PIXEL_ID, 'option' ) );
	}

	/**
	 * Dealer identifier used to build shared event IDs.
	 *
	 * @return string
	 */
	public function get_dealer_id(): string {
		return trim( (string) get_field( self::OPTION_DEALER_ID, 'option' ) );
	}

	/**
	 * Whether debug logging is enabled.
	 *
	 * @return bool
	 */
	public function is_debug(): bool {
		return (bool) get_field( self::OPTION_DEBUG, 'option' );
	}

	/**
	 * Whether the (future) Conversions API phase is enabled.
	 *
	 * @return bool
	 */
	public function is_capi_enabled(): bool {
		return (bool) get_field( self::OPTION_CAPI_ENABLED, 'option' );
	}

	/**
	 * Whether the pixel is fully configured and ready to load.
	 *
	 * @return bool
	 */
	public function is_configured(): bool {
		return $this->is_enabled() && '' !== $this->get_pixel_id();
	}

	/**
	 * Whether a Conversions API key has been saved.
	 *
	 * @return bool
	 */
	public function has_capi_key(): bool {
		return '' !== get_option( self::OPTION_CAPI_KEY, '' );
	}

	/**
	 * Encrypt and store the Conversions API key.
	 *
	 * @param string $key Plain-text key.
	 * @return void
	 */
	public function save_capi_key( string $key ): void {
		if ( '' === $key ) {
			return;
		}
		update_option( self::OPTION_CAPI_KEY, $this->encrypt( $key ), false );
	}

	/**
	 * Retrieve and decrypt the stored Conversions API key.
	 *
	 * Server-side use only (future Conversions API phase) — never expose
	 * this value in HTML, JS, or logs.
	 *
	 * @return string Plain-text key or empty string.
	 */
	public function get_capi_key(): string {
		$encrypted = get_option( self::OPTION_CAPI_KEY, '' );
		if ( '' === $encrypted ) {
			return '';
		}
		return $this->decrypt( $encrypted );
	}

	/**
	 * Mask the Pixel ID for safe inclusion in debug logs.
	 *
	 * @return string
	 */
	public function get_masked_pixel_id(): string {
		$id  = $this->get_pixel_id();
		$len = strlen( $id );

		if ( 0 === $len ) {
			return '';
		}

		if ( $len <= 4 ) {
			return str_repeat( '*', $len );
		}

		return substr( $id, 0, 2 ) . str_repeat( '*', $len - 4 ) . substr( $id, -2 );
	}

	// -------------------------------------------------------------------------
	// Encryption helpers (AES-256-CBC via OpenSSL) — mirrors ADF_Api_Client.
	// -------------------------------------------------------------------------

	/**
	 * Encrypt a string for storage.
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
}

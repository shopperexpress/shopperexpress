<?php
/**
 * SOC API Settings Module
 *
 * Controls the global API mode toggle and Intice API connection settings.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Modules;

use App\Components\Api\Intice_Api_Client;
use App\Components\SOC\Contracts\SOC_Module;
use App\Components\SOC\Support\SOC_Cache;
use App\Components\SOC\Support\SOC_Logger;

/**
 * Class Api_Settings
 */
class Api_Settings implements SOC_Module {

	const OPTION_API_MODE      = 'shopperexpress_api_mode_enabled';
	const OPTION_API_KEY       = 'shopperexpress_intice_api_key';
	const OPTION_API_URL       = 'shopperexpress_intice_api_url';
	const OPTION_CACHE_ENABLED = 'shopperexpress_intice_cache_enabled';

	/**
	 * @return string
	 */
	public function get_slug(): string {
		return 'api-settings';
	}

	/**
	 * @return string
	 */
	public function get_label(): string {
		return 'API Settings';
	}

	/**
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-admin-network';
	}

	/**
	 * @param bool $force_refresh
	 * @return array
	 */
	public function collect( bool $force_refresh = false ): array {
		if ( $force_refresh ) {
			SOC_Cache::forget( $this->get_slug(), 'data' );
		}

		$cached = SOC_Cache::get( $this->get_slug(), 'data' );
		if ( false !== $cached ) {
			return $cached;
		}

		$api_url = get_option( self::OPTION_API_URL, '' );
		$api_key = get_option( self::OPTION_API_KEY, '' );

		$api_mode     = (bool) get_option( self::OPTION_API_MODE, false );
		$cache_enabled = (bool) get_option( self::OPTION_CACHE_ENABLED, true );

		$data = array(
			'api_mode_enabled' => $api_mode,
			'cache_enabled'    => $cache_enabled,
			'api_url'          => $api_url,
			'api_key_set'      => ! empty( $api_key ),
			'api_key_masked'   => $this->mask_key( $api_key ),
			'connection_test'  => null,
			'api_cache'        => $api_mode ? $this->collect_api_cache() : null,
			'listings_fields'  => $this->get_listings_fields_reference(),
			'collected_at'     => current_time( 'mysql' ),
		);

		SOC_Cache::set( $this->get_slug(), 'data', $data, 1 * MINUTE_IN_SECONDS );

		return $data;
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function render( array $data ): void {
		require get_template_directory() . '/inc/Components/SOC/views/api-settings.php';
	}

	// ─── Public helpers ───────────────────────────────────────────────────────

	/**
	 * Toggle API mode on/off.
	 *
	 * @param bool $enabled
	 * @return bool New state.
	 */
	public function set_api_mode( bool $enabled ): bool {
		update_option( self::OPTION_API_MODE, $enabled ? '1' : '0', false );
		SOC_Cache::forget( $this->get_slug(), 'data' );
		return $enabled;
	}

	/**
	 * Toggle Intice Nexus cache on/off.
	 *
	 * @param bool $enabled
	 * @return bool New state.
	 */
	public function set_cache_enabled( bool $enabled ): bool {
		update_option( self::OPTION_CACHE_ENABLED, $enabled ? '1' : '0', false );
		SOC_Cache::forget( $this->get_slug(), 'data' );
		return $enabled;
	}

	/**
	 * Save API credentials.
	 *
	 * @param string $url
	 * @param string $key
	 * @return void
	 */
	public function save_credentials( string $url, string $key ): void {
		update_option( self::OPTION_API_URL, esc_url_raw( $url ), false );
		// Only overwrite key when a new one is explicitly provided.
		if ( '' !== $key ) {
			update_option( self::OPTION_API_KEY, sanitize_text_field( $key ), false );
		}
		SOC_Cache::forget( $this->get_slug(), 'data' );
	}

	/**
	 * Test connection to the Intice API /meta endpoint.
	 *
	 * @return array { ok: bool, status: int|string, ms: float, error?: string }
	 */
	public function test_connection(): array {
		$base = rtrim( get_option( self::OPTION_API_URL, '' ), '/' );
		// Strip any trailing /api/v1 so the stored URL can be either the root or the versioned prefix.
		$base = preg_replace( '#/api/v1$#', '', $base );
		$url  = $base . '/api/v1/vehicles';
		$key  = get_option( self::OPTION_API_KEY, '' );

		if ( empty( $url ) || empty( $key ) ) {
			return array( 'ok' => false, 'error' => 'API URL or key not configured.' );
		}

		$start    = microtime( true );
		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 8,
				'sslverify' => false,
				'headers'   => array(
					'X-API-KEY'    => $key,
					'Content-Type' => 'application/json',
				),
			)
		);
		$ms = round( ( microtime( true ) - $start ) * 1000, 2 );

		if ( is_wp_error( $response ) ) {
			return array( 'ok' => false, 'error' => $response->get_error_message(), 'ms' => $ms );
		}

		$code = wp_remote_retrieve_response_code( $response );

		return array(
			'ok'     => ( $code >= 200 && $code < 300 ),
			'status' => $code,
			'ms'     => $ms,
		);
	}

	// ─── API Cache helpers ────────────────────────────────────────────────────

	/**
	 * Collect Intice API transient cache stats for the dashboard table.
	 *
	 * @return array
	 */
	public function collect_api_cache(): array {
		global $wpdb;

		$prefix  = Intice_Api_Client::CACHE_PREFIX;
		$entries = array(
			array(
				'key'   => $prefix . 'vehicles_%',
				'label' => 'Vehicles (SRP)',
				'ttl'   => Intice_Api_Client::CACHE_TTL_VEHICLES,
			),
			array(
				'key'   => $prefix . 'vehicle_%',
				'label' => 'Single Vehicle (VDP)',
				'ttl'   => Intice_Api_Client::CACHE_TTL_VEHICLE,
			),
			array(
				'key'   => $prefix . 'meta',
				'label' => 'Filters Meta',
				'ttl'   => Intice_Api_Client::CACHE_TTL_META,
			),
		);

		$rows = array();

		foreach ( $entries as $entry ) {
			$like    = '_transient_timeout_' . $entry['key'];
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
					$like
				)
			);

			$count      = count( $results );
			$expires_at = null;
			$status     = 'missing';

			if ( $count > 0 ) {
				$earliest = min( array_column( $results, 'option_value' ) );
				$now      = time();

				if ( $earliest > $now ) {
					$status     = 'valid';
					$expires_at = human_time_diff( $now, $earliest ) . ' left';
				} else {
					$status     = 'expired';
					$expires_at = 'Expired';
				}
			}

			// Check for stale entries while live cache is being rebuilt.
			$stale_like  = '_transient_timeout_' . str_replace( Intice_Api_Client::CACHE_PREFIX, Intice_Api_Client::CACHE_PREFIX_STALE, $entry['key'] );
			$stale_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s", $stale_like ) );
			$has_stale   = (int) $stale_count > 0;

			$rows[] = array(
				'label'      => $entry['label'],
				'key'        => $entry['key'],
				'count'      => $count,
				'status'     => ( $status === 'missing' && $has_stale ) ? 'stale' : $status,
				'expires_at' => $expires_at,
				'ttl_label'  => human_readable_duration( gmdate( 'H:i:s', $entry['ttl'] ) ),
			);
		}

		return $rows;
	}

	/**
	 * Flush all Intice API transients and log the action.
	 *
	 * @return int Number of deleted rows.
	 */
	public function flush_api_cache(): int {
		$client  = Intice_Api_Client::instance();
		$client->flush_cache();

		$user = wp_get_current_user();
		SOC_Logger::write( 'cache', 'Intice API cache flushed by: ' . ( $user->user_email ?: 'unknown' ) );
		SOC_Cache::forget( $this->get_slug(), 'data' );

		global $wpdb;
		// Count was already deleted; return a best-effort row count.
		return (int) $wpdb->rows_affected;
	}

	/**
	 * Flush a specific cache group (vehicles / vehicle / meta).
	 *
	 * @param string $group  'vehicles', 'vehicle', or 'meta'.
	 * @return int Deleted rows.
	 */
	public function flush_api_cache_group( string $group ): int {
		global $wpdb;

		$prefix = Intice_Api_Client::CACHE_PREFIX;

		$like_map = array(
			'vehicles' => $prefix . 'vehicles_%',
			'vehicle'  => $prefix . 'vehicle_%',
			'meta'     => $prefix . 'meta',
		);

		$like = $like_map[ $group ] ?? null;

		if ( ! $like ) {
			return 0;
		}

		$deleted = (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_' . $like,
				'_transient_timeout_' . $like
			)
		);

		$user = wp_get_current_user();
		SOC_Logger::write( 'cache', "Intice API cache flushed [{$group}] by: " . ( $user->user_email ?: 'unknown' ) );
		SOC_Cache::forget( $this->get_slug(), 'data' );

		return $deleted;
	}

	// ─── Private helpers ──────────────────────────────────────────────────────

	/**
	 * Reference list of the Listings ACF fields (name, slug, demo value) grouped
	 * by their ACF tab. Purely informational — used by the "ACF Fields Reference"
	 * tab so field names/keys are documented in one place for anyone wiring up
	 * Nexus API field mappings in code.
	 *
	 * @return array<string, array<int, array{label: string, slug: string, demo: string}>>
	 */
	private function get_listings_fields_reference(): array {
		return array(
			'General'     => array(
				array( 'label' => 'VIN', 'slug' => 'vin_number', 'demo' => '1HGCM82633A004352' ),
				array( 'label' => 'Stock', 'slug' => 'stock_number', 'demo' => 'A12345' ),
				array( 'label' => 'Year', 'slug' => 'year', 'demo' => '2024' ),
				array( 'label' => 'Make', 'slug' => 'make', 'demo' => 'Toyota' ),
				array( 'label' => 'Model', 'slug' => 'model', 'demo' => 'Camry' ),
				array( 'label' => 'Trim', 'slug' => 'trim', 'demo' => 'SE' ),
				array( 'label' => 'VehicleType', 'slug' => 'condition', 'demo' => 'new' ),
				array( 'label' => 'BodyStyle', 'slug' => 'bodystyle', 'demo' => 'Sedan' ),
				array( 'label' => 'Style', 'slug' => 'body_style', 'demo' => 'Sedan' ),
				array( 'label' => 'TransmissionType', 'slug' => 'transmission', 'demo' => 'Automatic' ),
				array( 'label' => 'InteriorType', 'slug' => 'interiortype', 'demo' => 'Cloth' ),
				array( 'label' => 'InteriorColor', 'slug' => 'interior_color', 'demo' => 'Black' ),
				array( 'label' => 'ExteriorColor', 'slug' => 'exterior_color', 'demo' => 'Silver' ),
				array( 'label' => 'Mileage', 'slug' => 'mileage', 'demo' => '12345' ),
				array( 'label' => 'Miles Display', 'slug' => 'miles_display', 'demo' => '12,345 mi' ),
				array( 'label' => 'DateInStock', 'slug' => 'dateinstock', 'demo' => '2026-06-01' ),
				array( 'label' => 'Sold', 'slug' => 'sold', 'demo' => '' ),
				array( 'label' => 'Vehicle Status', 'slug' => 'vehicle-status', 'demo' => 'Available' ),
				array( 'label' => 'Status_Code', 'slug' => 'status_code', 'demo' => 'A' ),
				array( 'label' => 'Url', 'slug' => 'url', 'demo' => '/listings/new-2024-toyota-camry-sedan-se-1hgcm82633a004352-a12345/' ),
				array( 'label' => 'Information', 'slug' => 'information', 'demo' => 'Internal notes text.' ),
				array( 'label' => 'Comment1–5', 'slug' => 'comment1 … comment5', 'demo' => 'Free-text comment' ),
			),
			'Fuel'        => array(
				array( 'label' => 'MPGCity', 'slug' => 'city_mpg', 'demo' => '28' ),
				array( 'label' => 'MPGHwy', 'slug' => 'highway_mpg', 'demo' => '39' ),
				array( 'label' => 'EPAClassification', 'slug' => 'epaclassification', 'demo' => 'Midsize Cars' ),
				array( 'label' => 'Fuel Type', 'slug' => 'fuel_type', 'demo' => 'Gasoline' ),
			),
			'Mechanical'  => array(
				array( 'label' => 'EngineSize', 'slug' => 'engine', 'demo' => '2.5L' ),
				array( 'label' => 'EngineDisplacement', 'slug' => 'enginedisplacement', 'demo' => '2487' ),
				array( 'label' => 'EngineSizeUOM', 'slug' => 'enginesizeuom', 'demo' => 'cc' ),
				array( 'label' => 'EngineBlock', 'slug' => 'engineblock', 'demo' => 'I' ),
				array( 'label' => 'EngineCylinders', 'slug' => 'enginecylinders', 'demo' => '4' ),
				array( 'label' => 'Transmission_Speed', 'slug' => 'transmission_speed', 'demo' => '8' ),
				array( 'label' => 'Transmission_Description', 'slug' => 'transmission_description', 'demo' => '8-Speed Automatic' ),
				array( 'label' => 'DriveTrain', 'slug' => 'drivetrain', 'demo' => 'FWD' ),
				array( 'label' => 'Wheelbase_Code', 'slug' => 'wheelbase_code', 'demo' => 'WB1' ),
			),
			'Payment'     => array(
				array( 'label' => 'Lease Payment', 'slug' => 'lease_payment', 'demo' => '349' ),
				array( 'label' => 'Loan Payment', 'slug' => 'loan_payment', 'demo' => '429' ),
				array( 'label' => 'Loan Payment (sort)', 'slug' => 'loan_payment_sort', 'demo' => '429' ),
				array( 'label' => 'Loan Payment (sort 1)', 'slug' => 'loan_payment_sort_1', 'demo' => '429' ),
				array( 'label' => 'DueAtSigning', 'slug' => 'down_payment', 'demo' => '2999' ),
				array( 'label' => 'LeaseTerm', 'slug' => 'leaseterm', 'demo' => '36' ),
				array( 'label' => 'LoanTerm', 'slug' => 'loanterm', 'demo' => '60' ),
				array( 'label' => 'LoanAPR', 'slug' => 'loanapr', 'demo' => '4.9' ),
				array( 'label' => 'TotalOfPmts', 'slug' => 'totalofpmts', 'demo' => '25740' ),
			),
			'Pricing'     => array(
				array( 'label' => 'Price', 'slug' => 'price', 'demo' => '25999' ),
				array( 'label' => 'Price (sort)', 'slug' => 'price_sort', 'demo' => '25999' ),
				array( 'label' => 'MSRP', 'slug' => 'original_price', 'demo' => '28450' ),
				array( 'label' => 'InvoiceAmount', 'slug' => 'invoiceamount', 'demo' => '26800' ),
				array( 'label' => 'DefaultBookValue', 'slug' => 'defaultbookvalue', 'demo' => '27100' ),
				array( 'label' => 'InternetPrice', 'slug' => 'internetprice', 'demo' => '25999' ),
				array( 'label' => 'CustomPrice1–3', 'slug' => 'customprice1 … customprice3', 'demo' => '25499' ),
				array( 'label' => 'Pack', 'slug' => 'pack', 'demo' => '395' ),
				array( 'label' => 'Holdback', 'slug' => 'holdback', 'demo' => '812' ),
				array( 'label' => 'Cost', 'slug' => 'cost', 'demo' => '24200' ),
			),
			'Media'       => array(
				array( 'label' => 'PrimaryImageUrl', 'slug' => 'primaryimageurl', 'demo' => 'https://cdn.example.com/vehicles/abc123.jpg' ),
				array( 'label' => 'PrimaryThumbUrl', 'slug' => 'primarythumburl', 'demo' => 'https://cdn.example.com/vehicles/abc123_thumb.jpg' ),
				array( 'label' => 'Use Images list', 'slug' => 'use_images_list', 'demo' => 'primary' ),
				array( 'label' => 'ImageUrlList Primary', 'slug' => 'gallery', 'demo' => '[ { image_url: … }, … ]' ),
				array( 'label' => 'ImageUrlList Alternative', 'slug' => 'gallery_srp', 'demo' => '[ { image_url: … }, … ]' ),
			),
			'Description' => array(
				array( 'label' => 'Description', 'slug' => 'vehicle_overview', 'demo' => 'This Camry SE features…' ),
				array( 'label' => 'Photo_Timestamp', 'slug' => 'photo_timestamp', 'demo' => '1735689600' ),
				array( 'label' => 'PassengerCapacity', 'slug' => 'passengercapacity', 'demo' => '5' ),
				array( 'label' => 'MarketClass', 'slug' => 'marketclass', 'demo' => 'Midsize Cars' ),
				array( 'label' => 'Factory_Codes', 'slug' => 'factory_codes', 'demo' => '1234, 5678' ),
				array( 'label' => 'Certified', 'slug' => 'certified', 'demo' => 'Yes' ),
				array( 'label' => 'Certified Custom URL', 'slug' => 'certified_custom_url', 'demo' => 'https://example.com/certified' ),
				array( 'label' => 'Int_Color_Code', 'slug' => 'int_color_code', 'demo' => '1G3' ),
				array( 'label' => 'Ext_Color_Code', 'slug' => 'ext_color_code', 'demo' => '1F7' ),
				array( 'label' => 'ModelNumber', 'slug' => 'modelnumber', 'demo' => '2532' ),
				array( 'label' => 'Doors', 'slug' => 'doors', 'demo' => '4' ),
			),
		);
	}

	/**
	 * Mask an API key leaving only first/last 4 chars visible.
	 *
	 * @param string $key
	 * @return string
	 */
	private function mask_key( string $key ): string {
		$len = strlen( $key );
		if ( $len < 10 ) {
			return str_repeat( '•', $len );
		}
		return substr( $key, 0, 4 ) . str_repeat( '•', $len - 8 ) . substr( $key, -4 );
	}
}

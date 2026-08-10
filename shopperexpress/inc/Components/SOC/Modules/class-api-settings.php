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
use App\Components\Api\Intice_Rest;
use App\Components\SOC\Contracts\SOC_Module;
use App\Components\SOC\Support\SOC_Cache;
use App\Components\SOC\Support\SOC_Logger;

/**
 * Class Api_Settings
 */
class Api_Settings implements SOC_Module {

	const OPTION_API_MODE       = 'shopperexpress_api_mode_enabled';
	const OPTION_API_KEY        = 'shopperexpress_intice_api_key';
	const OPTION_API_URL        = 'shopperexpress_intice_api_url';
	const OPTION_CACHE_ENABLED  = 'shopperexpress_intice_cache_enabled';
	const OPTION_VEHICLE_FILTERS = 'shopperexpress_vehicle_filters';

	/**
	 * Static reference of filterable vehicle fields — same choices/semantics as the
	 * "Custom Sort" ACF repeater (field values live either at the vehicle's top level
	 * or inside the dealer-mapped `payload` bag; Intice_Rest::get_sort_field_value()
	 * already knows how to resolve both). "custom" lets an admin type an exact payload
	 * key when a dealer's mapping doesn't match one of these.
	 *
	 * @var array<string,string>
	 */
	const FILTER_FIELDS = array(
		'condition'         => 'Condition',
		'year'              => 'Year',
		'body_style'        => 'Body Style',
		'make'              => 'Make',
		'model'             => 'Model',
		'drivetrain'        => 'Drivetrain',
		'trim'              => 'Trim',
		'engine'            => 'Engine',
		'transmission'      => 'Transmission',
		'exterior_color'    => 'Exterior Color',
		'interior_color'    => 'Interior Color',
		'fuel_type'         => 'Fuel Type',
		'mileage'           => 'Mileage',
		'certified'         => 'Certified',
		'vehicle-status'    => 'Vehicle Status',
		'price_sort'        => 'Price (sort)',
		'original_price'    => 'MSRP',
		'loan_payment_sort' => 'Loan Payment (sort)',
		'dateinstock'       => 'DateInStock',
		'custom'            => 'Custom field key…',
	);

	/**
	 * @var array<string,string>
	 */
	const FILTER_OPERATORS = array(
		'>=' => '>=',
		'<=' => '<=',
		'>'  => '>',
		'<'  => '<',
		'='  => '=',
		'!=' => '≠',
	);

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
			'filters'          => array(
				'listings'      => $this->get_filters( 'listings' ),
				'used-listings' => $this->get_filters( 'used-listings' ),
			),
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
	 * Get the saved filter rows for a post type, for rendering the SOC panel's repeater.
	 * Always includes at least one (empty) row so the repeater never renders blank.
	 *
	 * @param string $post_type 'listings' or 'used-listings'.
	 * @return array<int,array{field:string,custom_key:string,operator:string,value:string}>
	 */
	public function get_filters( string $post_type ): array {
		$all  = get_option( self::OPTION_VEHICLE_FILTERS, array() );
		$rows = is_array( $all[ $post_type ] ?? null ) ? $all[ $post_type ] : array();

		if ( empty( $rows ) ) {
			$rows[] = array( 'field' => '', 'custom_key' => '', 'operator' => '>=', 'value' => '' );
		}

		return $rows;
	}

	/**
	 * Get only the saved (non-empty) filter rows for a post type — used by Intice_Rest
	 * to actually apply the filters, as opposed to get_filters() which always includes
	 * a trailing blank row for rendering the SOC panel's repeater.
	 *
	 * @param string $post_type 'listings' or 'used-listings'.
	 * @return array<int,array{field:string,custom_key:string,operator:string,value:string}>
	 */
	public static function get_active_filters( string $post_type ): array {
		$all = get_option( self::OPTION_VEHICLE_FILTERS, array() );
		return is_array( $all[ $post_type ] ?? null ) ? $all[ $post_type ] : array();
	}

	/**
	 * Save the filter rows for a post type — empty rows (no field/custom key or no value) are dropped.
	 *
	 * @param string $post_type 'listings' or 'used-listings'.
	 * @param array  $rows      Raw rows from the SOC panel: [{field, custom_key, operator, value}, ...].
	 * @return void
	 */
	public function save_filters( string $post_type, array $rows ): void {
		$sanitized = array();

		foreach ( $rows as $row ) {
			$field      = sanitize_key( $row['field'] ?? '' );
			$custom_key = sanitize_key( $row['custom_key'] ?? '' );
			$operator   = sanitize_text_field( $row['operator'] ?? '>=' );
			$value      = sanitize_text_field( $row['value'] ?? '' );

			$key = 'custom' === $field ? $custom_key : $field;

			if ( '' === $key || '' === $value || ! isset( self::FILTER_OPERATORS[ $operator ] ) ) {
				continue;
			}

			if ( 'custom' !== $field && ! isset( self::FILTER_FIELDS[ $field ] ) ) {
				continue;
			}

			$sanitized[] = array(
				'field'      => $field,
				'custom_key' => $custom_key,
				'operator'   => $operator,
				'value'      => $value,
			);
		}

		$all               = get_option( self::OPTION_VEHICLE_FILTERS, array() );
		$all[ $post_type ] = $sanitized;
		update_option( self::OPTION_VEHICLE_FILTERS, $all );

		SOC_Cache::forget( $this->get_slug(), 'data' );
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
	 * Reads from Intice_Api_Client's PHP-side key registry rather than
	 * querying wp_options directly — a persistent object cache (Redis/Memcached,
	 * e.g. Object Cache Pro) stores transients outside the options table
	 * entirely, so a raw SQL LIKE query against wp_options would always read
	 * back empty even though the cache is live. The registry is backend-agnostic.
	 *
	 * @return array
	 */
	public function collect_api_cache(): array {
		$registry = Intice_Api_Client::get_registry();
		$now      = time();

		$groups = array(
			array( 'key' => 'vehicles', 'label' => 'Vehicles (SRP)', 'ttl' => Intice_Api_Client::CACHE_TTL_VEHICLES, 'api_group' => true ),
			array( 'key' => 'vehicle', 'label' => 'Single Vehicle (VDP)', 'ttl' => Intice_Api_Client::CACHE_TTL_VEHICLE, 'api_group' => true ),
			array( 'key' => 'meta', 'label' => 'Filters Meta', 'ttl' => Intice_Api_Client::CACHE_TTL_META, 'api_group' => true ),
			array( 'key' => 'srp_listings', 'label' => 'New Listings (SRP)', 'ttl' => Intice_Rest::CACHE_TTL, 'api_group' => false ),
			array( 'key' => 'srp_used-listings', 'label' => 'Used Listings (SRP)', 'ttl' => Intice_Rest::CACHE_TTL, 'api_group' => false ),
			array( 'key' => 'srp_listings_custom', 'label' => 'New Listings (Custom Sort)', 'ttl' => Intice_Rest::CACHE_TTL, 'api_group' => false ),
			array( 'key' => 'srp_used-listings_custom', 'label' => 'Used Listings (Custom Sort)', 'ttl' => Intice_Rest::CACHE_TTL, 'api_group' => false ),
		);

		$rows = array();

		foreach ( $groups as $group ) {
			$live_entries  = array();
			$stale_entries = array();

			foreach ( $registry as $cache_key => $meta ) {
				if ( ( $meta['group'] ?? '' ) !== $group['key'] ) {
					continue;
				}

				$is_stale_key = 0 === strpos( $cache_key, Intice_Api_Client::CACHE_PREFIX_STALE )
					|| 0 === strpos( $cache_key, Intice_Rest::CACHE_PREFIX_STALE );

				if ( $is_stale_key ) {
					$stale_entries[ $cache_key ] = $meta;
				} else {
					$live_entries[ $cache_key ] = $meta;
				}
			}

			$count      = count( $live_entries );
			$status     = 'missing';
			$expires_at = null;

			if ( $count > 0 ) {
				$earliest = min( array_column( $live_entries, 'expires_at' ) );

				if ( $earliest > $now ) {
					$status     = 'valid';
					$expires_at = human_time_diff( $now, $earliest ) . ' left';
				} else {
					$status     = 'expired';
					$expires_at = 'Expired';
				}
			}

			if ( 'missing' === $status && ! empty( $stale_entries ) ) {
				$status = 'stale';
			}

			$rows[] = array(
				'label'      => $group['label'],
				'key'        => $group['key'],
				'count'      => $count,
				'status'     => $status,
				'expires_at' => $expires_at,
				'ttl_label'  => human_readable_duration( gmdate( 'H:i:s', $group['ttl'] ) ),
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
		$flushed = $client->flush_cache();

		$user = wp_get_current_user();
		SOC_Logger::write( 'cache', 'Intice API cache flushed by: ' . ( $user->user_email ?: 'unknown' ) );
		SOC_Cache::forget( $this->get_slug(), 'data' );

		return $flushed;
	}

	/**
	 * Flush a specific cache group.
	 *
	 * @param string $group  'vehicles', 'vehicle', 'meta', 'new', 'used', 'new-custom', or 'used-custom'.
	 * @return int Deleted rows.
	 */
	public function flush_api_cache_group( string $group ): int {
		$srp_map = array(
			'new'         => array( 'listings', false ),
			'used'        => array( 'used-listings', false ),
			'new-custom'  => array( 'listings', true ),
			'used-custom' => array( 'used-listings', true ),
		);

		if ( isset( $srp_map[ $group ] ) ) {
			[ $post_type, $custom_sort ] = $srp_map[ $group ];
			$deleted = Intice_Rest::clear_cache( $post_type, $custom_sort );

			$user = wp_get_current_user();
			SOC_Logger::write( 'cache', "Intice SRP cache flushed [{$group}] by: " . ( $user->user_email ?: 'unknown' ) );
			SOC_Cache::forget( $this->get_slug(), 'data' );

			return $deleted;
		}

		if ( ! in_array( $group, array( 'vehicles', 'vehicle', 'meta' ), true ) ) {
			return 0;
		}

		// Stale-while-revalidate: existing data keeps being served from a 2h stale copy
		// while a background cron regenerates the live cache for this group.
		$deleted = Intice_Api_Client::instance()->flush_group( $group );

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

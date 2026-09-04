<?php
/**
 * WordPress JSON LD.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class JSON_LD
 *
 * @package App\Base\Component
 */
class JSON_LD implements Theme_Component {

	/**
	 * Default field config — matches current hardcoded behaviour.
	 * mode:'legacy' means this config is ignored and legacy methods run as-is.
	 *
	 * @var array
	 */
	private const DEFAULT_CONFIG = array(
		'mode'          => 'legacy',
		'post_types'    => array( 'listings', 'used-listings', 'lease-offers', 'finance-offers', 'conditional-offers' ),
		'archive_limit' => 24,
		'vehicle'       => array(
			'name'                        => array( 'enabled' => true ),
			'description'                 => array( 'enabled' => true ),
			'image'                       => array( 'enabled' => true ),
			'brand'                       => array( 'enabled' => true,  'acf_key' => 'make'           ),
			'model'                       => array( 'enabled' => true,  'acf_key' => 'model'          ),
			'vehicleConfiguration'        => array( 'enabled' => true,  'acf_key' => 'trim'           ),
			'vehicleModelDate'            => array( 'enabled' => true,  'acf_key' => 'year'           ),
			'bodyType'                    => array( 'enabled' => true,  'acf_key' => 'body_style'     ),
			'vehicleIdentificationNumber' => array( 'enabled' => true,  'acf_key' => 'vin'            ),
			'vehicleEngine'               => array( 'enabled' => true,  'acf_key' => 'engine'         ),
			'fuelType'                    => array( 'enabled' => true,  'acf_key' => 'fuel_type'      ),
			'vehicleTransmission'         => array( 'enabled' => true,  'acf_key' => 'transmission'   ),
			'mileageFromOdometer'         => array( 'enabled' => true,  'acf_key' => 'mileage'        ),
			'color'                       => array( 'enabled' => true,  'acf_key' => 'exterior_color' ),
			'vehicleInteriorColor'        => array( 'enabled' => false, 'acf_key' => 'interior_color' ),
			'offers'                      => array( 'enabled' => true ),
			'offers_sub'                  => array(
				'price_currency' => 'USD',
				'price_key'      => 'price',
				'availability'   => 'InStock',
				'condition'      => 'auto',
				'seller_name_key' => 'dealer_name',
				'seller_url_key'  => 'dealer_url',
			),
			'additionalProperty'          => array( 'enabled' => true ),
			'features_limit'              => 0,
			// Extended Specs (all off by default — opt-in).
			'numberOfDoors'              => array( 'enabled' => false, 'acf_key' => 'doors'             ),
			'driveWheelConfiguration'    => array( 'enabled' => false, 'acf_key' => 'drive_type'        ),
			'vehicleSeatingCapacity'     => array( 'enabled' => false, 'acf_key' => 'seating_capacity'  ),
			'fuelConsumption'            => array( 'enabled' => false, 'acf_key' => 'mpg_city'          ),
			'knownVehicleDamages'        => array( 'enabled' => false, 'acf_key' => 'known_damages'     ),
			'vehicleSpecialUsage'        => array( 'enabled' => false, 'acf_key' => 'certified'         ),
			'meetsEmissionStandard'      => array( 'enabled' => false, 'acf_key' => 'emission_standard' ),
			'numberOfForwardGears'       => array( 'enabled' => false, 'acf_key' => 'gears'             ),
		),
		'custom_properties' => array(),
		'lease_offer'       => array(
			'name'          => array( 'enabled' => true,  'acf_key' => '',            'static_value' => '' ),
			'description'   => array( 'enabled' => true,  'acf_key' => 'description', 'static_value' => '' ),
			'priceCurrency' => array( 'enabled' => true,  'acf_key' => '',            'static_value' => 'USD' ),
			'price'         => array( 'enabled' => true,  'acf_key' => 'payment',     'static_value' => '' ),
			'validThrough'  => array( 'enabled' => true,  'acf_key' => 'end_date',    'static_value' => '' ),
			'availability'  => array( 'enabled' => true,  'acf_key' => '',            'static_value' => 'InStock' ),
			'url'           => array( 'enabled' => true,  'acf_key' => '',            'static_value' => '' ),
			'seller'        => array( 'enabled' => true,  'acf_key' => 'dealer_name', 'static_value' => '' ),
		),
		'finance_offer'     => array(
			'name'          => array( 'enabled' => true,  'acf_key' => '',            'static_value' => '' ),
			'description'   => array( 'enabled' => true,  'acf_key' => 'description', 'static_value' => '' ),
			'priceCurrency' => array( 'enabled' => true,  'acf_key' => '',            'static_value' => 'USD' ),
			'price'         => array( 'enabled' => true,  'acf_key' => 'payment',     'static_value' => '' ),
			'validThrough'  => array( 'enabled' => true,  'acf_key' => 'end_date',    'static_value' => '' ),
			'availability'  => array( 'enabled' => true,  'acf_key' => '',            'static_value' => 'InStock' ),
			'url'           => array( 'enabled' => true,  'acf_key' => '',            'static_value' => '' ),
			'seller'        => array( 'enabled' => true,  'acf_key' => 'dealer_name', 'static_value' => '' ),
		),
		'conditional_offer' => array(
			'name'          => array( 'enabled' => true,  'acf_key' => '',                 'static_value' => '' ),
			'description'   => array( 'enabled' => true,  'acf_key' => 'description',      'static_value' => '' ),
			'priceCurrency' => array( 'enabled' => true,  'acf_key' => '',                 'static_value' => 'USD' ),
			'price'         => array( 'enabled' => true,  'acf_key' => 'conditional_cash', 'static_value' => '' ),
			'validThrough'  => array( 'enabled' => true,  'acf_key' => 'end_date',         'static_value' => '' ),
			'availability'  => array( 'enabled' => true,  'acf_key' => '',                 'static_value' => 'InStock' ),
			'url'           => array( 'enabled' => true,  'acf_key' => '',                 'static_value' => '' ),
		),
		'service_offer'     => array(
			'name'          => array( 'enabled' => true,  'acf_key' => '',             'static_value' => '' ),
			'description'   => array( 'enabled' => true,  'acf_key' => 'description',  'static_value' => '' ),
			'priceCurrency' => array( 'enabled' => true,  'acf_key' => '',             'static_value' => 'USD' ),
			'price'         => array( 'enabled' => true,  'acf_key' => 'payment',      'static_value' => '' ),
			'validThrough'  => array( 'enabled' => true,  'acf_key' => 'end_date',     'static_value' => '' ),
			'availability'  => array( 'enabled' => true,  'acf_key' => '',             'static_value' => 'InStock' ),
			'url'           => array( 'enabled' => true,  'acf_key' => '',             'static_value' => '' ),
			'seller'        => array( 'enabled' => true,  'acf_key' => 'dealer_name',  'static_value' => '' ),
			'itemOffered'   => array( 'enabled' => false, 'acf_key' => 'service_name', 'static_value' => '' ),
		),
		'archive_srp'          => array(
			'name'        => array( 'enabled' => true, 'acf_key' => '', 'static_value' => 'New & Used Vehicles' ),
			'description' => array( 'enabled' => true, 'acf_key' => '', 'static_value' => 'Browse our inventory of new and pre-owned vehicles.' ),
			'url'         => array( 'enabled' => true, 'acf_key' => '', 'static_value' => '' ),
			'hasPart'     => array( 'enabled' => true, 'acf_key' => '', 'static_value' => '' ),
		),
		'archive_srp_listings' => array(
			'name'        => array( 'enabled' => true, 'acf_key' => '', 'static_value' => 'New Vehicles' ),
			'description' => array( 'enabled' => true, 'acf_key' => '', 'static_value' => 'Browse our new vehicle inventory.' ),
			'url'         => array( 'enabled' => true, 'acf_key' => '', 'static_value' => '' ),
			'hasPart'     => array( 'enabled' => true, 'acf_key' => '', 'static_value' => '' ),
		),
		'archive_srp_used_listings' => array(
			'name'        => array( 'enabled' => true, 'acf_key' => '', 'static_value' => 'Pre-Owned Vehicles' ),
			'description' => array( 'enabled' => true, 'acf_key' => '', 'static_value' => 'Browse our pre-owned vehicle inventory.' ),
			'url'         => array( 'enabled' => true, 'acf_key' => '', 'static_value' => '' ),
			'hasPart'     => array( 'enabled' => true, 'acf_key' => '', 'static_value' => '' ),
		),
		'srp_item'          => array(
			'brand'                       => array( 'enabled' => true ),
			'model'                       => array( 'enabled' => true ),
			'vehicleModelDate'            => array( 'enabled' => true ),
			'vehicleIdentificationNumber' => array( 'enabled' => true ),
			'image'                       => array( 'enabled' => true ),
			'offers'                      => array( 'enabled' => true ),
		),
		'srp_item_offers_sub' => array(
			'price_currency'  => 'USD',
			'price_key'       => 'price',
			'availability'    => 'InStock',
			'condition'       => 'auto',
			'show_seller'     => '1',
			'seller_name_key' => 'dealer_name',
		),
		'lease_offer_srp'   => array(
			'name'        => array( 'enabled' => true, 'acf_key' => '', 'static_value' => 'Lease Offers' ),
			'description' => array( 'enabled' => true, 'acf_key' => '', 'static_value' => 'Browse our current lease offers and special deals.' ),
			'url'         => array( 'enabled' => true, 'acf_key' => '', 'static_value' => '' ),
			'hasPart'     => array( 'enabled' => true, 'acf_key' => '', 'static_value' => '' ),
		),
		'finance_offer_srp' => array(
			'name'        => array( 'enabled' => true, 'acf_key' => '', 'static_value' => 'Finance Offers' ),
			'description' => array( 'enabled' => true, 'acf_key' => '', 'static_value' => 'Browse our current finance offers and loan specials.' ),
			'url'         => array( 'enabled' => true, 'acf_key' => '', 'static_value' => '' ),
			'hasPart'     => array( 'enabled' => true, 'acf_key' => '', 'static_value' => '' ),
		),
		'conditional_offer_srp' => array(
			'name'        => array( 'enabled' => true, 'acf_key' => '', 'static_value' => 'Conditional Offers' ),
			'description' => array( 'enabled' => true, 'acf_key' => '', 'static_value' => 'Browse our time-limited conditional offers and promotions.' ),
			'url'         => array( 'enabled' => true, 'acf_key' => '', 'static_value' => '' ),
			'hasPart'     => array( 'enabled' => true, 'acf_key' => '', 'static_value' => '' ),
		),
		'service_offer_srp' => array(
			'name'        => array( 'enabled' => true, 'acf_key' => '', 'static_value' => 'Service Offers' ),
			'description' => array( 'enabled' => true, 'acf_key' => '', 'static_value' => 'Browse our current service specials and maintenance offers.' ),
			'url'         => array( 'enabled' => true, 'acf_key' => '', 'static_value' => '' ),
			'hasPart'     => array( 'enabled' => true, 'acf_key' => '', 'static_value' => '' ),
		),
		'research_srp'      => array(
			'name'        => array( 'enabled' => true, 'acf_key' => '', 'static_value' => 'Research & Buying Guides' ),
			'description' => array( 'enabled' => true, 'acf_key' => '', 'static_value' => 'Browse our expert buying guides and vehicle research articles.' ),
			'url'         => array( 'enabled' => true, 'acf_key' => '', 'static_value' => '' ),
			'hasPart'     => array( 'enabled' => true, 'acf_key' => '', 'static_value' => '' ),
		),
		'research'          => array(
			'headline'      => array( 'enabled' => true,  'acf_key' => '',               'static_value' => '' ),
			'description'   => array( 'enabled' => true,  'acf_key' => 'description',    'static_value' => '' ),
			'author'        => array( 'enabled' => true,  'acf_key' => '',               'static_value' => '' ),
			'datePublished' => array( 'enabled' => true,  'acf_key' => '',               'static_value' => '' ),
			'image'         => array( 'enabled' => true,  'acf_key' => 'featured_image', 'static_value' => '' ),
		),
	);

	/**
	 * Default ACF / API source keys per property.
	 * Empty string = system-resolved (not a simple field lookup).
	 *
	 * @var array
	 */
	public const DEFAULT_SOURCES = array(
		'name'                        => array( 'acf' => '',               'api' => ''               ),
		'description'                 => array( 'acf' => 'ai_vdp_description', 'api' => ''           ),
		'image'                       => array( 'acf' => '',               'api' => ''               ),
		'brand'                       => array( 'acf' => 'make',           'api' => 'make'           ),
		'model'                       => array( 'acf' => 'model',          'api' => 'model'          ),
		'vehicleConfiguration'        => array( 'acf' => 'trim',           'api' => 'trim'           ),
		'vehicleModelDate'            => array( 'acf' => 'year',           'api' => 'year'           ),
		'bodyType'                    => array( 'acf' => 'body_style',     'api' => 'body_style'     ),
		'vehicleIdentificationNumber' => array( 'acf' => 'vin',            'api' => 'vin'            ),
		'vehicleEngine'               => array( 'acf' => 'engine',         'api' => 'engine'         ),
		'fuelType'                    => array( 'acf' => 'fuel_type',      'api' => 'fuel_type'      ),
		'vehicleTransmission'         => array( 'acf' => 'transmission',   'api' => 'transmission'   ),
		'mileageFromOdometer'         => array( 'acf' => 'mileage',        'api' => 'mileage'        ),
		'color'                       => array( 'acf' => 'exterior_color', 'api' => 'exterior_color' ),
		'vehicleInteriorColor'        => array( 'acf' => 'interior_color', 'api' => 'interior_color' ),
		'offers'                      => array( 'acf' => '',               'api' => ''               ),
		'additionalProperty'          => array( 'acf' => '',               'api' => ''               ),
		'numberOfDoors'               => array( 'acf' => 'doors',             'api' => 'doors'             ),
		'driveWheelConfiguration'     => array( 'acf' => 'drive_type',        'api' => 'drive_type'        ),
		'vehicleSeatingCapacity'      => array( 'acf' => 'seating_capacity',  'api' => 'seating_capacity'  ),
		'fuelConsumption'             => array( 'acf' => 'mpg_city',          'api' => 'mpg_city'          ),
		'knownVehicleDamages'         => array( 'acf' => 'known_damages',     'api' => 'known_damages'     ),
		'vehicleSpecialUsage'         => array( 'acf' => 'certified',         'api' => 'certified'         ),
		'meetsEmissionStandard'       => array( 'acf' => 'emission_standard', 'api' => 'emission_standard' ),
		'numberOfForwardGears'        => array( 'acf' => 'gears',             'api' => 'gears'             ),
	);

	/**
	 * Offer post types that use Offer schema instead of Vehicle schema.
	 *
	 * @var string[]
	 */
	private array $offer_types = array( 'offers', 'lease-offers', 'finance-offers', 'conditional-offers', 'research' );

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_head', array( $this, 'render_json' ) );
	}

	// ── Config ────────────────────────────────────────────────────────────────

	/**
	 * Merge saved option with defaults.
	 *
	 * @return array
	 */
	public static function get_config(): array {
		$config = array_replace_recursive(
			self::DEFAULT_CONFIG,
			(array) get_option( 'json_ld_field_config', array() )
		);
		// Fill type-specific vehicle configs from the base 'vehicle' defaults when not yet saved separately.
		if ( empty( $config['vehicle_listings'] ) ) {
			$config['vehicle_listings'] = $config['vehicle'];
		}
		if ( empty( $config['vehicle_used_listings'] ) ) {
			$config['vehicle_used_listings'] = $config['vehicle'];
		}
		if ( empty( $config['srp_item_listings'] ) ) {
			$config['srp_item_listings'] = $config['srp_item'];
		}
		if ( empty( $config['srp_item_used_listings'] ) ) {
			$config['srp_item_used_listings'] = $config['srp_item'];
		}
		if ( empty( $config['archive_srp_listings'] ) ) {
			$config['archive_srp_listings'] = $config['archive_srp'];
		}
		if ( empty( $config['archive_srp_used_listings'] ) ) {
			$config['archive_srp_used_listings'] = $config['archive_srp'];
		}
		if ( empty( $config['srp_item_offers_sub_listings'] ) ) {
			$config['srp_item_offers_sub_listings'] = $config['srp_item_offers_sub'];
		}
		if ( empty( $config['srp_item_offers_sub_used_listings'] ) ) {
			$config['srp_item_offers_sub_used_listings'] = $config['srp_item_offers_sub'];
		}
		// Ensure offers_sub and features_limit are present in per-type vehicle configs.
		foreach ( array( 'vehicle_listings', 'vehicle_used_listings' ) as $vt_key ) {
			if ( empty( $config[ $vt_key ]['offers_sub'] ) ) {
				$config[ $vt_key ]['offers_sub'] = $config['vehicle']['offers_sub'];
			}
			if ( ! isset( $config[ $vt_key ]['features_limit'] ) ) {
				$config[ $vt_key ]['features_limit'] = $config['vehicle']['features_limit'] ?? 0;
			}
		}
		return $config;
	}

	/**
	 * True when the builder is active and drives field output.
	 *
	 * @return bool
	 */
	public static function is_builder_mode(): bool {
		return 'builder' === ( self::get_config()['mode'] ?? 'legacy' );
	}

	/**
	 * Normalise a single property entry from the saved config.
	 * Handles both old bool format and new array format for backward compat.
	 *
	 * Returns: ['enabled' => bool, 'acf_key' => string, 'static_value' => string]
	 *
	 * @param array  $vehicle_cfg The 'vehicle' sub-array from get_config().
	 * @param string $key         Property key, e.g. 'brand'.
	 * @return array
	 */
	public static function prop_cfg( array $vehicle_cfg, string $key ): array {
		$defaults = array(
			'enabled'      => false,
			'acf_key'      => self::DEFAULT_SOURCES[ $key ]['acf'] ?? '',
			'static_value' => '',
		);

		$raw = $vehicle_cfg[ $key ] ?? false;

		if ( is_bool( $raw ) ) {
			return array_merge( $defaults, array( 'enabled' => $raw ) );
		}

		return array_merge( $defaults, (array) $raw );
	}

	/**
	 * Read an ACF field value for the builder path.
	 * Returns static_value if set, otherwise reads from the resolved ACF key.
	 *
	 * @param array  $p       Normalised prop config from prop_cfg().
	 * @param string $default Fallback ACF field name if acf_key is empty.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	private static function read_acf( array $p, string $default, int $post_id ): string {
		if ( '' !== $p['static_value'] ) {
			return wp_strip_all_tags( $p['static_value'] );
		}
		$key = $p['acf_key'] ?: $default;
		return wp_strip_all_tags( (string) get_field( $key, $post_id ) );
	}

	/**
	 * Read an API $v field for the builder path.
	 * Returns static_value if set, otherwise reads from the resolved acf_key.
	 *
	 * @param array  $p       Normalised prop config from prop_cfg().
	 * @param string $default Fallback $v array key if acf_key is empty.
	 * @param array  $v       Vehicle data from Intice API.
	 * @return string
	 */
	private static function read_api( array $p, string $default, array $v ): string {
		if ( '' !== $p['static_value'] ) {
			return wp_strip_all_tags( $p['static_value'] );
		}
		$key = $p['acf_key'] ?: $default;
		return wp_strip_all_tags( (string) ( $v[ $key ] ?? '' ) );
	}

	// ── Hooks ─────────────────────────────────────────────────────────────────

	/**
	 * Render JSON LD.
	 *
	 * @return void
	 */
	public function render_json(): void {

		$singular_types    = array( 'listings', 'used-listings' );
		$is_singular_vdp   = is_singular( $singular_types );
		$is_singular_offer = is_singular( $this->offer_types );
		$is_page_with_pt   = is_page() && get_field( 'post_type' );
		$is_archive_pt     = is_post_type_archive();

		if ( ! $is_singular_vdp && ! $is_singular_offer && ! $is_page_with_pt && ! $is_archive_pt ) {
			return;
		}

		// In API mode, vehicle VDPs are served by single-listings-api.php which
		// registers its own wp_head JSON-LD action (priority 5). The fake queried
		// object has ID=0, so bail here to avoid a duplicate schema block.
		if ( \App\is_api_mode() && $is_singular_vdp ) {
			global $wp_query;
			$qo = $wp_query->queried_object ?? null;
			if ( $qo && 0 === (int) ( $qo->ID ?? -1 ) ) {
				return;
			}
		}
		// phpcs:ignore
		echo $this->get_json();
	}

	/**
	 * Get JSON LD.
	 *
	 * @return string
	 */
	public function get_json(): string {

		$singular_types = array( 'listings', 'used-listings' );

		$post_id = get_the_ID();

		$acf_post_type = function_exists( 'get_field' )
		? get_field( 'post_type', $post_id )
		: null;

		$is_vdp_single =
		is_singular( $singular_types )
		&& ! is_post_type_archive();

		$is_offer_single =
		is_singular( $this->offer_types )
		&& ! is_post_type_archive();

		$is_page_archive =
		is_page()
		&& in_array( $acf_post_type, array_merge( $singular_types, $this->offer_types ), true );

		$is_archive =
		is_post_type_archive()
		|| $is_page_archive;

		if ( $is_vdp_single ) {
			return self::is_builder_mode()
				? $this->get_single_vehicle_json_builder()
				: $this->get_single_vehicle_json_legacy();
		}

		if ( $is_offer_single ) {
			return $this->get_single_offer_json();
		}

		if ( $is_archive ) {
			return $this->get_collection_json();
		}

		return '';
	}

	// ── Vehicle — Legacy path (unchanged) ────────────────────────────────────

	/**
	 * Build Vehicle JSON-LD using the original hardcoded field list.
	 * Called when mode = 'legacy'.
	 *
	 * @return string
	 */
	private function get_single_vehicle_json_legacy(): string {
		$post_id   = get_the_ID();
		$post_type = get_post_type( $post_id );
		$permalink = get_permalink( $post_id );

		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Vehicle',
			'@id'      => esc_url( $permalink ) . '/#vehicle',
			'url'      => esc_url( $permalink ),
		);

		$title = wp_strip_all_tags( get_the_title( $post_id ) );
		if ( $title ) {
			$schema['name'] = $title;
		}

		$ai_desc = get_field( 'ai_vdp_description', $post_id );
		if ( $ai_desc ) {
			$schema['description'] = wp_strip_all_tags( $ai_desc );
		}

		$thumbnail = get_the_post_thumbnail_url( $post_id, 'full' );
		if ( $thumbnail ) {
			$schema['image'] = esc_url( $thumbnail );
		}

		$make = wp_strip_all_tags( (string) get_field( 'make', $post_id ) );
		if ( $make ) {
			$schema['brand'] = array(
				'@type' => 'Brand',
				'name'  => $make,
			);
		}

		$model = wp_strip_all_tags( (string) get_field( 'model', $post_id ) );
		if ( $model ) {
			$schema['model'] = $model;
		}

		$trim = wp_strip_all_tags( (string) get_field( 'trim', $post_id ) );
		if ( $trim ) {
			$schema['vehicleConfiguration'] = $trim;
		}

		$year = get_field( 'year', $post_id );
		if ( $year ) {
			$schema['vehicleModelDate'] = (string) $year;
		}

		$mileage = get_field( 'mileage', $post_id );
		if ( $mileage ) {
			$schema['mileageFromOdometer'] = array(
				'@type'    => 'QuantitativeValue',
				'value'    => (int) $mileage,
				'unitCode' => 'SMI',
			);
		}

		$vin = wp_strip_all_tags( (string) get_field( 'vin', $post_id ) );
		if ( $vin ) {
			$schema['vehicleIdentificationNumber'] = $vin;
		}

		$engine = wp_strip_all_tags( (string) get_field( 'engine', $post_id ) );
		if ( $engine ) {
			$schema['vehicleEngine'] = array(
				'@type'       => 'EngineSpecification',
				'description' => $engine,
			);
		}

		$fuel = wp_strip_all_tags( (string) get_field( 'fuel_type', $post_id ) );
		if ( $fuel ) {
			$schema['fuelType'] = $fuel;
		}

		$transmission = wp_strip_all_tags( (string) get_field( 'transmission', $post_id ) );
		if ( $transmission ) {
			$schema['vehicleTransmission'] = $transmission;
		}

		$body_style = wp_strip_all_tags( (string) get_field( 'body_style', $post_id ) );
		if ( $body_style ) {
			$schema['bodyType'] = $body_style;
		}

		$exterior_color = wp_strip_all_tags( (string) get_field( 'exterior_color', $post_id ) );
		if ( $exterior_color ) {
			$schema['color'] = $exterior_color;
		}

		$interior_color = wp_strip_all_tags( (string) get_field( 'interior_color', $post_id ) );
		if ( $interior_color ) {
			$schema['vehicleInteriorColor'] = $interior_color;
		}

		$condition = ( 'used-listings' === $post_type )
			? 'https://schema.org/UsedCondition'
			: 'https://schema.org/NewCondition';
		$offer     = array(
			'@type'         => 'Offer',
			'priceCurrency' => 'USD',
			'availability'  => 'https://schema.org/InStock',
			'itemCondition' => $condition,
			'url'           => esc_url( $permalink ),
		);

		$price = get_field( 'price', $post_id );
		if ( $price ) {
			$offer['price'] = (string) $price;
		}

		$dealer_name = wp_strip_all_tags( (string) get_field( 'dealer_name', $post_id ) );
		$dealer_url  = get_field( 'dealer_url', $post_id );
		if ( $dealer_name ) {
			$offer['seller'] = self::build_seller_nap( $post_id, $dealer_name, $dealer_url );
		}

		$schema['offers'] = $offer;

		$additional = $this->build_features_additional_property( get_the_ID() );
		if ( ! empty( $additional ) ) {
			$schema['additionalProperty'] = $additional;
		}

		$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );

		return '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n";
	}

	// ── Vehicle — Builder path (config-driven) ────────────────────────────────

	/**
	 * Build Vehicle JSON-LD using the builder field config.
	 * Called when mode = 'builder'.
	 *
	 * @return string
	 */
	private function get_single_vehicle_json_builder(): string {
		$post_id   = get_the_ID();
		$post_type = get_post_type( $post_id );
		$permalink = get_permalink( $post_id );
		$cfg_key = 'used-listings' === $post_type ? 'vehicle_used_listings' : 'vehicle_listings';
		$vcfg    = self::get_config()[ $cfg_key ] ?? array();

		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Vehicle',
			'@id'      => esc_url( $permalink ) . '/#vehicle',
			'url'      => esc_url( $permalink ),
		);

		$p = self::prop_cfg( $vcfg, 'name' );
		if ( $p['enabled'] ) {
			$title = '' !== $p['static_value']
				? wp_strip_all_tags( $p['static_value'] )
				: wp_strip_all_tags( get_the_title( $post_id ) );
			if ( $title ) {
				$schema['name'] = $title;
			}
		}

		$p = self::prop_cfg( $vcfg, 'description' );
		if ( $p['enabled'] ) {
			$desc = self::read_acf( $p, 'ai_vdp_description', $post_id );
			if ( $desc ) {
				$schema['description'] = $desc;
			}
		}

		$p = self::prop_cfg( $vcfg, 'image' );
		if ( $p['enabled'] ) {
			$img = '' !== $p['static_value']
				? esc_url( $p['static_value'] )
				: get_the_post_thumbnail_url( $post_id, 'full' );
			if ( $img ) {
				$schema['image'] = esc_url( $img );
			}
		}

		$p = self::prop_cfg( $vcfg, 'brand' );
		if ( $p['enabled'] ) {
			$make = self::read_acf( $p, 'make', $post_id );
			if ( $make ) {
				$schema['brand'] = array( '@type' => 'Brand', 'name' => $make );
			}
		}

		$p = self::prop_cfg( $vcfg, 'model' );
		if ( $p['enabled'] ) {
			$model = self::read_acf( $p, 'model', $post_id );
			if ( $model ) {
				$schema['model'] = $model;
			}
		}

		$p = self::prop_cfg( $vcfg, 'vehicleConfiguration' );
		if ( $p['enabled'] ) {
			$trim = self::read_acf( $p, 'trim', $post_id );
			if ( $trim ) {
				$schema['vehicleConfiguration'] = $trim;
			}
		}

		$p = self::prop_cfg( $vcfg, 'vehicleModelDate' );
		if ( $p['enabled'] ) {
			$year = '' !== $p['static_value']
				? wp_strip_all_tags( $p['static_value'] )
				: (string) get_field( $p['acf_key'] ?: 'year', $post_id );
			if ( $year ) {
				$schema['vehicleModelDate'] = $year;
			}
		}

		$p = self::prop_cfg( $vcfg, 'bodyType' );
		if ( $p['enabled'] ) {
			$body = self::read_acf( $p, 'body_style', $post_id );
			if ( $body ) {
				$schema['bodyType'] = $body;
			}
		}

		$p = self::prop_cfg( $vcfg, 'vehicleIdentificationNumber' );
		if ( $p['enabled'] ) {
			$vin = self::read_acf( $p, 'vin', $post_id );
			if ( $vin ) {
				$schema['vehicleIdentificationNumber'] = $vin;
			}
		}

		$p = self::prop_cfg( $vcfg, 'vehicleEngine' );
		if ( $p['enabled'] ) {
			$engine = self::read_acf( $p, 'engine', $post_id );
			if ( $engine ) {
				$schema['vehicleEngine'] = array( '@type' => 'EngineSpecification', 'description' => $engine );
			}
		}

		$p = self::prop_cfg( $vcfg, 'fuelType' );
		if ( $p['enabled'] ) {
			$fuel = self::read_acf( $p, 'fuel_type', $post_id );
			if ( $fuel ) {
				$schema['fuelType'] = $fuel;
			}
		}

		$p = self::prop_cfg( $vcfg, 'vehicleTransmission' );
		if ( $p['enabled'] ) {
			$trans = self::read_acf( $p, 'transmission', $post_id );
			if ( $trans ) {
				$schema['vehicleTransmission'] = $trans;
			}
		}

		$p = self::prop_cfg( $vcfg, 'mileageFromOdometer' );
		if ( $p['enabled'] ) {
			$mileage = '' !== $p['static_value']
				? (int) $p['static_value']
				: (int) get_field( $p['acf_key'] ?: 'mileage', $post_id );
			if ( $mileage ) {
				$schema['mileageFromOdometer'] = array(
					'@type'    => 'QuantitativeValue',
					'value'    => $mileage,
					'unitCode' => 'SMI',
				);
			}
		}

		$p = self::prop_cfg( $vcfg, 'color' );
		if ( $p['enabled'] ) {
			$color = self::read_acf( $p, 'exterior_color', $post_id );
			if ( $color ) {
				$schema['color'] = $color;
			}
		}

		$p = self::prop_cfg( $vcfg, 'vehicleInteriorColor' );
		if ( $p['enabled'] ) {
			$interior = self::read_acf( $p, 'interior_color', $post_id );
			if ( $interior ) {
				$schema['vehicleInteriorColor'] = $interior;
			}
		}

		$p_offers = self::prop_cfg( $vcfg, 'offers' );
		if ( $p_offers['enabled'] ) {
			$osub         = is_array( $vcfg['offers_sub'] ?? null ) ? $vcfg['offers_sub'] : array();
			$currency     = sanitize_text_field( $osub['price_currency'] ?? 'USD' ) ?: 'USD';
			$price_acf    = sanitize_text_field( $osub['price_key'] ?? $osub['price_acf'] ?? 'price' ) ?: 'price';
			$seller_n_acf = sanitize_text_field( $osub['seller_name_key'] ?? $osub['seller_name_acf'] ?? 'dealer_name' ) ?: 'dealer_name';
			$seller_u_acf = sanitize_text_field( $osub['seller_url_key'] ?? $osub['seller_url_acf'] ?? 'dealer_url' ) ?: 'dealer_url';
			$avail_key    = sanitize_text_field( $osub['availability'] ?? 'InStock' ) ?: 'InStock';
			$cond_raw     = sanitize_text_field( $osub['condition'] ?? 'auto' );
			if ( 'auto' === $cond_raw || empty( $cond_raw ) ) {
				$cond_key = ( 'used-listings' === $post_type ) ? 'UsedCondition' : 'NewCondition';
			} else {
				$cond_key = $cond_raw;
			}
			$offer = array(
				'@type'         => 'Offer',
				'priceCurrency' => $currency,
				'availability'  => 'https://schema.org/' . $avail_key,
				'itemCondition' => 'https://schema.org/' . $cond_key,
				'url'           => esc_url( $permalink ),
			);
			$price = get_field( $price_acf, $post_id );
			if ( $price ) {
				$offer['price'] = (string) $price;
			}
			$dealer_name = wp_strip_all_tags( (string) get_field( $seller_n_acf, $post_id ) );
			$dealer_url  = get_field( $seller_u_acf, $post_id );
			if ( $dealer_name ) {
				$offer['seller'] = self::build_seller_nap( $post_id, $dealer_name, $dealer_url );
			}
			$schema['offers'] = $offer;
		}

		$p_feat = self::prop_cfg( $vcfg, 'additionalProperty' );
		if ( $p_feat['enabled'] ) {
			$limit      = (int) ( $vcfg['features_limit'] ?? 0 );
			$additional = $this->build_features_additional_property( $post_id, $limit );
			if ( ! empty( $additional ) ) {
				$schema['additionalProperty'] = $additional;
			}
		}

		// Extended Specs.
		$p = self::prop_cfg( $vcfg, 'numberOfDoors' );
		if ( $p['enabled'] ) {
			$val = self::read_acf( $p, 'doors', $post_id );
			if ( $val ) { $schema['numberOfDoors'] = $val; }
		}

		$p = self::prop_cfg( $vcfg, 'driveWheelConfiguration' );
		if ( $p['enabled'] ) {
			$val = self::read_acf( $p, 'drive_type', $post_id );
			if ( $val ) { $schema['driveWheelConfiguration'] = $val; }
		}

		$p = self::prop_cfg( $vcfg, 'vehicleSeatingCapacity' );
		if ( $p['enabled'] ) {
			$val = self::read_acf( $p, 'seating_capacity', $post_id );
			if ( $val ) { $schema['vehicleSeatingCapacity'] = $val; }
		}

		$p = self::prop_cfg( $vcfg, 'fuelConsumption' );
		if ( $p['enabled'] ) {
			$val = self::read_acf( $p, 'mpg_city', $post_id );
			if ( $val ) {
				$schema['fuelConsumption'] = array( '@type' => 'QuantitativeValue', 'value' => $val, 'unitText' => 'mpg' );
			}
		}

		$p = self::prop_cfg( $vcfg, 'knownVehicleDamages' );
		if ( $p['enabled'] ) {
			$val = self::read_acf( $p, 'known_damages', $post_id );
			if ( $val ) { $schema['knownVehicleDamages'] = $val; }
		}

		$p = self::prop_cfg( $vcfg, 'vehicleSpecialUsage' );
		if ( $p['enabled'] ) {
			$val = self::read_acf( $p, 'certified', $post_id );
			if ( $val ) { $schema['vehicleSpecialUsage'] = $val; }
		}

		$p = self::prop_cfg( $vcfg, 'meetsEmissionStandard' );
		if ( $p['enabled'] ) {
			$val = self::read_acf( $p, 'emission_standard', $post_id );
			if ( $val ) { $schema['meetsEmissionStandard'] = $val; }
		}

		$p = self::prop_cfg( $vcfg, 'numberOfForwardGears' );
		if ( $p['enabled'] ) {
			$val = self::read_acf( $p, 'gears', $post_id );
			if ( $val ) { $schema['numberOfForwardGears'] = $val; }
		}

		// Custom Properties.
		foreach ( self::get_config()['custom_properties'] ?? array() as $cp ) {
			$key = sanitize_key( $cp['key'] ?? '' );
			$val = wp_strip_all_tags( (string) ( $cp['value'] ?? '' ) );
			if ( $key && $val ) {
				$schema[ $key ] = $val;
			}
		}

		$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );

		return '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n";
	}

	// ── API mode static helper ────────────────────────────────────────────────

	/**
	 * Build Vehicle JSON-LD from Intice Nexus API data.
	 * Respects builder config when mode = 'builder', outputs all fields otherwise.
	 *
	 * @param array  $v         Vehicle data from Intice_Api_Client::get_vehicle().
	 * @param string $post_type 'listings' or 'used-listings'.
	 * @param string $url       Canonical VDP URL.
	 * @param string $desc      Pre-built description string (auto-generated or from payload).
	 * @return string Full <script type="application/ld+json"> tag or empty string.
	 */
	public static function build_vehicle_schema_from_api( array $v, string $post_type, string $url, string $desc ): string {
		$use_builder = self::is_builder_mode();
		$vcfg_key    = 'used-listings' === $post_type ? 'vehicle_used_listings' : 'vehicle_listings';
		$vcfg        = $use_builder ? ( self::get_config()[ $vcfg_key ] ?? array() ) : array();

		$prop = function ( string $key ) use ( $use_builder, $vcfg ): array {
			if ( ! $use_builder ) {
				return array( 'enabled' => true, 'acf_key' => '', 'static_value' => '' );
			}
			return self::prop_cfg( $vcfg, $key );
		};

		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Vehicle',
			'@id'      => esc_url( $url ) . '/#vehicle',
			'url'      => esc_url( $url ),
		);

		$p = $prop( 'name' );
		if ( $p['enabled'] ) {
			$name = '' !== $p['static_value']
				? wp_strip_all_tags( $p['static_value'] )
				: trim( implode( ' ', array_filter( array( $v['year'] ?? '', $v['make'] ?? '', $v['model'] ?? '', $v['trim'] ?? '' ) ) ) );
			if ( $name ) {
				$schema['name'] = $name;
			}
		}

		$p = $prop( 'description' );
		if ( $p['enabled'] && $desc ) {
			$schema['description'] = '' !== $p['static_value']
				? wp_strip_all_tags( $p['static_value'] )
				: wp_strip_all_tags( $desc );
		}

		$p = $prop( 'image' );
		if ( $p['enabled'] ) {
			if ( '' !== $p['static_value'] ) {
				$img = $p['static_value'];
			} else {
				$img = $v[ $p['acf_key'] ?: 'images' ][0] ?? ( $v['image'] ?? '' );
				if ( is_array( $img ) ) {
					$img = $img['url'] ?? '';
				}
			}
			if ( $img ) {
				$schema['image'] = esc_url( $img );
			}
		}

		$p = $prop( 'brand' );
		if ( $p['enabled'] ) {
			$make = self::read_api( $p, 'make', $v );
			if ( $make ) {
				$schema['brand'] = array( '@type' => 'Brand', 'name' => $make );
			}
		}

		$p = $prop( 'model' );
		if ( $p['enabled'] ) {
			$model = self::read_api( $p, 'model', $v );
			if ( $model ) {
				$schema['model'] = $model;
			}
		}

		$p = $prop( 'vehicleConfiguration' );
		if ( $p['enabled'] ) {
			$trim = self::read_api( $p, 'trim', $v );
			if ( $trim ) {
				$schema['vehicleConfiguration'] = $trim;
			}
		}

		$p = $prop( 'vehicleModelDate' );
		if ( $p['enabled'] ) {
			$year = '' !== $p['static_value']
				? wp_strip_all_tags( $p['static_value'] )
				: (string) ( $v[ $p['acf_key'] ?: 'year' ] ?? '' );
			if ( $year ) {
				$schema['vehicleModelDate'] = $year;
			}
		}

		$p = $prop( 'bodyType' );
		if ( $p['enabled'] ) {
			$body = self::read_api( $p, 'body_style', $v );
			if ( $body ) {
				$schema['bodyType'] = $body;
			}
		}

		$p = $prop( 'vehicleIdentificationNumber' );
		if ( $p['enabled'] ) {
			$vin = self::read_api( $p, 'vin', $v );
			if ( $vin ) {
				$schema['vehicleIdentificationNumber'] = $vin;
			}
		}

		$p = $prop( 'vehicleEngine' );
		if ( $p['enabled'] ) {
			$engine = self::read_api( $p, 'engine', $v );
			if ( $engine ) {
				$schema['vehicleEngine'] = array( '@type' => 'EngineSpecification', 'description' => $engine );
			}
		}

		$p = $prop( 'fuelType' );
		if ( $p['enabled'] ) {
			$fuel = self::read_api( $p, 'fuel_type', $v );
			if ( $fuel ) {
				$schema['fuelType'] = $fuel;
			}
		}

		$p = $prop( 'vehicleTransmission' );
		if ( $p['enabled'] ) {
			$trans = self::read_api( $p, 'transmission', $v );
			if ( $trans ) {
				$schema['vehicleTransmission'] = $trans;
			}
		}

		$p = $prop( 'mileageFromOdometer' );
		if ( $p['enabled'] ) {
			$mileage = '' !== $p['static_value']
				? (int) $p['static_value']
				: (int) ( $v[ $p['acf_key'] ?: 'mileage' ] ?? 0 );
			if ( $mileage ) {
				$schema['mileageFromOdometer'] = array(
					'@type'    => 'QuantitativeValue',
					'value'    => $mileage,
					'unitCode' => 'SMI',
				);
			}
		}

		$p = $prop( 'color' );
		if ( $p['enabled'] ) {
			$color = self::read_api( $p, 'exterior_color', $v );
			if ( $color ) {
				$schema['color'] = $color;
			}
		}

		$p = $prop( 'vehicleInteriorColor' );
		if ( $p['enabled'] ) {
			$interior = self::read_api( $p, 'interior_color', $v );
			if ( $interior ) {
				$schema['vehicleInteriorColor'] = $interior;
			}
		}

		$p_offers = $prop( 'offers' );
		if ( $p_offers['enabled'] ) {
			$osub         = is_array( $vcfg['offers_sub'] ?? null ) ? $vcfg['offers_sub'] : array();
			$currency     = sanitize_text_field( $osub['price_currency'] ?? 'USD' ) ?: 'USD';
			$price_api    = sanitize_text_field( $osub['price_key'] ?? $osub['price_api'] ?? 'price' ) ?: 'price';
			$seller_n_api = sanitize_text_field( $osub['seller_name_key'] ?? $osub['seller_name_api'] ?? 'dealer_name' ) ?: 'dealer_name';
			$avail_key    = sanitize_text_field( $osub['availability'] ?? 'InStock' ) ?: 'InStock';
			$cond_raw     = sanitize_text_field( $osub['condition'] ?? 'auto' );
			if ( 'auto' === $cond_raw || empty( $cond_raw ) ) {
				$cond_key = ( 'used-listings' === $post_type ) ? 'UsedCondition' : 'NewCondition';
			} else {
				$cond_key = $cond_raw;
			}
			$offer = array(
				'@type'         => 'Offer',
				'priceCurrency' => $currency,
				'availability'  => 'https://schema.org/' . $avail_key,
				'itemCondition' => 'https://schema.org/' . $cond_key,
				'url'           => esc_url( $url ),
			);
			$price_val = $v[ $price_api ] ?? ( $v['price'] ?? '' );
			if ( ! empty( $price_val ) ) {
				$offer['price'] = (string) $price_val;
			}
			$dealer_name = $v[ $seller_n_api ] ?? ( $v['payload']['dealer name'] ?? ( $v['dealer_name'] ?? '' ) );
			if ( $dealer_name ) {
				$offer['seller'] = array( '@type' => 'AutoDealer', 'name' => wp_strip_all_tags( $dealer_name ) );
			}
			$schema['offers'] = $offer;
		}

		$p_feat = $prop( 'additionalProperty' );
		if ( $p_feat['enabled'] ) {
			$flat = array();
			foreach ( $v['features'] ?? array() as $group ) {
				foreach ( $group['features'] ?? array() as $row ) {
					$flat[] = $row;
				}
			}

			if ( ! empty( $flat ) ) {
				usort( $flat, fn( $a, $b ) => (int) ( $b['ranking'] ?? 0 ) - (int) ( $a['ranking'] ?? 0 ) );

				$overrides = array();
				while ( have_rows( 'feature_list_chromedata', 'options' ) ) {
					the_row();
					$overrides[ (string) get_sub_field( 'id' ) ] = (string) get_sub_field( 'text' );
				}

				$limit      = $use_builder
					? (int) ( $vcfg['features_limit'] ?? 0 )
					: (int) get_field( 'limit_feature_list', 'options' );
				$additional = array();

				foreach ( $flat as $i => $feat ) {
					if ( $limit > 0 && $i >= $limit ) {
						break;
					}
					$id   = (string) ( $feat['id'] ?? '' );
					$text = ! empty( $overrides[ $id ] ) ? $overrides[ $id ] : ( $feat['feature'] ?? '' );
					if ( $text ) {
						$additional[] = array(
							'@type' => 'PropertyValue',
							'name'  => 'Feature',
							'value' => wp_strip_all_tags( $text ),
						);
					}
				}

				if ( ! empty( $additional ) ) {
					$schema['additionalProperty'] = $additional;
				}
			}
		}

		// Extended Specs.
		$ext_simple = array(
			'numberOfDoors'           => 'doors',
			'driveWheelConfiguration' => 'drive_type',
			'vehicleSeatingCapacity'  => 'seating_capacity',
			'knownVehicleDamages'     => 'known_damages',
			'vehicleSpecialUsage'     => 'certified',
			'meetsEmissionStandard'   => 'emission_standard',
			'numberOfForwardGears'    => 'gears',
		);
		foreach ( $ext_simple as $schema_key => $default_api_key ) {
			$p = $prop( $schema_key );
			if ( $p['enabled'] ) {
				$val = self::read_api( $p, $default_api_key, $v );
				if ( $val ) { $schema[ $schema_key ] = $val; }
			}
		}

		$p = $prop( 'fuelConsumption' );
		if ( $p['enabled'] ) {
			$val = self::read_api( $p, 'mpg_city', $v );
			if ( $val ) {
				$schema['fuelConsumption'] = array( '@type' => 'QuantitativeValue', 'value' => $val, 'unitText' => 'mpg' );
			}
		}

		// Custom Properties.
		foreach ( self::get_config()['custom_properties'] ?? array() as $cp ) {
			$key = sanitize_key( $cp['key'] ?? '' );
			$val = wp_strip_all_tags( (string) ( $cp['value'] ?? '' ) );
			if ( $key && $val ) {
				$schema[ $key ] = $val;
			}
		}

		$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );

		return '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n";
	}

	// ── Seller (AutoDealer) NAP helper ───────────────────────────────────────

	/**
	 * Build an AutoDealer seller object with NAP (name/address/phone) data.
	 *
	 * Address/phone come from the per-vehicle ACF fields populated by the
	 * dealer's feed import (dealer_address, dealer_city, dealer_state,
	 * dealer_zip, dealer_phone) — same field group as dealer_name/dealer_url,
	 * not a site-wide options value (there isn't one with real data).
	 *
	 * @param int    $post_id Vehicle post ID.
	 * @param string $name    Dealer name (already resolved by the caller — may be from a custom ACF key in builder mode).
	 * @param mixed  $url     Dealer URL (already resolved by the caller), or empty.
	 * @return array{'@type': string, name: string, url?: string, telephone?: string, address?: array}
	 */
	private static function build_seller_nap( int $post_id, string $name, $url ): array {
		$seller = array(
			'@type' => 'AutoDealer',
			'name'  => $name,
		);

		if ( $url ) {
			$seller['url'] = esc_url( $url );
		}

		$phone = wp_strip_all_tags( (string) get_field( 'dealer_phone', $post_id ) );
		if ( $phone ) {
			$seller['telephone'] = $phone;
		}

		$street = wp_strip_all_tags( (string) get_field( 'dealer_address', $post_id ) );
		$city   = wp_strip_all_tags( (string) get_field( 'dealer_city', $post_id ) );
		$state  = wp_strip_all_tags( (string) get_field( 'dealer_state', $post_id ) );
		$zip    = wp_strip_all_tags( (string) get_field( 'dealer_zip', $post_id ) );

		if ( $street || $city || $state || $zip ) {
			$address = array( '@type' => 'PostalAddress' );
			if ( $street ) {
				$address['streetAddress'] = $street;
			}
			if ( $city ) {
				$address['addressLocality'] = $city;
			}
			if ( $state ) {
				$address['addressRegion'] = $state;
			}
			if ( $zip ) {
				$address['postalCode'] = $zip;
			}
			$seller['address'] = $address;
		}

		return $seller;
	}

	// ── Features helper ───────────────────────────────────────────────────────

	/**
	 * Build additionalProperty array from ACF features_items for a post.
	 *
	 * @param int $post_id
	 * @param int $limit   0 = no limit (reads ACF option when 0 and called from legacy path).
	 * @return array
	 */
	private function build_features_additional_property( int $post_id, int $limit = -1 ): array {
		$flat = array();

		while ( have_rows( 'features_items', $post_id ) ) {
			the_row();
			while ( have_rows( 'features' ) ) {
				the_row();
				$flat[] = array(
					'ranking' => (int) get_sub_field( 'ranking' ),
					'feature' => (string) get_sub_field( 'feature' ),
					'id'      => (string) get_sub_field( 'id' ),
				);
			}
		}

		if ( empty( $flat ) ) {
			return array();
		}

		usort( $flat, fn( $a, $b ) => $b['ranking'] - $a['ranking'] );

		$overrides = array();
		while ( have_rows( 'feature_list_chromedata', 'options' ) ) {
			the_row();
			$overrides[ (string) get_sub_field( 'id' ) ] = (string) get_sub_field( 'text' );
		}

		// -1 sentinel = called from legacy path, read ACF option as before.
		if ( -1 === $limit ) {
			$limit = (int) get_field( 'limit_feature_list', 'options' );
		}

		$additional = array();

		foreach ( $flat as $i => $feat ) {
			if ( $limit > 0 && $i >= $limit ) {
				break;
			}
			$text = ! empty( $overrides[ $feat['id'] ] ) ? $overrides[ $feat['id'] ] : $feat['feature'];
			if ( $text ) {
				$additional[] = array(
					'@type' => 'PropertyValue',
					'name'  => 'Feature',
					'value' => wp_strip_all_tags( $text ),
				);
			}
		}

		return $additional;
	}

	// ── Offer schemas (ACF only, no builder mode yet) ─────────────────────────

	/**
	 * Build Offer JSON-LD for a single offer (lease, finance, or conditional) page.
	 *
	 * @return string
	 */
	private function get_single_offer_json(): string {
		$post_id   = get_the_ID();
		$post_type = get_post_type( $post_id );
		$permalink = get_permalink( $post_id );
		$title     = wp_strip_all_tags( get_the_title( $post_id ) );

		$schema = array(
			'@context'     => 'https://schema.org',
			'@type'        => 'Offer',
			'@id'          => esc_url( $permalink ) . '/#offer',
			'name'         => $title,
			'url'          => esc_url( $permalink ),
			'availability' => 'https://schema.org/InStock',
		);

		switch ( $post_type ) {

			case 'lease-offers':
				$description = wp_strip_all_tags( (string) get_field( 'conditional_description', $post_id ) );
				if ( $description ) {
					$schema['description'] = $description;
				}

				$payment = get_field( 'payment', $post_id );
				if ( $payment ) {
					$schema['price']         = (string) $payment;
					$schema['priceCurrency'] = 'USD';
				}
				$prices = get_field( 'prices', $post_id );
				$price  = 0;
				if ( is_array( $prices ) && ! empty( $prices[0]['price'] ) ) {
					$price = $prices[0]['price'];
				}
				if ( ! empty( $price ) ) {
					$schema['price']         = (string) $price;
					$schema['priceCurrency'] = 'USD';
				}

				$imagelist = get_field( 'imagelistpath', $post_id );
				if ( is_array( $imagelist ) && ! empty( $imagelist[0]['image_url'] ) ) {
					$schema['photo'] = $imagelist[0]['image_url'];
				}

				$end_date = get_field( 'end_date', $post_id );
				if ( $end_date ) {
					$schema['validThrough'] = $end_date;
				}

				$additional = array();

				$term = get_field( 'term', $post_id );
				if ( $term ) {
					$additional[] = array(
						'@type' => 'PropertyValue',
						'name'  => 'Lease Term',
						'value' => $term . ' Months',
					);
				}

				$due_at_signing = get_field( 'due_at_signing', $post_id );
				if ( $due_at_signing ) {
					$additional[] = array(
						'@type' => 'PropertyValue',
						'name'  => 'Due at Signing',
						'value' => '$' . $due_at_signing,
					);
				}

				$yearly_mileage = get_field( 'yearly_excess_mileage', $post_id );
				if ( $yearly_mileage ) {
					$additional[] = array(
						'@type' => 'PropertyValue',
						'name'  => 'Mileage Allowance',
						'value' => number_format( (int) $yearly_mileage ) . ' miles/year',
					);
				}

				if ( ! empty( $additional ) ) {
					$schema['additionalProperty'] = $additional;
				}

				$car = $this->build_car_schema( $post_id );
				if ( ! empty( $car ) ) {
					$schema['itemOffered'] = $car;
				}
				break;

			case 'finance-offers':
				$description = wp_strip_all_tags( (string) get_field( 'apr_description', $post_id ) );
				if ( $description ) {
					$schema['description'] = $description;
				}

				$end_date = get_field( 'end_date', $post_id );
				if ( $end_date ) {
					$schema['validThrough'] = $end_date;
				}

				$additional = array();

				$apr = get_field( 'apr', $post_id );
				if ( $apr ) {
					$additional[] = array(
						'@type' => 'PropertyValue',
						'name'  => 'APR',
						'value' => $apr . '%',
					);
				}

				$term = get_field( 'term', $post_id );
				if ( $term ) {
					$additional[] = array(
						'@type' => 'PropertyValue',
						'name'  => 'Term',
						'value' => $term . ' Months',
					);
				}

				if ( ! empty( $additional ) ) {
					$schema['additionalProperty'] = $additional;
				}

				$car = $this->build_car_schema( $post_id );
				if ( ! empty( $car ) ) {
					$schema['itemOffered'] = $car;
				}
				break;

			case 'conditional-offers':
			case 'offers':
				$description = wp_strip_all_tags( (string) get_field( 'conditional_description', $post_id ) );
				if ( $description ) {
					$schema['description'] = $description;
				}

				$cash = get_field( 'conditional_cash', $post_id );
				if ( $cash ) {
					$schema['price']         = (string) (int) $cash;
					$schema['priceCurrency'] = 'USD';
				}

				$end_date = get_field( 'end_date', $post_id );
				if ( $end_date ) {
					$schema['validThrough'] = $end_date;
				}
				break;
		}

		$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );

		return '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n";
	}

	/**
	 * Build a Car schema fragment from ACF fields on a given post.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	private function build_car_schema( int $post_id ): array {
		$car = array();

		$year  = wp_strip_all_tags( (string) get_field( 'year', $post_id ) );
		$make  = wp_strip_all_tags( (string) get_field( 'make', $post_id ) );
		$model = wp_strip_all_tags( (string) get_field( 'model', $post_id ) );
		$trim  = wp_strip_all_tags( (string) get_field( 'trim', $post_id ) );

		if ( ! $make && ! $model && ! $year ) {
			return array();
		}

		$car['@type'] = 'Car';

		$name_parts = array_filter( array( $year, $make, $model, $trim ) );
		if ( ! empty( $name_parts ) ) {
			$car['name'] = implode( ' ', $name_parts );
		}

		if ( $make ) {
			$car['brand'] = array(
				'@type' => 'Brand',
				'name'  => $make,
			);
		}

		if ( $model ) {
			$car['model'] = $model;
		}

		if ( $year ) {
			$car['vehicleModelDate'] = $year;
		}

		if ( $trim ) {
			$car['vehicleConfiguration'] = $trim;
		}

		$gallery = get_field( 'gallery', $post_id );
		if ( ! empty( $gallery ) && is_array( $gallery ) ) {
			$first_image = reset( $gallery );
			$image_url   = $first_image['image_url'] ?? ( $first_image['image_background'] ?? '' );
			if ( $image_url ) {
				$car['image'] = esc_url( $image_url );
			}
		}

		return $car;
	}

	// ── Archive / SRP ─────────────────────────────────────────────────────────

	/**
	 * Build CollectionPage + ItemList JSON-LD for archive / page-with-post-type.
	 *
	 * @return string
	 */
	private function get_collection_json(): string {
		if ( is_post_type_archive() ) {
			$pt_object = get_queried_object();
			$post_type = $pt_object->name;
			$title     = $pt_object->labels->singular_name;
			$permalink = get_post_type_archive_link( $post_type );
		} else {
			$post_id   = get_the_ID();
			$post_type = get_field( 'post_type', $post_id );
			$title     = get_the_title( $post_id );
			$permalink = get_permalink( $post_id );
		}

		// Builder-mode overrides for CollectionPage name / description.
		$coll_description = '';
		if ( self::is_builder_mode() && in_array( $post_type, array( 'listings', 'used-listings' ), true ) ) {
			$coll_cfg_key = 'used-listings' === $post_type ? 'archive_srp_used_listings' : 'archive_srp_listings';
			$coll_cfg     = self::get_config()[ $coll_cfg_key ] ?? array();
			if ( ! empty( $coll_cfg['name']['enabled'] ) && '' !== ( $coll_cfg['name']['static_value'] ?? '' ) ) {
				$title = $coll_cfg['name']['static_value'];
			}
			if ( ! empty( $coll_cfg['description']['enabled'] ) && '' !== ( $coll_cfg['description']['static_value'] ?? '' ) ) {
				$coll_description = $coll_cfg['description']['static_value'];
			}
		}

		if ( \App\is_api_mode() && in_array( $post_type, array( 'listings', 'used-listings' ), true ) ) {
			return $this->get_collection_json_api( $post_type, (string) $title, (string) $permalink, $coll_description );
		}

		$transient     = get_field( $post_type . '_transient', 'option' );
		$get_transient = get_transient( $transient );

		if ( ! $get_transient ) {
			return '';
		}

		$archive_limit             = self::is_builder_mode()
			? (int) ( self::get_config()['archive_limit'] ?? 24 )
			: 24;
		$get_transient['vehicles'] = array_slice( $get_transient['vehicles'], 0, $archive_limit );
		$is_offer_archive          = in_array( $post_type, $this->offer_types, true );

		if ( $is_offer_archive ) :
			$items    = array();
			$position = 1;
			foreach ( $get_transient['vehicles'] as $item ) {
				$offer_item = array(
					'@type' => 'Offer',
					'name'  => esc_html( $item['title'] ),
					'url'   => esc_url( $item['link'] ),
				);

				if ( ! empty( $item['photo'] ) ) {
					$offer_item['image'] = esc_url( $item['photo'] );
				}
				if ( ! empty( $item['payment'] ) ) {
					$offer_item['price']         = (string) $item['payment'];
					$offer_item['priceCurrency'] = 'USD';
				}
				if ( ! empty( $item['price'] ) ) {
					$offer_item['price']         = (string) $item['price'];
					$offer_item['priceCurrency'] = 'USD';
				}
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => $position,
					'item'     => $offer_item,
				);
				++$position;
			}

			$schema = array_filter( array(
				'@context'    => 'https://schema.org',
				'@type'       => 'CollectionPage',
				'@id'         => esc_url( $permalink ) . '/#collection',
				'name'        => esc_html( $title ),
				'description' => '' !== $coll_description ? esc_html( $coll_description ) : null,
				'url'         => esc_url( $permalink ),
				'mainEntity'  => array(
					'@type'           => 'ItemList',
					'numberOfItems'   => count( $items ),
					'itemListElement' => $items,
				),
			), fn( $v ) => null !== $v );

			$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );

			echo '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n"; // phpcs:ignore
		else :
			$wp_srp_key  = 'used-listings' === $post_type ? 'srp_item_used_listings' : 'srp_item_listings';
			$wp_item_cfg = self::is_builder_mode() ? ( self::get_config()[ $wp_srp_key ] ?? array() ) : array();
			$wp_prop_on  = static function ( string $key ) use ( $wp_item_cfg ): bool {
				if ( empty( $wp_item_cfg ) ) {
					return true;
				}
				$p = $wp_item_cfg[ $key ] ?? array( 'enabled' => true );
				return is_array( $p ) ? ! empty( $p['enabled'] ) : ! empty( $p );
			};
			// Returns static_value from config if set, otherwise $fallback.
			$item_sv = static function ( string $key, $fallback ) use ( $wp_item_cfg ) {
				$p = $wp_item_cfg[ $key ] ?? array();
				$sv = is_array( $p ) ? trim( $p['static_value'] ?? '' ) : '';
				return '' !== $sv ? $sv : $fallback;
			};
			$items    = array();
			$position = 1;
			foreach ( $get_transient['vehicles'] as $item ) {
				$vehicle = array(
					'@type' => 'Vehicle',
					'name'  => esc_html( $item['title'] ),
					'url'   => esc_url( $item['link'] ),
				);

				if ( ! empty( $item['ai_vdp_description'] ) ) {
					$vehicle['description'] = wp_strip_all_tags( trim( $item['ai_vdp_description'] ) );
				}

				if ( $wp_prop_on( 'image' ) && ! empty( $item['photo'] ) ) {
					$vehicle['image'] = esc_url( $item_sv( 'image', $item['photo'] ) );
				}

				if ( $wp_prop_on( 'brand' ) && ! empty( $item['terms']['make'][0] ) ) {
					$vehicle['brand'] = array(
						'@type' => 'Brand',
						'name'  => esc_html( $item_sv( 'brand', $item['terms']['make'][0] ) ),
					);
				}

				if ( $wp_prop_on( 'model' ) && ! empty( $item['terms']['model'][0] ) ) {
					$model = esc_html( $item['terms']['model'][0] );
					if ( ! empty( $item['terms']['trim'][0] ) ) {
						$model .= ' ' . esc_html( $item['terms']['trim'][0] );
					}
					$vehicle['model'] = $item_sv( 'model', $model );
				}

				if ( $wp_prop_on( 'vehicleModelDate' ) && ! empty( $item['year'] ) ) {
					$vehicle['vehicleModelDate'] = esc_html( $item_sv( 'vehicleModelDate', $item['year'] ) );
				}

				if ( $wp_prop_on( 'vehicleIdentificationNumber' ) && ! empty( $item['terms']['vin'][0] ) ) {
					$vehicle['vehicleIdentificationNumber'] = esc_html( $item_sv( 'vehicleIdentificationNumber', $item['terms']['vin'][0] ) );
				}

				if ( $wp_prop_on( 'offers' ) ) {
					$offer = array(
						'@type'         => 'Offer',
						'priceCurrency' => 'USD',
						'availability'  => 'https://schema.org/InStock',
						'itemCondition' => 'https://schema.org/NewCondition',
						'url'           => esc_url( $item['link'] ),
					);

					if ( ! empty( $item['price'] ) || 0 == $item['price'] ) {
						$offer['price'] = (string) $item['price'];
					}

					if ( ! empty( $item['dealer_name'] ) ) {
						$offer['seller'] = array(
							'@type' => 'AutoDealer',
							'name'  => esc_html( $item['dealer_name'] ),
							'url'   => esc_url( $item['link'] ),
						);
					}

					$vehicle['offers'] = $offer;
				}

				$items[] = array(
					'@type'    => 'ListItem',
					'position' => $position,
					'url'      => esc_url( $item['link'] ),
					'item'     => $vehicle,
				);
				++$position;
			}

			$schema = array_filter( array(
				'@context'    => 'https://schema.org',
				'@type'       => 'CollectionPage',
				'@id'         => esc_url( $permalink ) . '/#collection',
				'name'        => esc_html( $title ),
				'description' => '' !== $coll_description ? esc_html( $coll_description ) : null,
				'url'         => esc_url( $permalink ),
				'mainEntity'  => array(
					'@type'           => 'ItemList',
					'numberOfItems'   => count( $items ),
					'itemListElement' => $items,
				),
			), fn( $v ) => null !== $v );

			$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
			echo '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n"; // phpcs:ignore

		endif;

		return '';
	}

	// ── Archive / SRP — API mode ──────────────────────────────────────────────

	/**
	 * Build CollectionPage + ItemList JSON-LD from the Intice Nexus API.
	 * Used on /listings/ and /used-listings/ (and their page equivalents) when
	 * API mode is active — the WP transient path never populates in that mode.
	 *
	 * @param string $post_type 'listings' or 'used-listings'.
	 * @param string $title     Page/archive title for the CollectionPage name.
	 * @param string $permalink Canonical URL for the CollectionPage.
	 * @return string Full <script type="application/ld+json"> tag or empty string.
	 */
	private function get_collection_json_api( string $post_type, string $title, string $permalink, string $description = '' ): string {
		$condition = 'used-listings' === $post_type ? 'used' : 'new';
		$client    = \App\Components\Api\Intice_Api_Client::instance();
		$api_data  = $client->get_vehicles( array( 'condition' => $condition ) );

		if ( is_wp_error( $api_data ) || empty( $api_data['data'] ) ) {
			return '';
		}

		$archive_limit = self::is_builder_mode()
			? (int) ( self::get_config()['archive_limit'] ?? 24 )
			: 24;

		$raw_vehicles = array_slice( $api_data['data'], 0, $archive_limit );
		$use_builder  = self::is_builder_mode();
		$srp_item_key = 'used-listings' === $post_type ? 'srp_item_used_listings' : 'srp_item_listings';
		$item_cfg     = $use_builder ? ( self::get_config()[ $srp_item_key ] ?? array() ) : array();
		$prop_on      = static function ( string $key ) use ( $item_cfg ): bool {
			if ( empty( $item_cfg ) ) {
				return true;
			}
			$p = $item_cfg[ $key ] ?? array( 'enabled' => true );
			return is_array( $p ) ? ! empty( $p['enabled'] ) : ! empty( $p );
		};
		// Returns static_value from item config if set, otherwise $fallback.
		$item_sv = static function ( string $key, $fallback ) use ( $item_cfg ) {
			$p = $item_cfg[ $key ] ?? array();
			$sv = is_array( $p ) ? trim( $p['static_value'] ?? '' ) : '';
			return '' !== $sv ? $sv : $fallback;
		};
		$srp_osub_key = 'used-listings' === $post_type ? 'srp_item_offers_sub_used_listings' : 'srp_item_offers_sub_listings';
		$osub         = $use_builder ? ( self::get_config()[ $srp_osub_key ] ?? array() ) : array();
		$o_currency   = sanitize_text_field( $osub['price_currency'] ?? 'USD' ) ?: 'USD';
		$o_price_api  = sanitize_text_field( $osub['price_api'] ?? 'price' ) ?: 'price';
		$o_avail      = sanitize_text_field( $osub['availability'] ?? 'InStock' ) ?: 'InStock';
		$o_cond_raw   = sanitize_text_field( $osub['condition'] ?? 'auto' );
		$o_cond_key   = ( 'auto' === $o_cond_raw || '' === $o_cond_raw )
			? ( 'used-listings' === $post_type ? 'UsedCondition' : 'NewCondition' )
			: $o_cond_raw;
		$o_show_seller  = ! isset( $osub['show_seller'] ) || ! empty( $osub['show_seller'] );
		$o_seller_api   = sanitize_text_field( $osub['seller_name_api'] ?? 'dealer_name' ) ?: 'dealer_name';
		$items    = array();
		$position = 1;

		foreach ( $raw_vehicles as $v ) {
			$vin  = strtoupper( $v['vin'] ?? '' );
			$url  = \App\Components\Api\Intice_VDP::vdp_url( $vin, $post_type, $v );

			$name_parts = array_filter( array( $v['year'] ?? '', $v['make'] ?? '', $v['model'] ?? '', $v['trim'] ?? '' ) );
			$vehicle    = array(
				'@type' => 'Vehicle',
				'name'  => trim( implode( ' ', $name_parts ) ),
				'url'   => esc_url( $url ),
			);

			if ( $prop_on( 'brand' ) && ! empty( $v['make'] ) ) {
				$vehicle['brand'] = array( '@type' => 'Brand', 'name' => esc_html( $item_sv( 'brand', $v['make'] ) ) );
			}
			if ( $prop_on( 'model' ) && ! empty( $v['model'] ) ) {
				$vehicle['model'] = esc_html( $item_sv( 'model', $v['model'] ) );
			}
			if ( $prop_on( 'vehicleModelDate' ) && ! empty( $v['year'] ) ) {
				$vehicle['vehicleModelDate'] = (string) $item_sv( 'vehicleModelDate', $v['year'] );
			}
			if ( $prop_on( 'vehicleIdentificationNumber' ) && $vin ) {
				$vehicle['vehicleIdentificationNumber'] = $item_sv( 'vehicleIdentificationNumber', $vin );
			}
			$photo = $v['thumb'] ?? $v['image'] ?? '';
			if ( $prop_on( 'image' ) && $photo ) {
				$vehicle['image'] = esc_url( $item_sv( 'image', $photo ) );
			}

			if ( $prop_on( 'offers' ) ) {
				$offer = array(
					'@type'         => 'Offer',
					'priceCurrency' => $o_currency,
					'availability'  => 'https://schema.org/' . $o_avail,
					'itemCondition' => 'https://schema.org/' . $o_cond_key,
					'url'           => esc_url( $url ),
				);
				$price_val = $v[ $o_price_api ] ?? ( $v['price'] ?? '' );
				if ( '' !== (string) $price_val ) {
					$offer['price'] = (string) $price_val;
				}
				if ( $o_show_seller ) {
					$dealer_name = $v[ $o_seller_api ] ?? ( $v['dealer_name'] ?? ( $v['payload']['dealer name'] ?? '' ) );
					if ( $dealer_name ) {
						$offer['seller'] = array( '@type' => 'AutoDealer', 'name' => wp_strip_all_tags( (string) $dealer_name ) );
					}
				}
				$vehicle['offers'] = $offer;
			}

			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $position,
				'url'      => esc_url( $url ),
				'item'     => $vehicle,
			);
			++$position;
		}

		if ( empty( $items ) ) {
			return '';
		}

		$schema = array_filter( array(
			'@context'    => 'https://schema.org',
			'@type'       => 'CollectionPage',
			'@id'         => esc_url( $permalink ) . '/#collection',
			'name'        => esc_html( $title ),
			'description' => '' !== $description ? esc_html( $description ) : null,
			'url'         => esc_url( $permalink ),
			'mainEntity'  => array(
				'@type'           => 'ItemList',
				'numberOfItems'   => count( $items ),
				'itemListElement' => $items,
			),
		), fn( $v ) => null !== $v );

		$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );

		return '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n";
	}
}

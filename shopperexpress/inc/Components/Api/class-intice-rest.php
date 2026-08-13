<?php
/**
 * Intice Nexus REST Proxy
 *
 * Registers WP REST endpoints that proxy the Intice Nexus API and return
 * the same JSON shape the existing SRP JavaScript expects.
 *
 * Endpoints:
 *   GET /wp-json/v1/intice/vehicles/{post_type}
 *   GET /wp-json/v1/intice/meta
 *
 * @package Shopperexpress
 */

namespace App\Components\Api;

use App\Components\SOC\Modules\Api_Settings;
use App\Components\Theme_Component;

/**
 * Class Intice_Rest
 */
class Intice_Rest implements Theme_Component {

	/**
	 * TTL for the assembled SRP response cache (rendered HTML + terms + sort).
	 */
	const CACHE_TTL = 15 * MINUTE_IN_SECONDS;

	/**
	 * Prefix for the named per-post-type/per-sort SRP transients.
	 */
	const CACHE_PREFIX = 'intice_srp_';

	/**
	 * Prefix for the stale-while-revalidate copy of an SRP transient.
	 */
	const CACHE_PREFIX_STALE = 'intice_srp_stale_';

	/**
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'intice_cache_regen', array( $this, 'handle_cache_regen' ) );
		add_action( 'intice_srp_cache_regen', array( $this, 'handle_srp_cache_regen' ), 10, 2 );
		add_action( 'acf/save_post', array( $this, 'maybe_flush_custom_sort_cache' ), 20 );
	}

	/**
	 * Flush the "Custom sort" SRP cache when the Theme Options page is saved.
	 *
	 * The assembled SRP response is cached per post-type/sort-mode
	 * (see cache_key()), but that key doesn't depend on the actual sort
	 * rule contents — without this, changing/enabling `custom_sort_listings`
	 * or `custom_sort_used-listings` in ACF silently has no visible effect
	 * until the 15-minute TTL expires.
	 *
	 * @param int|string $post_id ACF passes 'options' for options-page saves.
	 * @return void
	 */
	public function maybe_flush_custom_sort_cache( $post_id ): void {
		if ( 'options' !== $post_id ) {
			return;
		}

		self::clear_cache( 'listings', true );
		self::clear_cache( 'used-listings', true );
	}

	/**
	 * WP cron callback: regenerate default Intice API cache entries.
	 *
	 * @return void
	 */
	public function handle_cache_regen(): void {
		Intice_Api_Client::instance()->regen_cache();
	}

	/**
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			'v1',
			'/intice/vehicles/(?P<post_type>[a-zA-Z0-9-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_vehicles' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'v1',
			'/intice/meta',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_meta' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	// ─── Handlers ─────────────────────────────────────────────────────────────

	/**
	 * Proxy GET /wp-json/v1/intice/vehicles/{post_type}
	 *
	 * Returns same shape as the existing WP vehicles endpoint so the SRP JS
	 * works without any changes:
	 * { vehicles: [ { html, terms, price, payment, year, title, link, search, photo } ] }
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_vehicles( \WP_REST_Request $request ): \WP_REST_Response {
		$post_type = sanitize_key( $request->get_param( 'post_type' ) );

		if ( ! in_array( $post_type, array( 'listings', 'used-listings' ), true ) ) {
			return new \WP_REST_Response( array( 'error' => 'Post type not supported in API mode.' ), 400 );
		}

		$custom_sort = ! empty( $_REQUEST['sort'] ) && 'custom' === $_REQUEST['sort'];
		$clear       = ! empty( $_REQUEST['clear'] );
		$loged       = ! empty( $_REQUEST['loged'] ) ? $_REQUEST['loged'] : '';
		$cache_key   = self::cache_key( $post_type, $custom_sort );
		$stale_key   = self::stale_cache_key( $post_type, $custom_sort );

		if ( ! $clear && $this->is_cache_enabled() ) {
			$cached = get_transient( $cache_key );

			if ( false !== $cached ) {
				return rest_ensure_response( $cached );
			}

			// Serve stale data immediately while a background cron rebuilds the live cache,
			// same stale-while-revalidate pattern as Intice_Api_Client.
			$stale = get_transient( $stale_key );

			if ( false !== $stale ) {
				$this->schedule_srp_regen( $post_type, $custom_sort );

				return rest_ensure_response( $stale );
			}
		}

		$output = $this->build_srp_payload( $post_type, $custom_sort, $loged );

		if ( is_wp_error( $output ) ) {
			return new \WP_REST_Response(
				array(
					'error'    => $output->get_error_message(),
					'vehicles' => array(),
				),
				502
			);
		}

		if ( $this->is_cache_enabled() ) {
			$this->store_srp_cache( $post_type, $custom_sort, $output );
		}

		return rest_ensure_response( $output );
	}

	/**
	 * WP cron callback: rebuild one SRP cache entry (post_type/sort combo) in the
	 * background after a stale-serving request or an explicit flush.
	 *
	 * @param string $post_type
	 * @param bool   $custom_sort
	 * @return void
	 */
	public function handle_srp_cache_regen( string $post_type, bool $custom_sort ): void {
		$output = $this->build_srp_payload( $post_type, $custom_sort, '' );

		if ( is_wp_error( $output ) ) {
			return;
		}

		$this->store_srp_cache( $post_type, $custom_sort, $output );
	}

	/**
	 * Fetch, sort, and render vehicles for a post type/sort combo into the
	 * SRP JS response shape. Shared by the live request path and the
	 * background regen cron callback.
	 *
	 * @param string $post_type
	 * @param bool   $custom_sort
	 * @param string $loged
	 * @return array|\WP_Error
	 */
	private function build_srp_payload( string $post_type, bool $custom_sort, string $loged ) {
		$client     = Intice_Api_Client::instance();
		$conditions = array(
			'listings'      => 'new',
			'used-listings' => 'used',
		);

		$condition = isset( $conditions[ $post_type ] ) ? $conditions[ $post_type ] : '';

		$api_data = $client->get_vehicles(
			array(
				// 'mode'      => 'full',
				'condition' => $condition,
			)
		);

		if ( is_wp_error( $api_data ) ) {
			return $api_data;
		}

		$raw_vehicles = $api_data['data'] ?? array();
		$raw_vehicles = self::apply_vehicle_filters( $raw_vehicles, $post_type );

		if ( $custom_sort ) {
			$sort_rules = $this->get_custom_sort_rules( $post_type );

			if ( ! empty( $sort_rules ) ) {
				$raw_vehicles = $this->apply_custom_sort( $raw_vehicles, $sort_rules );
			}
		}

		// Build VIN → WP permalink map in one DB query.
		$vin_permalink_map = $this->build_vin_permalink_map( $post_type, $raw_vehicles );

		// Get filter term keys configured for this post type.
		$tax_keys = array_keys( wps_tax( $post_type ) );

		$vehicles = array();

		foreach ( $raw_vehicles as $vehicle ) {
			$vin       = strtoupper( $vehicle['vin'] ?? '' );
			$year      = $vehicle['year'] ?? '';
			$make      = $vehicle['make'] ?? '';
			$model     = $vehicle['model'] ?? '';
			$trim      = $vehicle['trim'] ?? '';
			$permalink = $vin_permalink_map[ $vin ] ?? '#';

			// Build terms array for the JS filter engine.
			$terms = $this->build_terms( $vehicle, $tax_keys );

			// Add vin/stock for autocomplete search.
			if ( $vin ) {
				$terms['vin'] = array( $vin );
			}
			if ( ! empty( $vehicle['stock'] ) ) {
				$terms['stock'] = array( $vehicle['stock'] );
			}

			// Render the card HTML.
			ob_start();
			get_template_part(
				'template-parts/content-listings-api',
				null,
				array(
					'vehicle'   => $vehicle,
					'post_type' => $post_type,
					'permalink' => $permalink,
					'loged'     => $loged,
				)
			);
			$html = ob_get_clean();

			$vehicles[] = array(
				'title'   => trim( "{$year} {$make} {$model}" ),
				'link'    => $permalink,
				'search'  => trim( "{$year} {$make} {$model} {$trim}" ),
				'terms'   => $terms,
				'html'    => $html,
				'price'   => (float) ( $this->get_sort_field_value( $vehicle, 'price' ) ?? 0 ),
				'payment' => (float) ( $this->get_sort_field_value( $vehicle, 'loan_payment_sort' ) ?? 0 ),
				'year'    => (string) $year,
				// Prefer the gallery-resolved list (respects use_images_list,
				// same as the rendered card in 'html' above) over the flat
				// primary-only thumb/image fields.
				'photo'   => $vehicle['images'][0] ?? ( $vehicle['thumb'] ?? ( $vehicle['image'] ?? '' ) ),
			);
		}

		return array(
			'vehicles' => $vehicles,
			'cards'    => array(),
		);
	}

	/**
	 * Write both the live and stale-fallback transients for an SRP payload.
	 *
	 * @param string $post_type
	 * @param bool   $custom_sort
	 * @param array  $output
	 * @return void
	 */
	private function store_srp_cache( string $post_type, bool $custom_sort, array $output ): void {
		$cache_key = self::cache_key( $post_type, $custom_sort );
		$stale_key = self::stale_cache_key( $post_type, $custom_sort );
		$group     = 'srp_' . $post_type . ( $custom_sort ? '_custom' : '' );

		set_transient( $cache_key, $output, self::CACHE_TTL );
		set_transient( $stale_key, $output, Intice_Api_Client::STALE_TTL );
		Intice_Api_Client::track_key( $cache_key, $group, self::CACHE_TTL );
		Intice_Api_Client::track_key( $stale_key, $group, Intice_Api_Client::STALE_TTL );
	}

	/**
	 * Schedule (or reuse an already-pending) background regen for one SRP combo.
	 *
	 * @param string $post_type
	 * @param bool   $custom_sort
	 * @return void
	 */
	private function schedule_srp_regen( string $post_type, bool $custom_sort ): void {
		$args = array( $post_type, $custom_sort );

		if ( ! wp_next_scheduled( 'intice_srp_cache_regen', $args ) ) {
			wp_schedule_single_event( time(), 'intice_srp_cache_regen', $args );
		}

		spawn_cron();
	}

	/**
	 * Proxy GET /wp-json/v1/intice/meta
	 *
	 * Returns Intice filter metadata (makes, models, year/price ranges, conditions).
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_meta( \WP_REST_Request $request ): \WP_REST_Response {
		$client = Intice_Api_Client::instance();
		$meta   = $client->get_meta();

		if ( is_wp_error( $meta ) ) {
			return new \WP_REST_Response( array( 'error' => $meta->get_error_message() ), 502 );
		}

		return rest_ensure_response( $meta );
	}

	// ─── Private helpers ──────────────────────────────────────────────────────

	/**
	 * Build a VIN → permalink map for all vehicles.
	 *
	 * In API mode: returns /post-type/{VIN} URLs directly (no DB query needed,
	 * and works even after WP posts are deleted).
	 *
	 * In WP mode (fallback): looks up WP post permalinks via a single meta query.
	 *
	 * @param string $post_type
	 * @param array  $vehicles
	 * @return array<string,string>
	 */
	private function build_vin_permalink_map( string $post_type, array $vehicles ): array {
		$vins = array_filter( array_column( $vehicles, 'vin' ) );

		if ( empty( $vins ) ) {
			return array();
		}

		// API mode: generate VIN-based URLs directly — no WP post lookup required.
		if ( \App\is_api_mode() ) {
			// Index vehicles by VIN for O(1) lookup.
			$by_vin = array();
			foreach ( $vehicles as $v ) {
				$v_vin = strtoupper( $v['vin'] ?? '' );
				if ( $v_vin ) {
					$by_vin[ $v_vin ] = $v;
				}
			}

			$map = array();
			foreach ( $vins as $vin ) {
				$vin         = strtoupper( $vin );
				$map[ $vin ] = \App\Components\Api\Intice_VDP::vdp_url( $vin, $post_type, $by_vin[ $vin ] ?? array() );
			}
			return $map;
		}

		// WP mode fallback: find existing posts by VIN meta.
		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => 'vin_number',
						'value'   => array_map( 'strtoupper', $vins ),
						'compare' => 'IN',
					),
				),
			)
		);

		$map = array();
		foreach ( $posts as $post_id ) {
			$vin = strtoupper( get_post_meta( $post_id, 'vin_number', true ) );
			if ( $vin ) {
				$map[ $vin ] = get_permalink( $post_id );
			}
		}
		wp_reset_postdata();

		return $map;
	}

	/**
	 * Build a terms array for the JS filter engine from an Intice vehicle array.
	 *
	 * Iterates the wps_tax keys for this post type and maps each to the
	 * corresponding Intice API field via TERM_MAP.
	 *
	 * @param array    $vehicle   Intice vehicle data array.
	 * @param string[] $tax_keys  Filter term keys from wps_tax().
	 * @return array
	 */
	private function build_terms( array $vehicle, array $tax_keys ): array {
		$terms   = array();
		$payload = $vehicle['payload'] ?? array();

		foreach ( $tax_keys as $key ) {
			// "Key Features" is multi-value, not a scalar field — VehicleApiResource
			// groups it by heading: [{heading, features:[{feature, ranking, id}, ...]}].
			// Mirrors the legacy WP-mode flattening in class-api.php (is_array($field)
			// && $key == 'features' → array of $item['feature']) so the SRP filter
			// checkbox list gets the same flat list of feature names either way.
			if ( 'features' === $key && ! empty( $vehicle['features'] ) && is_array( $vehicle['features'] ) ) {
				$flat = array();
				foreach ( $vehicle['features'] as $group ) {
					foreach ( $group['features'] ?? array() as $item ) {
						if ( ! empty( $item['feature'] ) ) {
							$flat[] = (string) $item['feature'];
						}
					}
				}

				if ( ! empty( $flat ) ) {
					$terms[ $key ] = $flat;
				}

				continue;
			}

			// Strip post-type suffixes (e.g. 'make-listings' → 'make').
			$base = preg_replace( '/-(?:listings|used-listings)$/', '', $key );
			// Also try underscore variant (body_style vs body-style).
			$base_underscore = str_replace( '-', '_', $base );

			// Most site-configured filters map to top-level vehicle fields
			// (make, model, drivetrain, ...), but many — engine, dealer_special,
			// or anything a dealer's Import Template mapped as "payload" —
			// only exist inside `payload`. Check both so a filter configured
			// against a payload-only field isn't silently left with no options.
			$value = $vehicle[ $base ]
				?? $vehicle[ $base_underscore ]
				?? $payload[ $base ]
				?? $payload[ $base_underscore ]
				?? null;

			if ( $value !== null && $value !== '' ) {
				$terms[ $key ] = array( (string) $value );
			}
		}

		// Always include condition and certified for filter compatibility.
		if ( ! empty( $vehicle['condition'] ) && ! isset( $terms['condition'] ) ) {
			$terms['condition'] = array( (string) $vehicle['condition'] );
		}

		if ( isset( $vehicle['certified'] ) && $vehicle['certified'] ) {
			$terms['certified'] = array( 'true' );
		}

		return $terms;
	}

	/**
	 * Read the "Custom sort" ACF repeater rules for a post type (Options page).
	 *
	 * Same source used by the WP-mode sort in class-api.php, so the rules
	 * configured in SOC/Theme Options apply identically in API mode.
	 *
	 * @param string $post_type
	 * @return array<int,array{field:string,order:string,numeric:bool}>
	 */
	private function get_custom_sort_rules( string $post_type ): array {
		$sort_rules = array();

		if ( have_rows( 'custom_sort_' . $post_type, 'option' ) ) {
			while ( have_rows( 'custom_sort_' . $post_type, 'option' ) ) {
				the_row();

				$field = get_sub_field( 'field' );
				$order = strtoupper( get_sub_field( 'order' ) ) === 'DESC' ? 'DESC' : 'ASC';

				if ( $field ) {
					$field        = 'price_sort' === $field ? 'price' : $field;
					$sort_rules[] = array(
						'field'   => $field,
						'order'   => $order,
						'numeric' => in_array( $field, array( 'price', 'mileage', 'year', 'dealer_special' ), true ),
					);
				}
			}
		}

		return $sort_rules;
	}

	/**
	 * Resolve a sort field's value from a raw Intice vehicle array.
	 *
	 * Checks the top-level vehicle fields first, then falls back to the
	 * dealer-mapped `payload` bag (engine, loan_payment_sort, dateinstock, etc.
	 * live there rather than at the top level). A top-level value of 0/''
	 * is treated as "not usable" rather than "present" — some dealer import
	 * mappings leave a top-level column dead (e.g. price_sort=0) while the
	 * real value still lives in payload, so an isset-only check (`??`) would
	 * lock in the dead value and never look further.
	 *
	 * @param array  $vehicle
	 * @param string $field
	 * @return mixed
	 */
	private static function get_sort_field_value( array $vehicle, string $field ) {
		$payload = $vehicle['payload'] ?? array();

		switch ( $field ) {
			case 'price':
				return self::first_usable(
					array(
						$vehicle['price_sort'] ?? null,
						$payload['price_sort'] ?? null,
						$vehicle['price'] ?? null,
						$payload['price'] ?? null,
					)
				);
			case 'original_price':
				return self::first_usable(
					array(
						$vehicle['msrp'] ?? null,
						$payload['msrp'] ?? null,
					)
				);
			case 'loan_payment_sort':
				return self::first_usable(
					array(
						$vehicle['payment_sort'] ?? null,
						$payload['loan_payment_sort'] ?? null,
						$payload['loan_payment'] ?? null,
					)
				);
			default:
				return self::first_usable(
					array(
						$vehicle[ $field ] ?? null,
						$payload[ $field ] ?? null,
					)
				);
		}
	}

	/**
	 * Return the first non-empty candidate, or null if all are empty.
	 *
	 * @param array $candidates
	 * @return mixed
	 */
	private static function first_usable( array $candidates ) {
		foreach ( $candidates as $candidate ) {
			if ( ! empty( $candidate ) ) {
				return $candidate;
			}
		}

		return null;
	}

	/**
	 * Apply multi-level custom sort rules to a raw Intice vehicles array.
	 *
	 * Mirrors the usort logic in class-api.php::generate_vehicles_data_new()
	 * so "Custom sort" behaves identically whether vehicles come from WP
	 * posts or the Nexus API.
	 *
	 * @param array $vehicles   Raw vehicle arrays from Intice_Api_Client::get_vehicles().
	 * @param array $sort_rules Rules from get_custom_sort_rules().
	 * @return array
	 */
	private function apply_custom_sort( array $vehicles, array $sort_rules ): array {
		if ( empty( $sort_rules ) || empty( $vehicles ) ) {
			return $vehicles;
		}

		$normalize = function ( $value, $numeric ) {
			if ( $value === null || $value === '' ) {
				return $numeric ? INF : '';
			}

			if ( $numeric ) {
				return (float) preg_replace( '/[^0-9.-]/', '', (string) $value );
			}

			return mb_strtolower( (string) $value );
		};

		usort(
			$vehicles,
			function ( $a, $b ) use ( $sort_rules, $normalize ) {
				foreach ( $sort_rules as $rule ) {
					$field   = $rule['field'];
					$order   = $rule['order'];
					$numeric = $rule['numeric'];

					$val_a = $normalize( $this->get_sort_field_value( $a, $field ), $numeric );
					$val_b = $normalize( $this->get_sort_field_value( $b, $field ), $numeric );

					if ( $val_a == $val_b ) {
						continue;
					}

					if ( 'ASC' === $order ) {
						return $val_a <=> $val_b;
					}

					return $val_b <=> $val_a;
				}

				return 0;
			}
		);

		return $vehicles;
	}

	/**
	 * Exclude vehicles that don't match every configured filter row for this post type
	 * (rows are ANDed together). Rows are configured in SOC → API Settings → Filters.
	 *
	 * Public/static so callers that need the *real* (post-exclusion) vehicle count
	 * outside the SRP request lifecycle — e.g. get_listings_count() for the ACF
	 * model-slider count badge — apply the exact same rules instead of drifting out
	 * of sync with what the results grid actually shows.
	 *
	 * @param array  $vehicles  Raw vehicle arrays from Intice_Api_Client::get_vehicles().
	 * @param string $post_type 'listings' or 'used-listings'.
	 * @return array
	 */
	public static function apply_vehicle_filters( array $vehicles, string $post_type ): array {
		$rows = Api_Settings::get_active_filters( $post_type );

		if ( empty( $rows ) || empty( $vehicles ) ) {
			return $vehicles;
		}

		return array_values(
			array_filter(
				$vehicles,
				function ( $vehicle ) use ( $rows ) {
					foreach ( $rows as $row ) {
						if ( ! self::filter_row_matches( $vehicle, $row ) ) {
							return false;
						}
					}
					return true;
				}
			)
		);
	}

	/**
	 * Evaluate a single filter row against one vehicle.
	 *
	 * Public/static so callers (e.g. get_listings_count() for the ACF model-slider
	 * count badge) can match arbitrary ad-hoc rows (year/make/model/trim equality)
	 * against an already-fetched vehicle list instead of issuing a fresh Nexus
	 * request per slide.
	 *
	 * @param array $vehicle Raw Intice vehicle array.
	 * @param array $row     {field, custom_key, operator, value}.
	 * @return bool
	 */
	public static function filter_row_matches( array $vehicle, array $row ): bool {
		$field = 'custom' === $row['field'] ? $row['custom_key'] : $row['field'];
		$field = 'price_sort' === $field ? 'price' : $field;

		$actual   = self::get_sort_field_value( $vehicle, $field );
		$operator = $row['operator'];
		$expected = $row['value'];

		if ( in_array( $operator, array( '>=', '<=', '>', '<' ), true ) ) {
			$actual_num   = (float) preg_replace( '/[^0-9.-]/', '', (string) $actual );
			$expected_num = (float) preg_replace( '/[^0-9.-]/', '', (string) $expected );

			switch ( $operator ) {
				case '>=':
					return $actual_num >= $expected_num;
				case '<=':
					return $actual_num <= $expected_num;
				case '>':
					return $actual_num > $expected_num;
				case '<':
					return $actual_num < $expected_num;
			}
		}

		if ( 'empty' === $operator || 'not_empty' === $operator ) {
			$is_empty = '' === trim( (string) $actual );
			return 'empty' === $operator ? $is_empty : ! $is_empty;
		}

		if ( in_array( $operator, array( 'len_eq', 'len_gt', 'len_lt' ), true ) ) {
			$actual_len   = mb_strlen( trim( (string) $actual ) );
			$expected_len = (int) preg_replace( '/[^0-9-]/', '', (string) $expected );

			switch ( $operator ) {
				case 'len_eq':
					return $actual_len === $expected_len;
				case 'len_gt':
					return $actual_len > $expected_len;
				case 'len_lt':
					return $actual_len < $expected_len;
			}
		}

		$matches = mb_strtolower( trim( (string) $actual ) ) === mb_strtolower( trim( (string) $expected ) );

		return '!=' === $operator ? ! $matches : $matches;
	}

	/**
	 * Build the named transient key for a post type / sort-mode combination.
	 *
	 * @param string $post_type   'listings' or 'used-listings'.
	 * @param bool   $custom_sort Whether this is the "custom sort" variant.
	 * @return string
	 */
	public static function cache_key( string $post_type, bool $custom_sort ): string {
		return self::CACHE_PREFIX . $post_type . ( $custom_sort ? '_custom' : '_default' );
	}

	/**
	 * Build the stale-while-revalidate transient key for a post type / sort-mode combination.
	 *
	 * @param string $post_type   'listings' or 'used-listings'.
	 * @param bool   $custom_sort Whether this is the "custom sort" variant.
	 * @return string
	 */
	public static function stale_cache_key( string $post_type, bool $custom_sort ): string {
		return self::CACHE_PREFIX_STALE . $post_type . ( $custom_sort ? '_custom' : '_default' );
	}

	/**
	 * Clear the assembled SRP cache for a post type / sort-mode combination, using the
	 * same stale-while-revalidate pattern as Intice_Api_Client::flush_cache(): the current
	 * value is preserved as a 2h stale copy (so the site keeps serving it) while a
	 * background cron rebuilds the live cache.
	 *
	 * @param string $post_type   'listings' or 'used-listings'.
	 * @param bool   $custom_sort Whether to clear the "custom sort" variant.
	 * @return int 1 if a transient was deleted, 0 otherwise.
	 */
	public static function clear_cache( string $post_type, bool $custom_sort ): int {
		$cache_key = self::cache_key( $post_type, $custom_sort );
		$stale_key = self::stale_cache_key( $post_type, $custom_sort );
		$group     = 'srp_' . $post_type . ( $custom_sort ? '_custom' : '' );

		$value = get_transient( $cache_key );

		if ( false !== $value ) {
			set_transient( $stale_key, $value, Intice_Api_Client::STALE_TTL );
			Intice_Api_Client::track_key( $stale_key, $group, Intice_Api_Client::STALE_TTL );
		}

		$deleted = delete_transient( $cache_key ) ? 1 : 0;
		Intice_Api_Client::untrack_key( $cache_key );

		$args = array( $post_type, $custom_sort );

		if ( ! wp_next_scheduled( 'intice_srp_cache_regen', $args ) ) {
			wp_schedule_single_event( time(), 'intice_srp_cache_regen', $args );
		}

		spawn_cron();

		return $deleted;
	}

	/**
	 * Whether the assembled SRP response should be cached.
	 *
	 * Shares the same on/off toggle as Intice_Api_Client's raw API cache.
	 *
	 * @return bool
	 */
	private function is_cache_enabled(): bool {
		return (bool) get_option( Api_Settings::OPTION_CACHE_ENABLED, true );
	}
}

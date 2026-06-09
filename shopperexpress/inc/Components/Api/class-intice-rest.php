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

use App\Components\Theme_Component;

/**
 * Class Intice_Rest
 */
class Intice_Rest implements Theme_Component {

	/**
	 * Maps Intice API field names to WP filter term keys used by the JS engine.
	 * Keys are the ACF/wps_tax field names; values are Intice API vehicle array keys.
	 *
	 * @var array<string,string>
	 */
	private const TERM_MAP = array(
		'make'           => 'make',
		'model'          => 'model',
		'trim'           => 'trim',
		'year'           => 'year',
		'condition'      => 'condition',
		'body_style'     => 'body_style',
		'drivetrain'     => 'drivetrain',
		'fuel_type'      => 'fuel_type',
		'mileage'        => 'mileage',
		'exterior_color' => 'exterior_color',
		'interior_color' => 'interior_color',
	);

	/**
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
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
			return new \WP_REST_Response(
				array(
					'error'    => $api_data->get_error_message(),
					'vehicles' => array(),
				),
				502
			);
		}

		$raw_vehicles = $api_data['data'] ?? array();

		// Build VIN → WP permalink map in one DB query.
		$vin_permalink_map = $this->build_vin_permalink_map( $post_type, $raw_vehicles );

		// Get filter term keys configured for this post type.
		$tax_keys = array_keys( wps_tax( $post_type ) );

		$loged    = ! empty( $_REQUEST['loged'] ) ? $_REQUEST['loged'] : '';
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
				'price'   => (float) ( $vehicle['price_sort'] ?? $vehicle['price'] ?? 0 ),
				'payment' => (float) ( $vehicle['payload']['loan_payment_sort'] ?? 0 ),
				'year'    => (string) $year,
				'photo'   => $vehicle['thumb'] ?? $vehicle['image'] ?? '',
			);
		}

		return rest_ensure_response(
			array(
				'vehicles' => $vehicles,
				'cards'    => array(),
			)
		);
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
		$terms = array();

		foreach ( $tax_keys as $key ) {
			// Strip post-type suffixes (e.g. 'make-listings' → 'make').
			$base = preg_replace( '/-(?:listings|used-listings)$/', '', $key );
			// Also try underscore variant (body_style vs body-style).
			$base_underscore = str_replace( '-', '_', $base );

			$value = $vehicle[ $base ] ?? $vehicle[ $base_underscore ] ?? null;

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
}

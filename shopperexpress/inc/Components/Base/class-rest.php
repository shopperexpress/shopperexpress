<?php
/**
 * WordPress Rest API.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class Rest
 *
 * @package App\Base\Component
 */
class Rest implements Theme_Component {


	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action(
			'rest_api_init',
			function () {
				$methods = get_class_methods( $this );

				foreach ( $methods as $method ) {
					if ( strpos( $method, 'rest_' ) === 0 ) {
						$route = str_replace( '_', '-', substr( $method, 5 ) );
						register_rest_route(
							'favorite/v1',
							$route,
							array(
								'methods'             => array( 'GET', 'POST' ),
								'callback'            => array( $this, $method ),
								'permission_callback' => '__return_true',
							)
						);
					}
				}
			}
		);
	}

	public function rest_render( \WP_REST_Request $request ) {
		$posts     = $request->get_param( 'post_id' );
		$post_type = sanitize_key( $request->get_param( 'action' ) );

		if ( ! $posts || ! $post_type ) {
			return new \WP_REST_Response(
				array( 'error' => 'Missing required parameters' ),
				400
			);
		}
		$posts = explode( ',', $posts );
		$posts = is_array( $posts ) ? $posts : array( $posts );

		$allowed = array(
			'lease-offers',
			'finance-offers',
			'conditional-offers',
			'used-listings',
			'listings',
			'offers',
		);

		if ( in_array( $post_type, $allowed, true ) ) {
			$api_mode = get_option( 'shopperexpress_api_mode_enabled' )
				&& class_exists( '\App\Components\Api\Intice_Api_Client' )
				&& in_array( $post_type, array( 'listings', 'used-listings' ), true );

			ob_start();

			if ( $api_mode ) {
				// Build VIN lookup from the same get_vehicles() call SRP uses — same data structure.
				$client     = \App\Components\Api\Intice_Api_Client::instance();
				$conditions = array(
					'listings'      => 'new',
					'used-listings' => 'used',
				);
				$api_data = $client->get_vehicles( array( 'condition' => $conditions[ $post_type ] ) );
				$all_vehicles = ( ! is_wp_error( $api_data ) ) ? ( $api_data['data'] ?? array() ) : array();

				// Index by VIN for fast lookup.
				$by_vin = array();
				foreach ( $all_vehicles as $v ) {
					$v_vin = strtoupper( $v['vin'] ?? '' );
					if ( $v_vin ) {
						$by_vin[ $v_vin ] = $v;
					}
				}

				foreach ( $posts as $post_id ) {
					$vin     = strtoupper( trim( $post_id ) );
					$vehicle = $by_vin[ $vin ] ?? null;
					if ( ! $vehicle ) {
						continue;
					}
					$permalink = home_url( '/' . $post_type . '/' . $vin . '/' );
					get_template_part(
						'template-parts/content-listings-api',
						null,
						array(
							'vehicle'   => $vehicle,
							'post_type' => $post_type,
							'permalink' => $permalink,
							'loged'     => is_user_logged_in() ? 'true' : '',
						)
					);
				}
			} else {
				foreach ( $posts as $post_id ) {
					$post_id = trim( $post_id );
					if ( $post_type === get_post_type( $post_id ) ) {
						get_template_part(
							'template-parts/content',
							$post_type,
							array(
								'post_id' => $post_id,
								'action'  => 'favorite',
							)
						);
					}
				}
			}

			$html = ob_get_clean();

			return new \WP_REST_Response(
				array(
					'success' => true,
					'html'    => $html,
				),
				200
			);
		}

		return new \WP_REST_Response(
			array( 'error' => 'Invalid post type or not allowed' ),
			400
		);
	}

	public function rest_favorite_count( \WP_REST_Request $request ) {
		$user_id = intval( $request->get_param( 'user' ) ) ?? 0;

		$post_types = array(
			'listings',
			'used-listings',
			'conditional-offers',
			'lease-offers',
			'finance-offers',
			'offers',
		);

		$output = array();
		foreach ( $post_types as $post_type ) {
			$output[ $post_type ] = get_user_favorites_count(
				$user_id,
				null,
				array( 'post_type' => array( $post_type ) )
			);
		}

		// Merge API VIN favorites (stored separately from SimpleFavorites plugin).
		if ( get_option( 'shopperexpress_api_mode_enabled' ) ) {
			$api_favorites = array();
			if ( is_user_logged_in() ) {
				$meta          = get_user_meta( get_current_user_id(), 'wps_api_favorites', true );
				$api_favorites = is_array( $meta ) ? $meta : array();
			} else {
				$raw           = isset( $_COOKIE['wps_api_favorites'] )
					? json_decode( stripslashes( $_COOKIE['wps_api_favorites'] ), true )
					: null;
				$api_favorites = is_array( $raw ) ? $raw : array();
			}

			foreach ( array( 'listings', 'used-listings' ) as $pt ) {
				$api_count      = count( $api_favorites[ $pt ] ?? array() );
				$output[ $pt ] += $api_count;
			}
		}

		return new \WP_REST_Response( $output, 200 );
	}
}

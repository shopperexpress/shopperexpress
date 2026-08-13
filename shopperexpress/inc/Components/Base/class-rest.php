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

		// Cache the REST index/discovery document (global `/` and namespace
		// roots like `/wp/v2`) for anonymous requests. Building it walks every
		// registered route across every active plugin — identical output for
		// every guest, and the actual cost behind the reported 9-11s hits on
		// /wp-json/wp/v2/. Short-circuiting via rest_pre_dispatch skips that
		// walk entirely on a cache hit, instead of just filtering the result.
		add_filter( 'rest_pre_dispatch', array( $this, 'maybe_serve_cached_rest_index' ), 10, 3 );
		add_filter( 'rest_post_dispatch', array( $this, 'maybe_cache_rest_index_response' ), 10, 3 );
	}

	/**
	 * Short-circuit REST dispatch with a cached index document, if we have one.
	 *
	 * @param mixed            $result  Pre-computed result (null unless another filter already handled it).
	 * @param \WP_REST_Server  $server  Server instance.
	 * @param \WP_REST_Request $request Current request.
	 * @return mixed
	 */
	public function maybe_serve_cached_rest_index( $result, $server, $request ) {
		if ( ! empty( $result ) || 'GET' !== $request->get_method() || is_user_logged_in() ) {
			return $result;
		}

		$cached = get_transient( $this->index_cache_key( $request->get_route() ) );

		return false !== $cached ? new \WP_REST_Response( $cached, 200 ) : $result;
	}

	/**
	 * Cache the response if it's an index/discovery document.
	 *
	 * Detected by shape (a top-level `routes` map) rather than by guessing which
	 * paths WP treats as an index — that's the one thing every index response
	 * (global or per-namespace) has that no real resource response does.
	 *
	 * @param \WP_REST_Response $response Response about to be served.
	 * @param \WP_REST_Server   $server   Server instance.
	 * @param \WP_REST_Request  $request  Current request.
	 * @return \WP_REST_Response
	 */
	public function maybe_cache_rest_index_response( $response, $server, $request ) {
		if ( 'GET' !== $request->get_method() || is_user_logged_in() || ! $response instanceof \WP_REST_Response || $response->is_error() ) {
			return $response;
		}

		$data = $response->get_data();
		if ( is_array( $data ) && isset( $data['routes'] ) ) {
			set_transient( $this->index_cache_key( $request->get_route() ), $data, 15 * MINUTE_IN_SECONDS );
		}

		return $response;
	}

	/**
	 * Transient key for a cached REST index document, scoped per route
	 * (global `/` vs. `/wp/v2` etc. are cached separately).
	 *
	 * @param string $route REST route, e.g. '/' or '/wp/v2'.
	 * @return string
	 */
	private function index_cache_key( string $route ): string {
		return 'sf_rest_idx_' . md5( $route );
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

		// Mirrors UserRepository::getFavorites() — a truthy $user_id is treated
		// as a logged-in-style lookup by the Favorites plugin even when the
		// current visitor isn't authenticated, so it must not take the guest
		// shortcut below.
		$logged_in_style = is_user_logged_in() || $user_id > 0;

		if ( ! $logged_in_style && ! $this->cookie_has_favorites( 'simplefavorites' ) ) {
			// Guests who have never favorited anything carry no `simplefavorites`
			// cookie at all — it's only ever set by SyncSingleFavorite::setCookie()
			// on the first add. That's most anonymous/bot traffic, so skip the six
			// get_user_favorites_count() calls (the actual 9-11s cost) entirely.
			$output = array_fill_keys( $post_types, 0 );
		} else {
			$cache_key = $this->favorite_count_cache_key( $user_id, $logged_in_style );
			$output    = get_transient( $cache_key );

			if ( false === $output ) {
				$output = array();
				foreach ( $post_types as $post_type ) {
					$output[ $post_type ] = get_user_favorites_count(
						$user_id,
						null,
						array( 'post_type' => array( $post_type ) )
					);
				}
				set_transient( $cache_key, $output, 45 );
			}
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

	/**
	 * Whether the given Favorites-plugin cookie actually lists any favorited posts.
	 *
	 * @param string $name Cookie name (e.g. 'simplefavorites').
	 * @return bool
	 */
	private function cookie_has_favorites( string $name ): bool {
		if ( empty( $_COOKIE[ $name ] ) ) {
			return false;
		}

		$decoded = json_decode( stripslashes( $_COOKIE[ $name ] ), true );
		if ( ! is_array( $decoded ) ) {
			return false;
		}

		// Cookie shape mirrors UserRepository::favoritesWithSiteID() — an array
		// of per-site entries, each with a 'posts' list (or a flat legacy list).
		foreach ( $decoded as $entry ) {
			if ( is_array( $entry ) && ! empty( $entry['posts'] ) ) {
				return true;
			}
			if ( ! is_array( $entry ) && $entry ) {
				return true; // Flat legacy format: array of post IDs directly.
			}
		}

		return false;
	}

	/**
	 * Build the transient key for a cached favorite-count response.
	 *
	 * Logged-in-style lookups (real login, or a truthy user param — see
	 * rest_favorite_count()) are keyed by that user ID. Guests are keyed by
	 * the content of their own favorites cookie so two different guests
	 * (both $user_id === 0) never share a cached result.
	 *
	 * @param int  $user_id         Requested user ID (may be 0 for guests).
	 * @param bool $logged_in_style Whether this is a logged-in-style lookup.
	 * @return string
	 */
	private function favorite_count_cache_key( int $user_id, bool $logged_in_style ): string {
		if ( $logged_in_style ) {
			$id = $user_id ?: get_current_user_id();
			return 'sf_fav_count_u' . $id;
		}

		$cookie = isset( $_COOKIE['simplefavorites'] ) ? (string) $_COOKIE['simplefavorites'] : '';
		return 'sf_fav_count_g' . md5( $cookie );
	}
}

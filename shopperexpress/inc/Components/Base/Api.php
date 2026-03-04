<?php

/**
 * WordPress API Component.
 *
 * This component registers and handles custom REST API endpoints for the WordPress theme.
 * It includes methods for registering endpoints, handling API requests, and managing cached responses.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;
use WP_Query;

/**
 * Class Api
 *
 * This class is responsible for setting up custom REST API endpoints and handling requests for vehicle data.
 * It implements the Theme_Component interface and provides functionality to register API routes,
 * fetch and cache data, and manage API responses.
 *
 * @package App\Components\Base
 */
class Api implements Theme_Component {


	/**
	 * Register hooks for custom REST API endpoints.
	 *
	 * This method adds a hook to the `rest_api_init` action to register custom REST API endpoints
	 * when the REST API is initialized.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'custom_rest_api_endpoint' ) );
	}

	/**
	 * Remove existing cache.
	 *
	 * @return void
	 */ private function clear_cache( string $post_type ): void {
		global $wpdb;

		$now = time();

		if ( ! empty( $_REQUEST['clear'] ) ) {
			$like     = '_transient_timeout_v1/vehicles/' . $post_type . '%';
			$timeouts = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT option_name FROM $wpdb->options
                 WHERE option_name LIKE %s AND option_value < %d",
					$like,
					$now
				)
			);

			foreach ( $timeouts as $timeout ) {
				$transient_name = str_replace( '_transient_timeout_', '_transient_', $timeout->option_name );
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM $wpdb->options WHERE option_name = %s OR option_name = %s",
						$transient_name,
						$timeout->option_name
					)
				);
			}
		}

		if ( ! empty( $_REQUEST['clear-relevent'] ) ) {
			$like     = '_transient_timeout_v1/relevent_vehicles_' . $post_type . '%';
			$timeouts = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT option_name FROM $wpdb->options
                 WHERE option_name LIKE %s AND option_value < %d",
					$like,
					$now
				)
			);

			foreach ( $timeouts as $timeout ) {
				$transient_name = str_replace( '_transient_timeout_', '_transient_', $timeout->option_name );
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM $wpdb->options WHERE option_name = %s OR option_name = %s",
						$transient_name,
						$timeout->option_name
					)
				);
			}
		}
}


	/**
	 * Register custom REST API endpoints for vehicles data.
	 *
	 * This function registers two REST API endpoints:
	 * 1. `/v1/vehicles/(?P<post_type>[a-zA-Z0-9-]+)` - Fetches vehicles data based on post type.
	 * 2. `/v1/vehicles/` - Fetches data for all vehicles.
	 *
	 * @return void
	 */
public function custom_rest_api_endpoint(): void {
	register_rest_route(
		'v1',
		'/vehicles/(?P<post_type>[a-zA-Z0-9-]+)',
		array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_vehicles_data' ),
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'v1',
		'/vehicles/',
		array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_vehicles_all' ),
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'v1',
		'/search/',
		array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_search' ),
			'permission_callback' => '__return_true',
		)
	);
}

public function search_object( $object, $searchTerm, $post_type ) {
	$results           = array();
	$aggregatedResults = array();

	if ( empty( $searchTerm ) ) {
		return $results;
	}

	$postType  = $post_type;
	$post_type = '-' . $post_type;

	$searchTerms = explode( ' ', $searchTerm );
	foreach ( $searchTerms as $searchTerm ) {
		foreach ( $object->data['vehicles'] as $vehicle ) {
			foreach ( $vehicle['terms'] as $key => $values ) {
				if ( is_array( $values ) ) {
					foreach ( $values as $value ) {
						if ( stripos( $value, $searchTerm ) !== false ) {
							$i    = ! empty( $i ) ? $i : 1;
							$make = ! empty( $vehicle['terms'][ 'make' . $post_type ] ) ? $vehicle['terms'][ 'make' . $post_type ][0] : '';
							$make = $value == $make || str_contains( $make, $value ) ? '' : $make;

							if ( $i == 1 ) {
								$title = $value;
							} else {
								if ( $key != 'model' . $post_type ) {
									$model = $vehicle['terms'][ 'model' . $post_type ][0];
								}
								$trim    = $vehicle['terms'][ 'trim' . $post_type ][0];
								$title   = array();
								$title[] = $value;
								if ( ! empty( $model ) ) {
									$title[] = $model;
								}
								if ( ! empty( $trim ) ) {
									$title[] = $trim;
								}
								if ( ! empty( $make ) ) {
									$title[] = $make;
								}
								$title = implode( ' ', $title );
							}

							$suggestion = array(
								'suggestion' => $title,
								'link'       => home_url( $postType ),
								'facetInfo'  => array(),
							);
							if ( $i != 1 ) {
								if ( ! empty( $make ) ) {
									$suggestion['facetInfo'][ 'make' . $post_type ] = $make;
								}
								if ( ! empty( $model ) ) {
									$suggestion['facetInfo'][ 'model' . $post_type ] = $model;
								}
								if ( ! empty( $trim ) ) {
									$suggestion['facetInfo'][ 'trim' . $post_type ] = $trim;
								}
							}
							if ( ! empty( $value ) ) {
								$suggestion['facetInfo'][ $key ] = $value;
							}

							if ( in_array( $key, array( 'vin', 'stock' ) ) ) {
								$suggestion           = array(
									'suggestion' => $value,
									'link'       => home_url( $postType ),
									'facetInfo'  => array( $key => $value ),
								);
								$aggregatedResults[0] = $suggestion;
							} else {
								$uniqueKey = md5( json_encode( $suggestion ) );

								if ( isset( $aggregatedResults[ $uniqueKey ] ) ) {
								} else {
									$aggregatedResults[ $uniqueKey ] = $suggestion;
								}
							}

							++$i;
						}
					}
				}
			}
		}
	}

	$results = array_values( $aggregatedResults );

	return $results;
}


public function get_search() {
	$output = array(
		'suggestions' => array(),
		'vehicles'    => array(),
	);

	$post_type  = ! empty( $_REQUEST['post_type'] ) ? $_REQUEST['post_type'] : 'listings';
	$searchTerm = ! empty( $_REQUEST['search'] ) ? $_REQUEST['search'] : '';

	if ( is_array( $post_type ) ) {
		$matches = array();
		foreach ( $post_type as $type ) {
			$get_vehicles_data = $this->get_vehicles_data( array( 'post_type' => $type ) );
			$search            = $this->search_object( $get_vehicles_data, $searchTerm, $type );

			if ( $type == 'listings' ) {
				$output['isNewResults'] = ! empty( $search ) ? true : false;
			} elseif ( $type == 'used-listings' ) {
				$output['isUsedResults'] = ! empty( $search ) ? true : false;
			}

			$matches = array_merge( $matches, $search );
		}
	} else {
		$get_vehicles_data = $this->get_vehicles_data( array( 'post_type' => $post_type ) );
		$matches           = $this->search_object( $get_vehicles_data, $searchTerm, $post_type );
	}

	if ( count( $matches ) > 0 ) {
		foreach ( $matches as $match ) {
			$output['suggestions'][] = array(
				'suggestion' => $match['suggestion'],
				'facetInfo'  => $match['facetInfo'],
				'link'       => $match['link'],
			);
		}
	} else {
		$output['suggestions'] = array();
	}

	return rest_ensure_response( $output );
}


	/**
	 * Get vehicles data for a specific post type.
	 *
	 * @param WP_REST_Request $request The REST API request object.
	 * @return WP_REST_Response The vehicles data as a REST API response.
	 */
public function get_vehicles_data( $post_type, $clear = '' ) {
	$clear     = ! empty( $_REQUEST['clear'] ) || ! empty( $clear ) ? true : false;
	$post_type = $post_type['post_type'];

	$sort      = ! empty( $_REQUEST['sort'] ) ? '-' . sanitize_text_field( $_REQUEST['sort'] ) : '';
	$similar   = ! empty( $_REQUEST['similar'] ) && $_REQUEST['similar'] == 'true' ? '-similar' : '';
	$cache_key = 'v1/vehicles/' . $post_type . $sort . $similar . date( 'Y-m-d-H-i-s' );
	$output    = '';
	$transient = $sort ? get_field( $post_type . '_transient_custom', 'option' ) : get_field( $post_type . '_transient', 'option' );

	if ( $clear ) {

		$output = $this->generate_transient( $post_type, $cache_key );
		$this->clear_cache( $post_type );

		$to      = 'recache@intice.com';
		$subject = get_bloginfo( 'name' ) . ' Notification';
		$message = sprintf(
			"The cache has been cleared for %s \n\nWebsite: %s\nPost Type: %s\nTime: %s",
			get_bloginfo( 'name' ),
			get_site_url(),
			$post_type,
			current_time( 'Y-m-d H:i:s' )
		);
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		if ( ! wp_mail( $to, $subject, $message, $headers ) ) {
			error_log( 'Failed to send cache clear notification email.' );
		}
	} else {

		$cached_response = get_field( 'cache_api', 'options' ) ? get_transient( $transient ) : false;

		if ( false === $cached_response ) {
			$output = $this->generate_transient( $post_type, $cache_key );
		} else {
			$output = $cached_response;
		}
	}

	return rest_ensure_response( $output );
}

	/**
	 * Generate a transient cache for vehicles data.
	 *
	 * @param string $post_type The post type for which to generate data.
	 * @param string $cache_key The key used for caching.
	 */
public function generate_transient( string $post_type = '', string $cache_key = '' ) {

	$sort = ! empty( $_REQUEST['sort'] ) ? true : false;
	if ( $post_type == 'listings' || $post_type == 'used-listings' ) {
		if ( get_field( 'use_new_databse_table', 'option' ) ) {
			$output['vehicles'] = ( isset( $_REQUEST['similar'] ) && $_REQUEST['similar'] == 'true' ) || ( isset( $_REQUEST['sort'] ) && $_REQUEST['sort'] == 'unique' ) ? $this->generate_vehicles_data_new( $post_type, true ) : $this->generate_vehicles_data_new( $post_type );
		} else {
			$output['vehicles'] = ( isset( $_REQUEST['similar'] ) && $_REQUEST['similar'] == 'true' ) || ( isset( $_REQUEST['sort'] ) && $_REQUEST['sort'] == 'unique' ) ? $this->generate_vehicles_data( $post_type, true ) : $this->generate_vehicles_data( $post_type );
		}
	} else {
		$output['vehicles'] = $this->generate_vehicles_data( $post_type );
	}

	$time = 24 * HOUR_IN_SECONDS;
	set_transient( $cache_key, $output, $time );
	if ( $sort ) {
		update_field( $post_type . '_transient_custom', $cache_key, 'option' );
	} else {
		update_field( $post_type . '_transient', $cache_key, 'option' );
	}
	update_field( $post_type . '_transient', $cache_key, 'option' );

	return $output;
}

public function get_meta_fields( $post_id ) {
	$meta_fields = get_field( 'similar_vehicles_from', 'options' );
	$meta_fields = ! is_array( $meta_fields ) ? array( $meta_fields ) : $meta_fields;
	$meta_args   = array();
	foreach ( $meta_fields as $field ) {
		if ( $item = get_field( $field, $post_id ) ) {
			$meta_args[] = array(
				'key'   => $field,
				'value' => $item,
			);
		}
	}

	return $meta_args;
}

public function exclude_vehicles( $post_type, $post_id, $similar_vehicles = false ) {
	if ( ! $similar_vehicles ) {
		return;
	}
	$args = array(
		'post_type'      => $post_type,
		'posts_per_page' => -1,
		'fields'         => 'ids',
	);

	$meta_args = $this->get_meta_fields( $post_id );

	if ( ! empty( $meta_args ) ) {
		$args['meta_query'] = array_merge( array( 'relation' => 'AND' ), $meta_args );
	}

	$query = new WP_Query( $args );

	return $query->posts;
}

private function generate_vehicles_data_new( string $post_type, $similar_vehicles = false ): array {

	$sort = ! empty( $_REQUEST['sort'] ) && $_REQUEST['sort'] === 'custom' ? true : false;

	// Set up query arguments based on post type
	$args = array(
		'post_type'      => $post_type,
		'posts_per_page' => -1,
		'fields'         => 'ids',
	);

	if ( $sort && in_array( $post_type, array( 'listings', 'used-listings' ), true ) ) {
		$meta_query = array( 'relation' => 'AND' );
		$orderby    = array();

		if ( have_rows( 'custom_sort_' . $post_type, 'option' ) ) {
			while ( have_rows( 'custom_sort_' . $post_type, 'option' ) ) {
				the_row();

				$field = get_sub_field( 'field' );
				$order = strtoupper( get_sub_field( 'order' ) ) === 'DESC' ? 'DESC' : 'ASC';

				if ( $field ) {
					$clause = $field . '_clause';

					$meta_query[ $clause ] = array(
						'key'     => $field,
						'compare' => 'EXISTS',
					);

					if ( in_array( $field, array( 'price', 'mileage', 'year', 'dealer_special' ), true ) ) {

						$meta_query[ $clause ]['type'] = 'NUMERIC';
					}

					$orderby[ $clause ] = $order;
				}
			}
		}

		if ( count( $meta_query ) > 1 ) {
			$args['meta_query'] = $meta_query;
		}
		if ( ! empty( $orderby ) ) {
			$args['orderby'] = $orderby;
		}
	}

	// Add meta query arguments for specific post types
	if ( in_array( $post_type, array( 'listings', 'used-listings' ), true ) && ! $sort ) {
		$args['meta_key'] = 'price';
		$args['orderby']  = 'meta_value_num';
		$args['order']    = 'ASC';
	}

	// Execute the query
	$query    = get_posts( $args );
	$exclude  = array();
	$vehicles = array();
	global $wpdb;
	$get_wps_tax = wps_tax( $post_type );

	foreach ( $query as $post_id ) {
		$row              = $wpdb->get_var( $wpdb->prepare( "SELECT data FROM `{$post_type}` WHERE post_id = %d", $post_id ) );
		$get_field        = json_decode( $row, true );
		$exclude_vehicles = '';

		if ( ! in_array( $post_id, $exclude ) ) {
			$exclude_vehicles = array();

			if ( $post_type == 'listings' && $similar_vehicles ) {
				$exclude_vehicles = $this->exclude_vehicles( $post_type, $post_id, true );
				if ( ! empty( $exclude_vehicles ) ) {
					$exclude = array_merge( $exclude, $exclude_vehicles );
					$exclude = array_unique( $exclude );
				}
			}

			$terms = array();

			// Get taxonomies and associated terms
			foreach ( $get_wps_tax as $index => $value ) {
				$key   = $index;
				$field = ! empty( $get_field[ $index ] ) ? $get_field[ $index ] : '';

				if ( ! empty( $field ) ) {
					if ( is_array( $field ) && $key == 'features' ) {
						$new_field = array();
						foreach ( $field as $item ) {
							$new_field[] = $item['feature'];
						}
						$terms[ $key ] = $new_field;
					} elseif ( $index == 'trims' ) {
						$new_field = array();
						foreach ( $field as $item ) {
							$new_field[] = $item['trim'];
						}
						$terms[ $key ] = $new_field;
					} else {
						$terms[ $key ] = array( $field );
					}
				} elseif ( $index == 'hide-duplicates' ) {
					$terms[ $key ] = array( 'enabled' );
				}
			}

			// Handle additional fields for specific post types
			if ( in_array( $post_type, array( 'listings', 'used-listings' ), true ) ) {
				$terms['certified'] = ! empty( $get_field['certified'] ) ? array( $get_field['certified'] ) : get_field( 'certified', $post_id );
				$dealer_special     = ! empty( $get_field['dealer_special'] ) ? $get_field['dealer_special'] : '';
				if ( $dealer_special ) {
					$terms['dealer-special'] = esc_html( $dealer_special );
				}

				$features = array();
				$images   = array();
				if ( have_rows( 'features_items', $post_id ) ) {
					// Get features and sort them by ranking
					while ( have_rows( 'features_items', $post_id ) ) :
						the_row();
						while ( have_rows( 'features' ) ) :
							the_row();
							$features[] = array(
								'ranking' => (int) get_sub_field( 'ranking' ),
								'feature' => esc_html( get_sub_field( 'feature' ) ),
								'id'      => get_sub_field( 'id' ),
							);
							endwhile;
						endwhile;

					usort(
						$features,
						function ( $a, $b ) {
							return $b['ranking'] - $a['ranking'];
						}
					);

					// Get feature descriptions from global options
					while ( have_rows( 'feature_list_chromedata', 'options' ) ) :
						the_row();
						$images[ get_sub_field( 'id' ) ] = array(
							'text' => esc_html( get_sub_field( 'text' ) ),
						);
						endwhile;

					// Limit features by the defined limit in options
					$limit = (int) get_field( 'limit_feature_list', 'options' );
					foreach ( $features as $index => $feature ) {
						if ( $index < $limit ) {
							$id                      = $feature['id'];
							$text                    = $images[ $id ]['text'] ?? $feature['feature'];
							$terms['feature_list'][] = $text;
						}
					}
				}
			}

			if ( $vin_number = $get_field['vin_number'] ) {
				$terms['vin'] = array( $vin_number );
			}
			if ( $stock_number = $get_field['stock_number'] ) {
				$terms['stock'] = array( $stock_number );
			}
			$loged = ! empty( $_REQUEST['loged'] ) ? $_REQUEST['loged'] : '';

			// Generate HTML content
			ob_start();
			$args = array(
				'post_id'   => $post_id,
				'post_type' => $post_type,
				'loged'     => $loged,
			);
			if ( $exclude_vehicles ) {
				$args['similar'] = count( $exclude_vehicles );
			}
			$query_data = array();

			foreach ( $this->get_meta_fields( $post_id ) as $field ) {
				if ( isset( $field['key'], $field['value'] ) ) {
					$field                = $field['key'];
					$query_data[ $field ] = strtolower( $get_field[ $field ] );
				}
			}

			$query_params        = http_build_query( $query_data, '', '&', PHP_QUERY_RFC3986 );
			$args['similar_url'] = home_url( $post_type ) . '?' . $query_params;

			get_template_part( 'template-parts/content', $post_type, $args );
			$html = ob_get_clean();

			// Set up vehicle data array
			$vehicle_data = array(
				'title'  => esc_html( get_the_title( $post_id ) ),
				'link'   => esc_url( get_the_permalink( $post_id ) ),
				'search' => $post_type === 'service-offers' ? wp_kses_post( get_the_content( $post_id ) ) : esc_html( get_the_title( $post_id ) ),
				'terms'  => $terms,
				'html'   => $html,
			);
			if ( $post_type == 'offers' ) {
				$vehicle_data['payment'] = get_field( 'lease_payment', $post_id );
				$vehicle_data['year']    = get_the_date( 'Ymdhms', $post_id );

			}
			if ( $post_type == 'offers' || $post_type == 'service-offers' ) {
				$vehicle_data['priority'] = get_field( 'priority', $post_id );
			}

			// Add additional fields for specific post types

			switch ( $post_type ) {
				case 'listings':
				case 'used-listings':
					$payment               = ! empty( $get_field['loan_payment_sort'] ) ? $get_field['loan_payment_sort'] : '';
					$payment               = ! empty( $payment ) ? $payment : get_field( 'loan_payment_sort', $post_id );
					$vehicle_data['price'] = ! empty( $get_field['price_sort'] ) ? $get_field['price_sort'] : '';
					if ( ! empty( $payment ) && 0 != $payment ) {
						$vehicle_data['payment'] = $payment;
					}
					$dateinstock = ! empty( $get_field['dateinstock'] ) ? $get_field['dateinstock'] : '';
					if ( ! empty( $dateinstock ) ) {
						$vehicle_data['dateinstock'] = $dateinstock;
					}

					$vehicle_data['year']        = ! empty( $get_field['year'] ) ? $get_field['year'] : '';
					$vehicle_data['dealer_name'] = ! empty( $get_field['dealer_name'] ) ? $get_field['dealer_name'] : '';
					$vehicle_data['photo']       = ! empty( $get_field['primaryimageurl'] ) ? $get_field['primaryimageurl'] : '';
					break;

				case 'lease-offers':
					$vehicle_data['price']   = get_field( 'msrp', $post_id );
					$vehicle_data['payment'] = get_field( 'payment', $post_id );
					break;

				case 'finance-offers':
					$vehicle_data['payment'] = get_field( 'apr', $post_id );
					break;

				case 'conditional-offers':
					$vehicle_data['payment'] = (int) get_field( 'conditional_cash', $post_id );
					break;
			}

			$vehicles[] = $vehicle_data;
		}
	}

	wp_reset_postdata();

	return $vehicles;
}

	/**
	 * Generates and sorts vehicle data based on the provided post type.
	 *
	 * This function retrieves posts of a specified post type, gathers associated taxonomy terms and custom fields,
	 * and optionally sorts the results. It also handles special cases for certain post types like 'listings' and 'used-listings'.
	 *
	 * @param string $post_type The post type to retrieve vehicle data for.
	 * @return array An array containing the vehicle data, including terms, HTML, and custom fields.
	 */
private function generate_vehicles_data( string $post_type, $similar_vehicles = false ): array {

	$sort = ! empty( $_REQUEST['sort'] ) && $_REQUEST['sort'] === 'custom' ? true : false;

	// Set up query arguments based on post type
	$args = array(
		'post_type'      => $post_type,
		'posts_per_page' => -1,
		'fields'         => 'ids',
	);

	if ( $sort && in_array( $post_type, array( 'listings', 'used-listings' ), true ) ) {
		$meta_query = array( 'relation' => 'AND' );
		$orderby    = array();

		if ( have_rows( 'custom_sort_' . $post_type, 'option' ) ) {
			while ( have_rows( 'custom_sort_' . $post_type, 'option' ) ) {
				the_row();

				$field = get_sub_field( 'field' );
				$order = strtoupper( get_sub_field( 'order' ) ) === 'DESC' ? 'DESC' : 'ASC';

				if ( $field ) {
					$clause = $field . '_clause';

					$meta_query[ $clause ] = array(
						'key'     => $field,
						'compare' => 'EXISTS',
					);

					if ( in_array( $field, array( 'price', 'mileage' ), true ) ) {
						$meta_query[ $clause ]['type'] = 'NUMERIC';
					}

					$orderby[ $clause ] = $order;
				}
			}
		}

		if ( count( $meta_query ) > 1 ) {
			$args['meta_query'] = $meta_query;
		}
		if ( ! empty( $orderby ) ) {
			$args['orderby'] = $orderby;
		}
	}

	// Add meta query arguments for specific post types
	if ( in_array( $post_type, array( 'listings', 'used-listings' ), true ) && ! $sort ) {
		$args['meta_key'] = 'price';
		$args['orderby']  = 'meta_value_num';
		$args['order']    = 'ASC';
	}

	// Execute the query
	$query    = get_posts( $args );
	$exclude  = array();
	$vehicles = array();

	foreach ( $query as $post_id ) {
		$exclude_vehicles = '';
		if ( ! in_array( $post_id, $exclude ) ) {
			$exclude_vehicles = array();
			if ( $post_type == 'listings' && $similar_vehicles ) {
				$exclude_vehicles = $this->exclude_vehicles( $post_type, $post_id, true );
				if ( ! empty( $exclude_vehicles ) ) {
					$exclude = array_merge( $exclude, $exclude_vehicles );
					$exclude = array_unique( $exclude );
				}
			}
			$terms = array();

			// Get taxonomies and associated terms
			foreach ( wps_tax( $post_type ) as $index => $value ) {
				$key = $index;
				if ( $field = get_field( $index, $post_id ) ) {
					if ( is_array( $field ) && $key == 'features' ) {
						$new_field = array();
						foreach ( $field as $item ) {
							$new_field[] = $item['feature'];
						}
						$terms[ $key ] = $new_field;
					} elseif ( $index == 'trims' ) {
						$new_field = array();
						foreach ( $field as $item ) {
							$new_field[] = $item['trim'];
						}
						$terms[ $key ] = $new_field;
					} else {
						$terms[ $key ] = array( $field );
					}
				} elseif ( $index == 'hide-duplicates' ) {
					$terms[ $key ] = array( 'enabled' );
				}
			}

			// Handle additional fields for specific post types
			if ( in_array( $post_type, array( 'listings', 'used-listings' ), true ) ) {
				$terms['certified'] = ! empty( get_field( 'certified', $post_id ) ) ? array( get_field( 'certified', $post_id ) ) : '';
				if ( $dealer_special = get_field( 'dealer_special', $post_id ) ) {
					$terms['dealer-special'] = esc_html( $dealer_special );
				}

				$features = array();
				$images   = array();

				// Get features and sort them by ranking
				while ( have_rows( 'features_items', $post_id ) ) :
					the_row();
					while ( have_rows( 'features' ) ) :
						the_row();
						$features[] = array(
							'ranking' => (int) get_sub_field( 'ranking' ),
							'feature' => esc_html( get_sub_field( 'feature' ) ),
							'id'      => get_sub_field( 'id' ),
						);
						endwhile;
					endwhile;

				usort(
					$features,
					function ( $a, $b ) {
						return $b['ranking'] - $a['ranking'];
					}
				);

				// Get feature descriptions from global options
				while ( have_rows( 'feature_list_chromedata', 'options' ) ) :
					the_row();
					$images[ get_sub_field( 'id' ) ] = array(
						'text' => esc_html( get_sub_field( 'text' ) ),
					);
					endwhile;

				// Limit features by the defined limit in options
				$limit = (int) get_field( 'limit_feature_list', 'options' );
				foreach ( $features as $index => $feature ) {
					if ( $index < $limit ) {
						$id                      = $feature['id'];
						$text                    = $images[ $id ]['text'] ?? $feature['feature'];
						$terms['feature_list'][] = $text;
					}
				}
			}
			if ( $vin_number = get_field( 'vin_number', $post_id ) ) {
				$terms['vin'] = array( $vin_number );
			}
			if ( $stock_number = get_field( 'stock_number', $post_id ) ) {
				$terms['stock'] = array( $stock_number );
			}
			$loged = ! empty( $_REQUEST['loged'] ) ? $_REQUEST['loged'] : '';

			// Generate HTML content
			ob_start();
			$args = array(
				'post_id'   => $post_id,
				'post_type' => $post_type,
				'loged'     => $loged,
			);
			if ( $exclude_vehicles ) {
				$args['similar'] = count( $exclude_vehicles );
			}
			$query_data = array();
			foreach ( $this->get_meta_fields( $post_id ) as $field ) {
				if ( isset( $field['key'], $field['value'] ) ) {
					$field                = $field['key'];
					$query_data[ $field ] = strtolower( wps_get_term( $post_id, $field ) );
				}
			}

			$query_params        = http_build_query( $query_data, '', '&', PHP_QUERY_RFC3986 );
			$args['similar_url'] = home_url( $post_type ) . '?' . $query_params;

			get_template_part( 'template-parts/content', $post_type, $args );
			$html = ob_get_clean();

			// Set up vehicle data array
			$vehicle_data = array(
				'title'  => esc_html( get_the_title( $post_id ) ),
				'link'   => esc_url( get_the_permalink( $post_id ) ),
				'search' => $post_type === 'service-offers' ? wp_kses_post( get_the_content( $post_id ) ) : esc_html( get_the_title( $post_id ) ),
				'terms'  => $terms,
				'html'   => $html,
			);
			if ( $post_type == 'offers' ) {
				$vehicle_data['payment'] = get_field( 'lease_payment', $post_id );
				$vehicle_data['year']    = get_the_date( 'Ymdhms', $post_id );
			}

			if ( $post_type == 'offers' || $post_type == 'service-offers' ) {
				$vehicle_data['priority'] = get_field( 'priority', $post_id );
			}

			// Add additional fields for specific post types
			switch ( $post_type ) {
				case 'listings':
				case 'used-listings':
					$payment               = ! empty( get_field( 'loan_payment_sort', $post_id ) ) ? get_field( 'loan_payment_sort', $post_id ) : '';
					$vehicle_data['price'] = ! empty( get_field( 'price_sort', $post_id ) ) ? get_field( 'price_sort', $post_id ) : '';
					if ( ! empty( $payment ) && 0 !== $payment ) {
						$vehicle_data['payment'] = $payment;
					}

					if ( $dateinstock = get_field( 'dateinstock', $post_id ) ) {
						$vehicle_data['dateinstock'] = $dateinstock;
					}

					$vehicle_data['year'] = get_field( 'year', $post_id );
					break;

				case 'lease-offers':
					$vehicle_data['price']   = get_field( 'msrp', $post_id );
					$vehicle_data['payment'] = get_field( 'payment', $post_id );
					break;

				case 'finance-offers':
					$vehicle_data['payment'] = get_field( 'apr', $post_id );
					break;

				case 'conditional-offers':
					$vehicle_data['payment'] = (int) get_field( 'conditional_cash', $post_id );
					break;
			}

			$vehicles[] = $vehicle_data;
		}
	}

	wp_reset_postdata();

	// Handle custom sorting for specific post types
	/*
	if (!empty($_REQUEST['sort']) && $_REQUEST['sort'] === 'custom') {
		if ($post_type === 'used-listings') {
			$custom_sort_by_make = get_field('custom_sort_by_make', 'options') ? get_field('custom_sort_by_make', 'options') : 'honda';

			$vehicles = array_map(function ($vehicle) use ($custom_sort_by_make) {
				if (isset($vehicle['terms']['make'][0])) {
					$make = strtolower(trim($vehicle['terms']['make'][0]));
					$vehicle['make'] = $make;
				}
				return $vehicle;
			}, $vehicles);

			$honda_vehicles = array_filter($vehicles, function ($vehicle) use ($custom_sort_by_make) {
				return isset($vehicle['make']) && $vehicle['make'] === strtolower(trim($custom_sort_by_make));
			});

			$other_vehicles = array_filter($vehicles, function ($vehicle) use ($custom_sort_by_make) {
				return !(isset($vehicle['make']) && $vehicle['make'] === strtolower(trim($custom_sort_by_make)));
			});

			usort($other_vehicles, function ($a, $b) {
				return strcmp($a['make'], $b['make']);
			});

			$vehicles = array_merge($honda_vehicles, $other_vehicles);
		} elseif ($post_type === 'listings') {
			usort($vehicles, function ($a, $b) {
				return strcmp($a['terms']['make'][0], $b['terms']['make'][0])
					?: strcmp($a['terms']['model'][0], $b['terms']['model'][0])
					?: strcmp($a['terms']['trim'][0], $b['terms']['trim'][0]);
			});
		}
	}
	*/
	return $vehicles;
}

	/**
	 * Get all vehicles data.
	 *
	 * This method fetches and caches all vehicles data from the 'listings' and 'used-listings'
	 * post types. If a cached response is available, it will be returned; otherwise, data will be generated
	 * and cached.
	 *
	 * @return WP_REST_Response The vehicles data as a REST API response.
	 */
public function get_vehicles_all() {
	$cache_key       = 'v1/vehicles/';
	$cached_response = get_field( 'cache_api', 'options' ) ? get_transient( $cache_key ) : false;

	if ( false === $cached_response ) {
		$output = $this->generate_vehicles_all();
		set_transient( $cache_key, $output, HOUR_IN_SECONDS );
	} else {
		$output = $cached_response;
	}

	return rest_ensure_response( $output );
}

	/**
	 * Generate data for all vehicles.
	 *
	 * This method retrieves all vehicles from 'listings' and 'used-listings' post types and formats
	 * the data for the API response.
	 *
	 * @return array The formatted vehicles data.
	 */
private function generate_vehicles_all(): array {
	$output = array();

	$query = get_posts(
		array(
			'post_type'      => array( 'listings', 'used-listings' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	foreach ( $query as $post_id ) {
		$output['vehicles'][] = array(
			'title'  => get_the_title( $post_id ),
			'link'   => get_the_permalink( $post_id ),
			'search' => get_the_title( $post_id ),
		);
	}

	wp_reset_postdata();

	return $output;
}
}

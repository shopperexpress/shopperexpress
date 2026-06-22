<?php
/**
 * Theme filters
 *
 * @package Shopperexpress
 */

add_filter( 'big_image_size_threshold', '__return_false' );

add_filter(
	'body_class',
	function ( $classes ) {
		if ( is_user_logged_in() ) {
			$classes[] = 'logged-in';
		}

		$font = get_field( 'font', 'options' );
		if ( ! empty( $font ) && 1 != $font ) {
			$classes[] = 'theme-inner';
		}

		if ( get_field( 'new_home_page_styles' ) ) {
			$classes[] = 'new-landing';
		}

		if ( is_front_page() || is_single() ) {
			$classes[] = 'page-loaded';
		}

		return $classes;
	}
);

add_filter(
	'pre_set_site_transient_update_themes',
	function ( $transient ) {

		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$theme_slug = 'shopperexpress';
		$repo       = 'shopperexpress/shopperexpress';

		if ( ! isset( $transient->checked[ $theme_slug ] ) ) {
			return $transient;
		}

		$current_version = $transient->checked[ $theme_slug ];

		$response = wp_remote_get(
			"https://api.github.com/repos/$repo/releases/latest",
			array(
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'WordPress',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $transient;
		}

		$release = json_decode( wp_remote_retrieve_body( $response ) );

		if ( empty( $release->tag_name ) ) {
			return $transient;
		}

		$new_version = ltrim( $release->tag_name, 'v' );

		$package_url = '';
		if ( ! empty( $release->assets ) ) {
			foreach ( $release->assets as $asset ) {
				if ( str_ends_with( $asset->name, '.zip' ) ) {
					$package_url = $asset->browser_download_url;
					break;
				}
			}
		}

		if ( ! $package_url ) {
			$package_url = "https://github.com/$repo/archive/refs/tags/{$release->tag_name}.zip";
		}

		if ( version_compare( $current_version, $new_version, '<' ) ) {
			$transient->response[ $theme_slug ] = array(
				'theme'       => $theme_slug,
				'slug'        => $theme_slug,
				'new_version' => $new_version,
				'package'     => $package_url,
				'url'         => "https://github.com/$repo",
			);
		}

		return $transient;
	}
);

if ( ! is_admin() ) {
	foreach ( array(
		'wpseo_title',
		'wpseo_metadesc',
		'wpseo_opengraph_title',
		'wpseo_opengraph_desc',
		'wpseo_twitter_title',
		'wpseo_twitter_desc',
	) as $hook ) {
		add_filter( $hook, 'do_shortcode' );
	}
}

add_filter(
	'wpseo_title',
	function ( $title ) {
		return do_shortcode( $title );
	}
);

add_filter(
	'wpseo_metadesc',
	function ( $desc ) {
		return do_shortcode( $desc );
	}
);

add_filter(
	'redirect_canonical',
	function ( $redirect_url, $requested_url ) {

		if ( isset( $_GET['year'] ) ) {
			return false;
		}

		return $redirect_url;
	},
	10,
	2
);

add_filter(
	'wp_all_import_use_wp_set_object_terms',
	function ( $use_wp_set_object_terms, $tx_name ) {
		return true;
	},
	10,
	2
);

add_filter( 'comments_open', '__return_false', 20, 2 );
add_filter( 'pings_open', '__return_false', 20, 2 );
add_filter( 'comments_array', '__return_empty_array', 10, 2 );

add_filter(
	'acf/the_field/allow_unsafe_html',
	function ( $allowed, $selector ) {
		return true;
	},
	10,
	2
);

add_filter( 'acf/format_value/type=textarea', 'do_shortcode' );

add_filter(
	'acf/format_value',
	function ( $value, $post_id, $field ) {

		if ( in_array( $field['type'], array( 'textarea', 'text' ) ) ) {
			if ( is_array( $value ) ) {
				$value = implode( '', $value );
			}
			$value = do_shortcode( $value );
		}

		return $value;
	},
	10,
	3
);

add_filter(
	'wpforms_smart_tag_process',
	function ( $content, $tag ) {

		$post_id = get_the_ID();

		// API mode: if post_id in the request is a non-numeric VIN, fetch vehicle
		// from the Intice API instead of reading ACF fields from a WP post.
		$api_vehicle    = null;
		$raw_request_id = isset( $_REQUEST['post_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['post_id'] ) ) : '';

		if (
			$raw_request_id
			&& ! is_numeric( $raw_request_id )
			&& get_option( 'shopperexpress_api_mode_enabled' )
			&& class_exists( '\App\Components\Api\Intice_Api_Client' )
		) {
			$api_result = \App\Components\Api\Intice_Api_Client::instance()->get_vehicle( $raw_request_id );
			if ( ! is_wp_error( $api_result ) && ! empty( $api_result ) ) {
				$api_vehicle = $api_result;
			}
		}

		// tag => [ acf_field, api_key (, 'upper'|'price') ]
		// 'api_key' omitted = ACF-only tag (offers/service/disclosure).
		$tag_map = array(
			// Vehicle fields — API-aware.
			'year'               => array( 'year', 'year' ),
			'make'               => array( 'make', 'make' ),
			'model'              => array( 'model', 'model' ),
			'trim'               => array( 'trim', 'trim' ),
			'miles'              => array( 'mileage', 'mileage' ),
			'vin'                => array( 'vin_number', 'vin', 'upper' ),
			'stock'              => array( 'stock-number', 'stock' ),
			'type'               => array( 'condition', 'condition' ),
			// ACF-only fields.
			'service_disclaimer' => array( 'offerdisclaimer' ),
			'offer_year'         => array( 'year' ),
			'offer_make'         => array( 'make' ),
			'offer_model'        => array( 'model' ),
			'offer_trim'         => array( 'trim' ),
			'service_title'      => array( 'title' ),
			'service_offer'      => array( 'offertext' ),
			'service_dept'       => array( 'dept' ),
			'service_type'       => array( 'type' ),
			'service_expiration' => array( 'expiration' ),
			'service_info'       => array( 'additioninfo' ),
			'service_image'      => array( 'offerimage' ),
			'disclosure_lease'   => array( 'disclosure_lease' ),
			'disclosure_finance' => array( 'disclosure_finance' ),
			'disclosure_cash'    => array( 'disclosure_cash' ),
		);

		if ( isset( $tag_map[ $tag ] ) ) {
			[ $acf_key, $api_key, $format ] = $tag_map[ $tag ] + array( null, null, null );

			$value = ( $api_vehicle && $api_key ) ? ( $api_vehicle[ $api_key ] ?? null ) : get_field( $acf_key, $post_id );

			if ( $value ) {
				if ( 'upper' === $format ) {
					$value = strtoupper( $value );
				}
				$content = str_replace( '{' . $tag . '}', $value, $content );
			}

			return $content;
		}

		// Special cases that don't fit the simple get→replace pattern.
		switch ( $tag ) {

			case 'msrp':
				$raw   = $api_vehicle ? ( $api_vehicle['msrp'] ?? 0 ) : get_field( 'price', $post_id );
				$value = $raw ? number_format( (int) $raw ) : null;
				break;

			case 'best_price':
				$raw   = $api_vehicle
					? ( $api_vehicle['price_sort'] ?? $api_vehicle['price'] ?? 0 )
					: get_field( 'price', $post_id );
				$value = $raw ? number_format( (int) $raw ) : null;
				break;

			case 'internet_price':
				if ( $api_vehicle ) {
					$payload = $api_vehicle['payload'] ?? array();
					$raw     = $payload['customprice2'] ?? ( $payload['internet_price'] ?? 0 );
					$value   = $raw ? number_format( (int) $raw ) : null;
				} else {
					$raw   = get_field( 'customprice2', $post_id );
					$value = is_float( $raw ) ? number_format( $raw ) : null;
				}
				break;

			case 'offer_image':
				$gallery = get_field( 'gallery', $post_id );
				$value   = ! empty( $gallery[0]['image_url'] ) ? $gallery[0]['image_url'] : null;
				break;

			case 'url':
				if ( $api_vehicle ) {
					$vin_val  = strtoupper( $api_vehicle['vin'] ?? $raw_request_id );
					$referrer = wp_get_referer();
					$slug     = ( $referrer && str_contains( $referrer, 'used-listings' ) ) ? 'used-listings' : 'listings';
					$value    = home_url( '/' . $slug . '/' . $vin_val . '/' );
				} else {
					$value = get_the_permalink( $post_id );
				}
				break;

			default:
				$value = null;
		}

		if ( ! empty( $value ) ) {
			$content = str_replace( '{' . $tag . '}', $value, $content );
		}

		while ( have_rows( 'smart_tags', 'options' ) ) :
				the_row();
				$field      = get_sub_field( 'acf_field_selector' );
				$field_type = get_sub_field( 'field_type' );
			if ( $tag === $field['value'] ) {

				switch ( $field_type ) {
					case 'price':
						$get_field = get_field( $tag, $post_id ) ? number_format( get_field( $tag, $post_id ) ) : null;
						break;
					case 'text':
					default:
						$get_field = get_field( $tag, $post_id ) ? get_field( $tag, $post_id ) : null;
						break;
				}
				if ( $get_field ) {
					$content = str_replace( '{' . $tag . '}', $get_field, $content );
				}
			}

			endwhile;

		return $content;
	},
	10,
	2
);

/**
 * Inject asc_event into the WPForms AJAX success response so asc-datalayer.js
 * ajaxSuccess handler can fire ascPublishEvent without a separate client-side call.
 *
 * Filter signature: ( $response, $form_id, $form_data ) — $form_id is an int.
 * wp_send_json_success( $response ) wraps it as { success: true, data: $response },
 * so keys added here appear in JS as response.data.asc_event.
 */
add_filter(
	'wpforms_ajax_submit_success_response',
	function ( $response, $form_id, $form_data ) {

		// Resolve form_type: find the field whose CSS class contains "asc_form_type",
		// then read its submitted value from $_POST['wpforms']['fields'][$field_id].
		$form_type       = 'unknown';
		$submitted_fields = isset( $_POST['wpforms']['fields'] ) && is_array( $_POST['wpforms']['fields'] )
			? wp_unslash( $_POST['wpforms']['fields'] )
			: array();

		if ( ! empty( $form_data['fields'] ) ) {
			foreach ( $form_data['fields'] as $field_id => $field_cfg ) {
				if ( ! empty( $field_cfg['css'] ) && false !== strpos( $field_cfg['css'], 'asc_form_type' ) ) {
					$raw = $submitted_fields[ $field_id ] ?? '';
					if ( is_array( $raw ) ) {
						$raw = implode( '', $raw );
					}
					$form_type = sanitize_text_field( $raw ) ?: 'unknown';
					break;
				}
			}
		}

		$page_type     = sanitize_text_field( wp_unslash( $_POST['asc_page_type'] ?? '' ) );
		$page_location = esc_url_raw( wp_unslash( $_POST['asc_page_location'] ?? '' ) );
		$items_raw     = wp_unslash( $_POST['asc_items'] ?? '[]' );
		$items         = json_decode( $items_raw, true );
		if ( ! is_array( $items ) ) {
			$items = array();
		}

		$base = array(
			'event_owner'   => 'intice',
			'page_type'     => $page_type,
			'page_location' => $page_location,
			'form_id'       => 'wpforms_' . absint( $form_id ),
			'form_type'     => $form_type,
			'items'         => $items,
		);

		$response['asc_events'] = array(
			array_merge( $base, array( 'event' => 'asc_form_submission' ) ),
			array_merge( $base, array( 'event' => 'asc_form_submission_' . $form_type ) ),
		);

		return $response;
	},
	10,
	3
);

add_filter(
	'wpforms_smart_tags',
	function ( $tags ) {

		$items = array(
			'disclosure_lease'   => 'Disclosure lease',
			'disclosure_finance' => 'Disclosure finance',
			'disclosure_cash'    => 'Disclosure cach',
			'year'               => 'Year',
			'make'               => 'Make',
			'model'              => 'Model',
			'trim'               => 'Trim',
			'miles'              => 'Miles',
			'vin'                => 'Vin',
			'stock'              => 'Stock',
			'type'               => 'Type',
			'offer_year'         => 'Offer-Year',
			'offer_make'         => 'Offer-Make',
			'offer_model'        => 'Offer-Model',
			'offer_trim'         => 'Offer-Trim',
			'offer_image'        => 'Offer Primary Image URL',
			'service_title'      => 'Service-Title',
			'service_offer'      => 'Service-Offer',
			'service_dept'       => 'Service-Dept',
			'service_type'       => 'Service-Type',
			'service_expiration' => 'Service-Expiration',
			'service_info'       => 'Service-Info',
			'service_image'      => 'Service Primary Image URL',
			'msrp'               => 'MSRP',
			'best_price'         => 'Best Price',
			'internet_price'     => 'Internet Price',
			'service_disclaimer' => 'Service Disclaimer',
			'url'                => 'Page URL',

		);

		while ( have_rows( 'smart_tags', 'options' ) ) :
			the_row();
			$field = get_sub_field( 'acf_field_selector' );

			if ( $field ) {
				$items[ $field['value'] ] = $field['label'];
			}

		endwhile;

		foreach ( $items as $slug => $item ) {
			$tags[ $slug ] = $item;
		}

		return $tags;
	}
);

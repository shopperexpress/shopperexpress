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
	add_filter( 'wpseo_title', 'do_shortcode' );
	add_filter( 'wpseo_metadesc', 'do_shortcode' );
	add_filter( 'wpseo_opengraph_title', 'do_shortcode' );
	add_filter( 'wpseo_opengraph_desc', 'do_shortcode' );
	add_filter( 'wpseo_twitter_title', 'do_shortcode' );
	add_filter( 'wpseo_twitter_desc', 'do_shortcode' );
}

add_filter( 'wpseo_title', 'do_shortcode' );

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

		switch ( $tag ) {

			case 'service_disclaimer':
				$offerdisclaimer = get_field( 'offerdisclaimer', $post_id ) ? get_field( 'offerdisclaimer', $post_id ) : null;
				if ( $offerdisclaimer ) {
					$content = str_replace( '{service_disclaimer}', $offerdisclaimer, $content );
				}
				break;

			case 'msrp':
				$price = get_field( 'price', $post_id ) ? number_format( get_field( 'price', $post_id ) ) : null;
				if ( $price ) {
					$content = str_replace( '{msrp}', $price, $content );
				}
				break;

			case 'best_price':
				$price = get_field( 'price', $post_id ) ? number_format( get_field( 'price', $post_id ) ) : null;
				if ( $price ) {
					$content = str_replace( '{best_price}', $price, $content );
				}
				break;

			case 'internet_price':
				$price = get_field( 'customprice2', $post_id );
				if ( is_float( $price ) ) {
					$content = str_replace( '{internet_price}', number_format( $price ), $content );
				}
				break;

			case 'offer_year':
				$year = get_field( 'year', $post_id );
				if ( $year ) {
					$content = str_replace( '{offer_year}', $year, $content );
				}
				break;

			case 'offer_make':
				$make = get_field( 'make', $post_id );
				if ( $make ) {
					$content = str_replace( '{offer_make}', $make, $content );
				}
				break;

			case 'offer_model':
				$model = get_field( 'model', $post_id );
				if ( $model ) {
					$content = str_replace( '{offer_model}', $model, $content );
				}
				break;

			case 'offer_trim':
				$trim = get_field( 'trim', $post_id );
				if ( $trim ) {
					$content = str_replace( '{offer_trim}', $trim, $content );
				}
				break;

			case 'offer_image':
				$image = get_field( 'gallery', $post_id ) ? get_field( 'gallery', $post_id )[0]['image_url'] : null;
				if ( $image ) {
					$content = str_replace( '{offer_image}', $image, $content );
				}
				break;

			case 'service_title':
				$title = get_field( 'title', $post_id );
				if ( $title ) {
					$content = str_replace( '{service_title}', $title, $content );
				}
				break;

			case 'service_offer':
				$offer = get_field( 'offertext', $post_id );
				if ( $offer ) {
					$content = str_replace( '{service_offer}', $offer, $content );
				}
				break;

			case 'service_dept':
				$dept = get_field( 'dept', $post_id );
				if ( $dept ) {
					$content = str_replace( '{service_dept}', $dept, $content );
				}
				break;

			case 'service_type':
				$type = get_field( 'type', $post_id );
				if ( $type ) {
					$content = str_replace( '{service_type}', $type, $content );
				}
				break;

			case 'service_expiration':
				$expiration = get_field( 'expiration', $post_id );
				if ( $expiration ) {
					$content = str_replace( '{service_expiration}', $expiration, $content );
				}
				break;

			case 'service_info':
				$info = get_field( 'additioninfo', $post_id );
				if ( $info ) {
					$content = str_replace( '{service_info}', $info, $content );
				}
				break;

			case 'service_image':
				$image = get_field( 'offerimage', $post_id );
				if ( $image ) {
					$content = str_replace( '{service_image}', $image, $content );
				}
				break;

			case 'disclosure_lease':
				$lease = get_field( 'disclosure_lease', $post_id );
				if ( $lease ) {
					$content = str_replace( '{disclosure_lease}', $lease, $content );
				}
				break;

			case 'disclosure_finance':
				$finance = get_field( 'disclosure_finance', $post_id );
				if ( $finance ) {
					$content = str_replace( '{disclosure_finance}', $finance, $content );
				}
				break;

			case 'disclosure_cash':
				$cash = get_field( 'disclosure_cash', $post_id );
				if ( $cash ) {
					$content = str_replace( '{disclosure_cash}', $cash, $content );
				}
				break;

			case 'year':
				$year = get_field( 'year', $post_id );
				if ( $year ) {
					$content = str_replace( '{year}', $year, $content );
				}
				break;

			case 'make':
				$make = get_field( 'make', $post_id );
				if ( $make ) {
					$content = str_replace( '{make}', $make, $content );
				}
				break;

			case 'model':
				$model = get_field( 'model', $post_id );
				if ( $model ) {
					$content = str_replace( '{model}', $model, $content );
				}
				break;

			case 'trim':
				$trim = get_field( 'trim', $post_id );
				if ( $trim ) {
					$content = str_replace( '{trim}', $trim, $content );
				}
				break;

			case 'miles':
				$miles = get_field( 'mileage', $post_id );
				if ( $miles ) {
					$content = str_replace( '{miles}', $miles, $content );
				}
				break;

			case 'vin':
				$vin = get_field( 'vin_number', $post_id );
				if ( $vin ) {
					$content = str_replace( '{vin}', $vin, $content );
				}
				break;

			case 'stock':
				$stock = get_field( 'stock-number', $post_id );
				if ( $stock ) {
					$content = str_replace( '{stock}', $stock, $content );
				}
				break;

			case 'type':
				$type = get_field( 'condition', $post_id );
				if ( $type ) {
					$content = str_replace( '{type}', $type, $content );
				}
				break;
			case 'url':
				$url = get_the_permalink( $post_id );
				if ( $url ) {
					$content = str_replace( '{url}', $url, $content );
				}
				break;
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

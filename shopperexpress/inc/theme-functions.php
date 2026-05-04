<?php
/**
 * Theme functions.
 *
 * @package Shopperexpress
 */

/**
 * Clean phone function.
 *
 * @param  string $phone incoming string.
 * @return string
 */
function clean_phone( $phone = '' ) {
	if ( empty( $phone ) ) {
		return;
	}
	return preg_replace( '/[^0-9]/', '', $phone );
}

/**
 * Get theme attachment image
 *
 * @param  int    $id image id.
 * @param  string $size image size.
 * @param  array  $attr additional attributes.
 * @return string
 */
function get_attachment_image( int $id, string $size = 'full', array $attr = array() ): string {
	$image = wp_get_attachment_image( $id, $size, null, $attr );

	if ( ! $image ) {
		return '';
	}

	return preg_replace( '/(height|width)="\d*"\s/', '', wp_kses_post( $image ) );
}

/**
 * Get date archive link
 *
 * @return string
 */
function get_date_archive_link() {
	if ( get_option( 'eg_date_archive_link_type' ) == 'year' ) {
		$res = get_year_link( get_the_date( 'Y' ) );
	} elseif ( get_option( 'eg_date_archive_link_type' ) == 'day' ) {
		$res = get_day_link( get_the_date( 'Y' ), get_the_date( 'm' ), get_the_date( 'd' ) );
	} else {
		$res = get_month_link( get_the_date( 'Y' ), get_the_date( 'm' ) );
	}
	return $res;
}

/**
 * Get link
 *
 * @param  array  $link link array.
 * @param  string $class class name.
 * @param  string $before before link text.
 * @param  string $attr additional attributes.
 * @return string
 */
function wps_get_link( $link, $class = null, $before = null, $attr = null ) {
	if ( $link ) {

		$title  = $link['url'];
		$url    = $link['url'];
		$target = null;

		if ( isset( $link['title'] ) and ! empty( $link['title'] ) ) {
			$title = $link['title'];
		}

		if ( $target = $link['target'] ) {
			$target = ' target="' . $target . '" ';
		}

		if ( ! empty( $class ) ) {
			$class = 'class="' . $class . '" ';
		}

		return '<a href="' . esc_url( $url ) . '" ' . $class . $target . $attr . '>' . $before . $title . '</a>';
	}
}

add_action(
	'wp_print_styles',
	function () {
		if ( is_singular( 'listings' ) || is_page_template() == 'archive-listings' ) {
			wp_styles()->add_data( 'style', 'after', '' );
		}
	}
);

/**
 * SEO URL
 *
 * @param string $string string.
 * @return string
 */
function seoUrl( $string ) {
	if ( empty( $string ) ) {
		return;
	}
	$string = strtolower( $string );
	$string = preg_replace( '/[^a-z0-9_\s-]/', '', $string );
	$string = preg_replace( '/[\s-]+/', ' ', $string );
	$string = preg_replace( '/[\s_]/', '-', $string );
	return $string;
}

/**
 * Hex to RGB
 *
 * @param  string $hex hex color.
 * @param  bool   $alpha alpha.
 * @return string
 */
function hexToRgb( $hex, $alpha = false ) {
	$hex      = str_replace( '#', '', $hex );
	$length   = strlen( $hex );
	$rgb['r'] = hexdec( $length == 6 ? substr( $hex, 0, 2 ) : ( $length == 3 ? str_repeat( substr( $hex, 0, 1 ), 2 ) : 0 ) );
	$rgb['g'] = hexdec( $length == 6 ? substr( $hex, 2, 2 ) : ( $length == 3 ? str_repeat( substr( $hex, 1, 1 ), 2 ) : 0 ) );
	$rgb['b'] = hexdec( $length == 6 ? substr( $hex, 4, 2 ) : ( $length == 3 ? str_repeat( substr( $hex, 2, 1 ), 2 ) : 0 ) );
	if ( $alpha ) {
		$rgb['a'] = $alpha;
	}
	return implode( ',', $rgb );
}

add_action(
	'wp_logout',
	function () {
		wp_safe_redirect( home_url() );
		exit;
	}
);

add_shortcode(
	'page_id',
	function ( $atts = array() ) {
		global $post;
		return $post->ID;
	}
);

function adf_email( $fields = array() ) {
	$first_name = ! empty( $fields['first_name'] ) ? sanitize_text_field( $fields['first_name'] ) : '';
	$last_name  = ! empty( $fields['last_name'] ) ? sanitize_text_field( $fields['last_name'] ) : '';
	$email      = ! empty( $fields['email'] ) ? sanitize_email( $fields['email'] ) : '';
	$phone      = ! empty( $fields['phone'] ) ? sanitize_text_field( $fields['phone'] ) : '';
	$comments   = ! empty( $fields['comments'] ) ? wp_kses_post( $fields['comments'] ) : '';
	$zip        = ! empty( $fields['zip'] ) ? sanitize_text_field( $fields['zip'] ) : '';

	$message = '<?xml version="1.0" encoding="utf-8"?>
	<?ADF version="1.0"?>
	<adf>
		<prospect>
			<id source="shopperexpress" sequence="1"></id>
			<requestdate>' . date( 'm-d-Y' ) . '</requestdate>
			<customer>
				<contact primarycontact="1">
					<name part="first">' . wps_esc_xml( $first_name ) . '</name>
					<name part="last">' . wps_esc_xml( $last_name ) . '</name>
					<name part="full">' . wps_esc_xml( $first_name . ' ' . $last_name ) . '</name>
					<email>' . wps_esc_xml( $email ) . '</email>
					<phone time="day" type="voice">' . wps_esc_xml( $phone ) . '</phone>
					<address>
						<street line="1"></street>
						<street line="2"></street>
						<city></city>
						<regioncode></regioncode>
						<postalcode>' . wps_esc_xml( $zip ) . '</postalcode>
						<country></country>
					</address>
				</contact>
				<comments>' . wps_esc_xml( $comments ) . '</comments>
			</customer>
			<provider>
				<name part="full">intice</name>
				<service>shopperexpress</service>
				<url>http://www.inticeinc.com</url>
				<email>support@inticeinc.com</email>
				<phone>855-747-7770</phone>
				<contact primarycontact="1">
					<name part="full">Intice Inc</name>
					<email>support@inticeinc.com</email>
					<phone time="day" type="voice">855-747-7770</phone>
					<phone time="day" type="fax">888-220-2913</phone>
					<address>
						<street line="1">2660 Cypress Ridge Blvd.</street>
						<street line="2">Suite 103</street>
						<city>Wesley Chapel</city>
						<regioncode>FL</regioncode>
						<postalcode>33544</postalcode>
						<country>USA</country>
					</address>
				</contact>
			</provider>
		</prospect>
	</adf>';

	$mail_to  = array();
	$mail_cc  = array();
	$mail_bcc = array();

	while ( have_rows( 'email_notification', 'options' ) ) :
		the_row();
		$email_address = sanitize_email( get_sub_field( 'email' ) );
		if ( is_email( $email_address ) ) {
			$mail_to[] = $email_address;
		}
	endwhile;

	while ( have_rows( 'email_notification_cc', 'options' ) ) :
		the_row();
		$email_address = sanitize_email( get_sub_field( 'email' ) );
		if ( is_email( $email_address ) ) {
			$mail_cc[] = $email_address;
		}
	endwhile;

	while ( have_rows( 'email_notification_bcc', 'options' ) ) :
		the_row();
		$email_address = sanitize_email( get_sub_field( 'email' ) );
		if ( is_email( $email_address ) ) {
			$mail_bcc[] = $email_address;
		}
	endwhile;

	if ( ! empty( $mail_to ) ) {
		$headers = array(
			'Content-Type: text/html; charset=utf-8',
		);

		if ( ! empty( $mail_cc ) ) {
			$headers[] = 'Cc: ' . implode( ', ', $mail_cc );
		}

		if ( ! empty( $mail_bcc ) ) {
			$headers[] = 'Bcc: ' . implode( ', ', $mail_bcc );
		}

		wp_mail( implode( ', ', $mail_to ), 'intice360: ' . $first_name . ' ' . $last_name, $message, $headers );
	}
}

function wps_esc_xml( $data ) {
	return htmlspecialchars( $data, ENT_XML1 | ENT_COMPAT, 'UTF-8' );
}

add_action(
	'register_new_user',
	function () {
		remove_action( 'register_new_user', 'wp_send_new_user_notifications' );
	},
	9
);

add_action(
	'wpforms_process_complete',
	function ( $fields, $entry, $form_data, $entry_id ) {

		if ( get_field( 'contact_savings_form', 'options' ) == $entry['id'] || get_field( 'unlock_savings_form', 'options' ) == $entry['id'] ) {
			if ( ! session_id() && session_status() !== PHP_SESSION_ACTIVE ) {
				// session_start();
				if ( ! is_user_logged_in() ) {
					wps_auth( 'setcookie' );
				}
			}
		}
	},
	10,
	4
);

add_action(
	'template_redirect',
	function () {
		if ( ! empty( $_GET['action'] ) && $_GET['action'] == 'logout' ) {
			wps_auth( 'logout' );
		}
	}
);

function wps_auth( $action = '' ) {

	$auth   = is_user_logged_in() ? true : false;
	$action = ! empty( $_GET['action'] ) ? $_GET['action'] : $action;

	switch ( $action ) {
		case 'setcookie':
			$auth = wps_login( 'test' );
			break;

		case 'logout':
			$auth = false;
			wp_logout();
			break;
	}

	return $auth;
}

function check_user_exists_by_login( $login ) {
	return username_exists( $login );
}

function generate_random_password( $length = 12 ) {
	// Characters allowed in the password
	$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_';

	// Generate random bytes
	$bytes = random_bytes( $length );

	// Convert random bytes to string
	$password = '';
	for ( $i = 0; $i < $length; $i++ ) {
		// Get random index
		$index = ord( $bytes[ $i ] ) % strlen( $chars );
		// Append character at the random index to the password
		$password .= $chars[ $index ];
	}

	return $password;
}

function add_user_if_not_exists( $login, $email, $password ) {
	if ( ! username_exists( $login ) ) {
		$user_id = wp_create_user( $login, $password, $email );
		if ( ! is_wp_error( $user_id ) ) {
			// User was created successfully
			return $user_id;
		} else {
			// Error creating user
			return false;
		}
	} else {
		// User already exists
		return false;
	}
}

function wps_login( $username ) {
	if ( is_user_logged_in() ) {
		wp_logout();
	}

	if ( function_exists( 'allow_programmatic_login' ) ) {
		add_filter( 'authenticate', 'allow_programmatic_login', 10, 3 );
	}

	$username = sanitize_user( $username, true );

	if ( empty( $username ) || ! username_exists( $username ) ) {
		if ( $user_id = add_user_if_not_exists( $username, 'example1@test.test', generate_random_password() ) ) {
			$username = get_userdata( $user_id )->user_login;
		} else {
			return false;
		}
	}

	$user = wp_signon( array( 'user_login' => $username ) );

	if ( function_exists( 'allow_programmatic_login' ) ) {
		remove_filter( 'authenticate', 'allow_programmatic_login', 10, 3 );
	}

	if ( is_wp_error( $user ) ) {
		return false;
	}

	if ( is_a( $user, 'WP_User' ) ) {
		wp_set_current_user( $user->ID, $user->user_login );

		if ( is_user_logged_in() ) {
			return true;
		}
	}

	return false;
}


function allow_programmatic_login( $user, $username, $password ) {
	return get_user_by( 'login', $username );
}

add_action(
	'pmxi_after_xml_import ',
	function () {
		$query = new WP_Query(
			array(
				'post_type'   => 'listings',
				'post_status' => 'publish',
				'fields'      => 'ids',
				'numberposts' => -1,
				'meta_query'  => array(
					array(
						'key'   => 'sold',
						'value' => 'Yes',
					),
				),
			)
		);
		if ( $query->posts ) {
			foreach ( $query->posts as $post_id ) {
				wp_delete_post( $post_id );
			}
		}
	},
	10
);

add_filter( 'auto_update_plugin', '__return_true' );
remove_action( 'wp_head', 'wp_site_icon', 99 );

function wps_site_icon() {
	if ( ! has_site_icon() && ! is_customize_preview() ) {
		return;
	}

	if ( ! is_admin() ) {
		$meta_tags = array();
		$icon_32   = get_site_icon_url( 32 );
		if ( empty( $icon_32 ) && is_customize_preview() ) {
			$icon_32 = '/favicon.ico';
		}

		$meta_tags[] = sprintf( '<link rel="icon" href="%s" type="image/png" />', esc_url( get_site_icon_url() ) );

		if ( $icon_32 ) {
			$meta_tags[] = sprintf( '<link rel="icon" href="%s" sizes="32x32" type="image/png" />', esc_url( $icon_32 ) );
		}
		$icon_192 = get_site_icon_url( 192 );
		if ( $icon_192 ) {
			$meta_tags[] = sprintf( '<link rel="icon" href="%s" sizes="192x192" type="image/png" />', esc_url( $icon_192 ) );
		}
		$icon_180 = get_site_icon_url( 180 );
		if ( $icon_180 ) {
			$meta_tags[] = sprintf( '<link rel="apple-touch-icon" href="%s" type="image/png" />', esc_url( $icon_180 ) );
		}
		$icon_270 = get_site_icon_url( 270 );
		if ( $icon_270 ) {
			$meta_tags[] = sprintf( '<meta name="msapplication-TileImage" content="%s" />', esc_url( $icon_270 ) );
		}

		$meta_tags = apply_filters( 'site_icon_meta_tags', $meta_tags );
		$meta_tags = array_filter( $meta_tags );

		foreach ( $meta_tags as $meta_tag ) {
			echo "$meta_tag\n";
		}
	}
}
apply_filters( 'site_icon_meta_tags', function () {} );
add_action( 'wp_head', 'wps_site_icon' );

add_action(
	'pre_get_posts',
	function ( $query ) {
		if ( ! is_admin() && $query->is_main_query() && is_post_type_archive( 'listings' ) ) {
			$query->set( 'order', 'ASC' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'meta_key', 'price' );
		}
		return $query;
	}
);

add_image_size( 'drop', 40, 30, false );

/**
 * Retrieves a list of taxonomies and their associated labels and icons for a given post type.
 *
 * This function generates an array of taxonomies for a specific post type, based on filters
 * defined in the "filters_list" ACF (Advanced Custom Fields) repeater field. It checks conditions
 * for displaying each filter and returns an array with labels and icons for the applicable filters.
 *
 * @param string $post_type The post type for which to retrieve taxonomies.
 * @return array An associative array of taxonomies with labels and icons.
 */
function wps_tax( string $post_type ): array {

	$output = array();

	// Determine the suffix for ACF field name based on the post type
	$row_suffix = ! in_array( $post_type, array( 'listings', 'used-listings' ) ) ? '_' . $post_type : '';

	// Loop through each row in the ACF "filters_list" repeater field
	while ( have_rows( 'filters_list' . $row_suffix, 'options' ) ) :
		the_row();
		$filter  = get_sub_field( 'filter' );
		$label   = get_sub_field( 'label' ) ? get_sub_field( 'label' ) : $label;
		$show_on = get_sub_field( 'show_on' ) ? get_sub_field( 'show_on' ) : 'all';

		// Determine whether to show the filter based on the post type
		if ( $show_on !== 'all' ) {
			$show_on = ( $show_on === $post_type );
		} else {
			$show_on = true;
		}

		// Add the filter to the output array if it should be shown and isn't already added
		if ( $filter && get_sub_field( 'show_filter' ) && $show_on && ! isset( $output[ $filter ] ) ) {
			$output[ $filter ] = array(
				'label' => esc_html( $label ),
				'icon'  => get_sub_field( 'icon' ),
			);
		}

	endwhile;

	return $output;
}

add_action(
	'filter_modal',
	function () {
		if ( is_post_type_archive() || is_page_template( 'pages/template-srp.php' ) ) {
			get_template_part( 'template-parts/modal', 'tabs' );
		}
	}
);

add_action(
	'init',
	function () {
		$repeater_rows = get_field( 'shortcodes', 'options' );

		if ( $repeater_rows ) {
			foreach ( $repeater_rows as $row ) {
				$shortcode_name  = strtolower( str_replace( ' ', '_', $row['shortcode_name'] ) );
				$shortcode_value = $row['shortcode_value'];

				add_shortcode(
					'sc_' . $shortcode_name,
					function () use ( $shortcode_value ) {
						return wp_kses_post( $shortcode_value );
					}
				);
			}
		}
	}
);

add_action(
	'admin_init',
	function () {

		global $pagenow;

		if ( $pagenow === 'edit-comments.php' ) {
			wp_safe_redirect( admin_url() );
			exit;
		}

		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );

		foreach ( get_post_types() as $post_type ) {
			if ( post_type_supports( $post_type, 'comments' ) ) {
				remove_post_type_support( $post_type, 'comments' );
				remove_post_type_support( $post_type, 'trackbacks' );
			}
		}
	}
);

add_action(
	'admin_menu',
	function () {
		remove_menu_page( 'edit-comments.php' );
	}
);

add_action(
	'init',
	function () {
		if ( is_admin_bar_showing() ) {
			remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
		}
	}
);

/**
 * Retrieves the vehicle spin link for a given VIN.
 *
 * This function checks if the given VIN is empty and returns early if it is.
 * It then retrieves the URL for the CSV file containing vehicle data from ACF options.
 * If the URL is empty, it returns false.
 *
 * @param string $vin The Vehicle Identification Number (VIN) for which to retrieve the spin link.
 * @return string|bool The vehicle spin link if found, or false if the VIN is empty or the URL is missing.
 */
function get_vehicle_spin( $vin = null ) {
	if ( empty( $vin ) ) {
		return;
	}

	$csv_url  = get_field( 'url_for_csv_360', 'options' );
	$response = wp_remote_get( $csv_url );

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$csv_content = wp_remote_retrieve_body( $response );
	$rows        = array_map( 'str_getcsv', explode( "\n", $csv_content ) );
	foreach ( $rows as $row ) {
		if ( isset( $row[1] ) && $row[1] === $vin ) {
			if ( isset( $row[2] ) ) {
				$link = $row[2];
				if ( empty( $link ) ) {
					return;
				}
				$link = preg_replace( '#/NLP\??#', '/NLP', $link );
				$link = str_replace( 'NLPvehicle_fkey', 'NLP/?vehicle_fkey', $link );
				return $link;
			}
			return false;
		}
	}

	return false;
}

function search_relevent_vehicles( $post_id = null ) {

	if ( get_field( 'show_similar_vehicles', 'options' ) != true ) {
		return null;
	}
	if ( get_post_type( $post_id ) != 'listings' ) {
		return null;
	}

	$post_type   = get_post_type( $post_id );
	$meta_fields = get_field( 'similar_vehicles_from', 'options' );
	$meta_fields = ! is_array( $meta_fields ) ? array( $meta_fields ) : $meta_fields;

	$args = array(
		'post_type'      => $post_type,
		'posts_per_page' => -1,
		'fields'         => 'ids',
	);

	$meta_args  = array();
	$query_data = array();
	foreach ( $meta_fields as $field ) {
		if ( $item = get_field( $field, $post_id ) ) {
			$meta_args[]          = array(
				'key'   => $field,
				'value' => $item,
			);
			$query_data[ $field ] = strtolower( get_field( $field, $post_id ) );
		}
	}

	if ( ! empty( $meta_args ) ) {
		$args['meta_query'] = array_merge( array( 'relation' => 'AND' ), $meta_args );
	}
	if ( ! isset( $_REQUEST['clear-relevent'] ) ) {
		$cached_response = get_transient( 'relevent_vehicles_' . $post_type . $post_id );
		$cached_response = get_field( 'cache_similar_vehicles', 'options' ) ? $cached_response : false;
	}
	if ( false === $cached_response ) {
		$query        = new WP_Query( $args );
		$query_params = http_build_query( $query_data, '', '&', PHP_QUERY_RFC3986 );
		$url          = home_url( $post_type ) . '?' . $query_params;
		$output       = array(
			'count' => $query->found_posts,
			'url'   => $url,
		);

		set_transient( 'relevent_vehicles_' . $post_type . $post_id, $output, HOUR_IN_SECONDS );
	} else {
		$output = $cached_response;
	}

	return $output;
}

define(
	'ALLOWED_TAGS',
	array(
		'svg'  => array(
			'xmlns'           => true,
			'width'           => true,
			'height'          => true,
			'viewBox'         => true,
			'fill'            => true,
			'stroke'          => true,
			'stroke-width'    => true,
			'stroke-linecap'  => true,
			'stroke-linejoin' => true,
			'aria-hidden'     => true,
			'role'            => true,
			'class'           => true,
			'style'           => true,
		),
		'path' => array(
			'd'               => true,
			'fill'            => true,
			'stroke'          => true,
			'stroke-width'    => true,
			'stroke-linecap'  => true,
			'stroke-linejoin' => true,
		),
	)
);

/**
 * Generates HTML for a default image.
 *
 * This function retrieves a default image URL from the options. If no image is found,
 * it falls back to a placeholder image. Optionally, a CSS class can be added to the
 * surrounding `div` element.
 *
 * @param string|null $class Optional. CSS class to add to the `div` element. Default is null.
 * @return string HTML markup for the image.
 */
function default_image( string $class = null, string $post_type = null ): string {
	// Retrieve the ID of the default image from the options and sanitize it as an integer

	if ( ! empty( $post_type ) && ! in_array( $post_type, array( 'listings', 'used-listings' ) ) ) {
		$default_image_id = absint( get_field( $post_type . '_default_image', 'option' ) );
	} else {
		$default_image_id = absint( get_field( 'default_image', 'option' ) );
	}

	// Determine the image URL, falling back to a placeholder if necessary
	$def_img = $default_image_id !== 0
		? wp_get_attachment_image_url( $default_image_id, 'full' )
		: esc_url( App\asset_url( 'images/image-placeholder.png' ) );

	// Sanitize and set the CSS class if provided
	$class_attr = ! empty( $class ) ? ' class="' . esc_attr( $class ) . '"' : '';

	// Generate and return the HTML markup for the image
	return '<div' . $class_attr . '><img src="' . esc_url( $def_img ) . '" alt="' . esc_attr__( 'Image description', 'shopperexpress' ) . '" class="img-fluid"></div>';
}

function get_default_image( string $post_type = null ): string {
	// Retrieve the ID of the default image from the options and sanitize it as an integer

	if ( ! empty( $post_type ) && ! in_array( $post_type, array( 'listings', 'used-listings' ) ) ) {
		$default_image_id = absint( get_field( $post_type . '_default_image', 'option' ) );
	} else {
		$default_image_id = absint( get_field( 'default_image', 'option' ) );
	}

	return $default_image_id !== 0
		? wp_get_attachment_image_url( $default_image_id, 'full' )
		: esc_url( App\asset_url( 'images/image-placeholder.png' ) );
}

/**
 * Generate event script based on the event ID.
 *
 * This function takes an event ID, location, and VIN number to generate
 * the corresponding script for launching different events.
 *
 * @param int    $event_id   The ID of the event from ACF field.
 * @param string $location   The location data used in the event script.
 * @param string $vin_number The VIN number used in the event script.
 *
 * @return string The generated JavaScript function or action to be executed.
 */
function get_event_script( $event_id, $location, $vin_number ) {
	// Validate and sanitize inputs
	$event_id   = absint( $event_id );
	$location   = sanitize_text_field( $location );
	$vin_number = sanitize_text_field( $vin_number );

	switch ( $event_id ) {
		case 1:
			// Launch event for "Loan"
			return "launchDM('" . esc_js( $location ) . "', '" . esc_js( $vin_number ) . "', 'Loan');";

		case 2:
			// Launch event for TM
			return "launchTM('" . esc_js( $location ) . "', '" . esc_js( $vin_number ) . "');";

		case 3:
			// Launch event for LOM
			return "launchLOM('" . esc_js( $location ) . "', '" . esc_js( $vin_number ) . "');";

		case 4:
			// Launch event for LM
			return "launchLM('" . esc_js( $location ) . "', '" . esc_js( $vin_number ) . "');";

		case 5:
			// Launch event for "Lease"
			return "launchDM('" . esc_js( $location ) . "', '" . esc_js( $vin_number ) . "', 'Lease');";

		case 6:
			// Launch event for ECO
			return "launchECO('" . esc_js( $location ) . "', '', '', '', '', '" . esc_js( $vin_number ) . "');";

		case 7:
			// Launch popup event
			return 'popup';

		case 8:
			// Launch event for Leadmaker Lite
			return "launchLMLite('" . esc_js( $location ) . "', '" . esc_js( $vin_number ) . "');";

		default:
			// Return empty string if no valid event ID
			return '';
	}
}

function get_url_with_fields( $post_id = '', $post_type = '', $url = '' ) {
	if ( empty( $post_id ) && empty( $post_type ) ) {
		return;
	}
	$fields = array(
		'year',
		'make',
		'model',
		'trim',
		'miles',
		'vin',
		'stock',
		'url',
	);

	foreach ( $fields as $field ) {
		if ( $field == 'url' ) {
			$url = str_replace( '[' . $field . ']', get_permalink( $post_id ), $url );
		} else {
			$url = str_replace( '[' . $field . ']', get_field( $field, $post_id ), $url );
		}
	}

	return $url;
}

function get_listings_count( $year, $make, $model, $condition, $index, $row ) {
	$count = get_transient( 'acf-count-' . $index . $row );
	if ( ! empty( $_REQUEST['clear'] ) ) {
		$count = false;
	}
	if ( false === $count ) {
		$meta_query = array( 'relation' => 'AND' );

		if ( $year ) {
			$meta_query[] = array(
				'key'     => 'year',
				'value'   => $year,
				'compare' => '=',
			);
		}

		if ( $make ) {
			$meta_query[] = array(
				'key'     => 'make',
				'value'   => $make,
				'compare' => '=',
			);
		}

		if ( $model ) {
			$meta_query[] = array(
				'key'     => 'model',
				'value'   => $model,
				'compare' => '=',
			);
		}

		if ( $condition ) {
			$meta_query[] = array(
				'key'     => 'condition',
				'value'   => $condition,
				'compare' => '=',
			);
		}

		$args = array(
			'post_type'      => 'listings',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'meta_query'     => $meta_query,
		);

		$query = new WP_Query( $args );
		$count = $query->found_posts;

		set_transient( 'acf-count-' . $index . $row, $count, 12 * HOUR_IN_SECONDS );

		wp_reset_postdata();
	}

	return $count;
}

add_action(
	'pre_get_posts',
	function ( $query ) {
		if ( ! is_admin() && isset( $query->query_vars['year'] ) ) {
			$query->is_year            = false;
			$query->is_date            = false;
			$query->query_vars['year'] = null;
		}
	}
);

add_action(
	'wp_parse_request',
	function ( $query ) {
		if ( isset( $query->query_vars['year'] ) ) {
			$query->query_vars['year'] = null;
			$query->is_year            = false;
			$query->is_date            = false;
		}
	}
);

add_action(
	'pmxi_after_xml_import',
	function ( $import_id ) {
		while ( have_rows( 'after_import', 'options' ) ) :
			the_row();
			if ( get_sub_field( 'import_id' ) == $import_id ) {
				if ( get_sub_field( 'use' ) == 1 ) {
					wp_remote_get( esc_url( get_sub_field( 'action_url' ) ) );
				} else {
					$post_type = get_sub_field( 'post_type' );
					$command   = "wp api clear {$post_type} --clear=true";
					exec( escapeshellcmd( $command ) . ' > /dev/null 2>&1 &' );
				}
			}
	endwhile;
	}
);


function CallAPI( $url, $data, $post_id ) {
	$post_type = get_post_type( $post_id );
	$type      = in_array( $post_type, array( 'finance-offers', 'lease-offers', 'conditional-offers' ) ) ? $post_type . '_' : null;

	if ( ! have_rows( $type . 'features_items', $post_id ) ) {
		if ( function_exists( 'run_callapi_in_background' ) ) {
			run_callapi_in_background( $url, $data, $post_id );
		}
	}
}

add_action( 'template_redirect', 'redirect_by_vin_and_post_type' );

function redirect_by_vin_and_post_type() {
	if ( is_admin() || ! isset( $_GET['vin'] ) || empty( $_GET['vin'] ) ) {
		return;
	}

	$vin = sanitize_text_field( $_GET['vin'] );

	$request_uri = $_SERVER['REQUEST_URI'];

	if ( strpos( $request_uri, '/listings/' ) !== false ) {
		$post_type = 'listings';
	} elseif ( strpos( $request_uri, '/used-listings/' ) !== false ) {
		$post_type = 'used-listings';
	} else {
		return;
	}

	$query = new WP_Query(
		array(
			'post_type'      => $post_type,
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => 'vin_number',
					'value'   => $vin,
					'compare' => '=',
				),
			),
		)
	);

	if ( $query->posts ) {
		$redirect_url = get_permalink( $query->posts[0] );

		wp_redirect( $redirect_url, 301 );
		exit;
	} else {
		return;
	}

	wp_reset_query();
}

function custom_yoast_breadcrumbs_as_ol() {
	if ( function_exists( 'yoast_breadcrumb' ) ) {
		ob_start();
		yoast_breadcrumb( '', '' );
		$breadcrumbs_html = ob_get_clean();

		preg_match_all(
			'/<a [^>]+>.*?<\/a>|<span class="breadcrumb_last"[^>]*>.*?<\/span>|<li[^>]*class="[^"]*breadcrumb-item[^"]*"[^>]*>.*?<\/li>/s',
			$breadcrumbs_html,
			$matches
		);

		if ( ! empty( $matches[0] ) ) {

			foreach ( $matches[0] as &$crumb ) {
				if ( preg_match( '/<li[^>]*>(.*?)<\/li>/s', $crumb, $inner ) ) {
					$crumb = $inner[1];
				}
				if ( strpos( $crumb, '<a' ) === false ) {
					$crumb = '<a href="#">' . $crumb . '</a>';
				}
			}
			$items = $matches[0];

			// array_shift($items);

			echo '<ol class="breadcrumbs">';
			foreach ( $items as $index => $item ) {
				if ( $index === array_key_last( $items ) && str_starts_with( $item, '<span' ) ) {
					preg_match( '/<span.*?>(.*?)<\/span>/', $item, $text_match );
					$label = $text_match[1] ?? '—';
					echo '<li><a href="#">' . esc_html( $label ) . '</a></li>';
				} else {
					echo '<li>' . $item . '</li>';
				}
			}
			echo '</ol>';
		}
	}
}

function display_svg_icon( $svg_code, $display = true ) {

	if ( empty( trim( $svg_code ) ) ) {
		return;
	}

	if ( strpos( $svg_code, 'aria-hidden="true"' ) === false ) {
		$svg_code = preg_replace( '/<svg([^>]*)>/', '<svg$1 aria-hidden="true">', $svg_code );
	}

	if ( $display ) {
		echo $svg_code;
	} else {
		return $svg_code;
	}
}

/**
 * ACF SVG filter to allow raw SVG code.
 *
 * https://www.advancedcustomfields.com/resources/html-escaping/
 */
add_filter( 'wp_kses_allowed_html', 'acf_add_allowed_svg_tag', 10, 2 );

function acf_add_allowed_svg_tag( $tags, $context ) {
	if ( $context === 'acf' ) {
		$tags['svg']  = array(
			'xmlns'               => true,
			'width'               => true,
			'height'              => true,
			'preserveAspectRatio' => true,
			'fill'                => true,
			'viewbox'             => true,
			'role'                => true,
			'aria-hidden'         => true,
			'focusable'           => true,
		);
		$tags['path'] = array(
			'd'    => true,
			'fill' => true,
		);
	}

	return $tags;
}

function get_json_from_url( $url = '' ) {
	if ( empty( $url ) ) {
		return;
	}
	$response = wp_remote_get( $url );

	if ( is_wp_error( $response ) ) {
		return null;
	}

	$body = wp_remote_retrieve_body( $response );

	$data = json_decode( $body, true );

	return $data;
}

function wps_check_current_usser() {
	if ( is_user_logged_in() ) {
		$allowed_roles = array( 'administrator', 'dealership_admin', 'full_dealer_admin' );
		$current_user  = wp_get_current_user();
		if ( array_intersect( $allowed_roles, $current_user->roles ) ) {
			return true;
		}
	}
	return false;
}

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( is_singular( 'listings' ) ) {
			if ( function_exists( 'acf_enqueue_scripts' ) ) {
				acf_enqueue_scripts();
			} else {
				wp_enqueue_script( 'acf-input' );
				wp_enqueue_style( 'acf-input' );
			}
		}
	},
	999
);

function get_backup_images( $vin_number ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'image_backup';
	$row        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE vin = %s", $vin_number ), ARRAY_A );
	$gallery    = array(
		'image_url'        => get_default_image(),
		'image_background' => false,
		'image_reverse'    => false,
	);
	if ( $row ) {
		$images = explode( '|', $row['images'] );
		$images = is_array( $images ) ? $images : array( $images );
		if ( ! empty( $images ) ) {
				$gallery = array();
			foreach ( $images as $image ) {
				$gallery[] = array(
					'image_url'        => str_replace( 'http://', 'https://', $image ),
					'image_background' => false,
					'image_reverse'    => false,
				);
			}
		}
		return $gallery;
	}

	return $gallery;
}

function set_backup_images( $vin_number, $gallery ) {
	global $wpdb;

	$table_name = $wpdb->prefix . 'image_backup';
	if ( is_array( $gallery ) ) {
		$urls = array();

		foreach ( $gallery as $item ) {
			if ( is_array( $item ) && ! empty( $item['image_url'] ) ) {
				$urls[] = trim( str_replace( 'http://', 'https://', $item['image_url'] ) );
			}
		}

		$gallery = implode( '|', $urls );
	}

	$gallery = str_replace( 'http://', 'https://', $gallery );

	$gallery = trim( (string) $gallery );

	if ( $gallery === '' ) {
		return false;
	}

	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE vin = %s",
			$vin_number
		),
		ARRAY_A
	);

	if ( $row ) {
		$updated = $wpdb->update(
			$table_name,
			array(
				'images'     => $gallery,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'vin' => $vin_number ),
			array(
				'%s',
				'%s',
			),
			array( '%s' )
		);

		return ( false !== $updated );
	}

	$inserted = $wpdb->insert(
		$table_name,
		array(
			'vin'    => $vin_number,
			'images' => $gallery,
		),
		array(
			'%s',
			'%s',
		)
	);

	return ( false !== $inserted );
}

add_action(
	'init',
	function () {

		global $wp_filter;

		if ( empty( $wp_filter['wp_head']->callbacks ) ) {
			return;
		}

		foreach ( $wp_filter['wp_head']->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $cb ) {

				if (
				is_array( $cb['function'] )
				&& is_object( $cb['function'][0] )
				&& $cb['function'][0] instanceof HeaderAndFooterScripts
				&& $cb['function'][1] === 'wp_head'
				) {
					remove_action( 'wp_head', $cb['function'], $priority );
				}
			}
		}
	}
);

add_action(
	'wp_head',
	function () {

		if ( ! is_singular() ) {
			return;
		}

		$meta = get_post_meta( get_the_ID(), '_inpost_head_script', true );

		if (
		is_array( $meta )
		&& ! empty( $meta['synth_header_script'] )
		) {
			echo do_shortcode( $meta['synth_header_script'] ), "\n";
		}
	},
	20
);

add_filter(
	'wpseo_breadcrumb_output_wrapper',
	function () {
		return 'ol';
	}
);
add_filter(
	'wpseo_breadcrumb_output_class',
	function () {
		return 'breadcrumb';
	}
);
add_filter(
	'wpseo_breadcrumb_single_link',
	function ( $link_output ) {

		if ( strpos( $link_output, 'breadcrumb_last' ) !== false ) {
			$link_output = str_replace(
				'<span',
				'<li class="breadcrumb-item active"',
				$link_output
			);
			$link_output = str_replace( '</span>', '</li>', $link_output );
		} else {
			$link_output = str_replace(
				'<span',
				'<li class="breadcrumb-item"',
				$link_output
			);
			$link_output = str_replace( '</span>', '</li>', $link_output );
		}

		return $link_output;
	}
);
add_filter(
	'wpseo_breadcrumb_separator',
	function () {
		return '';
	}
);

add_image_size( '454x255', 454, 255, true );

/**
 * Get font family from font styling.
 *
 * @param string $font_styling Font styling.
 */
function get_font_family( $font_styling = '' ) {
	$fonts_family = '';

	if ( $font_styling ) :
		switch ( $font_styling ) {
			case 'Roboto Condensed':
				$fonts_family = 'Roboto+Condensed:wght@100..900';
				break;
			case 'Roboto Mono':
				$fonts_family = 'Roboto+Mono:wght@100..700';
				break;
			case 'Poppins':
				$fonts_family = 'Poppins:wght@400;600;700';
				break;
			case 'Lato':
				$fonts_family = 'Lato:wght@400;700';
				break;
			case 'PT Sans':
				$fonts_family = 'PT+Sans:ital,wght@0,400;0,700;1,400;1,700';
				break;
			case 'PT Sans Narrow':
				$fonts_family = 'PT+Sans+Narrow:wght@400;700';
				break;
			case 'Inter':
				$fonts_family = 'Inter:opsz,wght@14..32,100..900';
				break;
		}
		if ( ! empty( $fonts_family ) ) :
			?>
			<style>
				@import url('https://fonts.googleapis.com/css2?family=<?php echo $fonts_family; ?>&display=swap');
			</style>
			<?php
			endif;
	endif;
}

/**
 * Build style attribute.
 *
 * @param array $style Style array.
 */
function build_style_attr( array $style = array() ): string {
	$styles = array();

	$map = array(
		'text_color'  => 'color',
		'font_size'   => 'font-size',
		'font_weight' => 'font-weight',
		'font_family' => 'font-family',
	);

	foreach ( $map as $field => $css_property ) {
		if ( empty( $style[ $field ] ) ) {
			continue;
		}

		$value = esc_attr( $style[ $field ] );

		if ( 'font_size' === $field ) {
			$value .= 'px';
		}

		$styles[] = "{$css_property}: {$value};";
	}

	return $styles ? ' style="' . implode( ' ', $styles ) . '"' : '';
}

<?php

/**
 * Theme functions.
 *
 * @package ThemeName
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
 * Contact form 7 helper function
 *
 * @param  int $form_id form id.
 * @return string
 */
function get_form( int $form_id ): string {

	$form_id = absint( $form_id );

	if ( ! $form_id ) {
		return '';
	}

	return do_shortcode( '[contact-form-7 id="' . $form_id . '"]' );
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
 * Get theme backgroun image (in style attribute)
 *
 * @param  int    $id image id.
 * @param  string $size image size.
 * @return string
 */
function get_bg_image( int $id, string $size = 'full' ): string {
	$image = wp_get_attachment_image_src( $id, $size );

	if ( ! $image[0] ) {
		return '';
	}

	return ' style="background-image: url(' . esc_url( $image[0] ) . ');"';
}

/**
 * Get featured image alt attribute.
 *
 * @param  int $attachment_id attachment id, alt of which will be displayed.
 * @return string $image_alt
 */
function get_attachment_alt( int $attachment_id ): string {
	$img_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

	if ( ! empty( $img_alt ) ) {
		return $img_alt;
	} else {
		return esc_html__( 'image description', 'shopperexpress' );
	}
}

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

function card_detail( $post_id = null, $post_type = '' ) {

	$post_type = ! empty( $post_type ) ? '-' . $post_type : null;

	$terms = array(
		'vin-number' . $post_type     => __( 'Vin', 'shopperexpress' ),
		'stock-number' . $post_type   => __( 'Stock', 'shopperexpress' ),
		'mileage' . $post_type        => __( 'Mileage', 'shopperexpress' ),
		'engine' . $post_type         => __( 'Engine/Fuel', 'shopperexpress' ),
		'transmission' . $post_type   => __( 'Transmission', 'shopperexpress' ),
		'drivetrain' . $post_type     => __( 'Drivetrain', 'shopperexpress' ),
		'exterior-color' . $post_type => __( 'Exterior color', 'shopperexpress' ),
		'interior-color' . $post_type => __( 'Interior color', 'shopperexpress' ),
		'trim' . $post_type           => __( 'Trim', 'shopperexpress' ),

	);

	foreach ( $terms as $key => $value ) :
		?>
		<dt><?php echo $value; ?>:</dt>
		<?php
		if ( str_contains( $key, 'vin-number' ) ) :
			?>
			<dd class="vin"><?php else : ?>
			<dd><?php endif; ?>
			<?php
			if ( $key == 'engine' ) {
				echo wps_get_term( $post_id, $key ) . ' / ' . wps_get_term( $post_id, 'fuel-type' . $post_type );
			} else {
				echo wps_get_term( $post_id, $key );
			}
			?>
			</dd>
		<?php
	endforeach;
}

function offers_card_detail( $post_id = null ) {
	$terms = array(
		'year'  => __( 'Year', 'shopperexpress' ),
		'make'  => __( 'Make', 'shopperexpress' ),
		'model' => __( 'Model', 'shopperexpress' ),
		'trim'  => __( 'Trim', 'shopperexpress' ),

	);

	foreach ( $terms as $key => $value ) :
		?>
			<dt><?php echo $value; ?>:</dt>
			<dd><?php echo wps_get_term( $post_id, $key . '-' . get_post_type( $post_id ) ); ?></dd>
		<?php
	endforeach;
	if ( $add_info = get_field( 'addinfo' ) ) {
		?>
			<dt><?php echo _e( 'Add`l Info', 'shopperexpress' ); ?>:</dt>
			<dd><?php echo $add_info; ?></dd>
		<?php
	}
}


function seoUrl( $string ) {
	$string = strtolower( $string );
	$string = preg_replace( '/[^a-z0-9_\s-]/', '', $string );
	$string = preg_replace( '/[\s-]+/', ' ', $string );
	$string = preg_replace( '/[\s_]/', '-', $string );
	return $string;
}

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

add_action( 'wp_logout', 'auto_redirect_after_logout' );

function auto_redirect_after_logout() {
	wp_safe_redirect( home_url() );
	exit;
}

/**
 * Retrieves the specified field value from a taxonomy term or a custom field.
 *
 * This function checks if a given taxonomy exists and retrieves the specified field value
 * (e.g., name, slug) from the first term associated with the post. If the taxonomy doesn't exist,
 * it tries to retrieve a custom field value using the provided taxonomy name,
 * which may be altered to fit the custom field naming convention.
 *
 * @param int|WP_Post $post The post ID or object.
 * @param string      $taxonomy The taxonomy name.
 * @param string      $field Optional. The field to retrieve from the term. Default is 'name'.
 * @return string|null The sanitized term field value or custom field value, or null if not found.
 */
function wps_get_term( $post, string $taxonomy, string $field = 'name', $field_type = 'taxonomy' ): ?string {

	$value = null;

	// Adjust the taxonomy name to match custom field format
	$field = str_replace( '-' . get_post_type( $post ), '', $taxonomy );

	// Get the custom field value
	$value = get_field( $field, $post, true, true );

	// Return the sanitized value, or null if value is empty
	return $value !== null ? $value : null;
}

function shortcode_callback_page_id( $atts = array() ) {
	global $post;
	return $post->ID;
}
add_shortcode( 'page_id', 'shortcode_callback_page_id' );

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

/*
add_action('init', function () {
	if (!session_id() && session_status() !== PHP_SESSION_ACTIVE && !is_admin()) {
		session_start();
	}
});
*/
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
	'wpcf7_mail_sent',
	function ( $contact_form ) {
		$submission      = WPCF7_Submission::get_instance();
		$contact_form    = WPCF7_ContactForm::get_current();
		$contact_form_id = $contact_form->id;

		if ( $submission ) {
			$posted_data = $submission->get_posted_data();
		} else {
			return;
		}

		adf_email(
			array(
				'first_name' => $posted_data['firstName'],
				'last_name'  => $posted_data['lastName'],
				'email'      => $posted_data['your-email'],
				'zip'        => $posted_data['zip'],
				'comments'   => $posted_data['message'],
			)
		);
	}
);


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


function shortcode_callback_offer_payment( $atts = array() ) {
	$acf_prefix = 'service-';

	global $post;
	$post_id = $post->ID;

	$location      = wps_get_term( $post_id, 'location' );
	$condition     = wps_get_term( $post_id, 'condition' );
	$loanterm      = get_field( 'loanterm', $post_id );
	$loanapr       = get_field( 'loanapr', $post_id );
	$down_payment  = wps_get_term( $post_id, 'down-payment' );
	$lease_payment = wps_get_term( $post_id, 'lease-payment' );
	$loan_payment  = wps_get_term( $post_id, 'loan-payment' );
	$leaseterm     = wps_get_term( $post_id, 'leaseterm' );
	while ( have_rows( $acf_prefix . 'offers_flexible_content', 'options' ) ) :
		the_row();
		if ( get_row_layout() == 'payment' && have_rows( 'payment_list' ) ) {
			while ( have_rows( 'payment_list' ) ) :
				the_row();
				$link         = get_sub_field( 'link' );
				$lock         = get_sub_field( 'lock' );
				$show_payment = $lock ? get_sub_field( 'show_payment' ) : false;
				$show_event   = get_sub_field( 'show_event' );

				$down_payment = ! empty( $down_payment ) ? $down_payment : number_format( $price );

				switch ( $atts['type'] ) {
					case 'lease-payment':
						if ( $down_payment && $lease_payment ) {
							$lease_payment = ! empty( $lease_payment ) ? '$' . number_format( $lease_payment ) : null;
							$output        = ! empty( $lease_payment ) ? '<span class="savings">$' . $down_payment . ' ' . __( 'DOWN', 'shopperexpress' ) . '</span>' . $lease_payment . ' <sub>/mo</sub>' : null;
						} else {
							$output = null;
						}

						break;

					case 'Disclosure_loan':
						if ( $condition != 'Slightly Used' && $condition != 'Used' ) {
							$output = $loanterm ? '<span class="savings">' . $loanterm . ' ' . __( 'mos.', 'shopperexpress' ) . '</span>' : '';
							if ( $loanapr ) {
								$output .= $loanapr . '% <sub>APR</sub>';
							}
						} else {
							$output = 2;
						}
						break;

					case 'Disclosure_lease':
						if ( $down_payment && $lease_payment ) {
							$lease_payment = ! empty( $lease_payment ) && $lease_payment != 'None' && $lease_payment > 0 ? '$' . number_format( $lease_payment ) : null;
							$output        = ! empty( $lease_payment ) ? '<span class="savings">$' . $down_payment . ' ' . __( 'DOWN', 'shopperexpress' ) . ' ' . $leaseterm . ' ' . __( 'mos.', 'shopperexpress' ) . '</span>' . $lease_payment . ' <sub>/mo</sub>' : null;
						} else {
							$output = null;
						}
						break;
					case 'Disclosure_Cash':
						if ( $condition != 'Slightly Used' && $condition != 'Used' ) {
							$cash_offer       = get_field( 'cash_offer' );
							$cash_offer       = is_int( $cash_offer ) ? '$' . number_format( $cash_offer ) : $cash_offer;
							$cash_offer_label = get_field( 'cash_offer_label' );
							$output           = ! empty( $cash_offer ) ? '<span class="savings">' . $cash_offer_label . '</span>' . $cash_offer : null;
						} else {
							$output = null;
						}
						break;

					default:
						$loan_payment = ! empty( $loan_payment ) && $loan_payment != 'None' ? '$' . number_format( $loan_payment ) . ' <sub>/mo</sub>' : null;
						$output       = ! empty( $loan_payment ) ? '<span class="savings">$' . $down_payment . ' ' . __( 'DOWN', 'shopperexpress' ) . '</span>' . $loan_payment : null;
						break;
				}

			endwhile;
		}
	endwhile;

	return strip_tags( $output );
}
add_shortcode( 'offer_payment', 'shortcode_callback_offer_payment' );


function shortcode_callback_offer_content( $atts = array() ) {
	global $post;
	$post_id = $post->ID;
	switch ( $atts['type'] ) {
		case 'lease':
			$output = get_field( 'disclosure_lease', $post_id );
			break;
		case 'loan':
			$output = get_field( 'disclosure_finance', $post_id );
			break;
		case 'cash':
			$output = get_field( 'disclosure_cash', $post_id );
			break;
	}

	return strip_tags( $output );
}
add_shortcode( 'offer_content', 'shortcode_callback_offer_content' );


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

function wps_get_icon( $icon = '' ) {
	return '<i class="material-symbols-rounded">' . str_replace( ' ', '_', $icon ) . '</i>';
}

add_shortcode(
	'stock',
	function ( $atts = array() ) {

		$condition = ! empty( $atts['condition'] ) ? strtolower( $atts['condition'] ) : 'new';
		$post_type = $condition == 'used' ? 'used-listings' : 'listings';

		$args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);

		$query = new WP_Query( $args );

		return $query->found_posts;
	}
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

add_shortcode(
	'show',
	function ( $atts = array() ) {

		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_the_id();

		if ( ! empty( $atts['tax'] ) ) {
			return wps_get_term( $post_id, $atts['tax'] . '-' . get_post_type( $post_id ) );
		} elseif ( ! empty( $atts['field'] ) ) {
			$output = get_field( $atts['field'], $post_id );
			if ( is_array( $output ) ) {
				return;
			}
			return $output;
		}

		return;
	}
);

add_shortcode(
	'price',
	function ( $atts = array() ) {

		$post_id = ! empty( $atts['id'] ) && $atts['id'] != 'post_id' ? $atts['id'] : get_the_ID();

		$output = '<span class="js-is-empty">';
		if ( $price = get_field( 'price', $post_id ) ) {
			$output .= $price;
		}
		$output .= '</span>';
		return $output;
	}
);

add_shortcode(
	'loan-term',
	function ( $atts = array() ) {
		$post_id = ! empty( $atts['id'] ) && $atts['id'] != 'post_id' ? $atts['id'] : get_the_ID();

		$output = '<span class="js-is-empty">';
		if ( $price = get_field( 'loanterm', $post_id ) ) {
			$output .= $price;
		}
		$output .= '</span>';
		return $output;
	}
);

add_shortcode(
	'lease-term',
	function ( $atts = array() ) {
		$post_id = ! empty( $atts['id'] ) && $atts['id'] != 'post_id' ? $atts['id'] : get_the_ID();

		$output = '<span class="js-is-empty">';
		if ( $price = get_field( 'leaseterm', $post_id ) ) {
			$output .= $price;
		}
		$output .= '</span>';
		return $output;
	}
);

add_shortcode(
	'due-at-signing',
	function ( $atts = array() ) {
		$post_id = ! empty( $atts['id'] ) && $atts['id'] != 'post_id' ? $atts['id'] : get_the_ID();

		$output = '<span class="js-is-empty">';
		if ( $price = get_field( 'down_payment', $post_id ) ) {
			$output .= $price;
		}
		$output .= '</span>';
		return $output;
	}
);

add_shortcode(
	'total-of-payments',
	function ( $atts = array() ) {
		$post_id = ! empty( $atts['id'] ) && $atts['id'] != 'post_id' ? $atts['id'] : get_the_ID();

		$output = '<span class="js-is-empty">';
		if ( $price = get_field( 'totalofpmts', $post_id ) ) {
			$output .= $price;
		}
		$output .= '</span>';
		return $output;
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
	'acf/the_field/allow_unsafe_html',
	function ( $allowed, $selector ) {
		return true;
	},
	10,
	2
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

add_filter( 'comments_open', '__return_false', 20, 2 );
add_filter( 'pings_open', '__return_false', 20, 2 );
add_filter( 'comments_array', '__return_empty_array', 10, 2 );

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

add_shortcode(
	'site_url',
	function ( $atts = array(), $content = '' ) {
		return get_template_directory_uri() . '/assets/dist/';
	}
);

add_filter(
	'wp_all_import_use_wp_set_object_terms',
	function ( $use_wp_set_object_terms, $tx_name ) {
		return true;
	},
	10,
	2
);

function get_vehicle_spin( $vin = null ) {
	if ( empty( $vin ) ) {
		return;
	}

	$csvUrl   = get_field( 'url_for_csv_360', 'options' );
	$response = wp_remote_get( $csvUrl );

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$csvContent = wp_remote_retrieve_body( $response );
	$rows       = array_map( 'str_getcsv', explode( "\n", $csvContent ) );
	foreach ( $rows as $row ) {
		if ( isset( $row[1] ) && $row[1] === $vin ) {
			if ( isset( $row[2] ) ) {
				$link = $row[2];
				$link = preg_replace( '#/NLP\??#', '/NLP/?', $link );
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
			$query_data[ $field ] = strtolower( wps_get_term( $post_id, $field ) );
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

add_filter( 'big_image_size_threshold', '__return_false' );

add_filter(
	'body_class',
	function ( $classes ) {
		if ( is_user_logged_in() ) {
			$classes[] = 'logged-in';
		}
		return $classes;
	}
);

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
			$url = str_replace( '[' . $field . ']', wps_get_term( $post_id, $field . $post_type ), $url );
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

add_filter(
	'redirect_canonical',
	function ( $redirect_url, $requested_url ) {
		if ( isset( $_GET['year'] ) ) {
			return $requested_url;
		}
		return $redirect_url;
	},
	10,
	2
);

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
		/*
		$curl = curl_init();

		$url = sprintf("%s?%s", $url, http_build_query($data));

		$mt = explode(' ', microtime());

		$chromedata_timestamp = ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000));

		$chromedata_noonce = substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(32))), 0, 32);
		$realm = 'http://chromedata.com';

		$chromedata_app_id = get_field('chromedata_app_id', 'options');
		$shared_secret = get_field('shared_secret', 'options');
		$chromedata_secret_digest_original = $chromedata_noonce . $chromedata_timestamp . $shared_secret;
		$chromedata_secret_digest = base64_encode(sha1($chromedata_secret_digest_original, true));
		$token = "Atmosphere realm=\"{$realm}\",";
		$token .= "chromedata_app_id=\"{$chromedata_app_id}\",";
		$token .= "chromedata_nonce=\"{$chromedata_noonce}\",";
		$token .= "chromedata_secret_digest=\"{$chromedata_secret_digest}\",";
		$token .= "chromedata_digest_method=SHA1,";
		$token .= "chromedata_version=1.0,";
		$token .= "chromedata_timestamp=\"{$chromedata_timestamp}\"";

		$headers = array(
			"Accept: application/json",
			"Content-Type: application/json",
			"Authorization: {$token}",
		);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_VERBOSE, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		$result = curl_exec($curl);
		curl_close($curl);

		$result = json_decode($result, true);

		if (!empty($result['result'])) {
			foreach ($result['result']['features'] as $item) {
				$output[$item['sectionName']][] = $item;
			}
		}
		foreach ($output as $index => $item) {
			$list = [];
			foreach ($item as $value) {
				$description = $value['description'] != $value['nameNoBrand'] ? $value['description'] . ': ' . $value['nameNoBrand'] : $value['description'];
				$list[] = ['feature' => $description, 'ranking' => $value['rankingValue'], 'id' => $value['id']];
			}
			add_row($type . 'features_items', ['heading' => $index, 'features' => $list], $post_id);
		}
		*/
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

		preg_match_all( '/<a [^>]+>.*?<\/a>|<span class="breadcrumb_last"[^>]*>(.*?)<\/span>/', $breadcrumbs_html, $matches );

		if ( ! empty( $matches[0] ) ) {
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

		return ( $updated !== false );
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

	return ( $inserted !== false );
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

add_filter( 'wpseo_title', 'do_shortcode' );

add_filter( 'wpseo_title', 'my_wpseo_title_shortcodes' );
function my_wpseo_title_shortcodes( $title ) {
	return do_shortcode( $title );
}

add_filter( 'wpseo_metadesc', 'my_wpseo_metadesc_shortcodes' );
function my_wpseo_metadesc_shortcodes( $desc ) {
	return do_shortcode( $desc );
}
if ( ! is_admin() ) {
	add_filter( 'wpseo_title', 'do_shortcode' );
	add_filter( 'wpseo_metadesc', 'do_shortcode' );
	add_filter( 'wpseo_opengraph_title', 'do_shortcode' );
	add_filter( 'wpseo_opengraph_desc', 'do_shortcode' );
	add_filter( 'wpseo_twitter_title', 'do_shortcode' );
	add_filter( 'wpseo_twitter_desc', 'do_shortcode' );
}

add_action(
	'admin_init',
	function () {
		delete_site_transient( 'update_themes' );
	}
);

add_filter(
	'pre_set_site_transient_update_themes',
	function ( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$theme_slug      = 'shopperexpress';
		$current_version = $transient->checked[ $theme_slug ];

		// GitHub API
		$repo     = 'shopperexpress/shopperexpress';
		$response = wp_remote_get( "https://api.github.com/repos/$repo/releases/latest" );

		if ( is_wp_error( $response ) ) {
			return $transient;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( ! $body || empty( $body->tag_name ) ) {
			return $transient;
		}

		$new_version = ltrim( $body->tag_name, 'v' );
		$zip_url     = $body->zipball_url;

		if ( version_compare( $current_version, $new_version, '<' ) ) {
			$transient->response[ $theme_slug ] = array(
				'theme'       => $theme_slug,
				'new_version' => $new_version,
				'package'     => $zip_url,
				'url'         => "https://github.com/$repo",
			);
		}

		return $transient;
	}
);

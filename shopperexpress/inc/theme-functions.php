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

/**
 * Return the configured whitelist entries that are allowed to trigger ADF *API*
 * delivery. Each entry may be either a WP Forms numeric form ID or an exact
 * form name/title — both are supported so the feature works even for webhook
 * notifications that don't (yet) pass a `form_id` body param.
 *
 * @return string[] Trimmed, non-empty whitelist entries.
 */
function wps_get_adf_tracked_form_ids(): array {
	$raw     = (string) get_option( 'adf_wpforms_form_ids', '' );
	$entries = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
	return array_values( array_unique( $entries ) );
}

/**
 * Whether a given WP Forms submission is allowed to send its lead via the ADF API.
 *
 * Matches against the whitelist by numeric form ID first (when the webhook was
 * configured to send one), falling back to a case-insensitive match on the
 * form name — which is already present in every existing webhook payload via
 * the `Form_Name` field, so the whitelist works without touching WP Forms'
 * webhook configuration.
 *
 * An empty whitelist means the feature hasn't been configured yet, so nothing is
 * restricted (preserves existing behavior until the site owner opts in).
 *
 * @param string $form_id   Raw form ID from the incoming request (may be empty).
 * @param string $form_name Form name/title from the incoming request (may be empty).
 * @return bool
 */
function wps_is_adf_tracked_form( string $form_id, string $form_name = '' ): bool {
	$tracked = wps_get_adf_tracked_form_ids();

	if ( empty( $tracked ) ) {
		return true;
	}

	if ( '' !== $form_id && in_array( $form_id, $tracked, true ) ) {
		return true;
	}

	if ( '' !== $form_name ) {
		foreach ( $tracked as $entry ) {
			if ( 0 === strcasecmp( $entry, $form_name ) ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Dispatch an ADFXML payload via the configured delivery method (email, API, or both).
 *
 * Writes a row to {prefix}_adf_lead_log regardless of outcome.
 *
 * @param string $xml    Rendered ADF template produced by wps_render_adf_template().
 * @param array  $fields Original lead fields (first_name, last_name, email, phone, form_name …).
 * @return array{success: bool, method: string, response_code: int, error_message: string}
 */
function wps_dispatch_adf( string $xml, array $fields ): array {
	global $wpdb;

	$method      = get_option( 'adf_delivery_method', 'email' );
	$first_name  = sanitize_text_field( $fields['first_name'] ?? '' );
	$last_name   = sanitize_text_field( $fields['last_name'] ?? '' );
	$email       = sanitize_email( $fields['email'] ?? '' );
	$phone       = sanitize_text_field( $fields['phone'] ?? '' );
	$form_name   = sanitize_text_field( $fields['form_name'] ?? '' );
	$lead_source = sanitize_text_field( $fields['lead_source'] ?? wp_get_referer() ?: '' );
	$site_name   = sanitize_text_field( get_option( 'adf_site_name', get_bloginfo( 'name' ) ) );

	// Duplicate prevention: block same email+phone within the configured window.
	$dedup_minutes = (int) get_option( 'adf_dedup_minutes', 0 );
	if ( $dedup_minutes > 0 && '' !== $email ) {
		$table     = $wpdb->prefix . 'adf_lead_log';
		$duplicate = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM `{$table}`
				 WHERE email = %s AND phone = %s AND status = 'success'
				   AND submitted_at >= DATE_SUB( NOW(), INTERVAL %d MINUTE )
				 LIMIT 1",
				$email,
				$phone,
				$dedup_minutes
			)
		);
		if ( $duplicate ) {
			return array(
				'success'       => false,
				'method'        => $method,
				'response_code' => 0,
				'error_message' => 'Duplicate lead skipped.',
				'log_id'        => 0,
			);
		}
	}

	$log = array(
		'submitted_at'    => current_time( 'mysql' ),
		'site_name'       => $site_name,
		'form_name'       => $form_name,
		'lead_source'     => $lead_source,
		'first_name'      => $first_name,
		'last_name'       => $last_name,
		'email'           => $email,
		'phone'           => $phone,
		'delivery_method' => $method,
		'api_endpoint'    => '',
		'response_code'   => 0,
		'response_body'   => '',
		'status'          => 'pending',
		'retry_count'     => 0,
		'error_message'   => '',
		'adfxml_payload'  => $xml,
	);

	if ( 'api' === $method || 'both' === $method ) {
		$client              = new \App\Components\Base\ADF_Api_Client();
		$log['api_endpoint'] = get_option( \App\Components\Base\ADF_Api_Client::OPTION_ENDPOINT, '' );
		$result              = $client->send( $xml, $fields );

		$log['response_code'] = $result['response_code'];
		$log['response_body'] = $result['response_body'];
		$log['error_message'] = $result['error_message'];
		$log['status']        = $result['success'] ? 'success' : 'failed';

		if ( 'both' === $method ) {
			// Email is sent unconditionally alongside the API call.
			wps_send_adf_email( $xml, $first_name, $last_name );
		} elseif ( ! $result['success'] && get_option( 'adf_api_fallback_email', 0 ) ) {
			// API-only mode: fallback to email on failure.
			wps_send_adf_email( $xml, $first_name, $last_name );
		}

		if ( ! $result['success'] ) {
			wps_adf_notify_admin_failure( $log );
		}
	} else {
		wps_send_adf_email( $xml, $first_name, $last_name );
		$log['status'] = 'success';
		$result        = array(
			'success'       => true,
			'response_code' => 0,
			'error_message' => '',
		);
	}

	$wpdb->insert(
		$wpdb->prefix . 'adf_lead_log',
		$log,
		array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s' )
	);

	return array(
		'success'       => 'success' === $log['status'],
		'method'        => $method,
		'response_code' => $log['response_code'],
		'error_message' => $log['error_message'],
		'log_id'        => $wpdb->insert_id,
	);
}

/**
 * Send admin notification when ADF API delivery fails.
 *
 * @param array $log The log row (without id).
 * @return void
 */
function wps_adf_notify_admin_failure( array $log ): void {
	if ( ! get_option( 'adf_notify_admin_on_failure', 0 ) ) {
		return;
	}

	$to = sanitize_email( get_option( 'adf_notify_email', get_option( 'admin_email' ) ) );
	if ( ! is_email( $to ) ) {
		return;
	}

	$subject = sprintf( '[%s] ADF Lead Delivery Failed — %s %s', get_bloginfo( 'name' ), $log['first_name'], $log['last_name'] );

	$body = "A lead failed to deliver via API.\n\n"
		. "Name:     {$log['first_name']} {$log['last_name']}\n"
		. "Email:    {$log['email']}\n"
		. "Phone:    {$log['phone']}\n"
		. "Form:     {$log['form_name']}\n"
		. "Time:     {$log['submitted_at']}\n"
		. "Endpoint: {$log['api_endpoint']}\n"
		. "HTTP Code:{$log['response_code']}\n"
		. "Error:    {$log['error_message']}\n\n"
		. 'Review in the Operation Center → Lead Delivery panel: ' . admin_url( 'admin.php?page=soc-lead-delivery' );

	wp_mail( $to, $subject, $body );
}

/**
 * Send ADFXML by email using WordPress mail.
 *
 * @param string $xml        ADFXML payload string.
 * @param string $first_name Lead first name (used in subject line).
 * @param string $last_name  Lead last name (used in subject line).
 * @return void
 */
function wps_send_adf_email( string $xml, string $first_name, string $last_name ): void {
	$mail_to  = array();
	$mail_cc  = array();
	$mail_bcc = array();

	while ( have_rows( 'email_notification', 'options' ) ) :
		the_row();
		$addr = sanitize_email( get_sub_field( 'email' ) );
		if ( is_email( $addr ) ) {
			$mail_to[] = $addr;
		}
	endwhile;

	while ( have_rows( 'email_notification_cc', 'options' ) ) :
		the_row();
		$addr = sanitize_email( get_sub_field( 'email' ) );
		if ( is_email( $addr ) ) {
			$mail_cc[] = $addr;
		}
	endwhile;

	while ( have_rows( 'email_notification_bcc', 'options' ) ) :
		the_row();
		$addr = sanitize_email( get_sub_field( 'email' ) );
		if ( is_email( $addr ) ) {
			$mail_bcc[] = $addr;
		}
	endwhile;

	if ( empty( $mail_to ) ) {
		return;
	}

	$headers = array( 'Content-Type: text/html; charset=utf-8' );

	if ( ! empty( $mail_cc ) ) {
		$headers[] = 'Cc: ' . implode( ', ', $mail_cc );
	}
	if ( ! empty( $mail_bcc ) ) {
		$headers[] = 'Bcc: ' . implode( ', ', $mail_bcc );
	}

	wp_mail(
		implode( ', ', $mail_to ),
		'intice360: ' . $first_name . ' ' . $last_name,
		$xml,
		$headers
	);
}

/**
 * Backward-compatible wrapper — renders the configured ADF template and dispatches
 * via the configured delivery method. No-op when no `adf_templates` row exists.
 *
 * @param array $fields Lead fields: first_name, last_name, email, phone, comments, zip.
 * @return void
 */
function adf_email( $fields = array() ) {
	$template_row = wps_resolve_adf_template();

	if ( null === $template_row ) {
		return;
	}

	$xml = wps_render_adf_template( $template_row['template'], $fields );

	wps_dispatch_adf( $xml, $fields );
}

/**
 * Substitute {{token}} placeholders (and {{date}}) into an ADF template string.
 * Same substitution rules as the `adf_action()` ajax handler, factored out so
 * other callers can reuse an `adf_templates` row.
 *
 * @param string $template Raw template string (subject or XML body) containing {{token}} placeholders.
 * @param array  $tokens   Flat key => scalar value map. Keys are matched without the surrounding {{ }}.
 * @return string
 */
function wps_render_adf_template( string $template, array $tokens ): string {
	$rendered = str_replace( '{{date}}', gmdate( 'm-d-Y' ), $template );

	foreach ( $tokens as $key => $value ) {
		$rendered = str_replace( '{{' . $key . '}}', is_scalar( $value ) ? (string) $value : '', $rendered );
	}

	return $rendered;
}

/**
 * Find an `adf_templates` (Theme Options) row by its "Template Name" sub-field.
 *
 * @param string $name Exact template_name value to match (case-insensitive).
 * @return array{subject: string, template: string}|null Null when not found or $name is empty.
 */
function wps_get_adf_template_by_name( string $name ): ?array {
	$name = trim( $name );
	if ( '' === $name ) {
		return null;
	}

	$found = null;

	while ( have_rows( 'adf_templates', 'options' ) ) :
		the_row();
		if ( 0 === strcasecmp( trim( (string) get_sub_field( 'template_name' ) ), $name ) ) {
			$found = array(
				'subject'  => (string) get_sub_field( 'subject' ),
				'template' => (string) get_sub_field( 'template' ),
			);
			break;
		}
	endwhile;

	return $found;
}

/**
 * Resolve which `adf_templates` (Theme Options) row a lead should render through.
 *
 * Looks up an exact named template first, then falls back to the first
 * configured row so callers that don't have (or care about) a specific
 * template name still work. Returns null when the `adf_templates` repeater
 * has no rows at all — callers must treat that as "nothing to send" rather
 * than falling back to a hardcoded structure.
 *
 * @param string $template_name Optional exact template_name to look up first.
 * @return array{subject: string, template: string}|null
 */
function wps_resolve_adf_template( string $template_name = '' ): ?array {
	if ( '' !== $template_name ) {
		$named = wps_get_adf_template_by_name( $template_name );
		if ( null !== $named ) {
			return $named;
		}
	}

	$default = null;

	while ( have_rows( 'adf_templates', 'options' ) ) :
		the_row();
		$default = array(
			'subject'  => (string) get_sub_field( 'subject' ),
			'template' => (string) get_sub_field( 'template' ),
		);
		break;
	endwhile;

	return $default;
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

/**
 * WordPress' own logged-in cookie is HttpOnly, so front-end JS can't read it to fix
 * the "logged-in" body class / payment-list auth state on cached pages. Mirror it in
 * a plain, non-HttpOnly cookie that carries no privileges by itself — it's only a
 * client-readable signal, every real auth check still goes through WordPress.
 */
function wps_sync_client_auth_cookie( $expire = 0 ) {
	$expire = $expire ?: time() + 2 * DAY_IN_SECONDS;
	setcookie( 'wps_logged_in', '1', $expire, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), false );
	$_COOKIE['wps_logged_in'] = '1';
}

function wps_clear_client_auth_cookie() {
	setcookie( 'wps_logged_in', '', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), false );
	unset( $_COOKIE['wps_logged_in'] );
}

add_action(
	'set_logged_in_cookie',
	function ( $logged_in_cookie, $expire ) {
		wps_sync_client_auth_cookie( $expire );
	},
	10,
	2
);

add_action( 'clear_auth_cookie', 'wps_clear_client_auth_cookie' );

// Backfill for sessions that logged in before this cookie existed, so it's applied
// on the next request that actually reaches PHP without waiting for a fresh login.
add_action(
	'init',
	function () {
		$flag_present = ! empty( $_COOKIE['wps_logged_in'] );
		$is_authed    = is_user_logged_in();

		if ( $is_authed && ! $flag_present ) {
			wps_sync_client_auth_cookie();
		} elseif ( ! $is_authed && $flag_present ) {
			wps_clear_client_auth_cookie();
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
function get_vehicle_spin( string $vin ): string|false {
	if ( empty( $vin ) ) {
		return false;
	}

	$index = get_transient( 'wps_360_spin_index' );

	if ( false === $index ) {
		$csv_url  = get_field( 'url_for_csv_360', 'options' );
		$response = wp_remote_get( $csv_url, array( 'timeout' => 10 ) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$index = array();
		$rows  = array_map( 'str_getcsv', explode( "\n", wp_remote_retrieve_body( $response ) ) );

		foreach ( $rows as $row ) {
			if ( isset( $row[1], $row[2] ) ) {
				$index[ trim( $row[1] ) ] = trim( $row[2] );
			}
		}

		set_transient( 'wps_360_spin_index', $index, 6 * HOUR_IN_SECONDS );
	}

	if ( ! isset( $index[ $vin ] ) || empty( $index[ $vin ] ) ) {
		return false;
	}

	$link = preg_replace( '#/NLP\??#', '/NLP', $index[ $vin ] );
	return str_replace( 'NLPvehicle_fkey', 'NLP/?vehicle_fkey', $link );
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
function default_image( string $class = null, string $post_type = null, $alt = array() ): string {
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

	$alt = ! empty( $alt ) ? implode( ' ', $alt ) : esc_html__( 'Image description', 'shopperexpress' );

	// Generate and return the HTML markup for the image
	return '<div' . $class_attr . '><img src="' . esc_url( $def_img ) . '" alt="' . esc_attr( $alt ) . '" class="img-fluid"></div>';
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
			$url = str_replace( '[' . $field . ']', (string) ( get_field( $field, $post_id ) ?? '' ), $url );
		}
	}

	return $url;
}

/**
 * Fetch the full Nexus vehicle list for a given condition, memoized per request.
 *
 * The model-slider (tabs-slider.php) renders one slide per model and calls
 * get_listings_count() for each — without this, every slide would issue its own
 * Nexus API request. Memoizing per condition means at most one request for "new"
 * and one for "used" per pageload, reusing whatever Intice_Api_Client::get_vehicles()
 * already has cached for the SRP grid.
 *
 * @param string|null $condition
 * @return array
 */
function get_api_vehicles_by_condition( $condition ) {
	static $cache = array();

	$key = (string) $condition;

	if ( ! array_key_exists( $key, $cache ) ) {
		$result        = \App\Components\Api\Intice_Api_Client::instance()->get_vehicles( array_filter( array( 'condition' => $condition ) ) );
		$cache[ $key ] = is_wp_error( $result ) ? array() : ( $result['data'] ?? array() );
	}

	return $cache[ $key ];
}

function get_listings_count( $year, $make, $model, $condition, $trim, $index, $row ) {
	$count = get_transient( 'acf-count-' . $index . $row );
	if ( ! empty( $_REQUEST['clear'] ) ) {
		$count = false;
	}
	if ( false === $count ) {
		if ( \App\is_api_mode() ) {
			// Slides don't carry a post_type — infer it the same way the SRP endpoints
			// split new vs. used, so the right "API Settings → Filters" exclusion rules apply.
			$condition_norm = $condition ? strtolower( (string) $condition ) : '';
			$post_type      = in_array( $condition_norm, array( 'used', 'certified' ), true ) ? 'used-listings' : 'listings';

			// Reuses the exact same client call (and transient cache key) the SRP grid
			// warms on page load — one shared fetch per condition instead of a fresh
			// Nexus request per model-slider slide.
			$vehicles = get_api_vehicles_by_condition( $condition ?: null );

			$rows = array_filter(
				array(
					$year  ? array( 'field' => 'year', 'custom_key' => '', 'operator' => '=', 'value' => $year ) : null,
					$make  ? array( 'field' => 'make', 'custom_key' => '', 'operator' => '=', 'value' => $make ) : null,
					$model ? array( 'field' => 'model', 'custom_key' => '', 'operator' => '=', 'value' => $model ) : null,
					$trim  ? array( 'field' => 'trim', 'custom_key' => '', 'operator' => '=', 'value' => $trim ) : null,
				)
			);

			// Apply the same "API Settings → Filters" exclusion rules the SRP grid uses,
			// so this badge never shows a number the shop-section grid doesn't actually display.
			$rows = array_merge( $rows, \App\Components\SOC\Modules\Api_Settings::get_active_filters( $post_type ) );

			$matching = array_filter(
				$vehicles,
				function ( $vehicle ) use ( $rows ) {
					foreach ( $rows as $rule ) {
						if ( ! \App\Components\Api\Intice_Rest::filter_row_matches( $vehicle, $rule ) ) {
							return false;
						}
					}
					return true;
				}
			);

			$count = count( $matching );

			set_transient( 'acf-count-' . $index . $row, $count, 12 * HOUR_IN_SECONDS );

			return $count;
		}

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

		if ( $trim ) {
			$meta_query[] = array(
				'key'     => 'trim',
				'value'   => $trim,
				'compare' => '=',
			);
		}

		$args = array(
			'post_type'      => 'listings',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => 1,
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

		if ( ! have_rows( 'after_import', 'options' ) ) {
			return;
		}

		$need_clear_cache = false;

		while ( have_rows( 'after_import', 'options' ) ) {

			the_row();

			$row_import_id = (int) get_sub_field( 'import_id' );

			if ( $row_import_id !== (int) $import_id ) {
				continue;
			}

			$need_clear_cache = true;

			$use_external_url = (bool) get_sub_field( 'use' );

			if ( 1 === $use_external_url ) {

				$action_url = trim( (string) get_sub_field( 'action_url' ) );

				if ( ! empty( $action_url ) ) {

					wp_remote_get(
						esc_url_raw( $action_url ),
						array(
							'timeout'  => 5,
							'blocking' => false,
						)
					);
				}
			} else {

				$post_type = sanitize_key( get_sub_field( 'post_type' ) );

				if ( ! empty( $post_type ) ) {

					$command = sprintf(
						'wp api clear %s --clear=true > /dev/null 2>&1 &',
						escapeshellarg( $post_type )
					);

					if ( function_exists( 'exec' ) && ! in_array( 'exec', array_map( 'trim', explode( ',', (string) ini_get( 'disable_functions' ) ) ), true ) ) {
						exec( $command );
					}
				}
			}
		}

		if ( $need_clear_cache && function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
	}
);

/**
 * Call API function.
 *
 * This function is used to make asynchronous calls to external APIs. It supports both GET and POST requests.
 *
 * @param string $url The URL to which the request is made.
 * @param array  $data The data to be sent in the request.
 * @param int    $post_id The ID of the post associated with the request.
 *
 * @return void
 */
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

/**
 * Redirect to the correct single listing page based on VIN and post type in the URL.
 *
 * Checks for a 'vin' parameter in the request. If present, and the current request URI matches
 * '/listings/' or '/used-listings/', attempts to find the corresponding post by VIN and performs a 301 redirect.
 * Used to ensure deep links by VIN always point to the canonical vehicle page.
 *
 * @return void
 */
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
		wp_reset_postdata();
		wp_safe_redirect( $redirect_url, 301 );
		exit;
	} else {
		return;
	}
}

/**
 * Custom Yoast breadcrumbs as ordered list.
 *
 * This function overrides the default Yoast breadcrumbs to output them as an ordered list.
 * It checks if the Yoast breadcrumb function exists, and if so, it captures the breadcrumbs
 * into a buffer, cleans it, and then uses regular expressions to extract the breadcrumb items.
 * The items are then outputted as an ordered list with anchor tags for navigation.
 *
 * @return void
 */
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

			echo '<ol class="breadcrumbs">';
			foreach ( $items as $index => $item ) {
				if ( $index === array_key_last( $items ) && str_starts_with( $item, '<span' ) ) {
					preg_match( '/<span.*?>(.*?)<\/span>/', $item, $text_match );
					// Yoast's breadcrumb title field stores raw text (may include our
					// dynamic [sc_*] shortcodes, e.g. dealership name/city) — run it
					// through do_shortcode() instead of just escaping it as plain text.
					$label = do_shortcode( $text_match[1] ?? '—' );
					echo '<li><a href="#">' . $label . '</a></li>';
				} else {
					echo '<li>' . do_shortcode( $item ) . '</li>';
				}
			}
			echo '</ol>';
		}
	}
}

/**
 * Display SVG icon.
 *
 * This function displays an SVG icon. It checks if the provided SVG code is not empty,
 * and if it doesn't contain the 'aria-hidden' attribute, it adds it. If the $display
 * parameter is true, it echoes the SVG code; otherwise, it returns it.
 *
 * @param string $svg_code The SVG code to display.
 * @param bool   $display Whether to display the SVG code (default: true).
 *
 * @return string|void Returns the SVG code if $display is false, otherwise void.
 */
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
 * Render an ACF image field value as inline SVG (when the file is an SVG)
 * or as a regular <img> tag (any other mime type). Alt text is read from
 * the image field itself.
 *
 * @param array  $image   ACF image field value (array format with 'ID', 'url', 'alt', 'mime_type').
 * @param string $classes Space-separated CSS classes for the <img>/<svg> element.
 *
 * @return void
 */
function render_acf_image_icon( $image, $classes = '' ) {

	if ( empty( $image['url'] ) ) {
		return;
	}

	if ( 'image/svg+xml' === ( $image['mime_type'] ?? '' ) ) {
		$svg_path = ! empty( $image['ID'] ) ? get_attached_file( $image['ID'] ) : '';
		$svg_code = $svg_path && file_exists( $svg_path ) ? file_get_contents( $svg_path ) : '';

		if ( $svg_code ) {
			if ( $classes && ! preg_match( '/class=/', $svg_code ) ) {
				$svg_code = preg_replace( '/<svg([^>]*)>/', '<svg$1 class="' . esc_attr( $classes ) . '">', $svg_code, 1 );
			}
			display_svg_icon( $svg_code );
			return;
		}
	}

	printf(
		'<img class="%1$s" src="%2$s" alt="%3$s">',
		esc_attr( $classes ),
		esc_url( $image['url'] ),
		esc_attr( $image['alt'] ?? '' )
	);
}

/**
 * ACF SVG filter to allow raw SVG code.
 *
 * https://www.advancedcustomfields.com/resources/html-escaping/
 */
add_filter( 'wp_kses_allowed_html', 'acf_add_allowed_svg_tag', 10, 2 );

/**
 * Add SVG tag to allowed HTML tags.
 *
 * @param array  $tags Array of allowed HTML tags.
 * @param string $context The context for which the tags are being filtered.
 *
 * @return array The modified array of allowed HTML tags.
 */
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

/**
 * Get JSON data from URL.
 *
 * This function retrieves JSON data from a specified URL using wp_remote_get.
 * It handles errors and returns the decoded JSON data.
 *
 * @param string $url The URL to fetch JSON data from.
 *
 * @return array|object|void The decoded JSON data, or void if an error occurs.
 */
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

/**
 * Check if the current user has access.
 *
 * This function determines whether the current user has access based on their roles.
 * It checks if the user is logged in and has any of the specified allowed roles.
 *
 * @return bool Returns true if the user has access, false otherwise.
 */
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

/**
 * Enqueue ACF scripts and styles.
 *
 * This function enqueues the Advanced Custom Fields (ACF) scripts and styles
 * if the current post is a singular 'listings' post. It checks if ACF is
 * available and enqueues the necessary scripts and styles accordingly.
 *
 * @return void
 */

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

/**
 * Get backup images for a given VIN number.
 *
 * This function retrieves backup images associated with a specific VIN number
 * from the database. It checks if the VIN number exists in the backup image
 * table and returns the associated images. If no images are found, it returns
 * the default image URL.
 *
 * @param string $vin_number The VIN number to retrieve backup images for.
 *
 * @return array An array of image URLs associated with the VIN number.
 */
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

/**
 * Set backup images for a given VIN number.
 *
 * This function sets backup images associated with a specific VIN number in the
 * database. It takes an array of image URLs and stores them as a comma-separated
 * string in the backup image table. If the VIN number already exists, the
 * existing record is updated; otherwise, a new record is inserted.
 *
 * @param string $vin_number The VIN number to set backup images for.
 * @param array  $gallery An array of image URLs to store as backup images.
 *
 * @return bool Returns true if the backup images are successfully set, false otherwise.
 */
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

/**
 * Remove header and footer scripts.
 *
 * This function removes the header and footer scripts from the WordPress
 * theme. It iterates through the registered actions and removes any instances
 * of the HeaderAndFooterScripts class with the 'wp_head' or 'wp_footer'
 * action.
 *
 * @return void
 */

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

/**
 * Add custom header script.
 *
 * This function adds a custom header script to the WordPress theme. It is
 * executed on the 'wp_head' action and checks if the current post is a singular
 * post. If it is, it retrieves the custom header script from the post meta
 * and echoes it.
 *
 * @return void
 */

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
		'text_color'    => 'color',
		'font_size'     => 'font-size',
		'font_weight'   => 'font-weight',
		'font_family'   => 'font-family',
		'margin_bottom' => 'margin-bottom',
	);

	foreach ( $map as $field => $css_property ) {
		if ( ! isset( $style[ $field ] ) || '' === $style[ $field ] ) {
			continue;
		}

		$value = esc_attr( $style[ $field ] );

		if ( 'font_size' === $field || 'margin_bottom' === $field ) {
			$value .= 'px';
		}

		$styles[] = "{$css_property}: {$value};";
	}

	return $styles ? ' style="' . implode( ' ', $styles ) . '"' : '';
}

/**
 * Get step button styles.
 *
 * @return array
 */
function wps_step_button_styles(): array {
	static $styles = array();
	return $styles;
}

/**
 * Add step button style.
 *
 * @param string $css CSS.
 */
function wps_add_step_button_style( string $css ): void {
	static $styles = array();

	$styles[] = $css;

	add_action(
		'wp_footer',
		function () use ( &$styles ) {
			if ( empty( $styles ) ) {
				return;
			}

			echo '<style id="wps-step-btn-hover">' . implode( "\n", array_unique( $styles ) ) . '</style>';
		},
		5
	);
}

/**
 * Universal button renderer with unique ID + inline hover styles
 *
 * @param array $btn Button data.
 * @param array $args Button args.
 */
function render_step_button( array $btn, array $args = array() ) {

	static $i = 0;
	++$i;

	$id    = 'step-btn-' . $i . '-' . wp_generate_password( 4, false, false );
	$title = ! empty( $btn['title'] ) ? $btn['title'] : ( $args['default_title'] ?? '' );
	$class = ! empty( $args['class'] ) ? $args['class'] : '';
	$attrs = ! empty( $args['attrs'] ) ? $args['attrs'] : '';

	$styles = array();

	if ( ! empty( $btn['font_size'] ) ) {
		$styles[] = 'font-size:' . esc_attr( $btn['font_size'] ) . 'px';
	}
	if ( ! empty( $btn['background_color'] ) ) {
		$styles[] = 'background-color:' . esc_attr( $btn['background_color'] );
	}
	if ( ! empty( $btn['text_color'] ) ) {
		$styles[] = 'color:' . esc_attr( $btn['text_color'] );
	}
	if ( ! empty( $btn['font_family'] ) ) {
		$styles[] = 'font-family:' . esc_attr( $btn['font_family'] );
	}
	if ( ! empty( $btn['font_weight'] ) ) {
		$styles[] = 'font-weight:' . esc_attr( $btn['font_weight'] );
	}

	$style_attr = ! empty( $styles ) ? 'style="' . implode( ';', $styles ) . '"' : '';

	$hover_css = '';

	if ( ! empty( $btn['background_color_hover'] ) || ! empty( $btn['text_color_hover'] ) ) {
		$hover_css .= '#' . $id . ':hover{';

		if ( ! empty( $btn['background_color_hover'] ) ) {
			$hover_css .= 'background-color:' . esc_attr( $btn['background_color_hover'] ) . ' !important;';
		}
		if ( ! empty( $btn['text_color_hover'] ) ) {
			$hover_css .= 'color:' . esc_attr( $btn['text_color_hover'] ) . ' !important;';
		}

		$hover_css .= '}';
	}

	if ( $hover_css ) {
		wps_add_step_button_style( $hover_css );
	}

	printf(
		'<button id="%1$s" type="button" class="%2$s" %3$s %4$s>%5$s</button>',
		esc_attr( $id ),
		esc_attr( $class ),
		$attrs,
		$style_attr,
		esc_html( $title )
	);
}

/**
 * Resolve the dealer identifier used to build shared ASC conversion event IDs.
 *
 * Falls back to the ASC Store Name when no dedicated OpenAI dealer ID is set,
 * matching the ASC settings already surfaced on the ASC Settings options page.
 *
 * @return string
 */
function wps_asc_dealer_id(): string {
	$dealer_id = trim( (string) get_option( 'openai_ads_dealer_id', '' ) );

	if ( '' !== $dealer_id ) {
		return sanitize_title( $dealer_id );
	}

	$store_name = function_exists( 'get_field' ) ? (string) get_field( 'asc_store_name', 'option' ) : '';

	return '' !== $store_name ? sanitize_title( $store_name ) : 'unknown';
}

/**
 * Generate a shared conversion event ID for the ASC event system.
 *
 * Format: asc_<dealer-id>_<event-name>_<uuid>
 *
 * The same ID is used across the ASC event record, the OpenAI browser pixel,
 * the future OpenAI Conversions API call, and internal logs so that browser
 * and server conversions can be deduplicated.
 *
 * @param string $event_name ASC event name (e.g. asc_form_submission_sales).
 * @return string
 */
function wps_generate_asc_event_id( string $event_name ): string {
	return sprintf(
		'asc_%s_%s_%s',
		wps_asc_dealer_id(),
		sanitize_key( $event_name ),
		wp_generate_uuid4()
	);
}

<?php
// Default theme settings
/*
//Staging restrictions
if ( file_exists( sys_get_temp_dir() . '/staging-restrictions.php' ) ) {
	define( 'STAGING_RESTRICTIONS', true );
	require_once sys_get_temp_dir() . '/staging-restrictions.php';
}
*/
function seo_warning() {
	if( get_option( 'blog_public' ) ) return;
	
	$message = __( 'You are blocking access to robots. You must go to your <a href="%s">Reading</a> settings and uncheck the box for Search Engine Visibility.', 'shopperexpress' );

	echo '<div class="error"><p>';
	printf( $message, admin_url( 'options-reading.php' ) );
	echo '</p></div>';
}
add_action( 'admin_notices', 'seo_warning' );

function theme_disable_cheks() {
	$disabled_checks = array( 'TagCheck', 'Plugin_Territory', 'CustomCheck', 'EditorStyleCheck' );
	global $themechecks;
	foreach ( $themechecks as $key => $check ) {
		if ( is_object( $check ) && in_array( get_class( $check ), $disabled_checks ) ) {
			unset( $themechecks[$key] );
		}
	}
}
add_action( 'themecheck_checks_loaded', 'theme_disable_cheks' );

add_theme_support( 'automatic-feed-links' );

/* Custom logo support. Uncomment or delete on production
function theme_add_logo_support() {
	add_theme_support( 'custom-logo' );
}
add_action( 'after_setup_theme', 'theme_add_logo_support' );
*/

if ( !isset( $content_width ) ) {
	$content_width = 900;
}

remove_action( 'wp_head', 'wp_generator' );

function theme_localization () {
	load_theme_textdomain( 'shopperexpress', get_template_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'theme_localization' );

/*
 * Let WordPress manage the document title.
 * By adding theme support, we declare that this theme does not use a
 * hard-coded <title> tag in the document head, and expect WordPress to
 * provide it for us.
 */
add_theme_support( 'title-tag' );

//Add [email]...[/email] shortcode
function shortcode_email( $atts, $content ) {
	return antispambot( $content );
}
add_shortcode( 'email', 'shortcode_email' );

//Replace standard wp menu classes
function change_menu_classes( $css_classes ) {
	return str_replace( array( 'current-menu-item', 'current-menu-parent', 'current-menu-ancestor' ), 'active', $css_classes );
}
add_filter( 'nav_menu_css_class', 'change_menu_classes' );

//Allow tags in category description
$filters = array( 'pre_term_description', 'pre_link_description', 'pre_link_notes', 'pre_user_description' );
foreach ( $filters as $filter ) {
	remove_filter( $filter, 'wp_filter_kses' );
}

function clean_phone( $phone ){
    return preg_replace( '/[^0-9]/', '', $phone );
}

//Make wp admin menu html valid
function wp_admin_bar_valid_search_menu( $wp_admin_bar ) {
	if ( is_admin() )
		return;

	$form  = '<form action="' . esc_url( home_url( '/' ) ) . '" method="get" id="adminbarsearch"><div>';
	$form .= '<input class="adminbar-input" name="s" id="adminbar-search" tabindex="10" type="text" value="" maxlength="150" />';
	$form .= '<input type="submit" class="adminbar-button" value="' . __( 'Search', 'shopperexpress' ) . '"/>';
	$form .= '</div></form>';

	$wp_admin_bar->add_menu( array(
		'parent' => 'top-secondary',
		'id'     => 'search',
		'title'  => $form,
		'meta'   => array(
			'class'    => 'admin-bar-search',
			'tabindex' => -1,
		)
	) );
}

function fix_admin_menu_search() {
	remove_action( 'admin_bar_menu', 'wp_admin_bar_search_menu', 4 );
	add_action( 'admin_bar_menu', 'wp_admin_bar_valid_search_menu', 4 );
}
add_action( 'add_admin_bar_menus', 'fix_admin_menu_search' );

//Disable comments on pages by default
function theme_page_comment_status( $post_ID, $post, $update ) {
	if ( !$update ) {
		remove_action( 'save_post_page', 'theme_page_comment_status', 10 );
		wp_update_post( array(
			'ID' => $post->ID,
			'comment_status' => 'closed',
		) );
		add_action( 'save_post_page', 'theme_page_comment_status', 10, 3 );
	}
}
add_action( 'save_post_page', 'theme_page_comment_status', 10, 3 );

//custom excerpt
function theme_the_excerpt() {
	global $post;
	
	if ( trim( $post->post_excerpt ) ) {
		the_excerpt();
	} elseif ( strpos( $post->post_content, '<!--more-->' ) !== false ) {
		the_content();
	} else {
		the_excerpt();
	}
}

//theme password form
function theme_get_the_password_form() {
	global $post;
	$post = get_post( $post );
	$label = 'pwbox-' . ( empty($post->ID) ? rand() : $post->ID );
	$output = '<form action="' . esc_url( site_url( 'wp-login.php?action=postpass', 'login_post' ) ) . '" class="post-password-form" method="post">
	<p>' . __( 'This content is password protected. To view it please enter your password below:', 'shopperexpress' ) . '</p>
	<p><label for="' . $label . '">' . __( 'Password:', 'shopperexpress' ) . '</label> <input name="post_password" id="' . $label . '" type="password" size="20" /> <input type="submit" name="Submit" value="' . esc_attr__( 'Submit', 'shopperexpress' ) . '" /></p></form>
	';
	return $output;
}
add_filter( 'the_password_form', 'theme_get_the_password_form' );

function basetheme_options_capability(){
	$role = get_role( 'administrator' );
	$role->add_cap( 'theme_options_view' );
}
add_action( 'admin_init', 'basetheme_options_capability' );

//theme options tab in appearance
if( function_exists( 'acf_add_options_sub_page' ) && current_user_can( 'theme_options_view' ) ) {
	// add parent
	$parent = acf_add_options_page(array(
		'page_title' 	=> 'Theme General Options',
		'menu_title' 	=> 'Theme Options',
		'redirect' 		=> false
	));

	$child = acf_add_options_sub_page(array(
            'page_title'  => __('VDP Options'),
            'menu_title'  => __('VDP Options'),
            'parent_slug' => $parent['menu_slug'],
        ));
	$child = acf_add_options_sub_page(array(
		'page_title'  => __('Special Offers'),
		'menu_title'  => __('Special Offers'),
		'parent_slug' => $parent['menu_slug'],
	));
}

//acf theme functions placeholders
if( !class_exists( 'acf' ) && !is_admin() ) {
	function get_field_reference( $field_name, $post_id ) { return ''; }
	function get_field_objects( $post_id = false, $options = array() ) { return false; }
	function get_fields( $post_id = false ) { return false; }
	function get_field( $field_key, $post_id = false, $format_value = true )  { return false; }
	function get_field_object( $field_key, $post_id = false, $options = array() ) { return false; }
	function the_field( $field_name, $post_id = false ) {}
	function have_rows( $field_name, $post_id = false ) { return false; }
	function the_row() {}
	function reset_rows( $hard_reset = false ) {}
	function has_sub_field( $field_name, $post_id = false ) { return false; }
	function get_sub_field( $field_name ) { return false; }
	function the_sub_field( $field_name ) {}
	function get_sub_field_object( $child_name ) { return false;}
	function acf_get_child_field_from_parent_field( $child_name, $parent ) { return false; }
	function register_field_group( $array ) {}
	function get_row_layout() { return false; }
	function acf_form_head() {}
	function acf_form( $options = array() ) {}
	function update_field( $field_key, $value, $post_id = false ) { return false; }
	function delete_field( $field_name, $post_id ) {}
	function create_field( $field ) {}
	function reset_the_repeater_field() {}
	function the_repeater_field( $field_name, $post_id = false ) { return false; }
	function the_flexible_field( $field_name, $post_id = false ) { return false; }
	function acf_filter_post_id( $post_id ) { return $post_id; }
}

// date archive link
add_action( 'admin_init',
	function (){
		add_settings_section(
			'eg_setting_section',
			__( 'Date archive link', 'shopperexpress' ),
			function () {},
			'reading'
		);
	
		add_settings_field(
			'eg_setting_name',
			__( 'Type', 'shopperexpress' ),
			'eg_setting_callback_function',
			'reading',
			'eg_setting_section'
		);
	
		register_setting( 'reading', 'eg_date_archive_link_type' );
	}
);

function eg_setting_callback_function(){
	if ( get_option( 'eg_date_archive_link_type' ) ) $type = get_option( 'eg_date_archive_link_type' );
	else $type = "month";
	echo '
	 <select name="eg_date_archive_link_type">
		 <option ' . selected( $type, 'day', false ) . ' value="day">' . __( 'Day', 'shopperexpress' ).'</option>
		 <option ' . selected( $type, 'month', false ) . ' value="month">' . __( 'Month', 'shopperexpress' ).'</option>
		 <option ' . selected( $type, 'year', false ) . ' value="year">' . __( 'Year', 'shopperexpress' ).'</option>
	 </select>
	';
}

function get_date_archive_link(){
	if ( get_option( 'eg_date_archive_link_type' ) == "year" ){
		$res = get_year_link( get_the_date( "Y" ) );
	}
	elseif ( get_option( 'eg_date_archive_link_type' ) == "day" ){
		$res = get_day_link( get_the_date( "Y" ), get_the_date( "m" ), get_the_date( "d" ) );
	}
	else {
		$res = get_month_link( get_the_date( "Y" ), get_the_date( "m" ) );
	}
	return $res;
}
 
function defer_js( $tag, $handle, $src ){
	if( ! is_admin() )
		$tag = str_replace( ' src=', ' defer src=', $tag );
		
	return $tag;
}
# commented block below, because there may be errors with js, if need you can uncomment this block
// add_filter( 'script_loader_tag', 'defer_js', 99, 3 );

remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 );
add_filter('json_enabled', '__return_false');
add_filter('json_jsonp_enabled', '__return_false');
remove_action('wp_head', 'rsd_link');

add_filter( 'upload_mimes', function( $t, $user ) {
                $t['svg'] = 'image/svg+xml';
                return $t;
        }, 10, 2 );

add_filter( 'getimagesize_mimes_to_exts', function( $types ) {
                $types['application/octet-stream'] = 'svg';
                return $types;
        } );
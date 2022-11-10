<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// Default settings
include( get_template_directory() . '/inc/default.php' );

// Theme functions
include( get_template_directory() . '/inc/theme_functions.php' );

// Custom Menu Walker
include( get_template_directory() . '/inc/classes.php' );

// Custom Widgets
include( get_template_directory() . '/inc/widgets.php' );

// Theme sidebars
include( get_template_directory() . '/inc/sidebars.php' );

include( get_template_directory() . '/inc/ajax.php' );

// Theme css & js
include( get_template_directory() . '/inc/scripts.php' );

include( get_template_directory() . '/inc/cpt.php' );

include( get_template_directory() . '/inc/cron.php' );

flush_rewrite_rules();

add_action( 'wp', function () {
	global $wp_query;

	if( !get_the_id() && !is_archive() && !is_admin() ) {
		header("HTTP/1.1 301 Moved Permanently"); 
		header("Location: /" . $wp_query->query['post_type']);
	}
});
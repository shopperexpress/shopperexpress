<?php
// Theme css & js

function shopperexpress_scripts_styles() {

	if ( $video_id = get_field( 'video_id', 'options' ) ) {
		wp_enqueue_script( 'shopperexpress-wistia', 'https://fast.wistia.com/embed/medias/' . $video_id . '.jsonp', array( 'jquery' ), '', false );
	}

	wp_deregister_style('listing_shortcodes');
	wp_deregister_style('listing_style');
	wp_deregister_style('listing_mobile');
	wp_deregister_style('bootstrap');
	
	wp_enqueue_script( 'shopperexpress-external', 'https://fast.wistia.com/assets/external/E-v1.js', array( 'jquery' ), '', false );
	wp_enqueue_script( 'shopperexpress-popper', 'https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js', array( 'jquery' ), '', false );
	wp_enqueue_script( 'shopperexpress-bootstrap-js', get_template_directory_uri() . '/js/bootstrap.min.js', array( 'jquery' ), '', false );
	wp_enqueue_script( 'shopperexpress-jquery', get_template_directory_uri() . '/js/jquery.main.js', array( 'jquery' ), '', false );
	wp_enqueue_script( 'shopperexpress-impl', get_template_directory_uri() . '/js/impl.js', array( 'jquery' ), '', false );
	wp_localize_script( 'shopperexpress-jquery', 'ajax', array( 'admin' =>  admin_url( 'admin-ajax.php' ) ) );

	// Loads our main stylesheet.
	wp_enqueue_style( 'shopperexpress-fonts_material', 'https://fonts.googleapis.com/css2?family=Material+Icons', array() );
	wp_enqueue_style( 'shopperexpress-fonts-Montserrat', 'https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Roboto:wght@400;500&display=swap', array() );
	wp_enqueue_style( 'shopperexpress-fonts-Roboto', 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap', array() );
	wp_enqueue_style( 'shopperexpress-bootstrap_css', get_template_directory_uri() . '/css/bootstrap.css', array() );
	wp_enqueue_style( 'shopperexpress-main_css', get_template_directory_uri() . '/css/main.css', array() );
	wp_enqueue_style( 'shopperexpress-style', get_stylesheet_uri(), array() );
	//if(is_page_template('pages/template-fullwidth.php')) wp_enqueue_style( 'shopperexpress-locations', get_template_directory_uri() . '/css/location.css', array() );
}
add_action( 'wp_enqueue_scripts', 'shopperexpress_scripts_styles' );
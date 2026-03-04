<?php

/**
 * Enqueue theme scripts.
 *
 * @package ThemeName
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class Scripts
 *
 * @package App\Components\Base
 */
class Scripts implements Theme_Component {


	/**
	 * Register theme styles and scripts.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_style' ) );
	}

	/**
	 * Theme styles enqueue.
	 *
	 * @return void
	 */
	public function enqueue_styles(): void {

		$theme_version = wp_get_theme()->get( 'Version' );

		wp_enqueue_style(
			'base/style',
			get_stylesheet_uri(),
			array(),
			filemtime( get_stylesheet_directory() . '/style.css' )
		);

		wp_enqueue_style(
			'shopperexpress/fonts-Montserrat',
			'https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;0,900;1,400;1,500&family=Roboto:wght@400;500&display=swap',
			array(),
			$theme_version
		);

		wp_enqueue_style(
			'shopperexpress/bootstrap_css',
			\App\asset_url_old( 'css/bootstrap.css' ),
			array(),
			$theme_version
		);
		wp_enqueue_style(
			'shopperexpress/main_css',
			\App\asset_url_old( 'css/main.css' ),
			array(),
			$theme_version
		);
		wp_enqueue_style(
			'shopperexpress/style',
			\App\asset_url( 'style.css' ),
			array(),
			$theme_version
		);
	}

	/**
	 * Admin panel styles enqueue.
	 *
	 * @return void
	 */
	public function load_admin_style(): void {

		$theme_version = wp_get_theme()->get( 'Version' );

		wp_enqueue_style(
			'shopperexpress/admin_panel_css',
			\App\asset_url_old( 'css/admin-panel.css' ),
			array(),
			$theme_version
		);
	}

	/**
	 * Theme scripts enqueue.
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		$in_footer     = true;
		$theme_version = wp_get_theme()->get( 'Version' );

		wp_deregister_script( 'comment-reply' );
		wp_deregister_style( 'listing_shortcodes' );
		wp_deregister_style( 'listing_style' );
		wp_deregister_style( 'listing_mobile' );
		wp_deregister_style( 'bootstrap' );
		/*
		if ( $video_id = get_field( 'video_id', 'options' ) ) {
			wp_enqueue_script( 'shopperexpress-wistia', 'https://fast.wistia.com/embed/medias/' . $video_id . '.jsonp', array( 'jquery' ), '', false );
			wp_enqueue_script(
				'shopperexpress/wistia',
				'https://fast.wistia.com/embed/medias/' . $video_id . '.jsonp',
				[ 'jquery' ],
				$theme_version,
				false
			);
		}
		*/
		wp_enqueue_script(
			'shopperexpress/external',
			'https://fast.wistia.com/assets/external/E-v1.js',
			array( 'jquery' ),
			$theme_version,
			false
		);

		wp_enqueue_script(
			'shopperexpress/popper',
			'https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js',
			array( 'jquery' ),
			$theme_version,
			false
		);
		wp_enqueue_script(
			'shopperexpress/bootstrap-js',
			\App\asset_url_old( 'js/bootstrap.min.js' ),
			array( 'jquery' ),
			$theme_version,
			false
		);
		wp_enqueue_script(
			'shopperexpress/jquery',
			\App\asset_url( 'js/app.js' ),
			array( 'jquery' ),
			time(),
			false
		);
		wp_enqueue_script(
			'shopperexpress/google-map',
			'https://maps.googleapis.com/maps/api/js?key=AIzaSyDejIT65GensQRQ4KepnS7xcFDM-gu5JUI&libraries=marker&loading=async&callback=initCustomMap',
			array( 'jquery' ),
			$theme_version,
			false
		);
		wp_localize_script(
			'shopperexpress/jquery',
			'ajax',
			array(
				'admin'   => admin_url( 'admin-ajax.php' ),
				'request' => $_GET,
				'nonce'   => wp_create_nonce( 'shopperexpress_nonce' ),
			)
		);
		wp_enqueue_script(
			'shopperexpress/impl',
			\App\asset_url_old( 'js/impl.js' ),
			array( 'jquery' ),
			$theme_version,
			false
		);
		wp_localize_script(
			'shopperexpress/impl',
			'ajax',
			array(
				'admin'   => admin_url( 'admin-ajax.php' ),
				'request' => $_GET,
				'loged'   => is_user_logged_in(),
			)
		);
	}
}

<?php
/**
 * Enqueue theme scripts.
 *
 * @package Shopperexpress
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
			'shopperexpress/asc-publish',
			\App\asset_url_old( 'js/asc-publish.js' ),
			array(),
			$theme_version,
			true
		);
		wp_enqueue_script(
			'shopperexpress/asc-cta',
			\App\asset_url_old( 'js/asc-cta.js' ),
			array( 'shopperexpress/asc-publish' ),
			$theme_version,
			true
		);
		wp_enqueue_script(
			'shopperexpress/asc-datalayer',
			\App\asset_url_old( 'js/asc-datalayer.js' ),
			array( 'shopperexpress/asc-publish', 'jquery' ),
			$theme_version,
			true
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
				'admin'          => admin_url( 'admin-ajax.php' ),
				'request'        => ! empty( $_GET ) ? $_GET : '',
				'nonce'          => wp_create_nonce( 'shopperexpress_nonce' ),
				'adf_lead_nonce' => wp_create_nonce( 'submit_adf_lead' ),
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
				'request' => ! empty( $_GET ) ? $_GET : '',
				'loged'   => is_user_logged_in(),
				'nonce'   => wp_create_nonce( 'shopperexpress_nonce' ),
			)
		);
	}
}

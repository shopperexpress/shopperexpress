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
	 * Theme version.
	 *
	 * @var string
	 */
	protected $theme_version;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->theme_version = wp_get_theme()->get( 'Version' );
	}

	/**
	 * Register theme styles and scripts.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_style' ) );
		add_action( 'admin_head', array( $this, 'render_vars' ), 1 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'load_admin_block_style' ) );
	}

	/**
	 * Theme styles enqueue.
	 *
	 * @return void
	 */
	public function render_vars(): void {

		$theme_color = get_field( 'theme_color', 'options' );
		if ( ! $theme_color ) {
			return;
		}

		$overlay_color   = get_field( 'overlay_color', 'options' );
		$overlay_opacity = get_field( 'overlay_opacity', 'options' );

		?>
		<style>
			:root {
				--primary: <?php echo esc_attr( $theme_color ); ?>;
				--primary-rgb: <?php echo esc_attr( hexToRgb( $theme_color ) ); ?>;
				--primary-rgba: <?php echo esc_attr( hexToRgb( $theme_color ) ); ?>;

				<?php if ( $overlay_color ) : ?>
				--overlay-color-rgb: <?php echo esc_attr( hexToRgb( $overlay_color ) ); ?>;
				<?php endif; ?>

				<?php if ( $overlay_opacity ) : ?>
				--overlay-opacity: <?php echo esc_attr( $overlay_opacity ); ?>;
				<?php endif; ?>
				--font-family-base: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif;
			}
			body{
				font-family: var(--font-family-base);
				font-size: 13px;
			}
			::after, ::before {
				box-sizing: content-box;
			}
			button, input, optgroup, select, textarea {
				line-height: normal;
			}
			.block-editor-inserter__preview-container__popover iframe {
				max-width: inherit;
			}
			.block-editor-list-view-block__contents-cell table td,.block-editor-list-view-block__contents-cell table th {
				border: none;
			}
		</style>
		<?php
	}

	/**
	 * Theme styles enqueue.
	 *
	 * @return void
	 */
	public function enqueue_styles(): void {

		$theme_version = $this->theme_version;

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
	public function load_admin_block_style(): void {
		$theme_version = $this->theme_version;

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

		$theme_version = $this->theme_version;

		wp_enqueue_style(
			'shopperexpress/main_css_admin',
			\App\asset_url_old( 'css/main.css' ),
			array(),
			$theme_version
		);
	}

	/**
	 * Combined new+used vehicles feed REST URL for the currently active API mode.
	 *
	 * @return string
	 */
	private function vehicles_feed_url(): string {
		return \App\is_api_mode()
			? rest_url( 'v1/intice/vehicles/vehicles-feed' )
			: rest_url( 'v1/vehicles/vehicles-feed' );
	}

	/**
	 * Theme scripts enqueue.
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {

		$theme_version = $this->theme_version;

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
			$theme_version,
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
			'shopperexpress/asc-intice-tool-intake',
			\App\asset_url_old( 'js/asc-intice-tool-intake.js' ),
			array( 'shopperexpress/asc-publish' ),
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
			'shopperexpress/asc-menu',
			\App\asset_url_old( 'js/asc-menu.js' ),
			array( 'shopperexpress/asc-publish' ),
			$theme_version,
			true
		);
		wp_enqueue_script(
			'shopperexpress/asc-media',
			\App\asset_url_old( 'js/asc-media.js' ),
			array( 'shopperexpress/asc-publish', 'jquery' ),
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
			'shopperexpress/asc-openai-ads',
			\App\asset_url_old( 'js/asc-openai-ads.js' ),
			array( 'shopperexpress/asc-publish' ),
			$theme_version,
			true
		);
		wp_enqueue_script(
			'shopperexpress/google-map',
			'https://maps.googleapis.com/maps/api/js?key=AIzaSyDejIT65GensQRQ4KepnS7xcFDM-gu5JUI&libraries=marker,places&loading=async&callback=initCustomMap',
			array( 'jquery' ),
			$theme_version,
			false
		);
		wp_localize_script(
			'shopperexpress/jquery',
			'ajax',
			array(
				'admin'               => admin_url( 'admin-ajax.php' ),
				'request'             => ! empty( $_GET ) ? $_GET : '',
				'nonce'               => wp_create_nonce( 'shopperexpress_nonce' ),
				'adf_lead_nonce'      => wp_create_nonce( 'submit_adf_lead' ),
				'api_mode'            => (bool) get_option( 'shopperexpress_api_mode_enabled' ),
				'google_reviews_rest' => rest_url( 'v1/google-reviews' ),
				'vehicles_feed_rest'  => $this->vehicles_feed_url(),
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
				'admin'               => admin_url( 'admin-ajax.php' ),
				'request'             => ! empty( $_GET ) ? $_GET : '',
				'loged'               => is_user_logged_in(),
				'nonce'               => wp_create_nonce( 'shopperexpress_nonce' ),
				'api_mode'            => (bool) get_option( 'shopperexpress_api_mode_enabled' ),
				'google_reviews_rest' => rest_url( 'v1/google-reviews' ),
				'vehicles_feed_rest'  => $this->vehicles_feed_url(),
			)
		);
	}
}

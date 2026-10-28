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
		// After shopperexpress_enqueue_assets() (priority 10) registers the 'shopperexpress' bundle
		// handle, which enqueue_scripts() localizes.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_style' ) );
		add_action( 'admin_head', array( $this, 'render_vars' ), 1 );
		add_action( 'wp_head', array( $this, 'render_frontend_vars' ) );
		add_action( 'wp_head', array( $this, 'render_header_script_field' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'load_admin_block_style' ) );
	}

	/**
	 * Output the admin-defined "for_script_header" field (raw head scripts/meta from theme options).
	 *
	 * @return void
	 */
	public function render_header_script_field(): void {
		the_field( 'for_script_header', 'options' );
	}

	/**
	 * Render front-end CSS custom properties (theme color, overlay, nav colors, font) from theme options.
	 *
	 * @return void
	 */
	public function render_frontend_vars(): void {

		$theme_color = get_field( 'theme_color', 'options' );
		if ( ! $theme_color ) {
			return;
		}

		$font                        = get_field( 'font', 'options' );
		$overlay_color               = get_field( 'overlay_color', 'options' );
		$overlay_opacity             = get_field( 'overlay_opacity', 'options' );
		$header_nav_drop_bg          = get_field( 'header_nav_drop_bg', 'options' ) ? get_field( 'header_nav_drop_bg', 'options' ) : 'rgba(248, 246, 246, 1)';
		$header_nav_link_color       = get_field( 'header_nav_link_color', 'options' ) ? get_field( 'header_nav_link_color', 'options' ) : 'rgba(26, 26, 26, 1)';
		$header_nav_link_hover_color = get_field( 'header_nav_link_hover_color', 'options' ) ? get_field( 'header_nav_link_hover_color', 'options' ) : 'rgba(0, 116, 179, 1)';

		?>
		<style type="text/css">
			:root {
				--primary: <?php echo esc_attr( $theme_color ); ?> !important;
				--primary-rgb: <?php echo esc_attr( hexToRgb( $theme_color ) ); ?> !important;
				--primary-rgba: <?php echo esc_attr( hexToRgb( $theme_color ) ); ?> !important;
				<?php if ( $overlay_color ) : ?>
				--overlay-color-rgb: <?php echo esc_attr( hexToRgb( $overlay_color ) ); ?> !important;
				<?php endif; ?>
				<?php if ( $overlay_opacity ) : ?>
				--overlay-opacity: <?php echo esc_attr( $overlay_opacity ); ?> !important;
				<?php endif; ?>
				<?php if ( $header_nav_drop_bg ) : ?>
				--header-nav-drop-bg: <?php echo esc_attr( $header_nav_drop_bg ); ?> !important;
				<?php endif; ?>
				<?php if ( $header_nav_link_color ) : ?>
				--header-nav-link-color: <?php echo esc_attr( $header_nav_link_color ); ?> !important;
				<?php endif; ?>
				<?php if ( $header_nav_link_hover_color ) : ?>
				--header-nav-link-hover-color: <?php echo esc_attr( $header_nav_link_hover_color ); ?> !important;
				<?php endif; ?>
				<?php
				switch ( $font ) {
					case 1:
						?>
						--font-family-base: "Montserrat", "Helvetica Neue", Arial, sans-serif;
						<?php
						break;
					case 3:
						?>
						--font-family-base: Inter, Arial, Helvetica Neue, sans-serif;
						<?php
						break;
					default:
						?>
						--font-family-base: Arial Rounded MT Bold, Helvetica Rounded, Arial, sans-serif;
						<?php
						break;
				}
				?>
			}
		</style>
		<?php
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
		// The theme bundle itself (assets/css/style.css) is enqueued by inc/enqueue.php.
		wp_enqueue_style(
			'base/style',
			get_stylesheet_uri(),
			array( 'shopperexpress' ),
			filemtime( get_stylesheet_directory() . '/style.css' )
		);
	}

	/**
	 * Block editor styles enqueue.
	 *
	 * @return void
	 */
	public function load_admin_block_style(): void {
		wp_enqueue_style(
			'shopperexpress/style',
			\App\asset_url( 'css/style.css' ),
			array(),
			shopperexpress_asset_version( 'assets/css/style.css' )
		);
	}

	/**
	 * Admin panel styles enqueue.
	 *
	 * @return void
	 */
	public function load_admin_style(): void {
		wp_enqueue_style(
			'shopperexpress/admin',
			\App\asset_url( 'css/admin.css' ),
			array(),
			shopperexpress_asset_version( 'assets/css/admin.css' )
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

		wp_register_script(
			'shopperexpress/google-map',
			'https://maps.googleapis.com/maps/api/js?key=AIzaSyDejIT65GensQRQ4KepnS7xcFDM-gu5JUI&libraries=marker,places&loading=async',
			array( 'jquery' ),
			$theme_version,
			true
		);
		// The theme bundle (assets/js/app.js) is enqueued by inc/enqueue.php as 'shopperexpress'.
		wp_localize_script(
			'shopperexpress',
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
	}
}

<?php
/**
 * Custom login page UI.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class Login
 *
 * @package App\Components\Base
 */
class Login implements Theme_Component {

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
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'login_header', array( $this, 'render_open_wrapper' ) );
		add_action( 'login_footer', array( $this, 'render_close_wrapper' ) );
		add_filter( 'login_headerurl', array( $this, 'logo_url' ) );
		add_filter( 'login_headertext', array( $this, 'logo_text' ) );
	}

	/**
	 * Enqueue login stylesheet and output ACF-driven CSS custom properties.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		wp_enqueue_style(
			'shopperexpress/login',
			get_template_directory_uri() . '/assets/dist/login.css',
			array(),
			$this->theme_version
		);

		$this->output_inline_vars();
	}

	/**
	 * Emit a <style> block with CSS custom properties sourced from ACF options.
	 * This keeps all image/colour configuration in the WP admin, not in the SCSS.
	 *
	 * @return void
	 */
	private function output_inline_vars(): void {
		$panel_image = $this->get_image_url( 'login_panel_image' ) ? $this->get_image_url( 'login_panel_image' ) : get_template_directory_uri() . '/assets/images/login/intice360Artboard-1@.webp';
		$logo_image  = $this->get_image_url( 'login_logo_image' ) ? $this->get_image_url( 'login_logo_image' ) : get_template_directory_uri() . '/assets/images/login/intice360intice@3x-1.png';

		$vars = array();

		if ( $panel_image ) {
			$vars[] = '--login-panel-image: url(' . esc_url( $panel_image ) . ');';
		}

		if ( $logo_image ) {
			$vars[] = '--login-logo-image: url(' . esc_url( $logo_image ) . ');';
		}

		if ( empty( $vars ) ) {
			return;
		}

		echo '<style id="shopperexpress-login-vars">';
		echo '#uip-login-wrap {';
		echo implode( ' ', $vars );
		echo '}';
		echo '</style>' . "\n";
	}

	/**
	 * Output opening wrapper HTML before the login form.
	 * login_header fires inside <body>, before <div id="login">.
	 *
	 * @return void
	 */
	public function render_open_wrapper(): void {
		?>
		<div id="uip-login-wrap">
			<div id="uip-login-form-wrap">
				<div id="uip-login-form">
		<?php
	}

	/**
	 * Output closing wrapper HTML after the login form.
	 * login_footer fires after the closing </form> and </div#login>.
	 *
	 * @return void
	 */
	public function render_close_wrapper(): void {
		?>
				</div><!-- /#uip-login-form -->
			</div><!-- /#uip-login-form-wrap -->

			<div id="uip-login-panel" aria-hidden="true">
				<?php echo wp_kses_post( $this->get_panel_html() ); ?>
			</div><!-- /#uip-login-panel -->
		</div><!-- /#uip-login-wrap -->
		<?php
	}

	/**
	 * Replace the logo link URL with the site home URL.
	 *
	 * @return string
	 */
	public function logo_url(): string {
		return home_url( '/' );
	}

	/**
	 * Replace the logo link title attribute with the site name.
	 *
	 * @return string
	 */
	public function logo_text(): string {
		return get_bloginfo( 'name' );
	}


	/**
	 * Resolve an ACF image field (option) to a full URL string.
	 *
	 * @param string $field_name ACF field name.
	 * @return string Empty string when not set.
	 */
	private function get_image_url( string $field_name ): string {
		if ( ! function_exists( 'get_field' ) ) {
			return '';
		}

		$image = get_field( $field_name, 'option' );

		if ( empty( $image ) ) {
			return '';
		}

		if ( is_array( $image ) ) {
			return $image['url'] ?? '';
		}

		if ( is_numeric( $image ) ) {
			return (string) wp_get_attachment_url( (int) $image );
		}

		return is_string( $image ) ? $image : '';
	}

	/**
	 * Build panel inner HTML.
	 * Filterable so other code can inject panel content.
	 *
	 * @return string
	 */
	private function get_panel_html(): string {
		return (string) apply_filters( 'shopperexpress_login_panel_html', '' );
	}
}

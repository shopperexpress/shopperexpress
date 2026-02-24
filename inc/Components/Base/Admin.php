<?php
/**
 * Admin calss.
 * Add some options to the WordPress admin dashboard.
 *
 * @package ThemeName
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class Admin
 *
 * @package App\Components\Base
 */
class Admin implements Theme_Component {

	/**
	 * Adds required the action and filter hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_notices', [ $this, 'seo_warning' ] );
		add_action( 'add_admin_bar_menus', [ $this, 'fix_admin_menu_search' ] );
	}

	/**
	 * Add seo warning in case if Search Engine Visibility option
	 *
	 * @return void
	 */
	public function seo_warning(): void {
		if ( get_option( 'blog_public' ) ) {
			return;
		}

		// translators: %s reading options link.
		$message = __( 'You are blocking access to robots. You must go to your <a href="%s">Reading</a> settings and uncheck the box for Search Engine Visibility.', 'base' );

		echo '<div class="error"><p>';
		printf( wp_kses( $message, 'default' ), esc_url( admin_url( 'options-reading.php' ) ) );
		echo '</p></div>';
	}

	/**
	 * Fix admin bar search menu
	 *
	 * @return void
	 */
	public function fix_admin_menu_search(): void {
		remove_action( 'admin_bar_menu', 'wp_admin_bar_search_menu', 4 );
		add_action( 'admin_bar_menu', [ $this, 'wp_admin_bar_valid_search_menu' ], 4 );
	}

	/**
	 * Make wp admin menu html valid
	 *
	 * @param object $wp_admin_bar WordPress admin bar object.
	 */
	public function wp_admin_bar_valid_search_menu( object $wp_admin_bar ): void {
		if ( is_admin() ) {
			return;
		}

		$form  = '<form action="' . esc_url( home_url( '/' ) ) . '" method="get" id="adminbarsearch"><div>';
		$form .= '<input class="adminbar-input" name="s" id="adminbar-search" tabindex="10" type="text" value="" maxlength="150" />';
		$form .= '<input type="submit" class="adminbar-button" value="' . esc_html__( 'Search', 'base' ) . '"/>';
		$form .= '</div></form>';

		$wp_admin_bar->add_menu( [
			'parent' => 'top-secondary',
			'id'     => 'search',
			'title'  => $form,
			'meta'   => [
				'class'    => 'admin-bar-search',
				'tabindex' => -1,
			],
		] );
	}
}

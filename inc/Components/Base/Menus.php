<?php
/**
 * WordPress Nav Menus component.
 *
 * @package ThemeName
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class Menus
 *
 * @package App\Base\Component
 */
class Menus implements Theme_Component {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'after_setup_theme', [ $this, 'register_nav_menus' ] );
	}

	/**
	 * Register theme nav menus.
	 *
	 * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
	 * @return void
	 */
	public function register_nav_menus(): void {
		register_nav_menus( [
			'drop-down' => esc_html__( 'Drop-down', 'shopperexpress' ),
			'header' => esc_html__( 'Header Navigation', 'shopperexpress' ),
		] );
	}
}

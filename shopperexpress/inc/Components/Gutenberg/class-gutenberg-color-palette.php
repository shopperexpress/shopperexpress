<?php
/**
 * Custom Gutenberg color palette.
 *
 * @package Shopperexpress
 */

namespace App\Components\Gutenberg;

use App\Components\Theme_Component;

/**
 * Class Gutenberg_Color_Palette
 *
 * @package App\Components\Gutenberg
 */
class Gutenberg_Color_Palette implements Theme_Component {


	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'after_setup_theme', array( $this, 'theme_color_palette' ) );
	}

	/**
	 * Theme color palette.
	 *
	 * @return void
	 */
	public function theme_color_palette(): void {
		add_theme_support(
			'editor-color-palette',
			array(
				array(
					'name'  => esc_html__( 'Primary', 'shopperexpress' ),
					'slug'  => 'primary',
					'color' => '#0090ff',
				),
				array(
					'name'  => esc_html__( 'Black', 'shopperexpress' ),
					'slug'  => 'black',
					'color' => '#000',
				),
				array(
					'name'  => esc_html__( 'Grey', 'shopperexpress' ),
					'slug'  => 'grey',
					'color' => '#8f8f8f',
				),
				array(
					'name'  => esc_html__( 'White', 'shopperexpress' ),
					'slug'  => 'white',
					'color' => '#fff',
				),
				array(
					'name'  => esc_html__( 'Blue', 'shopperexpress' ),
					'slug'  => 'blue',
					'color' => '#59BACC',
				),
				array(
					'name'  => esc_html__( 'Green', 'shopperexpress' ),
					'slug'  => 'green',
					'color' => '#58AD69',
				),
				array(
					'name'  => esc_html__( 'Orange', 'shopperexpress' ),
					'slug'  => 'orange',
					'color' => '#FFBC49',
				),
				array(
					'name'  => esc_html__( 'Red', 'shopperexpress' ),
					'slug'  => 'red',
					'color' => '#E2574C',
				),
			)
		);
	}
}

<?php

/**
 * Custom Gutenberg color palette.
 *
 * @package ThemeName
 */

namespace App\Components\Gutenberg;

use App\Components\Theme_Component;

/**
 * Class Gutenberg_Color_Palette
 *
 * @package App\Components\Gutenberg
 */
class Gutenberg_Color_Palette implements Theme_Component
{

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void
	{
		add_action('after_setup_theme', [$this, 'theme_color_palette']);
	}

	/**
	 * Theme color palette.
	 *
	 * @return void
	 */
	public function theme_color_palette(): void
	{
		add_theme_support('editor-color-palette', [
			[
				'name'  => esc_html__('Primary', 'base'),
				'slug'  => 'primary',
				'color' => '#0090ff',
			],
			[
				'name'  => esc_html__('Black', 'base'),
				'slug'  => 'black',
				'color' => '#000',
			],
			[
				'name'  => esc_html__('Grey', 'base'),
				'slug'  => 'grey',
				'color' => '#8f8f8f',
			],
			[
				'name'  => esc_html__('White', 'base'),
				'slug'  => 'white',
				'color' => '#fff',
			],
			[
				'name'  => esc_html__('Blue', 'base'),
				'slug'  => 'blue',
				'color' => '#59BACC',
			],
			[
				'name'  => esc_html__('Green', 'base'),
				'slug'  => 'green',
				'color' => '#58AD69',
			],
			[
				'name'  => esc_html__('Orange', 'base'),
				'slug'  => 'orange',
				'color' => '#FFBC49',
			],
			[
				'name'  => esc_html__('Red', 'base'),
				'slug'  => 'red',
				'color' => '#E2574C',
			],
		]);
	}
}

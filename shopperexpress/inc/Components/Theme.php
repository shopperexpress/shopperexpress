<?php
/**
 * Theme main class.
 * Main theme class takes care about initalizing theme features.
 *
 * @package Shopperexpress
 */

namespace App\Components;

use InvalidArgumentException;
use Auryn\Injector;
use Auryn\InjectionException;

/**
 * Class Theme
 *
 * @package App\Components
 */
class Theme {


	/**
	 * Dependency Injection Container.
	 *
	 * @var Injector
	 */
	private $injector;

	/**
	 * Constructor.
	 *
	 * @param Injector $injector Dependency Injection Container.
	 */
	public function __construct( Injector $injector ) {
		$this->injector = $injector;
	}

	/**
	 * Store all the theme classes inside an array
	 *
	 * @return array Full list of classes
	 */
	public function get_theme_components(): array {
		$components = array(
			\App\Components\Base\Theme_Support::class,
			\App\Components\Base\Shortcode::class,
			\App\Components\Base\Admin::class,
			\App\Components\Base\Options_Page::class,
			\App\Components\Base\Scripts::class,
			\App\Components\Base\Sidebars::class,
			\App\Components\Base\Menus::class,
			\App\Components\Base\CPT::class,
			\App\Components\Base\Api::class,
			\App\Components\Base\Ajax::class,
			\App\Components\Base\Rest::class,
			\App\Components\Base\ACF::class,
			\App\Components\Base\JSON_LD::class,
			\App\Components\Base\Export::class,
			\App\Components\Base\PopupResolver::class,
		);

		$gutenberg_components = array(
			\App\Components\Gutenberg\Custom_Blocks_Category::class,
			\App\Components\Gutenberg\Register_Gutenberg_Blocks::class,
			\App\Components\Gutenberg\Gutenberg_Color_Palette::class,
		);

		$components = array_merge( $components, $gutenberg_components );

		return $components;
	}

	/**
	 * Loop through the classes, initialize them, and call the register() method if it exists.
	 *
	 * @return void
	 * @throws InjectionException If cycling is detected during initialization.
	 */
	public function register_components(): void {
		$theme_components = $this->get_theme_components();

		array_map(
			function ( $class ) {
				$component = $this->injector->make( $class );

				if ( $component instanceof Theme_Component ) {
					$component->register();
				} else {
					throw new InvalidArgumentException(
						wp_kses_post(
							sprintf(
							// translators: %s: component name.
								esc_html__( 'The %s theme component don`t implements the Theme_Component interface.', 'base' ),
								$class
							)
						)
					);
				}
			},
			$theme_components
		);
	}
}

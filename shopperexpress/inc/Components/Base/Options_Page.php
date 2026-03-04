<?php
/**
 * Options Page.
 * Setup options page using ACF plugin.
 *
 * @package ThemeName
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class Options_Page
 *
 * @package App\Components\Base
 */
class Options_Page implements Theme_Component {

	/**
	 * Adds required the action and filter hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->add_options_page();
	}

	/**
	 * Add options page to the theme.
	 *
	 * @link https://www.advancedcustomfields.com/resources/acf_add_options_sub_page/
	 * @return void
	 */
	public function add_options_page(): void {
		if ( function_exists( 'acf_add_options_page' ) ) {
			$parent = acf_add_options_page(
				array(
					'page_title' => 'Theme General Options',
					'menu_title' => 'Theme Options',
					'redirect'   => false,
				)
			);

			if ( ! $parent ) {
				return;
			}

			$sub_pages = array(
				'listings'           => 'Listings',
				'special-offers'     => 'Special Offers',
				'research'           => 'Research',
				'service-offers'     => 'Service Offers',
				'finance-offers'     => 'Finance Offers',
				'lease-offers'       => 'Lease Offers',
				'conditional-offers' => 'Conditional Offers',
			);

			foreach ( $sub_pages as $slug => $title ) {
				acf_add_options_sub_page(
					array(
						'page_title'  => $title,
						'menu_title'  => $title,
						'menu_slug'   => $slug,
						'parent_slug' => $parent['menu_slug'],
					)
				);
			}
		}
	}
}

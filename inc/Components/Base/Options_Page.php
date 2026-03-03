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
		add_action(
			'acf/init',
			function () {
				$this->add_options_page();
			}
		);
	}

	/**
	 * Add options page to the theme.
	 *
	 * @link https://www.advancedcustomfields.com/resources/acf_add_options_sub_page/
	 * @return void
	 */
	private function add_options_page(): void {
		if ( class_exists( 'ACF' ) ) {
			$parent = acf_add_options_page(
				array(
					'page_title' => 'Theme General Options',
					'menu_title' => 'Theme Options',
					'redirect'   => false,
				)
			);

			$sub_pages = array(
				__( 'Listings', 'shopperexpress' ),
				__( 'Special Offers', 'shopperexpress' ),
				__( 'Research', 'shopperexpress' ),
				__( 'Service Offers', 'shopperexpress' ),
				__( 'Finance Offers', 'shopperexpress' ),
				__( 'Lease Offers', 'shopperexpress' ),
				__( 'Conditional Offers', 'shopperexpress' ),
			);

			foreach ( $sub_pages as $item ) {
				$child = acf_add_options_sub_page(
					array(
						'page_title'  => $item,
						'menu_title'  => $item,
						'parent_slug' => $parent['menu_slug'],
					)
				);
			}
		}
	}
}

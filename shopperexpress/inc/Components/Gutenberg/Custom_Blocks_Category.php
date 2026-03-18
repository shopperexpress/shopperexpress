<?php
/**
 * Register custom blocks category.
 *
 * @package Shopperexpress
 */

namespace App\Components\Gutenberg;

use App\Components\Theme_Component;

/**
 * Class Custom_Blocks_Category
 *
 * @package App\Components\Gutenberg
 */
class Custom_Blocks_Category implements Theme_Component {


	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'block_categories_all', array( $this, 'register_category' ), 10, 2 );
	}

	/**
	 * Register custom blocks category.
	 *
	 * @param array    $categories Default categories array.
	 * @param \WP_Post $post Post being loaded.
	 * @return array
	 */
	public function register_category( array $categories, $block_editor_context ): array {
		return array_merge(
			$categories,
			array(
				array(
					'slug'  => 'custom-acf-blocks',
					'title' => esc_html__( 'Custom ACF Blocks', 'base' ),
				),
			)
		);
	}
}

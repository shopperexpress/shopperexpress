<?php
/**
 * Block: Tabs Slider
 *
 * Title: Tabs Slider
 * Description: Vehicle type filter with model slider from options page.
 * Keywords: slider tabs models vehicles filter
 * Category: custom-acf-blocks
 * Icon: car
 *
 * @package ShopperExpress
 */

if ( \App\Components\Gutenberg\Block_Preview_Helper::render( $block ) ) {
	return;
}

if ( $is_preview ) {
	\App\Components\Gutenberg\Block_Preview_Helper::render( $block, true );
	return;
}

get_template_part(
	'template-parts/acf-shared/tabs-slider',
	null,
	array(
		'heading'                    => get_field( 'heading' ),
		'sort_by_number_of_vehicles' => get_field( 'sort_by_number_of_vehicles' ),
		'index'                      => 0,
	)
);

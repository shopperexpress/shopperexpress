<?php
/**
 * Block: Image Left Text Right
 *
 * Title: Image Left & Text Right
 * Description: Two-column block with image on the left and text on the right.
 * Keywords: image text columns trade
 * Category: custom-acf-blocks
 * Icon: align-left
 *
 * @package ShopperExpress
 */

if ( \App\Components\Gutenberg\Block_Preview_Helper::render( $block ) ) {
	return;
}

get_template_part(
	'template-parts/acf-shared/image-left-text-right',
	null,
	array(
		'layout_class'       => ' trade ',
		'icon'               => get_field( 'icon' ),
		'title'              => get_field( 'title' ),
		'subtitle'           => get_field( 'subtitle' ),
		'image'              => get_field( 'image' ),
		'image_decor'        => get_field( 'image_decor' ),
		'decor_position'     => get_field( 'decor_position' ),
		'bottom_button_code' => get_field( 'bottom_button_code' ),
		'additional_image'   => get_field( 'additional_image' ),
		'centered_image'     => get_field( 'centered_image' ),
		'text'               => get_field( 'text' ),
	)
);

<?php
/**
 * Block: Text Left Image Right
 *
 * Title: Text Left & Image Right
 * Description: Two-column block with text on the left and image on the right.
 * Keywords: text image columns
 * Category: custom-acf-blocks
 * Icon: align-right
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
	'template-parts/acf-shared/image-left-text-right',
	null,
	array(
		'layout_class'       => '',
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

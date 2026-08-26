<?php
/**
 * Block: Gallery
 *
 * Title: Gallery
 * Description: Fancybox grid gallery with optional full-width layout.
 * Keywords: gallery grid fancybox images
 * Category: custom-acf-blocks
 * Icon: format-gallery
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
	'template-parts/acf-shared/gallery',
	null,
	array(
		'gray_background' => get_field( 'gray_background' ),
		'full_width'      => get_field( 'full_width' ),
		'remove_paddings' => get_field( 'remove_paddings' ),
		'text'            => get_field( 'text' ),
		'images'          => get_field( 'gallery' ),
		'row_index'       => 0,
	)
);

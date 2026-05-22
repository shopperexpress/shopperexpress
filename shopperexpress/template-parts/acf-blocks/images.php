<?php
/**
 * Block: Images
 *
 * Title: Images
 * Description: Simple image gallery section.
 * Keywords: images gallery media
 * Category: custom-acf-blocks
 * Icon: format-gallery
 *
 * @package ShopperExpress
 */

if ( \App\Components\Gutenberg\Block_Preview_Helper::render( $block ) ) {
	return;
}

get_template_part(
	'template-parts/acf-shared/images',
	null,
	array(
		'images' => get_field( 'images' ),
	)
);

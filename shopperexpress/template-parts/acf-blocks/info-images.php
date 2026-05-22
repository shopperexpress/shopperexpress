<?php
/**
 * Block: Info Images
 *
 * Title: Info Images
 * Description: Titled image section.
 * Keywords: info images title media
 * Category: custom-acf-blocks
 * Icon: format-image
 *
 * @package ShopperExpress
 */

if ( \App\Components\Gutenberg\Block_Preview_Helper::render( $block ) ) {
	return;
}

get_template_part(
	'template-parts/acf-shared/info-images',
	null,
	array(
		'title'  => get_field( 'title' ),
		'images' => get_field( 'images' ),
	)
);

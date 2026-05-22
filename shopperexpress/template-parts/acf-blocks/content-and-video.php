<?php
/**
 * Block: Content and Video
 *
 * Title: Content and Video
 * Description: Side-by-side content card and Wistia video.
 * Keywords: content video wistia awards
 * Category: custom-acf-blocks
 * Icon: format-video
 *
 * @package ShopperExpress
 */

if ( \App\Components\Gutenberg\Block_Preview_Helper::render( $block ) ) {
	return;
}

get_template_part(
	'template-parts/acf-shared/content-and-video',
	null,
	array(
		'top_image' => get_field( 'top_image' ),
		'html'      => get_field( 'html' ),
		'wistia_id' => get_field( 'wistia_id' ),
	)
);

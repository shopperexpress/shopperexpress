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
		'top_image'           => get_field( 'top_image' ),
		'html'                => get_field( 'html' ),
		'primary_video'       => get_field( 'primary_video' ),
		'override_video'      => get_field( 'override_video' ),
		'override_start_date' => get_field( 'override_start_date' ),
		'override_end_date'   => get_field( 'override_end_date' ),
	)
);

<?php
/**
 * Block: Single Video
 *
 * Title: Single Video
 * Description: Embed video code section.
 * Keywords: video embed media
 * Category: custom-acf-blocks
 * Icon: format-video
 *
 * @package ShopperExpress
 */

if ( \App\Components\Gutenberg\Block_Preview_Helper::render( $block ) ) {
	return;
}

get_template_part(
	'template-parts/acf-shared/single-video',
	null,
	array(
		'video_code'    => get_field( 'video_code' ),
		'remove_margin' => get_field( 'remove_margin' ),
	)
);

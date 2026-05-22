<?php
/**
 * Block: Text & Video Slider
 *
 * Title: Text & Video Slider
 * Description: Specials slider with payment info and Wistia video per offer.
 * Keywords: specials slider video offers payment
 * Category: custom-acf-blocks
 * Icon: slides
 *
 * @package ShopperExpress
 */

if ( \App\Components\Gutenberg\Block_Preview_Helper::render( $block ) ) {
	return;
}

get_template_part(
	'template-parts/acf-shared/text-video-slider',
	null,
	array(
		'sort_offers_by' => get_field( 'sort_offers_by' ),
		'slider_speed'   => get_field( 'slider_speed' ) ?: 500,
		'autoplay_speed' => get_field( 'autoplay_speed' ) ?: 5000,
		'hide_block'     => get_field( 'hide_block' ),
	)
);

<?php
/**
 * Flexible Content Wrapper: Text & Video Slider
 *
 * @package ShopperExpress
 */

get_template_part(
	'template-parts/acf-shared/text-video-slider',
	null,
	array(
		'sort_offers_by' => get_sub_field( 'sort_offers_by' ),
		'slider_speed'   => get_sub_field( 'slider_speed' ) ?: 500,
		'autoplay_speed' => get_sub_field( 'autoplay_speed' ) ?: 5000,
		'hide_block'     => get_sub_field( 'hide_block' ),
	)
);

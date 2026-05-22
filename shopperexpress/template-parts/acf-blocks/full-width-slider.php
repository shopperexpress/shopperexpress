<?php
/**
 * Block: Full Width Slider
 *
 * Title: Full Width Slider
 * Description: Date-gated full-width image slider with disclosure support.
 * Keywords: slider fullwidth banner images
 * Category: custom-acf-blocks
 * Icon: slides
 *
 * @package ShopperExpress
 */

if ( \App\Components\Gutenberg\Block_Preview_Helper::render( $block ) ) {
	return;
}

$slider_speed   = get_field( 'slider_speed' ) ?: 500;
$autoplay_speed = get_field( 'autoplay_speed' ) ?: 5000;
$today          = date( 'Ymd' );
$slides         = array();

if ( have_rows( 'slider' ) ) {
	while ( have_rows( 'slider' ) ) {
		the_row();
		$image      = get_sub_field( 'image' );
		$start_date = get_sub_field( 'start_date' );
		$end_date   = get_sub_field( 'end_date' );

		if ( $image && $start_date && $end_date && $today >= $start_date && $today <= $end_date ) {
			$image_mobile = get_sub_field( 'image_mobile' ) ?: $image;
			$slides[]     = array(
				'image'           => $image,
				'image_mobile'    => $image_mobile,
				'url'             => get_sub_field( 'url' ),
				'open_in_new_tab' => get_sub_field( 'open_in_new_tab' ),
				'ariaLabel'       => get_sub_field( 'arialabel' ),
				'disclosure'      => get_sub_field( 'disclosure' ),
			);
		}
	}
}

get_template_part(
	'template-parts/acf-shared/full-width-slider',
	null,
	array(
		'remove_paddings' => get_field( 'remove_paddings' ),
		'slider_speed'    => $slider_speed,
		'autoplay_speed'  => $autoplay_speed,
		'slides'          => $slides,
	)
);

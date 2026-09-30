<?php
/**
 * Block: LP Service Slider
 *
 * Title: LP Service Slider
 * Description: Landing page slick carousel of service cards — image, title, text, and a CTA button.
 * Keywords: landing services slider carousel cards
 * Category: custom-acf-blocks
 * Icon: slides
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

$slides = array();
if ( have_rows( 'slides' ) ) {
	while ( have_rows( 'slides' ) ) {
		the_row();
		$slides[] = array(
			'image'       => get_sub_field( 'image' ),
			'title'       => get_sub_field( 'title' ),
			'text'        => get_sub_field( 'text' ),
			'button_link' => get_sub_field( 'button_link' ),
		);
	}
}

get_template_part(
	'template-parts/acf-shared/lp-service-slider',
	null,
	array(
		'subtitle' => get_field( 'subtitle' ),
		'title'    => get_field( 'title' ),
		'slides'   => $slides,
		'anchor'   => $block['anchor'] ?? '',
	)
);

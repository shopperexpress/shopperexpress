<?php
/**
 * Flexible Content Wrapper: LP Service Slider
 *
 * @package ShopperExpress
 */

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
		'subtitle' => get_sub_field( 'subtitle' ),
		'title'    => get_sub_field( 'title' ),
		'slides'   => $slides,
		'anchor'   => get_sub_field( 'anchor' ),
	)
);

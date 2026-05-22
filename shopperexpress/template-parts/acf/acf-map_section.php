<?php
/**
 * Flexible Content Wrapper: Map Section
 *
 * @package ShopperExpress
 */

get_template_part(
	'template-parts/acf-shared/map-section',
	null,
	array(
		'title'    => get_sub_field( 'title' ),
		'subtitle' => get_sub_field( 'subtitle' ),
		'button'   => get_sub_field( 'button' ),
		'ltd'      => get_sub_field( 'ltd' ),
		'lng'      => get_sub_field( 'lng' ),
		'zoom'     => get_sub_field( 'zoom' ),
	)
);

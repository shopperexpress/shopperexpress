<?php
/**
 * Flexible Content Wrapper: LP Hero
 *
 * @package ShopperExpress
 */

$buttons = array();
if ( have_rows( 'buttons' ) ) {
	while ( have_rows( 'buttons' ) ) {
		the_row();
		$buttons[] = array(
			'link'  => get_sub_field( 'link' ),
			'style' => get_sub_field( 'style' ),
		);
	}
}

$nav_links = array();
if ( have_rows( 'nav_links' ) ) {
	while ( have_rows( 'nav_links' ) ) {
		the_row();
		$nav_links[] = array(
			'link' => get_sub_field( 'link' ),
		);
	}
}

get_template_part(
	'template-parts/acf-shared/lp-hero',
	null,
	array(
		'subtitle'         => get_sub_field( 'subtitle' ),
		'title'            => get_sub_field( 'title' ),
		'text'             => get_sub_field( 'text' ),
		'buttons'          => $buttons,
		'bg_image_desktop' => get_sub_field( 'bg_image_desktop' ),
		'bg_image_mobile'  => get_sub_field( 'bg_image_mobile' ),
		'nav_links'        => $nav_links,
		'anchor'           => get_sub_field( 'anchor' ),
	)
);

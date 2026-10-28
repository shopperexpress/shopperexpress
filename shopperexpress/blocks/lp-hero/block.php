<?php
/**
 * Block: LP Hero
 *
 * Title: LP Hero
 * Description: Landing page hero — background image, heading, buttons, and a quick-nav link row.
 * Keywords: landing hero visual banner
 * Category: custom-acf-blocks
 * Icon: cover-image
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
		'subtitle'          => get_field( 'subtitle' ),
		'title'             => get_field( 'title' ),
		'text'              => get_field( 'text' ),
		'buttons'           => $buttons,
		'bg_image_desktop'  => get_field( 'bg_image_desktop' ),
		'bg_image_mobile'   => get_field( 'bg_image_mobile' ),
		'nav_links'         => $nav_links,
		'anchor'            => $block['anchor'] ?? '',
	)
);

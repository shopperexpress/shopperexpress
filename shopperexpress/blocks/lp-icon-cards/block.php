<?php
/**
 * Block: LP Icon Cards
 *
 * Title: LP Icon Cards
 * Description: Landing page grid of icon + title + text cards. Optionally link each card, and toggle light/dark/gray style.
 * Keywords: landing icon cards grid features
 * Category: custom-acf-blocks
 * Icon: grid-view
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

$cards = array();
if ( have_rows( 'cards' ) ) {
	while ( have_rows( 'cards' ) ) {
		the_row();
		$cards[] = array(
			'icon_svg' => get_sub_field( 'icon_svg' ),
			'title'    => get_sub_field( 'title' ),
			'text'     => get_sub_field( 'text' ),
			'link'     => get_sub_field( 'link' ),
		);
	}
}

get_template_part(
	'template-parts/acf-shared/lp-icon-cards',
	null,
	array(
		'subtitle'    => get_field( 'subtitle' ),
		'title'       => get_field( 'title' ),
		'text'        => get_field( 'text' ),
		'cards'       => $cards,
		'style'       => get_field( 'style' ),
		'background'  => get_field( 'background' ),
		'pt_0'        => get_field( 'pt_0' ),
		'anchor'      => $block['anchor'] ?? '',
	)
);

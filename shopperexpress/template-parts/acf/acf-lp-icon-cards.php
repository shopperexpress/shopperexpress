<?php
/**
 * Flexible Content Wrapper: LP Icon Cards
 *
 * @package ShopperExpress
 */

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
		'subtitle'    => get_sub_field( 'subtitle' ),
		'title'       => get_sub_field( 'title' ),
		'text'        => get_sub_field( 'text' ),
		'cards'       => $cards,
		'style'       => get_sub_field( 'style' ),
		'background'  => get_sub_field( 'background' ),
		'pt_0'        => get_sub_field( 'pt_0' ),
		'anchor'      => get_sub_field( 'anchor' ),
	)
);

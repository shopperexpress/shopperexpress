<?php
/**
 * Block: LP Content Columns
 *
 * Title: LP Content Columns
 * Description: Landing page two-column block — text/buttons on one side, image on the other. Light or dark style.
 * Keywords: landing two columns text image
 * Category: custom-acf-blocks
 * Icon: align-pull-left
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

get_template_part(
	'template-parts/acf-shared/lp-content-columns',
	null,
	array(
		'subtitle'   => get_field( 'subtitle' ),
		'title'      => get_field( 'title' ),
		'text'       => get_field( 'text' ),
		'buttons'    => $buttons,
		'image'      => get_field( 'image' ),
		'style'      => get_field( 'style' ),
		'show_decor' => get_field( 'show_decor' ),
		'anchor'     => $block['anchor'] ?? '',
	)
);

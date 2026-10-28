<?php
/**
 * Block: LP Steps
 *
 * Title: LP Steps
 * Description: Landing page text column paired with a numbered step-by-step list.
 * Keywords: landing steps process how it works
 * Category: custom-acf-blocks
 * Icon: editor-ol
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

$steps = array();
if ( have_rows( 'steps' ) ) {
	while ( have_rows( 'steps' ) ) {
		the_row();
		$steps[] = array(
			'title' => get_sub_field( 'title' ),
			'text'  => get_sub_field( 'text' ),
		);
	}
}

get_template_part(
	'template-parts/acf-shared/lp-steps',
	null,
	array(
		'subtitle'    => get_field( 'subtitle' ),
		'title'       => get_field( 'title' ),
		'text'        => get_field( 'text' ),
		'button_link' => get_field( 'button_link' ),
		'steps'       => $steps,
		'anchor'      => $block['anchor'] ?? '',
	)
);

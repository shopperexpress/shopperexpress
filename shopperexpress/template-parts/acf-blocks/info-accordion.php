<?php
/**
 * Block: Info Accordion
 *
 * Title: Info Accordion
 * Description: FAQ / info accordion with JSON-LD schema output.
 * Keywords: accordion faq info schema
 * Category: custom-acf-blocks
 * Icon: list-view
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

$accordion = array();
if ( have_rows( 'accordion' ) ) {
	while ( have_rows( 'accordion' ) ) {
		the_row();
		$accordion[] = array(
			'heading' => get_sub_field( 'heading' ),
			'text'    => get_sub_field( 'text' ),
		);
	}
}

get_template_part(
	'template-parts/acf-shared/info-accordion',
	null,
	array(
		'section_row' => 0,
		'accordion'   => $accordion,
	)
);

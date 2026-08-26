<?php
/**
 * Block: Map Section
 *
 * Title: Map Section
 * Description: Google Maps section with address and directions button.
 * Keywords: map location address directions
 * Category: custom-acf-blocks
 * Icon: location
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

get_template_part(
	'template-parts/acf-shared/map-section',
	null,
	array(
		'title'    => get_field( 'title' ),
		'subtitle' => get_field( 'subtitle' ),
		'button'   => get_field( 'button' ),
		'ltd'      => get_field( 'ltd' ),
		'lng'      => get_field( 'lng' ),
		'zoom'     => get_field( 'zoom' ),
	)
);

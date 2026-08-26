<?php
/**
 * Block: Get Offer
 *
 * Title: Get Offer
 * Description: Call-to-action section with background image and offer code.
 * Keywords: offer cta background
 * Category: custom-acf-blocks
 * Icon: megaphone
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
	'template-parts/acf-shared/get-offer',
	null,
	array(
		'background_image' => get_field( 'background_image' ),
		'text'             => get_field( 'text' ),
		'for_code'         => get_field( 'for_code' ),
	)
);

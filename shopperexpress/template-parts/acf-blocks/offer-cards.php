<?php
/**
 * Block: Offer Cards
 *
 * Title: Offer Cards
 * Description: Slick slider of offer/service-offer cards pulled from post types.
 * Keywords: offers cards slider
 * Category: custom-acf-blocks
 * Icon: slides
 *
 * @package ShopperExpress
 */

if ( \App\Components\Gutenberg\Block_Preview_Helper::render( $block ) ) {
	return;
}

get_template_part(
	'template-parts/acf-shared/offer-cards',
	null,
	array(
		'show_offers_from' => get_field( 'show_offers_from' ),
		'offers_order'     => get_field( 'offers_order' ),
	)
);

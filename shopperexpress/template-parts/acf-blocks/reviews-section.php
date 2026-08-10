<?php
/**
 * Block: Reviews Section
 *
 * Title: Reviews Section
 * Description: Google Reviews widget with live rating, stars and review list.
 * Keywords: reviews google rating testimonials
 * Category: custom-acf-blocks
 * Icon: star-filled
 *
 * @package ShopperExpress
 */

if ( \App\Components\Gutenberg\Block_Preview_Helper::render( $block ) ) {
	return;
}

get_template_part(
	'template-parts/acf-shared/reviews-section',
	null,
	array(
		'heading'     => get_field( 'heading' ),
		'description' => get_field( 'description' ),
		// Resolved from the connected Business Profile location's Place ID —
		// not an editable field, so every block always points at the same
		// dealership listing the site is actually connected to.
		'place_id'    => ( new \App\Components\Base\Google_Business_Reviews() )->get_settings()['place_id'],
		'cta_text'    => get_field( 'cta_text' ),
		'cta_url'     => get_field( 'cta_url' ),
	)
);

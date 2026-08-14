<?php
/**
 * ACF Flexible Content: Reviews Section
 *
 * @package ShopperExpress
 */

get_template_part(
	'template-parts/acf-shared/reviews-section',
	null,
	array(
		'heading'      => get_sub_field( 'heading' ),
		'description'  => get_sub_field( 'description' ),
		'layout_style' => get_sub_field( 'layout_style' ) ?: 'list',
		// Resolved from the connected Business Profile location's Place ID —
		// not an editable field, so every block always points at the same
		// dealership listing the site is actually connected to.
		'place_id'     => ( new \App\Components\Base\Google_Business_Reviews() )->get_settings()['place_id'],
		'cta_text'     => get_sub_field( 'cta_text' ),
		'cta_url'      => get_sub_field( 'cta_url' ),
	)
);

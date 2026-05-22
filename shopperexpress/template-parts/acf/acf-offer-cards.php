<?php
/**
 * Flexible Content Wrapper: Offer Cards
 *
 * @package ShopperExpress
 */

get_template_part(
	'template-parts/acf-shared/offer-cards',
	null,
	array(
		'show_offers_from' => get_sub_field( 'show_offers_from' ),
		'offers_order'     => get_sub_field( 'offers_order' ),
	)
);

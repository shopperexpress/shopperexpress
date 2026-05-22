<?php
/**
 * Flexible Content Wrapper: Get Offer
 *
 * @package ShopperExpress
 */

get_template_part(
	'template-parts/acf-shared/get-offer',
	null,
	array(
		'background_image' => get_sub_field( 'background_image' ),
		'text'             => get_sub_field( 'text' ),
		'for_code'         => get_sub_field( 'for_code' ),
	)
);

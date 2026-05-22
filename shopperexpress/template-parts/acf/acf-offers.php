<?php
/**
 * Flexible Content Wrapper: Offers
 *
 * @package ShopperExpress
 */

$offers = array();
if ( have_rows( 'offers' ) ) {
	while ( have_rows( 'offers' ) ) {
		the_row();
		$offers[] = array(
			'image'       => get_sub_field( 'image' ),
			'description' => get_sub_field( 'description' ),
		);
	}
}

get_template_part(
	'template-parts/acf-shared/offers',
	null,
	array(
		'remove_paddings' => get_sub_field( 'remove_paddings' ),
		'offers'          => $offers,
	)
);

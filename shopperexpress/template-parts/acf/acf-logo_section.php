<?php
/**
 * Flexible Content Wrapper: Logo Section
 *
 * @package ShopperExpress
 */

$logos = array();
if ( have_rows( 'logos' ) ) {
	while ( have_rows( 'logos' ) ) {
		the_row();
		$logos[] = array(
			'alt'     => get_sub_field( 'alt' ),
			'new_tab' => get_sub_field( 'new_tab' ),
			'link'    => get_sub_field( 'link' ),
			'image'   => get_sub_field( 'image' ),
		);
	}
}

get_template_part(
	'template-parts/acf-shared/logo-section',
	null,
	array(
		'heading'          => get_sub_field( 'heading' ),
		'logos_backgorund' => get_sub_field( 'logos_backgorund' ),
		'logos_per_row'    => get_sub_field( 'logos_per_row' ),
		'remove_paddings'  => get_sub_field( 'remove_paddings' ),
		'logos'            => $logos,
	)
);

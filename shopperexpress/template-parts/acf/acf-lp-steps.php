<?php
/**
 * Flexible Content Wrapper: LP Steps
 *
 * @package ShopperExpress
 */

$steps = array();
if ( have_rows( 'steps' ) ) {
	while ( have_rows( 'steps' ) ) {
		the_row();
		$steps[] = array(
			'title' => get_sub_field( 'title' ),
			'text'  => get_sub_field( 'text' ),
		);
	}
}

get_template_part(
	'template-parts/acf-shared/lp-steps',
	null,
	array(
		'subtitle'    => get_sub_field( 'subtitle' ),
		'title'       => get_sub_field( 'title' ),
		'text'        => get_sub_field( 'text' ),
		'button_link' => get_sub_field( 'button_link' ),
		'steps'       => $steps,
		'anchor'      => get_sub_field( 'anchor' ),
	)
);

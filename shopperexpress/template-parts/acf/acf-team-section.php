<?php
/**
 * ACF Flexible Content: Team Section
 *
 * @package ShopperExpress
 */

$members = array();
if ( have_rows( 'members' ) ) {
	while ( have_rows( 'members' ) ) {
		the_row();
		$members[] = array(
			'photo'    => get_sub_field( 'photo' ),
			'name'     => get_sub_field( 'name' ),
			'position' => get_sub_field( 'position' ),
			'phone'    => get_sub_field( 'phone' ),
			'email'    => get_sub_field( 'email' ),
			'category' => get_sub_field( 'category' ),
		);
	}
}

get_template_part(
	'template-parts/acf-shared/team-section',
	null,
	array(
		'heading'            => get_sub_field( 'heading' ),
		'description'        => get_sub_field( 'description' ),
		'members'            => $members,
		'footer_heading'     => get_sub_field( 'footer_heading' ),
		'footer_button_text' => get_sub_field( 'footer_button_text' ),
		'footer_button_url'  => get_sub_field( 'footer_button_url' ),
	)
);

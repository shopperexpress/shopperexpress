<?php
/**
 * Block: Team Section
 *
 * Title: Team Section
 * Description: Filterable team member grid with photo, position and contact info.
 * Keywords: team staff members grid filter
 * Category: custom-acf-blocks
 * Icon: groups
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
		'heading'            => get_field( 'heading' ),
		'description'        => get_field( 'description' ),
		'members'            => $members,
		'footer_heading'     => get_field( 'footer_heading' ),
		'footer_button_text' => get_field( 'footer_button_text' ),
		'footer_button_url'  => get_field( 'footer_button_url' ),
	)
);

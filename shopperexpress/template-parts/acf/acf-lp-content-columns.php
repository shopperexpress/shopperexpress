<?php
/**
 * Flexible Content Wrapper: LP Content Columns
 *
 * @package ShopperExpress
 */

$buttons = array();
if ( have_rows( 'buttons' ) ) {
	while ( have_rows( 'buttons' ) ) {
		the_row();
		$buttons[] = array(
			'link'  => get_sub_field( 'link' ),
			'style' => get_sub_field( 'style' ),
		);
	}
}

get_template_part(
	'template-parts/acf-shared/lp-content-columns',
	null,
	array(
		'subtitle'   => get_sub_field( 'subtitle' ),
		'title'      => get_sub_field( 'title' ),
		'text'       => get_sub_field( 'text' ),
		'buttons'    => $buttons,
		'image'      => get_sub_field( 'image' ),
		'style'      => get_sub_field( 'style' ),
		'show_decor' => get_sub_field( 'show_decor' ),
		'anchor'     => get_sub_field( 'anchor' ),
	)
);

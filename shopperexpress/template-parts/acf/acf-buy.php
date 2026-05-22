<?php
/**
 * Flexible Content Wrapper: Buy
 *
 * @package ShopperExpress
 */

$columns = array();
if ( have_rows( 'columns' ) ) {
	while ( have_rows( 'columns' ) ) {
		the_row();
		$columns[] = array(
			'icon_image'  => get_sub_field( 'icon_image' ),
			'title'       => get_sub_field( 'title' ),
			'description' => get_sub_field( 'description' ),
		);
	}
}

get_template_part(
	'template-parts/acf-shared/buy',
	null,
	array(
		'text'    => get_sub_field( 'text' ),
		'slogan'  => get_sub_field( 'slogan' ),
		'columns' => $columns,
	)
);

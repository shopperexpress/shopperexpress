<?php
/**
 * Block: Buy
 *
 * Title: Buy
 * Description: Feature columns with icons, heading, and slogan.
 * Keywords: buy features icons columns
 * Category: custom-acf-blocks
 * Icon: grid-view
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
		'text'    => get_field( 'text' ),
		'slogan'  => get_field( 'slogan' ),
		'columns' => $columns,
	)
);

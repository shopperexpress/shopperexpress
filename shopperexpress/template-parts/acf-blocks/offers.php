<?php
/**
 * Block: Offers
 *
 * Title: Offers
 * Description: Two-column offer cards with image and description.
 * Keywords: offers deals promotions
 * Category: custom-acf-blocks
 * Icon: tag
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
		'remove_paddings' => get_field( 'remove_paddings' ),
		'offers'          => $offers,
	)
);

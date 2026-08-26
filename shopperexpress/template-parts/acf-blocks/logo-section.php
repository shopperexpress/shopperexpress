<?php
/**
 * Block: Logo Section
 *
 * Title: Logo Section
 * Description: Logo grid with configurable columns and optional background colour.
 * Keywords: logos brands partners grid
 * Category: custom-acf-blocks
 * Icon: screenoptions
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
		'heading'          => get_field( 'heading' ),
		'logos_backgorund' => get_field( 'logos_backgorund' ),
		'logos_per_row'    => get_field( 'logos_per_row' ),
		'remove_paddings'  => get_field( 'remove_paddings' ),
		'logos'            => $logos,
	)
);

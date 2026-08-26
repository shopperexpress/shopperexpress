<?php
/**
 * Block: Buttons
 *
 * Title: Buttons
 * Description: Info section with a list of button codes.
 * Keywords: buttons info list
 * Category: custom-acf-blocks
 * Icon: button
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

$buttons = array();
if ( have_rows( 'buttons' ) ) {
	while ( have_rows( 'buttons' ) ) {
		the_row();
		$buttons[] = array(
			'button_code' => get_sub_field( 'button_code' ),
		);
	}
}

get_template_part(
	'template-parts/acf-shared/buttons',
	null,
	array(
		'buttons'    => $buttons,
		'is_preview' => $is_preview,
	)
);

<?php
/**
 * Block: Form
 *
 * Title: Form
 * Description: WPForms embed section with optional heading and text.
 * Keywords: form contact wpforms
 * Category: custom-acf-blocks
 * Icon: forms
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

get_template_part(
	'template-parts/acf-shared/form',
	null,
	array(
		'title' => get_field( 'title' ),
		'text'  => get_field( 'text' ),
		'form'  => get_field( 'form' ),
	)
);

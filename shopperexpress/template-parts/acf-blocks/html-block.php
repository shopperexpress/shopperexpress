<?php
/**
 * Block: HTML Block
 *
 * Title: HTML Block
 * Description: Custom HTML/embed section with optional container and background.
 * Keywords: html embed code custom
 * Category: custom-acf-blocks
 * Icon: editor-code
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
	'template-parts/acf-shared/html-block',
	null,
	array(
		'is_container'        => get_field( 'is_container' ),
		'css_class'           => get_field( 'css_class' ),
		'add_grey_background' => get_field( 'add_grey_background' ),
		'remove_paddings'     => get_field( 'remove_paddings' ),
		'html'                => get_field( 'html' ),
	)
);

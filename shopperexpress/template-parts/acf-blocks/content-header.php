<?php
/**
 * Block: Content Header
 *
 * Title: Content Header
 * Description: Logo, heading, and text block for page headers.
 * Keywords: header content logo title
 * Category: custom-acf-blocks
 * Icon: heading
 *
 * @package ShopperExpress
 */

if ( \App\Components\Gutenberg\Block_Preview_Helper::render( $block ) ) {
	return;
}

get_template_part(
	'template-parts/acf-shared/content-header',
	null,
	array(
		'logo_image' => get_field( 'logo_image' ),
		'title'      => get_field( 'title' ),
		'text'       => get_field( 'text' ),
		'is_preview' => $is_preview,
	)
);

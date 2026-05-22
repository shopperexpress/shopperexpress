<?php
/**
 * Block: Content Block
 *
 * Title: Content Block
 * Description: Cash-offer style content block with icon, text, and images.
 * Keywords: content block cash offer
 * Category: custom-acf-blocks
 * Icon: align-left
 *
 * @package ShopperExpress
 */

if ( \App\Components\Gutenberg\Block_Preview_Helper::render( $block ) ) {
	return;
}

get_template_part(
	'template-parts/acf-shared/content-block',
	null,
	array(
		'icon_image'   => get_field( 'icon_image' ),
		'title'        => get_field( 'title' ),
		'text'         => get_field( 'text' ),
		'link'         => get_field( 'link' ),
		'first_image'  => get_field( 'first_image' ),
		'second_image' => get_field( 'second_image' ),
		'is_preview'   => $is_preview,
	)
);

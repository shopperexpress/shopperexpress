<?php
/**
 * Block: Full Width Image
 *
 * Title: Full Width Image
 * Description: Responsive full-width image with optional link and mobile variant.
 * Keywords: image fullwidth banner
 * Category: custom-acf-blocks
 * Icon: format-image
 *
 * @package ShopperExpress
 */

if ( \App\Components\Gutenberg\Block_Preview_Helper::render( $block ) ) {
	return;
}

$image        = get_field( 'image' );
$image_mobile = get_field( 'image_mobile' );

get_template_part(
	'template-parts/acf-shared/full-width-image',
	null,
	array(
		'image'           => $image,
		'image_mobile'    => $image_mobile ?: $image,
		'remove_paddings' => get_field( 'remove_paddings' ),
		'url'             => get_field( 'url' ),
		'open_in_new_tab' => get_field( 'open_in_new_tab' ),
	)
);

<?php
/**
 * Block: Block Logo
 *
 * Title: Block Logo
 * Description: Large logo image display block.
 * Keywords: logo image brand
 * Category: custom-acf-blocks
 * Icon: format-image
 *
 * @package ShopperExpress
 */

if ( \App\Components\Gutenberg\Block_Preview_Helper::render( $block ) ) {
	return;
}

get_template_part(
	'template-parts/acf-shared/block-logo',
	null,
	array(
		'logo' => get_field( 'logo' ),
	)
);

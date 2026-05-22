<?php
/**
 * Flexible Content Wrapper: Full Width Image Section
 *
 * @package ShopperExpress
 */

$image        = get_sub_field( 'image' );
$image_mobile = get_sub_field( 'image_mobile' );

get_template_part(
	'template-parts/acf-shared/full-width-image',
	null,
	array(
		'image'           => $image,
		'image_mobile'    => $image_mobile ?: $image,
		'remove_paddings' => get_sub_field( 'remove_paddings' ),
		'url'             => get_sub_field( 'url' ),
		'open_in_new_tab' => get_sub_field( 'open_in_new_tab' ),
	)
);

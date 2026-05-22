<?php
/**
 * Flexible Content Wrapper: Gallery
 *
 * @package ShopperExpress
 */

get_template_part(
	'template-parts/acf-shared/gallery',
	null,
	array(
		'gray_background' => get_sub_field( 'gray_background' ),
		'full_width'      => get_sub_field( 'full_width' ),
		'remove_paddings' => get_sub_field( 'remove_paddings' ),
		'text'            => get_sub_field( 'text' ),
		'images'          => get_sub_field( 'gallery' ),
		'row_index'       => get_row_index(),
	)
);

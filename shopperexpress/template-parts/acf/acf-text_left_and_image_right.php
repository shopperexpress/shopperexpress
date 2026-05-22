<?php
/**
 * Flexible Content Wrapper: Text Left and Image Right
 *
 * @package ShopperExpress
 */

get_template_part(
	'template-parts/acf-shared/image-left-text-right',
	null,
	array(
		'layout_class'       => '',
		'icon'               => get_sub_field( 'icon' ),
		'title'              => get_sub_field( 'title' ),
		'subtitle'           => get_sub_field( 'subtitle' ),
		'image'              => get_sub_field( 'image' ),
		'image_decor'        => get_sub_field( 'image_decor' ),
		'decor_position'     => get_sub_field( 'decor_position' ),
		'bottom_button_code' => get_sub_field( 'bottom_button_code' ),
		'additional_image'   => get_sub_field( 'additional_image' ),
		'centered_image'     => get_sub_field( 'centered_image' ),
		'text'               => get_sub_field( 'text' ),
	)
);

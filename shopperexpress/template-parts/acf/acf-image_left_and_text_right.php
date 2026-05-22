<?php
/**
 * Flexible Content Wrapper: Image Left and Text Right
 *
 * @package ShopperExpress
 */

$layout       = get_row_layout();
$layout_class = ( 'image_left_and_text_right' === $layout ) ? ' trade ' : '';

get_template_part(
	'template-parts/acf-shared/image-left-text-right',
	null,
	array(
		'layout_class'       => $layout_class,
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

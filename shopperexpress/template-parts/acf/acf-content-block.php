<?php
/**
 * Flexible Content Wrapper: Content Block
 *
 * @package ShopperExpress
 */

get_template_part(
	'template-parts/acf-shared/content-block',
	null,
	array(
		'icon_image'   => get_sub_field( 'icon_image' ),
		'title'        => get_sub_field( 'title' ),
		'text'         => get_sub_field( 'text' ),
		'link'         => get_sub_field( 'link' ),
		'first_image'  => get_sub_field( 'first_image' ),
		'second_image' => get_sub_field( 'second_image' ),
	)
);

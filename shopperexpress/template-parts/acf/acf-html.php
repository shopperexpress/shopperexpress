<?php
/**
 * Flexible Content Wrapper: HTML Block
 *
 * @package ShopperExpress
 */

get_template_part(
	'template-parts/acf-shared/html-block',
	null,
	array(
		'is_container'        => get_sub_field( 'is_container' ),
		'css_class'           => get_sub_field( 'css_class' ),
		'add_grey_background' => get_sub_field( 'add_grey_background' ),
		'remove_paddings'     => get_sub_field( 'remove_paddings' ),
		'html'                => get_sub_field( 'html' ),
	)
);

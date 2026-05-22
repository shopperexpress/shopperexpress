<?php
/**
 * Flexible Content Wrapper: Info Images
 *
 * @package ShopperExpress
 */

get_template_part(
	'template-parts/acf-shared/info-images',
	null,
	array(
		'title'  => get_sub_field( 'title' ),
		'images' => get_sub_field( 'images' ),
	)
);

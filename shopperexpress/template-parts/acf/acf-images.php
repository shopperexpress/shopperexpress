<?php
/**
 * Flexible Content Wrapper: Images
 *
 * @package ShopperExpress
 */

get_template_part(
	'template-parts/acf-shared/images',
	null,
	array(
		'images' => get_sub_field( 'images' ),
		'anchor' => get_sub_field( 'anchor' ),
	)
);

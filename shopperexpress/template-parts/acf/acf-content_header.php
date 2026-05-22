<?php
/**
 * Flexible Content Wrapper: Content Header
 *
 * @package ShopperExpress
 */

get_template_part(
	'template-parts/acf-shared/content-header',
	null,
	array(
		'logo_image' => get_sub_field( 'logo_image' ),
		'title'      => get_sub_field( 'title' ),
		'text'       => get_sub_field( 'text' ),
	)
);

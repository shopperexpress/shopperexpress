<?php
/**
 * Flexible Content Wrapper: Block Logo
 *
 * @package ShopperExpress
 */

get_template_part(
	'template-parts/acf-shared/block-logo',
	null,
	array(
		'logo' => get_sub_field( 'logo' ),
	)
);

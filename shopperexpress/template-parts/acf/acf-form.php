<?php
/**
 * Flexible Content Wrapper: Form
 *
 * @package ShopperExpress
 */

get_template_part(
	'template-parts/acf-shared/form',
	null,
	array(
		'title' => get_sub_field( 'title' ),
		'text'  => get_sub_field( 'text' ),
		'form'  => get_sub_field( 'form' ),
	)
);

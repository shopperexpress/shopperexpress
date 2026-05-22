<?php
/**
 * Flexible Content Wrapper: Sub Footer Section
 *
 * @package ShopperExpress
 */

get_template_part(
	'template-parts/acf-shared/sub-footer',
	null,
	array(
		'main_title'     => get_sub_field( 'main_title' ),
		'addresses_list' => get_sub_field( 'addresses_list' ),
		'schedule_title' => get_sub_field( 'schedule_title' ),
		'schedule_list'  => get_sub_field( 'schedule_list' ),
	)
);

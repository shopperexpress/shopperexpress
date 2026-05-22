<?php
/**
 * Flexible Content Wrapper: Tabs Slider
 *
 * @package ShopperExpress
 */

get_template_part(
	'template-parts/acf-shared/tabs-slider',
	null,
	array(
		'heading'                    => get_sub_field( 'heading' ),
		'sort_by_number_of_vehicles' => get_sub_field( 'sort_by_number_of_vehicles' ),
		'index'                      => get_row_index(),
	)
);

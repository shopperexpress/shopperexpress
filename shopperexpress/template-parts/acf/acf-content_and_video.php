<?php
/**
 * Flexible Content Wrapper: Content and Video
 *
 * @package ShopperExpress
 */

get_template_part(
	'template-parts/acf-shared/content-and-video',
	null,
	array(
		'top_image' => get_sub_field( 'top_image' ),
		'html'      => get_sub_field( 'html' ),
		'wistia_id' => get_sub_field( 'wistia_id' ),
	)
);

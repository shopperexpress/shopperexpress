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
		'top_image'           => get_sub_field( 'top_image' ),
		'html'                => get_sub_field( 'html' ),
		'primary_video'       => get_sub_field( 'primary_video' ),
		'override_video'      => get_sub_field( 'override_video' ),
		'override_start_date' => get_sub_field( 'override_start_date' ),
		'override_end_date'   => get_sub_field( 'override_end_date' ),
	)
);

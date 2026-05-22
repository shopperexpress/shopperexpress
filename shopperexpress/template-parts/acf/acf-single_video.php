<?php
/**
 * Flexible Content Wrapper: Single Video
 *
 * @package ShopperExpress
 */

get_template_part(
	'template-parts/acf-shared/single-video',
	null,
	array(
		'video_code'    => get_sub_field( 'video_code' ),
		'remove_margin' => get_sub_field( 'remove_margin' ),
	)
);

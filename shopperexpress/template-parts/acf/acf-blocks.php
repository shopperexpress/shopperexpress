<?php
/**
 * Flexible Content Wrapper: Blocks (Intro Blocks)
 *
 * @package ShopperExpress
 */

get_template_part(
	'template-parts/acf-shared/intro-blocks',
	null,
	array(
		'icon'                  => get_sub_field( 'icon' ),
		'title_intro'           => get_sub_field( 'title_intro' ),
		'content_intro'         => get_sub_field( 'content_intro' ),
		'script_for_link_intro' => get_sub_field( 'script_for_link_intro' ),
		'title_for_link_intro'  => get_sub_field( 'title_for_link_intro' ),
		'first_image_intro'     => get_sub_field( 'first_image_intro' ),
		'second_image_intro'    => get_sub_field( 'second_image_intro' ),
		'left_text'             => get_sub_field( 'left_text' ),
		'right_text'            => get_sub_field( 'right_text' ),
		'left_image'            => get_sub_field( 'left_image' ),
		'right_image'           => get_sub_field( 'right_image' ),
		'icon2'                 => get_sub_field( 'icon2' ),
		'title_block'           => get_sub_field( 'title_block' ),
		'content_block'         => get_sub_field( 'content_block' ),
		'script_for_link'       => get_sub_field( 'script_for_link' ),
		'title_for_link'        => get_sub_field( 'title_for_link' ),
		'first_image_block'     => get_sub_field( 'first_image_block' ),
		'second_image_block'    => get_sub_field( 'second_image_block' ),
	)
);

<?php
/**
 * Template for displaying vehicle offer card.
 *
 * @param array $args Array of arguments containing post ID and other data.
 *
 * @package Shopperexpress
 */

$post_id = ! empty( $args['post_id'] ) ? $args['post_id'] : get_the_id();
get_template_part(
	'template-parts/content',
	'listings',
	array(
		'post_type' => '-used-listings',
		'post_id'   => $post_id,
	)
);

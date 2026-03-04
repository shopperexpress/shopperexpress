<?php
/**
 * Template part for displaying archive used listings
 *
 * @package Shopperexpress
 */

$page_id = ! empty( $args['page_id'] ) ? $args['page_id'] : '';
get_template_part(
	'archive',
	'listings',
	array(
		'post_type' => 'used-listings',
		'page_id'   => $page_id,
	)
);

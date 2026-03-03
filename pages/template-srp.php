<?php
/*
Template Name: SRP Template
*/

$post_type = get_field( 'post_type' ) ?? 'listings';

get_template_part(
	'archive',
	$post_type,
	array(
		'post_type' => $post_type,
		'page_id'   => get_the_id(),
		'template'  => 'srp',
	)
);

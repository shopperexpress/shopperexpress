<?php
/**
 * Template for displaying vehicle offer card.
 *
 * @param array $args Array of arguments containing post ID and other data.
 *
 * @package Shopperexpress
 */

$post_id = ! empty( $args['post_id'] ) ? absint( $args['post_id'] ) : get_the_id();
$action  = ! empty( $args['action'] ) ? array(
	'post_id' => $post_id,
	'action'  => $args['action'],
) : array( 'post_id' => $post_id );

get_template_part( 'template-parts/content', 'lease-offers', $action );

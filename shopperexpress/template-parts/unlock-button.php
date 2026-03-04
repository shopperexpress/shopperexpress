<?php
/**
 * Template part for displaying unlock button
 *
 * @package Shopperexpress
 */

$args    = ! empty( $args ) ? $args : array();
$post_id = ! empty( $args['post_id'] ) ? $args['post_id'] : get_the_id();
$loged   = ! empty( $args['loged'] ) ? $args['loged'] : is_user_logged_in();
$show    = ! empty( $args['show-image'] ) ? $args['show-image'] : 'true';
$field   = 'used-listings' == get_post_type( $post_id ) ? '-used-listings' : '';

if ( in_array( get_post_type( $post_id ), array( 'listings', 'used-listings', 'offers', 'service-offers' ) ) ) {
	if ( in_array( get_post_type( $post_id ), array( 'listings', 'used-listings' ) ) && 'true' == $show ) {
		while ( have_rows( 'custom_image' . $field, 'options' ) ) :
			the_row();
			$image = get_sub_field( 'image' );
			$class = get_sub_field( 'hide_on_mobile' ) ? 'animate-img d-none d-md-block' : 'animate-img';
			$attr  = get_sub_field( 'fade' ) ? array( 'class' => $class ) : array();

			if ( get_sub_field( 'show_custom_image' ) && $image ) {
				echo wp_kses_post( get_attachment_image( $image['id'], 'full', $attr ) );
			}
		endwhile;
	}

	if ( ! wps_auth() && ! $loged ) :
		while ( have_rows( 'unlock_button_' . get_post_type( $post_id ), 'options' ) ) :
			the_row();
			get_template_part( 'template-parts/components/button', null, array( 'post_id' => $post_id ) );
		endwhile;
	else :
		while ( have_rows( 'contact_button_' . get_post_type( $post_id ), 'options' ) ) :
			the_row();
			get_template_part( 'template-parts/components/button', null, array( 'post_id' => $post_id ) );
		endwhile;
	endif;

	if ( ! is_single( $post_id ) ) {
		while ( have_rows( 'vdp_button_' . get_post_type( $post_id ), 'options' ) ) :
			the_row();
			get_template_part(
				'template-parts/components/button',
				null,
				array(
					'post_id' => $post_id,
					'link'    => get_permalink( $post_id ),
				)
			);
		endwhile;
	}
}

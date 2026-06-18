<?php
/**
 * API-mode equivalent of template-parts/unlock-button.php
 *
 * Uses $post_type, $permalink, and $is_single from $args instead of relying
 * on WP post functions (get_post_type, is_single, get_permalink).
 *
 * Accepts $args:
 *   post_type  (string) — 'listings' or 'used-listings'
 *   permalink  (string) — full VDP URL (used for "View Details" on SRP)
 *   is_single  (bool)   — true on VDP, false on SRP
 *   loged      (string) — 'true' | '' (from SRP JS; falls back to is_user_logged_in)
 *   show-image (string) — 'true' (default) | 'false' — whether to render custom images
 *   vin        (string) — VIN passed as post_id to the button (API mode)
 *
 * @package Shopperexpress
 */

$post_type = $args['post_type'] ?? 'listings';
$permalink = $args['permalink'] ?? '#';
$is_single = ! empty( $args['is_single'] );
$show      = ( $args['show-image'] ?? 'true' ) !== 'false';
$loged     = ! empty( $args['loged'] ) ? $args['loged'] : is_user_logged_in();
$post_id   = $args['post_id'] ?? '';

if ( ! in_array( $post_type, array( 'listings', 'used-listings', 'offers', 'service-offers' ), true ) ) {
	return;
}

$field    = 'used-listings' === $post_type ? '-used-listings' : '';
$single   = $is_single ? '-vdp' : '';
$position = get_field( 'cta_animation_position' . $field . $single, 'options' );

$custom_img_html = '';

if ( in_array( $post_type, array( 'listings', 'used-listings' ), true ) && $show ) {
	ob_start();
	while ( have_rows( 'custom_image' . $field, 'options' ) ) :
		the_row();
		$image = get_sub_field( 'image' );
		$class = get_sub_field( 'hide_on_mobile' ) ? 'animate-img d-none d-md-block' : 'animate-img';
		$attr  = get_sub_field( 'fade' ) ? array( 'class' => $class ) : array();

		if ( get_sub_field( 'show_custom_image' ) && $image ) {
			echo wp_kses_post( get_attachment_image( $image['id'], 'full', $attr ) );
		}
	endwhile;
	$custom_img_html = ob_get_clean();
}

if ( 'above' === $position ) {
	echo wp_kses_post( $custom_img_html );
}

if ( ! wps_auth() && ! $loged ) :
	while ( have_rows( 'unlock_button_' . $post_type, 'options' ) ) :
		the_row();
		get_template_part(
			'template-parts/components/button',
			null,
			array(
				'post_id'   => $post_id,
				'is_single' => $is_single,
			)
		);
	endwhile;
else :
	while ( have_rows( 'contact_button_' . $post_type, 'options' ) ) :
		the_row();
		get_template_part(
			'template-parts/components/button',
			null,
			array(
				'post_id'   => $post_id,
				'is_single' => $is_single,
			)
		);
	endwhile;
endif;

// "View Details" button on SRP — links to VDP via permalink from API.
if ( ! $is_single ) :
	while ( have_rows( 'vdp_button_' . $post_type, 'options' ) ) :
		the_row();
		get_template_part(
			'template-parts/components/button',
			null,
			array(
				'post_id' => 0,
				'link'    => $permalink,
			)
		);
	endwhile;
endif;

if ( 'below' === $position ) {
	echo wp_kses_post( $custom_img_html );
}

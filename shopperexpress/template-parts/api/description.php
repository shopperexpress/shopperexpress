<?php
/**
 * API-mode equivalent of template-parts/description-block.php
 *
 * ACF options loop is identical to the original. AI description is read from
 * the Intice payload fields instead of post meta.
 *
 * Accepts $args:
 *   vehicle   (array)  — Intice API vehicle object
 *   post_type (string) — 'listings' or 'used-listings'
 *   type      (string) — 'srp' | 'vdp' | 'single' | 'both'
 *
 * @package Shopperexpress
 */

$vehicle   = $args['vehicle']   ?? array();
$post_type = $args['post_type'] ?? 'listings';
$type      = $args['type']      ?? 'srp';

// ACF options description block — identical logic to description-block.php.
if ( have_rows( 'description_block', 'options' ) ) :
	while ( have_rows( 'description_block', 'options' ) ) :
		the_row();
		$listing_type = get_sub_field( 'listing_type' );
		$show_on      = get_sub_field( 'show_on' );

		if ( ( $post_type === $listing_type || 'both' === $listing_type ) && ( $type === $show_on || 'both' === $show_on ) ) :
			$text         = get_sub_field( 'text' );
			$font_size    = get_sub_field( 'font_size' );
			$font_styling = get_sub_field( 'font_styling' );
			$weight       = get_sub_field( 'weight' );

			$style = '';
			if ( $font_size ) {
				$style .= 'font-size:' . esc_attr( $font_size ) . 'px;';
			}
			if ( $font_styling ) {
				$style .= 'font-family:' . esc_attr( $font_styling ) . ';';
			}
			if ( $weight ) {
				$style .= 'font-weight:' . esc_attr( $weight ) . ';';
			}
			get_font_family( $font_styling );

			if ( $text ) :
				?>
				<div class="info-block__description"<?php echo $style ? ' style="' . esc_attr( $style ) . '"' : ''; ?>>
					<?php echo wp_kses_post( $text ); ?>
				</div>
				<?php
			endif;
		endif;
	endwhile;
endif;

// AI description from Intice payload (replaces _ai_vdp_description post meta).
if ( 'vdp' === $type || 'single' === $type ) :
	$payload  = $vehicle['payload'] ?? array();
	$ai_desc  = $payload['ai_vdp_description'] ?? '';

	if ( $ai_desc ) :
		echo wp_kses_post( $ai_desc );
	endif;
endif;

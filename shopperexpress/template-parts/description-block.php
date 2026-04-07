<?php
/**
 * The template part for displaying the Description Block.
 *
 * @package Maps
 */

$post_type = ! empty( $args['post_type'] ) ? $args['post_type'] : 'listing';
$type      = ! empty( $args['type'] ) ? $args['type'] : 'single';

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
			?>
			<?php if ( $text ) : ?>
				<div class="info-block__description" <?php echo $style ? ' style="' . esc_attr( $style ) . '"' : ''; ?>>
					<?php echo wp_kses_post( $text ); ?>
				</div>
				<?php
			endif;
		endif;
	endwhile;
endif;

<?php
/**
 * Template part for displaying button
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Shopperexpress
 */

$post_id = ! empty( $args['post_id'] ) ? $args['post_id'] : get_the_id();
$show    = is_single( $post_id ) ? get_sub_field( 'show_single' ) : get_sub_field( 'show' );
$link    = ! empty( $args['link'] ) ? $args['link'] : '';
if ( $show ) :
	$style        = '';
	$font_size    = is_single( $post_id ) ? get_sub_field( 'font_size_single' ) : get_sub_field( 'font_size' );
	$animation    = get_sub_field( 'animation' ) ? 'animate-btn ' : null;
	$font_styling = is_single( $post_id ) ? get_sub_field( 'font_styling_single' ) : get_sub_field( 'font_styling' );
	$weight       = is_single( $post_id ) ? get_sub_field( 'weight_single' ) : get_sub_field( 'weight' );
	if ( $background_color = get_sub_field( 'background_color' ) ) {
		$style .= 'background-color: ' . $background_color . '; border-color: ' . $background_color . ';';
	}
	if ( $text_color = get_sub_field( 'text_color' ) ) {
		$style .= 'color: ' . $text_color . ';';
	}
	if ( $font_size ) {
		$style .= 'font-size: ' . $font_size . 'px;';
	}
	if ( $font_styling ) {
		$style .= 'font-family: ' . $font_styling . ';';
	}
	if ( $weight ) {
		$style .= 'font-weight: ' . $weight . ';';
	}
	$style        = $style ? ' style="' . $style . '"' : '';
	$fonts_family = '';

	if ( $font_styling ) :
		switch ( $font_styling ) {
			case 'Roboto Condensed':
				$fonts_family = 'Roboto+Condensed:wght@100..900';
				break;
			case 'Roboto Mono':
				$fonts_family = 'Roboto+Mono:wght@100..700';
				break;
			case 'Poppins':
				$fonts_family = 'Poppins:wght@400;600;700';
				break;
			case 'Lato':
				$fonts_family = 'Lato:wght@400;700';
				break;
			case 'PT Sans':
				$fonts_family = 'PT+Sans:ital,wght@0,400;0,700;1,400;1,700';
				break;
			case 'PT Sans Narrow':
				$fonts_family = 'PT+Sans+Narrow:wght@400;700';
				break;
			case 'Inter':
				$fonts_family = 'Inter:opsz,wght@14..32,100..900';
				break;
		}
		if ( ! empty( $fonts_family ) ) :
			?>
			<style>
				@import url('https://fonts.googleapis.com/css2?family=<?php echo $fonts_family; ?>&display=swap');
			</style>
			<?php
		endif;
	endif;
	?>
	<button
		type="button"
		<?php
		if ( ! is_single() ) {
			echo 'data-post="' . $post_id . '"';}
		?>
		class="btn btn-primary btn-custom btn-block <?php echo $animation; ?>"
		<?php
		if ( $form_id = get_sub_field( 'form_id' ) ) :
			?>
			data-form-id="<?php echo $form_id; ?>" <?php endif; ?>
		<?php if ( ! $link ) : ?>
		data-toggle="modal" data-target="#unlockSavingsModal"
		<?php else : ?>
		onclick="window.location.href='<?php echo esc_url( $link ); ?>'"
		<?php endif; ?>
		<?php echo $style; ?>>
		<?php the_sub_field( 'svg_icon' ); ?>
		<?php the_sub_field( 'title' ); ?>
	</button>
<?php endif; ?>

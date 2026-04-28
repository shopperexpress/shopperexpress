<?php
/**
 * Template part for displaying payment list
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Shopperexpress
 */

$post_id   = ! empty( $args['post_id'] ) ? $args['post_id'] : get_the_id();
$post_type = ! empty( $args['post_type'] ) ? $args['post_type'] : get_post_type( $post_id );
$style     = ! empty( $args['style'] ) ? $args['style'] : 'single';
$bottom    = ! empty( $args['single-bottom'] ) ? true : false;

while ( have_rows( 'payment_list', 'options' ) ) :
	the_row();
	$row = get_row_layout();
	switch ( $style ) {
		case 'single':
			$show = get_sub_field( 'show_on_vdp' );
			break;

		default:
			$show = get_sub_field( 'show_on_srp' );
			break;
	}
	$vehicle_type = get_sub_field( 'vehicle_type' );

	if ( 'payment' === $row ) :
		$show_payment = get_sub_field( 'show_payment' );
		$add_asterisk = get_sub_field( 'add_asterisk' ) ? '*' : null;
		$price        = get_sub_field( 'show_text_if_less_than' ) ? get_sub_field( 'price' ) : 0;
		$show_symbol  = get_sub_field( 'show_symbol' );
		$show_symbol  = ! empty( $show_symbol ) && 'none' !== $show_symbol ? $show_symbol : '';
		$font_size    = is_single() ? get_sub_field( 'vdp_font_size' ) : get_sub_field( 'srp_font_size' );
		$text_color   = get_sub_field( 'text_color' );
		$style_attr   = '';
		if ( $text_color || $font_size ) {
			$style_attr .= ' style="';
			if ( $text_color ) {
				$style_attr .= 'color: ' . esc_attr( $text_color ) . '; ';
			}
			if ( $font_size ) {
				$style_attr .= 'font-size: ' . esc_attr( $font_size ) . 'px; ';
			}
			$style_attr .= '"';
		}

		if ( ( $post_type === $vehicle_type || 'All' === $vehicle_type ) && $show && 'hidden' !== $show_payment ) :
			$payment_type     = get_sub_field( 'payment_type' );
			$heading          = get_sub_field( 'heading' ) ? get_sub_field( 'heading' ) : $payment_type['label'];
			$is_cross_through = get_sub_field( 'text_cross_through' );

			if ( 'comment1' === $payment_type['value'] || 'comment2' === $payment_type['value'] ) {
				$payment = '<span class="price-spr-primary" ' . $style_attr . '>' . get_field( $payment_type['value'], $post_id ) . '</span>';
			} elseif ( get_field( $payment_type['value'], $post_id ) == false || intval( get_field( $payment_type['value'], $post_id ) ) <= $price ) {
					$payment = get_sub_field( 'svg_icon_condition' ) . get_sub_field( 'title_condition' );
				if ( 'original_price' === $payment_type['value'] ) {
					$payment = '<span class="price-spr" ' . $style_attr . '>' . $payment . '</span>';
				} else {
					$payment = '<span class="price-spr-primary" ' . $style_attr . '>' . $payment . '</span>';
				}
			} else {
				$payment = $show_symbol . '$' . number_format( intval( get_field( $payment_type['value'], $post_id ) ) ) . $add_asterisk;
				if ( $is_cross_through ) {
					if ( 'original_price' === $payment_type['value'] ) {
						$payment = '<s class="price-spr" ' . $style_attr . '>' . $payment . '</s>';
					} else {
						$payment = '<s class="price-spr-primary" ' . $style_attr . ' ' . $style_attr . '>' . $payment . '</s>';
					}
				} elseif ( 'original_price' === $payment_type['value'] ) {
						$payment = '<span class="price-spr" ' . $style_attr . '>' . $payment . '</s>';
				} else {
					$payment = '<span class="price-spr-primary" ' . $style_attr . '>' . $payment . '</s>';
				}
			}
			?>
			<li class="
			<?php
			if ( 'visible' === $show_payment ) :
				?>
				show<?php endif; ?>">
				<?php if ( $bottom ) : ?>
					<h4><?php echo $heading; ?></h4>
					<div class="summary-list__row">
						<?php echo wpautop( get_sub_field( 'description' ) ); ?>
						<strong class="price" <?php echo $style_attr; ?>><?php echo $payment; ?></strong>
					</div>
				<?php else : ?>
					<a href="#" data-post="<?php echo $post_id; ?>" data-toggle="modal" data-target="#unlockSavingsModal">
						<?php if ( $style == 'single' ) : ?>
							<div class="text-holder">
								<h4 class="h3"><?php echo $heading; ?></h4>
								<?php echo wpautop( get_sub_field( 'description' ) ); ?>
							</div>
							<?php if ( $show_payment == 'visible' || wps_auth() ) : ?>
								<strong class="price" <?php echo $style_attr; ?>><?php echo $payment; ?></strong>
							<?php else : ?>
								<span class="btn btn-primary unlock-item" data-toggle="modal" data-target="#unlockSavingsModal"><?php the_sub_field( 'lock_svg_icon' ); ?><?php the_sub_field( 'lock_text' ); ?></span>
							<?php endif; ?>
						<?php else : ?>
							<strong class="dt"><?php echo $heading; ?></strong>
							<strong class="price" <?php echo $style_attr; ?>><?php echo $payment; ?></strong>
							<span class="btn btn-primary unlock-item"><?php the_sub_field( 'lock_svg_icon' ); ?><?php the_sub_field( 'lock_text' ); ?></span>
						<?php endif ?>
					</a>
				<?php endif; ?>
			</li>
			<?php
		endif;
	elseif ( 'enhanced_payment' === $row ) :
		$title        = get_sub_field( 'title' );
		$lock         = get_sub_field( 'lock' );
		$show_payment = get_sub_field( 'show_payment' );
		$payment      = null;
		$payment      = get_field( $show_payment, $post_id );
		if ( $payment >= 0 ) {
			$payment = '$' . number_format( intval( $payment ) );
			if ( $after_payment = get_sub_field( 'after_payment' ) ) {
				$payment .= '<sub>' . $after_payment . '</sub>';
			}
		}
		$lock_icon = get_sub_field( 'lock_svg_icon' );
		$lock_text = get_sub_field( 'lock_text' );
		$onclick   = array();

		if ( get_sub_field( 'show_event' ) ) :
			while ( have_rows( 'events' ) ) :
				the_row();
				$onclick[] = str_replace( 'VIN', get_field( 'vin_number', $post_id ), get_sub_field( 'event' ) );
			endwhile;
		endif;

		if ( ( $post_type === $vehicle_type || 'All' === $vehicle_type ) && $show && $payment ) :
			?>
			<li class="
			<?php
			if ( ! $lock ) :
				?>
				show<?php endif; ?>" 
				<?php
				if ( $onclick ) :
					?>
				onclick="<?php echo implode( ' ', $onclick ); ?>" <?php endif; ?>>
				<?php if ( $bottom ) : ?>
					<h4><?php echo $title; ?></h4>
					<div class="summary-list__row">
						<?php echo wpautop( get_sub_field( 'description' ) ); ?>
						<strong class="price"><?php echo $payment; ?></strong>
					</div>
				<?php else : ?>
					<a href="#" 
					<?php
					if ( $lock ) :
						?>
						data-post="<?php echo $post_id; ?>" data-toggle="modal" data-target="#unlockSavingsModal" <?php endif; ?>>
						<?php if ( 'single' === $style ) : ?>
							<div class="text-holder">
								<?php if ( $title ) : ?>
									<h4 class="h3"><?php echo esc_html( $title ); ?></h4>
									<?php
								endif;
								echo wpautop( get_sub_field( 'description' ) );
								?>
							</div>
							<?php if ( ! $lock || wps_auth() ) : ?>
								<strong class="price"><?php echo $payment; ?></strong>
							<?php else : ?>
								<span class="btn btn-primary unlock-item" data-toggle="modal" data-target="#unlockSavingsModal"><?php echo $lock_icon; ?><?php echo esc_html( $lock_text ); ?></span>
							<?php endif; ?>
						<?php else : ?>
							<strong class="dt"><?php echo esc_html( $title ); ?></strong>
							<?php if ( $payment ) : ?>
								<strong class="price"><?php echo $payment; ?></strong>
							<?php endif ?>
							<span class="btn btn-primary unlock-item"><?php echo esc_html( $lock_text ); ?></span>
						<?php endif; ?>
					</a>
				<?php endif; ?>
			</li>
			<?php
		endif;
	endif;
endwhile;

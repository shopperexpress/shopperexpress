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
$style     = ! empty( $args['style'] ) ? true : false;
$html_1    = $html_2 = null;

while ( have_rows( 'payment_list_new', 'options' ) ) :
	the_row();
	$text_color   = get_sub_field( 'text_color' ) ? ' style="color: ' . esc_attr( get_sub_field( 'text_color' ) ) . ' ;"' : '';
	$active       = get_sub_field( 'active' );
	$start_date   = get_sub_field( 'start_date' );
	$end_date     = get_sub_field( 'end_date' );
	$current_date = date( 'Ymd' );
	$show_symbol  = get_sub_field( 'show_symbol' ) ? '- ' : '';
	$show         = $active ? $current_date >= $start_date && $current_date <= $end_date : true;
	if ( get_sub_field( 'vehicle_type' ) == $post_type && $show ) :
		$result = true;
		if ( $search = strtolower( get_sub_field( 'search' ) ) ) :
			$tax = array();
			switch ( get_sub_field( 'search_in' ) ) {
				case 1:
					$tax[] = 'year-' . $post_type;
					break;
				case 2:
					$tax[] = 'make-' . $post_type;
					break;
				case 3:
					$tax[] = 'model-' . $post_type;
					$tax[] = 'trim-' . $post_type;
					break;
				case 4:
					$tax[] = 'year-' . $post_type;
					$tax[] = 'make-' . $post_type;
					break;
				case 5:
					$tax[] = 'year-' . $post_type;
					$tax[] = 'make-' . $post_type;
					$tax[] = 'model-' . $post_type;
					break;
				case 6:
					$tax[] = 'year-' . $post_type;
					$tax[] = 'make-' . $post_type;
					$tax[] = 'model-' . $post_type;
					$tax[] = 'trim-' . $post_type;
					break;
			}

			switch ( get_sub_field( 'rule_to_show' ) ) {
				case 1:
					foreach ( $tax as $item ) {
						$result = strpos( strtolower( $search ), strtolower( wps_get_term( $post_id, $item ) ) ) !== false;
						if ( $result == true ) {
							break;
						}
					}
					break;
				case 2:
					foreach ( $tax as $item ) {
						$result = strtolower( wps_get_term( $post_id, $item ) ) === $search;
						if ( $result == true ) {
							$result = false;
							break;
						} else {
							$result = true;
							break;
						}
					}

					break;
				case 3:
					$taxItems = array();
					foreach ( $tax as $item ) {
						$taxItems[] = wps_get_term( $post_id, $item );
					}
					$result = ! empty( $taxItems ) && strtolower( implode( ' ', $taxItems ) ) == strtolower( $search ) ? true : false;
					break;
			}
		endif;
		if ( true === $result ) :
			$heading     = get_sub_field( 'heading' );
			$description = get_sub_field( 'description' );

			$value = get_sub_field( 'value' );

			if ( get_sub_field( 'select_value_type' ) == 2 ) {
				$calculated_value = wps_get_term( $post_id, strtolower( get_sub_field( 'calculated_value' ) ) );

				switch ( get_sub_field( 'operator' ) ) {
					case 'Subtract':
						$value = intval( $calculated_value ) - intval( $value );
						break;

					case 'Add':
						$value = intval( $calculated_value ) + intval( $value );
						break;
				}

				$value = ! empty( $value ) && $value != 0 && $value > 0 ? $value : null;
			} elseif ( get_sub_field( 'select_value_type' ) == 3 ) {
				$calculated_value = wps_get_term( $post_id, strtolower( get_sub_field( 'calculated_value' ) ) );
				$value_from_field = wps_get_term( $post_id, strtolower( get_sub_field( 'value_from_field' ) ) );
				$value_1          = get_sub_field( 'value_1' );
				$result           = '';
				switch ( get_sub_field( 'operator_1' ) ) {
					case '>':
						$result = intval( $value_from_field ) > intval( $value_1 );
						break;
					case '<':
						$result = intval( $value_from_field ) < intval( $value_1 );
						break;
					case '=':
						$result = intval( $value_from_field ) == intval( $value_1 );
						break;
					case '!=':
						$result = intval( $value_from_field ) != intval( $value_1 );
						break;
				}
				if ( ! empty( $result ) && $result != 0 ) {
					switch ( get_sub_field( 'operator' ) ) {
						case 'Subtract':
							$value = intval( $calculated_value ) - intval( $value );
							break;

						case 'Add':
							$value = intval( $calculated_value ) + intval( $value );
							break;
					}

					$value = ! empty( $value ) && $value != 0 && $value > 0 ? $value : null;
				} else {
					$value = '';
				}
			}

			if ( $args['is_single'] ) {
				$show_block = get_sub_field( 'show_on_vdp' );
			} else {
				$show_block = get_sub_field( 'show_on_srp' );
			}

			if ( $value && $heading && $show_block ) :
				$small_pricing_block = get_sub_field( 'small_pricing_block' );
				ob_start();
				?>
				<li class="show
				<?php
				if ( $small_pricing_block ) :
					?>
					text--sm<?php endif; ?>">
					<a href="#" data-content="<?php echo strip_tags( get_sub_field( 'pop_up_details' ) ); ?>" data-toggle="modal" data-target="#popUpDetails">
						<?php
						if ( $style ) :
							if ( $heading ) :
								?>
								<strong class="dt"><?php echo $heading; ?></strong>
								<?php
							endif;
						else :
							?>
							<div class="text-holder">
								<?php if ( $heading ) : ?>
									<h4 class="h3"><?php echo $heading; ?></h4>
									<?php
								endif;
								if ( ! $small_pricing_block ) {
									echo $description;
								}
								?>
							</div>
							<?php
						endif;
						if ( $value ) :
							$value = $show_symbol . '$' . number_format( intval( $value ) );
							$value = get_sub_field( 'cross_heading' ) ? '<s>' . $value . '</s>' : $value;
							?>
							<strong class="price"><span class="
							<?php
							if ( $style ) :
								?>
								price-spr
								<?php
						else :
							?>
								market-price<?php endif; ?>" <?php echo $text_color; ?>><?php echo $value; ?></span></strong>
						<?php endif; ?>
					</a>
				</li>
				<?php
				if ( $small_pricing_block ) :
					$html_2 .= ob_get_clean();
				else :
					$html_1 .= ob_get_clean();
				endif;
			endif;
		endif;
	endif;
endwhile;

echo $html_1;

if ( $html_2 ) :
	?>
	</ul>
	<ul class="payment-info">
	<?php
	echo $html_2;
endif;

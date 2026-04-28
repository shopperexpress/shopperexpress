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
$style     = ! empty( $args['style'] );
$is_single = ! empty( $args['is_single'] );
$html_1    = '';
$html_2    = '';

// search_in field values.
$search_in_map = array(
	1 => array( 'year' ),
	2 => array( 'make' ),
	3 => array( 'model', 'trim' ),
	4 => array( 'year', 'make' ),
	5 => array( 'year', 'make', 'model' ),
	6 => array( 'year', 'make', 'model', 'trim' ),
);

while ( have_rows( 'payment_list_new', 'options' ) ) :
	the_row();

	$active       = get_sub_field( 'active' );
	$start_date   = get_sub_field( 'start_date' );
	$end_date     = get_sub_field( 'end_date' );
	$current_date = date_i18n( 'Ymd' );
	$show         = $active ? ( $current_date >= $start_date && $current_date <= $end_date ) : true;

	if ( get_sub_field( 'vehicle_type' ) !== $post_type || ! $show ) :
		continue;
	endif;

	$result = true;
	$search = strtolower( (string) get_sub_field( 'search' ) );

	if ( $search ) :
		$search_in = (int) get_sub_field( 'search_in' );
		$tax       = isset( $search_in_map[ $search_in ] ) ? $search_in_map[ $search_in ] : array();
		$rule      = (int) get_sub_field( 'rule_to_show' );

		switch ( $rule ) {
			case 1:
				$result = false;
				foreach ( $tax as $item ) {
					if ( false !== strpos( $search, strtolower( (string) get_field( $item, $post_id ) ) ) ) {
						$result = true;
						break;
					}
				}
				break;

			case 2:
				$result = true;
				foreach ( $tax as $item ) {
					if ( strtolower( (string) get_field( $item, $post_id ) ) === $search ) {
						$result = false;
						break;
					}
				}
				break;

			case 3:
				$tax_values = array();
				foreach ( $tax as $item ) {
					$tax_values[] = (string) get_field( $item, $post_id );
				}
				$result = ! empty( $tax_values ) && strtolower( implode( ' ', $tax_values ) ) === $search;
				break;
		}
	endif;

	if ( true !== $result ) :
		continue;
	endif;

	$heading           = get_sub_field( 'heading' );
	$description       = get_sub_field( 'description' );
	$value             = get_sub_field( 'value' );
	$select_value_type = (int) get_sub_field( 'select_value_type' );

	if ( 2 === $select_value_type || 4 === $select_value_type ) {
		$calculated_value = (int) get_field( strtolower( (string) get_sub_field( 'calculated_value' ) ), $post_id );
		$operand          = 2 === $select_value_type
			? (int) $value
			: (int) get_field( strtolower( (string) get_sub_field( 'calculated_field' ) ), $post_id );

		switch ( get_sub_field( 'operator' ) ) {
			case 'Subtract':
				$value = $calculated_value - $operand;
				break;
			case 'Add':
				$value = $calculated_value + $operand;
				break;
		}

		$value = ( ! empty( $value ) && (int) $value > 0 ) ? $value : null;

	} elseif ( 3 === $select_value_type ) {
		$calculated_value = (int) get_field( strtolower( (string) get_sub_field( 'calculated_value' ) ), $post_id );
		$value_from_field = (int) get_field( strtolower( (string) get_sub_field( 'value_from_field' ) ), $post_id );
		$value_1          = (int) get_sub_field( 'value_1' );
		$condition        = false;

		switch ( get_sub_field( 'operator_1' ) ) {
			case '>':
				$condition = $value_from_field > $value_1;
				break;
			case '<':
				$condition = $value_from_field < $value_1;
				break;
			case '=':
				$condition = $value_from_field === $value_1;
				break;
			case '!=':
				$condition = $value_from_field !== $value_1;
				break;
		}

		if ( $condition ) {
			switch ( get_sub_field( 'operator' ) ) {
				case 'Subtract':
					$value = $calculated_value - (int) $value;
					break;
				case 'Add':
					$value = $calculated_value + (int) $value;
					break;
			}
			$value = ( ! empty( $value ) && (int) $value > 0 ) ? $value : null;
		} else {
			$value = '';
		}
	}

	$show_block = $is_single ? get_sub_field( 'show_on_vdp' ) : get_sub_field( 'show_on_srp' );

	if ( ! $value || ! $heading || ! $show_block ) :
		continue;
	endif;

	$font_size  = is_single() ? get_sub_field( 'vdp_font_size' ) : get_sub_field( 'srp_font_size' );
	$text_color = get_sub_field( 'text_color' );
	$style_attr = '';
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
	$show_symbol         = get_sub_field( 'show_symbol' );
	$show_symbol         = ! empty( $show_symbol ) && 'none' !== $show_symbol ? $show_symbol : '';
	$small_pricing_block = get_sub_field( 'small_pricing_block' );

	$formatted_value = $show_symbol . '$' . number_format( (int) $value );
	if ( get_sub_field( 'cross_heading' ) ) {
		$formatted_value = '<s>' . $formatted_value . '</s>';
	}

	$price_class = $style ? 'price-spr' : 'market-price';

	ob_start();
	?>
	<li class="show<?php echo $small_pricing_block ? ' text--sm' : ''; ?>">
		<a href="#" data-content="<?php echo esc_attr( wp_strip_all_tags( (string) get_sub_field( 'pop_up_details' ) ) ); ?>" data-toggle="modal" data-target="#popUpDetails">
			<?php if ( $style ) : ?>
				<strong class="dt"><?php echo esc_html( $heading ); ?></strong>
			<?php else : ?>
				<div class="text-holder">
					<h4 class="h3"><?php echo esc_html( $heading ); ?></h4>
					<?php if ( ! $small_pricing_block ) : ?>
						<?php echo wp_kses_post( $description ); ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<strong class="price">
				<span class="<?php echo esc_attr( $price_class ); ?>"<?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?>>
					<?php echo wp_kses( $formatted_value, array( 's' => array() ) ); ?>
				</span>
			</strong>
		</a>
	</li>
	<?php
	if ( $small_pricing_block ) :
		$html_2 .= ob_get_clean();
	else :
		$html_1 .= ob_get_clean();
	endif;

endwhile;

echo $html_1; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with escaped parts above

if ( $html_2 ) :
	?>
	</ul>
	<ul class="payment-info">
	<?php
	echo $html_2; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with escaped parts above
endif;

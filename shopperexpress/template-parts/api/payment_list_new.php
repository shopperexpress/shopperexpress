<?php
/**
 * API-mode equivalent of template-parts/components/payment_list_new.php
 *
 * Reads vehicle fields directly from the Intice API vehicle array instead of
 * ACF post fields. Matching logic (search_in, rule_to_show) and display logic
 * are identical to the original — only the data source changes.
 *
 * Accepts $args:
 *   vehicle   (array)  — Intice API vehicle object
 *   post_type (string) — 'listings' or 'used-listings'
 *   style     (string) — 'archive' (SRP) | '' (VDP)
 *   is_single (bool)   — true on VDP, false on SRP
 *   loged     (string) — 'true' if user is logged in. Passed in explicitly on the
 *                         SRP (archive) card, which is rendered inside a REST API
 *                         callback where WordPress resets the current user to 0
 *                         because the request carries no X-WP-Nonce header — so
 *                         wps_auth()/is_user_logged_in() can't be trusted there.
 *                         On the VDP (normal page load) this arg is not passed
 *                         and wps_auth() is used directly.
 *
 * @package Shopperexpress
 */

$vehicle   = $args['vehicle'] ?? array();
$post_type = $args['post_type'] ?? 'listings';
$style     = ! empty( $args['style'] );
$is_single = ! empty( $args['is_single'] );
$is_authed = isset( $args['loged'] ) ? 'true' === $args['loged'] : wps_auth();

if ( empty( $vehicle ) ) {
	return;
}

$payload = $vehicle['payload'] ?? array();

// Map ACF field name → Intice API value for matching and calculations.
$field_map = array(
	'year'           => (string) ( $vehicle['year'] ?? '' ),
	'make'           => (string) ( $vehicle['make'] ?? '' ),
	'model'          => (string) ( $vehicle['model'] ?? '' ),
	'trim'           => (string) ( $vehicle['trim'] ?? '' ),
	'loan_payment'   => (int) ( $payload['loan_payment'] ?? $payload['loan_payment_sort'] ?? 0 ),
	'lease_payment'  => (int) ( $payload['lease_payment'] ?? $payload['lease_payment_sort'] ?? 0 ),
	'original_price' => (int) ( $vehicle['msrp'] ?? 0 ),
	'price'          => (int) ( $vehicle['price_sort'] ?? $vehicle['price'] ?? 0 ),
	'price_sort'     => (int) ( $vehicle['price_sort'] ?? $vehicle['price'] ?? 0 ),
	'down_payment'   => (int) ( $payload['down_payment'] ?? 0 ),
);

// search_in field values — same as original.
$search_in_map = array(
	1 => array( 'year' ),
	2 => array( 'make' ),
	3 => array( 'model', 'trim' ),
	4 => array( 'year', 'make' ),
	5 => array( 'year', 'make', 'model' ),
	6 => array( 'year', 'make', 'model', 'trim' ),
);

$html_1 = '';
$html_2 = '';

while ( have_rows( 'payment_list_new', 'options' ) ) :
	the_row();

	$active       = get_sub_field( 'active' );
	$show_payment = get_sub_field( 'show_payment' );
	$start_date   = get_sub_field( 'start_date' );
	$end_date     = get_sub_field( 'end_date' );
	$custom_text  = get_sub_field( 'custom_text' );
	$current_date = date_i18n( 'Ymd' );
	$show         = $active ? ( $current_date >= $start_date && $current_date <= $end_date ) : true;

	if ( get_sub_field( 'vehicle_type' ) !== $post_type || ! $show ) :
		continue;
	endif;

	$result = true;
	$search = strtolower( (string) get_sub_field( 'search' ) );

	if ( $search ) :
		$search_in = (int) get_sub_field( 'search_in' );
		$tax       = $search_in_map[ $search_in ] ?? array();
		$rule      = (int) get_sub_field( 'rule_to_show' );

		switch ( $rule ) {
			case 1:
				$result = false;
				foreach ( $tax as $item ) {
					if ( false !== strpos( $search, strtolower( $field_map[ $item ] ?? '' ) ) ) {
						$result = true;
						break;
					}
				}
				break;

			case 2:
				$result = true;
				foreach ( $tax as $item ) {
					if ( strtolower( $field_map[ $item ] ?? '' ) === $search ) {
						$result = false;
						break;
					}
				}
				break;

			case 3:
				$tax_values = array();
				foreach ( $tax as $item ) {
					$tax_values[] = (string) ( $field_map[ $item ] ?? '' );
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
		$calculated_key = strtolower( (string) get_sub_field( 'calculated_value' ) );
		$calculated     = (int) ( $field_map[ $calculated_key ] ?? 0 );
		$operand        = 2 === $select_value_type
			? (int) $value
			: (int) ( $field_map[ strtolower( (string) get_sub_field( 'calculated_field' ) ) ] ?? 0 );

		switch ( get_sub_field( 'operator' ) ) {
			case 'Subtract':
				$value = $calculated - $operand;
				break;
			case 'Add':
				$value = $calculated + $operand;
				break;
		}

		$value = ( ! empty( $value ) && (int) $value > 0 ) ? $value : null;

	} elseif ( 3 === $select_value_type || 5 === $select_value_type ) {
		$from_key   = strtolower( (string) get_sub_field( 'value_from_field' ) );
		$from_value = (int) ( $field_map[ $from_key ] ?? 0 );
		$value_1    = 5 === $select_value_type
			? (int) get_sub_field( 'value_1' )
			: (int) ( $field_map[ strtolower( (string) get_sub_field( 'calculated_field_1' ) ) ] ?? 0 );
		$condition  = false;

		switch ( get_sub_field( 'operator_1' ) ) {
			case '>':
				$condition = $from_value > $value_1;
				break;
			case '<':
				$condition = $from_value < $value_1;
				break;
			case '=':
				$condition = $from_value === $value_1;
				break;
			case '!=':
				$condition = $from_value !== $value_1;
				break;
		}

		if ( $condition ) {
			$calculated_key = strtolower( (string) get_sub_field( 'calculated_value' ) );
			$calculated     = (int) ( $field_map[ $calculated_key ] ?? 0 );
			$operand        = 3 === $select_value_type
				? (int) $value
				: (int) ( $field_map[ strtolower( (string) get_sub_field( 'calculated_field' ) ) ] ?? 0 );

			switch ( get_sub_field( 'operator' ) ) {
				case 'Subtract':
					$value = $calculated - $operand;
					break;
				case 'Add':
					$value = $calculated + $operand;
					break;
			}

			$value = ( ! empty( $value ) && (int) $value > 0 ) ? $value : null;
		} else {
			$value = $custom_text;
		}
	}

	$show_block = $is_single ? get_sub_field( 'show_on_vdp' ) : get_sub_field( 'show_on_srp' );
	$show_state = ( 'locked' === $show_payment && ! $is_authed )
		|| ( 'unlocked' === $show_payment && $is_authed )
		|| 'both' === $show_payment;

	if ( ! $value || ! $heading || ! $show_block || ! $show_state ) :
		continue;
	endif;

	$get_style = get_sub_field( ( $is_single ? 'vdp_' : 'srp_' ) . 'style' );
	$style_row = array();

	if ( isset( $get_style['padding_top'] ) && '' !== $get_style['padding_top'] ) {
		$style_row[] = 'padding-top:' . $get_style['padding_top'] . 'px;';
	}
	if ( isset( $get_style['padding_bottom'] ) && '' !== $get_style['padding_bottom'] ) {
		$style_row[] = 'padding-bottom:' . $get_style['padding_bottom'] . 'px;';
	}
	if ( isset( $get_style['min_height'] ) && '' !== $get_style['min_height'] ) {
		$style_row[] = 'min-height:' . $get_style['min_height'] . 'px;';
	}

	$style_row_attr = ! empty( $style_row ) ? ' style="' . implode( '', $style_row ) . '"' : '';

	foreach ( array( 'title', 'description', 'price' ) as $key ) {
		${$key . '_style'} = ! empty( $get_style[ $key ] )
			? build_style_attr( $get_style[ $key ] )
			: '';
	}

	$show_symbol         = get_sub_field( 'show_symbol' );
	$show_symbol         = ! empty( $show_symbol ) && 'none' !== $show_symbol ? $show_symbol : '';
	$small_pricing_block = get_sub_field( 'small_pricing_block' );

	if ( is_numeric( $value ) ) {
		$formatted_value = $show_symbol . '$' . number_format( (int) $value );
		if ( get_sub_field( 'cross_heading' ) ) {
			$formatted_value = '<s>' . $formatted_value . '</s>';
		}
	} else {
		$formatted_value = $value;
	}

	$price_class = $style ? 'price-spr' : 'market-price';

	ob_start();
	?>
	<li class="show<?php echo $small_pricing_block ? ' text--sm' : ''; ?>"<?php echo $style_row_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<a href="#" data-content='<?php echo wp_json_encode( wp_kses_post( get_sub_field( 'pop_up_details' ) ) ); ?>' data-toggle="modal" data-target="#popUpDetails">
			<?php if ( $style ) : ?>
				<strong class="dt"<?php echo $title_style; // phpcs:ignore ?>><?php echo esc_html( $heading ); ?></strong>
			<?php else : ?>
				<div class="text-holder">
					<h4 class="h3"<?php echo $title_style; // phpcs:ignore ?>><?php echo esc_html( $heading ); ?></h4>
					<?php if ( ! $small_pricing_block ) : ?>
						<p<?php echo $description_style; // phpcs:ignore ?>><?php echo esc_html( $description ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<strong class="price">
				<span class="<?php echo esc_attr( $price_class ); ?>"<?php echo $price_style; // phpcs:ignore ?>>
					<?php echo $formatted_value; // phpcs:ignore ?>
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

echo $html_1; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

if ( $html_2 ) :
	?>
	</ul>
	<ul class="payment-info">
	<?php
	echo $html_2; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
endif;

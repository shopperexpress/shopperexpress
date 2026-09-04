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
 *
 * Locked and unlocked rows are both always rendered into markup (this template can
 * be served from a full-page cache, so a server-side auth check baked into the
 * cache would go stale for other visitors). Each row gets a `pay-locked` or
 * `pay-unlocked` class, and CSS shows/hides them against the `logged-in` class on
 * `<body>`, which is corrected client-side from the real auth cookie on every load.
 *
 * @package Shopperexpress
 */

$vehicle   = $args['vehicle'] ?? array();
$post_type = $args['post_type'] ?? 'listings';
$style     = ! empty( $args['style'] );
$is_single = ! empty( $args['is_single'] );

if ( empty( $vehicle ) ) {
	return;
}

$payload = $vehicle['payload'] ?? array();

// A few ACF field-name tokens (from the "Calculated Value"/"Value from field"
// select choices) don't match their Nexus payload key 1:1 — map each to the
// candidate key(s) to actually look up, tried in order.
$field_aliases = array(
	'original_price' => array( 'msrp' ),
	'price'          => array( 'price_sort', 'price' ),
	'price_sort'     => array( 'price_sort', 'price' ),
	'loan_payment'   => array( 'loan_payment', 'loan_payment_sort' ),
	'lease_payment'  => array( 'lease_payment', 'lease_payment_sort' ),
);

/**
 * Resolve an ACF-field-name-style token (as picked in the "Calculated Value" /
 * "Value from field" select, or the year/make/model/trim matching rules)
 * against the raw Intice vehicle array — top-level fields first, then the
 * dealer-mapped `payload` bag. Same fallback pattern as
 * Intice_Rest::build_terms()/resolve_field(). Unlike a hardcoded allowlist,
 * this resolves ANY payload key (invoiceamount, holdback, cost, pack, ...)
 * without needing a matching map entry.
 *
 * @param string $key Lowercased field token, e.g. 'year', 'invoiceamount', 'holdback'.
 * @return string
 */
$resolve_field = function ( string $key ) use ( $vehicle, $payload, $field_aliases ) {
	foreach ( $field_aliases[ $key ] ?? array( $key ) as $candidate ) {
		$value = $vehicle[ $candidate ] ?? ( $payload[ $candidate ] ?? null );

		if ( null !== $value && '' !== $value ) {
			return (string) $value;
		}
	}

	return '';
};

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
					if ( false !== strpos( $search, strtolower( $resolve_field( $item ) ) ) ) {
						$result = true;
						break;
					}
				}
				break;

			case 2:
				$result = true;
				foreach ( $tax as $item ) {
					if ( strtolower( $resolve_field( $item ) ) === $search ) {
						$result = false;
						break;
					}
				}
				break;

			case 3:
				$tax_values = array();
				foreach ( $tax as $item ) {
					$tax_values[] = $resolve_field( $item );
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
		$calculated     = (int) $resolve_field( $calculated_key );
		$operand        = 2 === $select_value_type
			? (int) $value
			: (int) $resolve_field( strtolower( (string) get_sub_field( 'calculated_field' ) ) );

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
		$from_value = (int) $resolve_field( $from_key );
		$value_1    = 5 === $select_value_type
			? (int) get_sub_field( 'value_1' )
			: (int) $resolve_field( strtolower( (string) get_sub_field( 'calculated_field_1' ) ) );
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
			$calculated     = (int) $resolve_field( $calculated_key );
			$operand        = 3 === $select_value_type
				? (int) $value
				: (int) $resolve_field( strtolower( (string) get_sub_field( 'calculated_field' ) ) );

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

	if ( ! $value || ! $heading || ! $show_block ) :
		continue;
	endif;

	// Both locked and unlocked rows are always rendered — this markup can be served
	// from a full-page cache, so the actual show/hide decision is made purely with
	// CSS against the real-time "logged-in" class the client sets from its own cookie.
	$auth_class = '';
	if ( 'locked' === $show_payment ) {
		$auth_class = ' pay-locked';
	} elseif ( 'unlocked' === $show_payment ) {
		$auth_class = ' pay-unlocked';
	}

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
	<li class="show<?php echo esc_attr( $auth_class ); ?><?php echo $small_pricing_block ? ' text--sm' : ''; ?>"<?php echo $style_row_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
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

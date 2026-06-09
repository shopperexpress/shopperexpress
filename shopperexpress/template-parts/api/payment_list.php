<?php
/**
 * API-mode equivalent of template-parts/components/payment_list.php
 *
 * Reads payment values from the Intice API vehicle array instead of ACF post fields.
 * Renders the same HTML so existing CSS continues to work.
 *
 * Accepts $args:
 *   vehicle   (array)  — Intice API vehicle object
 *   post_type (string) — 'listings' or 'used-listings'
 *   style     (string) — 'single' (VDP) | 'archive' (SRP)
 *   bottom    (bool)   — sticky summary variant
 *
 * @package Shopperexpress
 */

$vehicle   = $args['vehicle']   ?? array();
$post_type = $args['post_type'] ?? 'listings';
$style     = $args['style']     ?? 'single';
$bottom    = ! empty( $args['single-bottom'] );

if ( empty( $vehicle ) ) {
	return;
}

$vin     = strtoupper( $vehicle['vin'] ?? '' );
$payload = $vehicle['payload'] ?? array();

// Map ACF payment_type values → Intice API fields.
$field_map = array(
	'loan_payment'   => (float) ( $payload['loan_payment']        ?? $payload['loan_payment_sort']   ?? 0 ),
	'lease_payment'  => (float) ( $payload['lease_payment']       ?? $payload['lease_payment_sort']  ?? 0 ),
	'original_price' => (float) ( $vehicle['msrp']                ?? 0 ),
	'price'          => (float) ( $vehicle['price_sort']          ?? $vehicle['price']                ?? 0 ),
	'price_sort'     => (float) ( $vehicle['price_sort']          ?? $vehicle['price']                ?? 0 ),
	'down_payment'   => (float) ( $payload['down_payment']        ?? 0 ),
	'comment1'       => (string) ( $payload['comment1']           ?? $payload['special field 1']     ?? '' ),
	'comment2'       => (string) ( $payload['comment2']           ?? $payload['special field 2']     ?? '' ),
);

while ( have_rows( 'payment_list', 'options' ) ) :
	the_row();
	$row = get_row_layout();

	$show = ( 'single' === $style )
		? get_sub_field( 'show_on_vdp' )
		: get_sub_field( 'show_on_srp' );

	$vehicle_type = get_sub_field( 'vehicle_type' );

	if ( 'payment' === $row ) :
		$show_payment = get_sub_field( 'show_payment' );
		$add_asterisk = get_sub_field( 'add_asterisk' ) ? '*' : null;
		$price_limit  = get_sub_field( 'show_text_if_less_than' ) ? get_sub_field( 'price' ) : 0;
		$show_symbol  = get_sub_field( 'show_symbol' );
		$show_symbol  = ! empty( $show_symbol ) && 'none' !== $show_symbol ? $show_symbol : '';
		$font_size    = ( 'single' === $style ) ? get_sub_field( 'vdp_font_size' ) : get_sub_field( 'srp_font_size' );
		$text_color   = get_sub_field( 'text_color' );
		$style_attr   = '';

		if ( $text_color || $font_size ) {
			$style_attr = ' style="';
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
			$heading          = get_sub_field( 'heading' ) ?: $payment_type['label'];
			$is_cross_through = get_sub_field( 'text_cross_through' );
			$type_key         = $payment_type['value'] ?? '';
			$field_value      = $field_map[ $type_key ] ?? null;

			if ( 'comment1' === $type_key || 'comment2' === $type_key ) {
				$payment = '<span class="price-spr-primary"' . $style_attr . '>' . esc_html( (string) $field_value ) . '</span>';
			} elseif ( ! $field_value || (float) $field_value <= (float) $price_limit ) {
				$payment = get_sub_field( 'svg_icon_condition' ) . get_sub_field( 'title_condition' );
				if ( 'original_price' === $type_key ) {
					$payment = '<span class="price-spr"' . $style_attr . '>' . $payment . '</span>';
				} else {
					$payment = '<span class="price-spr-primary"' . $style_attr . '>' . $payment . '</span>';
				}
			} else {
				$payment = $show_symbol . '$' . number_format( (int) $field_value ) . $add_asterisk;
				if ( $is_cross_through ) {
					$tag     = 'original_price' === $type_key ? 'price-spr' : 'price-spr-primary';
					$payment = '<s class="' . $tag . '"' . $style_attr . '>' . $payment . '</s>';
				} elseif ( 'original_price' === $type_key ) {
					$payment = '<span class="price-spr"' . $style_attr . '>' . $payment . '</span>';
				} else {
					$payment = '<span class="price-spr-primary"' . $style_attr . '>' . $payment . '</span>';
				}
			}
			?>
			<li class="<?php echo 'visible' === $show_payment ? 'show' : ''; ?>">
				<?php if ( $bottom ) : ?>
					<h4><?php echo esc_html( $heading ); ?></h4>
					<div class="summary-list__row">
						<?php echo wpautop( get_sub_field( 'description' ) ); // phpcs:ignore ?>
						<strong class="price"<?php echo $style_attr; // phpcs:ignore ?>><?php echo $payment; // phpcs:ignore ?></strong>
					</div>
				<?php else : ?>
					<a href="#" data-post="<?php echo esc_attr( $vin ); ?>" data-toggle="modal" data-target="#unlockSavingsModal">
						<?php if ( 'single' === $style ) : ?>
							<div class="text-holder">
								<h4 class="h3"><?php echo esc_html( $heading ); ?></h4>
								<?php echo wpautop( get_sub_field( 'description' ) ); // phpcs:ignore ?>
							</div>
							<?php if ( 'visible' === $show_payment || wps_auth() ) : ?>
								<strong class="price"<?php echo $style_attr; // phpcs:ignore ?>><?php echo $payment; // phpcs:ignore ?></strong>
							<?php else : ?>
								<span class="btn btn-primary unlock-item" data-toggle="modal" data-target="#unlockSavingsModal">
									<?php the_sub_field( 'lock_svg_icon' ); ?>
									<?php the_sub_field( 'lock_text' ); ?>
								</span>
							<?php endif; ?>
						<?php else : ?>
							<strong class="dt"><?php echo esc_html( $heading ); ?></strong>
							<strong class="price"<?php echo $style_attr; // phpcs:ignore ?>><?php echo $payment; // phpcs:ignore ?></strong>
							<span class="btn btn-primary unlock-item">
								<?php the_sub_field( 'lock_svg_icon' ); ?>
								<?php the_sub_field( 'lock_text' ); ?>
							</span>
						<?php endif; ?>
					</a>
				<?php endif; ?>
			</li>
			<?php
		endif;

	elseif ( 'enhanced_payment' === $row ) :
		$title        = get_sub_field( 'title' );
		$lock         = get_sub_field( 'lock' );
		$show_payment = get_sub_field( 'show_payment' );
		$lock_icon    = get_sub_field( 'lock_svg_icon' );
		$lock_text    = get_sub_field( 'lock_text' );
		$raw_value    = $field_map[ strtolower( (string) $show_payment ) ] ?? null;
		$payment      = null;

		if ( $raw_value !== null && (float) $raw_value >= 0 ) {
			$payment = '$' . number_format( (int) $raw_value );
			$after   = get_sub_field( 'after_payment' );
			if ( $after ) {
				$payment .= '<sub>' . esc_html( $after ) . '</sub>';
			}
		}

		if ( ( $post_type === $vehicle_type || 'All' === $vehicle_type ) && $show && $payment ) :
			?>
			<li class="<?php echo ! $lock ? 'show' : ''; ?>">
				<a href="#"<?php echo $lock ? ' data-post="' . esc_attr( $vin ) . '" data-toggle="modal" data-target="#unlockSavingsModal"' : ''; ?>>
					<?php if ( 'single' === $style ) : ?>
						<div class="text-holder">
							<?php if ( $title ) : ?>
								<h4 class="h3"><?php echo esc_html( $title ); ?></h4>
							<?php endif; ?>
							<?php echo wpautop( get_sub_field( 'description' ) ); // phpcs:ignore ?>
						</div>
						<?php if ( ! $lock || wps_auth() ) : ?>
							<strong class="price"><?php echo $payment; // phpcs:ignore ?></strong>
						<?php else : ?>
							<span class="btn btn-primary unlock-item" data-toggle="modal" data-target="#unlockSavingsModal">
								<?php echo $lock_icon; // phpcs:ignore ?>
								<?php echo esc_html( $lock_text ); ?>
							</span>
						<?php endif; ?>
					<?php else : ?>
						<strong class="dt"><?php echo esc_html( $title ); ?></strong>
						<?php if ( $payment ) : ?>
							<strong class="price"><?php echo $payment; // phpcs:ignore ?></strong>
						<?php endif; ?>
						<span class="btn btn-primary unlock-item"><?php echo esc_html( $lock_text ); ?></span>
					<?php endif; ?>
				</a>
			</li>
			<?php
		endif;
	endif;
endwhile;

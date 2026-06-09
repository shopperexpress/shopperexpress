<?php
/**
 * Vehicle card for API mode (Intice Nexus data source).
 *
 * Accepts $args:
 *   vehicle   (array)  — Intice API vehicle object
 *   post_type (string) — 'listings' or 'used-listings'
 *   permalink (string) — WP VDP URL (from VIN lookup) or '#'
 *   loged     (string) — 'true' if user is logged in
 *
 * @package Shopperexpress
 */

$vehicle   = $args['vehicle'] ?? array();
$post_type = $args['post_type'] ?? 'listings';
$permalink = $args['permalink'] ?? '#';
$loged     = $args['loged'] ?? '';

if ( empty( $vehicle ) ) {
	return;
}

$year           = $vehicle['year'] ?? '';
$make           = $vehicle['make'] ?? '';
$model          = $vehicle['model'] ?? '';
$trim           = $vehicle['trim'] ?? '';
$drivetrain     = $vehicle['drivetrain'] ?? '';
$exterior_color = $vehicle['exterior_color'] ?? '';
$mileage        = $vehicle['mileage'] ?? 0;
$price          = $vehicle['price'] ?? 0;
$vin            = strtoupper( $vehicle['vin'] ?? '' );

$thumb     = $vehicle['thumb'] ?? ( $vehicle['image'] ?? '' );
$features  = $vehicle['features'] ?? array();
$sold      = ! empty( $vehicle['sold'] );
$certified = ! empty( $vehicle['certified'] );
$alt_array = array( $year, $make, $model, $trim, $exterior_color, '- ' . get_bloginfo( 'name' ) . ' - Image' );

// Payment data from payload (import-template fields).
$payload      = $vehicle['payload'] ?? array();
$loan_payment = $payload['loan_payment_sort'] ?? ( $payload['loan_payment'] ?? 0 );
$status       = $payload['special field 3'] ?? '';

$price_display   = $price ? '$' . number_format( (int) $price ) : '';
$payment_display = $loan_payment ? '$' . number_format( (int) $loan_payment ) . '/mo' : '';

$aria_label = implode(
	' ',
	array_filter( array( esc_html__( 'Go to', 'shopperexpress' ), $year, $make, $model, $drivetrain, $trim, 'page' ) )
);

$alt = implode( ' ', array_filter( array( $year, $make, $model, $trim, $exterior_color, '- ' . get_bloginfo( 'name' ) . ' - Image' ) ) );
?>
<div class="col-sm-6 col-lg-4 col-xxl-3">
	<div class="card">
		<div class="card-body">
			<a class="ghost-link" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $aria_label ); ?>"></a>
			<div class="card-head">
				<div class="card-head__holder">
					<span class="card-brand"><?php echo esc_html( $year . ' ' . $make ); ?></span>
					<?php
					if ( $vin ) {
						echo '<button class="api-favorite-button" data-postid="' . esc_attr( $vin ) . '" data-posttype="' . esc_attr( $post_type ) . '"><i class="sf-icon-star-empty"></i></button>';
					}
					?>
				</div>
				<strong class="card-model"><?php echo esc_html( trim( $model . ' ' . $drivetrain . ' ' . $trim ) ); ?></strong>
			</div>
			<?php
			get_template_part(
				'template-parts/api/gallery',
				null,
				array(
					'vehicle'   => $vehicle,
					'post_type' => $post_type,
					'is_single' => false,
				)
			);
			?>
			<?php if ( $status ) : ?>
				<div class="badges-list">
					<span class="card-badge-status"><?php echo esc_html( $status ); ?></span>
				</div>
			<?php endif; ?>
			<!-- Detail info -->
			<?php if ( have_rows( 'listings-detail_srp_detail_info', 'options' ) ) : ?>
				<dl class="card-detail">
					<?php
					while ( have_rows( 'listings-detail_srp_detail_info', 'options' ) ) :
						the_row();
						$label = get_sub_field( 'label' );
						$value = get_sub_field( 'value' );

						if ( ! empty( $value ) ) {

							$result = preg_replace_callback(
								'/\b([a-z_]+)\b/',
								function ( $match ) use ( $vehicle ) {

									$field = $match[1];
									if ( 'vin_number' === $field ) {
										$field = 'vin';
									} elseif ( 'stock_number' === $field ) {
										$field = 'stock';
									} elseif ( 'miles_display' === $field ) {
										$field = 'mileage';
									}

									$value = $vehicle[ $field ];

									if ( $value !== null && $value !== '' ) {
										return $value;
									} else {
										return '';
									}
								},
								$value
							);
						}
						if ( ! empty( $result ) && ! empty( $label ) ) :
							if ( $label ) :
								?>
								<dt><?php echo esc_html( $label ); ?></dt>
							<?php endif; ?>
							<dd
								<?php
								if ( str_contains( strtolower( $label ), 'vin' ) ) :
									?>
								class="vin" <?php endif; ?>>
								<?php echo str_replace( '&nbsp;', ' ', esc_html( $result ) ); ?>
							</dd>
							<?php
						endif;
					endwhile;
					?>
				</dl>
				<?php
			endif;
			?>
			<!-- Disclosure -->
			<div class="card-info-row">
				<?php if ( get_field( 'comment_footer', 'options' ) && ! get_field( 'hide_disclosure_srp', 'option' ) ) : ?>
					<button class="btn-disclosure" data-toggle="modal" data-target="#detailModal">
						<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
							<path d="M480-280q17 0 28.5-11.5T520-320v-160q0-17-11.5-28.5T480-520q-17 0-28.5 11.5T440-480v160q0 17 11.5 28.5T480-280Zm0-320q17 0 28.5-11.5T520-640q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640q0 17 11.5 28.5T480-600Zm0 520q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z" />
						</svg>
						<?php esc_html_e( 'Disclosure', 'shopperexpress' ); ?>
					</button>
				<?php endif; ?>
			</div>
			<ul class="payment-info">
				<?php
				get_template_part(
					'template-parts/api/payment_list',
					null,
					array(
						'vehicle'   => $vehicle,
						'post_type' => $post_type,
						'style'     => 'archive',
					)
				);
				get_template_part(
					'template-parts/api/payment_list_new',
					null,
					array(
						'vehicle'   => $vehicle,
						'post_type' => $post_type,
						'style'     => 'archive',
						'is_single' => false,
					)
				);
				?>
			</ul>
			<?php
			get_template_part(
				'template-parts/api/description',
				null,
				array(
					'vehicle'   => $vehicle,
					'post_type' => $post_type,
					'type'      => 'srp',
				)
			);
			get_template_part(
				'template-parts/api/unlock',
				null,
				array(
					'post_type' => $post_type,
					'permalink' => $permalink,
					'is_single' => false,
					'loged'     => $loged,
					'vin'       => $vin,
				)
			);

			get_template_part(
				'template-parts/api/conversion_block',
				null,
				array(
					'vehicle'   => $vehicle,
					'post_type' => $post_type,
				)
			);
			?>
		</div>
	</div>
</div>

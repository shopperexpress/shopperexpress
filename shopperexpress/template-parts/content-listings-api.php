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

// Resolved directly from payload.use_images_list (images_primary/images_srp),
// not Nexus's own active_image_list — see \App\resolve_vehicle_gallery().
// Same source used by the VDP gallery, template-parts/api/gallery.php.
$thumb     = \App\resolve_vehicle_gallery( $vehicle )[0]['url'] ?? ( $vehicle['thumb'] ?? ( $vehicle['image'] ?? '' ) );
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
					<?php
					if ( $status ) :
						$badge = \App\resolve_badge_style( $status, 'srp', $post_type );
						?>
						<div class="badges-list">
							<span class="card-badge-status"<?php echo $badge['style'] ? ' style="' . esc_attr( $badge['style'] ) . '"' : ''; ?>><?php echo esc_html( $badge['text'] ); ?></span>
						</div>
					<?php endif; ?>
					<span class="card-brand"><?php echo esc_html( $year . ' ' . $make ); ?></span>
					<?php if ( $vin ) : ?>
						<button class="compare__btn" type="button" aria-label="<?php esc_attr_e( 'Compare', 'shopperexpress' ); ?>" data-postid="<?php echo esc_attr( $vin ); ?>" data-posttype="<?php echo esc_attr( $post_type ); ?>" data-toggle="tooltip" data-placement="top" title="+Compare">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
								<path d="M10.2666626,4.0002441h-3.7297363c-.3166504,0-.604126.0916748-.8624878.2749634-.2583008.1833496-.4375.4249878-.5374756.7250366l-2.1000366,6v8c0,.2833252.0958252.520813.2875366.7124634.1916504.1916504.4291382.2875366.7124634.2875366h1c.2833252,0,.520874-.0958862.7125244-.2875366s.2874756-.4291382.2874756-.7124634v-1h4.2297363v4.600647h1.5408936V.7614746h-1.5408936v3.2387695ZM6.8869629,6.0002441h3.3796997v3h-4.4297485l1.0500488-3ZM5.0369263,16.0002441v-5h5.2297363v5h-5.2297363ZM9.0369263,13.5002441c0,.416626-.145813.770813-.4375,1.0625-.291626.291626-.645813.4375-1.0625.4375-.416626,0-.770813-.145874-1.0625-.4375-.291626-.291687-.4375-.645874-.4375-1.0625,0-.416687.145874-.770874.4375-1.0625.291687-.291687.645874-.4375,1.0625-.4375.416687,0,.770874.145813,1.0625.4375.291687.291626.4375.645813.4375,1.0625ZM21.0369263,11.0002441v8c0,.2833252-.0958252.520813-.2874756.7124634s-.4291992.2875366-.7125244.2875366h-1c-.2833252,0-.520813-.0958862-.7124634-.2875366-.1917114-.1916504-.2875366-.4291382-.2875366-.7124634v-1h-4.9998169v-2h5.9998169v-5h-5.9998169v-2h5.1998291l-1.0499878-3h-4.1498413v-2h4.4998169c.3167114,0,.604187.0916748.8624878.2749634.2583618.1833496.4375.4249878.5375366.7250366l2.0999756,6ZM16.5369263,15.0002441c-.416626,0-.770813-.145874-1.0625-.4375-.291626-.291687-.4375-.645874-.4375-1.0625,0-.416687.145874-.770874.4375-1.0625.291687-.291687.645874-.4375,1.0625-.4375.416687,0,.770874.145813,1.0625.4375.291687.291626.4375.645813.4375,1.0625,0,.416626-.145813.770813-.4375,1.0625-.291626.291626-.645813.4375-1.0625.4375Z"></path>
							</svg>
						</button>
						<button class="api-favorite-button" data-postid="<?php echo esc_attr( $vin ); ?>" data-posttype="<?php echo esc_attr( $post_type ); ?>"><i class="sf-icon-star-empty"></i></button>
					<?php endif; ?>
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
				<div class="nav card-tabs" role="tablist">
					<button class="card-tabs-link" id="detail-tab-<?php echo $vin; ?>" data-toggle="tab" data-target="#product-detail-info-<?php echo $vin; ?>" type="button" role="tab" aria-controls="product-detail-info-<?php echo $vin; ?>" aria-selected="false"><?php esc_html_e( 'Detail', 'shopperexpress' ); ?></button>
					<button class="card-tabs-link active" id="price-tab-<?php echo $vin; ?>" data-toggle="tab" data-target="#product-price-info-<?php echo $vin; ?>" type="button" role="tab" aria-controls="product-price-info-<?php echo $vin; ?>" aria-selected="true"><?php esc_html_e( 'Pricing', 'shopperexpress' ); ?></button>
				</div>
			</div>
			<div class="tab-content">
				<?php
				/**
				 * Render `listings-detail_srp_detail_info` rows whose `show_on`
				 * checkbox (defaults to 'detail') matches $show_on — used for both
				 * the Detail tab and the optional Pricing-tab detail block below.
				 * Renders nothing if no row matches.
				 */
				$render_srp_detail_rows = function ( string $show_on ) use ( $vehicle ) {
					if ( ! have_rows( 'listings-detail_srp_detail_info', 'options' ) ) {
						return;
					}

					ob_start();
					while ( have_rows( 'listings-detail_srp_detail_info', 'options' ) ) :
						the_row();
						$label       = get_sub_field( 'label' );
						$value       = get_sub_field( 'value' );
						$row_show_on = get_sub_field( 'show_on' );
						$row_show_on = ! empty( $row_show_on ) ? (array) $row_show_on : array( 'detail' );

						if ( ! in_array( $show_on, $row_show_on, true ) ) {
							continue;
						}

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

									$value = $vehicle[ $field ] ?? null;
									if ( $value === null || $value === '' ) {
										$value = $vehicle['payload'][ $field ] ?? null;
									}

									return ( $value !== null && $value !== '' ) ? $value : '';
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
					$rows_html = ob_get_clean();

					if ( '' === trim( $rows_html ) ) {
						return;
					}
					?>
					<dl class="card-detail">
						<?php echo $rows_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped per-row above. ?>
					</dl>
					<?php
				};
				?>
				<div class="tab-pane fade" id="product-detail-info-<?php echo $vin; ?>" role="tabpanel" aria-labelledby="detail-tab-<?php echo $vin; ?>">
					<!-- Detail info -->
					<?php $render_srp_detail_rows( 'detail' ); ?>
				</div>
				<div class="tab-pane fade show active" id="product-price-info-<?php echo $vin; ?>" role="tabpanel" aria-labelledby="price-tab-<?php echo $vin; ?>">
					<ul class="payment-info">
						<?php
						get_template_part(
							'template-parts/api/payment_list',
							null,
							array(
								'vehicle'   => $vehicle,
								'post_type' => $post_type,
								'style'     => 'archive',
								'loged'     => $loged,
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
								'loged'     => $loged,
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
						$render_srp_detail_rows( 'pricing' );
						?>
				</div>
			</div>
			<?php
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

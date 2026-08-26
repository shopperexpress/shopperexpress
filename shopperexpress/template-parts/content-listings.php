<?php
/**
 * Template for displaying vehicle offer card.
 *
 * @param array $args Array of arguments containing post ID and other data.
 *
 * @package Shopperexpress
 */

$post_id        = ! empty( $args['post_id'] ) ? $args['post_id'] : get_the_id();
$post_type      = get_post_type( $post_id );
$condition      = get_field( 'condition', $post_id );
$similar_url    = ! empty( $args['similar_url'] ) ? $args['similar_url'] : null;
$year           = get_field( 'year', $post_id );
$make           = get_field( 'make', $post_id );
$model          = get_field( 'model', $post_id );
$mileage        = get_field( 'mileage', $post_id );
$drivetrain     = get_field( 'drivetrain', $post_id );
$trim           = get_field( 'trim', $post_id );
$exterior_color = get_field( 'exterior_color', $post_id );
$aria_label     = array( esc_html__( 'Go to', 'shopperexpress' ), esc_html( $year ), esc_html( $make ), esc_html( $model ), esc_html( $drivetrain ), esc_html( $trim ), 'page' );
$alt_array      = array( $year, $make, $model, $trim, $exterior_color, '- ' . get_bloginfo( 'name' ) . ' - Image' );
?>
<div class="col-sm-6 col-lg-4 col-xxl-3">
	<div class="card">
		<div class="card-body">
			<a class="ghost-link" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" aria-label="<?php echo esc_attr( implode( ' ', $aria_label ) ); ?>"></a>
			<div class="card-head">
				<div class="card-head__holder">
					<?php if ( $status = get_field( 'vehicle-status', $post_id ) ) : ?>
						<div class="badges-list">
							<span class="card-badge-status"><?php echo $status; ?></span>
						</div>
					<?php endif ?>
					<span class="card-brand"><?php echo esc_html( $year ); ?> <?php echo esc_html( $make ); ?></span>
					<button class="compare__btn" type="button" aria-label="<?php esc_attr_e( 'Compare', 'shopperexpress' ); ?>" data-postid="<?php echo esc_attr( $post_id ); ?>" data-posttype="<?php echo esc_attr( get_post_type( $post_id ) ); ?>" data-toggle="tooltip" data-placement="top" title="+Compare">
						<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
							<path d="M10.2666626,4.0002441h-3.7297363c-.3166504,0-.604126.0916748-.8624878.2749634-.2583008.1833496-.4375.4249878-.5374756.7250366l-2.1000366,6v8c0,.2833252.0958252.520813.2875366.7124634.1916504.1916504.4291382.2875366.7124634.2875366h1c.2833252,0,.520874-.0958862.7125244-.2875366s.2874756-.4291382.2874756-.7124634v-1h4.2297363v4.600647h1.5408936V.7614746h-1.5408936v3.2387695ZM6.8869629,6.0002441h3.3796997v3h-4.4297485l1.0500488-3ZM5.0369263,16.0002441v-5h5.2297363v5h-5.2297363ZM9.0369263,13.5002441c0,.416626-.145813.770813-.4375,1.0625-.291626.291626-.645813.4375-1.0625.4375-.416626,0-.770813-.145874-1.0625-.4375-.291626-.291687-.4375-.645874-.4375-1.0625,0-.416687.145874-.770874.4375-1.0625.291687-.291687.645874-.4375,1.0625-.4375.416687,0,.770874.145813,1.0625.4375.291687.291626.4375.645813.4375,1.0625ZM21.0369263,11.0002441v8c0,.2833252-.0958252.520813-.2874756.7124634s-.4291992.2875366-.7125244.2875366h-1c-.2833252,0-.520813-.0958862-.7124634-.2875366-.1917114-.1916504-.2875366-.4291382-.2875366-.7124634v-1h-4.9998169v-2h5.9998169v-5h-5.9998169v-2h5.1998291l-1.0499878-3h-4.1498413v-2h4.4998169c.3167114,0,.604187.0916748.8624878.2749634.2583618.1833496.4375.4249878.5375366.7250366l2.0999756,6ZM16.5369263,15.0002441c-.416626,0-.770813-.145874-1.0625-.4375-.291626-.291687-.4375-.645874-.4375-1.0625,0-.416687.145874-.770874.4375-1.0625.291687-.291687.645874-.4375,1.0625-.4375.416687,0,.770874.145813,1.0625.4375.291687.291626.4375.645813.4375,1.0625,0,.416626-.145813.770813-.4375,1.0625-.291626.291626-.645813.4375-1.0625.4375Z"></path>
						</svg>
					</button>
					<?php
					if ( shortcode_exists( 'favorite_button' ) ) {
						echo do_shortcode( '[favorite_button post_id="' . $post_id . '"]' );}
					?>
				</div>
				<strong class="card-model"><?php echo esc_html( $model ); ?> <?php echo esc_html( $drivetrain ); ?> <?php echo esc_html( $trim ); ?> </strong>
			</div>
			<?php
			get_template_part(
				'template-parts/gallery',
				null,
				array(
					'post_type' => get_post_type( $post_id ),
					'post_id'   => $post_id,
					'alt'       => $alt_array,
				)
			);
			?>
			<div class="card-info-row">
				<?php if ( get_field( 'comment_footer', 'options' ) && ! get_field( 'hide_disclosure_srp', 'option' ) ) : ?>
					<button class="btn-disclosure" data-toggle="modal" data-target="#detailModal">
						<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
							<path
								d="M480-280q17 0 28.5-11.5T520-320v-160q0-17-11.5-28.5T480-520q-17 0-28.5 11.5T440-480v160q0 17 11.5 28.5T480-280Zm0-320q17 0 28.5-11.5T520-640q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640q0 17 11.5 28.5T480-600Zm0 520q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z" />
						</svg>
						<?php esc_html_e( 'Disclosure', 'shopperexpress' ); ?>
					</button>
					<?php
				endif;
				$similar = ! empty( $args['similar'] ) ? $args['similar'] : '';

				if ( $similar ) :
					if ( is_array( $similar ) ) {
						$similar_url = $similar['url'];
					}
					?>
					<span class="remaining-price">
						<?php if ( $similar_url ) : ?>
							<a href="<?php echo esc_url( $similar_url ); ?>">
							<?php endif; ?>
							<?php
							echo get_field( 'text_similar_vehicles', 'options' );
							?>
							<?php if ( $similar_url ) : ?>
							</a>
						<?php endif; ?>
					</span>
				<?php endif; ?>
				<div class="nav card-tabs" role="tablist">
					<button class="card-tabs-link" id="detail-tab-<?php echo $post_id ?>" data-toggle="tab" data-target="#product-detail-info-<?php echo $post_id ?>" type="button" role="tab" aria-controls="product-detail-info-<?php echo $post_id ?>" aria-selected="false"><?php esc_html_e( 'Detail', 'shopperexpress' ); ?></button>
					<button class="card-tabs-link active" id="price-tab-<?php echo $post_id ?>" data-toggle="tab" data-target="#product-price-info-<?php echo $post_id ?>" type="button" role="tab" aria-controls="product-price-info-<?php echo $post_id ?>" aria-selected="true"><?php esc_html_e( 'Pricing', 'shopperexpress' ); ?></button>
				</div>
			</div>
			<div class="tab-content">
				<div class="tab-pane fade" id="product-detail-info-<?php echo $post_id ?>" role="tabpanel" aria-labelledby="detail-tab-<?php echo $post_id ?>">
					<?php
					get_template_part(
						'template-parts/detail',
						'info',
						array(
							'post_type' => $post_type,
							'post_id'   => $post_id,
							'class'     => 'card-detail',
						)
					);
					?>
				</div>
				<div class="tab-pane fade show active" id="product-price-info-<?php echo $post_id ?>" role="tabpanel" aria-labelledby="price-tab-<?php echo $post_id ?>">
					<ul class="payment-info">
						<?php
						get_template_part(
							'template-parts/components/payment_list',
							null,
							array(
								'post_id'   => $post_id,
								'post_type' => $post_type,
								'style'     => 'archive',
							)
						);
						get_template_part(
							'template-parts/components/payment_list_new',
							null,
							array(
								'post_id'   => $post_id,
								'post_type' => $post_type,
								'style'     => 'archive',
								'is_single' => false,
							)
						);
						?>
					</ul>
					<?php
						get_template_part(
							'template-parts/description',
							'block',
							array(
								'post_type' => $post_type,
								'type'      => 'srp',
							)
						);
					?>
				</div>
			</div>
			<?php
			$loged = ! empty( $args['loged'] ) ? $args['loged'] : '';
			get_template_part(
				'template-parts/unlock',
				'button',
				array(
					'post_id' => $post_id,
					'loged'   => $loged,
				)
			);

			$vin = get_field( 'vin_number', $post_id );
			$vin = ! empty( $vin ) ? $vin : 0;
			if ( $vin ) {
				$ConversionBlock = new ConversionBlock( $vin, get_post_type( $post_id ), $post_id );
				echo $ConversionBlock->render();
			}
			?>
		</div>
	</div>
</div>

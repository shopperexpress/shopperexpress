<?php
/**
 * Template for displaying vehicle offer card.
 *
 * @param array $args Array of arguments containing post ID and other data.
 *
 * @package Shopperexpress
 */

$post_id     = ! empty( $args['post_id'] ) ? $args['post_id'] : get_the_id();
$post_type   = get_post_type( $post_id );
$condition   = get_field( 'condition', $post_id );
$similar_url = ! empty( $args['similar_url'] ) ? $args['similar_url'] : null;
?>
<div class="col-sm-6 col-lg-4 col-xxl-3">
	<div class="card">
		<div class="card-body">
			<div class="card-head">
				<div class="card-head__holder">
					<span class="card-brand"><?php echo get_field( 'year', $post_id ); ?> <?php echo get_field( 'make', $post_id ); ?></span>
					<?php
					if ( shortcode_exists( 'favorite_button' ) ) {
						echo do_shortcode( '[favorite_button post_id="' . $post_id . '"]' );}
					?>
				</div>
				<strong class="card-model"><?php echo get_field( 'model', $post_id ); ?> <?php echo get_field( 'drivetrain', $post_id ); ?> <?php echo get_field( 'trim', $post_id ); ?> </strong>
			</div>
			<?php
			get_template_part(
				'template-parts/gallery',
				null,
				array(
					'post_type' => get_post_type( $post_id ),
					'post_id'   => $post_id,
				)
			);

			$status = get_field( 'vehicle-status', $post_id ) ? get_field( 'vehicle-status', $post_id ) : null;
			?>
			<div class="badges-list">
				<span class="card-badge-status"><?php echo $status; ?></span>
			</div>
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
			<div class="card-info-row">
				<?php if ( get_field( 'comment_footer', 'options' ) ) : ?>
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
			</div>
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

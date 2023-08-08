<?php $post_id = get_the_id(); ?>
<div class="col-sm-6 col-lg-4 col-xxl-3">
	<div class="card">
		<div class="card-body">
			<div class="card-head">
				<span class="card-brand"><?php echo wps_get_term($post_id, 'year_used-listings'); ?> <?php echo wps_get_term($post_id, 'make_used-listings'); ?></span>
				<strong class="card-model"><?php echo wps_get_term($post_id, 'model_used-listings'); ?> <?php echo wps_get_term($post_id, 'trim_used-listings'); ?></strong>
			</div>
			<a href="<?php the_permalink(); ?>">
				<div class="detail-slider-holder">
					<div class="detail-slider">
						<?php
						if ( $gallery = get_field( 'gallery' ) ):
							foreach ( $gallery as $index => $image ) :
								if ( !empty( $image['image_url'] ) && $index <= 4 ) :
									?>
									<div>
										<img src="<?php echo $image['image_url']; ?>" srcset="<?php echo $image['image_url']; ?> 2x" alt="image description">
									</div>
									<?php
								endif;
							endforeach;
						else:
							$def_img = get_field('default_image', 'option') ? wp_get_attachment_image_url(get_field('default_image', 'option'), 'full') : get_stylesheet_directory_uri().'/images/image-placeholder.png';
							?>
							<div><img src="<?php echo $def_img; ?>" alt="" class="img-fluid"></div>
						<?php endif; ?>
					</div>
				</div>
			</a>
			<dl class="card-detail">
				<dt><?php _e('Stock','shopperexpress'); ?>:</dt>
				<dd><?php echo wps_get_term($post_id, 'stock-number_used-listings'); ?></dd>
				<dt><?php _e('VIN','shopperexpress'); ?>:</dt>
				<dd><?php echo wps_get_term($post_id, 'vin-number_used-listings'); ?></dd>
				<?php if ( function_exists('archive_card_detail') ): ?>
					<?php echo archive_card_detail($post_id,'used-listings'); ?>
				<?php endif; ?>
			</dl>
			<?php if ( have_rows( 'payment_list', 'options' ) ) : ?>
				<ul class="payment-info">
					<?php
					while ( have_rows( 'payment_list', 'options' ) ) : the_row();
						$show_payment = get_sub_field( 'show_payment' );
						$vehicle_type = get_sub_field( 'vehicle_type' );
						if ( $condition == $vehicle_type || $vehicle_type == 'All' ) :
							if ( $show_payment != 'hidden' || is_user_logged_in() ) :

								$payment_type = get_sub_field( 'payment_type' );
								
								$heading = get_sub_field( 'heading' ) ? get_sub_field( 'heading' ) : $payment_type['label'];

								if ( $payment_type['value'] == 'original_price' ) {
									$payment = '$' . number_format( intval( get_field( $payment_type['value'] ) ) );
									if (get_sub_field( 'text_cross_through' )) {
										$payment = '<span class="price-spr line-through"><s>' . $payment . '</s></span>';
									} else {
										$payment = '<span class="price-spr">' . $payment . '</span>';
									}
								}elseif( $payment_type['value'] == 'comment1' || $payment_type['value'] == 'comment2' ){
									$payment = '<span class="price-spr-primary">' . get_field( $payment_type['value'] ) . '</span>';
								}else{
									$payment = '<span class="price-spr-primary">$' . number_format( intval( get_field( $payment_type['value'] ) ) ) . '</span>';
								}
								?>
								<li>
									<strong class="dt"><?php echo $heading; ?></strong>
									<?php if ( $show_payment == 'visible' || is_user_logged_in() ) : ?>
										<strong class="price"><?php echo $payment; ?></strong>
									<?php else: ?>
										<span class="btn btn-primary unlock-item" data-toggle="modal" data-target="#unlockSavingsModal"><i class="material-icons"><?php the_sub_field( 'lock_icon' ); ?></i><?php the_sub_field( 'lock_text' ); ?></span>
									<?php endif; ?>
								</li>
								<?php
							endif;
						endif;
					endwhile;
					?>
				</ul>
				<?php while ( have_rows( 'unlock_button', 'options' ) ) : the_row(); ?>
					<button type="button" class="btn btn-primary btn-custom btn-block" data-toggle="modal" data-target="#contactModal"><i class="material-icons"><?php the_sub_field( 'icon' ); ?></i><?php the_sub_field( 'title' ); ?></button>
				<?php endwhile; ?>
			<?php endif; ?>
			<span  class='intice_bFramev2' data-vdp-vin='<?php echo wps_get_term($post_id, 'vin-number_used-listings'); ?>'>&nbsp;</span>
		</div>
	</div>
</div>
<?php $post_id = get_the_id(); ?>
<div class="col-sm-6 col-lg-4 col-xxl-3">
	<div class="card">
		<a href="<?php the_permalink(); ?>">
			<div class="card-body">
				<div class="card-head">
					<span class="card-brand"><?php echo wps_get_term($post_id, 'year'); ?> <?php echo wps_get_term($post_id, 'make'); ?></span>
					<strong class="card-model"><?php echo wps_get_term($post_id, 'model'); ?> <?php echo wps_get_term($post_id, 'trim'); ?></strong>
				</div>
				<div class="card-img">
					<?php
					if ( $gallery = get_field( 'gallery' ) ):
						if( isset($gallery[0]) ):
							echo '<img src="'.$gallery[0]['image_url'].'" alt="image">';

						endif;
					else:
						$def_img = get_field('default_image', 'option') ? wp_get_attachment_image_url(get_field('default_image', 'option'), 'full') : get_stylesheet_directory_uri().'/images/image-placeholder.png';
						?>
						<img src="<?php echo $def_img; ?>" alt="" class="img-fluid">
					<?php endif; ?>
				</div>
				<?php if ( function_exists('card_detail') ): ?>
					<dl class="card-detail"><?php echo offers_card_detail($post_id); ?></dl>
				<?php endif; ?>
				<?php
				$price = get_field( 'price' );
				$down_payment = wps_get_term( $post_id, 'down-payment');
				$lease_payment = wps_get_term( $post_id, 'lease-payment');
				$loan_payment = wps_get_term( $post_id, 'loan-payment');
				$leaseterm = wps_get_term( $post_id, 'leaseterm');
				$loanterm = get_field('loanterm');
				$loanapr = get_field('loanapr');
				$cash_offer = get_field('cash_offer') ? '$'. number_format(get_field('cash_offer')) : null;
				$cash_offer_label = get_field('cash_offer_label'); ?>

				<ul class="payment-info">
					<?php
					$i = 0;
					while ( have_rows('offers_flexible_content' , 'options' ) ) : the_row();
					
						if ( get_row_layout() == 'payment' && have_rows( 'payment_list' ) && $i == 0  ) :

							while ( have_rows( 'payment_list' ) ) : the_row();
								$lock = get_sub_field( 'lock' );
								$show_payment = $lock  ? get_sub_field( 'show_payment' ) : false;
	
								$down_payment = !empty($down_payment) ? $down_payment : number_format($price);

								switch ( $show_payment ) {
									case 'lease-payment':
									if ( $down_payment && $lease_payment ) {
	
										$lease_payment = !empty($lease_payment) ? '$' . $lease_payment : null;
										$text = !empty($lease_payment) ? '$' . $down_payment . ' ' . __('DOWN' , 'shopperexpress') .'<span class="savings">' . $lease_payment . ' <sub>/mo</sub></span>' : null;
									}else{
										$text = null;
									}

									break;

									case 'Disclosure_loan':
									if ( $condition != 'Slightly Used' && $condition != 'Used' ) {
										$text = $loanterm ? $loanterm . ' ' . __('mos.' , 'shopperexpress')  : '';
										if($loanapr) $text .= '<span class="savings">' . $loanapr . '% <sub>APR</sub></span>';
									}else{
										$text = null;
									}
									break;

									case 'Disclosure_lease':
									if ( $down_payment && $lease_payment ) {
										$lease_payment = !empty($lease_payment) && $lease_payment != 'None' && $lease_payment>0 ? '$' . number_format($lease_payment) : null;
										$text = !empty($lease_payment) ? '$' . $down_payment . ' ' . __('DOWN' , 'shopperexpress').' '. $leaseterm . ' ' . __('mos.' , 'shopperexpress') . '<span class="savings">' . $lease_payment . ' <sub>/mo</sub></span>' : null;
									}else{
										$text = null;
									}
									break;
									case 'Disclosure_Cash':
									if ( $condition != 'Slightly Used' && $condition != 'Used' ) {
										$cash_offer = get_field('cash_offer') ? '$'. number_format(get_field('cash_offer')) : null;
										$cash_offer_label = get_field('cash_offer_label');
										$text = !empty($cash_offer) ? $cash_offer_label . '<span class="savings">' . $cash_offer . '</span>' : null;
									}else{
										$text = null;
									}
									break;

									default:
									$loan_payment = !empty($loan_payment) && $loan_payment != 'None' ? '$' . number_format($loan_payment) . ' <sub>/mo</sub>' : null;
									$text = !empty($loan_payment) ? '$' . $down_payment . ' ' . __('DOWN' , 'shopperexpress') .'<span class="savings">' . $loan_payment . '</span>' : null;
									break;
								}
								if ( $text ) :
									?>
									<li>
										<?php if ( $title = get_sub_field( 'title' ) ): ?>
											<strong class="dt"><?php echo $title; ?></strong>
										<?php endif; ?>
										<strong class="price">
											<?php echo $text; ?>
										</strong>
									</li>
									<?php
								endif;
							endwhile;
						endif;
						$i++;
					endwhile;
					?>
				</ul>
			</div>
		</a>
	</div>
</div>
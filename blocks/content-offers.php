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
				<?php $down_payment = wps_get_term( $post_id, 'down-payment');
				$lease_payment = wps_get_term( $post_id, 'lease-payment');
				$loan_payment = wps_get_term( $post_id, 'loan-payment');
				$leaseterm = wps_get_term( $post_id, 'leaseterm');
				$loanterm = get_field('loanterm');
				$loanapr = get_field('loanapr');
				$cash_offer = get_field('cash_offer') ? '$'. number_format(get_field('cash_offer')) : null;
				$cash_offer_label = get_field('cash_offer_label'); ?>
				<ul class="payment-info">
					<?php if($loanapr || $loanterm){ ?>
						<li>
							<strong class="dt"><?= __('Finance', 'shopperexpress') ?></strong>
							<strong class="price">
								<?php if($loanterm){ echo $loanterm  ?> <?=  __('mos.' , 'shopperexpress')?><?php } ?>
								<?php if($loanapr){ echo '<span class="savings">'. $loanapr  ?>% <sub>APR</sub></span><?php } ?>
							</strong>
						</li>
					<?php } ?>
					
					<?php if($down_payment && $lease_payment){ 
						$lease_payment = !empty($lease_payment) && $lease_payment != 'None' && $lease_payment>0 ? '$' . number_format($lease_payment) : null; ?>
						<li>
							<strong class="dt"><?= __('Lease', 'shopperexpress') ?></strong>
							<strong class="price">$<?= $down_payment ?> <?= __('DOWN' , 'shopperexpress') ?> <?= $leaseterm ?> <?= __('MOS.' , 'shopperexpress') ?> <span class="savings"><?= $lease_payment ?><sub>/mo</sub></span></strong>
						</li>
					<?php } ?>
					<?php if($cash_offer || $cash_offer_label ){ ?>
					<li>
						<strong class="dt"><?= __('Cash', 'shopperexpress') ?></strong>
						<strong class="price"><?= $cash_offer_label ?> <span class="savings"><?= $cash_offer ?></span></strong>
					</li>
					<?php } ?>
				</ul>
			</div>
		</a>
	</div>
</div>
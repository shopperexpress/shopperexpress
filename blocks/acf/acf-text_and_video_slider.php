<?php

$args = array(
	'post_type'   => 'offers',
	'post_status' => 'publish',
	'posts_per_page' => -1,
);

$query = new WP_Query( $args );

if ($query->have_posts()): ?>
	<div class="section-specials">
		<div class="specails-slider slick-item">
			<?php while ($query->have_posts()): 
				$query->the_post();

				$post_id = get_the_ID();
				$wistia_id = get_field('wistia_id');
				$year = wps_get_term( $post_id, 'year');
				$make = wps_get_term( $post_id, 'make');
				$model = wps_get_term( $post_id, 'model');
				$trim = wps_get_term( $post_id, 'trim');
				$down_payment = wps_get_term( $post_id, 'down-payment');
				$lease_payment = wps_get_term( $post_id, 'lease-payment');
				$loan_payment = wps_get_term( $post_id, 'loan-payment');
				$leaseterm = wps_get_term( $post_id, 'leaseterm');
				$loanterm = get_field('loanterm');
				$loanapr = get_field('loanapr');
				$condition = wps_get_term( $post_id, 'condition');
			?>
				<div>
					<section class="slider-card">
						<h2><?php echo $year; ?> <?php echo $make; ?> <?php echo $model; ?> <?php echo $trim; ?></h2>
						<div class="card-row">
							<?php $counter = 0; ?>
							<?php $counter_2 = 0; ?>
							<?php while ( have_rows('offers_flexible_content' , 'options' ) ) : the_row(); ?>
								<?php if ($counter) continue; ?>
								<div class="info-block">
									<div class="heading">
										<?php if ( $image = get_sub_field( 'image' ) ): ?>
											<span class="icon">
												<?php echo wp_get_attachment_image( $image['id'], 'full' ); ?>
											</span>
											<?php
										endif;
										if ( $title = get_sub_field( 'title' ) ): ?>
											<h3><?php echo $title; ?></h3>
										<?php endif; ?>
									</div>
									<?php the_sub_field( 'description' );
									if ( get_row_layout() == 'payment' && have_rows( 'payment_list' ) ): ?>
										<ul class="payment-info">
											<?php
											while ( have_rows( 'payment_list' ) ) : the_row();
												$lock = get_sub_field( 'lock' );
												$show_payment = $lock  ? get_sub_field( 'show_payment' ) : false;
												$show_event = get_sub_field('show_event');
												

												if ( is_user_logged_in() ) {
													$user_info = get_userdata(get_current_user_id());
													$event_attr = '&First='. $user_info->user_firstname .'&last='. $user_info->user_lastname .'&email=' . $user_info->user_email . '&phone=' . get_field( 'phone' , 'user_' . get_current_user_id() );

												}else{
													$event_attr = null;
												}
												
												switch ( get_sub_field( 'event' ) ) {
													case 1:
														$event = "launchDM('" . $location . "','". $vin_number . "','Loan');";
														break;
													case 2:
														$event = "launchTM('" . $location . "','". $vin_number ."');";
														break;
													case 3:
														$event = "launchLOM('" . $location . "','". $vin_number ."');";
														break;
													case 4:
														$event = "launchLM('" . $location . "','". $vin_number ."');";
														break;
													case 5:
														$event = "launchDM('" . $location . "','". $vin_number ."','Lease');";
														break;
													case 6:
														$event = "launchECO('" . $location ."','','','','','". $vin_number ."');";
														break;
													case 7:
														$event = "popup";
														break;
												}
												
												$down_payment = !empty($down_payment) ? $down_payment : number_format($price);
												
												switch ( $show_payment ) {
													case 'lease-payment':
														if ( $down_payment && $lease_payment ) {
															$lease_payment = !empty($lease_payment) ? '$' . number_format($lease_payment) : null;
															$text = !empty($lease_payment) ? '<span class="savings">$' . $down_payment . ' ' . __('DOWN' , 'shopperexpress') .'</span>' . $lease_payment . ' <sub>/mo</sub>' : null;
														}else{
															$text = null;
														}

													break;

													case 'Disclosure_loan':
														if ( $condition != 'Slightly Used' && $condition != 'Used' ) {
															$text = $loanterm ? '<span class="savings">' . $loanterm . ' ' . __('mos.' , 'shopperexpress') .'</span>' : '';
															if($loanapr) $text .= '<span class="price-text">' . $loanapr . '% <sub>APR</sub></span>';
														}else{
															$text = null;
														}
														break;
													
													case 'Disclosure_lease':
														if ( $down_payment && $lease_payment ) {
															$lease_payment = !empty($lease_payment) && $lease_payment != 'None' && $lease_payment>0 ? '$' . number_format($lease_payment) : null;
															$text = !empty($lease_payment) ? '<span class="savings">$' . $down_payment . ' ' . __('DOWN' , 'shopperexpress').' '. $leaseterm . ' ' . __('mos.' , 'shopperexpress') . '</span><span class="price-text">' . $lease_payment . ' <sub>/mo</sub></span>' : null;
														}else{
															$text = null;
														}
														break;
													case 'Disclosure_Cash':
														if ( $condition != 'Slightly Used' && $condition != 'Used' ) {
															$cash_offer = get_field('cash_offer');
															$cash_offer = is_int( $cash_offer ) ? '$'. number_format($cash_offer) : $cash_offer;
															$cash_offer_label = get_field('cash_offer_label');
															$text = !empty($cash_offer) ? '<span class="savings">' . $cash_offer_label . '</span><span class="price-text">' . $cash_offer . '</span>' : null;
														}else{
															$text = null;
														}
														break;

													default:
														$loan_payment = !empty($loan_payment) && $loan_payment != 'None' ? '$' . number_format($loan_payment) . ' <sub>/mo</sub>' : null;
														$text = !empty($loan_payment) ? '<span class="savings">$' . $down_payment . ' ' . __('DOWN' , 'shopperexpress') .'</span>' . $loan_payment : null;
													break;
												}
												$title = get_sub_field( 'title' );
												if( $condition != 'Used'): ?>
													<li>
														<?php if($event == 'popup' && $text){ 
															$show_popup = get_sub_field('show_popup');
															if($show_popup) echo '<a href="#" data-toggle="modal" data-target="#'.$show_popup.'" data-vehicle-id="'.$post_id.'_'.$counter_2.'">';
															$counter_2++;
														}
														if( $lock == true && is_user_logged_in() && $event != 'popup' && $text){
															echo '<a href="#" onclick="javascript:inticeAllEvents.' . $event . '">';
														} ?>
														<div class="text-holder">
															<?php if ( $title ): ?>
																<h4 class="h3"><?php echo $title; ?></h4>
																<?php
															endif;
															the_sub_field( 'description' );
															?>
														</div>
														<?php
														if ( $lock == true && !is_user_logged_in() && $event != 'popup' ) :
															echo '<span class="unlock-item" data-toggle="modal" data-target="#unlockSavingsModal"><i class="material-icons">' . __('lock_open','shopperexpress') . '</i> ' . __('UNLOCK PAYMENT','shopperexpress') . '</span>';
														elseif( $lock == true && is_user_logged_in() && $event != 'popup' && $text ):
															echo '<strong class="price">' . $text . '</strong>';
														elseif($event == 'popup' && $text):
															echo '<strong class="price">' . $text . '</strong>';
														endif;

														if( $lock == false ):
															?>
															<a href="#" onclick="javascript:inticeAllEvents.<?php echo $event; ?>" class="btn btn-primary"><?php the_sub_field( 'text' ); ?></a>
															<?php
														endif;
														if(($event == 'popup' && $text) || ( $lock == true && is_user_logged_in() && $event != 'popup' && $text )) echo '</a>';
														?>
													</li>
													<?php
												endif;
											endwhile;
											?>
										</ul>
									<?php endif; ?>
								</div>
								<?php $counter++; ?>
							<?php endwhile; ?>
							<?php if ($wistia_id): ?>
								<div class="video-block">
									<div data-video='{"type": "wistia", "video": "<?php echo $wistia_id; ?>", "autoplay": true, "fluidWidth": true}'></div>
								</div>
							<?php endif; ?>
						</div>
					</section>
				</div>
			<?php endwhile; ?>
		</div>
	</div>

	<!-- Disclosure_lease Modal -->
	<div class="modal fade content-scrollable modal-offer" id="Disclosure_lease" tabindex="-1" aria-labelledby="DisclosureLeaseLabel" aria-hidden="true">
		<div class="modal-dialog modal-md modal-form modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header justify-content-center">
					<h3 class="modal-title"><?php _e('Additional Information','shopperexpress'); ?></h3>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<i class="material-icons"><?php _e('close','shopperexpress'); ?></i>
					</button>
				</div>
				<div class="modal-body">
					<?php
					if ( $heading_save_offer = get_field( 'heading_save_offer', 'options' ) ) :
						?>
						<h4><?php echo $heading_save_offer; ?></h4>
						<?php
					endif;
					if ( $form_lease_special = get_field( 'form_lease_special', 'options' ) ) echo do_shortcode('[contact-form-7 id="'.$form_lease_special->ID.'" html_class="form-unlock"]'); ?>
					<div class="text-wrapp">
						<div class="text-holder jcf-scrollable h-sm">
							<?php while ($query->have_posts()) {
								$query->the_post();
								echo '<div id="'.get_the_ID().'_0">'.get_field('disclosure_lease').'</div>';
							} ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Disclosure_loan Modal -->
	<div class="modal fade content-scrollable modal-offer" id="Disclosure_loan" tabindex="-1" aria-labelledby="DisclosureLoanLabel" aria-hidden="true">
		<div class="modal-dialog modal-md modal-form modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header justify-content-center">
					<h3 class="modal-title"><?php _e('Additional Information','shopperexpress'); ?></h3>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<i class="material-icons"><?php _e('close','shopperexpress'); ?></i>
					</button>
				</div>
				<div class="modal-body">
					<?php
					if ( $heading_save_offer = get_field( 'heading_save_offer', 'options' ) ) :
						?>
						<h4><?php echo $heading_save_offer; ?></h4>
						<?php
					endif;
					if ( $form_special_apr = get_field( 'form_special_apr', 'options' ) ) echo do_shortcode('[contact-form-7 id="'.$form_special_apr->ID.'" html_class="form-unlock"]'); ?>
					<div class="text-wrapp">
						<div class="text-holder jcf-scrollable h-sm">
							<?php while ($query->have_posts()) {
								$query->the_post();
								echo '<div id="'.get_the_ID().'_1">'.get_field('disclosure_finance').'</div>';
							} ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Disclosure_Cash Modal -->
	<div class="modal fade content-scrollable modal-offer" id="Disclosure_Cash" tabindex="-1" aria-labelledby="DisclosureCashLabel" aria-hidden="true">
		<div class="modal-dialog modal-md modal-form modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header justify-content-center">
					<h3 class="modal-title"><?php _e('Additional Information','shopperexpress'); ?></h3>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<i class="material-icons"><?php _e('close','shopperexpress'); ?></i>
					</button>
				</div>
				<div class="modal-body">
					<?php
					if ( $heading_save_offer = get_field( 'heading_save_offer', 'options' ) ) :
						?>
						<h4><?php echo $heading_save_offer; ?></h4>
						<?php
					endif;
					if ( $form_cash = get_field( 'form_cash', 'options' ) ) echo do_shortcode('[contact-form-7 id="'.$form_cash->ID.'" html_class="form-unlock"]'); ?>
					<div class="text-wrapp">
						<div class="text-holder jcf-scrollable h-sm">
							<?php while ($query->have_posts()) {
								$query->the_post();
								echo '<div id="'.get_the_ID().'_2">'.get_field('disclosure_cash').'</div>';
							} ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php wp_reset_query(); ?>
<?php endif; ?>

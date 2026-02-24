<?php
/**
 * Block Name: Text & Video Slider
 *
 * This is the template that displays the testimonial slider block.
 */


$args = array(
	'post_type'      => 'offers',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'fields'         => 'ids',
);

$sort_offers_by = get_sub_field( 'sort_offers_by' );

switch ( $sort_offers_by ) {
	case 'date':
		$args['orderby'] = 'date';
		$args['order']   = 'DESC';
		break;
	case 'payment_lowest':
		$args['orderby']  = 'meta_value_num';
		$args['meta_key'] = 'lease_payment';
		$args['order']    = 'ASC';
		break;
	case 'payment_highest':
		$args['orderby']  = 'meta_value_num';
		$args['meta_key'] = 'lease_payment';
		$args['order']    = 'DESC';
		break;
	case 'priority':
		$args['orderby']  = 'meta_value_num';
		$args['meta_key'] = 'priority';
		$args['order']    = 'ASC';
		break;
}

$query              = new WP_Query( $args );
$form_lease_special = get_field( 'form_lease_special', 'options' );
$form_special_apr   = get_field( 'form_special_apr', 'options' );
$slider_speed       = get_sub_field( 'slider_speed' ) ? get_sub_field( 'slider_speed' ) : 500;
$autoplay_speed     = get_sub_field( 'autoplay_speed' ) ? get_sub_field( 'autoplay_speed' ) : 5000;
if ( get_sub_field( 'hide_block' ) == false && $query->posts ) :
	?>
	<div class="section-specials">
		<div class="specials-slider slick-item" data-speed="<?php echo esc_html( $slider_speed ); ?>" data-autoplay-speed="<?php echo esc_html( $autoplay_speed ); ?>">
			<?php
			foreach ( $query->posts as $post_id ) :
				$wistia_id      = esc_html( get_field( 'wistia_id', $post_id ) );
				$year           = get_field( 'year', $post_id );
				$make           = get_field( 'make', $post_id );
				$model          = get_field( 'model', $post_id );
				$trim           = get_field( 'trim', $post_id );
				$down_payment   = get_field( 'down_payment', $post_id );
				$lease_payment  = get_field( 'lease_payment', $post_id );
				$loan_payment   = get_field( 'loan_payment', $post_id );
				$leaseterm      = get_field( 'leaseterm', $post_id );
				$loanterm       = esc_html( get_field( 'loanterm', $post_id ) );
				$loanapr        = esc_html( get_field( 'loanapr', $post_id ) );
				$condition      = get_field( 'condition-offers', $post_id );
				$custom_content = wp_kses_post( get_field( 'custom_content', $post_id ) );
				$mainTitle      = array( $year, $make, $model, $trim );
				?>
				<div>
					<section class="slider-card">
						<h2><?php echo implode( ' ', $mainTitle ); ?></h2>
						<div class="card-row">
							<?php
							$counter   = 0;
							$counter_2 = 0;

							while ( have_rows( 'offers_flexible_content', 'options' ) ) :
								the_row();
								?>
								<?php
								if ( $counter ) {
									continue;
								}
								?>
								<div class="info-block">
									<div class="heading">
										<?php if ( $image = get_sub_field( 'image' ) ) : ?>
											<span class="icon">
												<?php echo wp_get_attachment_image( $image['id'], 'full' ); ?>
											</span>
											<?php
										endif;
										if ( $title = get_sub_field( 'title' ) ) :
											?>
											<h3><?php echo $title; ?></h3>
										<?php endif; ?>
									</div>
									<?php
									echo $custom_content;
									if ( get_row_layout() == 'payment' && have_rows( 'payment_list' ) ) :
										?>
										<ul class="payment-info">
											<?php
											while ( have_rows( 'payment_list' ) ) :
												the_row();
												$lock         = get_sub_field( 'lock' );
												$show_payment = $lock ? get_sub_field( 'show_payment' ) : false;
												$show_event   = get_sub_field( 'show_event' );

												switch ( get_sub_field( 'event' ) ) {
													case 1:
														$event = "launchDM('" . $location . "','" . $vin_number . "','Loan');";
														break;
													case 2:
														$event = "launchTM('" . $location . "','" . $vin_number . "');";
														break;
													case 3:
														$event = "launchLOM('" . $location . "','" . $vin_number . "');";
														break;
													case 4:
														$event = "launchLM('" . $location . "','" . $vin_number . "');";
														break;
													case 5:
														$event = "launchDM('" . $location . "','" . $vin_number . "','Lease');";
														break;
													case 6:
														$event = "launchECO('" . $location . "','','','','','" . $vin_number . "');";
														break;
													case 7:
														$event = 'popup';
														break;
												}

												$down_payment = ! empty( $down_payment ) ? $down_payment : null;

												switch ( $show_payment ) {
													case 'lease-payment':
														if ( $down_payment && $lease_payment >= 0 ) {
															$lease_payment = ! empty( $lease_payment ) ? '$' . number_format( $lease_payment ) : null;
															$text          = ! empty( $lease_payment ) ? '<span class="savings">$' . $down_payment . ' ' . __( 'DOWN', 'shopperexpress' ) . '</span>' . $lease_payment . ' <sub>/mo</sub>' : null;
														} else {
															$text = null;
														}

														break;

													case 'Disclosure_loan':
														if ( $condition != 'Slightly Used' && $condition != 'Used' ) {
															$text = $loanterm ? '<span class="savings">' . $loanterm . ' ' . __( 'mos.', 'shopperexpress' ) . '</span>' : '';
															if ( $loanapr ) {
																$text .= '<span class="price-text">' . $loanapr . '% <sub>APR</sub></span>';
															}
														} else {
															$text = null;
														}
														break;

													case 'Disclosure_lease':
														if ( $down_payment && $lease_payment ) {
															$lease_payment = ! empty( $lease_payment ) && $lease_payment != 'None' && $lease_payment > 0 ? '$' . number_format( $lease_payment ) : null;
															$text          = ! empty( $lease_payment ) ? '<span class="savings">' . $leaseterm . ' ' . __( 'mos.', 'shopperexpress' ) . '</span><span class="price-text">' . $lease_payment . ' <sub>/mo</sub></span>' : null;
														} else {
															$text = null;
														}
														break;
													case 'Disclosure_Cash':
														if ( $condition != 'Slightly Used' && $condition != 'Used' ) {
															$cash_offer       = get_field( 'cash_offer', $post_id );
															$cash_offer       = is_int( $cash_offer ) ? '$' . number_format( $cash_offer ) : $cash_offer;
															$cash_offer_label = get_field( 'cash_offer_label', $post_id );
															$text             = ! empty( $cash_offer ) ? '<span class="savings">' . $cash_offer_label . '</span><span class="price-text">' . $cash_offer . '</span>' : null;
														} else {
															$text = null;
														}
														break;

													default:
														$loan_payment = ! empty( $loan_payment ) && $loan_payment != 'None' ? '$' . number_format( $loan_payment ) . ' <sub>/mo</sub>' : null;
														$text         = ! empty( $loan_payment ) ? '<span class="savings">$' . $down_payment . ' ' . __( 'DOWN', 'shopperexpress' ) . '</span>' . $loan_payment : null;
														break;
												}
												$title = get_sub_field( 'title' );
												if ( $condition != 'Used' && $text && ( $down_payment || $loan_payment || $lease_payment || $loanapr || $loanterm ) ) :
													?>
													<li>
														<?php
														if ( $event == 'popup' && $text ) {
															$show_popup = get_sub_field( 'show_popup' );
															if ( $show_popup ) {
																echo '<a href="#" data-toggle="modal" data-post="' . $post_id . '" data-target="#' . $show_popup . '-acf" data-vehicle-id="' . $post_id . '_' . get_row_index() . '">';
															}
															++$counter_2;
														}
														if ( $lock == true && wps_auth() && $event != 'popup' && $text ) {
															echo '<a href="#" onclick="javascript:inticeAllEvents.' . $event . '">';
														}
														?>
														<div class="text-holder">
															<?php if ( $title ) : ?>
																<h4 class="h3"><?php echo $title; ?></h4>
																<?php
															endif;
															the_sub_field( 'description' );
															?>
														</div>
														<?php
														if ( $lock == true && ! wps_auth() && $event != 'popup' ) :
															echo '<span class="unlock-item" data-toggle="modal" data-target="#unlockSavingsModal"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
                                                            <path
                                                                d="M840-640q32 0 56 24t24 56v80q0 7-1.5 15t-4.5 15L794-168q-9 20-30 34t-44 14H400q-33 0-56.5-23.5T320-200v-407q0-16 6.5-30.5T344-663l217-216q15-14 35.5-17t39.5 7q19 10 27.5 28t3.5 37l-45 184h218ZM160-120q-33 0-56.5-23.5T80-200v-360q0-33 23.5-56.5T160-640q33 0 56.5 23.5T240-560v360q0 33-23.5 56.5T160-120Z"
                                                            /></svg>' . __( 'UNLOCK PAYMENT', 'shopperexpress' ) . '</span>';
														elseif ( $lock == true && wps_auth() && $event != 'popup' && $text ) :
															echo '<strong class="price">' . $text . '</strong>';
														elseif ( $event == 'popup' && $text ) :
															echo '<strong class="price">' . $text . '</strong>';
														endif;

														if ( $lock == false ) :
															?>
															<a href="#" onclick="javascript:inticeAllEvents.<?php echo $event; ?>" class="btn btn-primary"><?php the_sub_field( 'text' ); ?></a>
															<?php
														endif;
														if ( ( $event == 'popup' && $text ) || ( $lock == true && wps_auth() && $event != 'popup' && $text ) ) {
															echo '</a>';
														}
														?>
													</li>
													<?php
												endif;
											endwhile;
											?>
										</ul>
									<?php endif; ?>
								</div>
								<?php ++$counter; ?>
								<?php
							endwhile;
							$title = implode( ' ', $mainTitle );
							$title = html_entity_decode( $title );
							$title = str_replace( array( '"', "'" ), '', $title );
							?>
							<?php if ( $wistia_id ) : ?>
								<div class="video-block">
									<div data-video='{"type": "wistia", "video": "<?php echo $wistia_id; ?>","title": "<?php echo esc_html( trim( $title ) ); ?>", "autoplay": false, "fluidWidth": true, "lazyLoad": true}'></div>
								</div>
							<?php endif; ?>
						</div>
					</section>
				</div>
			<?php endforeach; ?>
			<?php wp_reset_query(); ?>
		</div>
	</div>

	<!-- Disclosure_lease Modal -->
	<div class="modal fade modal-offer" id="Disclosure_lease-acf" tabindex="-1" aria-labelledby="DisclosureLeaseLabel" aria-hidden="true">
		<div class="modal-dialog modal-md modal-form modal-dialog-scrollable modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h3 class="modal-title"><?php _e( 'Additional Information', 'shopperexpress' ); ?></h3>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000">
							<path
								d="M480-424 284-228q-11 11-28 11t-28-11q-11-11-11-28t11-28l196-196-196-196q-11-11-11-28t11-28q11-11 28-11t28 11l196 196 196-196q11-11 28-11t28 11q11 11 11 28t-11 28L536-480l196 196q11 11 11 28t-11 28q-11 11-28 11t-28-11L480-424Z" />
						</svg>
					</button>
				</div>
				<div class="modal-body">
					<?php
					if ( $heading_save_offer = get_field( 'heading_save_offer', 'options' ) ) :
						?>
						<h4><?php echo esc_html( $heading_save_offer ); ?></h4>
						<?php
					endif;
					if ( $form_lease_special = get_field( 'form_lease_special', 'options' ) ) {
						$form_id = absint( $form_lease_special );
						echo do_shortcode( '[wpforms id="' . $form_id . '"]' );
					}
					?>
					<div class="text-wrapp">
						<div class="text-holder scrollable h-sm">
							<?php
							foreach ( $query->posts as $post_id ) {
								if ( $disclosure_lease = get_field( 'disclosure_lease', $post_id ) ) {
									echo '<div id="' . $post_id . '_1">' . wp_kses_post( $disclosure_lease ) . '</div>';
								}
							}
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Disclosure_loan Modal -->
	<div class="modal fade modal-offer" id="Disclosure_loan-acf" tabindex="-1" aria-labelledby="DisclosureLoanLabel" aria-hidden="true">
		<div class="modal-dialog modal-md modal-form modal-dialog-scrollable modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h3 class="modal-title"><?php _e( 'Additional Information', 'shopperexpress' ); ?></h3>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000">
							<path
								d="M480-424 284-228q-11 11-28 11t-28-11q-11-11-11-28t11-28l196-196-196-196q-11-11-11-28t11-28q11-11 28-11t28 11l196 196 196-196q11-11 28-11t28 11q11 11 11 28t-11 28L536-480l196 196q11 11 11 28t-11 28q-11 11-28 11t-28-11L480-424Z" />
						</svg>
					</button>
				</div>
				<div class="modal-body">
					<?php
					if ( $heading_save_offer = get_field( 'heading_save_offer', 'options' ) ) :
						?>
						<h4><?php echo esc_html( $heading_save_offer ); ?></h4>
						<?php
					endif;
					if ( $form_special_apr = get_field( 'form_special_apr', 'options' ) ) {
						$form_id = absint( $form_special_apr );
						echo do_shortcode( '[wpforms id="' . $form_id . '"]' );
					}
					?>
					<div class="text-wrapp">
						<div class="text-holder scrollable h-sm">
							<?php
							foreach ( $query->posts as $post_id ) {
								if ( $disclosure_finance = get_field( 'disclosure_finance', $post_id ) ) {
									echo '<div id="' . $post_id . '_2">' . wp_kses_post( $disclosure_finance ) . '</div>';
								}
							}
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Disclosure_Cash Modal -->
	<div class="modal fade modal-offer" id="Disclosure_Cash-acf" tabindex="-1" aria-labelledby="DisclosureCashLabel" aria-hidden="true">
		<div class="modal-dialog modal-md modal-form modal-dialog-scrollable modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h3 class="modal-title"><?php _e( 'Additional Information', 'shopperexpress' ); ?></h3>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000">
							<path
								d="M480-424 284-228q-11 11-28 11t-28-11q-11-11-11-28t11-28l196-196-196-196q-11-11-11-28t11-28q11-11 28-11t28 11l196 196 196-196q11-11 28-11t28 11q11 11 11 28t-11 28L536-480l196 196q11 11 11 28t-11 28q-11 11-28 11t-28-11L480-424Z" />
						</svg>
					</button>
				</div>
				<div class="modal-body">
					<?php
					if ( $heading_save_offer = get_field( 'heading_save_offer', 'options' ) ) :
						?>
						<h4><?php echo esc_html( $heading_save_offer ); ?></h4>
						<?php
					endif;
					if ( $form_cash = get_field( 'form_cash', 'options' ) ) {
						$form_id = absint( $form_special_apr );
						echo do_shortcode( '[wpforms id="' . $form_id . '"]' );
					}
					?>
					<div class="text-wrapp">
						<div class="text-holder scrollable h-sm">
							<?php
							foreach ( $query->posts as $post_id ) {
								if ( $disclosure_cash = get_field( 'disclosure_cash', $post_id ) ) {
									echo '<div id="' . $post_id . '_3">' . wp_kses_post( $disclosure_cash ) . '</div>';
								}
							}
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

<?php endif; ?>

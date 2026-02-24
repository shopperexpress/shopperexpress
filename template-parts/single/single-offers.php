<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package Shopperexpress
 */

get_header();

$acf_prefix = 'service-';

while ( have_posts() ) :
	the_post();
	$post_id                 = get_the_ID();
	$offers_default_image    = get_field( 'offers_default_image', 'options' ) ? esc_url( wp_get_attachment_image_url( get_field( 'offers_default_image', 'options' ), 'full' ) ) : '';
	$gallery                 = get_field( 'gallery' ) ? get_field( 'gallery' ) : array( 0 => array( 'image_url' => $offers_default_image ) );
	$post_type               = get_post_type( $post_id );
	$location                = get_field( 'location' );
	$year                    = get_field( 'year' );
	$make                    = get_field( 'make' );
	$model                   = get_field( 'model' );
	$trim                    = get_field( 'trim' );
	$down_payment            = get_field( 'down_payment' );
	$lease_payment           = get_field( 'lease_payment' );
	$loan_payment            = get_field( 'loan_payment' );
	$leaseterm               = get_field( 'leaseterm' );
	$loanterm                = get_field( 'loanterm' );
	$loanapr                 = get_field( 'loanapr' );
	$condition               = get_field( 'condition' );
	$search_inventory_button = get_field( 'search_inventory_button' );
	$vin_number              = null;
	$custom_content          = get_field( 'custom_content' );

	switch ( $condition ) {
		case 'Slightly Used':
			$textCondition = __( 'Market Value', 'shopperexpress' );
			break;
		case 'Used':
			$textCondition = __( 'Market Value', 'shopperexpress' );
			break;
		default:
			$textCondition = __( 'MSRP', 'shopperexpress' );
			break;
	}
	?>
	<div class="detail-section">
		<div class="container">
			<div class="row">
				<div class="col-sm-6">
					<div class="sticky-box">
						<div class="detail-top-row">
							<ol class="breadcrumbs">
								<li><a href="<?php echo get_post_type_archive_link( $post_type ); ?>"><?php _e( 'All Offers', 'shopperexpress' ); ?></a></li>
							</ol>
							<ul class="code-list text-right text-capitalize list-unstyled">
								<li><?php _e( 'Year & Model', 'shopperexpress' ); ?>: <?php echo $year . ' ' . $model; ?></li>
								<?php if ( $trim ) : ?>
									<li><?php _e( 'Trim', 'shopperexpress' ); ?>: <?php echo $trim; ?></li>
								<?php endif; ?>
							</ul>
						</div>
						<div class="detail-slider-holder">
							<?php if ( $gallery ) : ?>
								<div class="detail-slider-wrapper">
									<?php
									$slider         = get_field( 'offers_slider', 'options' );
									$autoplay       = ! empty( $slider['autoplay'] ) ? 'true' : 'false';
									$autoplay_speed = ! empty( $slider['autoplay_speed'] ) ? $slider['autoplay_speed'] * 60 * 60 : 3000;
									$i              = 1;
									$firstImage     = null;
									?>
									<div class="detail-slider" data-autoplay="<?php echo $autoplay; ?>" data-autoplay-speed="<?php echo $autoplay_speed; ?>">
										<?php foreach ( $gallery as $value ) : ?>
											<div class="slide bg-cover" style="background-image: url(<?php echo get_field( 'background_image', 'option' ); ?>)">
												<?php
												if ( $value['image_url'] ) :
													if ( $i == 1 ) {
														$firstImage = $value['image_url'];
													}
													echo '<a href="' . $value['image_url'] . '" data-fancybox="img-gallery"><img src="' . $value['image_url'] . '" alt="image"></a>';
													++$i;
												endif;
												?>
											</div>
										<?php endforeach ?>
									</div>
									<a href="#" data-type="iframe" class="btn-spin" data-fancybox>
										<svg x="0px" y="0px" viewBox="0 0 116.2 31.5" xml:space="preserve">
											<g>
												<path d="M51.6,15.9c-0.3,0-0.5,0.1-0.8,0.2s-0.4,0.3-0.5,0.4c-0.1,0.2-0.2,0.4-0.2,0.7c0,0.2,0.1,0.4,0.2,0.6
													c0.1,0.2,0.3,0.3,0.5,0.4c0.2,0.1,0.5,0.2,0.8,0.2c0.3,0,0.5,0,0.7-0.2c0.2-0.1,0.4-0.2,0.5-0.4c0.1-0.2,0.2-0.4,0.2-0.7
													c0-0.4-0.1-0.7-0.4-0.9C52.4,16,52,15.9,51.6,15.9z" />
												<path d="M83.9,12.9h-1.6v2.9h1.6c0.6,0,1-0.1,1.3-0.4c0.3-0.3,0.5-0.6,0.5-1.1s-0.2-0.8-0.5-1.1C84.9,13,84.5,12.9,83.9,12.9z" />
												<path d="M66.4,12.2c-0.2-0.1-0.4-0.1-0.6-0.1c-0.2,0-0.4,0-0.6,0.1s-0.3,0.2-0.4,0.4c-0.1,0.2-0.1,0.4-0.1,0.6c0,0.2,0,0.4,0.1,0.6
													c0.1,0.2,0.2,0.3,0.4,0.4s0.4,0.1,0.6,0.1c0.2,0,0.4,0,0.6-0.1c0.2-0.1,0.3-0.2,0.4-0.4c0.1-0.2,0.1-0.4,0.1-0.6
													c0-0.2,0-0.4-0.1-0.6C66.7,12.5,66.6,12.3,66.4,12.2z" />
												<path d="M100.5,0H15.8C7.1,0,0,7.1,0,15.8v0c0,8.7,7.1,15.8,15.8,15.8h84.7c8.7,0,15.8-7.1,15.8-15.8v0C116.2,7.1,109.2,0,100.5,0z
													M33.9,17.7c-1.1,0.8-2.6,1.4-4.3,1.8v-2c1.3-0.3,2.3-0.7,3-1.2c0.7-0.5,1-0.9,1-1.3c0-0.5-0.7-1.2-2.1-1.9S28,12,25.5,12
													s-4.4,0.4-5.9,1.1s-2.1,1.4-2.1,1.9c0,0.4,0.4,0.9,1.3,1.4c0.8,0.6,2.1,1,3.6,1.3l-1.3-1.3l1.4-1.4l4,4l-4,4l-1.4-1.4l1.8-1.8
													c-2.1-0.3-3.9-0.9-5.3-1.8c-1.4-0.9-2.1-1.9-2.1-3c0-1.4,1-2.6,2.9-3.5c1.9-1,4.3-1.5,7.1-1.5s5.2,0.5,7.1,1.5
													c1.9,1,2.9,2.2,2.9,3.5C35.5,16,35,16.9,33.9,17.7z M46.7,18.4c-0.3,0.4-0.6,0.8-1.1,1c-0.5,0.3-1.2,0.4-2,0.4
													c-0.6,0-1.2-0.1-1.7-0.2c-0.6-0.2-1.1-0.4-1.5-0.7l0.8-1.5c0.3,0.2,0.7,0.4,1.1,0.6s0.9,0.2,1.3,0.2c0.5,0,0.9-0.1,1.2-0.3
													c0.3-0.2,0.4-0.5,0.4-0.8c0-0.3-0.1-0.6-0.4-0.8c-0.2-0.2-0.6-0.3-1.2-0.3h-0.9v-1.3l1.7-2h-3.6v-1.6h5.9v1.3l-1.9,2.2
													c0.6,0.1,1.1,0.3,1.5,0.6c0.5,0.5,0.8,1.1,0.8,1.8C47.1,17.6,47,18,46.7,18.4z M54.5,18.5c-0.3,0.4-0.7,0.7-1.1,1
													c-0.5,0.2-1,0.3-1.6,0.3c-0.8,0-1.4-0.2-2-0.5c-0.6-0.3-1-0.8-1.3-1.4c-0.3-0.6-0.5-1.4-0.5-2.3c0-1,0.2-1.8,0.5-2.5
													c0.4-0.7,0.9-1.2,1.5-1.5c0.6-0.4,1.4-0.5,2.2-0.5c0.4,0,0.9,0,1.3,0.2c0.4,0.1,0.8,0.2,1.1,0.4l-0.7,1.4c-0.2-0.2-0.5-0.3-0.7-0.3
													c-0.3-0.1-0.5-0.1-0.8-0.1c-0.7,0-1.3,0.2-1.7,0.7c-0.4,0.4-0.6,1-0.6,1.8c0,0,0.1-0.1,0.1-0.1c0.2-0.2,0.5-0.4,0.9-0.5
													c0.3-0.1,0.7-0.2,1.1-0.2c0.5,0,1,0.1,1.5,0.3c0.4,0.2,0.8,0.5,1,0.9c0.3,0.4,0.4,0.9,0.4,1.4C54.9,17.6,54.8,18.1,54.5,18.5z
													M62.4,17.8c-0.3,0.6-0.7,1.1-1.3,1.5c-0.5,0.3-1.2,0.5-1.9,0.5c-0.7,0-1.3-0.2-1.8-0.5c-0.5-0.3-1-0.8-1.3-1.5
													c-0.3-0.6-0.5-1.4-0.5-2.4c0-0.9,0.2-1.7,0.5-2.4s0.7-1.1,1.3-1.5c0.5-0.3,1.2-0.5,1.8-0.5c0.7,0,1.3,0.2,1.9,0.5
													c0.5,0.3,1,0.8,1.3,1.5c0.3,0.6,0.5,1.4,0.5,2.4C62.8,16.4,62.7,17.2,62.4,17.8z M67.6,14.3c-0.2,0.3-0.4,0.6-0.8,0.7
													c-0.3,0.2-0.7,0.3-1.1,0.3c-0.4,0-0.7-0.1-1.1-0.3c-0.3-0.2-0.6-0.4-0.8-0.7c-0.2-0.3-0.3-0.7-0.3-1c0-0.4,0.1-0.7,0.3-1
													c0.2-0.3,0.4-0.6,0.8-0.7s0.7-0.3,1.1-0.3c0.4,0,0.7,0.1,1.1,0.3s0.6,0.4,0.8,0.7c0.2,0.3,0.3,0.7,0.3,1
													C67.9,13.6,67.8,14,67.6,14.3z M78.7,18.5c-0.3,0.4-0.7,0.7-1.2,0.9s-1.2,0.4-2,0.4c-0.7,0-1.3-0.1-1.9-0.3
													c-0.6-0.2-1.1-0.4-1.5-0.7l0.7-1.5c0.4,0.3,0.8,0.5,1.3,0.6c0.5,0.2,1,0.2,1.5,0.2c0.4,0,0.7,0,0.9-0.1c0.2-0.1,0.4-0.2,0.5-0.3
													c0.1-0.1,0.2-0.3,0.2-0.5c0-0.2-0.1-0.4-0.3-0.5c-0.2-0.1-0.4-0.2-0.7-0.3s-0.6-0.2-1-0.2c-0.3-0.1-0.7-0.2-1-0.3
													c-0.3-0.1-0.7-0.3-1-0.4c-0.3-0.2-0.5-0.4-0.7-0.7s-0.3-0.7-0.3-1.1c0-0.5,0.1-0.9,0.4-1.3c0.3-0.4,0.6-0.7,1.2-0.9
													c0.5-0.2,1.2-0.4,2-0.4c0.5,0,1.1,0.1,1.6,0.2c0.5,0.1,1,0.3,1.4,0.6l-0.6,1.5c-0.4-0.2-0.8-0.4-1.2-0.5c-0.4-0.1-0.8-0.2-1.2-0.2
													c-0.4,0-0.7,0-0.9,0.1s-0.4,0.2-0.5,0.3c-0.1,0.1-0.2,0.3-0.2,0.5c0,0.2,0.1,0.4,0.3,0.5c0.2,0.1,0.4,0.2,0.7,0.3s0.6,0.2,1,0.2
													c0.4,0.1,0.7,0.2,1,0.3c0.3,0.1,0.7,0.3,0.9,0.4c0.3,0.2,0.5,0.4,0.7,0.7c0.2,0.3,0.3,0.7,0.3,1.1C79,17.7,78.9,18.1,78.7,18.5z
													M87.2,15.9c-0.3,0.5-0.7,0.8-1.3,1c-0.5,0.2-1.2,0.4-1.9,0.4h-1.7v2.3h-2v-8.4H84c0.8,0,1.4,0.1,1.9,0.4c0.5,0.2,1,0.6,1.3,1.1
													s0.4,1,0.4,1.6C87.7,14.9,87.5,15.5,87.2,15.9z M91,19.7h-2v-8.4h2V19.7z M100.7,19.7h-1.6l-4.2-5.1v5.1H93v-8.4h1.6l4.2,5.1v-5.1
													h1.9V19.7z" />
												<path d="M60.1,13c-0.2-0.2-0.5-0.3-0.9-0.3c-0.3,0-0.6,0.1-0.8,0.3c-0.2,0.2-0.4,0.5-0.6,0.9c-0.1,0.4-0.2,0.9-0.2,1.5
													c0,0.6,0.1,1.1,0.2,1.5c0.1,0.4,0.3,0.7,0.6,0.9c0.2,0.2,0.5,0.3,0.8,0.3c0.3,0,0.6-0.1,0.9-0.3c0.2-0.2,0.4-0.5,0.6-0.9
													c0.1-0.4,0.2-0.9,0.2-1.5c0-0.6-0.1-1.1-0.2-1.5C60.5,13.5,60.3,13.2,60.1,13z" />
											</g>
										</svg>
									</a>
								</div>
								<div class="detail-slider-nav">
									<div class="slider-nav-holder">
										<?php foreach ( $gallery as $value ) : ?>
											<div class="slide bg-cover">
												<img src="<?php echo $value['image_url']; ?>" srcset="<?php echo $value['image_url']; ?> 2x" alt="image">
											</div>
										<?php endforeach ?>
									</div>
								</div>
								<div class="range-box">
									<input aria-label="Carousel thumbnails slider" value="0" min="0" max="100" step="1" type="range">
								</div>
							<?php endif; ?>
						</div>
						<?php
						get_template_part(
							'template-parts/detail',
							'info',
							array(
								'post_type' => $post_type,
								'post_id'   => $post_id,
							)
						);
						?>
						<ul class="details-list list-inline">
							<li class="list-inline-item"><a href="#" data-toggle="modal" data-target="#overviewModal">+<?php esc_html_e( 'See Details', 'shopperexpress' ); ?></a></li>
						</ul>
					</div>
				</div>
				<div class="col-sm-6">
					<div class="anchors-holder">
						<?php
						get_template_part(
							'template-parts/components/anchor',
							'list',
							array(
								'post_type'  => $post_type,
								'location'   => $location,
								'vin_number' => $vin_number,
							)
						);
						get_template_part( 'template-parts/components/anchor', 'copy', array( 'favorite' => true ) );
						?>
					</div>
					<?php

					$pageTitle   = array();
					$pageTitle[] = $year;
					$pageTitle[] = $make;
					$pageTitle[] = $model;
					$pageTitle[] = $trim;
					$pageTitle   = implode( ' ', $pageTitle );
					?>
					<h2><?php echo $pageTitle; ?></h2>
					<?php if ( $message = get_field( 'message' ) ) : ?>
						<div class="lead">
							<?php echo $message; ?>
						</div>
					<?php endif; ?>
					<?php $counter_for_button = 0; ?>
					<?php
					while ( have_rows( 'offers_flexible_content', 'options' ) ) :
						the_row();
						$row = get_row_index();
						?>
						<div class="info-block" id="block-<?php echo $row; ?>">
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
							if ( $row == 1 && $custom_content ) {
								echo $custom_content;
							} else {
								the_sub_field( 'description' );
							}

							if ( $search_inventory_button && $counter_for_button == 0 ) {
								echo '<a href="' . $search_inventory_button . '" class="btn btn-primary btn-custom btn-block">' . __( 'Search Inventory', 'shopperexpress' ) . '</a>';
							}
							++$counter_for_button;
							if ( get_row_layout() == 'payment' && have_rows( 'payment_list' ) ) {
								?>
								<ul class="payment-info">
									<?php
									while ( have_rows( 'payment_list' ) ) :
										the_row();
										$link         = get_sub_field( 'link' );
										$lock         = get_sub_field( 'lock' );
										$show_payment = $lock ? get_sub_field( 'show_payment' ) : false;
										$show_event   = get_sub_field( 'show_event' );
										$show_popup   = get_sub_field( 'show_popup' );

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

										$down_payment = ! empty( $down_payment ) ? $down_payment : number_format( $price );

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
														$text .= $loanapr . '% <sub>APR</sub>';
													}
												} else {
													$text = null;
												}
												break;

											case 'Disclosure_lease':
												if ( $lease_payment ) {
													$lease_payment = ! empty( $lease_payment ) && $lease_payment != 'None' && $lease_payment > 0 ? '$' . number_format( $lease_payment ) : null;
													$text          = ! empty( $lease_payment ) ? '<span class="savings">' . $leaseterm . ' ' . __( 'mos.', 'shopperexpress' ) . '</span>' . $lease_payment . ' <sub>/mo</sub>' : null;
												} else {
													$text = null;
												}
												break;
											case 'Disclosure_Cash':
												if ( $condition != 'Slightly Used' && $condition != 'Used' ) {
													$cash_offer       = get_field( 'cash_offer' );
													$cash_offer       = is_int( $cash_offer ) ? '$' . number_format( $cash_offer ) : $cash_offer;
													$cash_offer_label = get_field( 'cash_offer_label' );
													$text             = ! empty( $cash_offer ) ? '<span class="savings">' . $cash_offer_label . '</span>' . $cash_offer : null;
												} else {
													$text = null;
												}
												break;

											default:
												$loan_payment = ! empty( $loan_payment ) && $loan_payment != 'None' ? '$' . number_format( floatval( $loan_payment ) ) . ' <sub>/mo</sub>' : null;
												$text         = ! empty( $loan_payment ) ? '<span class="savings">$' . $down_payment . ' ' . __( 'DOWN', 'shopperexpress' ) . '</span>' . $loan_payment : null;
												break;
										}
										$title = get_sub_field( 'title' );
										if ( $condition != 'Used' ) :
											?>
											<li>
												<?php
												if ( $event == 'popup' && $text ) {
													if ( $show_popup ) {
														echo '<a href="#" data-toggle="modal" data-target="#' . $show_popup . '">';
													}
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
													echo str_replace( '[title]', $pageTitle, get_sub_field( 'description' ) );
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
													if ( $text = get_sub_field( 'text' ) ) :
														?>
														<a
															<?php
															if ( $show_popup == 'video' ) :
																?>
															data-width="1600" data-height="900" data-fancybox data-type="iframe" href="//fast.wistia.net/embed/iframe/<?php the_field( 'wistia_id' ); ?>"
																<?php
															else :
																?>
															href="#" onclick="javascript:inticeAllEvents.<?php echo $event; ?>" <?php endif; ?> class="btn btn-primary"><?php echo $text; ?></a>
														<?php
													endif;
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
								<?php
								if ( $row == 1 ) {
									?>
									<div class="info-block info-block--top">
										<?php get_template_part( 'template-parts/unlock', 'button' ); ?>
									</div>
									<?php
									$ConversionBlock = new ConversionBlock( 0, get_post_type() );
									echo $ConversionBlock->render();
								}
								?>
							<?php } elseif ( get_row_layout() == 'video' && have_rows( 'video_list' ) ) { ?>
								<ul class="payment-video">
									<?php
									while ( have_rows( 'video_list' ) ) :
										the_row();
										?>
										<li>
											<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000">
												<path
													d="m424-424-86-86q-11-11-28-11t-28 11q-11 11-11 28t11 28l114 114q12 12 28 12t28-12l226-226q11-11 11-28t-11-28q-11-11-28-11t-28 11L424-424ZM200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Z" />
											</svg>
											<?php if ( $title = get_sub_field( 'title' ) ) : ?>
												<strong class="title"><?php echo $title; ?></strong>
												<?php
											endif;
											the_sub_field( 'description' );
											if ( $video_id = get_sub_field( 'video_id' ) ) :
												?>
												<p><span class="wistia_embed wistia_async_<?php echo $video_id; ?> popover=true popoverContent=link" style="display: inline; position: relative; "><a class="btn-get-started " href="#video-<?php echo get_row_index(); ?>"><?php _e( 'Watch Video', 'shopperexpress' ); ?></a></span></p>
												<div style="display: none;" id="video-<?php echo get_row_index(); ?>">
													<script src="https://fast.wistia.com/embed/medias/<?php echo $video_id; ?>.jsonp" async></script>
													<div class="wistia_responsive_padding" style="padding:56.25% 0 0 0;position:relative;">
														<div class="wistia_responsive_wrapper" style="height:100%;left:0;position:absolute;top:0;width:100%;"><span class="wistia_embed wistia_async_<?php echo $video_id; ?> popover=true popoverAnimateThumbnail=true videoFoam=true" style="display:inline-block;height:100%;position:relative;width:100%">&nbsp;</span></div>
													</div>
												</div>
											<?php endif; ?>
										</li>
									<?php endwhile; ?>
								</ul>
							<?php } ?>
						</div>
					<?php endwhile; ?>
					<div class="info-block">
						<div class="summary-block text-center">
							<?php
							$link       = get_field( 'link', 'options' );
							$popup_form = get_field( 'popup_form', 'options' );
							if ( $link && ! $popup_form ) {
								echo wps_get_link( $link, 'btn btn-outline-primary', null );
							} elseif ( $popup_form && $link ) {
								echo '<a class="btn btn-outline-primary" href="#" data-toggle="modal" data-target="#contactModal">' . $link['title'] . '</a>';
							}
							if ( $phone = get_field( 'phone', 'options' ) ) {
								?>
								<strong class="phone"><?php _e( 'CALL', 'shopperexpress' ); ?> <a href="tel:<?php echo clean_phone( $phone ); ?>"><?php echo $phone; ?></a></strong>
								<?php
							}
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
	$vdp_description = get_field( 'vdp_description', 'options' );

	if ( have_rows( 'vdp_description' ) ) :
		?>
		<div class="vdp-description">
			<div class="container">
				<div class="vdp-content">
					<?php if ( $vdp_description['heading'] ) : ?>
						<strong class="title"><?php echo $vdp_description['heading']; ?></strong>
						<?php
					endif;

					if ( $vdp_description['text'] ) {
						echo $vdp_description['text'];
					}
					?>

					<ul class="vdp-list">
						<?php
						while ( have_rows( 'vdp_description' ) ) :
							the_row();
							if ( $text = get_sub_field( 'text' ) ) :
								?>
								<li><?php echo $text; ?></li>
								<?php
							endif;
						endwhile;
						?>
					</ul>
				</div>
			</div>
		</div>
		<?php
	endif;

	get_template_part( 'template-parts/accordion', 'detail-info', array( 'vin_number' => get_field( 'chrome' ) ) );

	get_template_part(
		'template-parts/accordion',
		null,
		array(
			'post_type' => $post_type,
			'type'      => 'random',
		)
	);

	if ( $comment_footer  = get_field( 'offers_comment_footer', 'options' ) ) :
		?>
		<div class="description-box">
			<div class="container-fluid">
				<?php echo $comment_footer; ?>
			</div>
		</div>
		<?php
	endif;

	get_template_part(
		'template-parts/model',
		'slider',
		array(
			'title'      => get_field( 'title_slider', 'options' ),
			'section_bg' => get_field( 'section_bg_offers', 'options' ),
			'slide_bg'   => get_field( 'slide_bg_offers', 'options' ),
		)
	);

endwhile;
get_footer();

get_template_part(
	'template-parts/copyLinkModal',
	null,
	array(
		'image'        => $firstImage,
		'title'        => $pageTitle,
		'vin'          => $vin_number,
		'stock_number' => get_field( 'stock_number' ),
	)
);
?>

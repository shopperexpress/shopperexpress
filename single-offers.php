<?php
get_header();

while (have_posts()) : the_post();
	$post_id = get_the_ID();
	$gallery = get_field( 'gallery' );
	$location = wps_get_term( $post_id, 'location');
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
	$search_inventory_button = get_field('search_inventory_button');

	switch ($condition) {
		case 'Slightly Used':
		$textCondition = __('Market Value','shopperexpress');
		break;
		case 'Used':
		$textCondition = __('Market Value','shopperexpress');
		break;
		default:
		$textCondition = __('MSRP','shopperexpress');
		break;
	}
 ?>
	<div class="detail-section">
		<div class="container">
			<div class="row">
				<div class="col-sm-6">
					<div class="sticky-box">
						<ul class="code-list text-right list-unstyled">
							<li><?php _e('Year & Make','shopperexpress'); ?>: <?php echo $year. ' ' .$make; ?></li>
							<li><?php _e('Model','shopperexpress'); ?>: <?php echo $model; ?></li>
						</ul>
						<div class="detail-slider-holder">
							<?php if ( $gallery ): ?>
								<div class="detail-slider">
									<?php foreach ($gallery as $value): ?>
										<div>
											<?php
											if( $value['image_url'] ):
												echo '<a href="'.$value['image_url'].'" data-fancybox="img-gallery"><img src="'.$value['image_url'].'" alt="image"></a>';

											endif; ?>
										</div>
									<?php endforeach ?>
								</div>
								<div class="detail-slider-nav jcf-scrollable">
									<?php foreach ($gallery as $value): ?>
										<div class="slide">
											<img src="<?php echo $value['image_url']; ?>" srcset="<?php echo $value['image_url']; ?> 2x" alt="image">
										</div>
									<?php endforeach ?>
								</div>
							<?php endif; ?>
						</div>
						<?php if ( function_exists('offers_card_detail') ): ?>
							<dl class="detail-info"><?php echo offers_card_detail($post_id); ?></dl>
						<?php endif; ?>
						<?php $overviewModal = get_field('vehicle_overview'); 
						$featuresAndOptionsModal = get_field('features_options');
						if($overviewModal || $featuresAndOptionsModal){ ?>
						<ul class="details-list list-inline">
							<li class="list-inline-item"><a href="#" data-toggle="modal" data-target="#overviewModal">+<?php _e('See Details','shopperexpress'); ?></a></li>
							<li class="list-inline-item"><a href="#" data-toggle="modal" data-target="#featuresAndOptionsModal">+<?php _e('Features & Options','shopperexpress'); ?></a></li>
						</ul>
						<?php } ?>
					</div>
				</div>
				<div class="col-sm-6">
					<?php if ( have_rows('offers_menu' , 'options' ) ): ?>
						<ul class="anchor-list">
							<?php

							if ( is_user_logged_in() ) {
								$user_info = get_userdata(get_current_user_id());
								$event_attr = '&First='. $user_info->user_firstname .'&last='. $user_info->user_lastname .'&email=' . $user_info->user_email . '&phone=' . get_field( 'phone' , 'user_' . get_current_user_id() );

							}else{
								$event_attr = null;
							}
							while ( have_rows('offers_menu' , 'options' ) ) : the_row();

								if( $image = get_sub_field( 'image' ) ):
									
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
									} ?>
										<li><a href="#" onclick="javascript:inticeAllEvents.<?php echo $event; ?>" data-toggle="tooltip" data-placement="top" title="<?php the_sub_field( 'tooltip' ); ?>"><?php echo wp_get_attachment_image($image['id'],'full'); ?><span class="decor-line"></span></a></li>
									<?php
								endif;
							endwhile;
							?>
						</ul>
						<?php
					endif;
					?>
					<h2><?php echo $year; ?> <?php echo $make; ?> <?php echo $model; ?> <?php echo $trim; ?></h2>
					<?php if ( $message = get_field( 'message' ) ): ?>
						<div class="lead">
							<?php echo $message; ?>
						</div>
					<?php endif; ?>

					<?php $counter_for_button = 0; ?>
					<?php while ( have_rows('offers_flexible_content' , 'options' ) ) : the_row(); ?>
						<div class="info-block" id="block-<?php echo get_row_index(); ?>">
							<div class="heading">
								<?php if ( $image = get_sub_field( 'image' ) ): ?>
									<span class="icon">
										<?php echo wp_get_attachment_image( $image['id'], 'full' ); ?>
									</span>
									<?php
								endif;
								if ( $title = get_sub_field( 'title' ) ):
									?>
									<h3><?php echo $title; ?></h3>
								<?php endif; ?>
							</div>
							<?php
							the_sub_field( 'description' );
							if ($search_inventory_button && $counter_for_button == 0) {
								echo '<a href="'.$search_inventory_button.'" class="btn btn-primary btn-custom btn-block">'.__('Search inventory','shopperexpress').'</a>';
							}
							$counter_for_button++;
							if ( get_row_layout() == 'payment' && have_rows( 'payment_list' ) ){
								?>
								<ul class="payment-info">
									<?php
									while ( have_rows( 'payment_list' ) ) : the_row();
										$link = get_sub_field( 'link' );
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
													if($loanapr) $text .= $loanapr . '% <sub>APR</sub>';
												}else{
													$text = null;
												}
												break;
											
											case 'Disclosure_lease':
												if ( $down_payment && $lease_payment ) {
													$lease_payment = !empty($lease_payment) && $lease_payment != 'None' && $lease_payment>0 ? '$' . number_format($lease_payment) : null;
													$text = !empty($lease_payment) ? '<span class="savings">$' . $down_payment . ' ' . __('DOWN' , 'shopperexpress').' '. $leaseterm . ' ' . __('mos.' , 'shopperexpress') . '</span>' . $lease_payment . ' <sub>/mo</sub>' : null;
												}else{
													$text = null;
												}
												break;
											case 'Disclosure_Cash':
												if ( $condition != 'Slightly Used' && $condition != 'Used' ) {
													$cash_offer = get_field('cash_offer');
													$cash_offer = is_int( $cash_offer ) ? '$'. number_format($cash_offer) : $cash_offer;
													$cash_offer_label = get_field('cash_offer_label');
													$text = !empty($cash_offer) ? '<span class="savings">' . $cash_offer_label . '</span>' . $cash_offer : null;
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
													if($show_popup) echo '<a href="#" data-toggle="modal" data-target="#'.$show_popup.'">';
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
							<?php }elseif( get_row_layout() == 'video' && have_rows( 'video_list' ) ){ ?>
								<ul class="payment-video">
									<?php while ( have_rows( 'video_list' ) ) : the_row(); ?>
										<li>
											<i class="material-icons"><?php _e('check_box','shopperexpress'); ?></i>
											<?php if ( $title = get_sub_field( 'title' ) ): ?>
												<strong class="title"><?php echo $title; ?></strong>
												<?php
											endif;
											the_sub_field( 'description' );
											if( $video_id = get_sub_field( 'video_id' ) ):
												?>
												<p><span class="wistia_embed wistia_async_<?php echo $video_id; ?> popover=true popoverContent=link" style="display: inline; position: relative; "><a class="btn-get-started "  href="#video-<?php echo get_row_index(); ?>"><?php _e('Watch Video','shopperexpress'); ?></a></span></p>
												<div style="display: none;" id="video-<?php echo get_row_index(); ?>">
													<script src="https://fast.wistia.com/embed/medias/<?php echo $video_id; ?>.jsonp" async></script>
													<div class="wistia_responsive_padding" style="padding:56.25% 0 0 0;position:relative;"><div class="wistia_responsive_wrapper" style="height:100%;left:0;position:absolute;top:0;width:100%;"><span class="wistia_embed wistia_async_<?php echo $video_id; ?> popover=true popoverAnimateThumbnail=true videoFoam=true" style="display:inline-block;height:100%;position:relative;width:100%">&nbsp;</span></div></div>
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
							
							<?php $link = get_field( 'link', 'options'); 
							$popup_form = get_field( 'popup_form', 'options');
							if( $link && !$popup_form){
								echo wps_get_link($link,'btn btn-outline-primary', null);
							}elseif($popup_form && $link){
								echo '<a class="btn btn-outline-primary" href="#" data-toggle="modal" data-target="#contactModal">'.$link['title'].'</a>';
							}
							if( $phone = get_field( 'phone', 'options' ) ){
								?>
								<strong class="phone"><?php _e('CALL','shopperexpress'); ?> <a href="tel:<?php echo clean_phone($phone); ?>"><?php echo $phone; ?></a></strong>
								<?php 
							}
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php if( $comment_footer  = get_field( 'offers_comment_footer', 'options' ) ): ?>
		<div class="description-box">
			<div class="container-fluid">
				<?php echo $comment_footer; ?>
			</div>
		</div>
		<?php
	endif;

	$title = get_field( 'title_slider', 'options' );
	$section_bg = get_field( 'section_bg_offers', 'options' );
	$slide_bg = get_field( 'slide_bg_offers', 'options' );

	if ( have_rows( 'slider' , 'options' ) ) :
		?>
		<section class="shop-section filter-section" <?php if ($section_bg) echo 'style="background-color:'.$section_bg.';"' ?>>
			<div class="container-fluid">
				<?php if ( $title ): ?>
					<h2 class="text-center"><?php echo $title; ?></h2>
				<?php endif; ?>
				<ul class="models-filter list-unstyled" data-filter-group="car-type">
					<li class="active"><a href="#" data-filter="all"><?php _e('all vehicles','shopperexpress'); ?></a></li>
					<?php
					while ( have_rows( 'slider' , 'options' ) ) : the_row();
						$type = get_sub_field( 'type' );
						$type_list[seoUrl($type)] = $type;
					endwhile;
					foreach( $type_list as $id => $value ):
						?>
						<li><a href="#" data-filter="<?php echo $id; ?>"><?php echo $value; ?></a></li>
					<?php endforeach; ?>
				</ul>
				<div class="model-slider">
					<?php while ( have_rows( 'slider' , 'options' ) ) : the_row(); ?>
						<div class="slide">
							<a class="model-card" href="<?php echo esc_url(get_sub_field( 'url' )); ?>">
								<?php if ( $image = get_sub_field( 'image' ) ): ?>
									<div class="img-box" <?php if ($slide_bg) echo 'style="background-color:'.$slide_bg.';"'?>>
										<?php echo wp_get_attachment_image( $image['id'], 'full' ); ?>
									</div>
								<?php endif; ?>
								<?php if ( $model = get_sub_field( 'model' ) ): ?>
									<strong class="model"><?php echo $model; ?></strong>
								<?php endif; ?>
								<span class="car-type hidden"><?php echo seoUrl(get_sub_field( 'type' )); ?></span>
							</a>
						</div>
					<?php endwhile; ?>
				</div>
			</div>
		</section>
		<?php
	endif;
endwhile;
get_footer();
?>
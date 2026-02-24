<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package Shopperexpress
 */

get_header();

while ( have_posts() ) :
	the_post();
	$post_type = get_post_type();
	$post_id   = get_the_id();
	$year      = get_field( 'year' );
	$make      = get_field( 'make' );
	$model     = get_field( 'model' );
	$trims     = get_field( 'trims' );
	?>
	<div class="detail-section">
		<div class="container">
			<div class="row">
				<div class="col-sm-6">
					<div class="sticky-box">
						<div class="detail-top-row">
							<ol class="breadcrumbs">
								<li><a href="<?php echo get_post_type_archive_link( $post_type ); ?>"><?php _e( 'All Models', 'shopperexpress' ); ?></a></li>
							</ol>
							<ul class="code-list text-right text-capitalize list-unstyled">
								<li><?php _e( 'Year & Model', 'shopperexpress' ); ?>: <?php echo $year . ' ' . $model; ?></li>
								<?php if ( ! empty( $trims[0] ) ) : ?>
									<li><?php _e( 'Trim', 'shopperexpress' ); ?>: <?php echo $trims[0]['trim']; ?></li>
								<?php endif; ?>
							</ul>
						</div>
						<?php
						get_template_part(
							'template-parts/components/detail',
							'slider',
							array(
								'post_type'  => $post_type,
								'evoxnumber' => get_field( 'evoxnumber' ),
							)
						);
						get_template_part(
							'template-parts/detail',
							'info',
							array(
								'post_type' => $post_type,
								'post_id'   => $post_id,
							)
						);
						?>
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
								'evoxnumber' => get_field( 'evoxnumber' ),
							)
						);
						get_template_part( 'template-parts/components/anchor', 'copy' );
						?>
					</div>
					<h2><?php echo $year; ?> <?php echo $make; ?> <?php echo $model; ?></h2>
					<div class="info-block">
						<?php
						while ( have_rows( 'info_block', 'options' ) ) :
							the_row();
							$icon    = get_sub_field( 'icon' );
							$heading = get_sub_field( 'heading' );
							$text    = get_sub_field( 'text' );

							if ( $icon || $heading ) :
								?>
								<div class="heading">
									<?php if ( $icon ) : ?>
										<span class="icon">
											<?php echo get_attachment_image( $icon['id'] ); ?>
										</span>
										<?php
									endif;
									if ( $heading ) :
										?>
										<h3><?php echo esc_html( $heading ); ?></h3>
									<?php endif; ?>
								</div>
								<?php
							endif;

							echo wp_kses_post( get_sub_field( 'text' ) );

						endwhile;
						if ( $searchinventory = get_field( 'searchinventory' ) ) :
							?>
							<a href="<?php echo esc_url( $searchinventory ); ?>" class="btn btn-primary btn-custom btn-block"><?php esc_html_e( 'Search Inventory', 'shopperexpress' ); ?></a>
							<?php
						endif;
						if ( $trims ) :
							?>
							<ul class="payment-info">
								<?php foreach ( $trims as $index => $item ) : ?>
									<li>
										<div class="text-holder">
											<h4 class="h3"><?php echo $item['trim']; ?></h4>
											<p><?php esc_html_e( 'MSRP Starting at', 'shopperexpress' ); ?>:</p>
										</div>
										<strong class="price"><?php echo get_field( 'prices' )[ $index ]['price']; ?></strong>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
					<?php
					while ( have_rows( 'research_flexible_content', 'options' ) ) :
						the_row();
						?>
						<div class="info-block" id="block-<?php echo get_row_index(); ?>">
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
							the_sub_field( 'description' );
							if ( get_row_layout() == 'payment' && have_rows( 'payment_list' ) ) {
								?>
								<ul class="payment-info">
									<?php
									while ( have_rows( 'payment_list' ) ) :
										the_row();
										$link         = get_sub_field( 'link' );
										$show_payment = get_sub_field( 'show_payment' );
										$lock         = get_sub_field( 'lock' );
										$event        = get_event_script( get_sub_field( 'event' ), $location, $vin_number );
										$down_payment = ! empty( $down_payment ) ? $down_payment : null;

										switch ( $show_payment ) {
											case 'lease-payment':
												if ( $condition != 'Slightly Used' && $condition != 'Used' ) {
													$lease_payment = ! empty( $lease_payment ) && $lease_payment != 'None' ? '$' . number_format( floatval( $lease_payment ) ) : null;
													$text          = ! empty( $lease_payment ) ? '<span class="savings">$' . $down_payment . ' ' . __( 'DOWN', 'shopperexpress' ) . '</span>' . $lease_payment . ' <sub>/mo</sub>' : null;
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

										if ( $condition != 'Used' || $title != 'Lease' ) :
											if ( $lock == true && ! empty( $text ) && ( ! empty( $loan_payment ) and ( clean_phone( $loan_payment ) > 0 || clean_phone( $lease_payment ) > 0 ) ) ) :
												?>
												<li>
													<?php
													if ( wps_auth() ) {
														echo '<a href="#" onclick="javascript:inticeAllEvents.' . $event . '">';}
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
													if ( ! wps_auth() ) :
														echo '<span class="unlock-item" data-toggle="modal" data-target="#unlockSavingsModal"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
                                                    <path
                                                        d="M840-640q32 0 56 24t24 56v80q0 7-1.5 15t-4.5 15L794-168q-9 20-30 34t-44 14H400q-33 0-56.5-23.5T320-200v-407q0-16 6.5-30.5T344-663l217-216q15-14 35.5-17t39.5 7q19 10 27.5 28t3.5 37l-45 184h218ZM160-120q-33 0-56.5-23.5T80-200v-360q0-33 23.5-56.5T160-640q33 0 56.5 23.5T240-560v360q0 33-23.5 56.5T160-120Z"
                                                    /></svg
                                                    >' . __( 'UNLOCK PAYMENT', 'shopperexpress' ) . '</span>';
													elseif ( wps_auth() ) :
														echo '<strong class="price">' . $text . '</strong>';
													endif;
													if ( wps_auth() ) {
														echo '</a>';
													}
													?>
												</li>
											<?php elseif ( $lock == false ) : ?>
												<li>
													<div class="text-holder">
														<?php if ( $title ) : ?>
															<h4 class="h3"><?php echo $title; ?></h4>
															<?php
														endif;
														the_sub_field( 'description' );
														?>
													</div>
													<a href="#" onclick="javascript:inticeAllEvents.<?php echo $event; ?>" class="btn btn-primary"><?php the_sub_field( 'text' ); ?></a>
												</li>
												<?php
											endif;
										endif;
									endwhile;
									?>
								</ul>
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
												<p><span class="wistia_embed wistia_async_<?php echo $video_id; ?> popover=true popoverContent=link" style="display: inline; position: relative; "><a class="btn-get-started " href="#video-<?php echo get_row_index(); ?>"><?php esc_html_e( 'Watch Video', 'shopperexpress' ); ?></a></span></p>
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
				</div>
			</div>
		</div>
	</div>
	<?php
	get_template_part( 'template-parts/accordion', 'detail-info', array( 'vin_number' => get_field( 'chrome' ) ) );

	$evo_iframe_ration = get_field( 'evo_iframe_ration', 'option' );

	$width  = $evo_iframe_ration == 'custom' ? get_field( 'custom_width', 'option' ) : 1024;
	$height = $evo_iframe_ration == 'custom' ? get_field( 'custom_height', 'option' ) : 768;

	if ( have_rows( 'extspinvifs' ) ) :
		?>
		<div class="evo-slider-wrapp">
			<div class="container">
				<div class="heading text-center mb-5">
					<img class="decor-icon" src="<?php echo App\asset_url( 'images/360.svg' ); ?>" alt="image description">
					<h3>360° Spin</h3>
				</div>
				<div class="evo-slider-holder">
					<div class="evo-slider-btn-overlay">
						<span id="click-and-drag" class="evo-slider-btn-click" aria-hidden="true">
							<img src="<?php echo App\asset_url( 'images/click-and-drag.svg' ); ?>" alt="click and drag">
						</span>
					</div>
					<div class="evo-slider">
						<?php
						while ( have_rows( 'extspinvifs' ) ) :
							the_row();
							if ( $spin = get_sub_field( 'spin' ) ) :
								?>
								<div class="slide">
									<iframe class="ratio-<?php echo esc_attr( $evo_iframe_ration ); ?>" src="<?php echo $spin; ?>" title="360 video" width="<?php echo esc_attr( $width ); ?>" height="<?php echo esc_attr( $height ); ?>" frameborder="0" loading="lazy"></iframe>
								</div>
								<?php
							endif;
						endwhile;
						?>
					</div>
				</div>
			</div>
		</div>
		<?php
	endif;

	get_template_part(
		'template-parts/accordion',
		null,
		array(
			'post_type' => $post_type,
			'type'      => 'random',
		)
	);

	if ( $comment_footer  = get_field( 'comment_footer', 'options' ) ) :
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
			'section_bg' => get_field( 'section_bg', 'options' ),
			'slide_bg'   => get_field( 'slide_bg', 'options' ),
		)
	);

	// start
	$pageTitle   = array();
	$pageTitle[] = $year;
	$pageTitle[] = $make;
	$pageTitle[] = $model;
	$pageTitle[] = $trim;
	$pageTitle   = implode( ' ', $pageTitle );
	$gallery     = get_field( 'imagelistpath' );
	$firstImage  = $gallery[0]['image_url'];
	$vin_number  = null;
	$args        = array(
		'image'        => $firstImage,
		'title'        => $pageTitle,
		'vin'          => $vin_number,
		'stock_number' => get_field( 'stock_number' ),
	);
	// end

endwhile;
get_footer();

get_template_part( 'template-parts/copyLinkModal', null, $args );
get_template_part( 'template-parts/modal', 'evo' );
?>

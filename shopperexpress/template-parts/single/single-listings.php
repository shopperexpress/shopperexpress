<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package Shopperexpress
 */

$post_type        = ! empty( $args['post_type'] ) ? $args['post_type'] : get_post_type();
$current_protocol = is_ssl() ? 'https://' : 'http://';
$firstImage       = null;
if ( is_user_logged_in() ) {
	acf_form_head();
}

// Record the view before get_header() sends output, so the dedup cookie can still be set.
if ( ! is_preview() ) {
	$vdp_vin_for_view_count = get_field( 'vin_number', get_queried_object_id() );
	if ( $vdp_vin_for_view_count ) {
		\App\Components\Base\Vehicle_Views::record_view( $vdp_vin_for_view_count );
	}
}

get_header();

while ( have_posts() ) :
	the_post();
	$get_post_type   = '-' . $post_type;
	$use_images_list = get_field( 'use_images_list' );
	$post_id         = get_the_id();
	$gallery         = 'alternative' === $use_images_list ? get_field( 'gallery_srp' ) : get_field( 'gallery' );
	$vin_number      = get_field( 'vin_number' );
	$location        = get_field( 'location' );
	$year            = get_field( 'year' );
	$make            = get_field( 'make' );
	$model           = get_field( 'model' );
	$trim            = get_field( 'trim' );
	$down_payment    = get_field( 'down_payment' );
	$lease_payment   = get_field( 'lease_payment' );
	$loan_payment    = get_field( 'loan_payment' );
	$condition       = get_field( 'condition' );
	$archive_link    = get_post_type_archive_link( $post_type );
	$make_slug       = get_field( 'make' );
	$model_slug      = get_field( 'model' );
	$condition_slug  = get_field( 'condition' );
	$drivetrain      = get_field( 'drivetrain' );
	$dealer_name     = get_field( 'dealer_name' );
	$exterior_color  = get_field( 'exterior_color' );
	$dealer_id       = $location;

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
			<?php get_template_part( 'template-parts/banner', 'single', array( 'post_type' => $post_type ) ); ?>
			<div class="row">
				<div class="col-sm-6">
					<div class="sticky-box">
						<div class="detail-top-row">
							<ol class="breadcrumbs">
								<li><a href="<?php echo add_query_arg( array( 'year' => $year ), $archive_link ); ?>"><?php echo $year; ?></a></li>
								<li><a href="<?php echo add_query_arg( array( 'condition' => $condition_slug ), $archive_link ); ?>"><?php echo $condition; ?></a></li>
								<li><a href="<?php echo add_query_arg( array( 'make' => $make_slug ), $archive_link ); ?>"><?php echo strlen( $make ) >= 10 ? mb_strimwidth( $make, 0, 10, '...' ) : $make; ?></a></li>
								<li><a href="<?php echo add_query_arg( array( 'model' => $model_slug ), $archive_link ); ?>"><?php echo strlen( $model ) >= 15 ? mb_strimwidth( $model, 0, 15, '...' ) : $model; ?></a></li>
							</ol>
							<?php if ( have_rows( 'text_list', 'options' ) ) : ?>
								<ul class="code-list text-right list-unstyled text-capitalize">
									<?php
									while ( have_rows( 'text_list', 'options' ) ) :
										the_row();
										$text   = array();
										$text[] = get_sub_field( 'text' );
										while ( have_rows( 'fields' ) ) :
											the_row();
											$field_slug = get_sub_field( 'field_slug' );
											if ( $field_slug ) {
												$text[] = get_field( $field_slug, $post_id );
											}
										endwhile;

										if ( $text ) :
											?>
											<li><?php echo implode( ' ', $text ); ?></li>
											<?php
										endif;
									endwhile;
									?>
								</ul>
							<?php endif; ?>
						</div>
						<div class="badges-wrapp">
							<?php
							$status = get_field( 'vehicle-status', $post_id ) ? get_field( 'vehicle-status', $post_id ) : null;
							if ( $status ) :
								$badge = \App\resolve_badge_style( $status, 'vdp', $post_type );
								?>
								<div class="badges-list">
									<span class="card-badge-status"<?php echo $badge['style'] ? ' style="' . esc_attr( $badge['style'] ) . '"' : ''; ?>><?php echo esc_html( $badge['text'] ); ?></span>
								</div>
							<?php endif; ?>
							<?php if ( wps_check_current_usser() ) : ?>
								<button class="btn btn-primary btn-edit" type="button" data-toggle="modal" data-target="#editModal">
									<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ffffff">
										<path
											d="M200-200h57l391-391-57-57-391 391v57Zm-40 80q-17 0-28.5-11.5T120-160v-97q0-16 6-30.5t17-25.5l505-504q12-11 26.5-17t30.5-6q16 0 31 6t26 18l55 56q12 11 17.5 26t5.5 30q0 16-5.5 30.5T817-647L313-143q-11 11-25.5 17t-30.5 6h-97Zm600-584-56-56 56 56Zm-141 85-28-29 57 57-29-28Z" />
									</svg>
									<?php esc_html_e( 'Edit', 'shopperexpress' ); ?>

								</button>
							<?php endif; ?>
						</div>
						<div class="detail-slider-holder">
							<?php
							$alt_array = array( $year, $make, $model, $trim, $exterior_color, '- ' . get_bloginfo( 'name' ) . ' - Image' );
							if ( ! empty( $gallery ) ) {
								set_backup_images( $vin_number, $gallery );
							} else {
								$gallery = get_backup_images( $vin_number );
							}
							?>
							<?php if ( ! empty( $gallery ) && is_array( $gallery ) && count( $gallery ) >= 1 ) : ?>
								<div class="detail-slider-wrapper">
									<?php
									$slider         = get_field( 'slider-single_slider', 'options' );
									$autoplay       = ! empty( $slider['autoplay'] ) ? 'true' : 'false';
									$autoplay_speed = ! empty( $slider['autoplay_speed'] ) ? $slider['autoplay_speed'] * 60 * 60 : 3000;
									?>
									<div class="detail-slider" data-autoplay="<?php echo $autoplay; ?>" data-autoplay-speed="<?php echo $autoplay_speed; ?>">
										<?php
										$i = 1;
										foreach ( $gallery as $value ) :
											$alt              = $alt_array;
											$alt[]            = $i;
											$alt              = implode( ' ', $alt );
											$image_background = ! empty( $value['image_background'] ) ? $value['image_background'] : '';
											$image_reverse    = ! empty( $value['image_reverse'] ) ? ' class="reverse-image" ' : '';
											?>
											<div class="slide
											<?php
											if ( $image_background ) :
												?>
												bg-cover" style="background-image: url(<?php echo get_field( 'background_image', 'option' ); ?>)<?php endif; ?>">
												<?php
												$image_url = ! empty( $value['image_url'] ) ? $value['image_url'] : '';
												if ( ! empty( $image_url ) ) :
													$image_url = strpos( $image_url, 'http://' ) === 0 ? $current_protocol . substr( $image_url, 7 ) : $current_protocol . substr( $image_url, 8 );
													if ( $i == 1 ) {
														$firstImage = $image_url;
													}
													echo '<a href="' . $value['image_url'] . '" ' . $image_reverse . ' data-fancybox="img-gallery"><img src="' . $value['image_url'] . '" alt="' . esc_attr( $alt ) . '"></a>';
												else :
													$get_default_image = get_default_image( $post_type );

													echo '<a href="' . esc_url( $get_default_image ) . '" ' . $image_reverse . ' data-fancybox="img-gallery">'
														. '<img src="' . esc_url( $get_default_image ) . '" alt="' . esc_attr( $alt ) . '">'
														. '</a>';

												endif;
												?>
											</div>
											<?php
											++$i;
										endforeach
										?>
									</div>
									<?php
									$spin_data_provider = get_field( 'spin_data_provider', 'options' );
									?>
								</div>
								<?php if ( $gallery ) : ?>
									<div class="detail-slider-nav">
										<button type="button" class="slider-nav-arrow slider-nav-prev" aria-label="<?php esc_attr_e( 'Previous thumbnails', 'shopperexpress' ); ?>">
											<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px"><path d="m432-480 156 156q11 11 11 28t-11 28q-11 11-28 11t-28-11L348-452q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l184-184q11-11 28-11t28 11q11 11 11 28t-11 28L432-480Z" /></svg>
										</button>
										<div class="slider-nav-wrap">
											<div class="slider-nav-holder">
												<?php
												foreach ( $gallery as $value ) :
												$alt       = $alt_array;
												$alt[]     = $i;
												$alt       = implode( ' ', $alt );
												$image_url = ! empty( $value['image_url'] ) ? $value['image_url'] : '';
												if ( $image_url ) :
													$image_background = $value['image_background'];
													$image_reverse    = $value['image_reverse'];
													$image_url        = strpos( $image_url, 'http://' ) === 0 ? $current_protocol . substr( $image_url, 7 ) : $current_protocol . substr( $image_url, 8 );
													?>
													<div class="slide
													<?php
													if ( $image_reverse ) :
														?>
														reverse-image <?php endif; ?>
														<?php
														if ( $image_background ) :
															?>
														bg-cover" style="background-image: url(<?php echo App\asset_url( 'images/360-background.webp' ); ?>)<?php endif; ?>">
														<img src="<?php echo $image_url; ?>" srcset="<?php echo $image_url; ?> 2x" alt="<?php echo esc_attr( $alt ); ?>">
													</div>
													<?php
												else :
													$get_default_image = get_default_image( $post_type );
													?>
													<div class="slide
													<?php
													if ( $image_reverse ) :
														?>
														reverse-image <?php endif; ?>
														<?php
														if ( $image_background ) :
															?>
														bg-cover" style="background-image: url(<?php echo App\asset_url( 'images/360-background.webp' ); ?>)<?php endif; ?>">
														<img src="<?php echo $get_default_image; ?>" srcset="<?php echo $get_default_image; ?> 2x" alt="<?php echo esc_attr( $alt ); ?>">
													</div>
													<?php
												endif;
											endforeach;
											?>
										</div>
									</div>
									<button type="button" class="slider-nav-arrow slider-nav-next" aria-label="<?php esc_attr_e( 'Next thumbnails', 'shopperexpress' ); ?>">
										<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px"><path d="M504-480 348-636q-11-11-11-28t11-28q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L404-268q-11 11-28 11t-28-11q-11-11-11-28t11-28l156-156Z" /></svg>
									</button>
								</div>
								<!-- <div class="range-box">
									<input aria-label="Carousel thumbnails slider" value="0" min="0" max="100" step="1" type="range">
								</div> -->
								<?php endif; ?>
								<?php if ( 'spinmaker' === $spin_data_provider ) : ?>
									<?php if ( $vin_number ) : ?>
										<a href="javascript:void(0)" class="btn-explore-vehicle" onclick="javascript:inticeAllEvents.launchSpinmaker('<?php echo esc_js( $location ); ?>','<?php echo esc_js( $vin_number ); ?>')">
											<svg class="svg-360" x="0px" y="0px" viewBox="0 0 49 32" xml:space="preserve">
												<path d="M16.666 5.326c-0.332 0-0.656-0.053-0.972-0.158s-0.595-0.246-0.836-0.421l-0.57 1.113c0.308 0.223 0.675 0.393 1.104 0.511s0.862 0.176 1.302 0.176c0.609 0 1.106-0.095 1.492-0.285s0.674-0.441 0.864-0.755c0.19-0.314 0.285-0.651 0.285-1.013 0-0.542-0.201-0.988-0.601-1.339-0.281-0.245-0.659-0.398-1.125-0.472l1.427-1.626v-0.95h-4.431v1.176h2.697l-1.296 1.483v0.959h0.66c0.416 0 0.717 0.069 0.904 0.207s0.28 0.329 0.28 0.57c0 0.253-0.105 0.454-0.316 0.602s-0.5 0.221-0.868 0.221zM21.332 6.19c0.422 0.238 0.922 0.357 1.501 0.357 0.452 0 0.859-0.085 1.221-0.258s0.648-0.416 0.859-0.732c0.211-0.316 0.316-0.689 0.316-1.117 0-0.397-0.098-0.744-0.294-1.040s-0.457-0.524-0.782-0.687c-0.326-0.163-0.693-0.244-1.103-0.244-0.302 0-0.577 0.040-0.828 0.121s-0.466 0.202-0.646 0.362c-0.035 0.031-0.053 0.081-0.086 0.114 0.018-0.591 0.168-1.056 0.474-1.371 0.326-0.335 0.76-0.502 1.302-0.502 0.217 0 0.425 0.023 0.624 0.068s0.386 0.128 0.561 0.249l0.542-1.076c-0.223-0.139-0.488-0.246-0.796-0.321s-0.627-0.113-0.958-0.113c-0.639 0-1.201 0.133-1.687 0.398s-0.865 0.651-1.139 1.157c-0.274 0.507-0.412 1.127-0.412 1.863 0 0.675 0.116 1.247 0.348 1.713s0.559 0.821 0.981 1.058zM21.78 4.028c0.093-0.141 0.221-0.255 0.384-0.339s0.353-0.127 0.57-0.127c0.326 0 0.587 0.086 0.782 0.258s0.294 0.402 0.294 0.692c0 0.187-0.045 0.352-0.136 0.497s-0.216 0.255-0.375 0.33c-0.16 0.076-0.342 0.113-0.547 0.113-0.235 0-0.434-0.041-0.597-0.122s-0.29-0.193-0.38-0.335c-0.090-0.141-0.135-0.3-0.135-0.475 0-0.187 0.046-0.351 0.14-0.493zM29.873 0.384c-0.407-0.256-0.872-0.384-1.397-0.384-0.518 0-0.981 0.128-1.388 0.384s-0.73 0.627-0.968 1.113c-0.238 0.485-0.357 1.077-0.357 1.776s0.119 1.292 0.357 1.777c0.238 0.485 0.561 0.856 0.968 1.112s0.869 0.384 1.388 0.384c0.525 0 0.99-0.127 1.397-0.384s0.729-0.627 0.968-1.112c0.238-0.486 0.357-1.077 0.357-1.777s-0.119-1.291-0.357-1.776c-0.238-0.486-0.561-0.857-0.968-1.113zM29.556 4.44c-0.106 0.302-0.25 0.523-0.434 0.665s-0.399 0.212-0.646 0.212c-0.235 0-0.446-0.071-0.633-0.212s-0.334-0.364-0.443-0.665c-0.109-0.301-0.163-0.69-0.163-1.166s0.054-0.865 0.163-1.166c0.109-0.301 0.256-0.523 0.443-0.665s0.398-0.213 0.633-0.213c0.247 0 0.462 0.071 0.646 0.213s0.329 0.364 0.434 0.665c0.106 0.302 0.158 0.69 0.158 1.166s-0.053 0.865-0.158 1.166zM34.81 2.364c0.141-0.232 0.212-0.492 0.212-0.782s-0.071-0.551-0.212-0.787c-0.142-0.235-0.333-0.42-0.574-0.556s-0.51-0.204-0.805-0.204c-0.29 0-0.555 0.068-0.796 0.204s-0.433 0.321-0.574 0.556c-0.142 0.235-0.213 0.497-0.213 0.787s0.071 0.55 0.213 0.782c0.141 0.233 0.333 0.418 0.574 0.556s0.506 0.208 0.796 0.208c0.295 0 0.564-0.069 0.805-0.208s0.432-0.324 0.574-0.556zM33.865 2.311c-0.127 0.075-0.268 0.113-0.425 0.113-0.163 0-0.307-0.036-0.434-0.108s-0.226-0.171-0.298-0.294c-0.072-0.123-0.109-0.269-0.109-0.439s0.036-0.316 0.109-0.443c0.072-0.127 0.172-0.226 0.298-0.298s0.271-0.109 0.434-0.109c0.157 0 0.298 0.036 0.425 0.109s0.226 0.173 0.298 0.302c0.072 0.13 0.109 0.276 0.109 0.439s-0.036 0.306-0.109 0.43c-0.072 0.124-0.171 0.223-0.298 0.299zM13.624 15.943l3.163 3.163c-3.811-0.689-6.751-1.713-8.819-3.071s-3.102-2.524-3.102-3.497c0-1.297 1.733-2.838 5.2-4.622 0.962-0.495 2.033-0.914 3.194-1.272l-1.406-4.607c-1.716 0.515-3.334 1.138-4.829 1.895-4.683 2.372-7.025 5.241-7.025 8.606 0 2.798 1.683 5.271 5.048 7.42s7.643 3.568 12.833 4.257l-4.257 4.379 3.406 3.406 9.731-9.731-9.731-9.731-3.406 3.406zM41.631 3.932c-1.683-0.852-3.511-1.546-5.474-2.092l-2.013 4.444c1.663 0.418 3.145 0.962 4.446 1.631 3.467 1.784 5.2 3.325 5.2 4.622 0 0.933-0.841 1.997-2.524 3.193s-4.085 2.2-7.207 3.011v4.987c4.338-0.973 7.856-2.453 10.552-4.44s4.044-4.237 4.044-6.751c0-3.365-2.342-6.234-7.025-8.606z" />
											</svg>
											<span><?php esc_html_e( 'Explore This Vehicle', 'shopperexpress' ); ?></span>
										</a>
									<?php endif; ?>
								<?php else : ?>
									<?php
									$API_KEY            = get_field( 'spin_data_api_key', 'option' );
									$cid                = get_field( 'spin_data_id', 'option' );
									$stringToHash       = $API_KEY . $cid . $vin_number;
									$auth               = hash( 'sha512', $stringToHash );
									$modal              = $spin_data_provider === 'evo' ? 'evo-slider-modal' : '';

									$classes = array(
										'btn-spin',
										'btn-explore-vehicle',
										$spin_data_provider === 'FlickFusion'
										? 'spin-video'
										: 'spin-' . $spin_data_provider,
									);

									$attrs = array();

									if ( $spin_data_provider === 'FlickFusion' ) {
										$attrs['data-url'] = get_vehicle_spin( $vin_number );
									}

									if ( $spin_data_provider === 'dealerimage' ) {
										$attrs['data-dealer-id'] = $cid;
									}

									if ( $API_KEY && $cid && $vin_number && $spin_data_provider != 'dealerimage' ) {
										$attrs['data-auth']     = $auth;
										$attrs['data-clientid'] = $cid;
									}
									?>
										<a
											href="#<?php echo esc_attr( $modal ); ?>"
											data-type="iframe"
											data-fancybox
											class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
											style="display: none;"
											<?php foreach ( $attrs as $key => $value ) : ?>
												<?php echo esc_attr( $key ); ?>="<?php echo esc_attr( $value ); ?>"
											<?php endforeach; ?>
										>
										<svg class="svg-360" x="0px" y="0px" viewBox="0 0 49 32" xml:space="preserve">
											<path d="M16.666 5.326c-0.332 0-0.656-0.053-0.972-0.158s-0.595-0.246-0.836-0.421l-0.57 1.113c0.308 0.223 0.675 0.393 1.104 0.511s0.862 0.176 1.302 0.176c0.609 0 1.106-0.095 1.492-0.285s0.674-0.441 0.864-0.755c0.19-0.314 0.285-0.651 0.285-1.013 0-0.542-0.201-0.988-0.601-1.339-0.281-0.245-0.659-0.398-1.125-0.472l1.427-1.626v-0.95h-4.431v1.176h2.697l-1.296 1.483v0.959h0.66c0.416 0 0.717 0.069 0.904 0.207s0.28 0.329 0.28 0.57c0 0.253-0.105 0.454-0.316 0.602s-0.5 0.221-0.868 0.221zM21.332 6.19c0.422 0.238 0.922 0.357 1.501 0.357 0.452 0 0.859-0.085 1.221-0.258s0.648-0.416 0.859-0.732c0.211-0.316 0.316-0.689 0.316-1.117 0-0.397-0.098-0.744-0.294-1.040s-0.457-0.524-0.782-0.687c-0.326-0.163-0.693-0.244-1.103-0.244-0.302 0-0.577 0.040-0.828 0.121s-0.466 0.202-0.646 0.362c-0.035 0.031-0.053 0.081-0.086 0.114 0.018-0.591 0.168-1.056 0.474-1.371 0.326-0.335 0.76-0.502 1.302-0.502 0.217 0 0.425 0.023 0.624 0.068s0.386 0.128 0.561 0.249l0.542-1.076c-0.223-0.139-0.488-0.246-0.796-0.321s-0.627-0.113-0.958-0.113c-0.639 0-1.201 0.133-1.687 0.398s-0.865 0.651-1.139 1.157c-0.274 0.507-0.412 1.127-0.412 1.863 0 0.675 0.116 1.247 0.348 1.713s0.559 0.821 0.981 1.058zM21.78 4.028c0.093-0.141 0.221-0.255 0.384-0.339s0.353-0.127 0.57-0.127c0.326 0 0.587 0.086 0.782 0.258s0.294 0.402 0.294 0.692c0 0.187-0.045 0.352-0.136 0.497s-0.216 0.255-0.375 0.33c-0.16 0.076-0.342 0.113-0.547 0.113-0.235 0-0.434-0.041-0.597-0.122s-0.29-0.193-0.38-0.335c-0.090-0.141-0.135-0.3-0.135-0.475 0-0.187 0.046-0.351 0.14-0.493zM29.873 0.384c-0.407-0.256-0.872-0.384-1.397-0.384-0.518 0-0.981 0.128-1.388 0.384s-0.73 0.627-0.968 1.113c-0.238 0.485-0.357 1.077-0.357 1.776s0.119 1.292 0.357 1.777c0.238 0.485 0.561 0.856 0.968 1.112s0.869 0.384 1.388 0.384c0.525 0 0.99-0.127 1.397-0.384s0.729-0.627 0.968-1.112c0.238-0.486 0.357-1.077 0.357-1.777s-0.119-1.291-0.357-1.776c-0.238-0.486-0.561-0.857-0.968-1.113zM29.556 4.44c-0.106 0.302-0.25 0.523-0.434 0.665s-0.399 0.212-0.646 0.212c-0.235 0-0.446-0.071-0.633-0.212s-0.334-0.364-0.443-0.665c-0.109-0.301-0.163-0.69-0.163-1.166s0.054-0.865 0.163-1.166c0.109-0.301 0.256-0.523 0.443-0.665s0.398-0.213 0.633-0.213c0.247 0 0.462 0.071 0.646 0.213s0.329 0.364 0.434 0.665c0.106 0.302 0.158 0.69 0.158 1.166s-0.053 0.865-0.158 1.166zM34.81 2.364c0.141-0.232 0.212-0.492 0.212-0.782s-0.071-0.551-0.212-0.787c-0.142-0.235-0.333-0.42-0.574-0.556s-0.51-0.204-0.805-0.204c-0.29 0-0.555 0.068-0.796 0.204s-0.433 0.321-0.574 0.556c-0.142 0.235-0.213 0.497-0.213 0.787s0.071 0.55 0.213 0.782c0.141 0.233 0.333 0.418 0.574 0.556s0.506 0.208 0.796 0.208c0.295 0 0.564-0.069 0.805-0.208s0.432-0.324 0.574-0.556zM33.865 2.311c-0.127 0.075-0.268 0.113-0.425 0.113-0.163 0-0.307-0.036-0.434-0.108s-0.226-0.171-0.298-0.294c-0.072-0.123-0.109-0.269-0.109-0.439s0.036-0.316 0.109-0.443c0.072-0.127 0.172-0.226 0.298-0.298s0.271-0.109 0.434-0.109c0.157 0 0.298 0.036 0.425 0.109s0.226 0.173 0.298 0.302c0.072 0.13 0.109 0.276 0.109 0.439s-0.036 0.306-0.109 0.43c-0.072 0.124-0.171 0.223-0.298 0.299zM13.624 15.943l3.163 3.163c-3.811-0.689-6.751-1.713-8.819-3.071s-3.102-2.524-3.102-3.497c0-1.297 1.733-2.838 5.2-4.622 0.962-0.495 2.033-0.914 3.194-1.272l-1.406-4.607c-1.716 0.515-3.334 1.138-4.829 1.895-4.683 2.372-7.025 5.241-7.025 8.606 0 2.798 1.683 5.271 5.048 7.42s7.643 3.568 12.833 4.257l-4.257 4.379 3.406 3.406 9.731-9.731-9.731-9.731-3.406 3.406zM41.631 3.932c-1.683-0.852-3.511-1.546-5.474-2.092l-2.013 4.444c1.663 0.418 3.145 0.962 4.446 1.631 3.467 1.784 5.2 3.325 5.2 4.622 0 0.933-0.841 1.997-2.524 3.193s-4.085 2.2-7.207 3.011v4.987c4.338-0.973 7.856-2.453 10.552-4.44s4.044-4.237 4.044-6.751c0-3.365-2.342-6.234-7.025-8.606z" />
										</svg>
										<span><?php esc_html_e( 'Explore This Vehicle', 'shopperexpress' ); ?></span>
									</a>
								<?php endif; ?>
							<?php else : ?>
								<div class="detail-slider">
									<?php
									if ( function_exists( 'default_image' ) ) {
										echo default_image( 'slide', '', $alt_array );
									}
									?>
								</div>
							<?php endif; ?>
						</div>
						<?php
						$badges_html = '';

						$certified_custom_url = get_field( 'certified_custom_url' );
						if ( have_rows( 'certified_badge', 'options' ) ) :
							while ( have_rows( 'certified_badge', 'options' ) ) :
								the_row();
								$image          = get_sub_field( 'image' );
								$show_badges_on = get_sub_field( 'show_badges_on' );
								$show_badges_on = is_array( $show_badges_on ) ? $show_badges_on : array( $show_badges_on );

								if ( $certified_custom_url && get_sub_field( 'show' ) && $image && in_array( $post_type, $show_badges_on ) ) {
									$badges_html .= '<li><a href="' . esc_url( $certified_custom_url ) . '" target="_blank">' . get_attachment_image( $image ) . '</a></li>';
								}
							endwhile;
						endif;

						if ( have_rows( 'additional_custom_badges', 'options' ) ) :
							while ( have_rows( 'additional_custom_badges', 'options' ) ) :
								the_row();
								$show_badges_on = get_sub_field( 'show_badges_on' );
								$show_badges_on = is_array( $show_badges_on ) ? $show_badges_on : array( $show_badges_on );
								$action         = get_sub_field( 'action' );
								if ( get_sub_field( 'show' ) && in_array( $post_type, $show_badges_on ) ) {
									$image = get_sub_field( 'image' );
									$url   = $action == 'api' ? add_query_arg(
										array(
											'action'      => 'get_pdf',
											'vin_number'  => $vin_number,
											'dealer_name' => $dealer_name,
										),
										admin_url( 'admin-ajax.php' )
									) : str_replace( '{VIN}', $vin_number, get_sub_field( 'url' ) );
									if ( $url && $image ) :
										if ( $action == 'api' ) :
											if ( get_transient( 'vdr_error_' . $vin_number ) ) :
												// VDR API returned an error on the first attempt — hide for all users.
											else :
												$ga_label     = esc_attr( get_sub_field( 'ga_event_label' ) );
												$error_msg    = esc_attr( get_field( 'vdr_error_message', 'options' ) );
												$data_attrs   = ' data-pdf';
												if ( $ga_label ) {
													$data_attrs .= ' data-ga-label="' . $ga_label . '"';
												}
												if ( $error_msg ) {
													$data_attrs .= ' data-error-message="' . $error_msg . '"';
												}
												$badges_html .= '<li><a href="' . esc_url( $url ) . '"' . $data_attrs . '>' . get_attachment_image( $image ) . '</a></li>';
											endif;
										else :
											$badges_html .= '<li><a href="' . esc_url( $url ) . '" target="_blank">' . get_attachment_image( $image ) . '</a></li>';
										endif;
									endif;
								}
							endwhile;
						endif;

						$ipacket_field = $post_type === 'used-listings' ? 'ipacket_used' : 'ipacket_new';
						$ipacket       = get_field( $ipacket_field, 'options' );
						if ( $ipacket && ! empty( $ipacket['show'] ) && ! empty( $ipacket['image'] ) && ! empty( $ipacket['url'] ) ) {
							$ipacket_url  = str_replace( '{VIN}', $vin_number, $ipacket['url'] );
							$badges_html .= '<li><a href="' . esc_url( $ipacket_url ) . '" target="_blank">' . get_attachment_image( $ipacket['image'] ) . '</a></li>';
						}

						if ( ! empty( $badges_html ) ) :
							?>
							<div class="details-html">
								<ul class="details-badges">
									<?php echo $badges_html; ?>
								</ul>
							</div>
						<?php endif; ?>

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
							<li class="list-inline-item"><a href="#" data-toggle="modal" data-target="#featuresAndOptionsModal">+<?php esc_html_e( 'Features & Options', 'shopperexpress' ); ?></a></li>
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
					<h2><?php echo implode( ' ', array_filter( array( $year, $make, $model, $drivetrain, $trim ) ) ); ?></h2>
					<div class="detail-info-row">
						<?php if ( get_field( 'comment_footer', 'options' ) && ! get_field( 'hide_disclosure_vdp', 'option' ) ) : ?>
							<button class="btn-disclosure" data-toggle="modal" data-target="#detailModal">
								<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
									<path
										d="M480-280q17 0 28.5-11.5T520-320v-160q0-17-11.5-28.5T480-520q-17 0-28.5 11.5T440-480v160q0 17 11.5 28.5T480-280Zm0-320q17 0 28.5-11.5T520-640q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640q0 17 11.5 28.5T480-600Zm0 520q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z" />
								</svg>
								<?php esc_html_e( 'Disclosure', 'shopperexpress' ); ?></button>
							<?php
						endif;
						if ( $relevent_vehicles = search_relevent_vehicles( $post_id ) ) :
							?>
							<span class="remaining-price"><a href="<?php echo esc_url( $relevent_vehicles['url'] ); ?>"><?php echo $relevent_vehicles['count'] . ' ' . get_field( 'text_similar_vehicles', 'options' ); ?></a></span>
						<?php endif; ?>
					</div>
					<?php if ( $message = get_field( 'message' ) ) : ?>
						<div class="lead">
							<?php echo $message; ?>
						</div>
					<?php endif; ?>
					<div class="info-block info-block--top">
						<ul class="payment-info">
							<?php
							get_template_part(
								'template-parts/components/payment_list',
								null,
								array(
									'post_id'   => $post_id,
									'post_type' => $post_type,
								)
							);
							get_template_part(
								'template-parts/components/payment_list_new',
								null,
								array(
									'post_id'   => $post_id,
									'post_type' => $post_type,
									'is_single' => true,
								)
							);
							?>
						</ul>
						<?php
						get_template_part(
							'template-parts/components/view-count-badge',
							null,
							array(
								'vin'       => $vin_number,
								'post_type' => $post_type,
							)
						);
						if ( 'listings' === $post_type ) {
							get_template_part(
								'template-parts/conditional',
								'offers',
								array(
									'make'       => $make,
									'vin_number' => $vin_number,
								)
							);
						}

						get_template_part(
							'template-parts/description',
							'block',
							array(
								'post_type' => $post_type,
								'type'      => 'vdp',
							)
						);
						get_template_part( 'template-parts/unlock', 'button' );
						?>
					</div>
					<div class="info-block">
						<div class="heading">
							<h3><?php esc_html_e( 'Shopping Tools', 'shopperexpress' ); ?></h3>
						</div>
						<?php
						$vin_number      = ! empty( $vin_number ) ? $vin_number : 0;
						$ConversionBlock = new ConversionBlock( $vin_number, $post_type );
						echo $ConversionBlock->render();

						while ( have_rows( 'auto_check', 'options' ) ) :
							the_row();
							$image = get_sub_field( 'image' );
							if ( $post_type == 'used-listings' && $image && get_sub_field( 'show' ) ) :
								?>
								<a href="<?php echo add_query_arg( array( 'action' => 'auto_check' ), get_permalink() ); ?>" target="_blank"><?php echo get_attachment_image( $image['id'] ); ?></a>
								<?php
							endif;
						endwhile;

						if ( $for_html_right = get_field( 'for_html_right', 'options' ) ) :
							?>
							<div class="info-html">
								<?php echo str_replace( '{VIN}', $vin_number, $for_html_right ); ?>
							</div>
						<?php endif; ?>
					</div>
					<?php
					while ( have_rows( 'flexible_content', 'options' ) ) :
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
										$down_payment = ( '' !== trim( (string) $down_payment ) ) ? $down_payment : null;

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
														/></svg>' . __( 'UNLOCK PAYMENT', 'shopperexpress' ) . '</span>';
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
					<div class="info-block sticky-summary">
						<div class="summary-block text-center">
							<div class="summary-holder">
								<h3><?php echo $year; ?> <?php echo $make; ?> <?php echo $model; ?> <?php echo $trim; ?></h3>
								<ul class="summary-list">
									<?php
									get_template_part(
										'template-parts/components/payment_list',
										null,
										array(
											'post_id'   => $post_id,
											'post_type' => $post_type,
											'single-bottom' => 'true',
										)
									);
									?>
								</ul>
								<?php get_template_part( 'template-parts/unlock', 'button', array( 'show-image' => 'false' ) ); ?>

							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

	</div>
	<?php
	$vdp_description    = get_field( 'vdp_description', 'options' );
	$ai_vdp_description = get_field( 'ai_vdp_description' );

	if ( have_rows( 'vdp_description' ) || $ai_vdp_description ) :
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
						if ( $ai_vdp_description ) :
							?>
							<li><?php echo wp_kses_post( $ai_vdp_description ); ?></li>
						<?php endif; ?>
					</ul>
				</div>
			</div>
		</div>
		<?php
	endif;

	get_template_part( 'template-parts/accordion', 'detail-info', array( 'vin_number' => $vin_number ) );

	get_template_part(
		'template-parts/accordion',
		null,
		array(
			'post_type' => $post_type,
			'type'      => 'random',
		)
	);

	$comment_footer = ( $post_type == 'used-listings' ) ? get_field( 'used_listings_comment_footer', 'options' ) : get_field( 'comment_footer', 'options' );
	if ( $comment_footer ) :
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

endwhile;
get_footer();

$title = array( $year, $make, $model, $trim );
get_template_part(
	'template-parts/copyLinkModal',
	null,
	array(
		'post_id'      => $post_id,
		'image'        => $firstImage,
		'title'        => implode( ' ', $title ),
		'vin'          => $vin_number,
		'stock_number' => get_field( 'stock_number' ),
	)
);
get_template_part( 'template-parts/detail', 'modal' );

if ( is_user_logged_in() ) {
	get_template_part( 'template-parts/modal', 'edit', array( 'title' => implode( ' ', $title ) ) );
}

?>

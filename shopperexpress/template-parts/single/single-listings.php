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
								<li><a href="<?php echo $archive_link . '?year' . '=' . $year; ?>"><?php echo $year; ?></a></li>
								<li><a href="<?php echo $archive_link . '?condition' . '=' . $condition_slug; ?>"><?php echo $condition; ?></a></li>
								<li><a href="<?php echo $archive_link . '?make' . '=' . $make_slug; ?>"><?php echo strlen( $make ) >= 10 ? mb_strimwidth( $make, 0, 10, '...' ) : $make; ?></a></li>
								<li><a href="<?php echo $archive_link . '?model' . '=' . $model_slug; ?>"><?php echo strlen( $model ) >= 15 ? mb_strimwidth( $model, 0, 15, '...' ) : $model; ?></a></li>
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
								?>
								<div class="badges-list">
									<span class="card-badge-status"><?php echo $status; ?></span>
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
													echo '<a href="' . $value['image_url'] . '" ' . $image_reverse . ' data-fancybox="img-gallery"><img src="' . $value['image_url'] . '" alt="image"></a>';
												else :
													$get_default_image = get_default_image( $post_type );

													echo '<a href="' . esc_url( $get_default_image ) . '" ' . $image_reverse . ' data-fancybox="img-gallery">'
														. '<img src="' . esc_url( $get_default_image ) . '" alt="image">'
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
									$API_KEY            = get_field( 'spin_data_api_key', 'option' );
									$cid                = get_field( 'spin_data_id', 'option' );
									$stringToHash       = $API_KEY . $cid . $vin_number;
									$auth               = hash( 'sha512', $stringToHash );
									$modal              = $spin_data_provider === 'evo' ? 'evo-slider-modal' : '';

									$classes = array(
										'btn-spin',
										$spin_data_provider === 'FlickFusion'
										? 'spin-video'
										: 'spin-' . $spin_data_provider,
									);

									$attrs = array();

									if ( $spin_data_provider === 'FlickFusion' ) {
										$attrs['data-url'] = get_vehicle_spin( $vin_number );
									}

									if ( $API_KEY && $cid && $vin_number ) {
										$attrs['data-auth']     = $auth;
										$attrs['data-clientid'] = $cid;
									}
									?>
										<a
											href="#<?php echo esc_attr( $modal ); ?>"
											data-type="iframe"
											data-fancybox
											class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
											<?php foreach ( $attrs as $key => $value ) : ?>
												<?php echo esc_attr( $key ); ?>="<?php echo esc_attr( $value ); ?>"
											<?php endforeach; ?>
										>
										<svg class="svg-360" x="0px" y="0px" viewBox="0 0 116.2 31.5" xml:space="preserve">
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
										<svg class="svg-video" x="0px" y="0px" viewBox="0 0 115.9 31.5">
											<g>
												<path d="M84.4,12.8c-0.4-0.2-0.8-0.3-1.3-0.3h-1.5v4.6h1.5c0.5,0,0.9,0,1.3-0.3c0.4-0.2,0.6-0.5,0.8-0.8c0.2-0.3,0.3-0.8,0.3-1.2
																								c0-0.4,0-0.9-0.3-1.2C84.9,13.3,84.7,13,84.4,12.8z" />
												<path d="M44.8,15.3c-0.3,0-0.4,0-0.6,0.1c-0.2,0-0.3,0.2-0.4,0.4s-0.1,0.3-0.1,0.6c0,0.3,0,0.4,0.1,0.5c0,0.2,0.2,0.3,0.4,0.4
																								c0.2,0,0.4,0.1,0.6,0.1v0.1c0.2,0,0.4,0,0.6-0.1c0.2,0,0.3-0.2,0.4-0.4c0-0.2,0.1-0.3,0.1-0.6c0-0.3-0.1-0.6-0.3-0.8
																								C45.4,15.4,45.1,15.3,44.8,15.3z" />
												<path d="M53.2,12.6c-0.2-0.2-0.4-0.2-0.7-0.2s-0.5,0-0.7,0.2c-0.2,0.2-0.4,0.4-0.5,0.8c-0.1,0.4-0.2,0.8-0.2,1.4
																								c0,0.6,0,1.1,0.2,1.4c0.1,0.4,0.3,0.6,0.5,0.8s0.4,0.2,0.7,0.2s0.5,0,0.7-0.2c0.2-0.2,0.4-0.4,0.5-0.8c0.1-0.4,0.2-0.8,0.2-1.4
																								c0-0.6,0-1.1-0.2-1.4C53.6,13,53.4,12.8,53.2,12.6z" />
												<path d="M100.2,0H15.7C7,0,0,7.1,0,15.8s7,15.7,15.7,15.7h84.4c8.7,0,15.8-7.1,15.8-15.8S108.9,0,100.2,0z M26.5,17
																								c-1.1,0.8-2.6,1.4-4.3,1.8v-2.1c1.3-0.3,2.3-0.7,3-1.2c0.7-0.5,1-0.9,1-1.3c0-0.4-0.7-1.2-2.1-1.9s-3.4-1.1-5.9-1.1
																								s-4.4,0.4-5.9,1.1c-1.4,0.7-2.1,1.4-2.1,1.9s0.4,0.9,1.3,1.4c0.8,0.6,2.1,1,3.6,1.3l-1.3-1.3l1.4-1.4l4,4l-4,4l-1.4-1.4l1.8-1.8
																								c-2.1-0.3-3.9-0.9-5.3-1.8c-1.4-0.9-2.1-1.9-2.1-3c0-1.1,1-2.6,2.9-3.5c1.9-0.9,4.3-1.5,7.1-1.5c2.8,0,5.2,0.5,7.1,1.5
																								s2.9,2.2,2.9,3.5S27.6,16.2,26.5,17z M39.8,17.9c-0.3,0.4-0.7,0.8-1.2,1c-0.5,0.3-1.2,0.4-2.1,0.4l0.1-0.1c-0.6,0-1.2,0-1.8-0.2
																								s-1.1-0.3-1.5-0.6l0.9-1.8c0.3,0.2,0.7,0.4,1.1,0.5c0.4,0.1,0.8,0.2,1.2,0.2c0.4,0,0.7,0,1-0.2c0.2-0.2,0.4-0.4,0.4-0.6
																								s-0.1-0.5-0.3-0.6c-0.2-0.1-0.5-0.2-1-0.2h-1v-1.5l1.5-1.6h-3.3v-1.8h6.1v1.5l-1.7,1.8c0.5,0.1,0.9,0.3,1.2,0.6
																								c0.5,0.5,0.8,1.1,0.8,1.8S40.1,17.4,39.8,17.9z M47.6,18c-0.3,0.4-0.7,0.8-1.2,1s-1.1,0.3-1.7,0.3l0.2-0.1c-0.8,0-1.5-0.2-2.1-0.5
																								s-1-0.8-1.4-1.4c-0.3-0.6-0.5-1.4-0.5-2.3s0.2-1.8,0.6-2.5c0.4-0.7,0.9-1.2,1.6-1.6c0.7-0.4,1.4-0.5,2.3-0.5c0.9,0,0.9,0,1.4,0.2
																								c0.5,0.2,0.8,0.3,1.1,0.5L47,12.8c-0.2-0.2-0.5-0.3-0.8-0.3c-0.3,0-0.5,0-0.8,0c-0.6,0-1.2,0.2-1.6,0.6c-0.3,0.3-0.5,0.8-0.6,1.3
																								c0.2-0.2,0.5-0.3,0.8-0.4c0.3-0.1,0.7-0.1,1.1-0.1s1,0.1,1.4,0.3c0.4,0.2,0.8,0.5,1.1,0.9c0.3,0.4,0.4,0.9,0.4,1.4
																								S47.9,17.6,47.6,18z M55.7,17.2c-0.3,0.7-0.8,1.2-1.3,1.5c-0.6,0.3-1.2,0.5-1.9,0.5s-1.4-0.2-1.9-0.5c-0.6-0.3-1-0.8-1.3-1.5
																								s-0.5-1.4-0.5-2.4s0.2-1.7,0.5-2.4c0.3-0.7,0.8-1.2,1.3-1.5c0.6-0.3,1.2-0.5,1.9-0.5s1.3,0.2,1.9,0.5c0.6,0.3,1,0.8,1.3,1.5
																								c0.3,0.7,0.5,1.4,0.5,2.4S56,16.5,55.7,17.2z M61.1,13.6c-0.2,0.3-0.5,0.6-0.8,0.8c-0.3,0.2-0.7,0.3-1.1,0.3c-0.4,0-0.8,0-1.1-0.3
																								c-0.3-0.2-0.6-0.4-0.8-0.8c-0.2-0.3-0.3-0.7-0.3-1.1s0-0.7,0.3-1.1c0.2-0.3,0.5-0.6,0.8-0.8c0.3-0.2,0.7-0.3,1.1-0.3
																								c0.4,0,0.8,0,1.1,0.3c0.3,0.2,0.6,0.4,0.8,0.8c0.2,0.3,0.3,0.7,0.3,1.1S61.4,13.2,61.1,13.6z M71,19h-2.3h-0.1L65,10.6h2.6l2.3,5.5
																								l2.3-5.5h2.4L71,19z M77.6,19h-2.4v-8.4h2.4V19z M87.4,17c-0.4,0.6-0.9,1.1-1.7,1.5C85,18.8,84.2,19,83.3,19h-4v-8.4h4
																								c0.9,0,1.7,0.2,2.4,0.5c0.7,0.3,1.3,0.8,1.7,1.5c0.4,0.6,0.6,1.4,0.6,2.2S87.8,16.4,87.4,17z M95.9,19h-6.7v-8.4h6.6v1.8h-4.2v1.5
																								h3.6v1.8h-3.6v1.4l-0.1,0.1h4.4V19z M106,16.7c-0.2,0.5-0.6,1-1,1.4c-0.4,0.4-0.9,0.7-1.5,0.9c-0.6,0.2-1.2,0.3-1.9,0.3l-0.1-0.1
																								c-0.7,0-1.3-0.1-1.9-0.3c-0.6-0.2-1.1-0.5-1.5-0.9s-0.7-0.9-1-1.4c-0.2-0.5-0.4-1.1-0.4-1.7c0-0.6,0.1-1.2,0.4-1.7
																								c0.2-0.5,0.6-1,1-1.4c0.4-0.4,0.9-0.7,1.5-0.9c0.6-0.2,1.2-0.3,1.9-0.3s1.3,0.1,1.9,0.3c0.6,0.2,1.1,0.5,1.5,0.9
																								c0.4,0.4,0.7,0.9,1,1.4c0.2,0.5,0.4,1.1,0.4,1.7C106.3,15.5,106.2,16.1,106,16.7z" />
												<path d="M59.7,11.7c-0.2,0-0.3-0.1-0.5-0.1c-0.2,0-0.4,0-0.5,0.1c-0.1,0-0.3,0.2-0.4,0.4c-0.1,0.2-0.1,0.3-0.1,0.5
																								c0,0.2,0,0.4,0.1,0.5c0,0.2,0.2,0.3,0.4,0.4c0.1,0,0.3,0.1,0.5,0.1c0.2,0,0.4,0,0.5-0.1c0.2,0,0.3-0.2,0.4-0.4
																								c0-0.2,0.1-0.3,0.1-0.5c0-0.2,0-0.4-0.1-0.5C60.1,11.9,59.9,11.8,59.7,11.7z" />
												<path d="M103.1,12.9c-0.2-0.2-0.4-0.4-0.7-0.5c-0.3-0.1-0.6-0.2-0.9-0.2c-0.3,0-0.6,0-0.9,0.2c-0.3,0.1-0.5,0.3-0.7,0.5
																								c-0.2,0.2-0.4,0.5-0.5,0.8c-0.1,0.3-0.2,0.6-0.2,1c0,0.4,0,0.7,0.2,1c0.1,0.3,0.3,0.5,0.5,0.8c0.2,0.2,0.5,0.4,0.7,0.5
																								c0.3,0.1,0.6,0.2,0.9,0.2c0.3,0,0.6,0,0.9-0.2c0.3-0.1,0.5-0.3,0.7-0.5s0.4-0.5,0.5-0.8s0.2-0.6,0.2-1c0-0.4,0-0.7-0.2-1
																								S103.3,13.2,103.1,12.9z" />
											</g>
										</svg>
									</a>
								</div>
								<?php if ( $gallery ) : ?>
									<div class="detail-slider-nav">
										<div class="slider-nav-holder">
											<?php
											foreach ( $gallery as $value ) :
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
														<img src="<?php echo $image_url; ?>" srcset="<?php echo $image_url; ?> 2x" alt="image">
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
														<img src="<?php echo $get_default_image; ?>" srcset="<?php echo $get_default_image; ?> 2x" alt="image">
													</div>
													<?php
												endif;
											endforeach;
											?>
										</div>
									</div>
									<div class="range-box">
										<input aria-label="Carousel thumbnails slider" value="0" min="0" max="100" step="1" type="range">
									</div>
								<?php endif; ?>
							<?php else : ?>
								<div class="detail-slider">
									<?php
									if ( function_exists( 'default_image' ) ) {
										echo default_image( 'slide' );}
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
											$badges_html .= '<li><a href="' . esc_url( $url ) . '" data-pdf>' . get_attachment_image( $image ) . '</a></li>';
										else :
											$badges_html .= '<li><a href="' . esc_url( $url ) . '" target="_blank">' . get_attachment_image( $image ) . '</a></li>';
										endif;
									endif;
								}
							endwhile;
						endif;

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
						<?php if ( get_field( 'comment_footer', 'options' ) ) : ?>
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
							'template-parts/conditional',
							'offers',
							array(
								'make'       => $make,
								'vin_number' => $vin_number,
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

<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package Shopperexpress
 */

$post_type = get_post_type();

get_header();

while ( have_posts() ) :
	the_post();
	$post_id                 = get_the_id();
	$year                    = wps_get_term( $post_id, 'year', '', 'field' );
	$make                    = wps_get_term( $post_id, 'make', '', 'field' );
	$model                   = wps_get_term( $post_id, 'model', '', 'field' );
	$title                   = wps_get_term( $post_id, 'title', '', 'field' );
	$condition               = get_field( 'condition', $post_id );
	$disclaimer              = get_field( 'disclaimer' );
	$bulletPoints            = get_field( 'bulletpoints' );
	$location                = get_field( 'location', $post_id );
	$price                   = get_field( 'price', $post_id );
	$vin                     = get_field( 'vin', $post_id );
	$search_inventory_button = get_field( 'search_inventory_button' );
	$custom_content          = '';
	$vin_number              = '';

	$data_id = get_posts(
		array(
			'post_type'      => 'append-data',
			'meta_query'     => array(
				array(
					'key'     => 'year',
					'value'   => $year,
					'compare' => '=',
				),
				array(
					'key'     => 'make',
					'value'   => $make,
					'compare' => '=',
				),
				array(
					'key'     => 'model',
					'value'   => $model,
					'compare' => '=',
				),
			),
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	$data_id = ! empty( $data_id ) ? $data_id[0] : '';

	if ( $data_id ) {
		$gallery = array();

		while ( have_rows( 'imageurllist', $data_id ) ) :
			the_row();
			if ( $url = get_sub_field( 'url' ) ) {
				$gallery[] = $url;
			}
		endwhile;

		$button_label = get_field( 'buttontext', $data_id );
		$button_url   = get_field( 'landingpageurl', $data_id );
		$year         = get_field( 'shownas_year', $data_id );
		$make         = get_field( 'shownas_make', $data_id );
		$model        = get_field( 'shownas_model', $data_id );
		$trim         = get_field( 'shownas_trim', $data_id );
		$shownas      = get_field( 'shownas', $data_id );

		$text = '';

		if ( ! empty( $year ) ) {
			$text .= $year . ' ';
		}
		if ( ! empty( $make ) ) {
			$text .= $make . ' ';
		}
		if ( ! empty( $model ) ) {
			$text .= $model . ' ';
		}
		if ( ! empty( $trim ) ) {
			$text .= $trim;
		}
		$text = trim( $text );
	} else {
		$gallery = $button_label = $button_url = $text = '';
	}

	switch ( get_post_type( $post_id ) ) {
		case 'lease-offers':
			$payment     = wps_get_term( $post_id, 'payment', '', 'field' );
			$description = wps_get_term( $post_id, 'conditional_description', '', 'field' );
			$term        = wps_get_term( $post_id, 'term', '', 'field' );

			if ( ! empty( $payment ) && ! empty( $term ) ) {
				$text_1 = sprintf(
					esc_html__( '$%1$s/mo for %2$d mos.', 'shopperexpress' ),
					number_format_i18n( $payment, 2 ),
					absint( $term )
				);
			}

			break;

		case 'finance-offers':
			$apr         = wps_get_term( $post_id, 'apr', '', 'field' );
			$description = wps_get_term( $post_id, 'apr_description', '', 'field' );
			$term        = wps_get_term( $post_id, 'term', '', 'field' );

			if ( ! empty( $apr ) && ! empty( $term ) ) {
				$text_1 = sprintf(
					esc_html__( '%1$s%% APR for %2$d mos.', 'shopperexpress' ),
					number_format_i18n( (float) $apr, 2 ),
					absint( $term )
				);
			}

			break;

		case 'conditional-offers':
			$description      = wps_get_term( $post_id, 'conditional_description', '', 'field' );
			$conditional_cash = wps_get_term( $post_id, 'conditional_cash', '', 'field' );

			if ( ! empty( $conditional_cash ) ) {
				$text_1 = sprintf(
					esc_html__( '$%1$s %2$s', 'shopperexpress' ),
					esc_html( $conditional_cash ),
					esc_html__( 'Special Offer', 'shopperexpress' )
				);
			}

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
								<li><a href="<?php echo esc_url( get_post_type_archive_link( $post_type ) ); ?>"><?php esc_attr_e( 'All Offers', 'shopperexpress' ); ?></a></li>
							</ol>
							<?php if ( $year || $make || $model ) : ?>
								<ul class="code-list text-right text-capitalize list-unstyled">
									<?php if ( $year || $make ) : ?>
										<li>
											<?php
											printf(
												esc_html__( 'Year & Make: %1$s %2$s', 'shopperexpress' ),
												esc_html( $year ),
												esc_html( $make )
											);
											?>
										</li>
										<?php
									endif;
									if ( $model ) :
										?>
										<li>
											<?php
											printf(
												esc_html__( 'Model: %s', 'shopperexpress' ),
												esc_html( $model )
											);
											?>
										</li>
									<?php endif; ?>
								</ul>
								<?php
							endif;
							?>
						</div>
						<?php if ( $title ) : ?>
							<div class="badges-wrapp">
								<div class="badges-list">
									<span class="card-badge-offer"><?php echo $title; ?></span>
								</div>
							</div>
							<?php
						endif;

						$gallery = ! empty( $gallery ) ? $gallery : array();

						if ( empty( $gallery ) ) {
							if ( have_rows( 'gallery' ) ) {
								while ( have_rows( 'gallery' ) ) {
									the_row();
									if ( $image_url = get_sub_field( 'image_url' ) ) {
										$gallery[] = get_sub_field( 'image_url' );
									}
								}
							} else {
								$default_image_id = absint( get_field( $post_type . '_default_image', 'option' ) );
								$def_img          = $default_image_id !== 0
									? wp_get_attachment_image_url( $default_image_id, 'full' )
									: esc_url( App\asset_url( 'images/image-placeholder.png' ) );
								$gallery[]        = $def_img;
							}
						}
						if ( ! empty( $gallery ) ) :

							$slider         = get_field( $post_type . '_slider', 'options' );
							$autoplay       = ! empty( $slider['autoplay'] ) ? 'true' : 'false';
							$autoplay_speed = ! empty( $slider['autoplay_speed'] ) ? $slider['autoplay_speed'] * 60 * 60 : 3000;
							?>
							<div class="detail-slider-holder">
								<div class="detail-slider-wrapper">
									<div class="detail-slider" data-autoplay="<?php echo $autoplay; ?>" data-autoplay-speed="<?php echo $autoplay_speed; ?>">
										<?php
										foreach ( $gallery as $index => $image ) :
											if ( $image ) :
												if ( $index == 1 ) {
													$firstImage = esc_url( $image );
												}
												?>
												<div class="slide bg-cover" style="background-image: url(<?php echo get_field( 'background_image', 'option' ); ?>)">
													<a href="<?php echo esc_url( $image ); ?>" data-fancybox="img-gallery"><img src="<?php echo esc_url( $image ); ?>" alt="<?php esc_attr_e( 'image', 'shopperexpress' ); ?>"></a>
												</div>
												<?php
											endif;
										endforeach;
										?>
									</div>
									<?php
									$evoxnumber         = get_field( 'evoxnumber', $data_id );
									$evox_productid     = get_field( 'evox_productid', 'options' );
									$evox_producttypeid = get_field( 'evox_producttypeid', 'options' );
									if ( $evoxnumber && $evox_productid && $evox_producttypeid ) :
										$evox   = new Evox( get_field( 'evoxnumber', $data_id ), $evox_productid, $evox_producttypeid );
										$images = $evox->get_images();
										if ( $images ) :
											?>
											<?php $spin_data_provider = get_field( 'spin_data_provider', 'options' ); ?>
											<a href="#" data-type="iframe" data-url="<?php echo $images[0]; ?>" class="btn-spin spin-evox" data-fancybox style="display: block;">
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
											<?php
										endif;
									endif;
									?>
								</div>
								<div class="detail-slider-nav">
									<div class="slider-nav-holder">
										<?php
										foreach ( $gallery as $image ) :
											if ( $image ) :
												?>
												<div class="slide bg-cover">
													<img src="<?php echo esc_url( $image ); ?>" srcset="<?php echo esc_url( $image ); ?> 2x" alt="<?php esc_attr_e( 'image', 'shopperexpress' ); ?>">
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
							</div>

							<?php
						endif;

						get_template_part(
							'template-parts/detail',
							'info',
							array(
								'post_type' => $post_type,
								'post_id'   => $data_id,
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
								'location'   => $location,
								'vin_number' => $vin_number,
							)
						);
						get_template_part( 'template-parts/components/anchor', 'copy', array( 'favorite' => true ) );
						?>
					</div>
					<?php
					$pageTitle = array( $year, $make, $model );
					$pageTitle = implode( ' ', $pageTitle );
					if ( $pageTitle ) :
						?>
						<h2><?php echo $pageTitle; ?></h2>
						<?php
					endif;
					$counter_for_button = 0;
					while ( have_rows( $post_type . '_flexible_content', 'options' ) ) :
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

								$title = str_replace( '[payment]', $text_1, get_sub_field( 'title' ) );

								if ( $title ) :
									?>
									<h3><?php echo $title; ?></h3>
								<?php endif; ?>
							</div>
							<?php
							$description   = str_replace( '[shown]', $text, $description );
							$bulletPoints  = str_replace( '[shown]', $text, $bulletPoints );
							$disclaimer    = str_replace( '[shown]', $text, $disclaimer );
							$shownas_year  = str_replace( '[shownas_year]', $text, $year );
							$shownas_make  = str_replace( '[shownas_make]', $text, $make );
							$shownas_model = str_replace( '[shownas_model]', $text, $model );
							$shownas_trim  = str_replace( '[shownas_trim]', $text, $trim );
							$shownas       = str_replace( '[shownas]', $text, $shownas );

							echo str_replace(
								array( '[description]', '[bullet-points]', '[disclaimer]', '[shownas_year]', '[shownas_make]', '[shownas_model]', '[shownas_trim]', '[shownas]' ),
								array( $description, $bulletPoints, $disclaimer, $year, $make, $model, $trim, $shownas ),
								get_sub_field( 'description' )
							);

							$button_url   = ! empty( $button_url ) ? $button_url : $search_inventory_button;
							$button_label = ! empty( $button_label ) ? $button_label : __( 'Search Inventory', 'shopperexpress' );

							if ( $button_url && $counter_for_button == 0 ) {
								echo '<a href="' . esc_url( $button_url ) . '" class="btn btn-primary btn-custom btn-block">' . esc_html( $button_label ) . '</a>';
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
										$event        = get_event_script( get_sub_field( 'event' ), $location, $vin_number );
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
												if ( $down_payment && $lease_payment ) {
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
																										/></svg
																										>' . __( 'UNLOCK PAYMENT', 'shopperexpress' ) . '</span>';
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
							<?php
							if ( get_row_index() == 1 ) {
								$ConversionBlock = new ConversionBlock( 0, get_post_type() );
								echo $ConversionBlock->render();
							}
							?>
						</div>
					<?php endwhile; ?>
					<div class="info-block">
						<div class="summary-block text-center">
							<?php
							$link       = get_field( $post_type . '_link', 'options' );
							$popup_form = get_field( $post_type . '_popup_form', 'options' );
							if ( $link && ! $popup_form ) {
								echo wps_get_link( $link, 'btn btn-outline-primary', null );
							} elseif ( $popup_form && $link ) {
								echo '<a class="btn btn-outline-primary" href="#" data-toggle="modal" data-target="#contactModal">' . $link['title'] . '</a>';
							}
							if ( $phone = get_field( $post_type . '_phone', 'options' ) ) {
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
	if ( $vin_number = get_field( 'vin', $data_id ) ) {
		get_template_part( 'template-parts/accordion', 'detail-info', array( 'vin_number' => $vin_number ) );
	}

	get_template_part(
		'template-parts/accordion',
		null,
		array(
			'post_type' => $post_type,
			'type'      => 'random',
		)
	);

	if ( $comment_footer  = get_field( $post_type . '_comment_footer', 'options' ) ) :
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
			'section_bg' => get_field( 'section_bg_' . $post_type, 'options' ),
			'slide_bg'   => get_field( 'slide_bg_' . $post_type, 'options' ),
		)
	);

endwhile;
get_footer();

get_template_part(
	'template-parts/copyLinkModal',
	null,
	array(
		'image' => $firstImage,
		'title' => $pageTitle,
	)
);
?>

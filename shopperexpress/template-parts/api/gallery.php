<?php
/**
 * Image gallery slider for API mode.
 *
 * Accepts $args:
 *   vehicle   (array)  — Intice API vehicle object
 *   is_single (bool)   — true = VDP full gallery + nav (default), false = SRP limited slides
 *   post_type (string) — 'listings' or 'used-listings'; used to read images_count option in SRP mode
 *
 * @package Shopperexpress
 */

$vehicle   = $args['vehicle'] ?? array();
$is_single = isset( $args['is_single'] ) ? (bool) $args['is_single'] : true;
$post_type = $args['post_type'] ?? 'listings';

$year           = $vehicle['year'] ?? '';
$make           = $vehicle['make'] ?? '';
$model          = $vehicle['model'] ?? '';
$trim           = $vehicle['trim'] ?? '';
$exterior_color = $vehicle['exterior_color'] ?? '';

// Resolved directly from payload.use_images_list (images_primary/images_srp),
// not Nexus's own active_image_list — see \App\resolve_vehicle_gallery().
// Each item is {url, is_background, is_reverse}.
$images = \App\resolve_vehicle_gallery( $vehicle );

if ( ! $is_single ) {
	$images_count = 'used-listings' === $post_type
		? get_field( 'images_count_used', 'options' )
		: get_field( 'images_count', 'options' );
	$images_count = ! empty( $images_count ) ? absint( $images_count ) : 1;
	$images       = array_slice( $images, 0, $images_count );
}

$alt_array = array( $year, $make, $model, $trim, $exterior_color, '- ' . get_bloginfo( 'name' ) . ' - Image' );
?>
<div class="detail-slider-holder">
	<?php if ( ! empty( $images ) && $is_single ) : ?>
		<div class="detail-slider-wrapper">
			<?php
			$slider_opts    = get_field( 'slider-single_slider', 'options' );
			$autoplay       = ! empty( $slider_opts['autoplay'] ) ? 'true' : 'false';
			$autoplay_speed = ! empty( $slider_opts['autoplay_speed'] ) ? $slider_opts['autoplay_speed'] * 60 * 60 : 3000;
			?>
			<div class="detail-slider"
				data-autoplay="<?php echo esc_attr( $autoplay ); ?>"
				data-autoplay-speed="<?php echo esc_attr( (string) $autoplay_speed ); ?>">
				<?php
				foreach ( $images as $i => $img ) :
					$img_url    = is_array( $img ) ? ( $img['url'] ?? '' ) : $img;
					$is_bg      = ! empty( $img['is_background'] );
					$is_reverse = ! empty( $img['is_reverse'] );
					$alt        = implode( ' ', array_filter( array_merge( $alt_array, array( $i + 1 ) ) ) );
					?>
					<div class="slide<?php echo $is_bg ? ' bg-cover' : ''; ?>"
						<?php if ( $is_bg ) : ?>
							style="background-image: url(<?php echo esc_url( get_field( 'background_image', 'option' ) ); ?>)"
						<?php endif; ?>
					>
						<a href="<?php echo esc_url( $img_url ); ?>" data-fancybox="img-gallery"<?php echo $is_reverse ? ' class="reverse-image"' : ''; ?>>
							<img src="<?php echo esc_url( $img_url ); ?>"
								srcset="<?php echo esc_url( $img_url ); ?> 2x"
								alt="<?php echo esc_attr( $alt ); ?>"
								<?php echo 0 === $i ? 'loading="eager"' : 'loading="lazy"'; ?>
							/>
						</a>
					</div>
				<?php endforeach; ?>
			</div>
			<?php
			$vin_number         = $vehicle['vin'] ?? '';
			$spin_data_provider = get_field( 'spin_data_provider', 'options' );
			$spin_api_key       = get_field( 'spin_data_api_key', 'option' );
			$spin_cid           = get_field( 'spin_data_id', 'option' );
			$spin_auth          = hash( 'sha512', $spin_api_key . $spin_cid . $vin_number );
			$spin_modal         = 'evo' === $spin_data_provider ? 'evo-slider-modal' : '';

			$spin_classes = array(
				'btn-spin',
				'FlickFusion' === $spin_data_provider ? 'spin-video' : 'spin-' . $spin_data_provider,
			);

			$spin_attrs = array();

			if ( 'FlickFusion' === $spin_data_provider ) {
				$spin_attrs['data-url'] = get_vehicle_spin( $vin_number );
			}

			if ( 'dealerimage' === $spin_data_provider ) {
				$spin_attrs['data-dealer-id'] = $spin_cid;
			}

			if ( $spin_api_key && $spin_cid && $vin_number && 'dealerimage' !== $spin_data_provider ) {
				$spin_attrs['data-auth']     = $spin_auth;
				$spin_attrs['data-clientid'] = $spin_cid;
			}
			?>
			<a
				href="#<?php echo esc_attr( $spin_modal ); ?>"
				data-type="iframe"
				data-fancybox
				class="<?php echo esc_attr( implode( ' ', $spin_classes ) ); ?>"
				<?php foreach ( $spin_attrs as $attr_key => $attr_value ) : ?>
					<?php echo esc_attr( $attr_key ); ?>="<?php echo esc_attr( $attr_value ); ?>"
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
<div class="detail-slider-nav">
				<div class="slider-nav-holder">
					<?php
					foreach ( $images as $img ) :
						$img_url    = is_array( $img ) ? ( $img['url'] ?? '' ) : $img;
						$is_bg      = ! empty( $img['is_background'] );
						$is_reverse = ! empty( $img['is_reverse'] );
						$alt        = implode( ' ', array_filter( $alt_array ) );
						?>
						<div class="slide<?php echo $is_bg ? ' bg-cover' : ''; ?>"
							<?php if ( $is_bg ) : ?>
								style="background-image: url(<?php echo esc_url( get_field( 'background_image', 'option' ) ); ?>)"
							<?php endif; ?>
						>
							<span<?php echo $is_reverse ? ' class="reverse-image"' : ''; ?>>
								<img src="<?php echo esc_url( $img_url ); ?>"
									srcset="<?php echo esc_url( $img_url ); ?> 2x"
									alt="<?php echo esc_attr( $alt ); ?>"
									loading="lazy" />
							</span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="range-box">
				<input aria-label="<?php esc_attr_e( 'Carousel thumbnails slider', 'shopperexpress' ); ?>"
					value="0" min="0" max="100" step="1" type="range" />
			</div>
	<?php elseif ( ! empty( $images ) ) : ?>
		<div class="detail-slider">
			<?php
			foreach ( $images as $i => $img ) :
				$img_url    = is_array( $img ) ? ( $img['url'] ?? '' ) : $img;
				$is_bg      = ! empty( $img['is_background'] );
				$is_reverse = ! empty( $img['is_reverse'] );
				$alt        = implode( ' ', array_filter( array_merge( $alt_array, array( $i + 1 ) ) ) );
				?>
				<div class="slide<?php echo $is_bg ? ' bg-cover' : ''; ?>"
					<?php if ( $is_bg ) : ?>
						style="background-image: url(<?php echo esc_url( get_field( 'background_image', 'option' ) ); ?>)"
					<?php endif; ?>
				>
					<span<?php echo $is_reverse ? ' class="reverse-image"' : ''; ?>>
						<img src="<?php echo esc_url( $img_url ); ?>"
							srcset="<?php echo esc_url( $img_url ); ?> 2x"
							alt="<?php echo esc_attr( $alt ); ?>"
							class="detail-slide-img"
							<?php echo 0 === $i ? 'loading="eager"' : 'loading="lazy"'; ?>
						/>
					</span>
				</div>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<div class="detail-slider">
			<?php
			if ( function_exists( 'default_image' ) ) {
				echo default_image( 'slide', $post_type, $alt_array ); // phpcs:ignore
			}
			?>
		</div>
	<?php endif; ?>
</div>

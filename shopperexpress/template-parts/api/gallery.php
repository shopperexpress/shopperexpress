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

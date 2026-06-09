<?php
/**
 * VDP image gallery slider for API mode.
 *
 * Accepts $args:
 *   vehicle (array) — Intice API vehicle object
 *
 * @package Shopperexpress
 */

$vehicle = $args['vehicle'] ?? array();

$year           = $vehicle['year']           ?? '';
$make           = $vehicle['make']           ?? '';
$model          = $vehicle['model']          ?? '';
$trim           = $vehicle['trim']           ?? '';
$exterior_color = $vehicle['exterior_color'] ?? '';
$images         = $vehicle['images']         ?? ( ! empty( $vehicle['image'] ) ? array( $vehicle['image'] ) : array() );

$alt_array = array( $year, $make, $model, $trim, $exterior_color, '- ' . get_bloginfo( 'name' ) . ' - Image' );
?>
<div class="detail-slider-holder">
	<?php if ( ! empty( $images ) ) : ?>
		<div class="detail-slider-wrapper">
			<?php
			$slider_opts    = get_field( 'slider-single_slider', 'options' );
			$autoplay       = ! empty( $slider_opts['autoplay'] ) ? 'true' : 'false';
			$autoplay_speed = ! empty( $slider_opts['autoplay_speed'] ) ? $slider_opts['autoplay_speed'] * 60 * 60 : 3000;
			?>
			<div class="detail-slider"
				data-autoplay="<?php echo esc_attr( $autoplay ); ?>"
				data-autoplay-speed="<?php echo esc_attr( (string) $autoplay_speed ); ?>">
				<?php foreach ( $images as $i => $img_url ) :
					$alt = implode( ' ', array_filter( array_merge( $alt_array, array( $i + 1 ) ) ) );
					?>
					<div class="slide">
						<a href="<?php echo esc_url( $img_url ); ?>" data-fancybox="img-gallery">
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
					<?php foreach ( $images as $img_url ) :
						$alt = implode( ' ', array_filter( $alt_array ) );
						?>
						<div class="slide">
							<img src="<?php echo esc_url( $img_url ); ?>"
								srcset="<?php echo esc_url( $img_url ); ?> 2x"
								alt="<?php echo esc_attr( $alt ); ?>"
								loading="lazy" />
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="range-box">
				<input aria-label="<?php esc_attr_e( 'Carousel thumbnails slider', 'shopperexpress' ); ?>"
					value="0" min="0" max="100" step="1" type="range" />
			</div>
		</div>
	<?php else : ?>
		<div class="detail-slider">
			<?php
			if ( function_exists( 'default_image' ) ) {
				echo default_image( 'slide', '', $alt_array ); // phpcs:ignore
			}
			?>
		</div>
	<?php endif; ?>
</div>

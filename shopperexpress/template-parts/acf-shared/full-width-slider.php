<?php
/**
 * Shared: Full Width Slider
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type bool   $remove_paddings  Add py-0 class.
 *   @type int    $slider_speed     Transition speed in ms.
 *   @type int    $autoplay_speed   Autoplay interval in ms.
 *   @type array  $slides           Pre-processed array of slide items, each with:
 *                                  image (ACF array: id, url), image_mobile (ACF array: url),
 *                                  url (string), open_in_new_tab (bool),
 *                                  ariaLabel (string), disclosure (string).
 * }
 */

$remove_paddings = $args['remove_paddings'] ?? false;
$slider_speed    = $args['slider_speed'] ?? 500;
$autoplay_speed  = $args['autoplay_speed'] ?? 5000;
$slides          = $args['slides'] ?? array();

if ( ! empty( $slides ) ) :
	?>
	<section class="section-full-width-slider<?php echo $remove_paddings ? ' py-0' : ''; ?>">
		<div class="container">
			<div class="full-width-image-slider slick-item" data-speed="<?php echo esc_html( $slider_speed ); ?>" data-autoplay-speed="<?php echo esc_html( $autoplay_speed ); ?>">
				<?php foreach ( $slides as $slide ) : ?>
					<?php
					$image           = $slide['image'] ?? null;
					$image_mobile    = $slide['image_mobile'] ?? $image;
					$url             = $slide['url'] ?? '';
					$open_in_new_tab = ! empty( $slide['open_in_new_tab'] );
					$aria_label      = $slide['ariaLabel'] ?? '';
					$disclosure      = $slide['disclosure'] ?? '';

					ob_start();
					?>
					<picture>
						<source srcset="<?php echo esc_url( $image_mobile['url'] ); ?>" media="(max-width: 767px)" />
						<source srcset="<?php echo esc_url( $image['url'] ); ?>" />
						<?php echo wp_kses_post( get_attachment_image( $image['id'] ) ); ?>
					</picture>
					<?php
					$output = ob_get_clean();
					?>
					<div>
						<?php if ( $url ) : ?>
							<a href="<?php echo esc_url( $url ); ?>"<?php echo $open_in_new_tab ? ' target="_blank"' : ''; ?> aria-label="<?php echo esc_attr( $aria_label ); ?>">
								<?php echo $output; ?>
							</a>
						<?php else : ?>
							<?php echo $output; ?>
						<?php endif; ?>
						<?php
						if ( $disclosure ) {
							get_template_part( 'template-parts/components/btn', 'disclosure', array( 'disclosure' => $disclosure ) );
						}
						?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

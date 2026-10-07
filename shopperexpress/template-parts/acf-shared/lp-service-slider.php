<?php
/**
 * Shared: LP Service Slider
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type string $subtitle
 *   @type string $title
 *   @type array  $slides   Array of {image, title, text, button_link (ACF link array: url, title, target)}.
 *   @type string $anchor
 * }
 */

$subtitle = $args['subtitle'] ?? '';
$title    = $args['title'] ?? '';
$slides   = $args['slides'] ?? array();
$anchor   = $args['anchor'] ?? '';

$arrow_svg = '<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" width="32" height="32" viewBox="0 0 32 32" fill="none"><path d="M26.52 14.926v-0.001c0-0.001-0.002-0.002-0.002-0.003s-0.002-0.001-0.002-0.002v-0.001c-0.018-0.018-0.033-0.034-0.051-0.052l-9.334-9.333c-0.623-0.623-1.638-0.624-2.263 0.001s-0.624 1.638 0.001 2.263l6.602 6.602h-14.804c-0.884 0-1.6 0.716-1.6 1.6s0.716 1.6 1.6 1.6h14.804l-6.602 6.603c-0.625 0.625-0.625 1.637-0.001 2.262 0.313 0.313 0.723 0.469 1.132 0.469s0.819-0.156 1.131-0.469l9.334-9.332c0.018-0.018 0.033-0.034 0.051-0.052v-0.001c0-0.001 0.002-0.002 0.002-0.002s0.002-0.002 0.002-0.003v-0.001c0.258-0.282 0.414-0.657 0.414-1.068v-0.010c0-0.411-0.156-0.786-0.414-1.068z"/></svg>';

if ( $subtitle || $title || ! empty( $slides ) ) :
	?>
	<section<?php echo $anchor ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?> class="lp-section bg-gray">
		<div class="container">
			<div class="lp-section__holder">
				<?php if ( $subtitle || $title ) : ?>
					<div class="lp-section__head-row">
						<div class="lp-section__head">
							<?php if ( $subtitle ) : ?>
								<span class="lp-subtitle"><?php echo esc_html( $subtitle ); ?></span>
							<?php endif; ?>
							<?php if ( $title ) : ?>
								<h2 class="lp-title"><?php echo esc_html( $title ); ?></h2>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>
				<?php if ( ! empty( $slides ) ) : ?>
					<div class="lp-card-slider slick-slider">
						<?php
						foreach ( $slides as $slide ) :
							$slide_title = $slide['title'] ?? '';
							if ( ! $slide_title && empty( $slide['text'] ) ) {
								continue;
							}
							?>
							<div class="lp-slide">
								<div class="lp-card-service">
									<?php if ( ! empty( $slide['image'] ) ) : ?>
										<div class="lp-card-service__image">
											<img src="<?php echo esc_url( $slide['image'] ); ?>" alt="<?php echo esc_attr( $slide_title ); ?>" />
										</div>
									<?php endif; ?>
									<?php if ( $slide_title ) : ?>
										<h3 class="lp-card-service__title"><?php echo esc_html( $slide_title ); ?></h3>
									<?php endif; ?>
									<?php if ( ! empty( $slide['text'] ) ) : ?>
										<p><?php echo esc_html( $slide['text'] ); ?></p>
									<?php endif; ?>
									<?php $button_link = $slide['button_link'] ?? null; ?>
									<?php if ( ! empty( $button_link['title'] ) ) : ?>
										<a href="<?php echo esc_url( $button_link['url'] ?: '#' ); ?>" class="lp-btn lp-btn-outline"<?php echo ! empty( $button_link['target'] ) ? ' target="' . esc_attr( $button_link['target'] ) . '"' : ''; ?>>
											<?php echo esc_html( $button_link['title'] ); ?>
											<?php echo $arrow_svg; ?>
										</a>
									<?php endif; ?>
								</div>
							</div>
							<?php
						endforeach;
						?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

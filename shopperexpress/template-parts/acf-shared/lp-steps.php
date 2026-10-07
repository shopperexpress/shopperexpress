<?php
/**
 * Shared: LP Steps
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type string $subtitle
 *   @type string $title
 *   @type string $text
 *   @type array  $button_link  ACF link array (url, title, target).
 *   @type array  $steps        Array of {title, text}.
 *   @type string $anchor
 * }
 */

$subtitle    = $args['subtitle'] ?? '';
$title       = $args['title'] ?? '';
$text        = $args['text'] ?? '';
$button_link = $args['button_link'] ?? null;
$steps       = $args['steps'] ?? array();
$anchor      = $args['anchor'] ?? '';

if ( $subtitle || $title || $text || ! empty( $steps ) ) :
	?>
	<section<?php echo $anchor ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?> class="lp-section-steps lp-section lp-two-columns">
		<div class="container">
			<div class="lp-section__holder">
				<div class="content-col">
					<?php if ( $subtitle ) : ?>
						<span class="lp-subtitle"><?php echo esc_html( $subtitle ); ?></span>
					<?php endif; ?>
					<?php if ( $title ) : ?>
						<h2 class="lp-title"><?php echo esc_html( $title ); ?></h2>
					<?php endif; ?>
					<?php if ( $text ) : ?>
						<p><?php echo wp_kses_post( nl2br( $text ) ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $button_link['title'] ) ) : ?>
						<div class="btn-holder">
							<a href="<?php echo esc_url( $button_link['url'] ?: '#' ); ?>" class="lp-btn lp-btn-primary"<?php echo ! empty( $button_link['target'] ) ? ' target="' . esc_attr( $button_link['target'] ) . '"' : ''; ?>><?php echo esc_html( $button_link['title'] ); ?></a>
						</div>
					<?php endif; ?>
				</div>
				<?php if ( ! empty( $steps ) ) : ?>
					<div class="media-col">
						<div class="lp-steps-wrap">
							<span class="step-progress"><span class="progress-bar"></span></span>
							<ol class="lp-steps-list">
								<?php foreach ( $steps as $step ) : ?>
									<?php if ( ! empty( $step['title'] ) || ! empty( $step['text'] ) ) : ?>
										<li>
											<?php if ( ! empty( $step['title'] ) ) : ?>
												<h3 class="lp-steps-list__title"><?php echo esc_html( $step['title'] ); ?></h3>
											<?php endif; ?>
											<?php if ( ! empty( $step['text'] ) ) : ?>
												<p><?php echo esc_html( $step['text'] ); ?></p>
											<?php endif; ?>
										</li>
									<?php endif; ?>
								<?php endforeach; ?>
							</ol>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

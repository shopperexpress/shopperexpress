<?php
/**
 * Shared: LP Hero
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type string $subtitle
 *   @type string $title            HTML allowed (e.g. <sup>).
 *   @type string $text
 *   @type array  $buttons          Array of {link (ACF link array: url, title, target), style}.
 *   @type string $bg_image_desktop Image URL.
 *   @type string $bg_image_mobile  Image URL.
 *   @type array  $nav_links        Array of {link (ACF link array: url, title, target)}.
 *   @type string $anchor
 * }
 */

$subtitle         = $args['subtitle'] ?? '';
$title            = $args['title'] ?? '';
$text             = $args['text'] ?? '';
$buttons          = $args['buttons'] ?? array();
$bg_image_desktop = $args['bg_image_desktop'] ?? '';
$bg_image_mobile  = $args['bg_image_mobile'] ?? '';
$nav_links        = $args['nav_links'] ?? array();
$anchor           = $args['anchor'] ?? '';

$arrow_svg = '<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" width="32" height="32" viewBox="0 0 32 32" fill="#000000"><path d="M26.52 14.926v-0.001c0-0.001-0.002-0.002-0.002-0.003s-0.002-0.001-0.002-0.002v-0.001c-0.018-0.018-0.033-0.034-0.051-0.052l-9.334-9.333c-0.623-0.623-1.638-0.624-2.263 0.001s-0.624 1.638 0.001 2.263l6.602 6.602h-14.804c-0.884 0-1.6 0.716-1.6 1.6s0.716 1.6 1.6 1.6h14.804l-6.602 6.603c-0.625 0.625-0.625 1.637-0.001 2.262 0.313 0.313 0.723 0.469 1.132 0.469s0.819-0.156 1.131-0.469l9.334-9.332c0.018-0.018 0.033-0.034 0.051-0.052v-0.001c0-0.001 0.002-0.002 0.002-0.002s0.002-0.002 0.002-0.003v-0.001c0.258-0.282 0.414-0.657 0.414-1.068v-0.010c0-0.411-0.156-0.786-0.414-1.068z"></path></svg>';

if ( $subtitle || $title || $text || ! empty( $buttons ) || ! empty( $nav_links ) ) :
	?>
	<section<?php echo $anchor ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?> class="lp-section-visual">
		<div class="lp-section-visual__holder">
			<?php if ( $bg_image_mobile ) : ?>
				<div class="lp-bg-image bg-cover mobile-bg" style="background-image: url(<?php echo esc_url( $bg_image_mobile ); ?>)"></div>
			<?php endif; ?>
			<?php if ( $bg_image_desktop ) : ?>
				<div class="lp-bg-image bg-cover desktop-bg" style="background-image: url(<?php echo esc_url( $bg_image_desktop ); ?>)"></div>
			<?php endif; ?>
			<div class="container">
				<div class="lp-section-visual__content">
					<?php if ( $subtitle ) : ?>
						<span class="lp-section-visual__subtitle"><?php echo esc_html( $subtitle ); ?></span>
					<?php endif; ?>
					<?php if ( $title ) : ?>
						<h1 class="lp-section-visual__title"><?php echo wp_kses_post( $title ); ?></h1>
					<?php endif; ?>
					<?php if ( $text ) : ?>
						<p><?php echo wp_kses_post( nl2br( $text ) ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $buttons ) ) : ?>
						<div class="btn-holder">
							<?php foreach ( $buttons as $button ) : ?>
								<?php $link = $button['link'] ?? null; ?>
								<?php if ( ! empty( $link['title'] ) ) : ?>
									<a href="<?php echo esc_url( $link['url'] ?: '#' ); ?>" class="lp-btn lp-btn-<?php echo esc_attr( $button['style'] ?: 'primary' ); ?>"<?php echo ! empty( $link['target'] ) ? ' target="' . esc_attr( $link['target'] ) . '"' : ''; ?>><?php echo esc_html( $link['title'] ); ?></a>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php if ( ! empty( $nav_links ) ) : ?>
			<div class="lp-nav-holder">
				<div class="container">
					<ul class="lp-nav-list">
						<?php foreach ( $nav_links as $nav_link ) : ?>
							<?php $link = $nav_link['link'] ?? null; ?>
							<?php if ( ! empty( $link['title'] ) ) : ?>
								<li>
									<a href="<?php echo esc_url( $link['url'] ?: '#' ); ?>"<?php echo ! empty( $link['target'] ) ? ' target="' . esc_attr( $link['target'] ) . '"' : ''; ?>><?php echo esc_html( $link['title'] ); ?> <?php echo $arrow_svg; ?></a>
								</li>
							<?php endif; ?>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		<?php endif; ?>
	</section>
<?php endif; ?>

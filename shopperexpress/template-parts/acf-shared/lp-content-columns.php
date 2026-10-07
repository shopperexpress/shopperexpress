<?php
/**
 * Shared: LP Content Columns
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type string $subtitle
 *   @type string $title
 *   @type string $text        WYSIWYG HTML.
 *   @type array  $buttons     Array of {link (ACF link array: url, title, target), style}.
 *   @type string $image       Image URL.
 *   @type string $style       "light" or "dark".
 *   @type bool   $show_decor  Show decorative corner spans (dark style only).
 *   @type string $anchor
 * }
 */

$subtitle   = $args['subtitle'] ?? '';
$title      = $args['title'] ?? '';
$text       = $args['text'] ?? '';
$buttons    = $args['buttons'] ?? array();
$image      = $args['image'] ?? '';
$style      = $args['style'] ?: 'light';
$show_decor = ! empty( $args['show_decor'] ) && 'dark' === $style;
$anchor     = $args['anchor'] ?? '';

$section_class = 'lp-section lp-two-columns' . ( 'dark' === $style ? ' lp-section-support bg-dark' : '' );

if ( $subtitle || $title || $text || ! empty( $buttons ) || $image ) :
	?>
	<section<?php echo $anchor ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?> class="<?php echo esc_attr( $section_class ); ?>">
		<div class="container">
			<div class="lp-section__holder">
				<?php if ( $show_decor ) : ?>
					<span class="decor-left" aria-hidden="true"></span>
					<span class="decor-right" aria-hidden="true"></span>
				<?php endif; ?>
				<div class="content-col">
					<?php if ( $subtitle ) : ?>
						<span class="lp-subtitle"><?php echo esc_html( $subtitle ); ?></span>
					<?php endif; ?>
					<?php if ( $title ) : ?>
						<h2 class="lp-title"><?php echo esc_html( $title ); ?></h2>
					<?php endif; ?>
					<?php if ( $text ) : ?>
						<?php echo wp_kses_post( $text ); ?>
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
				<?php if ( $image ) : ?>
					<div class="media-col">
						<div class="img-holder">
							<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $title ); ?>" />
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

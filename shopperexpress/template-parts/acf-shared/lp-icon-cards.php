<?php
/**
 * Shared: LP Icon Cards
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type string $subtitle
 *   @type string $title
 *   @type string $text       Optional paragraph shown under the heading (dark/contact variant).
 *   @type array  $cards      Array of {icon_svg, title, text, link (ACF link array: url, title, target)}.
 *   @type string $style      "", "light", or "dark" — maps to card-theme--{style}. "dark" also switches the
 *                            section to the "Ready to get started?" contact treatment (lp-section-contact,
 *                            bg-dark, decorative corners), matching the mockup.
 *   @type string $background  "white" or "gray" — maps to the section's bg-gray modifier (ignored when style is "dark").
 *   @type bool   $pt_0        Adds the pt-0 modifier (removes top padding).
 *   @type string $anchor
 * }
 */

$subtitle    = $args['subtitle'] ?? '';
$title       = $args['title'] ?? '';
$text        = $args['text'] ?? '';
$cards       = $args['cards'] ?? array();
$style       = $args['style'] ?? '';
$background  = $args['background'] ?? 'white';
$pt_0        = ! empty( $args['pt_0'] );
$anchor      = $args['anchor'] ?? '';
$is_dark     = 'dark' === $style;

$section_class = 'lp-section' . ( $is_dark ? ' lp-section-contact bg-dark' : ( 'gray' === $background ? ' bg-gray' : '' ) ) . ( $pt_0 ? ' pt-0' : '' );
$card_class    = 'lp-card' . ( $style ? ' card-theme--' . $style : '' );

$link_arrow_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M13.2597656,7.4628906v-.0004883c0-.0004883-.0009766-.0009766-.0009766-.0014648,0,0-.0009766-.0004883-.0009766-.0009766v-.0004883c-.0087891-.0087891-.0166016-.0170898-.0253906-.0258789l-4.6669922-4.6665039c-.3115234-.3115234-.8188477-.3120117-1.1313477.0004883-.3120117.3120117-.3120117.8188477.0004883,1.1313477l3.3007812,3.3007812H3.3334961c-.4418945,0-.7998047.3579102-.7998047.7998047s.3579102.7998047.7998047.7998047h7.4018555l-3.3007812,3.3012695c-.3125.3125-.3125.8183594-.0004883,1.1308594.15625.15625.3613281.234375.565918.234375s.409668-.078125.5654297-.234375l4.6669922-4.6660156c.0087891-.0087891.0166016-.0170898.0253906-.0258789v-.0004883c0-.0004883.0009766-.0009766.0009766-.0009766,0-.0004883.0009766-.0009766.0009766-.0014648v-.0004883c.1289062-.1411133.2070312-.3286133.2070312-.5341797v-.0048828c0-.2055664-.078125-.3930664-.2070312-.5341797Z"></path></svg>';

if ( $subtitle || $title || ! empty( $cards ) ) :
	?>
	<section<?php echo $anchor ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?> class="<?php echo esc_attr( $section_class ); ?>">
		<div class="container">
			<div class="lp-section__holder">
				<?php if ( $is_dark ) : ?>
					<span class="decor-left" aria-hidden="true"></span>
					<span class="decor-right" aria-hidden="true"></span>
				<?php endif; ?>
				<?php if ( $subtitle || $title || $text ) : ?>
					<div class="lp-section__head">
						<?php if ( $subtitle ) : ?>
							<span class="lp-subtitle"><?php echo esc_html( $subtitle ); ?></span>
						<?php endif; ?>
						<?php if ( $title ) : ?>
							<h2 class="lp-title"><?php echo esc_html( $title ); ?></h2>
						<?php endif; ?>
						<?php if ( $text ) : ?>
							<p><?php echo wp_kses_post( nl2br( $text ) ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<?php if ( ! empty( $cards ) ) : ?>
					<div class="lp-grid">
						<?php
						foreach ( $cards as $card ) :
							$card_title = $card['title'] ?? '';
							$card_text  = $card['text'] ?? '';
							$icon_svg   = $card['icon_svg'] ?? '';
							$link       = $card['link'] ?? null;
							$link_url   = $link['url'] ?? '';

							if ( ! $card_title && ! $card_text ) {
								continue;
							}

							$tag  = $link_url ? 'a' : 'div';
							$attr = $link_url ? ' href="' . esc_url( $link_url ) . '"' . ( ! empty( $link['target'] ) ? ' target="' . esc_attr( $link['target'] ) . '"' : '' ) : '';
							?>
							<<?php echo $tag . $attr; ?> class="<?php echo esc_attr( $card_class ); ?>">
								<?php if ( $icon_svg ) : ?>
									<div class="lp-card__icon"><?php echo $icon_svg; ?></div>
								<?php endif; ?>
								<?php if ( $link_url ) : ?>
									<strong class="lp-card__link">
										<?php echo esc_html( $card_title ); ?>
										<?php echo $link_arrow_svg; ?>
									</strong>
								<?php else : ?>
									<?php if ( $card_title ) : ?>
										<h3 class="lp-card__title"><?php echo esc_html( $card_title ); ?></h3>
									<?php endif; ?>
									<?php if ( $card_text ) : ?>
										<p><?php echo esc_html( $card_text ); ?></p>
									<?php endif; ?>
								<?php endif; ?>
							</<?php echo $tag; ?>>
							<?php
						endforeach;
						?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

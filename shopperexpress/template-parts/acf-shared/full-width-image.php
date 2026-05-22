<?php
/**
 * Shared: Full Width Image Section
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type array  $image           ACF image array (id, url).
 *   @type array  $image_mobile    ACF image array (url). Falls back to $image.
 *   @type bool   $remove_paddings Add py-0 class.
 *   @type string $url             Optional link URL.
 *   @type bool   $open_in_new_tab Open link in new tab.
 * }
 */

$image           = $args['image'] ?? null;
$image_mobile    = $args['image_mobile'] ?? null;
$remove_paddings = $args['remove_paddings'] ?? false;
$url             = $args['url'] ?? '';
$open_in_new_tab = $args['open_in_new_tab'] ?? false;

if ( $image ) :
	$image_mobile = $image_mobile ?: $image;
	?>
	<div class="section-full-width-image<?php echo $remove_paddings ? ' py-0' : ''; ?>">
		<div class="container">
			<div class="img-holder">
				<?php if ( $url ) : ?>
					<a href="<?php echo esc_url( $url ); ?>"<?php echo $open_in_new_tab ? ' target="_blank"' : ''; ?>>
						<picture>
							<source srcset="<?php echo esc_url( $image_mobile['url'] ); ?>" media="(max-width: 767px)" />
							<source srcset="<?php echo esc_url( $image['url'] ); ?>" />
							<?php echo wp_kses_post( get_attachment_image( $image['id'] ) ); ?>
						</picture>
					</a>
				<?php else : ?>
					<picture>
						<source srcset="<?php echo esc_url( $image_mobile['url'] ); ?>" media="(max-width: 767px)" />
						<source srcset="<?php echo esc_url( $image['url'] ); ?>" />
						<?php echo wp_kses_post( get_attachment_image( $image['id'] ) ); ?>
					</picture>
				<?php endif; ?>
			</div>
		</div>
	</div>
<?php endif; ?>

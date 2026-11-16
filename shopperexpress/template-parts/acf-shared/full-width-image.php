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
 *   @type string $engagement      Banner engagement: none | url | call. Empty = legacy (url if set).
 *   @type string $phone           Phone number used when engagement is "call".
 *   @type string $anchor          Optional HTML id (Gutenberg block "HTML anchor") so other
 *                                 links on the page can scroll directly to this section.
 * }
 */

$image           = $args['image'] ?? null;
$image_mobile    = $args['image_mobile'] ?? null;
$remove_paddings = $args['remove_paddings'] ?? false;
$url             = $args['url'] ?? '';
$open_in_new_tab = $args['open_in_new_tab'] ?? false;
$anchor          = $args['anchor'] ?? '';
$engagement      = $args['engagement'] ?? '';
$phone           = $args['phone'] ?? '';

// Legacy banners (no engagement saved) keep working: URL if one was entered.
if ( ! $engagement ) {
	$engagement = $url ? 'url' : 'none';
}

$href   = '';
$target = '';
if ( 'call' === $engagement ) {
	$digits = preg_replace( '/[^0-9+]/', '', (string) $phone );
	if ( $digits ) {
		$href = 'tel:' . $digits;
	}
} elseif ( 'url' === $engagement && $url ) {
	$href   = $url;
	$target = $open_in_new_tab ? ' target="_blank" rel="noopener"' : '';
}

if ( $image ) :
	$image_mobile = $image_mobile ?: $image;
	?>
	<div<?php echo $anchor ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?> class="section-full-width-image<?php echo $remove_paddings ? ' py-0' : ''; ?>">
		<div class="container">
			<div class="img-holder">
				<?php if ( $href ) : ?>
					<a href="<?php echo esc_url( $href ); ?>"<?php echo $target; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
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

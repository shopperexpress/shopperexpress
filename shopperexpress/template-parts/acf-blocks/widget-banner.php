<?php
/**
 * Title: Widget banner
 * Keywords: Keyword 1, Keyword 2
 * Category: custom-acf-blocks
 *
 * @param  array $block The block settings and attributes.
 * @param  string $content The block inner HTML (empty).
 * @param  bool $is_preview True during AJAX preview.
 * @param  (int|string) $post_id The post ID this block is saved to.
 *
 * @package Shopperexpress
 */

$block_id = ! empty( $block['id'] ) ? 'block-' . $block['id'] : '';
if ( ! empty( $block['anchor'] ) ) {
	$block_id = $block['anchor'];
}

$class_names = array( 'widget-banner' );
if ( ! empty( $block['className'] ) ) {
	$class_names[] = $block['className'];
}

$image = get_field( 'image' );
$url   = get_field( 'url' );

if ( $url && $image ) :
	?>
	<div id="<?php echo esc_attr( $block_id ); ?>" class="<?php echo esc_attr( implode( ' ', $class_names ) ); ?>">
		<a href="<?php echo esc_url( $url ); ?>" target="_blank">
			<?php echo get_attachment_image( $image['id'] ); ?>
		</a>
	</div>
<?php endif; ?>

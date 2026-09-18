<?php
/**
 * Shared: Info Images
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type string $title   Optional heading.
 *   @type array  $images  Array of attachment IDs (integers).
 *   @type string $anchor  Optional HTML id (Gutenberg block "HTML anchor") so other
 *                         links on the page can scroll directly to this section.
 * }
 */

$title  = $args['title'] ?? '';
$images = $args['images'] ?? array();
$anchor = $args['anchor'] ?? '';

if ( ! empty( $images ) ) : ?>
	<section<?php echo $anchor ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?> class="section">
		<div class="container">
			<?php
			if ( $title ) {
				?>
				<h3><?php echo $title; ?></h3>
				<?php
			}
			?>
			<?php
			foreach ( $images as $img ) {
				echo wp_get_attachment_image( $img, 'full' );}
			?>
		</div>
	</section>
<?php endif; ?>

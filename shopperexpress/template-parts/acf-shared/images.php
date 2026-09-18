<?php
/**
 * Shared: Images
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type array  $images  Array of ACF image arrays (each with 'id').
 *   @type string $anchor  Optional HTML id (Gutenberg block "HTML anchor") so other
 *                         links on the page can scroll directly to this section.
 * }
 */

$images = $args['images'] ?? array();
$anchor = $args['anchor'] ?? '';

if ( ! empty( $images ) ) :
	?>
	<section<?php echo $anchor ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?> class="section">
		<div class="container">
			<?php
			foreach ( $images as $image ) :
				echo wp_get_attachment_image( $image['id'], 'full' );
			endforeach;
			?>
		</div>
	</section>
<?php endif; ?>

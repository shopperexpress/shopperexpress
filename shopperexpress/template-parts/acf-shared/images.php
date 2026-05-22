<?php
/**
 * Shared: Images
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type array $images  Array of ACF image arrays (each with 'id').
 * }
 */

$images = $args['images'] ?? array();

if ( ! empty( $images ) ) :
	?>
	<section class="section">
		<div class="container">
			<?php
			foreach ( $images as $image ) :
				echo wp_get_attachment_image( $image['id'], 'full' );
			endforeach;
			?>
		</div>
	</section>
<?php endif; ?>

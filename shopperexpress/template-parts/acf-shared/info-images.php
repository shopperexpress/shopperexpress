<?php
/**
 * Shared: Info Images
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type string $title   Optional heading.
 *   @type array  $images  Array of attachment IDs (integers).
 * }
 */

$title  = $args['title'] ?? '';
$images = $args['images'] ?? array();

if ( ! empty( $images ) ) : ?>
	<section class="section">
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

<?php
/**
 * Images
 *
 * @package ShopperExpress
 */

$images = get_sub_field( 'images' );
if ( $images ) :
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

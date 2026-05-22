<?php
/**
 * Shared: Content Block
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type int    $icon_image    Attachment ID for icon.
 *   @type string $title         Heading.
 *   @type string $text          Body HTML.
 *   @type string $link          Raw link HTML.
 *   @type array  $first_image   ACF image array (id).
 *   @type array  $second_image  ACF image array (id).
 * }
 */

$icon_image   = $args['icon_image'] ?? null;
$title        = $args['title'] ?? '';
$text         = $args['text'] ?? '';
$link         = $args['link'] ?? '';
$first_image  = $args['first_image'] ?? null;
$second_image = $args['second_image'] ?? null;
$is_preview   = $args['is_preview'] ? ' id="page-container"' : '';
?>
<div <?php echo $is_preview; ?> class="container">
	<div class="block-holder">
		<div class="block">
			<!-- content block -->
			<section class="content-block cash-offer">
				<div class="text-box">
					<div class="heading">
						<?php if ( $icon_image ) : ?>
							<div class="icon">
								<?php
								$image_id = absint( $icon_image );
								echo wp_kses_post( wp_get_attachment_image( $image_id, 'full' ) );
								?>
							</div>
							<?php
						endif;
						if ( $title ) :
							?>
							<h2><?php echo $title; ?></h2>
						<?php endif; ?>
					</div>
					<?php if ( $text ) : ?>
						<div class="holder">
							<?php echo $text; ?>
						</div>
						<?php
					endif;

					echo $link;
					?>
				</div>
				<div class="img-box">
					<?php
					if ( $first_image ) {
						echo wp_get_attachment_image( $first_image['id'], 'full' );}
					?>
					<div class="add-img center-left">
						<?php
						if ( $second_image ) {
							echo wp_get_attachment_image( $second_image['id'], 'full' );}
						?>
					</div>
				</div>
			</section>
		</div>
	</div>
</div>

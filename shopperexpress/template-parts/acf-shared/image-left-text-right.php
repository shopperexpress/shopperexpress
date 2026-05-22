<?php
/**
 * Shared: Image Left & Text Right / Text Left & Image Right
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type string $layout_class       'trade ' for image_left_and_text_right, '' for text_left_and_image_right.
 *   @type int    $icon               Attachment ID for icon.
 *   @type string $title              Heading.
 *   @type string $subtitle           Sub-heading.
 *   @type int    $image              Attachment ID for main image.
 *   @type int    $image_decor        Attachment ID for decorative image.
 *   @type string $decor_position     CSS class for decor position (or 'none').
 *   @type string $bottom_button_code Raw HTML for button.
 *   @type int    $additional_image   Attachment ID for additional image.
 *   @type bool   $centered_image     Whether to center the image box.
 *   @type string $text               Body HTML.
 * }
 */

$layout_class       = $args['layout_class'] ?? '';
$icon               = $args['icon'] ?? null;
$title              = $args['title'] ?? '';
$subtitle           = $args['subtitle'] ?? '';
$image              = $args['image'] ?? null;
$image_decor        = $args['image_decor'] ?? null;
$decor_position     = $args['decor_position'] ?? '';
$bottom_button_code = $args['bottom_button_code'] ?? '';
$additional_image   = $args['additional_image'] ?? null;
$centered_image     = ! empty( $args['centered_image'] ) ? 'text-center' : '';
$text               = $args['text'] ?? '';
?>
<section class="content-block <?php echo $layout_class; ?> <?php echo $additional_image ? 'preferred' : ''; ?>">
	<div class="text-box">
		<div class="heading">
			<?php if ( $icon ) : ?>
				<div class="icon">
					<?php
					$image_id = absint( $icon );
					echo wp_kses_post( wp_get_attachment_image( $image_id, 'full' ) );
					?>
				</div>
				<?php
			endif;
			if ( $title || $subtitle ) :
				?>
				<h2>
					<?php if ( $title ) : ?>
						<span><?php echo esc_html( $title ); ?></span>
						<?php
					endif;
					echo esc_html( $subtitle );
					?>
				</h2>
			<?php endif; ?>
		</div>
		<?php if ( $text ) : ?>
			<div class="holder">
				<?php echo $text; ?>
			</div>
			<?php
		endif;
		echo $bottom_button_code;
		?>
	</div>
	<?php if ( $image || $image_decor ) : ?>
		<div class="img-box <?php echo esc_attr( $centered_image ); ?>">
			<?php
			$img_class = ( $image_decor && $decor_position !== 'none' ) ? '' : 'rotate-image';
			if ( $image ) {
				echo wp_get_attachment_image( $image, 'full', false, array( 'class' => $img_class ) );
			}
			if ( $image_decor && $decor_position !== 'none' ) :
				?>
				<div class="add-img <?php echo esc_attr( $decor_position ); ?>">
					<?php echo wp_get_attachment_image( $image_decor, 'full' ); ?>
				</div>
				<?php
			else :
				echo wp_get_attachment_image( $image_decor, 'full', false, array( 'class' => 'descrition-image' ) );
			endif;
			?>
		</div>
		<?php
	endif;
	if ( $additional_image ) :
		?>
		<div class="img-wrapp">
			<?php echo wp_get_attachment_image( $additional_image, 'full' ); ?>
		</div>
	<?php endif; ?>
</section>

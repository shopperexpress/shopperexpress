<?php
/**
 * Shared: Intro Section / Video Section
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type string $layout_class     'intro-block' or 'preferred' (video_section).
 *   @type bool   $is_video_section Whether this is a video_section layout.
 *   @type int    $icon             Attachment ID for icon.
 *   @type string $title            Heading.
 *   @type string $subtitle         Sub-heading.
 *   @type array  $image            ACF image array (id, url, alt).
 *   @type string $bottom_button_code  Raw HTML for bottom button.
 *   @type array  $bottom_image     ACF image array.
 *   @type array  $first_image      ACF image array.
 *   @type array  $second_image     ACF image array.
 *   @type array  $thrid_image      ACF image array.
 *   @type string $text             Body HTML.
 *   @type array  $link             ACF link array (url, title, target).
 *   @type array  $blocks           Pre-rendered nested rows. Each item has keys:
 *                                  For intro_section blocks: layout = get_row_layout() per block row.
 *                                  This template re-includes nested block templates.
 *   @type string $blocks_layout    'video_section' or 'intro_section'.
 * }
 */

$is_video_section   = $args['is_video_section'] ?? false;
$class              = $is_video_section ? 'preferred' : 'intro-block';
$icon               = $args['icon'] ?? null;
$title              = $args['title'] ?? '';
$subtitle           = $args['subtitle'] ?? '';
$image              = $args['image'] ?? null;
$bottom_button_code = $args['bottom_button_code'] ?? '';
$bottom_image       = $args['bottom_image'] ?? null;
$first_image        = $args['first_image'] ?? null;
$second_image       = $args['second_image'] ?? null;
$thrid_image        = $args['thrid_image'] ?? null;
$text               = $args['text'] ?? '';
$link               = $args['link'] ?? null;
$blocks_html        = $args['blocks_html'] ?? '';
?>
<div class="block">
	<div class="container">
		<?php if ( $is_video_section ) : ?>
			<div class="block-holder products-page">
		<?php endif; ?>
		<!-- content block -->
		<?php if ( $icon || $title || $subtitle || $image || $bottom_button_code || $bottom_image ) : ?>
			<section class="content-block <?php echo $class; ?>">
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
					if ( $link ) {
						echo wps_get_link( $link, 'more' ); }

					echo $bottom_button_code;
					?>
					<?php
					if ( $bottom_image ) {
						$image_id = absint( $bottom_image['id'] );
						echo wp_kses_post( wp_get_attachment_image( $image_id, 'full', false, array( 'class' => 'item-img' ) ) );
					}
					?>
				</div>
				<?php if ( $image ) : ?>
					<div class="img-box">
						<?php
						$image_id = absint( $image['id'] );
						echo wp_kses_post( wp_get_attachment_image( $image_id, 'full' ) );
						?>
					</div>
					<?php
				endif;
				if ( $first_image || $second_image ) :
					?>
					<div class="img-box">
						<?php
						if ( $first_image ) {
							$image_id = absint( $first_image['id'] );
							echo wp_kses_post( wp_get_attachment_image( $image_id, 'full', false, array( 'class' => 'rotate-image' ) ) );
						}
						if ( $second_image ) {
							$image_id = absint( $second_image['id'] );
							echo wp_kses_post( wp_get_attachment_image( $image_id, 'full', false, array( 'class' => 'descrition-image' ) ) );
						}
						?>
					</div>
					<?php
				endif;
				if ( $thrid_image ) :
					?>
					<div class="img-wrapp">
						<?php
						$image_id = absint( $thrid_image['id'] );
						echo wp_get_attachment_image( $image_id, 'full' );
						?>
					</div>
				<?php endif; ?>
			</section>
			<?php
		endif;
		if ( $is_video_section ) :
			?>
			</div></div>
			<?php
		endif;
		if ( $blocks_html ) :
			if ( $is_video_section ) :
				?>
				<section class="video-holder"><div class="container">
				<?php
			else :
				?>
				<div class="block-holder">
				<?php
			endif;
			echo $blocks_html;
			if ( $is_video_section ) :
				?>
				</div></section>
				<?php
			else :
				?>
				</div>
				<?php
			endif;
		endif;
		?>
		<?php if ( ! $is_video_section ) : ?>
		</div>
	<?php endif; ?>
</div>

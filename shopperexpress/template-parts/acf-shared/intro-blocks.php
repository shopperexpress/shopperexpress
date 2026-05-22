<?php
/**
 * Shared: Intro Blocks
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type int    $icon               Attachment ID for intro icon.
 *   @type string $title_intro        Intro heading.
 *   @type string $content_intro      Intro body HTML.
 *   @type string $script_for_link_intro JS onclick value.
 *   @type string $title_for_link_intro  Link label.
 *   @type array  $first_image_intro  ACF image array (keys: id, url, alt).
 *   @type array  $second_image_intro ACF image array.
 *   @type string $left_text          Left column text HTML.
 *   @type string $right_text         Right column text HTML.
 *   @type array  $right_image        ACF image array.
 *   @type array  $left_image         ACF image array.
 *   @type int    $icon2              Attachment ID for block icon.
 *   @type string $title_block        Block heading.
 *   @type string $content_block      Block body HTML.
 *   @type string $script_for_link    JS onclick value.
 *   @type string $title_for_link     Link label.
 *   @type array  $first_image_block  ACF image array.
 *   @type array  $second_image_block ACF image array.
 * }
 */

$icon                  = $args['icon'] ?? null;
$title_intro           = $args['title_intro'] ?? '';
$content_intro         = $args['content_intro'] ?? '';
$script_for_link_intro = $args['script_for_link_intro'] ?? '';
$title_for_link_intro  = $args['title_for_link_intro'] ?? '';
$first_image_intro     = $args['first_image_intro'] ?? null;
$second_image_intro    = $args['second_image_intro'] ?? null;
$left_text             = $args['left_text'] ?? '';
$right_text            = $args['right_text'] ?? '';
$right_image           = $args['right_image'] ?? null;
$left_image            = $args['left_image'] ?? null;
$icon2                 = $args['icon2'] ?? null;
$title_block           = $args['title_block'] ?? '';
$content_block         = $args['content_block'] ?? '';
$script_for_link       = $args['script_for_link'] ?? '';
$title_for_link        = $args['title_for_link'] ?? '';
$first_image_block     = $args['first_image_block'] ?? null;
$second_image_block    = $args['second_image_block'] ?? null;
?>
<div class="block">
	<div class="container">
		<section class="content-block intro-block">
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
					if ( $title_intro ) :
						?>
						<h2><?php echo $title_intro; ?></h2>
					<?php endif; ?>
				</div>
				<?php if ( $content_intro ) : ?>
					<div class="holder">
						<?php echo $content_intro; ?>
					</div>
					<?php
				endif;

				if ( $script_for_link_intro && $title_for_link_intro ) :
					?>
					<a class="more" onclick="<?php echo $script_for_link_intro; ?>"><?php echo esc_html( $title_for_link_intro ); ?></a>
					<?php
				endif;
				if ( $first_image_intro ) {
					$image_id = absint( $first_image_intro['id'] );
					echo wp_kses_post( wp_get_attachment_image( $image_id, 'full', false, array( 'class' => 'item-img' ) ) );
				}
				?>
			</div>
			<?php if ( $second_image_intro ) : ?>
				<div class="img-box">
					<?php
					$image_id = absint( $second_image_intro['id'] );
					echo wp_kses_post( wp_get_attachment_image( $image_id, 'full', false ) );
					?>
				</div>
			<?php endif; ?>
		</section>
		<?php
		if ( $left_text || $right_image || $left_image || $right_text ) :
			?>
			<div class="trade-sell">
				<div class="row">
					<div class="col-md-7 d-flex text-col justify-content-center justify-content-md-start">
						<?php
						if ( $left_text ) {
							echo wpautop( $left_text );}
						?>
					</div>
					<div class="col-md-5 img-col">
						<?php
						if ( $right_image ) {
							$image_id = absint( $right_image['id'] );
							echo wp_kses_post( wp_get_attachment_image( $image_id, 'full' ) );
						}
						?>
					</div>
				</div>
				<div class="row">
					<div class="col-md-7 col-lg-8 order-2 order-md-1">
						<?php
						if ( $left_image ) {
							$image_id = absint( $left_image['id'] );
							echo wp_kses_post( wp_get_attachment_image( $image_id, 'full' ) );
						}
						?>
					</div>
					<div class="col-md-5 col-lg-4 order-1 order-md-2 align-items-center d-flex text-col justify-content-center justify-content-md-start">
						<?php
						if ( $right_text ) {
							echo $right_text;}
						?>
					</div>
				</div>
			</div>
			<?php
		endif;
		?>
		<div class="block-holder">
			<section class="content-block cash-offer">
				<div class="text-box">
					<div class="heading">
						<?php if ( $icon2 ) : ?>
							<div class="icon">
								<?php
								$image_id = absint( $icon2 );
								echo wp_kses_post( wp_get_attachment_image( $image_id, 'full' ) );
								?>
							</div>
							<?php
						endif;
						if ( $title_block ) :
							?>
							<h2><?php echo $title_block; ?></h2>
						<?php endif; ?>
					</div>
					<?php if ( $content_block ) : ?>
						<div class="holder">
							<?php echo $content_block; ?>
						</div>
						<?php
					endif;

					if ( $script_for_link || $title_for_link ) :
						?>
						<a class="more" onclick="<?php echo $script_for_link; ?>"><?php echo $title_for_link; ?></a>
					<?php endif; ?>
				</div>
				<div class="img-box">
					<?php
					if ( $first_image_block ) {
						$image_id = absint( $first_image_block['id'] );
						echo wp_get_attachment_image( $image_id, 'full' );
					}
					?>
					<?php if ( $second_image_block ) : ?>
						<div class="add-img center-left">
							<?php
							$image_id = absint( $second_image_block['id'] );
							echo wp_get_attachment_image( $image_id, 'full' );
							?>
						</div>
					<?php endif; ?>
				</div>
			</section>
		</div>

	</div>
</div>

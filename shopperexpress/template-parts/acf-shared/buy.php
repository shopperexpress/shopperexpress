<?php
/**
 * Shared: Buy
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type string $text     Heading HTML.
 *   @type string $slogan   Slogan text.
 *   @type array  $columns  Array of column items with keys: icon_image (ID), title, description.
 * }
 */

$text    = $args['text'] ?? '';
$slogan  = $args['slogan'] ?? '';
$columns = $args['columns'] ?? array();

if ( $text || $slogan || ! empty( $columns ) ) :
	?>
	<section class="section-buy">
		<div class="container">
			<div class="row text-center">
				<?php if ( $text ) : ?>
					<div class="col-12 heading">
						<?php echo $text; ?>
					</div>
					<?php
				endif;
				foreach ( $columns as $col ) :
					$icon        = $col['icon_image'] ?? null;
					$col_title   = $col['title'] ?? '';
					$description = $col['description'] ?? '';

					if ( $icon || $col_title || $description ) :
						?>
						<div class="col-md-4">
							<?php if ( $icon ) : ?>
								<div class="icon">
									<?php
									$logo_id = absint( $icon );
									echo wp_kses_post( get_attachment_image( $logo_id ) );
									?>
								</div>
								<?php
							endif;
							if ( $col_title ) :
								?>
								<h3 class="h2"><?php echo $col_title; ?></h3>
								<?php
							endif;

							echo $description;
							?>
						</div>
						<?php
					endif;
				endforeach;
				?>
			</div>
		</div>
		<?php if ( $slogan ) : ?>
			<strong class="slogan"><?php echo $slogan; ?></strong>
		<?php endif; ?>
	</section>
<?php endif; ?>

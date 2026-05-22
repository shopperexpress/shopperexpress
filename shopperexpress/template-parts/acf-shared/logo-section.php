<?php
/**
 * Shared: Logo Section
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type string $heading          Section heading.
 *   @type string $logos_backgorund Background colour for logo links.
 *   @type string $logos_per_row    Column count class modifier.
 *   @type bool   $remove_paddings  Remove padding.
 *   @type array  $logos            Array of logo items, each with keys:
 *                                  alt, new_tab (bool), link (url), image (url).
 * }
 */

$heading          = $args['heading'] ?? '';
$logos_backgorund = $args['logos_backgorund'] ?? '';
$logos_per_row    = $args['logos_per_row'] ?? '';
$padding          = $args['remove_paddings'] ?? false;
$logos            = $args['logos'] ?? array();

if ( ! empty( $logos ) ) :
	?>
	<section class="section-location"<?php echo $padding ? ' style="padding: 0;"' : ''; ?>>
		<div class="container text-center">
			<?php
			if ( $heading ) {
				?>
				<h2><?php echo $heading; ?></h2>
				<?php
			}
			?>
			<ul class="location-logos <?php echo 'location-logos--columns-' . $logos_per_row; ?>">
				<?php foreach ( $logos as $logo_item ) : ?>
					<?php
					$alt     = $logo_item['alt'] ?? '';
					$new_tab = ! empty( $logo_item['new_tab'] ) ? ' target="_blank"' : '';
					$link    = $logo_item['link'] ?? '#';
					$image   = $logo_item['image'] ?? '';
					?>
					<li>
						<a href="<?php echo esc_url( $link ); ?>" <?php echo $new_tab; ?> aria-label="<?php printf( esc_html__( 'Read more about %s', 'shopperexpress' ), $alt ); ?>"
						<?php
						if ( $logos_backgorund ) {
							echo 'style="background-color:' . $logos_backgorund . ';"';}
						?>
						>
							<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $alt ); ?>">
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
<?php endif; ?>

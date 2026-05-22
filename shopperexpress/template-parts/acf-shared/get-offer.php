<?php
/**
 * Shared: Get Offer
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type array  $background_image  ACF image array (url).
 *   @type string $text              Text HTML.
 *   @type string $for_code          Raw code HTML.
 * }
 */

$background_image = $args['background_image'] ?? null;
$text             = $args['text'] ?? '';
$for_code         = $args['for_code'] ?? '';
?>
<section class="section-get-offer bg-cover"
<?php if ( $background_image ) : ?>
	style="background-image: url(<?php echo esc_url( $background_image['url'] ); ?>);"
<?php endif; ?>>
	<div class="container">
		<div class="row">
			<?php if ( $text ) : ?>
				<div class="col-md-12 text-white text-block">
					<?php echo $text; ?>
				</div>
			<?php endif; ?>
			<div class="col-md-12">
				<?php echo $for_code; ?>
			</div>
		</div>
	</div>
</section>

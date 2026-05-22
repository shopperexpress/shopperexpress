<?php
/**
 * Shared: Offers
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type bool  $remove_paddings  Add p-0 class.
 *   @type array $offers           Array of offer items, each with:
 *                                 image (ACF image array with 'id'), description (HTML).
 * }
 */

$remove_paddings = $args['remove_paddings'] ?? false;
$offers          = $args['offers'] ?? array();

if ( ! empty( $offers ) ) : ?>
	<div class="offer-section-wrapper">
		<section class="offer-section<?php echo $remove_paddings ? ' p-0' : ''; ?>">
			<div class="container">
				<div class="row">
					<?php foreach ( $offers as $offer ) : ?>
						<div class="col-md-6">
							<?php
							if ( ! empty( $offer['image'] ) ) {
								echo wp_get_attachment_image( $offer['image']['id'], 'full' ); }
							echo $offer['description'] ?? '';
							?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	</div>
<?php endif; ?>

<?php
/**
 * Shared: Content and Video
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type array  $top_image           ACF image array (url, alt).
 *   @type string $html                Content HTML.
 *   @type string $primary_video       Primary video embed code.
 *   @type string $override_video      Override video embed code.
 *   @type string $override_start_date Override start date (Y-m-d).
 *   @type string $override_end_date   Override end date (Y-m-d).
 * }
 */

$top_image           = $args['top_image'] ?? null;
$html                = $args['html'] ?? '';
$primary_video       = $args['primary_video'] ?? '';
$override_video      = $args['override_video'] ?? '';
$override_start_date = $args['override_start_date'] ?? '';
$override_end_date   = $args['override_end_date'] ?? '';

$video = $primary_video;

if ( $override_video && $override_start_date && $override_end_date ) {
	$today = current_time( 'Y-m-d' );
	if ( $today >= $override_start_date && $today <= $override_end_date ) {
		$video = $override_video;
	}
}

if ( $top_image || $html || $video ) :
	?>
		<section class="section-awards">
			<div class="holder">
				<div class="container">
					<div class="row">
						<?php if ( $top_image || $html ) : ?>
							<div class="col-md-6">
								<div class="card-about">
									<?php if ( $top_image ) : ?>
										<img class="card-logo"
										src="<?php echo esc_url( $top_image['url'] ); ?>"
										alt="<?php echo esc_attr( $top_image['alt'] ); ?>">
									<?php endif; ?>
									<?php if ( $html ) : ?>
										<div class="card-holder">
											<?php echo wp_kses_post( $html ); ?>
										</div>
									<?php endif; ?>
								</div>
							</div>
							<?php
						endif;
						if ( $video ) :
							?>
							<div class="col-md-6">
								<div class="video-block">
									<?php echo $video; // phpcs:ignore WordPress.Security.EscapeOutput -- trusted admin-entered embed code, may include <script>. ?>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>
<?php endif; ?>

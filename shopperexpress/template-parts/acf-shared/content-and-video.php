<?php
/**
 * Shared: Content and Video
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type array  $top_image  ACF image array (url, alt).
 *   @type string $html       Content HTML.
 *   @type string $wistia_id  Wistia video ID.
 * }
 */

$top_image = $args['top_image'] ?? null;
$html      = $args['html'] ?? '';
$wistia_id = $args['wistia_id'] ?? '';

if ( $top_image || $html || $wistia_id ) :
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
					if ( $wistia_id ) :
						?>
						<div class="col-md-6">
							<div class="video-block">
								<div data-video='{"type": "wistia", "video": "<?php echo esc_html( $wistia_id ); ?>", "title": "wistia video", "autoplay": false, "fluidWidth": true, "lazyLoad": true}'></div>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>

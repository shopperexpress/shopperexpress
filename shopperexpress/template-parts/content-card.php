<?php
/**
 * Card
 *
 * This is the template that displays the card block.
 *
 * @package Shopperexpress
 */

$image       = get_sub_field( 'image' );
$text        = get_sub_field( 'text' );
$link        = get_sub_field( 'link' );
$custom_html = get_sub_field( 'custom_html' );

if ( ( $custom_html && $text ) || ( $image || $text || $link ) ) :
	?>
	<div class="col-sm-6 col-lg-4 col-xxl-3">
		<?php if ( $custom_html ) : ?>
			<div class="card card--srp-html">
				<?php echo wp_kses_post( $text ); ?>
			</div>
		<?php else : ?>
			<div class="card card--srp">
				<div class="card-body">
					<div class="card-srp-content">
						<?php
						if ( $image ) {
							echo wp_kses_post( get_attachment_image( $image ) );
						}

						echo wp_kses_post( $text );

						if ( $link ) {
							echo wp_kses_post( wps_get_link( $link, 'btn btn-primary' ) );
						}
						?>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</div>
<?php endif; ?>

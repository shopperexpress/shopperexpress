<?php
/**
 * Card
 *
 * This is the template that displays the card block.
 *
 * @package Shopperexpress
 */

$image = get_sub_field( 'image' );
$text  = get_sub_field( 'text' );
$link  = get_sub_field( 'link' );

if ( $image || $text || $link ) :
	?>
	<div class="col-sm-6 col-lg-4 col-xxl-3">
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
	</div>
<?php endif; ?>

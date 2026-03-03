<?php
$json = ! empty( $args['json'] ) ? $args['json'] : '';
if ( ! empty( $json ) ) :
	foreach ( $json as $index => $item ) :
		$StartsOn = $item['StartsOn'];
		$StartsOn = strtotime( $StartsOn );
		$StartsOn = date( 'n/j/Y', $StartsOn );
		$EndsOn   = $item['EndsOn'];
		$EndsOn   = strtotime( $EndsOn );
		$EndsOn   = date( 'n/j/Y', $EndsOn );
		?>
		<!-- Conditions offers Modal -->
		<div class="modal fade" id="conditionalOffersDetail-<?php echo esc_attr( $index ); ?>" tabindex="-1" aria-labelledby="conditionalOffersDetailLabel" aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="conditionalOffersDetailLabel"><?php echo esc_html( $item['ProgramName'] ); ?></h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'shopperexpress' ); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000">
								<path
									d="M480-424 284-228q-11 11-28 11t-28-11q-11-11-11-28t11-28l196-196-196-196q-11-11-11-28t11-28q11-11 28-11t28 11l196 196 196-196q11-11 28-11t28 11q11 11 11 28t-11 28L536-480l196 196q11 11 11 28t-11 28q-11 11-28 11t-28-11L480-424Z" />
							</svg>
						</button>
					</div>
					<div class="modal-body-wrap">
						<div class="modal-body">
							<div class="content-holder">
								<?php echo wp_kses_post( wpautop( $item['IncentiveDesc'] ) ); ?>
								<ul class="offers-detail-list list-unstyled">
									<li><?php esc_html_e( 'Valid from', 'shopperexpress' ); ?>: <?php echo esc_html( $StartsOn ); ?> <?php esc_html_e( 'through', 'shopperexpress' ); ?> <?php echo esc_html( $EndsOn ); ?></li>
									<li><?php esc_html_e( 'Incentive Id', 'shopperexpress' ); ?>: <?php echo esc_html( $item['IncentiveId'] ); ?></li>
								</ul>
							</div>
						</div>
					</div>
					<div class="modal-footer justify-content-center justify-content-md-end">
						<button type="button" data-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'shopperexpress' ); ?>" class="btn btn-primary btn-lg"><?php esc_html_e( 'Close', 'shopperexpress' ); ?></button>
					</div>
				</div>
			</div>
		</div>
		<?php
	endforeach;
endif;
?>

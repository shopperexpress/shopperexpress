<?php
$popup = \App\Components\Base\PopupResolver::instance()->get();

if ( $popup ) :

	$cookie_expiration_days = $popup['cookie_expiration_days'] ?? 0;
	$title                  = ! empty( $popup['title'] ) ? $popup['title'] : '';
	$content                = ! empty( $popup['content'] ) ? $popup['content'] : '';
	$form                   = ! empty( $popup['form'] ) ? $popup['form'] : '';
	$cookie_name            = $popup['cookie_name'] ? $popup['cookie_name'] : '';
	?>
	<!-- Cookie Modal -->
	<div data-show="true" data-cookie-expire-days="<?php echo esc_attr( $cookie_expiration_days ); ?>" class="modal fade" id="<?php echo esc_attr( $cookie_name ); ?>" tabindex="-1" aria-labelledby="CookieModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<?php if ( ! empty( $title ) ) : ?>
						<h3 class="modal-title"><?php echo esc_html( $title ); ?></h3>
					<?php endif; ?>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000">
						<path
							d="M480-424 284-228q-11 11-28 11t-28-11q-11-11-11-28t11-28l196-196-196-196q-11-11-11-28t11-28q11-11 28-11t28 11l196 196 196-196q11-11 28-11t28 11q11 11 11 28t-11 28L536-480l196 196q11 11 11 28t-11 28q-11 11-28 11t-28-11L480-424Z"
						/>
						</svg>
					</button>
				</div>
				<div class="modal-body">
					<div class="content-holder">
					<?php
					if ( ! empty( $content ) ) {
						echo wp_kses_post( $content ); }
					if ( ! empty( $form ) ) {
						echo do_shortcode( '[wpforms id="' . $form . '"]' );
					}
					?>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>

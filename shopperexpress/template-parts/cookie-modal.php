<?php
/**
 * Template for displaying cookie modal.
 *
 * @param array $args Array of arguments containing post ID and other data.
 *
 * @package Shopperexpress
 */

$popup = \App\Components\Base\PopupResolver::instance()->get();

if ( $popup ) :

	$cookie_expiration_days = $popup['cookie_expiration_days'] ?? 0;
	$title                  = ! empty( $popup['title'] ) ? $popup['title'] : '';
	$content                = ! empty( $popup['content'] ) ? $popup['content'] : '';
	$form                   = ! empty( $popup['form'] ) ? $popup['form'] : '';
	$cookie_name            = $popup['cookie_name'] ? $popup['cookie_name'] : '';
	$step                   = $popup['steps'] ? $popup['steps'] : '';
	$content_step           = ! empty( $popup['content_step'] ) ? $popup['content_step'] : '';
	$close_button           = ! empty( $popup['close_button'] ) ? $popup['close_button'] : '';
	$next_button            = ! empty( $popup['next_button'] ) ? $popup['next_button'] : '';
	?>
	<!-- Cookie Modal -->
	<div data-show="true" data-cookie-expire-days="<?php echo esc_attr( $cookie_expiration_days ); ?>" class="modal fade" id="<?php echo str_replace( ' ', '', esc_attr( $cookie_name ) ); ?>" tabindex="-1">
		<div class="modal-dialog modal-lg modal-form modal-dialog-scrollable modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<?php if ( ! empty( $title ) ) : ?>
						<h3 class="modal-title"><?php echo esc_html( $title ); ?></h3>
					<?php endif; ?>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000">
							<path
								d="M480-424 284-228q-11 11-28 11t-28-11q-11-11-11-28t11-28l196-196-196-196q-11-11-11-28t11-28q11-11 28-11t28 11l196 196 196-196q11-11 28-11t28 11q11 11 11 28t-11 28L536-480l196 196q11 11 11 28t-11 28q-11 11-28 11t-28-11L480-424Z" />
						</svg>
					</button>
				</div>

				<div class="modal-body">
					<?php if ( $step ) : ?>
						<div class="modal-steps">
							<div class="step current-step">
								<?php
								if ( ! empty( $content_step ) ) {
									echo wp_kses_post( $content_step );
								}
								?>
							</div>
							<div class="step">
								<?php
								if ( ! empty( $content ) ) {
									echo wp_kses_post( $content );
								}
								if ( ! empty( $form ) ) {
									echo do_shortcode( '[wpforms id="' . $form . '"]' );
								}
								?>
							</div>
						</div>
						<?php
					else :
						if ( ! empty( $content ) ) {
							echo wp_kses_post( $content );
						}
						if ( ! empty( $form ) ) {
							echo do_shortcode( '[wpforms id="' . $form . '"]' );
						}
					endif;
					?>
				</div>
				<?php if ( $step ) : ?>
					<div class="modal-footer">
						<div class="steps-controls">
							<?php
							if ( ! empty( $close_button ) ) {
								render_step_button(
									$close_button,
									array(
										'default_title' => esc_html__( 'Close', 'shopperexpress' ),
										'class'         => 'btn-close-step',
										'attrs'         => 'data-dismiss="modal"',
									)
								);
							}
							if ( ! empty( $next_button ) ) {
								render_step_button(
									$next_button,
									array(
										'default_title' => esc_html__( 'Next', 'shopperexpress' ),
										'class'         => 'btn-next',
									)
								);
							}
							?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
<?php endif; ?>

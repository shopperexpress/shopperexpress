<?php
/**
 * Shared: Form
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type string     $title   Section heading.
 *   @type string     $text    Intro text HTML.
 *   @type int|string $form    WPForms form ID.
 * }
 */

$heading = $args['title'] ?? '';
$text    = $args['text'] ?? '';
$form    = $args['form'] ?? null;

if ( $form || $heading || $text ) : ?>
	<section class="form-section">
		<div class="container">
			<?php if ( $heading || $text ) : ?>
				<div class="heading text-center">
					<?php if ( $heading ) : ?>
						<h2>
							<?php echo esc_html( $heading ); ?>
						</h2>
						<?php
					endif;

					echo wp_kses_post( $text );
					?>
				</div>
				<?php
			endif;
			if ( $form ) {
				$form_id = absint( $form );
				echo do_shortcode( '[wpforms id="' . $form_id . '" title="false"]' );
			}
			?>
		</div>
	</section>
<?php endif; ?>

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
 *   @type string     $anchor  Optional HTML id (Gutenberg block "HTML anchor") so other
 *                             links on the page can scroll directly to this section.
 * }
 */

$heading = $args['title'] ?? '';
$text    = $args['text'] ?? '';
$form    = $args['form'] ?? null;
$anchor  = $args['anchor'] ?? '';

if ( $form || $heading || $text ) : ?>
	<section<?php echo $anchor ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?> class="form-section">
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

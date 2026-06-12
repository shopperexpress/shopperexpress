<?php
/**
 * VDP bottom description block for API mode.
 *
 * Renders the vdp_description ACF options block and AI description
 * from the Intice vehicle payload.
 *
 * Accepts $args:
 *   vehicle (array) — Intice API vehicle object
 *
 * @package Shopperexpress
 */

$vehicle = $args['vehicle'] ?? array();
$payload = $vehicle['payload'] ?? array();

$vdp_description = get_field( 'vdp_description', 'options' );
$ai_description  = $payload['ai_vdp_description'] ?? '';

if ( ! $vdp_description && ! $ai_description ) {
	return;
}
?>
<div class="vdp-description">
	<div class="container">
		<div class="vdp-content">
			<?php if ( ! empty( $vdp_description['heading'] ) ) : ?>
				<strong class="title"><?php echo esc_html( $vdp_description['heading'] ); ?></strong>
			<?php endif; ?>
			<?php if ( ! empty( $vdp_description['text'] ) ) : echo wp_kses_post( $vdp_description['text'] ); endif; ?>
			<ul class="vdp-list">
				<?php
				if ( have_rows( 'vdp_description', 'options' ) ) :
					while ( have_rows( 'vdp_description', 'options' ) ) :
						the_row();
						$text = get_sub_field( 'text' );
						if ( $text ) :
							echo '<li>' . esc_html( $text ) . '</li>';
						endif;
					endwhile;
				endif;
				if ( $ai_description ) :
					echo '<li>' . wp_kses_post( $ai_description ) . '</li>';
				endif;
				?>
			</ul>
		</div>
	</div>
</div>

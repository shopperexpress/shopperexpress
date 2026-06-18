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

ob_start();
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
$list_items = ob_get_clean();

$heading = $vdp_description['heading'] ?? '';
$body    = $vdp_description['text'] ?? '';

if ( ! $heading && ! $body && ! trim( $list_items ) ) {
	return;
}
?>
<div class="vdp-description">
	<div class="container">
		<div class="vdp-content">
			<?php if ( $heading ) : ?>
				<strong class="title"><?php echo esc_html( $heading ); ?></strong>
			<?php endif; ?>
			<?php if ( $body ) : echo wp_kses_post( $body ); endif; ?>
			<?php if ( trim( $list_items ) ) : ?>
				<ul class="vdp-list">
					<?php echo $list_items; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</div>

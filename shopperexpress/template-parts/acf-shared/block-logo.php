<?php
/**
 * Shared: Block Logo
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type array  $logo    ACF image array (keys: id, url, alt).
 *   @type string $anchor  Optional HTML id (Gutenberg block "HTML anchor") so other links on the page can scroll directly to this section.
 * }
 */

$logo   = $args['logo'] ?? null;
$anchor = $args['anchor'] ?? '';
?>
<section<?php echo $anchor ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?> class="block-logo">
	<div class="container">
		<?php
		if ( $logo ) {
			$logo_id = absint( $logo['id'] );
			echo wp_kses_post( wp_get_attachment_image( $logo_id, 'full', null, array( 'class' => 'logo-lg' ) ) );
		}
		?>
	</div>
</section>

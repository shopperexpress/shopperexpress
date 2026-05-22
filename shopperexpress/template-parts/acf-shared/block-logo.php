<?php
/**
 * Shared: Block Logo
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type array $logo  ACF image array (keys: id, url, alt).
 * }
 */

$logo = $args['logo'] ?? null;
?>
<section class="block-logo">
	<div class="container">
		<?php
		if ( $logo ) {
			$logo_id = absint( $logo['id'] );
			echo wp_kses_post( wp_get_attachment_image( $logo_id, 'full', null, array( 'class' => 'logo-lg' ) ) );
		}
		?>
	</div>
</section>

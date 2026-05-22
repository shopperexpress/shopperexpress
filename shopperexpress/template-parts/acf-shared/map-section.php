<?php
/**
 * Shared: Map Section
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type string $title     Heading.
 *   @type string $subtitle  Address HTML.
 *   @type array  $button    ACF link array (url, title).
 *   @type string $ltd       Latitude.
 *   @type string $lng       Longitude.
 *   @type string $zoom      Map zoom level.
 * }
 */

$title    = $args['title'] ?? '';
$subtitle = $args['subtitle'] ?? '';
$button   = $args['button'] ?? null;
$ltd      = $args['ltd'] ?? '';
$lng      = $args['lng'] ?? '';
$zoom     = $args['zoom'] ?? '';
?>

<section class="section-find-us">
	<div class="container">
		<div class="row">
			<?php if ( $title || $subtitle || $button ) : ?>
				<div class="col-md-6 col-lg-4">
					<div class="text-block">
						<?php
						if ( $title ) {
							echo '<h2>' . $title . '</h2>';}
						?>
						<?php
						if ( $subtitle ) {
							echo '<address>' . $subtitle . '</address>';}
						?>
						<?php
						if ( $button ) {
							echo '<a class="btn btn-primary btn-pill" target="_blank" href="' . $button['url'] . '">' . $button['title'] . '</a>';}
						?>
					</div>
				</div>
			<?php endif; ?>
			<?php if ( $ltd && $lng ) : ?>
				<div class="col-md-6 col-lg-8">
					<div class="map-holder" data-coordinates="<?php echo $ltd . ', ' . $lng; ?>" data-zoom="<?php echo $zoom; ?>"></div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>

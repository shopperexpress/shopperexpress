<?php
/**
 * Shared: HTML Block
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type bool   $is_container        Wrap in .container.
 *   @type string $css_class           Extra CSS class.
 *   @type bool   $add_grey_background Add bg-gray class.
 *   @type bool   $remove_paddings     Add p-0 class.
 *   @type string $html                Raw HTML output.
 * }
 */

$is_container        = $args['is_container'] ?? false;
$css_class           = $args['css_class'] ?? '';
$add_grey_background = $args['add_grey_background'] ?? false;
$remove_paddings     = $args['remove_paddings'] ?? false;
$html                = $args['html'] ?? '';

$section_classes = 'section-html';
if ( $add_grey_background ) {
	$section_classes .= ' bg-gray';
}
if ( $remove_paddings ) {
	$section_classes .= ' p-0';
}
if ( $css_class ) {
	$section_classes .= ' ' . $css_class;
}
?>
<section class="<?php echo esc_attr( $section_classes ); ?>">
	<?php if ( $is_container ) : ?>
		<div class="container">
	<?php endif; ?>
		<?php echo $html; ?>
	<?php if ( $is_container ) : ?>
		</div>
	<?php endif; ?>
</section>

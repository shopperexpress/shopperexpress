<?php
/**
 * Shared: Gallery
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type bool   $gray_background  Add bg-gray class.
 *   @type bool   $full_width       Full-width (no .container).
 *   @type bool   $remove_paddings  Add py-0.
 *   @type string $text             Intro HTML.
 *   @type array  $images           Array of ACF image arrays (id, url).
 *   @type int    $row_index        Parent row index for fancybox group.
 * }
 */

$gray_background = ! empty( $args['gray_background'] ) ? ' bg-gray' : '';
$full_width      = ! empty( $args['full_width'] );
$full_width_cls  = $full_width ? null : ' container';
$size            = $full_width ? array( 328, 250 ) : 'full';
$remove_paddings = ! empty( $args['remove_paddings'] );
$text            = $args['text'] ?? '';
$images          = $args['images'] ?? array();
$row_index       = $args['row_index'] ?? 0;

if ( ! empty( $images ) || $text ) :
	?>
	<section class="gallery-grid<?php echo $gray_background; ?><?php echo $remove_paddings ? ' py-0' : ''; ?>">
		<?php
		echo $text;

		if ( ! empty( $images ) ) :
			?>
			<ul class="gallery-grid__list list-unstyled<?php echo $full_width_cls; ?>">
				<?php foreach ( $images as $image ) : ?>
					<li><a href="<?php echo esc_url( $image['url'] ); ?>" data-fancybox="grid-gallery-<?php echo esc_attr( $row_index ); ?>"><?php echo wp_get_attachment_image( $image['id'], $size ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
<?php endif; ?>

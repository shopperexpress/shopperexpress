<?php
/**
 * Shared: Single Video
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type string $video_code    Embed code / iframe HTML.
 *   @type bool   $remove_margin Add my-0 class.
 *   @type string $anchor        Optional HTML id (Gutenberg block "HTML anchor") so other links on the page can scroll directly to this section.
 * }
 */

$video_code    = $args['video_code'] ?? '';
$remove_margin = $args['remove_margin'] ?? false;
$anchor        = $args['anchor'] ?? '';

if ( $video_code ) :
	?>
	<div<?php echo $anchor ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?> class="video-section<?php echo $remove_margin ? ' my-0' : ''; ?>">
		<div class="container">
			<div class="video-block">
				<?php echo $video_code; ?>
			</div>
		</div>
	</div>
<?php endif; ?>

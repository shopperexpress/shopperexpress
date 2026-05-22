<?php
/**
 * Shared: Single Video
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type string $video_code    Embed code / iframe HTML.
 *   @type bool   $remove_margin Add my-0 class.
 * }
 */

$video_code    = $args['video_code'] ?? '';
$remove_margin = $args['remove_margin'] ?? false;

if ( $video_code ) :
	?>
	<div class="video-section<?php echo $remove_margin ? ' my-0' : ''; ?>">
		<div class="container">
			<div class="video-block">
				<?php echo $video_code; ?>
			</div>
		</div>
	</div>
<?php endif; ?>

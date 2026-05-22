<?php
/**
 * Shared: Buttons
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type array $buttons  Array of items, each with key 'button_code' (HTML string).
 *   @type bool  $is_preview Whether preview mode is active.
 * }
 */

$buttons    = $args['buttons'] ?? array();
$is_preview = $args['is_preview'] ? ' id="page-container"' : '';

if ( ! empty( $buttons ) ) :
	?>
<div <?php echo $is_preview; ?>>
	<section class="info-section">
		<div class="container">
			<div class="info-wrapp">
				<ul class="info-list">
					<?php foreach ( $buttons as $item ) : ?>
						<?php if ( ! empty( $item['button_code'] ) ) : ?>
							<li><?php echo $item['button_code']; ?></li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</section>
</div>
<?php endif; ?>

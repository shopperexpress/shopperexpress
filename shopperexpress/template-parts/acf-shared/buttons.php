<?php
/**
 * Shared: Buttons
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type array  $buttons  Array of items, each with key 'button_code' (HTML string).
 *   @type bool   $is_preview Whether preview mode is active.
 *   @type string $anchor   Optional HTML id (Gutenberg block "HTML anchor") so other
 *                          links on the page can scroll directly to this section.
 * }
 */

$buttons    = $args['buttons'] ?? array();
$is_preview = $args['is_preview'] ? ' id="page-container"' : '';
$anchor     = $args['anchor'] ?? '';

if ( ! empty( $buttons ) ) :
	?>
<div <?php echo $is_preview; ?>>
	<section<?php echo $anchor ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?> class="info-section">
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

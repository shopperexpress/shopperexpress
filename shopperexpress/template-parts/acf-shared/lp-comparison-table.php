<?php
/**
 * Shared: LP Comparison Table
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type string $subtitle
 *   @type string $title
 *   @type array  $columns  Array of {label, logo (image URL), highlight (bool)}, in display order.
 *   @type array  $rows     Array of {label, values} — values is a flat array of strings, same order/count as $columns.
 *   @type string $anchor
 * }
 */

$subtitle = $args['subtitle'] ?? '';
$title    = $args['title'] ?? '';
$columns  = $args['columns'] ?? array();
$rows     = $args['rows'] ?? array();
$anchor   = $args['anchor'] ?? '';

$check_svg = '<svg aria-hidden="true" focusable="false" width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M22.1273804,7.7163696c-.581665-1.3397827-1.3661499-2.5032349-2.3533936-3.4904175-.9870605-.9871826-2.1505737-1.7716064-3.4902954-2.3533325-1.3397827-.5817871-2.7677002-.8726196-4.2836914-.8726196s-2.9439087.2908325-4.2836304.8726196c-1.3397827.5640869-2.5076294,1.3441162-3.5036011,2.3401489-.9960327.9959717-1.776062,2.1638184-2.3401489,3.5036011-.5817261,1.3397217-.8726196,2.7676392-.8726196,4.2836304s.2908936,2.9439087.8726196,4.2836914c.5640869,1.3397217,1.3441162,2.5076294,2.3401489,3.5036011.9959717.9960327,2.1638184,1.776001,3.5036011,2.3400879,1.3397217.5817261,2.7676392.8726196,4.2836304.8726196s2.9439087-.2908936,4.2836914-.8726196c1.3397217-.5640869,2.5076294-1.3440552,3.5036011-2.3400879.9960327-.9959717,1.776001-2.1638794,2.3400879-3.5036011.5817261-1.3397827.8726196-2.7677002.8726196-4.2836914s-.2908936-2.9439087-.8726196-4.2836304ZM17.0108032,9.7920532c-.0441284.0969849-.1013184.1895142-.171875.2776489l-5.9230347,5.9230957c-.0881958.0704956-.1806641.1278076-.27771.171875-.0968628.0440063-.2070923.0661011-.3305054.0661011-.1233521,0-.2335815-.0220947-.3305054-.0661011-.0969849-.0440674-.1895142-.1013794-.2776489-.171875l-2.5384521-2.5385132c-.0705566-.0880737-.1278076-.1806641-.171875-.2775879-.0440674-.0969849-.0661011-.2070923-.0661011-.3305054,0-.229187.0837402-.4274902.2511597-.5949707.1674805-.1675415.3657837-.2512207.5949707-.2512207.1234131,0,.2335815.0219727.3305054.0661011.0969849.0441284.1895142.1013794.2776489.171875l1.9302979,1.9567261,5.3149414-5.3413696c.0880737-.0704956.1806641-.1277466.2775879-.171814.0969849-.0441284.2072144-.0661621.3305664-.0661621.229126,0,.4275513.0837402.5949097.2512207.1675415.1674805.2512207.3657837.2512207.5949707,0,.1234131-.0220337.2335815-.0661011.3305054Z" fill="#00a63e"/></svg>';
$cross_svg = '<svg aria-hidden="true" focusable="false" width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M18,19c-.2558594,0-.5117188-.0976562-.7070312-.2929688l-5.2929688-5.2929688-5.2929688,5.2929688c-.390625.390625-1.0234375.390625-1.4140625,0s-.390625-1.0234375,0-1.4140625l5.2929688-5.2929688-5.2929688-5.2929688c-.390625-.390625-.390625-1.0234375,0-1.4140625s1.0234375-.390625,1.4140625,0l5.2929688,5.2929688,5.2929688-5.2929688c.390625-.390625,1.0234375-.390625,1.4140625,0s.390625,1.0234375,0,1.4140625l-5.2929688,5.2929688,5.2929688,5.2929688c.390625.390625.390625,1.0234375,0,1.4140625-.1953125.1953125-.4511719.2929688-.7070312.2929688Z" fill="#62748E"/></svg>';

if ( ! empty( $rows ) && ! empty( $columns ) ) :

	$render_cell = function ( $value ) use ( $check_svg, $cross_svg ) {
		$normalized = strtolower( trim( (string) $value ) );

		if ( in_array( $normalized, array( 'yes', 'true', '1' ), true ) ) {
			echo '<span class="comparison-status">' . $check_svg . '<span class="sr-only">Available</span></span>';
		} elseif ( in_array( $normalized, array( 'no', 'false', '0' ), true ) ) {
			echo '<span class="comparison-status">' . $cross_svg . '<span class="sr-only">Not available</span></span>';
		} elseif ( '' !== $normalized ) {
			echo '<span class="comparison-status">' . esc_html( $value ) . '</span>';
		}
	};
	?>
	<section<?php echo $anchor ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?> class="lp-section">
		<div class="container">
			<div class="lp-section__holder">
				<?php if ( $subtitle || $title ) : ?>
					<div class="lp-section__head">
						<?php if ( $subtitle ) : ?>
							<span class="lp-subtitle"><?php echo esc_html( $subtitle ); ?></span>
						<?php endif; ?>
						<?php if ( $title ) : ?>
							<h2 class="lp-title"><?php echo wp_kses_post( $title ); ?></h2>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<div class="lp-compare-wrap">
					<div class="lp-compare">
						<div class="lp-compare__column">
							<div></div>
							<?php foreach ( $rows as $row ) : ?>
								<div><?php echo esc_html( $row['label'] ?? '' ); ?></div>
							<?php endforeach; ?>
						</div>
						<?php foreach ( $columns as $col_index => $column ) : ?>
							<div class="lp-compare__column<?php echo ! empty( $column['highlight'] ) ? ' column-primary' : ''; ?>">
								<div>
									<?php echo wp_kses_post( $column['label'] ?? '' ); ?>
									<?php if ( ! empty( $column['logo'] ) ) : ?>
										<img src="<?php echo esc_url( $column['logo'] ); ?>" alt="<?php echo esc_attr( wp_strip_all_tags( $column['label'] ?? '' ) ); ?>" />
									<?php endif; ?>
								</div>
								<?php foreach ( $rows as $row ) : ?>
									<div><?php $render_cell( $row['values'][ $col_index ] ?? '' ); ?></div>
								<?php endforeach; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>

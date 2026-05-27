<?php
/**
 * SOC Performance
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit; ?>

<div id="soc-action-notice" class="soc-notice" role="alert"></div>

<!-- 1. Metrics Cards -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Performance Metrics', 'shopperexpress' ); ?></div>
	<div class="soc-grid">
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Database Size', 'shopperexpress' ); ?></div>
			<div class="soc-card__value"><?php echo esc_html( number_format( (float) ( $data['db_size_mb'] ?? 0 ), 2 ) ); ?></div>
			<div class="soc-card__sub">MB</div>
		</div>
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Autoload Size', 'shopperexpress' ); ?></div>
			<div class="soc-card__value"><?php echo esc_html( number_format( (float) ( $data['autoload_size_kb'] ?? 0 ), 2 ) ); ?></div>
			<div class="soc-card__sub">KB</div>
		</div>
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Query Count', 'shopperexpress' ); ?></div>
			<div class="soc-card__value"><?php echo esc_html( $data['query_count'] ?? 0 ); ?></div>
		</div>
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Memory Peak', 'shopperexpress' ); ?></div>
			<div class="soc-card__value"><?php echo esc_html( number_format( (float) ( $data['memory_peak_mb'] ?? 0 ), 2 ) ); ?></div>
			<div class="soc-card__sub">MB</div>
		</div>
	</div>
</div>

<!-- 2. Load Time Samples -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Load Time Samples', 'shopperexpress' ); ?></div>

	<?php if ( ! empty( $data['load_samples'] ) ) : ?>
		<table class="soc-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Time (ms)', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Memory (MB)', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Queries', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Sampled At', 'shopperexpress' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$samples = array_slice( (array) $data['load_samples'], 0, 20 );
				foreach ( $samples as $sample ) :
					$ms = (float) ( $sample['time_ms'] ?? 0 );
					if ( $ms < 500 ) {
						$color = '#00a32a';
					} elseif ( $ms < 1000 ) {
						$color = '#dba617';
					} else {
						$color = '#d63638';
					}
					?>
					<tr>
						<td style="color:<?php echo esc_attr( $color ); ?>; font-weight:600;">
							<?php echo esc_html( number_format( $ms, 0 ) ); ?> ms
						</td>
						<td><?php echo esc_html( number_format( (float) ( $sample['memory_mb'] ?? 0 ), 2 ) ); ?></td>
						<td><?php echo esc_html( $sample['queries'] ?? 0 ); ?></td>
						<td><?php echo esc_html( $sample['sampled_at'] ?? 'N/A' ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p><?php esc_html_e( 'No load time samples recorded yet.', 'shopperexpress' ); ?></p>
	<?php endif; ?>

	<p class="soc-footer-note" style="margin-top:10px;">
		<em><?php esc_html_e( 'Samples collected at 1-in-20 probability on admin requests.', 'shopperexpress' ); ?></em>
	</p>
</div>

<!-- 3. Slow Queries -->
<?php if ( ! empty( $data['slow_queries'] ) ) : ?>
	<div class="soc-section">
		<div class="soc-section__title"><?php esc_html_e( 'Slow Queries', 'shopperexpress' ); ?></div>
		<table class="soc-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Query', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Time (ms)', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Recorded At', 'shopperexpress' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $data['slow_queries'] as $sq ) : ?>
					<tr>
						<td><code style="font-size:11px; word-break:break-all;"><?php echo esc_html( $sq['query'] ?? '' ); ?></code></td>
						<td style="color:#d63638; font-weight:600;"><?php echo esc_html( number_format( (float) ( $sq['time_ms'] ?? 0 ), 2 ) ); ?> ms</td>
						<td><?php echo esc_html( $sq['recorded_at'] ?? 'N/A' ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>

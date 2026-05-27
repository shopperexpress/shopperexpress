<?php
/**
 * SOC Health API
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit; ?>

<div id="soc-action-notice" class="soc-notice" role="alert"></div>

<!-- 1. Internal Endpoints -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Internal Endpoints', 'shopperexpress' ); ?></div>
	<?php if ( ! empty( $data['internal'] ) ) : ?>
		<table class="soc-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'URL', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Status', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Response Time', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'OK', 'shopperexpress' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $data['internal'] as $ep ) : ?>
					<tr>
						<td><?php echo esc_html( $ep['name'] ?? '' ); ?></td>
						<td><code><?php echo esc_html( $ep['url'] ?? '' ); ?></code></td>
						<td><?php echo esc_html( $ep['status'] ?? 'N/A' ); ?></td>
						<td><?php echo esc_html( isset( $ep['response_time_ms'] ) ? $ep['response_time_ms'] . ' ms' : 'N/A' ); ?></td>
						<td><?php echo $ep['ok'] ? '<span class="soc-badge soc-badge--ok">&#10003;</span>' : '<span class="soc-badge soc-badge--fail">&#10007;</span>'; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p><?php esc_html_e( 'No endpoints.', 'shopperexpress' ); ?></p>
	<?php endif; ?>
</div>

<!-- 2. Integrations -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Integrations', 'shopperexpress' ); ?></div>
	<?php if ( ! empty( $data['integrations'] ) ) : ?>
		<table class="soc-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Service', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Endpoint', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Status', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Response Time', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'OK', 'shopperexpress' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $data['integrations'] as $ep ) :
					$ok = $ep['ok'];
					if ( $ok === null ) {
						$badge = '<span class="soc-badge soc-badge--neutral">—</span>';
					} elseif ( $ok ) {
						$badge = '<span class="soc-badge soc-badge--ok">&#10003;</span>';
					} else {
						$badge = '<span class="soc-badge soc-badge--fail">&#10007;</span>';
					}
					?>
					<tr>
						<td><strong><?php echo esc_html( $ep['name'] ?? '' ); ?></strong></td>
						<td>
							<?php if ( ! empty( $ep['url'] ) ) : ?>
								<code><?php echo esc_html( $ep['url'] ); ?></code>
							<?php else : ?>
								<em><?php esc_html_e( 'Not configured', 'shopperexpress' ); ?></em>
							<?php endif; ?>
						</td>
						<td>
							<?php echo esc_html( $ep['status'] ?? 'N/A' ); ?>
							<?php if ( ! empty( $ep['error'] ) ) : ?>
								<span class="soc-hint" title="<?php echo esc_attr( $ep['error'] ); ?>">&#9432;</span>
							<?php endif; ?>
						</td>
						<td><?php echo $ep['response_time_ms'] > 0 ? esc_html( $ep['response_time_ms'] . ' ms' ) : '—'; ?></td>
						<td><?php echo $badge; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>

<!-- 3. API Test Tool -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'API Test Tool', 'shopperexpress' ); ?></div>
	<div class="soc-action-bar">
		<input type="url"
				id="soc-test-api-url"
				class="regular-text"
				placeholder="<?php esc_attr_e( 'https://example.com/api/endpoint', 'shopperexpress' ); ?>" />
		<button id="soc-test-api-btn" class="button button-primary">
			<?php esc_html_e( 'Test URL', 'shopperexpress' ); ?>
		</button>
	</div>
	<div id="soc-api-test-result" style="display:none; margin-top:8px; font-size:13px;"></div>
</div>

<!-- 4. Failed API Log -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Failed API Log', 'shopperexpress' ); ?></div>
	<?php if ( ! empty( $data['failed_log'] ) ) : ?>
		<pre class="soc-log-output">
		<?php
		foreach ( array_slice( (array) $data['failed_log'], 0, 100 ) as $entry ) {
			echo esc_html( $entry ) . "\n";
		}
		?>
		</pre>
	<?php else : ?>
		<p><?php esc_html_e( 'No failed API log entries.', 'shopperexpress' ); ?></p>
	<?php endif; ?>
</div>

<p class="soc-footer-note">
	<em><?php printf( esc_html__( 'Last collected: %s', 'shopperexpress' ), esc_html( $data['collected_at'] ?? 'N/A' ) ); ?></em>
</p>

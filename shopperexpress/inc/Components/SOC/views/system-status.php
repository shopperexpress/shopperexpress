<?php
/**
 * SOC System Status
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Helper: render a colored status badge.
 *
 * @param bool   $ok  Whether the status is good.
 * @param string $yes Label for OK state.
 * @param string $no  Label for fail state.
 */
function soc_badge( bool $ok, string $yes = '✓', string $no = '✗' ): void {
	$class = $ok ? 'soc-badge--ok' : 'soc-badge--fail';
	$label = $ok ? $yes : $no;
	echo '<span class="soc-badge ' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
}
?>

<div id="soc-action-notice" class="soc-notice" role="alert"></div>

<!-- 1. PHP Environment -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'PHP Environment', 'shopperexpress' ); ?></div>
	<table class="soc-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Setting', 'shopperexpress' ); ?></th>
				<th><?php esc_html_e( 'Value', 'shopperexpress' ); ?></th>
				<th><?php esc_html_e( 'Status', 'shopperexpress' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?php esc_html_e( 'PHP Version', 'shopperexpress' ); ?></td>
				<td><?php echo esc_html( $data['php_version'] ?? 'N/A' ); ?></td>
				<td><?php soc_badge( ! empty( $data['php_ok'] ) ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Memory Limit', 'shopperexpress' ); ?></td>
				<td><?php echo esc_html( $data['memory_limit'] ?? 'N/A' ); ?></td>
				<td></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Memory Usage', 'shopperexpress' ); ?></td>
				<td><?php echo esc_html( $data['memory_usage'] ?? 'N/A' ); ?></td>
				<td></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Memory Peak', 'shopperexpress' ); ?></td>
				<td><?php echo esc_html( $data['memory_peak'] ?? 'N/A' ); ?></td>
				<td></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Max Execution Time', 'shopperexpress' ); ?></td>
				<td><?php echo esc_html( $data['max_execution'] ?? 'N/A' ); ?></td>
				<td></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Max Upload Size', 'shopperexpress' ); ?></td>
				<td><?php echo esc_html( $data['max_upload'] ?? 'N/A' ); ?></td>
				<td></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Post Max Size', 'shopperexpress' ); ?></td>
				<td><?php echo esc_html( $data['post_max_size'] ?? 'N/A' ); ?></td>
				<td></td>
			</tr>
		</tbody>
	</table>
</div>

<!-- 2. WordPress -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'WordPress', 'shopperexpress' ); ?></div>
	<table class="soc-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Setting', 'shopperexpress' ); ?></th>
				<th><?php esc_html_e( 'Value', 'shopperexpress' ); ?></th>
				<th><?php esc_html_e( 'Status', 'shopperexpress' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?php esc_html_e( 'WP Version', 'shopperexpress' ); ?></td>
				<td><?php echo esc_html( $data['wp_version'] ?? 'N/A' ); ?></td>
				<td></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'WP_DEBUG', 'shopperexpress' ); ?></td>
				<td><?php echo esc_html( ! empty( $data['wp_debug'] ) ? 'true' : 'false' ); ?></td>
				<td>
				<?php
				if ( ! empty( $data['wp_debug'] ) ) {
					echo '<span class="soc-badge soc-badge--warn">' . esc_html__( 'Warning: Debug On', 'shopperexpress' ) . '</span>';
				} else {
					soc_badge( true );
				}
				?>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'WP_DEBUG_LOG', 'shopperexpress' ); ?></td>
				<td><?php echo esc_html( ! empty( $data['wp_debug_log'] ) ? 'true' : 'false' ); ?></td>
				<td></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Multisite', 'shopperexpress' ); ?></td>
				<td><?php echo esc_html( ! empty( $data['multisite'] ) ? 'true' : 'false' ); ?></td>
				<td></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'REST URL', 'shopperexpress' ); ?></td>
				<td><?php echo esc_html( $data['rest_url'] ?? 'N/A' ); ?></td>
				<td></td>
			</tr>
		</tbody>
	</table>
</div>

<!-- 3. Server -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Server', 'shopperexpress' ); ?></div>
	<table class="soc-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Setting', 'shopperexpress' ); ?></th>
				<th><?php esc_html_e( 'Value', 'shopperexpress' ); ?></th>
				<th><?php esc_html_e( 'Status', 'shopperexpress' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?php esc_html_e( 'Server Software', 'shopperexpress' ); ?></td>
				<td><?php echo esc_html( $data['server_software'] ?? 'N/A' ); ?></td>
				<td></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'MySQL Version', 'shopperexpress' ); ?></td>
				<td><?php echo esc_html( $data['mysql_version'] ?? 'N/A' ); ?></td>
				<td></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'SSL', 'shopperexpress' ); ?></td>
				<td><?php echo esc_html( ! empty( $data['ssl'] ) ? 'Active' : 'Inactive' ); ?></td>
				<td><?php soc_badge( ! empty( $data['ssl'] ) ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'shell_exec', 'shopperexpress' ); ?></td>
				<td><?php echo esc_html( ! empty( $data['shell_exec'] ) ? 'Available' : 'Disabled' ); ?></td>
				<td></td>
			</tr>
		</tbody>
	</table>
</div>

<!-- 4. Filesystem -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Filesystem', 'shopperexpress' ); ?></div>
	<table class="soc-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Path', 'shopperexpress' ); ?></th>
				<th><?php esc_html_e( 'Writable', 'shopperexpress' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?php esc_html_e( 'wp-content', 'shopperexpress' ); ?></td>
				<td><?php soc_badge( ! empty( $data['wp_content_writable'] ), 'Writable', 'Not Writable' ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'uploads', 'shopperexpress' ); ?></td>
				<td><?php soc_badge( ! empty( $data['uploads_writable'] ), 'Writable', 'Not Writable' ); ?></td>
			</tr>
		</tbody>
	</table>
</div>

<!-- 5. OPcache -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'OPcache', 'shopperexpress' ); ?></div>
	<table class="soc-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Setting', 'shopperexpress' ); ?></th>
				<th><?php esc_html_e( 'Value', 'shopperexpress' ); ?></th>
				<th><?php esc_html_e( 'Status', 'shopperexpress' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?php esc_html_e( 'OPcache Enabled', 'shopperexpress' ); ?></td>
				<td><?php echo esc_html( ! empty( $data['opcache_enabled'] ) ? 'Yes' : 'No' ); ?></td>
				<td><?php soc_badge( ! empty( $data['opcache_enabled'] ) ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Hit Rate', 'shopperexpress' ); ?></td>
				<td>
				<?php
					$rate = $data['opcache_hit_rate'] ?? null;
					echo esc_html( $rate !== null ? number_format( (float) $rate, 2 ) . '%' : 'N/A' );
				?>
				</td>
				<td></td>
			</tr>
		</tbody>
	</table>
</div>

<!-- Footer -->
<p class="soc-footer-note">
	<em>
		<?php
		printf(
			/* translators: %s: collection timestamp */
			esc_html__( 'Cached data — last collected: %s', 'shopperexpress' ),
			esc_html( $data['collected_at'] ?? 'N/A' )
		);
		?>
		&nbsp;&mdash;&nbsp;<?php esc_html_e( 'Cache refreshes every 5 minutes.', 'shopperexpress' ); ?>
	</em>
</p>

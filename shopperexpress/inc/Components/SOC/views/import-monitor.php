<?php
/**
 * SOC Import Monitor View
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit;

$monitoring_enabled = $data['monitoring_enabled'] ?? false;
$rows               = $data['rows'] ?? array();
$total              = $data['total'] ?? 0;
$success            = $data['success'] ?? 0;
$failures           = $data['failures'] ?? 0;
$never              = $data['never'] ?? 0;
$next_cron          = $data['next_cron'] ?? false;
?>

<div id="soc-action-notice" class="soc-notice" role="alert"></div>

<?php if ( ! $monitoring_enabled ) : ?>
<div class="notice notice-warning inline" style="margin-bottom:16px;">
	<p>
		<strong><?php esc_html_e( 'Monitoring is currently disabled.', 'shopperexpress' ); ?></strong>
		<?php esc_html_e( 'Enable it on the', 'shopperexpress' ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=monitor-settings' ) ); ?>"><?php esc_html_e( 'Import Monitor settings page', 'shopperexpress' ); ?></a>.
	</p>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Overview', 'shopperexpress' ); ?></div>
	<div class="soc-grid">
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Total Monitored', 'shopperexpress' ); ?></div>
			<div class="soc-card__value"><?php echo esc_html( $total ); ?></div>
		</div>
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Last Run OK', 'shopperexpress' ); ?></div>
			<div class="soc-card__value" style="color:#1a6130;"><?php echo esc_html( $success ); ?></div>
		</div>
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Last Run Failed', 'shopperexpress' ); ?></div>
			<div class="soc-card__value" style="color:#d63638;"><?php echo esc_html( $failures ); ?></div>
		</div>
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Never Run', 'shopperexpress' ); ?></div>
			<div class="soc-card__value" style="color:#856404;"><?php echo esc_html( $never ); ?></div>
		</div>
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Next Cron Check', 'shopperexpress' ); ?></div>
			<div class="soc-card__value">
				<?php echo $next_cron ? esc_html( human_time_diff( $next_cron ) ) : '<span style="color:#aaa">—</span>'; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</div>
	</div>
</div>

<!-- Imports Table -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Monitored Imports', 'shopperexpress' ); ?></div>

	<?php if ( empty( $rows ) ) : ?>
		<p>
			<?php esc_html_e( 'No imports are configured yet.', 'shopperexpress' ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=monitor-settings' ) ); ?>"><?php esc_html_e( 'Add monitored imports →', 'shopperexpress' ); ?></a>
		</p>
	<?php else : ?>
		<table class="soc-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Name', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Check Mode', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Last Run', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Records', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Status', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Last Error', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Active', 'shopperexpress' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $rows as $row ) :
					$cfg   = $row['config'];
					$state = $row['state'];
					$id    = (int) $cfg['import_id'];
					$stat  = $state['status'] ?? '';

					$badge_class = match ( $stat ) {
						'success' => 'soc-badge--ok',
						'failure' => 'soc-badge--fail',
						default   => 'soc-badge--neutral',
					};
					$badge_label = $stat ?: esc_html__( 'Never Run', 'shopperexpress' );
					?>
					<tr>
						<td><?php echo esc_html( $id ); ?></td>
						<td><strong><?php echo esc_html( $cfg['import_name'] ?? '' ); ?></strong></td>
						<td><?php echo esc_html( ucwords( str_replace( '_', ' ', $cfg['check_mode'] ?? '' ) ) ); ?></td>
						<td>
							<?php if ( ! empty( $state['last_run'] ) ) : ?>
								<?php echo esc_html( $state['last_run'] ); ?>
								<?php $run_gmt = (int) ( $state['last_run_gmt'] ?? 0 ); if ( $run_gmt > 0 ) : ?>
									<br><small style="color:#888;"><?php echo esc_html( human_time_diff( $run_gmt ) ); ?> <?php esc_html_e( 'ago', 'shopperexpress' ); ?></small>
								<?php endif; ?>
							<?php else : ?>
								<span style="color:#aaa;">—</span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( isset( $state['created_count'] ) ) : ?>
								<span title="<?php esc_attr_e( 'Created / Updated / Skipped', 'shopperexpress' ); ?>">
									<span style="color:#1a6130;font-weight:600;"><?php echo esc_html( $state['created_count'] ?? 0 ); ?></span>
									<span style="color:#777;"> / </span>
									<span style="color:#2271b1;"><?php echo esc_html( $state['updated_count'] ?? 0 ); ?></span>
									<span style="color:#777;"> / </span>
									<span style="color:#856404;"><?php echo esc_html( $state['skipped_count'] ?? 0 ); ?></span>
								</span>
								<br><small style="color:#aaa;"><?php esc_html_e( 'C / U / S', 'shopperexpress' ); ?></small>
							<?php else : ?>
								<?php echo esc_html( $state['post_count'] ?? 0 ); ?>
							<?php endif; ?>
						</td>
						<td><span class="soc-badge <?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $badge_label ); ?></span></td>
						<td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-family:monospace;font-size:11px;color:#842029;"
							title="<?php echo esc_attr( $state['error'] ?? '' ); ?>">
							<?php echo esc_html( $state['error'] ?: '—' ); ?>
						</td>
						<td>
							<label class="soc-toggle">
								<input
									type="checkbox"
									class="soc-im-active-toggle"
									data-import-id="<?php echo esc_attr( $id ); ?>"
									<?php checked( ! empty( $cfg['active'] ) ); ?>
								>
								<span class="soc-toggle__slider"></span>
							</label>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<p style="margin-top:12px;color:#777;font-size:12px;">
			<?php esc_html_e( 'Last page load:', 'shopperexpress' ); ?> <?php echo esc_html( current_time( 'Y-m-d H:i:s' ) ); ?>
			&nbsp;|&nbsp;
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=monitor-settings' ) ); ?>"><?php esc_html_e( 'Edit settings →', 'shopperexpress' ); ?></a>
		</p>
	<?php endif; ?>
</div>

<style>
.soc-toggle { position:relative; display:inline-block; width:42px; height:24px; }
.soc-toggle input { opacity:0; width:0; height:0; }
.soc-toggle__slider { position:absolute; cursor:pointer; inset:0; background:#ccc; border-radius:24px; transition:.3s; }
.soc-toggle__slider:before { position:absolute; content:""; height:18px; width:18px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:.3s; }
.soc-toggle input:checked + .soc-toggle__slider { background:#2271b1; }
.soc-toggle input:checked + .soc-toggle__slider:before { transform:translateX(18px); }
</style>

<script>
(function ($) {
	'use strict';

	$(document).on('change', '.soc-im-active-toggle', function () {
		var importId = $(this).data('importId');
		var active   = this.checked ? 1 : 0;

		$.post(socData.ajaxUrl, {
			action    : 'soc_im_toggle',
			nonce     : socData.nonce,
			import_id : importId,
			active    : active,
		}).fail(function () {
			/* revert on failure */
			this.checked = ! this.checked;
		}.bind(this));
	});

}(jQuery));
</script>

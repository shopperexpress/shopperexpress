<?php
/**
 * SOC Cron Manager
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit; ?>

<div id="soc-action-notice" class="soc-notice" role="alert"></div>

<!-- 1. Summary Cards -->
<div class="soc-grid" style="grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); margin-bottom: 24px;">
	<div class="soc-card">
		<div class="soc-card__label"><?php esc_html_e( 'Total Events', 'shopperexpress' ); ?></div>
		<div class="soc-card__value"><?php echo esc_html( $data['total'] ?? 0 ); ?></div>
	</div>

	<div class="soc-card">
		<div class="soc-card__label"><?php esc_html_e( 'Overdue', 'shopperexpress' ); ?></div>
		<div class="soc-card__value" style="<?php echo ( ( $data['overdue_count'] ?? 0 ) > 0 ) ? 'color:#d63638' : ''; ?>">
			<?php echo esc_html( $data['overdue_count'] ?? 0 ); ?>
		</div>
		<?php if ( ( $data['overdue_count'] ?? 0 ) > 0 ) : ?>
			<div class="soc-card__sub"><span class="soc-badge soc-badge--fail"><?php esc_html_e( 'Overdue!', 'shopperexpress' ); ?></span></div>
		<?php endif; ?>
	</div>

	<div class="soc-card">
		<div class="soc-card__label"><?php esc_html_e( 'Action Scheduler', 'shopperexpress' ); ?></div>
		<div class="soc-card__value" style="font-size:14px; padding-top:4px;">
			<?php
			$as_active = ! empty( $data['action_scheduler'] );
			echo $as_active
				? '<span class="soc-badge soc-badge--ok">' . esc_html__( 'Active', 'shopperexpress' ) . '</span>'
				: '<span class="soc-badge soc-badge--neutral">' . esc_html__( 'Inactive', 'shopperexpress' ) . '</span>';
			?>
		</div>
	</div>

	<div class="soc-card">
		<div class="soc-card__label"><?php esc_html_e( 'WP_CRON', 'shopperexpress' ); ?></div>
		<div class="soc-card__value" style="font-size:14px; padding-top:4px;">
			<?php if ( ! empty( $data['wp_cron_disabled'] ) ) : ?>
				<span class="soc-badge soc-badge--warn"><?php esc_html_e( 'Disabled', 'shopperexpress' ); ?></span>
			<?php else : ?>
				<span class="soc-badge soc-badge--ok"><?php esc_html_e( 'Enabled', 'shopperexpress' ); ?></span>
			<?php endif; ?>
		</div>
	</div>
</div>

<!-- 2. Events Table -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Scheduled Events', 'shopperexpress' ); ?></div>

	<?php if ( ! empty( $data['events'] ) ) : ?>
		<table class="soc-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Hook', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Next Run', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Schedule', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Status', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Reschedule', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'shopperexpress' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $data['events'] as $event ) :
					$is_overdue = ! empty( $event['overdue'] );
					$hook       = $event['hook'] ?? '';
					$timestamp  = $event['timestamp'] ?? '';
					$schedule   = $event['schedule'] ?? '';
					$next_run   = $timestamp ? wp_date( 'Y-m-d H:i:s', $timestamp ) : 'N/A';
					?>
					<tr data-hook="<?php echo esc_attr( $hook ); ?>"
						data-timestamp="<?php echo esc_attr( $timestamp ); ?>"
						data-schedule="<?php echo esc_attr( $schedule ); ?>">
						<td><code><?php echo esc_html( $hook ); ?></code></td>
						<td><?php echo esc_html( $next_run ); ?></td>
						<td><?php echo esc_html( $schedule ?: 'Once' ); ?></td>
						<td>
							<?php if ( $is_overdue ) : ?>
								<span class="soc-badge soc-badge--fail"><?php esc_html_e( 'Overdue', 'shopperexpress' ); ?></span>
							<?php else : ?>
								<span class="soc-badge soc-badge--ok"><?php esc_html_e( 'OK', 'shopperexpress' ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<select class="soc-schedule-select">
								<?php foreach ( ( $data['schedules'] ?? array() ) as $key => $sched ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $schedule ); ?>>
										<?php echo esc_html( $sched['display'] ?? $key ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
						<td>
							<div class="soc-actions">
								<button class="button button-primary soc-run-cron"
										data-hook="<?php echo esc_attr( $hook ); ?>"
										data-args="<?php echo esc_attr( wp_json_encode( $event['args'] ?? array() ) ); ?>"
										title="<?php esc_attr_e( 'Run Now', 'shopperexpress' ); ?>">
									<span class="dashicons dashicons-controls-play" aria-hidden="true"></span>
									<span class="screen-reader-text"><?php esc_html_e( 'Run Now', 'shopperexpress' ); ?></span>
								</button>
								<button class="button soc-delete-cron"
										data-hook="<?php echo esc_attr( $hook ); ?>"
										data-timestamp="<?php echo esc_attr( $timestamp ); ?>"
										style="color:#d63638;"
										title="<?php esc_attr_e( 'Delete', 'shopperexpress' ); ?>">
									<span class="dashicons dashicons-trash" aria-hidden="true"></span>
									<span class="screen-reader-text"><?php esc_html_e( 'Delete', 'shopperexpress' ); ?></span>
								</button>
								<button class="button soc-reschedule-cron"
										data-hook="<?php echo esc_attr( $hook ); ?>"
										data-timestamp="<?php echo esc_attr( $timestamp ); ?>"
										title="<?php esc_attr_e( 'Reschedule', 'shopperexpress' ); ?>">
									<span class="dashicons dashicons-backup" aria-hidden="true"></span>
									<span class="screen-reader-text"><?php esc_html_e( 'Reschedule', 'shopperexpress' ); ?></span>
								</button>
			
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p><?php esc_html_e( 'No scheduled cron events found.', 'shopperexpress' ); ?></p>
	<?php endif; ?>
</div>

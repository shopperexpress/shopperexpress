<?php
/**
 * SOC Security Snapshot
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit; ?>

<div id="soc-action-notice" class="soc-notice" role="alert"></div>

<!-- 1. Security Score Cards -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Security Overview', 'shopperexpress' ); ?></div>
	<div class="soc-grid">
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'SSL', 'shopperexpress' ); ?></div>
			<div class="soc-card__value" style="font-size:14px; padding-top:4px;">
				<?php if ( ! empty( $data['ssl_active'] ) ) : ?>
					<span class="soc-badge soc-badge--ok"><?php esc_html_e( 'Active', 'shopperexpress' ); ?></span>
				<?php else : ?>
					<span class="soc-badge soc-badge--fail"><?php esc_html_e( 'Inactive', 'shopperexpress' ); ?></span>
				<?php endif; ?>
			</div>
		</div>

		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'File Editor', 'shopperexpress' ); ?></div>
			<div class="soc-card__value" style="font-size:14px; padding-top:4px;">
				<?php if ( ! empty( $data['file_editor_disabled'] ) ) : ?>
					<span class="soc-badge soc-badge--ok"><?php esc_html_e( 'Disabled', 'shopperexpress' ); ?></span>
				<?php else : ?>
					<span class="soc-badge soc-badge--warn"><?php esc_html_e( 'Enabled', 'shopperexpress' ); ?></span>
				<?php endif; ?>
			</div>
		</div>

		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Debug Mode', 'shopperexpress' ); ?></div>
			<div class="soc-card__value" style="font-size:14px; padding-top:4px;">
				<?php if ( ! empty( $data['debug_mode'] ) ) : ?>
					<span class="soc-badge soc-badge--warn"><?php esc_html_e( 'On — Warning', 'shopperexpress' ); ?></span>
				<?php else : ?>
					<span class="soc-badge soc-badge--ok"><?php esc_html_e( 'Off', 'shopperexpress' ); ?></span>
				<?php endif; ?>
			</div>
		</div>

		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Security Keys', 'shopperexpress' ); ?></div>
			<div class="soc-card__value" style="font-size:14px; padding-top:4px;">
				<?php if ( ! empty( $data['security_keys_set'] ) ) : ?>
					<span class="soc-badge soc-badge--ok"><?php esc_html_e( 'Set', 'shopperexpress' ); ?></span>
				<?php else : ?>
					<span class="soc-badge soc-badge--fail"><?php esc_html_e( 'Not Set', 'shopperexpress' ); ?></span>
				<?php endif; ?>
			</div>
		</div>

		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'REST API Public', 'shopperexpress' ); ?></div>
			<div class="soc-card__value" style="font-size:14px; padding-top:4px;">
				<?php if ( ! empty( $data['rest_api_public'] ) ) : ?>
					<span class="soc-badge soc-badge--warn"><?php esc_html_e( 'Public', 'shopperexpress' ); ?></span>
				<?php else : ?>
					<span class="soc-badge soc-badge--ok"><?php esc_html_e( 'Restricted', 'shopperexpress' ); ?></span>
				<?php endif; ?>
			</div>
		</div>

		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'WP Version', 'shopperexpress' ); ?></div>
			<div class="soc-card__value" style="font-size:14px; padding-top:4px;">
				<?php echo esc_html( $data['wp_version'] ?? 'N/A' ); ?>
				<?php if ( ! empty( $data['wp_version_ok'] ) ) : ?>
					<br><span class="soc-badge soc-badge--ok"><?php esc_html_e( 'Up to Date', 'shopperexpress' ); ?></span>
				<?php else : ?>
					<br><span class="soc-badge soc-badge--warn"><?php esc_html_e( 'Update Available', 'shopperexpress' ); ?></span>
				<?php endif; ?>
			</div>
		</div>

		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Active Plugins', 'shopperexpress' ); ?></div>
			<div class="soc-card__value"><?php echo esc_html( $data['active_plugins_count'] ?? 0 ); ?></div>
		</div>
	</div>
</div>

<!-- 2. Admin Users -->
<div class="soc-section">
	<div class="soc-section__title">
		<?php
		printf(
			/* translators: %d: admin count */
			esc_html__( 'Admin Users (%d)', 'shopperexpress' ),
			(int) ( $data['admin_count'] ?? 0 )
		);
		?>
	</div>

	<?php if ( ! empty( $data['admin_users'] ) ) : ?>
		<table class="soc-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Login', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Email', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Registered', 'shopperexpress' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $data['admin_users'] as $user ) : ?>
					<tr>
						<td><?php echo esc_html( $user['ID'] ?? '' ); ?></td>
						<td><strong><?php echo esc_html( $user['login'] ?? '' ); ?></strong></td>
						<td><?php echo esc_html( $user['email'] ?? '' ); ?></td>
						<td><?php echo esc_html( $user['registered'] ?? '' ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p><?php esc_html_e( 'No admin users found.', 'shopperexpress' ); ?></p>
	<?php endif; ?>
</div>

<!-- 3. Recent Failed Logins -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Recent Failed Logins', 'shopperexpress' ); ?></div>

	<?php if ( ! empty( $data['recent_failed_logins'] ) ) : ?>
		<table class="soc-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Username', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'IP Address', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Time', 'shopperexpress' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $data['recent_failed_logins'] as $login ) : ?>
					<tr>
						<td><?php echo esc_html( $login['username'] ?? '' ); ?></td>
						<td><?php echo esc_html( $login['ip'] ?? '' ); ?></td>
						<td><?php echo esc_html( $login['time'] ?? '' ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p><?php esc_html_e( 'No recent failed login attempts recorded.', 'shopperexpress' ); ?></p>
	<?php endif; ?>
</div>

<?php
/**
 * SOC Maintenance
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit;
$is_active = ! empty( $data['active'] );
?>

<div id="soc-action-notice" class="soc-notice" role="alert"></div>

<!-- 1. Status Card -->
<div class="soc-maintenance-toggle">
	<div class="soc-status-indicator <?php echo $is_active ? 'is-active' : ''; ?>"></div>
	<div class="soc-status-label">
		<?php
		echo $is_active
			? '<span style="color:#00a32a;">' . esc_html__( 'Maintenance Mode: ON', 'shopperexpress' ) . '</span>'
			: '<span style="color:#8c8f94;">' . esc_html__( 'Maintenance Mode: OFF', 'shopperexpress' ) . '</span>';
		?>
	</div>
	<?php if ( $is_active && ! empty( $data['enabled_since'] ) ) : ?>
		<div style="font-size:12px; color:#8c8f94; margin-left:auto;">
			<?php
			printf(
				/* translators: %s: timestamp */
				esc_html__( 'Enabled since: %s', 'shopperexpress' ),
				esc_html( $data['enabled_since'] )
			);
			?>
		</div>
	<?php endif; ?>
</div>

<!-- 2. Form -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Maintenance Settings', 'shopperexpress' ); ?></div>

	<div style="margin-bottom: 16px;">
		<label for="soc-whitelist-ips" style="display:block; font-weight:600; margin-bottom:6px;">
			<?php esc_html_e( 'Whitelisted IP Addresses (one per line)', 'shopperexpress' ); ?>
		</label>
		<textarea
			id="soc-whitelist-ips"
			rows="6"
			style="width:100%; max-width:400px; font-family:monospace; font-size:13px;"
			placeholder="192.168.1.1&#10;10.0.0.1"
		>
		<?php
		if ( ! empty( $data['whitelist_ips'] ) ) {
			echo esc_textarea( implode( "\n", (array) $data['whitelist_ips'] ) );
		}
		?>
		</textarea>
	</div>

	<div class="soc-action-bar">
		<button id="soc-enable-maintenance" class="button button-primary">
			<?php esc_html_e( 'Save &amp; Enable', 'shopperexpress' ); ?>
		</button>
		<button id="soc-disable-maintenance" class="button" <?php echo ! $is_active ? 'disabled' : ''; ?>>
			<?php esc_html_e( 'Disable', 'shopperexpress' ); ?>
		</button>
	</div>
</div>

<!-- Warning -->
<div class="soc-notice soc-notice--warn is-visible" style="display:block; background:#fcf0d0; color:#795a00; border-left:4px solid #dba617;">
	<strong><?php esc_html_e( 'Note:', 'shopperexpress' ); ?></strong>
	<?php esc_html_e( 'Administrators are always exempt from maintenance mode.', 'shopperexpress' ); ?>
</div>

<?php
/**
 * SOC General Settings view
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit;

$guard_enabled = isset( $data['guard_enabled'] ) ? (bool) $data['guard_enabled'] : true;
?>

<div id="soc-action-notice" class="soc-notice" role="alert"></div>

<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Front-end Behavior', 'shopperexpress' ); ?></div>

	<div class="soc-toggle-list">
		<?php
		$soc_row = array(
			'id'       => 'soc-intice-guard-toggle',
			'title'    => __( 'Intice Tool Guard', 'shopperexpress' ),
			'on'       => $guard_enabled,
			'note_on'  => __( '"Temporarily unavailable" popup if a tool fails to load', 'shopperexpress' ),
			'note_off' => __( 'Raw onclick, no popup', 'shopperexpress' ),
			'desc_on'  => __( 'Intice shopping-tool buttons (Leadmaker, Trademaker, Dealmaker, Loanmaker, …) wait for inticeAllEvents to load and show a "This tool is temporarily unavailable" popup instead of a silent dead button if it never does.', 'shopperexpress' ),
			'desc_off' => __( 'Intice shopping-tool buttons run their raw inline onclick handler directly, with no wait and no "temporarily unavailable" popup. If inticeAllEvents has not loaded yet (e.g. blocked by a consent tool), the button will fail silently.', 'shopperexpress' ),
		);
		require __DIR__ . '/partials/toggle-row.php';
		?>
	</div>
</div>

<?php
/**
 * SOC AI VDP Log View
 *
 * @package Shopperexpress
 *
 * Available variables:
 *   $data['logs']   array  {rows, total, per_page, page}
 *   $data['stats']  array  {total_24h, success_24h, error_24h, total_7d, error_7d}
 */

defined( 'ABSPATH' ) || exit;

$stats = $data['stats'] ?? array();
$logs  = $data['logs']  ?? array( 'rows' => array(), 'total' => 0 );
?>

<div id="soc-ai-vdp-notice" class="soc-notice" role="alert"></div>

<!-- Stats -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Statistics', 'shopperexpress' ); ?></div>
	<div class="soc-grid soc-grid--4">

		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Total (24h)', 'shopperexpress' ); ?></div>
			<div class="soc-card__value"><?php echo (int) ( $stats['total_24h'] ?? 0 ); ?></div>
		</div>

		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Success (24h)', 'shopperexpress' ); ?></div>
			<div class="soc-card__value soc-card__value--ok"><?php echo (int) ( $stats['success_24h'] ?? 0 ); ?></div>
		</div>

		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Errors (24h)', 'shopperexpress' ); ?></div>
			<div class="soc-card__value soc-card__value--fail"><?php echo (int) ( $stats['error_24h'] ?? 0 ); ?></div>
		</div>

		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Total (7d)', 'shopperexpress' ); ?></div>
			<div class="soc-card__value"><?php echo (int) ( $stats['total_7d'] ?? 0 ); ?></div>
		</div>

		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Errors (7d)', 'shopperexpress' ); ?></div>
			<div class="soc-card__value soc-card__value--fail"><?php echo (int) ( $stats['error_7d'] ?? 0 ); ?></div>
		</div>

	</div>
</div>

<!-- Log table -->
<div class="soc-section">
	<div class="soc-section__title">
		<?php esc_html_e( 'Generation Log', 'shopperexpress' ); ?>
	</div>

	<div class="soc-lead-filter" style="margin-bottom:12px;">
		<label><?php esc_html_e( 'Filter:', 'shopperexpress' ); ?></label>
		<select id="soc-ai-vdp-filter-status">
			<option value="all"><?php esc_html_e( 'All', 'shopperexpress' ); ?></option>
			<option value="success"><?php esc_html_e( 'Success', 'shopperexpress' ); ?></option>
			<option value="error"><?php esc_html_e( 'Error', 'shopperexpress' ); ?></option>
		</select>
	</div>

	<div id="soc-ai-vdp-table-wrap">
		<?php require __DIR__ . '/ai-vdp-log-table.php'; ?>
	</div>
</div>

<script>
(function ($) {
	var currentPage   = 1;
	var currentStatus = 'all';
	var nonce         = '<?php echo esc_js( wp_create_nonce( 'soc_nonce' ) ); ?>';

	function loadTable(status, page) {
		currentStatus = status;
		currentPage   = page;

		$.post(ajaxurl, {
			action: 'soc_ai_vdp_log_filter',
			nonce:  nonce,
			status: status,
			page:   page
		}, function (res) {
			if (res.success) {
				$('#soc-ai-vdp-table-wrap').html(res.data.html);
			}
		});
	}

	$('#soc-ai-vdp-filter-status').on('change', function () {
		loadTable($(this).val(), 1);
	});

	$(document).on('click', '.soc-ai-vdp-page-btn', function () {
		loadTable(currentStatus, parseInt($(this).data('page'), 10));
	});

}(jQuery));
</script>

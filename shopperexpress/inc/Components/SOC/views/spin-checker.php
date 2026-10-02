<?php
/**
 * SOC 360° Spin Checker
 *
 * Uses socData (ajaxUrl, nonce) already localized by SOC_Assets.
 * AJAX action: soc_spin_check
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit;

$active_provider = $data['active_provider'] ?? '';
$has_api_key     = ! empty( $data['has_api_key'] );
$has_client_id   = ! empty( $data['has_client_id'] );

$provider_labels = array(
	'impel'       => 'Impel (Spincar)',
	'autoexact'   => 'AutoExact (360booth)',
	'lesa'        => 'LESA',
	'dealerimage' => 'DealerImage',
	'autoport'    => 'Autoport',
);
?>

<div id="soc-action-notice" class="soc-notice" role="alert"></div>

<!-- 360° Spin Checker -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( '360° Spin Checker', 'shopperexpress' ); ?></div>
	<p style="margin-top:0; color:#646970;">
		<?php esc_html_e( 'Enter a VIN to check whether 360° spin data exists for it, across every supported spin provider — the same checks the VDP runs client-side, run here server-side so you don\'t need a live listing.', 'shopperexpress' ); ?>
	</p>

	<p style="margin:0 0 16px; color:#646970;">
		<?php esc_html_e( 'Active provider for this site (Theme Options → Spin Data Provider):', 'shopperexpress' ); ?>
		<strong><?php echo esc_html( $active_provider ?: '— none set —' ); ?></strong>
		&mdash;
		<?php if ( $has_api_key && $has_client_id ) : ?>
			<span style="color:#00a32a;"><?php esc_html_e( 'API key & Client ID configured', 'shopperexpress' ); ?></span>
		<?php else : ?>
			<span style="color:#d63638;"><?php esc_html_e( 'API key and/or Client ID missing — Impel/DealerImage checks will report as not configured.', 'shopperexpress' ); ?></span>
		<?php endif; ?>
	</p>

	<div class="soc-action-bar">
		<input
			id="soc-spin-vin-input"
			type="text"
			class="regular-text"
			maxlength="17"
			placeholder="<?php esc_attr_e( 'e.g. 1HGCM82633A004352', 'shopperexpress' ); ?>"
			autocomplete="off"
			spellcheck="false"
			style="font-family:monospace; letter-spacing:.05em; text-transform:uppercase;"
		/>
		<button id="soc-spin-check-btn" class="button button-primary">
			<?php esc_html_e( 'Check All Providers', 'shopperexpress' ); ?>
		</button>
	</div>

	<div id="soc-spin-spinner" style="display:none; align-items:center; gap:8px; color:#555; margin-bottom:12px;">
		<span class="spinner is-active" style="float:none; margin:0;"></span>
		<span><?php esc_html_e( 'Checking providers…', 'shopperexpress' ); ?></span>
	</div>

	<div id="soc-spin-results"></div>
</div>

<style>
.soc-spin-grid { display:flex; flex-direction:column; gap:8px; }
.soc-spin-row {
	display:flex;
	align-items:center;
	gap:12px;
	padding:10px 14px;
	border:1px solid #c3c4c7;
	border-radius:4px;
	background:#fff;
}
.soc-spin-row.found { border-left:4px solid #00a32a; }
.soc-spin-row.not-found { border-left:4px solid #d63638; }
.soc-spin-badge {
	display:inline-flex;
	align-items:center;
	justify-content:center;
	min-width:80px;
	padding:3px 10px;
	border-radius:3px;
	font-size:12px;
	font-weight:600;
	text-align:center;
}
.soc-spin-badge.found { background:#edfaef; color:#00a32a; }
.soc-spin-badge.not-found { background:#fcf0f1; color:#d63638; }
.soc-spin-provider { font-weight:600; min-width:150px; }
.soc-spin-message { color:#646970; flex:1; }
.soc-spin-url { font-size:11px; color:#888; word-break:break-all; display:block; margin-top:4px; }
.soc-spin-code { font-size:11px; color:#888; min-width:50px; text-align:right; }
</style>

<script>
(function ($) {
	'use strict';

	var VIN_RE = /^[A-HJ-NPR-Z0-9]{17}$/i;

	var $input   = $('#soc-spin-vin-input');
	var $btn     = $('#soc-spin-check-btn');
	var $spinner = $('#soc-spin-spinner');
	var $results = $('#soc-spin-results');

	var providerLabels = <?php echo wp_json_encode( $provider_labels ); ?>;

	var i18n =
	<?php
	echo wp_json_encode(
		array(
			'checking'  => __( 'Checking…', 'shopperexpress' ),
			'checkAll'  => __( 'Check All Providers', 'shopperexpress' ),
			'apiError'  => __( 'Request failed. Please try again.', 'shopperexpress' ),
			'vinEmpty'  => __( 'Please enter a VIN.', 'shopperexpress' ),
			'vinFormat' => __( 'VIN must be exactly 17 alphanumeric characters (I, O and Q are not valid).', 'shopperexpress' ),
		)
	);
	?>
	;

	function showNotice(type, msg) {
		$results.html(
			'<div class="notice notice-' + type + ' inline"><p>' +
			$('<span>').text(msg).html() +
			'</p></div>'
		);
	}

	function esc(str) {
		return $('<span>').text(str == null ? '' : str).html();
	}

	function renderResults(vin, rows) {
		var html = '<div class="soc-spin-result-header" style="margin-bottom:10px;">' +
			'<strong>' + esc(vin) + '</strong>' +
			'</div><div class="soc-spin-grid">';

		rows.forEach(function (row) {
			var label  = providerLabels[row.provider] || row.provider;
			var status = row.found ? 'found' : 'not-found';
			var badge  = row.found ? 'Available' : 'Not found';

			html += '<div class="soc-spin-row ' + status + '">' +
				'<span class="soc-spin-provider">' + esc(label) + '</span>' +
				'<span class="soc-spin-badge ' + status + '">' + badge + '</span>' +
				'<span class="soc-spin-message">' + esc(row.message) +
					(row.url ? '<span class="soc-spin-url">' + esc(row.url) + '</span>' : '') +
				'</span>' +
				'<span class="soc-spin-code">' + (row.code ? 'HTTP ' + esc(row.code) : '') + '</span>' +
				'</div>';
		});

		html += '</div>';
		$results.html(html);
	}

	function doCheck(vin) {
		vin = vin.toUpperCase().trim();
		if (!vin) { showNotice('warning', i18n.vinEmpty); return; }
		if (!VIN_RE.test(vin)) { showNotice('warning', i18n.vinFormat); return; }

		$input.val(vin);
		$btn.prop('disabled', true).text(i18n.checking);
		$spinner.css('display', 'flex');
		$results.empty();

		$.post(socData.ajaxUrl, {
			action : 'soc_spin_check',
			nonce  : socData.nonce,
			vin    : vin,
		})
		.done(function (resp) {
			if (!resp.success) {
				showNotice('error', resp.data && resp.data.message ? resp.data.message : i18n.apiError);
				return;
			}
			renderResults(resp.data.vin, resp.data.results);
		})
		.fail(function () { showNotice('error', i18n.apiError); })
		.always(function () {
			$btn.prop('disabled', false).text(i18n.checkAll);
			$spinner.hide();
		});
	}

	$btn.on('click', function () { doCheck($input.val()); });
	$input.on('keydown', function (e) { if (e.key === 'Enter') doCheck($input.val()); });
	$input.on('input', function () {
		var pos = this.selectionStart;
		this.value = this.value.toUpperCase();
		this.setSelectionRange(pos, pos);
	});

}(jQuery));
</script>

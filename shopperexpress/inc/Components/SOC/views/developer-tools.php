<?php
/**
 * SOC Developer Tools — VIN Checker
 *
 * Uses socData (ajaxUrl, nonce) already localized by SOC_Assets.
 * AJAX actions: soc_vin_lookup, soc_vin_clear_history, soc_vin_run_background, soc_vin_poll
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit;

$vin_history         = $data['vin_history'] ?? array();
$shell_exec_disabled = $data['shell_exec_disabled'] ?? true;
?>

<div id="soc-action-notice" class="soc-notice" role="alert"></div>

<!-- VIN Checker -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'VIN Checker', 'shopperexpress' ); ?></div>
	<p style="margin-top:0; color:#646970;">
		<?php esc_html_e( 'Enter a 17-character VIN to decode vehicle data via the Chromedata API.', 'shopperexpress' ); ?>
	</p>

	<div class="soc-action-bar">
		<input
			id="soc-vin-input"
			type="text"
			class="regular-text"
			maxlength="17"
			placeholder="<?php esc_attr_e( 'e.g. 1HGCM82633A004352', 'shopperexpress' ); ?>"
			autocomplete="off"
			spellcheck="false"
			style="font-family:monospace; letter-spacing:.05em; text-transform:uppercase;"
		/>
		<button id="soc-vin-btn" class="button button-primary">
			<?php esc_html_e( 'Check VIN', 'shopperexpress' ); ?>
		</button>
		<?php if ( ! empty( $vin_history ) ) : ?>
		<button id="soc-vin-clear-history" class="button button-link-delete">
			<?php esc_html_e( 'Clear History', 'shopperexpress' ); ?>
		</button>
		<?php endif; ?>
	</div>

	<div id="soc-vin-spinner" style="display:none; display:flex; align-items:center; gap:8px; color:#555; margin-bottom:12px;">
		<span class="spinner is-active" style="float:none; margin:0;"></span>
		<span><?php esc_html_e( 'Contacting Chromedata API…', 'shopperexpress' ); ?></span>
	</div>

	<?php if ( ! $shell_exec_disabled ) : ?>
	<div id="soc-vin-bg-row" style="display:none; margin-bottom:12px;">
		<button id="soc-vin-bg-btn" class="button button-secondary">
			<?php esc_html_e( 'Run in Background', 'shopperexpress' ); ?>
		</button>
		<span id="soc-vin-bg-status" style="margin-left:10px; font-style:italic; color:#555;"></span>
	</div>
	<?php endif; ?>

	<div id="soc-vin-results"></div>
</div>

<!-- VIN History -->
<?php if ( ! empty( $vin_history ) ) : ?>
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Recent VINs', 'shopperexpress' ); ?></div>
	<ul id="soc-vin-history-list" style="margin:0; list-style:none; display:flex; flex-wrap:wrap; gap:6px;">
		<?php foreach ( $vin_history as $entry ) : ?>
		<li style="display:flex; align-items:center; gap:6px; background:#f6f7f7; border:1px solid #c3c4c7; border-radius:3px; padding:4px 10px;">
			<button
				class="button-link soc-vin-history-item"
				data-vin="<?php echo esc_attr( $entry['vin'] ); ?>"
				style="font-family:monospace; font-size:13px; font-weight:600;"
			><?php echo esc_html( $entry['vin'] ); ?></button>
			<span style="font-size:11px; color:#888;">
				<?php echo esc_html( $entry['label'] ); ?>
				&mdash;
				<time><?php echo esc_html( $entry['time'] ); ?></time>
			</span>
		</li>
		<?php endforeach; ?>
	</ul>
</div>
<?php endif; ?>

<style>
#soc-vin-results .notice { margin:0 0 12px; }
.soc-vin-result-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
.soc-vin-result-header h3 { margin:0; }
.soc-vin-result-json { background:#1e1e1e; color:#d4d4d4; padding:16px; border-radius:4px; overflow:auto; max-height:600px; font-size:13px; line-height:1.6; white-space:pre; }
.soc-vin-cached { font-size:11px; color:#888; margin-left:8px; }
</style>

<script>
(function ($) {
	'use strict';

	var VIN_RE = /^[A-HJ-NPR-Z0-9]{17}$/i;

	var $input   = $('#soc-vin-input');
	var $btn     = $('#soc-vin-btn');
	var $spinner = $('#soc-vin-spinner');
	var $results = $('#soc-vin-results');
	var $bgRow   = $('#soc-vin-bg-row');
	var $bgBtn   = $('#soc-vin-bg-btn');
	var $bgStatus= $('#soc-vin-bg-status');
	var pollTimer= null;

	var i18n = 
	<?php
	echo wp_json_encode(
		array(
			'checking'  => __( 'Checking…', 'shopperexpress' ),
			'checkVin'  => __( 'Check VIN', 'shopperexpress' ),
			'noData'    => __( 'No data found for this VIN.', 'shopperexpress' ),
			'apiError'  => __( 'API request failed. Please try again.', 'shopperexpress' ),
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

	function doLookup(vin) {
		vin = vin.toUpperCase().trim();
		if (!vin) { showNotice('warning', i18n.vinEmpty); return; }
		if (!VIN_RE.test(vin)) { showNotice('warning', i18n.vinFormat); return; }

		$input.val(vin);
		$btn.prop('disabled', true).text(i18n.checking);
		$spinner.show();
		$results.empty();

		$.post(socData.ajaxUrl, {
			action : 'soc_vin_lookup',
			nonce  : socData.nonce,
			vin    : vin,
		})
		.done(function (resp) {
			if (!resp.success) {
				showNotice('error', resp.data && resp.data.message ? resp.data.message : i18n.apiError);
				return;
			}
			var d = resp.data;
			if (!d.hasData) {
				showNotice('warning', i18n.noData);
				return;
			}
			var cachedBadge = d.cached ? '<span class="soc-vin-cached">(cached)</span>' : '';
			var jsonStr     = JSON.stringify(d.features, null, 2);
			$results.html(
				'<div class="soc-vin-result-header">' +
					'<h3>' + $('<span>').text(d.vin).html() + cachedBadge + '</h3>' +
				'</div>' +
				'<div class="notice notice-success inline"><p>VIN decoded successfully.</p></div>' +
				'<pre class="soc-vin-result-json">' + $('<span>').text(jsonStr).html() + '</pre>'
			);
			$bgRow.show();
			$bgStatus.text('').css('color', '');
			if (!d.cached) { location.reload(); }
		})
		.fail(function () { showNotice('error', i18n.apiError); })
		.always(function () {
			$btn.prop('disabled', false).text(i18n.checkVin);
			$spinner.hide();
		});
	}

	$btn.on('click', function () { doLookup($input.val()); });
	$input.on('keydown', function (e) { if (e.key === 'Enter') doLookup($input.val()); });
	$input.on('input', function () {
		var pos = this.selectionStart;
		this.value = this.value.toUpperCase();
		this.setSelectionRange(pos, pos);
	});

	$(document).on('click', '.soc-vin-history-item', function () {
		doLookup($(this).data('vin'));
		$('html,body').animate({ scrollTop: $input.offset().top - 32 }, 150);
		$input.trigger('focus');
	});

	$('#soc-vin-clear-history').on('click', function () {
		$.post(socData.ajaxUrl, {
			action : 'soc_vin_clear_history',
			nonce  : socData.nonce,
		}).always(function () { location.reload(); });
	});

	function stopPolling() {
		if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
	}

	function startPolling(postId) {
		var attempts = 0;
		stopPolling();
		$bgStatus.css('color', '#555').text('Populating features_items… (post #' + postId + ')');
		pollTimer = setInterval(function () {
			attempts++;
			$.post(socData.ajaxUrl, {
				action  : 'soc_vin_poll',
				nonce   : socData.nonce,
				post_id : postId,
			})
			.done(function (resp) {
				if (!resp.success) return;
				if (resp.data.populated) {
					stopPolling();
					$bgStatus.css('color', '#00a32a').text(
						'Done — ' + resp.data.count + ' section(s) saved to ' + resp.data.field + ' (post #' + postId + ').'
					);
					$bgBtn.prop('disabled', false);
				} else if (attempts >= 30) {
					stopPolling();
					$bgStatus.css('color', '#d63638').text('Timed out waiting for features_items to populate.');
					$bgBtn.prop('disabled', false);
				}
			})
			.fail(function () {
				stopPolling();
				$bgStatus.css('color', '#d63638').text('Polling request failed.');
				$bgBtn.prop('disabled', false);
			});
		}, 3000);
	}

	$bgBtn.on('click', function () {
		var vin = $input.val().toUpperCase().trim();
		if (!vin) return;
		stopPolling();
		$bgBtn.prop('disabled', true);
		$bgStatus.css('color', '#555').text('Dispatching…');
		$.post(socData.ajaxUrl, {
			action : 'soc_vin_run_background',
			nonce  : socData.nonce,
			vin    : vin,
		})
		.done(function (resp) {
			if (resp.success) {
				$bgStatus.css('color', '#555').text(resp.data.message);
				startPolling(resp.data.post_id);
			} else {
				$bgStatus.css('color', '#d63638').text(resp.data && resp.data.message ? resp.data.message : 'Error.');
				$bgBtn.prop('disabled', false);
			}
		})
		.fail(function () {
			$bgStatus.css('color', '#d63638').text('Request failed.');
			$bgBtn.prop('disabled', false);
		});
	});

}(jQuery));
</script>

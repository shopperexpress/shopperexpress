<?php
/**
 * SOC Lead Delivery View
 *
 * @package Shopperexpress
 *
 * Available variables:
 *   $data['delivery_method']    string  'email'|'api'|'both'
 *   $data['api_endpoint']       string
 *   $data['api_configured']     bool
 *   $data['fallback_email']     bool
 *   $data['timeout']            int
 *   $data['secret_key_masked']  string  masked value or ''
 *   $data['site_name']          string
 *   $data['notify_admin']       bool
 *   $data['notify_email']       string
 *   $data['max_retries']        int
 *   $data['dedup_minutes']      int
 *   $data['wpforms_form_ids']   string  comma-separated WP Forms IDs
 *   $data['logs']               array   {rows, total, per_page, page}
 *   $data['stats']              array   {total_24h, success_24h, failed_24h, total_7d, failed_7d}
 */

defined( 'ABSPATH' ) || exit;

$method_val = $data['delivery_method'] ?? 'email';
$show_api   = in_array( $method_val, array( 'api', 'both' ), true );
$is_api     = 'api' === $method_val;
$stats      = $data['stats'] ?? array();
$logs      = $data['logs']  ?? array( 'rows' => array(), 'total' => 0 );
?>

<div id="soc-lead-notice" class="soc-notice" role="alert"></div>

<!-- =====================================================================
     1. Delivery Settings
     ===================================================================== -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Delivery Settings', 'shopperexpress' ); ?></div>

	<div class="soc-grid soc-grid--2">

		<!-- Method selector -->
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Delivery Method', 'shopperexpress' ); ?></div>
			<div class="soc-card__value">
				<label class="soc-lead-radio">
					<input type="radio" name="adf_delivery_method" value="email" <?php checked( 'email' === $method_val ); ?>>
					<?php esc_html_e( 'Email', 'shopperexpress' ); ?>
				</label>
				<label class="soc-lead-radio">
					<input type="radio" name="adf_delivery_method" value="api" <?php checked( 'api' === $method_val ); ?>>
					<?php esc_html_e( 'API', 'shopperexpress' ); ?>
				</label>
				<label class="soc-lead-radio">
					<input type="radio" name="adf_delivery_method" value="both" <?php checked( 'both' === $method_val ); ?>>
					<?php esc_html_e( 'Both', 'shopperexpress' ); ?>
				</label>
			</div>
		</div>

		<!-- API status badge -->
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'API Status', 'shopperexpress' ); ?></div>
			<div class="soc-card__value">
				<?php if ( $data['api_configured'] ) : ?>
					<span class="soc-badge soc-badge--ok"><?php esc_html_e( 'Configured', 'shopperexpress' ); ?></span>
				<?php else : ?>
					<span class="soc-badge soc-badge--warn"><?php esc_html_e( 'Not configured', 'shopperexpress' ); ?></span>
				<?php endif; ?>
			</div>
		</div>

	</div><!-- .soc-grid -->

	<table class="soc-table soc-lead-settings-table">
		<tbody>

			<!-- Site / Dealer name -->
			<tr>
				<th><?php esc_html_e( 'Site / Dealer Name', 'shopperexpress' ); ?></th>
				<td>
					<input type="text"
						id="soc-lead-site-name"
						class="regular-text"
						value="<?php echo esc_attr( $data['site_name'] ?? get_bloginfo( 'name' ) ); ?>"
						placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
					<p class="description"><?php esc_html_e( 'Stored in every lead log row as the dealer/site identifier.', 'shopperexpress' ); ?></p>
				</td>
			</tr>

			<!-- API credentials (shown when API is selected) -->
			<tr class="soc-lead-api-row" <?php echo $show_api ? '' : 'style="display:none"'; ?>>
				<th><?php esc_html_e( 'API Endpoint URL', 'shopperexpress' ); ?></th>
				<td>
					<input type="url"
						id="soc-lead-endpoint"
						class="regular-text"
						value="<?php echo esc_attr( $data['api_endpoint'] ?? '' ); ?>"
						placeholder="https://api.intice.io/v1/leads">
				</td>
			</tr>
			<tr class="soc-lead-api-row" <?php echo $show_api ? '' : 'style="display:none"'; ?>>
				<th><?php esc_html_e( 'Dealer ID', 'shopperexpress' ); ?></th>
				<td>
					<input type="text"
						id="soc-lead-dealer-id"
						class="regular-text"
						value="<?php echo esc_attr( $data['dealer_id'] ?? '' ); ?>"
						placeholder="660">
					<p class="description"><?php esc_html_e( 'Included as dealer_id in every ADF-XML API payload.', 'shopperexpress' ); ?></p>
				</td>
			</tr>
			<tr class="soc-lead-api-row" <?php echo $show_api ? '' : 'style="display:none"'; ?>>
				<th><?php esc_html_e( 'API Key (X-API-Key)', 'shopperexpress' ); ?></th>
				<td>
					<span id="soc-lead-key-masked" class="soc-lead-key-display">
						<?php echo esc_html( '' !== ( $data['secret_key_masked'] ?? '' ) ? $data['secret_key_masked'] : __( '(not set)', 'shopperexpress' ) ); ?>
					</span>
					<input type="password"
						id="soc-lead-key-input"
						class="regular-text"
						placeholder="<?php esc_attr_e( 'Enter new key to replace…', 'shopperexpress' ); ?>"
						style="display:none">
					<button type="button" class="button" id="soc-lead-key-edit">
						<?php esc_html_e( 'Change Key', 'shopperexpress' ); ?>
					</button>
				</td>
			</tr>
			<tr class="soc-lead-api-row" <?php echo $show_api ? '' : 'style="display:none"'; ?>>
				<th><?php esc_html_e( 'Request Timeout (s)', 'shopperexpress' ); ?></th>
				<td>
					<input type="number" id="soc-lead-timeout" class="small-text"
						value="<?php echo esc_attr( $data['timeout'] ?? 10 ); ?>" min="5" max="60">
				</td>
			</tr>
			<tr class="soc-lead-api-only-row" <?php echo $is_api ? '' : 'style="display:none"'; ?>>
				<th><?php esc_html_e( 'Fallback to Email on Failure', 'shopperexpress' ); ?></th>
				<td>
					<label class="soc-toggle">
						<input type="checkbox" id="soc-lead-fallback" <?php checked( $data['fallback_email'] ?? false ); ?>>
						<span class="soc-toggle__slider"></span>
					</label>
				</td>
			</tr>
			<tr class="soc-lead-api-row" <?php echo $show_api ? '' : 'style="display:none"'; ?>>
				<th><?php esc_html_e( 'WP Forms — ADF Form IDs', 'shopperexpress' ); ?></th>
				<td>
					<input type="text"
						id="soc-lead-wpforms-ids"
						class="regular-text"
						value="<?php echo esc_attr( $data['wpforms_form_ids'] ?? '' ); ?>"
						placeholder="12, 45, Contact Us">
					<p class="description"><?php esc_html_e( 'Comma-separated WP Forms IDs and/or exact form names allowed to send leads via the API when Delivery Method is API or Both. Matches by form_id if the webhook sends one, otherwise by Form_Name (already sent by every existing webhook) — no webhook reconfiguration required. Leave empty to allow all forms (no restriction). Forms not listed here still deliver via email as before; their leads are simply never sent to the API endpoint.', 'shopperexpress' ); ?></p>
				</td>
			</tr>
			<tr class="soc-lead-api-row" <?php echo $show_api ? '' : 'style="display:none"'; ?>>
				<th><?php esc_html_e( 'Auto-Retry Max Attempts', 'shopperexpress' ); ?></th>
				<td>
					<input type="number" id="soc-lead-max-retries" class="small-text"
						value="<?php echo esc_attr( $data['max_retries'] ?? 3 ); ?>" min="0" max="10">
					<p class="description"><?php esc_html_e( 'Set to 0 to disable automatic retry. Retries run every 15 minutes via WP Cron.', 'shopperexpress' ); ?></p>
				</td>
			</tr>

			<!-- Admin notifications -->
			<tr>
				<th><?php esc_html_e( 'Notify Admin on Failure', 'shopperexpress' ); ?></th>
				<td>
					<label class="soc-toggle">
						<input type="checkbox" id="soc-lead-notify-admin" <?php checked( $data['notify_admin'] ?? false ); ?>>
						<span class="soc-toggle__slider"></span>
					</label>
				</td>
			</tr>
			<tr id="soc-lead-notify-email-row" <?php echo ( $data['notify_admin'] ?? false ) ? '' : 'style="display:none"'; ?>>
				<th><?php esc_html_e( 'Notification Email', 'shopperexpress' ); ?></th>
				<td>
					<input type="email" id="soc-lead-notify-email" class="regular-text"
						value="<?php echo esc_attr( $data['notify_email'] ?? get_option( 'admin_email' ) ); ?>"
						placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
				</td>
			</tr>

			<!-- Duplicate prevention -->
			<tr>
				<th><?php esc_html_e( 'Duplicate Prevention Window (min)', 'shopperexpress' ); ?></th>
				<td>
					<input type="number" id="soc-lead-dedup" class="small-text"
						value="<?php echo esc_attr( $data['dedup_minutes'] ?? 0 ); ?>" min="0" max="1440">
					<p class="description"><?php esc_html_e( 'Block re-submission of the same email+phone within this many minutes. 0 = disabled.', 'shopperexpress' ); ?></p>
				</td>
			</tr>

		</tbody>
	</table>

	<div class="soc-lead-settings-actions">
		<button type="button" class="button button-primary" id="soc-lead-save-settings">
			<?php esc_html_e( 'Save Settings', 'shopperexpress' ); ?>
		</button>
		<button type="button" class="button soc-lead-api-row" id="soc-lead-test-connection"
			<?php echo $show_api ? '' : 'style="display:none"'; ?>>
			<?php esc_html_e( 'Test Connection', 'shopperexpress' ); ?>
		</button>
		<span id="soc-lead-test-result" class="soc-lead-test-result"></span>
	</div>

</div><!-- .soc-section -->

<!-- =====================================================================
     2. Stats Cards
     ===================================================================== -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Delivery Stats', 'shopperexpress' ); ?></div>
	<div class="soc-grid soc-grid--5">

		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Last 24h', 'shopperexpress' ); ?></div>
			<div class="soc-card__value"><?php echo (int) ( $stats['total_24h'] ?? 0 ); ?></div>
		</div>

		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Success 24h', 'shopperexpress' ); ?></div>
			<div class="soc-card__value soc-lead-stat--success"><?php echo (int) ( $stats['success_24h'] ?? 0 ); ?></div>
		</div>

		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Failed 24h', 'shopperexpress' ); ?></div>
			<div class="soc-card__value <?php echo ( $stats['failed_24h'] ?? 0 ) > 0 ? 'soc-lead-stat--failed' : ''; ?>">
				<?php echo (int) ( $stats['failed_24h'] ?? 0 ); ?>
			</div>
		</div>

		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Last 7 days', 'shopperexpress' ); ?></div>
			<div class="soc-card__value"><?php echo (int) ( $stats['total_7d'] ?? 0 ); ?></div>
		</div>

		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Failed 7 days', 'shopperexpress' ); ?></div>
			<div class="soc-card__value <?php echo ( $stats['failed_7d'] ?? 0 ) > 0 ? 'soc-lead-stat--failed' : ''; ?>">
				<?php echo (int) ( $stats['failed_7d'] ?? 0 ); ?>
			</div>
		</div>

	</div>
</div>

<!-- =====================================================================
     3. Lead Log
     ===================================================================== -->
<div class="soc-section">
	<div class="soc-section__title soc-lead-log-header">
		<?php esc_html_e( 'Lead Log', 'shopperexpress' ); ?>

		<div class="soc-lead-log-filters">
			<input type="text"
				id="soc-lead-filter-search"
				class="regular-text"
				placeholder="<?php esc_attr_e( 'Search email, name or phone…', 'shopperexpress' ); ?>">
			<select id="soc-lead-filter-status">
				<option value="all"><?php esc_html_e( 'All statuses', 'shopperexpress' ); ?></option>
				<option value="success"><?php esc_html_e( 'Success', 'shopperexpress' ); ?></option>
				<option value="failed"><?php esc_html_e( 'Failed', 'shopperexpress' ); ?></option>
				<option value="pending"><?php esc_html_e( 'Pending', 'shopperexpress' ); ?></option>
			</select>
			<button type="button" class="button" id="soc-lead-filter-btn">
				<?php esc_html_e( 'Filter', 'shopperexpress' ); ?>
			</button>
		</div>
	</div>

	<div id="soc-lead-log-wrap">
		<?php require __DIR__ . '/lead-delivery-table.php'; ?>
	</div>

</div><!-- .soc-section -->

<!-- =====================================================================
     Details Modal
     ===================================================================== -->
<div id="soc-lead-modal" class="soc-lead-modal" role="dialog" aria-modal="true" aria-labelledby="soc-lead-modal-title" style="display:none">
	<div class="soc-lead-modal__backdrop"></div>
	<div class="soc-lead-modal__box">
		<div class="soc-lead-modal__header">
			<h3 id="soc-lead-modal-title"><?php esc_html_e( 'Lead Delivery Details', 'shopperexpress' ); ?></h3>
			<button type="button" class="soc-lead-modal__close" aria-label="<?php esc_attr_e( 'Close', 'shopperexpress' ); ?>">&times;</button>
		</div>
		<div class="soc-lead-modal__body">
			<table class="soc-table">
				<tbody>
					<tr><th><?php esc_html_e( 'Lead', 'shopperexpress' ); ?></th><td id="soc-modal-name"></td></tr>
					<tr><th><?php esc_html_e( 'Submitted', 'shopperexpress' ); ?></th><td id="soc-modal-time"></td></tr>
					<tr><th><?php esc_html_e( 'HTTP Code', 'shopperexpress' ); ?></th><td id="soc-modal-code"></td></tr>
					<tr><th><?php esc_html_e( 'Error', 'shopperexpress' ); ?></th><td id="soc-modal-error"></td></tr>
				</tbody>
			</table>
			<div class="soc-lead-modal__response-wrap">
				<strong><?php esc_html_e( 'ADF-XML Payload Sent', 'shopperexpress' ); ?></strong>
				<pre id="soc-modal-payload" class="soc-log-output"></pre>
			</div>
			<div class="soc-lead-modal__response-wrap">
				<strong><?php esc_html_e( 'Raw API Response', 'shopperexpress' ); ?></strong>
				<pre id="soc-modal-response" class="soc-log-output"></pre>
			</div>
		</div>
	</div>
</div>

<?php
/**
 * VIN Checker Admin Page.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class Vin_Admin
 *
 * Adds a "VIN Checker" page under the Tools menu and handles
 * the AJAX lookup via the existing CallAPI() helper.
 *
 * @package App\Components\Base
 */
class Vin_Admin implements Theme_Component {

	const AJAX_ACTION   = 'vin_checker_lookup';
	const HISTORY_KEY   = 'vin_checker_history';
	const HISTORY_LIMIT = 10;
	const CACHE_TTL     = 3600; // seconds.

	/**
	 * Register hooks — standalone Tools page removed; VIN Checker lives in Operation Center.
	 *
	 * @return void
	 */
	public function register(): void {}

	// -------------------------------------------------------------------------
	// Menu
	// -------------------------------------------------------------------------

	/**
	 * Register the admin menu page under Tools.
	 *
	 * @return void
	 */
	public function add_menu_page(): void {
		add_management_page(
			__( 'VIN Checker', 'shopperexpress' ),
			__( 'VIN Checker', 'shopperexpress' ),
			'manage_options',
			'vin-checker',
			array( $this, 'render_page' )
		);
	}

	// -------------------------------------------------------------------------
	// Assets
	// -------------------------------------------------------------------------

	/**
	 * Enqueue inline styles only on our admin page.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'tools_page_vin-checker' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_add_inline_style( 'wp-color-picker', $this->inline_styles() );
	}

	// -------------------------------------------------------------------------
	// Page render
	// -------------------------------------------------------------------------

	/**
	 * Render the admin page HTML.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'shopperexpress' ) );
		}

		$history = $this->get_history();
		?>
		<div class="wrap vin-checker-wrap">
			<h1><?php esc_html_e( 'VIN Checker', 'shopperexpress' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Enter a 17-character VIN to decode vehicle data via the Chromedata API.', 'shopperexpress' ); ?>
			</p>

			<div class="vin-checker-form card">
				<div class="vin-checker-input-row">
					<input
						id="vin-checker-input"
						type="text"
						class="regular-text"
						maxlength="17"
						placeholder="<?php esc_attr_e( 'e.g. 1HGCM82633A004352', 'shopperexpress' ); ?>"
						autocomplete="off"
						spellcheck="false"
					/>
					<button id="vin-checker-btn" class="button button-primary">
						<?php esc_html_e( 'Check VIN', 'shopperexpress' ); ?>
					</button>
					<?php if ( ! empty( $history ) ) : ?>
					<button id="vin-checker-clear-history" class="button button-link-delete">
						<?php esc_html_e( 'Clear History', 'shopperexpress' ); ?>
					</button>
					<?php endif; ?>
				</div>

				<div id="vin-checker-spinner" class="vin-checker-spinner" style="display:none;">
					<span class="spinner is-active"></span>
					<span><?php esc_html_e( 'Contacting Chromedata API…', 'shopperexpress' ); ?></span>
				</div>

				<div id="vin-checker-bg-row" style="display:none; margin-top:12px;">
					<button id="vin-checker-bg-btn" class="button button-secondary">
						<?php esc_html_e( 'Run in Background', 'shopperexpress' ); ?>
					</button>
					<span id="vin-checker-bg-status" class="vin-checker-bg-status"></span>
				</div>

				<div id="vin-checker-results"></div>
			</div>

			<?php if ( ! empty( $history ) ) : ?>
			<div class="vin-checker-history card">
				<h2><?php esc_html_e( 'Recent VINs', 'shopperexpress' ); ?></h2>
				<ul id="vin-checker-history-list">
					<?php foreach ( $history as $entry ) : ?>
					<li>
						<button
							class="button-link vin-checker-history-item"
							data-vin="<?php echo esc_attr( $entry['vin'] ); ?>"
						><?php echo esc_html( $entry['vin'] ); ?></button>
						<span class="vin-checker-history-meta">
							<?php echo esc_html( $entry['label'] ); ?>
							&mdash;
							<time><?php echo esc_html( $entry['time'] ); ?></time>
						</span>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>
		</div>

		<script>
		var vinChecker =
		<?php
		echo wp_json_encode(
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::AJAX_ACTION ),
				'action'  => self::AJAX_ACTION,
				'i18n'    => array(
					'checking'  => __( 'Checking…', 'shopperexpress' ),
					'checkVin'  => __( 'Check VIN', 'shopperexpress' ),
					'noData'    => __( 'No data found for this VIN.', 'shopperexpress' ),
					'apiError'  => __( 'API request failed. Please try again.', 'shopperexpress' ),
					'vinEmpty'  => __( 'Please enter a VIN.', 'shopperexpress' ),
					'vinFormat' => __( 'VIN must be exactly 17 alphanumeric characters (I, O and Q are not valid).', 'shopperexpress' ),
					'copied'    => __( 'Copied!', 'shopperexpress' ),
				),
			)
		);
		?>
		;
		<?php echo $this->inline_script(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</script>
		<?php
	}

	// -------------------------------------------------------------------------
	// Public service methods (consumed by SOC Developer_Tools module)
	// -------------------------------------------------------------------------

	/**
	 * Perform a VIN lookup via Chromedata API.
	 *
	 * @param string $vin Validated 17-char VIN (uppercase).
	 * @return array|\WP_Error
	 */
	public function lookup_vin( string $vin ) {
		$cache_key = 'vin_check_' . md5( $vin );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			$cached['cached'] = true;
			return $cached;
		}

		$api_url = get_field( 'url_chromedata', 'options' );

		if ( empty( $api_url ) ) {
			return new \WP_Error( 'not_configured', __( 'API is not configured. Please set the Chromedata URL in Theme Options.', 'shopperexpress' ) );
		}

		$response = $this->call_chromedata_api( $api_url, array(
			'VIN'             => $vin,
			'onlyDecodeUsing' => 'V,E,C,S',
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$has_data = ! empty( $response['result']['features'] );

		if ( ! $has_data ) {
			return array( 'hasData' => false, 'vin' => $vin );
		}

		$payload = array(
			'hasData'  => true,
			'vin'      => $vin,
			'features' => $response['result']['features'],
			'cached'   => false,
		);

		set_transient( $cache_key, $payload, self::CACHE_TTL );
		$this->push_history( $vin, $response );

		return $payload;
	}

	/**
	 * Clear the current user's VIN history.
	 *
	 * @return void
	 */
	public function clear_user_history(): void {
		delete_user_meta( get_current_user_id(), self::HISTORY_KEY );
	}

	/**
	 * Dispatch a background Chromedata API call for a VIN.
	 *
	 * @param string $vin Validated VIN (uppercase).
	 * @return array|\WP_Error
	 */
	public function run_background_for_vin( string $vin ) {
		$api_url = get_field( 'url_chromedata', 'options' );

		if ( empty( $api_url ) ) {
			return new \WP_Error( 'not_configured', __( 'Chromedata URL is not configured.', 'shopperexpress' ) );
		}

		if ( ! function_exists( 'run_callapi_in_background' ) ) {
			return new \WP_Error( 'unavailable', __( 'run_callapi_in_background() is not available.', 'shopperexpress' ) );
		}

		if ( $this->is_shell_exec_disabled() ) {
			return new \WP_Error( 'shell_disabled', __( 'shell_exec is disabled on this server (disable_functions).', 'shopperexpress' ) );
		}

		$post_id = $this->find_post_by_vin( $vin );

		if ( ! $post_id ) {
			return new \WP_Error( 'not_found', __( 'No listing found for this VIN in listings or used-listings.', 'shopperexpress' ) );
		}

		run_callapi_in_background( $api_url, array(
			'VIN'             => $vin,
			'onlyDecodeUsing' => 'V,E,C,S',
		), $post_id );

		return array(
			'message' => sprintf(
				/* translators: 1: post ID, 2: post type */
				__( 'Background job dispatched for post #%1$d (%2$s).', 'shopperexpress' ),
				$post_id,
				get_post_type( $post_id )
			),
			'post_id' => $post_id,
		);
	}

	/**
	 * Poll whether features_items has been populated for a given post.
	 *
	 * @param int $post_id Post ID.
	 * @return array { populated: bool, count: int, field: string }
	 */
	public function poll_features( int $post_id ): array {
		$post_type   = get_post_type( $post_id );
		$type_prefix = in_array( $post_type, array( 'finance-offers', 'lease-offers', 'conditional-offers' ), true )
			? $post_type . '_'
			: '';
		$field_key   = $type_prefix . 'features_items';

		$rows  = get_field( $field_key, $post_id );
		$count = is_array( $rows ) ? count( $rows ) : 0;

		return array(
			'populated' => $count > 0,
			'count'     => $count,
			'field'     => $field_key,
		);
	}

	/**
	 * Return the current user's VIN history (newest first).
	 *
	 * @return array<int, array{vin: string, label: string, time: string}>
	 */
	public function get_history(): array {
		$history = get_user_meta( get_current_user_id(), self::HISTORY_KEY, true );
		return is_array( $history ) ? $history : array();
	}

	/**
	 * Check whether shell_exec is actually usable.
	 *
	 * @return bool True if disabled.
	 */
	public function is_shell_exec_disabled(): bool {
		$disabled = array_map( 'trim', explode( ',', (string) ini_get( 'disable_functions' ) ) );
		return in_array( 'shell_exec', $disabled, true );
	}

	// -------------------------------------------------------------------------
	// History helpers
	// -------------------------------------------------------------------------

	/**
	 * Find a post ID in listings or used-listings by VIN number (ACF field: vin_number).
	 *
	 * @param string $vin Uppercase VIN string.
	 * @return int Post ID, or 0 if not found.
	 */
	private function find_post_by_vin( string $vin ): int {
		$query = new \WP_Query(
			array(
				'post_type'      => array( 'listings', 'used-listings' ),
				'posts_per_page' => 1,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array(
					array(
						'key'   => 'vin_number',
						'value' => $vin,
					),
				),
			)
		);

		return ! empty( $query->posts ) ? (int) $query->posts[0] : 0;
	}

	/**
	 * Handle the AJAX request to clear the current user's VIN history.
	 *
	 * @return void
	 */
	public function handle_clear_history(): void {
		check_ajax_referer( self::AJAX_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'shopperexpress' ) ), 403 );
		}

		delete_user_meta( get_current_user_id(), self::HISTORY_KEY );
		wp_send_json_success();
	}

	/**
	 * Prepend a VIN to the current user's history, capping at HISTORY_LIMIT.
	 *
	 * @param string $vin      Decoded VIN.
	 * @param mixed  $response Raw API response used to derive a human label.
	 * @return void
	 */
	private function push_history( string $vin, $response ): void {
		$history = $this->get_history();

		// Remove any pre-existing entry for the same VIN so we don't duplicate.
		$history = array_values(
			array_filter(
				$history,
				fn( $e ) => $e['vin'] !== $vin
			)
		);

		$label = $this->derive_label( $response );

		array_unshift(
			$history,
			array(
				'vin'   => $vin,
				'label' => $label,
				'time'  => wp_date( 'M j, Y g:i a' ),
			)
		);

		$history = array_slice( $history, 0, self::HISTORY_LIMIT );
		update_user_meta( get_current_user_id(), self::HISTORY_KEY, $history );
	}

	/**
	 * Extract a short human-readable label from the API response.
	 *
	 * @param mixed $response Raw API response.
	 * @return string
	 */
	private function derive_label( $response ): string {
		if ( ! is_array( $response ) ) {
			return '';
		}

		// Pull the first feature's sectionName as a short identifier, if present.
		$features = $response['result']['features'] ?? array();
		$section  = ! empty( $features[0]['sectionName'] ) ? $features[0]['sectionName'] : '';

		return $section;
	}

	// -------------------------------------------------------------------------
	// API
	// -------------------------------------------------------------------------

	/**
	 * Call the Chromedata API and return the decoded JSON array.
	 *
	 * Delegates entirely to Chromedata_Client so auth logic lives in one place.
	 *
	 * @param string $url  Chromedata endpoint URL (from ACF options).
	 * @param array  $data Query parameters (VIN, onlyDecodeUsing, etc.).
	 * @return array|\WP_Error Decoded response array or WP_Error.
	 */
	private function call_chromedata_api( string $url, array $data ) {
		$response = Chromedata_Client::request( $url, $data );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return $response['result'];
	}

	// -------------------------------------------------------------------------
	// Inline assets
	// -------------------------------------------------------------------------

	/**
	 * Return the inline CSS string.
	 *
	 * @return string
	 */
	private function inline_styles(): string {
		return '
.vin-checker-wrap .card { padding: 20px; margin-top: 16px; max-width: 900px; }
.vin-checker-input-row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.vin-checker-input-row input { min-width: 260px; font-family: monospace; letter-spacing: .05em; text-transform: uppercase; }
.vin-checker-spinner { display: flex; align-items: center; gap: 8px; margin-top: 12px; color: #555; }
.vin-checker-spinner .spinner { float: none; margin: 0; }
#vin-checker-results { margin-top: 16px; }
#vin-checker-results .notice { margin: 0 0 12px; }
.vin-result-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.vin-result-header h3 { margin: 0; }
.vin-result-json { background: #1e1e1e; color: #d4d4d4; padding: 16px; border-radius: 4px; overflow: auto; max-height: 600px; font-size: 13px; line-height: 1.6; white-space: pre; }
.vin-checker-bg-status { margin-left: 10px; font-style: italic; color: #555; }
.vin-result-cached { font-size: 11px; color: #888; margin-left: 8px; }
.vin-checker-history { margin-top: 12px; max-width: 900px; }
.vin-checker-history h2 { font-size: 14px; margin-bottom: 8px; }
#vin-checker-history-list { margin: 0; list-style: none; display: flex; flex-wrap: wrap; gap: 6px; }
#vin-checker-history-list li { display: flex; align-items: center; gap: 6px; background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 3px; padding: 4px 10px; }
.vin-checker-history-item { font-family: monospace; font-size: 13px; font-weight: 600; }
.vin-checker-history-meta { font-size: 11px; color: #888; }
		';
	}

	/**
	 * Return the inline JS string (runs after jQuery and wp_localize_script data).
	 *
	 * @return string
	 */
	private function inline_script(): string {
		return <<<'JS'
(function ($) {
    'use strict';

    var cfg     = vinChecker;
    var $input  = $('#vin-checker-input');
    var $btn    = $('#vin-checker-btn');
    var $spin   = $('#vin-checker-spinner');
    var $res    = $('#vin-checker-results');
    var VIN_RE  = /^[A-HJ-NPR-Z0-9]{17}$/i;

    /* ---------- helpers ---------- */

    function showNotice(type, msg) {
        $res.html(
            '<div class="notice notice-' + type + ' inline"><p>' +
            $('<span>').text(msg).html() +
            '</p></div>'
        );
    }

    function doLookup(vin) {
        vin = vin.toUpperCase().trim();

        if (!vin) { showNotice('warning', cfg.i18n.vinEmpty); return; }
        if (!VIN_RE.test(vin)) { showNotice('warning', cfg.i18n.vinFormat); return; }

        $input.val(vin);
        $btn.prop('disabled', true).text(cfg.i18n.checking);
        $spin.show();
        $res.empty();

        $.post(cfg.ajaxUrl, {
            action : cfg.action,
            nonce  : cfg.nonce,
            vin    : vin
        })
        .done(function (resp) {
            if (!resp.success) {
                showNotice('error', resp.data && resp.data.message ? resp.data.message : cfg.i18n.apiError);
                return;
            }

            var d = resp.data;

            if (!d.hasData) {
                showNotice('warning', cfg.i18n.noData);
                return;
            }

            var cachedBadge = d.cached
                ? '<span class="vin-result-cached">(cached)</span>'
                : '';

            var jsonStr = JSON.stringify(d.features, null, 2);

            $res.html(
                '<div class="vin-result-header">' +
                    '<h3>' + $('<span>').text(d.vin).html() + cachedBadge + '</h3>' +
                '</div>' +
                '<div class="notice notice-success inline"><p>VIN decoded successfully.</p></div>' +
                '<pre class="vin-result-json">' + $('<span>').text(jsonStr).html() + '</pre>'
            );

            $bgRow.show();
            $bgStatus.text('').css('color', '');

            // Reload history list without full page reload.
            if (!d.cached) {
                location.reload();
            }
        })
        .fail(function () {
            showNotice('error', cfg.i18n.apiError);
        })
        .always(function () {
            $btn.prop('disabled', false).text(cfg.i18n.checkVin);
            $spin.hide();
        });
    }

    /* ---------- event bindings ---------- */

    $btn.on('click', function () {
        doLookup($input.val());
    });

    $input.on('keydown', function (e) {
        if (e.key === 'Enter') { doLookup($input.val()); }
    });

    // Force uppercase as user types.
    $input.on('input', function () {
        var pos = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(pos, pos);
    });

    // History item click.
    $(document).on('click', '.vin-checker-history-item', function () {
        doLookup($(this).data('vin'));
        $('html, body').animate({ scrollTop: $input.offset().top - 32 }, 150);
        $input.trigger('focus');
    });

    // Clear history.
    $('#vin-checker-clear-history').on('click', function () {
        $.post(cfg.ajaxUrl, {
            action : cfg.action + '_clear_history',
            nonce  : cfg.nonce
        }).always(function () { location.reload(); });
    });

    // Run in background.
    var $bgBtn    = $('#vin-checker-bg-btn');
    var $bgRow    = $('#vin-checker-bg-row');
    var $bgStatus = $('#vin-checker-bg-status');
    var pollTimer = null;

    function stopPolling() {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    }

    function startPolling(postId) {
        var attempts = 0;
        var maxAttempts = 30; // 30 × 3 s = 90 s timeout.

        stopPolling();
        $bgStatus.css('color', '#555').text('Populating features_items… (post #' + postId + ')');

        pollTimer = setInterval(function () {
            attempts++;

            $.post(cfg.ajaxUrl, {
                action  : cfg.action + '_poll',
                nonce   : cfg.nonce,
                post_id : postId
            })
            .done(function (resp) {
                if (!resp.success) { return; }

                if (resp.data.populated) {
                    stopPolling();
                    $bgStatus.css('color', '#00a32a').text(
                        'Done — ' + resp.data.count + ' section(s) saved to ' + resp.data.field + ' (post #' + postId + ').'
                    );
                    $bgBtn.prop('disabled', false);
                } else if (attempts >= maxAttempts) {
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
        if (!vin) { return; }

        stopPolling();
        $bgBtn.prop('disabled', true);
        $bgStatus.css('color', '#555').text('Dispatching…');

        $.post(cfg.ajaxUrl, {
            action : cfg.action + '_run_background',
            nonce  : cfg.nonce,
            vin    : vin
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
JS;
	}
}

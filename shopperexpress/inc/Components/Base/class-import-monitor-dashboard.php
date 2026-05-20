<?php
/**
 * Import Monitor – Admin Dashboard Page.
 *
 * Adds a standalone WP admin page under "Tools" that shows each monitored
 * import's last run time, status, post count, and error message.
 * Also handles an AJAX/form POST for toggling a single import's "active" flag.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class Import_Monitor_Dashboard
 *
 * @package App\Components\Base
 */
class Import_Monitor_Dashboard implements Theme_Component {

	const PAGE_SLUG    = 'wpim-dashboard';
	const NONCE_ACTION = 'wpim_toggle_import';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_wpim_toggle', array( $this, 'ajax_toggle_import' ) );
	}

	/**
	 * Register admin menu page.
	 *
	 * @return void
	 */
	public function add_menu_page(): void {
		add_menu_page(
			__( 'Import Monitor Dashboard', 'shopperexpress' ),
			__( 'Import Monitor', 'shopperexpress' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' ),
			'dashicons-update',
			100
		);
	}

	/**
	 * Enqueue inline styles and scripts only on the dashboard page.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		// Inline CSS — no external file required.
		$css = '
		.wpim-wrap { max-width: 1100px; }
		.wpim-wrap h1 { display:flex; align-items:center; gap:10px; }
		.wpim-table { border-collapse:collapse; width:100%; margin-top:20px; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.13); }
		.wpim-table th, .wpim-table td { padding:10px 14px; border-bottom:1px solid #e5e5e5; vertical-align:middle; text-align:left; }
		.wpim-table thead th { background:#f8f8f8; font-weight:600; font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:#555; }
		.wpim-table tbody tr:hover { background:#fafafa; }
		.wpim-badge { display:inline-block; padding:3px 9px; border-radius:12px; font-size:11px; font-weight:600; text-transform:uppercase; }
		.wpim-badge--success { background:#d4edda; color:#1a6130; }
		.wpim-badge--failure { background:#f8d7da; color:#842029; }
		.wpim-badge--missing { background:#fff3cd; color:#856404; }
		.wpim-badge--never   { background:#e2e3e5; color:#41464b; }
		.wpim-toggle { position:relative; display:inline-block; width:42px; height:24px; }
		.wpim-toggle input { opacity:0; width:0; height:0; }
		.wpim-toggle .slider { position:absolute; cursor:pointer; inset:0; background:#ccc; border-radius:24px; transition:.3s; }
		.wpim-toggle .slider:before { position:absolute; content:""; height:18px; width:18px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:.3s; }
		.wpim-toggle input:checked + .slider { background:#2271b1; }
		.wpim-toggle input:checked + .slider:before { transform:translateX(18px); }
		.wpim-status-bar { display:flex; gap:16px; margin:16px 0; flex-wrap:wrap; }
		.wpim-stat { background:#fff; border:1px solid #e5e5e5; border-radius:6px; padding:14px 20px; min-width:140px; }
		.wpim-stat__num { font-size:28px; font-weight:700; line-height:1; }
		.wpim-stat__label { font-size:12px; color:#777; margin-top:4px; }
		.wpim-error-cell { max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-family:monospace; font-size:11px; color:#842029; }
		';

		wp_add_inline_style( 'wp-admin', $css );

		// Inline JS for the toggle switch.
		$js = '
		document.addEventListener("DOMContentLoaded", function () {
			document.querySelectorAll(".wpim-active-toggle").forEach(function (checkbox) {
				checkbox.addEventListener("change", function () {
					var importId = this.dataset.importId;
					var active   = this.checked ? 1 : 0;
					var nonce    = wpimDashboard.nonce;
					var data     = new FormData();
					data.append("action",    "wpim_toggle");
					data.append("import_id", importId);
					data.append("active",    active);
					data.append("nonce",     nonce);
					fetch(wpimDashboard.ajaxUrl, { method: "POST", body: data })
						.then(function (r) { return r.json(); })
						.catch(function () { /* silent */ });
				});
			});
		});
		';

		wp_add_inline_script(
			'jquery',
			sprintf(
				'var wpimDashboard = %s; %s',
				wp_json_encode(
					array(
						'ajaxUrl' => admin_url( 'admin-ajax.php' ),
						'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
					)
				),
				$js
			)
		);
	}

	/**
	 * Render the admin dashboard page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'shopperexpress' ) );
		}

		$monitoring_enabled = (bool) get_field( 'wpim_enabled', 'option' );
		$imports            = Import_Monitor_Tracker::get_monitored_imports();

		$total    = count( $imports );
		$success  = 0;
		$failures = 0;
		$never    = 0;

		$rows = array();
		foreach ( $imports as $config ) {
			$id    = (int) $config['import_id'];
			$state = Import_Monitor_Tracker::get_state( $id );

			$rows[] = array(
				'config' => $config,
				'state'  => $state,
			);

			switch ( $state['status'] ?? '' ) {
				case 'success':
					++$success;
					break;
				case 'failure':
					++$failures;
					break;
				default:
					++$never;
			}
		}
		?>
		<div class="wrap wpim-wrap">
			<h1>
				<span class="dashicons dashicons-performance" style="font-size:28px;width:28px;height:28px;"></span>
				<?php esc_html_e( 'Import Monitor Dashboard', 'shopperexpress' ); ?>
			</h1>

			<?php if ( ! $monitoring_enabled ) : ?>
				<div class="notice notice-warning inline">
					<p>
						<strong><?php esc_html_e( 'Monitoring is currently disabled.', 'shopperexpress' ); ?></strong>
						<?php esc_html_e( 'Enable it on the', 'shopperexpress' ); ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=monitor-settings' ) ); ?>"><?php esc_html_e( 'Import Monitor settings page', 'shopperexpress' ); ?></a>.
					</p>
				</div>
			<?php endif; ?>

			<div class="wpim-status-bar">
				<div class="wpim-stat">
					<div class="wpim-stat__num"><?php echo esc_html( $total ); ?></div>
					<div class="wpim-stat__label"><?php esc_html_e( 'Total Monitored', 'shopperexpress' ); ?></div>
				</div>
				<div class="wpim-stat">
					<div class="wpim-stat__num" style="color:#1a6130"><?php echo esc_html( $success ); ?></div>
					<div class="wpim-stat__label"><?php esc_html_e( 'Last Run OK', 'shopperexpress' ); ?></div>
				</div>
				<div class="wpim-stat">
					<div class="wpim-stat__num" style="color:#842029"><?php echo esc_html( $failures ); ?></div>
					<div class="wpim-stat__label"><?php esc_html_e( 'Last Run Failed', 'shopperexpress' ); ?></div>
				</div>
				<div class="wpim-stat">
					<div class="wpim-stat__num" style="color:#856404"><?php echo esc_html( $never ); ?></div>
					<div class="wpim-stat__label"><?php esc_html_e( 'Never Run', 'shopperexpress' ); ?></div>
				</div>
				<div class="wpim-stat">
					<div class="wpim-stat__num">
						<?php
						$next = wp_next_scheduled( Import_Monitor_Cron::HOOK );
						echo $next
							? esc_html( human_time_diff( $next ) )
							: '<span style="color:#aaa">—</span>';
						?>
					</div>
					<div class="wpim-stat__label"><?php esc_html_e( 'Next Cron Check', 'shopperexpress' ); ?></div>
				</div>
			</div>

			<?php if ( empty( $rows ) ) : ?>
				<p>
					<?php esc_html_e( 'No imports are configured yet.', 'shopperexpress' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=monitor-settings' ) ); ?>"><?php esc_html_e( 'Add monitored imports →', 'shopperexpress' ); ?></a>
				</p>
			<?php else : ?>
				<table class="wpim-table">
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
							$cfg         = $row['config'];
							$state       = $row['state'];
							$id          = (int) $cfg['import_id'];
							$stat        = $state['status'] ?? '';
							$badge_class = match ( $stat ) {
								'success' => 'wpim-badge--success',
								'failure' => 'wpim-badge--failure',
								default   => 'wpim-badge--never',
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
										<?php
										// Use last_run_gmt (UTC Unix ts) for human_time_diff so the
										// "X ago" label is correct regardless of server timezone.
										$run_gmt = (int) ( $state['last_run_gmt'] ?? 0 );
										if ( $run_gmt > 0 ) :
											?>
											<br><small style="color:#888"><?php echo esc_html( human_time_diff( $run_gmt ) ); ?> <?php esc_html_e( 'ago', 'shopperexpress' ); ?></small>
										<?php endif; ?>
									<?php else : ?>
										<span style="color:#aaa"><?php esc_html_e( '—', 'shopperexpress' ); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<?php
									// Show created / updated / skipped breakdown when available.
									// Falls back to legacy post_count for state written before
									// the detailed counters were introduced.
									$has_detail = isset( $state['created_count'] );
									if ( $has_detail ) :
										?>
										<span title="<?php esc_attr_e( 'Created / Updated / Skipped', 'shopperexpress' ); ?>">
											<span style="color:#1a6130;font-weight:600"><?php echo esc_html( $state['created_count'] ?? 0 ); ?></span>
											<span style="color:#777"> / </span>
											<span style="color:#2271b1"><?php echo esc_html( $state['updated_count'] ?? 0 ); ?></span>
											<span style="color:#777"> / </span>
											<span style="color:#856404"><?php echo esc_html( $state['skipped_count'] ?? 0 ); ?></span>
										</span>
										<br><small style="color:#aaa" title="<?php esc_attr_e( 'Created / Updated / Skipped', 'shopperexpress' ); ?>">C / U / S</small>
									<?php else : ?>
										<?php echo esc_html( $state['post_count'] ?? 0 ); ?>
									<?php endif; ?>
								</td>
								<td><span class="wpim-badge <?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $badge_label ); ?></span></td>
								<td class="wpim-error-cell" title="<?php echo esc_attr( $state['error'] ?? '' ); ?>">
									<?php echo esc_html( $state['error'] ?: esc_html__( '—', 'shopperexpress' ) ); ?>
								</td>
								<td>
									<label class="wpim-toggle">
										<input
											type="checkbox"
											class="wpim-active-toggle"
											data-import-id="<?php echo esc_attr( $id ); ?>"
											<?php checked( ! empty( $cfg['active'] ) ); ?>
										>
										<span class="slider"></span>
									</label>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<p style="margin-top:16px;color:#777;font-size:12px;">
					<?php esc_html_e( 'Last page load:', 'shopperexpress' ); ?> <?php echo esc_html( current_time( 'Y-m-d H:i:s' ) ); ?>
					&nbsp;|&nbsp;
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=monitor-settings' ) ); ?>"><?php esc_html_e( 'Edit settings →', 'shopperexpress' ); ?></a>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * AJAX handler: toggle a single import's "active" flag inside the ACF
	 * repeater stored in options.
	 *
	 * @return void
	 */
	public function ajax_toggle_import(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'shopperexpress' ) ), 403 );
		}

		$import_id = (int) filter_input( INPUT_POST, 'import_id', FILTER_SANITIZE_NUMBER_INT );
		$active    = (bool) filter_input( INPUT_POST, 'active', FILTER_SANITIZE_NUMBER_INT );

		$imports = get_field( 'wpim_imports', 'option' );
		if ( ! is_array( $imports ) ) {
			wp_send_json_error( array( 'message' => __( 'No imports found.', 'shopperexpress' ) ) );
		}

		$updated = false;
		foreach ( $imports as &$row ) {
			if ( (int) ( $row['import_id'] ?? 0 ) === $import_id ) {
				$row['active'] = $active ? 1 : 0;
				$updated       = true;
				break;
			}
		}
		unset( $row );

		if ( ! $updated ) {
			wp_send_json_error( array( 'message' => __( 'Import not found.', 'shopperexpress' ) ) );
		}

		update_field( 'wpim_imports', $imports, 'option' );

		wp_send_json_success(
			array(
				'import_id' => $import_id,
				'active'    => $active,
			)
		);
	}
}

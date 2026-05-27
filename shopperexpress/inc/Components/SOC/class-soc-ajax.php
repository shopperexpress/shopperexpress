<?php
/**
 * Handles AJAX actions for the SOC module of Shopperexpress.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC;

use App\Components\SOC\Contracts\SOC_Module;
use App\Components\SOC\Support\SOC_Logger;
use App\Components\SOC\Support\SOC_Response;

/**
 * Class SOC_Ajax
 *
 * Manages AJAX endpoints and their corresponding handlers for the SOC component.
 */
class SOC_Ajax {

	/**
	 * Registered modules available for actions.
	 *
	 * @var array
	 */
	private array $modules;

	/**
	 * Maps AJAX action slugs to handler method names.
	 *
	 * @var array
	 */
	private array $actions = array(
		'soc_load_panel'         => 'handle_load_panel',
		'soc_refresh_module'     => 'handle_refresh_module',
		'soc_db_cleanup'         => 'handle_db_cleanup',
		'soc_clear_cache'        => 'handle_clear_cache',
		'soc_run_cron'           => 'handle_run_cron',
		'soc_delete_cron'        => 'handle_delete_cron',
		'soc_reschedule_cron'    => 'handle_reschedule_cron',
		'soc_clear_transients'   => 'handle_clear_transients',
		'soc_clear_object_cache' => 'handle_clear_object_cache',
		'soc_optimize_tables'    => 'handle_optimize_tables',
		'soc_clear_pt_cache'     => 'handle_clear_pt_cache',
		'soc_regen_pt_cache'     => 'handle_regen_pt_cache',
		'soc_flush_expired'      => 'handle_flush_expired',
		'soc_view_pt_keys'       => 'handle_view_pt_keys',
		'soc_pt_cache_status'    => 'handle_pt_cache_status',
		'soc_test_api'           => 'handle_test_api',
		'soc_dismiss_notice'     => 'handle_dismiss_notice',
		'soc_vin_lookup'         => 'handle_vin_lookup',
		'soc_vin_clear_history'  => 'handle_vin_clear_history',
		'soc_vin_run_background' => 'handle_vin_run_background',
		'soc_vin_poll'           => 'handle_vin_poll',
		'soc_im_toggle'          => 'handle_im_toggle',
	);

	/**
	 * SOC_Ajax constructor.
	 *
	 * @param array $modules Array of available modules.
	 */
	public function __construct( array $modules ) {
		$this->modules = $modules;
	}

	/**
	 * Registers the AJAX actions with WordPress.
	 *
	 * Hooks each action in $actions array to wp_ajax_.
	 */
	public function register(): void {
		foreach ( array_keys( $this->actions ) as $action ) {
			add_action( 'wp_ajax_' . $action, array( $this, 'dispatch' ) );
		}
	}

	/**
	 * Main AJAX Dispatcher.
	 *
	 * Verifies permissions and nonce, then calls the appropriate handler.
	 */
	public function dispatch(): void {
		// Only allow users with manage_options capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			SOC_Response::error( 'Forbidden', 403 );
		}

		// Verify nonce for security.
		check_ajax_referer( 'soc_nonce', 'nonce' );

		// Determine the AJAX action and corresponding handler.
		$action  = sanitize_key( $_POST['action'] ?? '' );
		$handler = $this->actions[ $action ] ?? null;

		if ( ! $handler ) {
			SOC_Response::error( 'Unknown action.' );
		}

		// Call handler method.
		$this->$handler();
	}

	// -------------------------------------------------------------------------
	// Handlers
	// -------------------------------------------------------------------------

	/**
	 * Renders a module panel and returns the HTML (used for dynamic panel reload).
	 */
	private function handle_load_panel(): void {
		$slug   = sanitize_key( $_POST['module'] ?? '' );
		$module = $this->modules[ $slug ] ?? null;

		if ( ! $module instanceof SOC_Module ) {
			SOC_Response::error( 'Module not found.', 404 );
		}

		$data = $module->collect( true );

		ob_start();
		$module->render( $data );
		$html = ob_get_clean();

		SOC_Response::success( array( 'html' => $html ) );
	}

	/**
	 * Handles AJAX request for refreshing a module's data.
	 */
	private function handle_refresh_module(): void {
		$slug   = sanitize_key( $_POST['module'] ?? '' );
		$module = $this->modules[ $slug ] ?? null;

		if ( ! $module instanceof SOC_Module ) {
			SOC_Response::error( 'Module not found.', 404 );
		}

		$data = $module->collect( true );
		SOC_Response::success( $data );
	}

	/**
	 * Handles clearing the cache via cache-manager module.
	 */
	private function handle_clear_cache(): void {
		$result = $this->modules['cache-manager']->clear_all();
		SOC_Response::success( array( 'cleared' => $result ) );
	}

	/**
	 * Manually triggers a cron hook with provided arguments.
	 */
	private function handle_run_cron(): void {
		$hook = sanitize_text_field( $_POST['hook'] ?? '' );
		$args = isset( $_POST['args'] ) ? (array) $_POST['args'] : array();

		do_action_ref_array( $hook, $args );

		SOC_Logger::write( 'cron', 'Manual cron run: ' . $hook );

		SOC_Response::success(
			array(
				'hook'   => $hook,
				'ran_at' => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Deletes a specific scheduled cron event.
	 */
	private function handle_delete_cron(): void {
		$hook      = sanitize_text_field( $_POST['hook'] ?? '' );
		$timestamp = absint( $_POST['timestamp'] ?? 0 );

		wp_unschedule_event( $timestamp, $hook, array() );

		SOC_Logger::write( 'cron', 'Cron deleted: ' . $hook );

		SOC_Response::success( array( 'deleted' => $hook ) );
	}

	/**
	 * Reschedules a cron event with new recurrence.
	 */
	private function handle_reschedule_cron(): void {
		$hook       = sanitize_text_field( $_POST['hook'] ?? '' );
		$timestamp  = absint( $_POST['timestamp'] ?? 0 );
		$recurrence = sanitize_key( $_POST['recurrence'] ?? '' );

		if ( ! $hook || ! $recurrence ) {
			SOC_Response::error( 'Missing hook or recurrence.' );
		}

		// Validate recurrence exists.
		$schedules = wp_get_schedules();
		if ( ! isset( $schedules[ $recurrence ] ) ) {
			SOC_Response::error( 'Invalid recurrence: ' . $recurrence );
		}

		// jQuery serializes JS arrays as args[]=value, so $_POST['args'] may already be a PHP array.
		// If it's a string (manually built request or JSON payload), decode it.
		$raw_args = $_POST['args'] ?? array();
		if ( is_array( $raw_args ) ) {
			$args = array_values( wp_unslash( $raw_args ) );
		} else {
			$decoded = json_decode( wp_unslash( (string) $raw_args ), true );
			$args    = is_array( $decoded ) ? $decoded : array();
		}

		// Find and remove every instance of this hook regardless of timestamp/args.
		wp_clear_scheduled_hook( $hook );

		$new_timestamp = time();
		$scheduled     = wp_schedule_event( $new_timestamp, $recurrence, $hook, $args );

		if ( false === $scheduled ) {
			SOC_Logger::write( 'cron', 'Reschedule FAILED: ' . $hook . ' as ' . $recurrence );
			SOC_Response::error( 'wp_schedule_event failed for hook: ' . $hook );
		}

		// Bust the cached cron data so the next page load reflects the new schedule.
		\App\Components\SOC\Support\SOC_Cache::forget( 'cron-manager', 'data' );

		SOC_Logger::write( 'cron', 'Cron rescheduled: ' . $hook . ' as ' . $recurrence . ' (next: ' . $new_timestamp . ')' );

		SOC_Response::success(
			array(
				'rescheduled'   => $hook,
				'new_timestamp' => $new_timestamp,
			)
		);
	}

	/**
	 * Deletes all transients from the options table.
	 */
	private function handle_clear_transients(): void {
		global $wpdb;

		$deleted = $wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%' OR option_name LIKE '_site_transient_%'"
		);

		SOC_Logger::write( 'cache', 'Transients cleared: ' . $deleted . ' rows' );

		SOC_Response::success( array( 'deleted_rows' => $deleted ) );
	}

	/**
	 * Clears the object cache.
	 */
	private function handle_clear_object_cache(): void {
		$result = wp_cache_flush();

		SOC_Logger::write( 'cache', 'Object cache flushed' );

		SOC_Response::success( array( 'flushed' => $result ) );
	}

	/**
	 * Runs a targeted DB cleanup (revisions, spam, trash, orphan transients).
	 */
	private function handle_db_cleanup(): void {
		$type   = sanitize_key( $_POST['type'] ?? '' );
		$module = $this->modules['database-health'] ?? null;

		if ( ! $module ) {
			SOC_Response::error( 'Database module not available.' );
		}

		switch ( $type ) {
			case 'revisions':
				$deleted = $module->cleanup_revisions();
				break;
			case 'spam':
				$deleted = $module->cleanup_spam();
				break;
			case 'trash':
				$deleted = $module->cleanup_trash();
				break;
			case 'orphan':
				$deleted = $module->cleanup_orphan_transients();
				break;
			case 'auto_drafts':
				$deleted = $module->cleanup_auto_drafts();
				break;
			case 'orphan_postmeta':
				$deleted = $module->cleanup_orphan_postmeta();
				break;
			case 'orphan_term_rels':
				$deleted = $module->cleanup_orphan_term_rels();
				break;
			default:
				SOC_Response::error( 'Unknown cleanup type.' );
		}

		SOC_Logger::write( 'general', 'DB cleanup: ' . $type . ' | deleted: ' . $deleted );
		SOC_Response::success(
			array(
				'type'    => $type,
				'deleted' => $deleted,
			)
		);
	}

	/**
	 * Optimizes all database tables.
	 */
	private function handle_optimize_tables(): void {
		global $wpdb;

		$tables  = $wpdb->get_col( 'SHOW TABLES' );
		$results = array();

		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$results[ $table ] = $wpdb->query( "OPTIMIZE TABLE `{$table}`" );
		}

		SOC_Logger::write( 'general', 'DB tables optimized: ' . count( $tables ) . ' tables' );

		SOC_Response::success( $results );
	}

	/**
	 * Clears cache for a specific post type.
	 */
	private function handle_clear_pt_cache(): void {
		$post_type = sanitize_key( $_POST['post_type'] ?? '' );

		if ( empty( $post_type ) ) {
			SOC_Response::error( 'post_type required.' );
		}

		$result = $this->modules['cache-manager']->clear_post_type_cache( $post_type );
		SOC_Response::success( $result );
	}

	/**
	 * Triggers cache regeneration for a post type without clearing existing cache.
	 */
	private function handle_regen_pt_cache(): void {
		$post_type = sanitize_key( $_POST['post_type'] ?? '' );

		if ( empty( $post_type ) ) {
			SOC_Response::error( 'post_type required.' );
		}

		$regen_url = rest_url( 'v1/vehicles/' . rawurlencode( $post_type ) );
		wp_remote_get(
			$regen_url,
			array(
				'timeout'   => 0.01,
				'blocking'  => false,
				'sslverify' => false,
				'body'      => array( 'clear' => '1' ),
			)
		);

		$user  = wp_get_current_user();
		$login = $user->user_email ?: 'unknown';

		// Record who triggered this regen so Cache_Registry can attribute the build.
		update_option(
			'soc_pt_generated_by_' . $post_type,
			array(
				'user' => $login,
				'at'   => time(),
			),
			false
		);

		$log = (array) get_option( 'soc_cache_log', array() );
		array_unshift(
			$log,
			array(
				'action'    => 'regenerate',
				'post_type' => $post_type,
				'user'      => $login,
				'time'      => current_time( 'mysql' ),
			)
		);
		update_option( 'soc_cache_log', array_slice( $log, 0, 50 ), false );

		SOC_Logger::write( 'cache', 'PT cache regen triggered: ' . $post_type );

		SOC_Response::success(
			array(
				'post_type' => $post_type,
				'regen'     => 'triggered',
			)
		);
	}

	/**
	 * Flushes all expired theme API transients.
	 */
	private function handle_flush_expired(): void {
		$result = $this->modules['cache-manager']->flush_expired_transients();
		SOC_Response::success( $result );
	}

	/**
	 * Returns current cache status for a single post type (used for polling).
	 */
	private function handle_pt_cache_status(): void {
		$post_type = sanitize_key( $_POST['post_type'] ?? '' );

		if ( empty( $post_type ) ) {
			SOC_Response::error( 'post_type required.' );
		}

		$desc = \App\Components\SOC\Support\Cache_Registry::describe( $post_type );

		SOC_Response::success(
			array(
				'post_type'      => $post_type,
				'status'         => $desc['status'],
				'expires_at'     => $desc['expires_at'],
				'last_generated' => $desc['last_generated'],
			)
		);
	}

	/**
	 * Returns raw transient keys for a post type (read-only inspection).
	 */
	private function handle_view_pt_keys(): void {
		$post_type = sanitize_key( $_POST['post_type'] ?? '' );

		if ( empty( $post_type ) ) {
			SOC_Response::error( 'post_type required.' );
		}

		$keys = $this->modules['cache-manager']->get_post_type_keys( $post_type );
		SOC_Response::success( array( 'keys' => $keys ) );
	}

	/**
	 * Pings a URL to test API health.
	 */
	private function handle_test_api(): void {
		$url = esc_url_raw( $_POST['url'] ?? '' );

		if ( empty( $url ) ) {
			SOC_Response::error( 'URL required.' );
		}

		$result = $this->modules['api-health']->ping( $url );

		SOC_Response::success( $result );
	}

	/**
	 * Dismisses a specific admin notice until changed in the DB/options.
	 */
	private function handle_dismiss_notice(): void {
		$key       = sanitize_key( $_POST['notice_key'] ?? '' );
		$dismissed = get_option( 'soc_dismissed_notices', array() );

		$dismissed[ $key ] = time();

		update_option( 'soc_dismissed_notices', $dismissed, false );

		SOC_Response::success();
	}

	// ─── VIN / Developer Tools ────────────────────────────────────────────────

	/**
	 * Perform a VIN lookup via Chromedata API.
	 */
	private function handle_vin_lookup(): void {
		$vin = strtoupper( trim( sanitize_text_field( wp_unslash( $_POST['vin'] ?? '' ) ) ) );

		if ( empty( $vin ) ) {
			SOC_Response::error( __( 'Please enter a VIN.', 'shopperexpress' ) );
		}

		if ( ! preg_match( '/^[A-HJ-NPR-Z0-9]{17}$/', $vin ) ) {
			SOC_Response::error( __( 'VIN must be exactly 17 alphanumeric characters (I, O and Q are not valid).', 'shopperexpress' ) );
		}

		$module = $this->modules['developer-tools'] ?? null;

		if ( ! $module ) {
			SOC_Response::error( 'Developer Tools module not available.' );
		}

		$result = $module->vin_lookup( $vin );

		if ( is_wp_error( $result ) ) {
			SOC_Logger::write( 'api', 'VIN lookup error: ' . $result->get_error_message() );
			SOC_Response::error( $result->get_error_message() );
		}

		SOC_Response::success( $result );
	}

	/**
	 * Clear the current user's VIN lookup history.
	 */
	private function handle_vin_clear_history(): void {
		$module = $this->modules['developer-tools'] ?? null;

		if ( $module ) {
			$module->vin_clear_history();
		}

		SOC_Response::success();
	}

	/**
	 * Dispatch a background Chromedata API call for a VIN.
	 */
	private function handle_vin_run_background(): void {
		$vin = strtoupper( trim( sanitize_text_field( wp_unslash( $_POST['vin'] ?? '' ) ) ) );

		if ( ! preg_match( '/^[A-HJ-NPR-Z0-9]{17}$/', $vin ) ) {
			SOC_Response::error( __( 'Invalid VIN.', 'shopperexpress' ) );
		}

		$module = $this->modules['developer-tools'] ?? null;

		if ( ! $module ) {
			SOC_Response::error( 'Developer Tools module not available.' );
		}

		$result = $module->vin_run_background( $vin );

		if ( is_wp_error( $result ) ) {
			SOC_Response::error( $result->get_error_message() );
		}

		SOC_Response::success( $result );
	}

	/**
	 * Poll whether features_items has been populated for a post.
	 */
	private function handle_vin_poll(): void {
		$post_id = absint( $_POST['post_id'] ?? 0 );

		if ( ! $post_id ) {
			SOC_Response::error( __( 'Invalid post ID.', 'shopperexpress' ) );
		}

		$module = $this->modules['developer-tools'] ?? null;

		if ( ! $module ) {
			SOC_Response::error( 'Developer Tools module not available.' );
		}

		SOC_Response::success( $module->vin_poll( $post_id ) );
	}

	// ─── Import Monitor ───────────────────────────────────────────────────────

	/**
	 * Toggle the "active" flag on a single monitored import.
	 */
	private function handle_im_toggle(): void {
		$import_id = absint( $_POST['import_id'] ?? 0 );
		$active    = (bool) absint( $_POST['active'] ?? 0 );

		if ( ! $import_id ) {
			SOC_Response::error( __( 'Invalid import ID.', 'shopperexpress' ) );
		}

		$module = $this->modules['import-monitor'] ?? null;

		if ( ! $module ) {
			SOC_Response::error( 'Import Monitor module not available.' );
		}

		$result = $module->toggle_import( $import_id, $active );

		if ( is_wp_error( $result ) ) {
			SOC_Response::error( $result->get_error_message() );
		}

		SOC_Response::success( $result );
	}
}

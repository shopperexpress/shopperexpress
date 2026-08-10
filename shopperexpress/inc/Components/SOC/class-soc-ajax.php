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
		'soc_im_toggle'               => 'handle_im_toggle',
		'soc_api_mode_toggle'         => 'handle_api_mode_toggle',
		'soc_api_save_credentials'    => 'handle_api_save_credentials',
		'soc_api_test_connection'     => 'handle_api_test_connection',
		'soc_flush_api_cache'         => 'handle_flush_api_cache',
		'soc_flush_api_cache_group'   => 'handle_flush_api_cache_group',
		'soc_intice_cache_toggle'     => 'handle_intice_cache_toggle',
		'soc_api_save_filters'        => 'handle_api_save_filters',
		// Lead Delivery.
		'soc_lead_settings_save'      => 'handle_lead_settings_save',
		'soc_lead_test_connection'    => 'handle_lead_test_connection',
		'soc_lead_retry'              => 'handle_lead_retry',
		'soc_lead_log_filter'         => 'handle_lead_log_filter',
		// VDR Requests.
		'soc_vdr_log_filter'          => 'handle_vdr_log_filter',
		// JSON-LD Schema Builder.
		'soc_json_ld_save'            => 'handle_json_ld_save',
		'soc_json_ld_preview'         => 'handle_json_ld_preview',
		'soc_json_ld_get_posts'       => 'handle_json_ld_get_posts',
		'soc_json_ld_reset'           => 'handle_json_ld_reset',
		// Google Reviews.
		'soc_google_reviews_save'            => 'handle_google_reviews_save',
		'soc_google_reviews_save_places_key' => 'handle_google_reviews_save_places_key',
		'soc_google_reviews_disconnect'      => 'handle_google_reviews_disconnect',
		'soc_google_reviews_list_accounts'   => 'handle_google_reviews_list_accounts',
		'soc_google_reviews_list_locations'  => 'handle_google_reviews_list_locations',
		'soc_google_reviews_save_account'    => 'handle_google_reviews_save_account',
		'soc_google_reviews_test'            => 'handle_google_reviews_test',
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

	// ─── API Settings ─────────────────────────────────────────────────────────

	/**
	 * Toggle the global API mode on/off.
	 */
	private function handle_api_mode_toggle(): void {
		$enabled = (bool) absint( $_POST['enabled'] ?? 0 );

		$module = $this->modules['api-settings'] ?? null;

		if ( ! $module ) {
			SOC_Response::error( 'API Settings module not available.' );
		}

		$new_state = $module->set_api_mode( $enabled );

		SOC_Logger::write( 'general', 'API mode toggled: ' . ( $new_state ? 'enabled' : 'disabled' ) );

		SOC_Response::success( array( 'enabled' => $new_state ) );
	}

	/**
	 * Save Intice API credentials (URL + key).
	 */
	private function handle_api_save_credentials(): void {
		$url = esc_url_raw( wp_unslash( $_POST['api_url'] ?? '' ) );
		$key = sanitize_text_field( wp_unslash( $_POST['api_key'] ?? '' ) );

		if ( empty( $url ) ) {
			SOC_Response::error( __( 'API URL is required.', 'shopperexpress' ) );
		}

		$module = $this->modules['api-settings'] ?? null;

		if ( ! $module ) {
			SOC_Response::error( 'API Settings module not available.' );
		}

		$module->save_credentials( $url, $key );

		SOC_Logger::write( 'general', 'Intice API credentials updated.' );

		SOC_Response::success( array( 'saved' => true ) );
	}

	/**
	 * Test the connection to the Intice API.
	 */
	private function handle_api_test_connection(): void {
		$module = $this->modules['api-settings'] ?? null;

		if ( ! $module ) {
			SOC_Response::error( 'API Settings module not available.' );
		}

		$result = $module->test_connection();

		SOC_Response::success( $result );
	}

	/**
	 * Toggle Intice Nexus cache on/off.
	 */
	private function handle_intice_cache_toggle(): void {
		$enabled = (bool) absint( $_POST['enabled'] ?? 1 );
		$module  = $this->modules['api-settings'] ?? null;

		if ( ! $module ) {
			SOC_Response::error( 'API Settings module not available.' );
		}

		$new_state = $module->set_cache_enabled( $enabled );

		SOC_Logger::write( 'cache', 'Intice cache ' . ( $new_state ? 'enabled' : 'disabled' ) );

		SOC_Response::success( array( 'enabled' => $new_state ) );
	}

	/**
	 * Flush all Intice API transient cache.
	 */
	private function handle_flush_api_cache(): void {
		$module = $this->modules['api-settings'] ?? null;

		if ( ! $module ) {
			SOC_Response::error( 'API Settings module not available.' );
		}

		$module->flush_api_cache();

		SOC_Logger::write( 'cache', 'Intice API cache: full flush via SOC' );

		SOC_Response::success( array( 'flushed' => true ) );
	}

	/**
	 * Save the vehicle exclusion filters for new and/or used listings.
	 *
	 * Expected POST: listings (JSON string of rows), used-listings (JSON string of rows).
	 */
	private function handle_api_save_filters(): void {
		$module = $this->modules['api-settings'] ?? null;

		if ( ! $module ) {
			SOC_Response::error( 'API Settings module not available.' );
		}

		foreach ( array( 'listings', 'used-listings' ) as $post_type ) {
			if ( ! isset( $_POST[ $post_type ] ) ) {
				continue;
			}

			$rows = json_decode( wp_unslash( (string) $_POST[ $post_type ] ), true );
			$module->save_filters( $post_type, is_array( $rows ) ? $rows : array() );

			\App\Components\Api\Intice_Rest::clear_cache( $post_type, false );
			\App\Components\Api\Intice_Rest::clear_cache( $post_type, true );
		}

		SOC_Logger::write( 'general', 'Vehicle filters saved.' );

		SOC_Response::success( array( 'message' => 'Filters saved.' ) );
	}

	/**
	 * Flush a specific Intice API cache group (vehicles / vehicle / meta /
	 * new / used / new-custom / used-custom).
	 */
	private function handle_flush_api_cache_group(): void {
		$group  = sanitize_key( $_POST['group'] ?? '' );
		$module = $this->modules['api-settings'] ?? null;

		if ( ! $module ) {
			SOC_Response::error( 'API Settings module not available.' );
		}

		$valid_groups = array( 'vehicles', 'vehicle', 'meta', 'new', 'used', 'new-custom', 'used-custom' );

		if ( ! in_array( $group, $valid_groups, true ) ) {
			SOC_Response::error( 'Invalid cache group.' );
		}

		$deleted = $module->flush_api_cache_group( $group );

		SOC_Logger::write( 'cache', "Intice API cache group [{$group}] flushed via SOC" );

		SOC_Response::success( array( 'group' => $group, 'deleted' => $deleted ) );
	}

	// =========================================================================
	// Lead Delivery handlers
	// =========================================================================

	/**
	 * Save ADF lead delivery settings.
	 *
	 * Expected POST: delivery_method (email|api|both), api_endpoint, secret_key (optional), timeout, fallback_email.
	 */
	private function handle_lead_settings_save(): void {
		$method       = sanitize_key( $_POST['delivery_method'] ?? 'email' );
		$endpoint     = esc_url_raw( wp_unslash( $_POST['api_endpoint'] ?? '' ) );
		$dealer_id    = sanitize_text_field( wp_unslash( $_POST['dealer_id'] ?? '' ) );
		$key          = sanitize_text_field( wp_unslash( $_POST['secret_key'] ?? '' ) );
		$timeout      = (int) ( $_POST['timeout'] ?? 10 );
		$fallback     = ! empty( $_POST['fallback_email'] ) ? 1 : 0;
		$site_name    = sanitize_text_field( wp_unslash( $_POST['site_name'] ?? '' ) );
		$provider_src = sanitize_text_field( wp_unslash( $_POST['provider_source'] ?? '' ) );
		$notify_admin = ! empty( $_POST['notify_admin'] ) ? 1 : 0;
		$notify_email = sanitize_email( wp_unslash( $_POST['notify_email'] ?? '' ) );
		$max_retries  = max( 0, min( 10, (int) ( $_POST['max_retries'] ?? 3 ) ) );
		$dedup        = max( 0, (int) ( $_POST['dedup_minutes'] ?? 0 ) );
		$wpforms_ids  = sanitize_text_field( wp_unslash( $_POST['wpforms_ids'] ?? '' ) );

		if ( ! in_array( $method, array( 'email', 'api', 'both' ), true ) ) {
			SOC_Response::error( 'Invalid delivery method.' );
		}

		update_option( 'adf_delivery_method', $method );
		update_option( 'adf_api_fallback_email', $fallback );
		update_option( 'adf_site_name', $site_name );
		update_option( 'adf_provider_source', '' !== $provider_src ? $provider_src : 'shopperexpress' );
		update_option( 'adf_notify_admin_on_failure', $notify_admin );
		update_option( 'adf_max_retries', $max_retries );
		update_option( 'adf_dedup_minutes', $dedup );
		update_option( 'adf_wpforms_ids', $wpforms_ids );

		if ( is_email( $notify_email ) ) {
			update_option( 'adf_notify_email', $notify_email );
		}

		$client = new \App\Components\Base\ADF_Api_Client();
		$client->save_endpoint( $endpoint );
		$client->save_dealer_id( $dealer_id );
		$client->save_timeout( $timeout );

		if ( '' !== $key ) {
			$client->save_secret_key( $key );
		}

		SOC_Logger::write( 'lead', "ADF delivery settings saved. Method: {$method}" );

		SOC_Response::success(
			array(
				'message'        => 'Settings saved.',
				'api_configured' => $client->is_configured(),
			)
		);
	}

	/**
	 * Test the configured API connection with a dummy payload.
	 */
	private function handle_lead_test_connection(): void {
		$client = new \App\Components\Base\ADF_Api_Client();

		if ( ! $client->is_configured() ) {
			SOC_Response::error( 'API endpoint or secret key is not configured.' );
		}

		$result = $client->test_connection();

		SOC_Logger::write( 'lead', 'ADF API connection test: ' . ( $result['success'] ? 'OK' : 'FAILED — ' . $result['error_message'] ) );

		if ( $result['success'] ) {
			SOC_Response::success(
				array(
					'message'       => 'Connection successful.',
					'response_code' => $result['response_code'],
				)
			);
		} else {
			SOC_Response::error( 'Connection failed: ' . $result['error_message'] . ' (HTTP ' . $result['response_code'] . ')' );
		}
	}

	/**
	 * Retry a failed lead by its log ID.
	 *
	 * Expected POST: log_id (int).
	 */
	private function handle_lead_retry(): void {
		$log_id = (int) ( $_POST['log_id'] ?? 0 );

		if ( $log_id <= 0 ) {
			SOC_Response::error( 'Invalid log ID.' );
		}

		$module = $this->modules['lead-delivery'] ?? null;

		if ( ! $module ) {
			SOC_Response::error( 'Lead Delivery module not available.' );
		}

		$result = $module->retry_lead( $log_id );

		SOC_Logger::write( 'lead', "Manual retry for log #{$log_id}: " . ( $result['success'] ? 'OK' : 'FAILED' ) );

		if ( $result['success'] ) {
			SOC_Response::success( array( 'message' => $result['message'] ) );
		} else {
			SOC_Response::error( $result['message'] );
		}
	}

	/**
	 * Return a filtered/paginated HTML fragment of the lead log table.
	 *
	 * Expected POST: status (all|success|failed|pending), page (int).
	 */
	private function handle_lead_log_filter(): void {
		$status = sanitize_key( $_POST['status'] ?? 'all' );
		$page   = max( 1, (int) ( $_POST['page'] ?? 1 ) );
		$search = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );

		$module = $this->modules['lead-delivery'] ?? null;

		if ( ! $module ) {
			SOC_Response::error( 'Lead Delivery module not available.' );
		}

		$logs = $module->fetch_logs( $status, $page, $search );

		ob_start();
		require get_template_directory() . '/inc/Components/SOC/views/lead-delivery-table.php';
		$html = ob_get_clean();

		SOC_Response::success( array( 'html' => $html ) );
	}

	/**
	 * Return a filtered/paginated HTML fragment of the VDR request log table.
	 *
	 * Expected POST: result (all|success|error), page (int).
	 */
	private function handle_vdr_log_filter(): void {
		$result = sanitize_key( $_POST['result'] ?? 'all' );
		$page   = max( 1, (int) ( $_POST['page'] ?? 1 ) );

		$module = $this->modules['vdr-requests'] ?? null;

		if ( ! $module ) {
			SOC_Response::error( 'VDR Requests module not available.' );
		}

		$logs = $module->fetch_logs( $result, $page );

		ob_start();
		require get_template_directory() . '/inc/Components/SOC/views/vdr-requests-table.php';
		$html = ob_get_clean();

		SOC_Response::success( array( 'html' => $html ) );
	}

	// =========================================================================
	// JSON-LD Schema Builder handlers
	// =========================================================================

	/**
	 * Save the JSON-LD builder config to WP option.
	 *
	 * Expected POST: config (JSON string with mode, post_types, archive_limit, vehicle toggles).
	 */
	private function handle_json_ld_save(): void {
		$raw = wp_unslash( $_POST['config'] ?? '' );

		if ( empty( $raw ) ) {
			SOC_Response::error( 'No config payload received.' );
		}

		$input = json_decode( $raw, true );

		if ( ! is_array( $input ) ) {
			SOC_Response::error( 'Invalid JSON config.' );
		}

		// Sanitize mode.
		$mode = sanitize_key( $input['mode'] ?? 'legacy' );
		if ( ! in_array( $mode, array( 'legacy', 'builder' ), true ) ) {
			$mode = 'legacy';
		}

		// Sanitize post_types.
		$allowed_pts = array( 'listings', 'used-listings', 'lease-offers', 'finance-offers', 'conditional-offers', 'research' );
		$post_types  = array();
		if ( isset( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
			foreach ( $input['post_types'] as $pt ) {
				$pt = sanitize_key( $pt );
				if ( in_array( $pt, $allowed_pts, true ) ) {
					$post_types[] = $pt;
				}
			}
		}

		// Sanitize archive limit.
		$archive_limit = max( 1, min( 100, (int) ( $input['archive_limit'] ?? 24 ) ) );

		// Sanitize vehicle prop config (each prop can be bool or {enabled,acf_key,static_value}).
		$allowed_props = array(
			'name', 'description', 'image', 'brand', 'model', 'vehicleConfiguration',
			'vehicleModelDate', 'bodyType', 'vehicleIdentificationNumber', 'vehicleEngine',
			'fuelType', 'vehicleTransmission', 'mileageFromOdometer', 'color',
			'vehicleInteriorColor', 'offers', 'additionalProperty',
			// Extended Specs.
			'numberOfDoors', 'driveWheelConfiguration', 'vehicleSeatingCapacity',
			'fuelConsumption', 'knownVehicleDamages', 'vehicleSpecialUsage',
			'meetsEmissionStandard', 'numberOfForwardGears',
		);
		$vehicle     = array();
		$raw_vehicle = is_array( $input['vehicle'] ?? null ) ? $input['vehicle'] : array();
		foreach ( $allowed_props as $prop ) {
			$raw_prop = $raw_vehicle[ $prop ] ?? false;
			if ( is_array( $raw_prop ) ) {
				$vehicle[ $prop ] = array(
					'enabled'      => ! empty( $raw_prop['enabled'] ),
					'acf_key'      => sanitize_text_field( $raw_prop['acf_key']      ?? '' ),
					'static_value' => sanitize_text_field( $raw_prop['static_value'] ?? '' ),
				);
			} else {
				$vehicle[ $prop ] = (bool) $raw_prop;
			}
		}
		$vehicle['features_limit'] = max( 0, min( 50, (int) ( $raw_vehicle['features_limit'] ?? 0 ) ) );

		// Sanitize offers_sub.
		$raw_osub = is_array( $raw_vehicle['offers_sub'] ?? null ) ? $raw_vehicle['offers_sub'] : array();
		$allowed_avail     = array( 'InStock', 'OutOfStock', 'PreOrder', 'SoldOut', 'Discontinued' );
		$allowed_condition = array( 'auto', 'NewCondition', 'UsedCondition', 'RefurbishedCondition' );
		$avail_val = sanitize_text_field( $raw_osub['availability'] ?? 'InStock' );
		$cond_val  = sanitize_text_field( $raw_osub['condition'] ?? 'auto' );
		$vehicle['offers_sub'] = array(
			'price_currency'  => sanitize_text_field( $raw_osub['price_currency']  ?? 'USD' ),
			'price_key'       => sanitize_text_field( $raw_osub['price_key']       ?? $raw_osub['price_acf'] ?? $raw_osub['price_api'] ?? 'price' ),
			'availability'    => in_array( $avail_val, $allowed_avail, true ) ? $avail_val : 'InStock',
			'condition'       => in_array( $cond_val, $allowed_condition, true ) ? $cond_val : 'auto',
			'seller_name_key' => sanitize_text_field( $raw_osub['seller_name_key'] ?? $raw_osub['seller_name_acf'] ?? $raw_osub['seller_name_api'] ?? 'dealer_name' ),
			'seller_url_key'  => sanitize_text_field( $raw_osub['seller_url_key']  ?? $raw_osub['seller_url_acf']  ?? 'dealer_url' ),
		);

		// Sanitize custom properties.
		$custom_properties = array();
		if ( isset( $input['custom_properties'] ) && is_array( $input['custom_properties'] ) ) {
			foreach ( $input['custom_properties'] as $cp ) {
				if ( ! is_array( $cp ) ) {
					continue;
				}
				$cp_key = sanitize_text_field( $cp['key'] ?? '' );
				$cp_val = sanitize_text_field( $cp['value'] ?? '' );
				if ( $cp_key ) {
					$custom_properties[] = array( 'key' => $cp_key, 'value' => $cp_val );
				}
			}
		}

		// Sanitize non-vehicle schema type configs.
		$non_vehicle_types = array(
			'lease_offer', 'finance_offer', 'conditional_offer',
			'service_offer', 'archive_srp', 'research',
			'srp_item_listings', 'srp_item_used_listings',
			'archive_srp_listings', 'archive_srp_used_listings',
			'lease_offer_srp', 'finance_offer_srp', 'conditional_offer_srp', 'service_offer_srp',
			'research_srp',
		);
		$non_vehicle_cfg = array();
		foreach ( $non_vehicle_types as $type_key ) {
			$raw_type = is_array( $input[ $type_key ] ?? null ) ? $input[ $type_key ] : array();
			$type_cfg = array();
			foreach ( $raw_type as $prop_key => $prop_val ) {
				$prop_key = sanitize_key( $prop_key );
				if ( ! $prop_key ) continue;
				if ( is_array( $prop_val ) ) {
					$type_cfg[ $prop_key ] = array(
						'enabled'      => ! empty( $prop_val['enabled'] ),
						'acf_key'      => sanitize_text_field( $prop_val['acf_key']      ?? '' ),
						'static_value' => sanitize_text_field( $prop_val['static_value'] ?? '' ),
					);
				} else {
					$type_cfg[ $prop_key ] = (bool) $prop_val;
				}
			}
			$non_vehicle_cfg[ $type_key ] = $type_cfg;
		}

		// Sanitize vehicle_listings and vehicle_used_listings (same structure as vehicle).
		$type_specific_vehicle = array();
		foreach ( array( 'vehicle_listings', 'vehicle_used_listings' ) as $vkey ) {
			$raw_vt = is_array( $input[ $vkey ] ?? null ) ? $input[ $vkey ] : array();
			$vt_cfg = array();
			foreach ( $allowed_props as $prop ) {
				$raw_prop = $raw_vt[ $prop ] ?? false;
				if ( is_array( $raw_prop ) ) {
					$vt_cfg[ $prop ] = array(
						'enabled'      => ! empty( $raw_prop['enabled'] ),
						'acf_key'      => sanitize_text_field( $raw_prop['acf_key']      ?? '' ),
						'static_value' => sanitize_text_field( $raw_prop['static_value'] ?? '' ),
					);
				} elseif ( $raw_prop !== false ) {
					$vt_cfg[ $prop ] = (bool) $raw_prop;
				}
			}
			// Persist features_limit and offers_sub per post type.
			$vt_cfg['features_limit'] = max( 0, min( 50, (int) ( $raw_vt['features_limit'] ?? $raw_vehicle['features_limit'] ?? 0 ) ) );
			$raw_vt_osub = is_array( $raw_vt['offers_sub'] ?? null ) ? $raw_vt['offers_sub'] : $raw_osub;
			$vt_avail    = sanitize_text_field( $raw_vt_osub['availability'] ?? 'InStock' );
			$vt_cond     = sanitize_text_field( $raw_vt_osub['condition'] ?? 'auto' );
			$vt_cfg['offers_sub'] = array(
				'price_currency'  => sanitize_text_field( $raw_vt_osub['price_currency']  ?? 'USD' ),
				'price_key'       => sanitize_text_field( $raw_vt_osub['price_key'] ?? $raw_vt_osub['price_acf'] ?? $raw_vt_osub['price_api'] ?? 'price' ),
				'availability'    => in_array( $vt_avail, $allowed_avail, true ) ? $vt_avail : 'InStock',
				'condition'       => in_array( $vt_cond, $allowed_condition, true ) ? $vt_cond : 'auto',
				'seller_name_key' => sanitize_text_field( $raw_vt_osub['seller_name_key'] ?? $raw_vt_osub['seller_name_acf'] ?? $raw_vt_osub['seller_name_api'] ?? 'dealer_name' ),
				'seller_url_key'  => sanitize_text_field( $raw_vt_osub['seller_url_key']  ?? $raw_vt_osub['seller_url_acf']  ?? 'dealer_url' ),
			);
			$type_specific_vehicle[ $vkey ] = $vt_cfg;
		}

		// Sanitize SRP offers sub-fields per post type.
		$srp_osub_sanitized = array();
		foreach ( array( 'srp_item_offers_sub_listings', 'srp_item_offers_sub_used_listings' ) as $osub_key ) {
			$raw_so   = is_array( $input[ $osub_key ] ?? null ) ? $input[ $osub_key ] : array();
			$so_avail = sanitize_text_field( $raw_so['availability'] ?? 'InStock' );
			$so_cond  = sanitize_text_field( $raw_so['condition'] ?? 'auto' );
			$srp_osub_sanitized[ $osub_key ] = array(
				'price_currency'  => sanitize_text_field( $raw_so['price_currency'] ?? 'USD' ),
				'price_key'       => sanitize_text_field( $raw_so['price_key'] ?? $raw_so['price_api'] ?? 'price' ),
				'availability'    => in_array( $so_avail, $allowed_avail, true ) ? $so_avail : 'InStock',
				'condition'       => in_array( $so_cond, $allowed_condition, true ) ? $so_cond : 'auto',
				'show_seller'     => sanitize_text_field( $raw_so['show_seller'] ?? '1' ),
				'seller_name_key' => sanitize_text_field( $raw_so['seller_name_key'] ?? $raw_so['seller_name_api'] ?? 'dealer_name' ),
			);
		}

		$config = array_merge(
			array(
				'mode'              => $mode,
				'post_types'        => $post_types,
				'archive_limit'     => $archive_limit,
				'vehicle'           => $vehicle,
				'custom_properties' => $custom_properties,
			),
			$non_vehicle_cfg,
			$type_specific_vehicle,
			$srp_osub_sanitized
		);

		update_option( 'json_ld_field_config', $config );

		SOC_Logger::write( 'general', "JSON-LD config saved. Mode: {$mode}" );

		SOC_Response::success( array( 'message' => 'JSON-LD config saved.', 'mode' => $mode ) );
	}

	/**
	 * Return the actual JSON-LD output for a given post ID, using the submitted config preview.
	 *
	 * Expected POST: post_id (int), config (JSON string — optional, uses saved config if absent).
	 */
	private function handle_json_ld_preview(): void {
		$post_id = absint( $_POST['post_id'] ?? 0 );

		if ( ! $post_id || ! get_post( $post_id ) ) {
			SOC_Response::error( 'Valid post_id required.' );
		}

		// If a preview config is submitted, temporarily override the saved option.
		$raw = wp_unslash( $_POST['config'] ?? '' );
		if ( $raw ) {
			$preview_cfg = json_decode( $raw, true );
			if ( is_array( $preview_cfg ) ) {
				add_filter(
					'pre_option_json_ld_field_config',
					function () use ( $preview_cfg ) {
						return $preview_cfg;
					}
				);
			}
		}

		// Boot a minimal WP query context so get_the_ID(), is_singular() etc. work.
		global $wp_query, $post;
		$post     = get_post( $post_id ); // phpcs:ignore
		$wp_query = new \WP_Query( array( 'p' => $post_id, 'post_type' => $post->post_type ) ); // phpcs:ignore
		setup_postdata( $post );

		$instance = new \App\Components\Base\JSON_LD();
		$json_tag = $instance->get_json();

		wp_reset_postdata();

		// Strip script wrapper — return only raw JSON for the preview panel.
		$json_raw = preg_replace( '/<\/?script[^>]*>/i', '', $json_tag );

		SOC_Response::success( array( 'json' => trim( $json_raw ) ) );
	}

	/**
	 * Return recent posts for a given post type (used by the Real preview picker).
	 *
	 * Expected POST: post_type (string).
	 */
	private function handle_json_ld_get_posts(): void {
		$post_type = sanitize_key( $_POST['post_type'] ?? 'listings' );
		$allowed   = array( 'listings', 'used-listings', 'lease-offers', 'finance-offers', 'conditional-offers', 'service-offers', 'research' );
		if ( ! in_array( $post_type, $allowed, true ) ) {
			SOC_Response::error( 'Invalid post type.' );
		}

		$query = new \WP_Query( array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		) );

		$posts = array();
		foreach ( $query->posts as $p ) {
			$posts[] = array(
				'id'    => $p->ID,
				'title' => $p->post_title ?: '(no title) #' . $p->ID,
			);
		}

		SOC_Response::success( array( 'posts' => $posts ) );
	}

	/**
	 * Reset JSON-LD config to defaults by deleting the WP option.
	 */
	private function handle_json_ld_reset(): void {
		delete_option( 'json_ld_field_config' );

		SOC_Logger::write( 'general', 'JSON-LD config reset to defaults.' );

		SOC_Response::success( array( 'message' => 'Config reset to defaults.' ) );
	}

	// =========================================================================
	// Google Reviews handlers
	// =========================================================================

	/**
	 * Save the Google Business Profile OAuth client credentials.
	 */
	private function handle_google_reviews_save(): void {
		$client_id     = sanitize_text_field( wp_unslash( $_POST['client_id'] ?? '' ) );
		$client_secret = trim( wp_unslash( $_POST['client_secret'] ?? '' ) );

		$module = $this->modules['google-reviews'] ?? null;

		if ( ! $module ) {
			SOC_Response::error( 'Google Reviews module not available.' );
		}

		$module->save_oauth_client( $client_id, $client_secret );

		SOC_Logger::write( 'general', 'Google Reviews OAuth client saved.' );

		SOC_Response::success( array( 'message' => 'Settings saved.' ) );
	}

	/**
	 * Save the Places API key (fallback source).
	 */
	private function handle_google_reviews_save_places_key(): void {
		$api_key = trim( wp_unslash( $_POST['api_key'] ?? '' ) );

		$module = $this->modules['google-reviews'] ?? null;

		if ( ! $module ) {
			SOC_Response::error( 'Google Reviews module not available.' );
		}

		$module->save_places_api_key( $api_key );

		SOC_Logger::write( 'general', 'Google Reviews Places API key saved.' );

		SOC_Response::success( array( 'message' => 'Settings saved.' ) );
	}

	/**
	 * Disconnect the Google Business Profile connection.
	 */
	private function handle_google_reviews_disconnect(): void {
		$module = $this->modules['google-reviews'] ?? null;

		if ( ! $module ) {
			SOC_Response::error( 'Google Reviews module not available.' );
		}

		$module->disconnect();

		SOC_Logger::write( 'general', 'Google Reviews disconnected.' );

		SOC_Response::success( array( 'message' => 'Disconnected.' ) );
	}

	/**
	 * List Business Profile accounts reachable by the connected OAuth app.
	 */
	private function handle_google_reviews_list_accounts(): void {
		$module = $this->modules['google-reviews'] ?? null;

		if ( ! $module ) {
			SOC_Response::error( 'Google Reviews module not available.' );
		}

		$accounts = $module->list_accounts();

		if ( is_wp_error( $accounts ) ) {
			SOC_Response::error( $accounts->get_error_message() );
			return;
		}

		SOC_Response::success( array( 'accounts' => $accounts ) );
	}

	/**
	 * List Business Profile locations under a given account.
	 */
	private function handle_google_reviews_list_locations(): void {
		$account_id = sanitize_text_field( wp_unslash( $_POST['account_id'] ?? '' ) );

		$module = $this->modules['google-reviews'] ?? null;

		if ( ! $module ) {
			SOC_Response::error( 'Google Reviews module not available.' );
		}

		$locations = $module->list_locations( $account_id );

		if ( is_wp_error( $locations ) ) {
			SOC_Response::error( $locations->get_error_message() );
			return;
		}

		SOC_Response::success( array( 'locations' => $locations ) );
	}

	/**
	 * Save the chosen Business Profile account/location.
	 */
	private function handle_google_reviews_save_account(): void {
		$account_id  = sanitize_text_field( wp_unslash( $_POST['account_id'] ?? '' ) );
		$location_id = sanitize_text_field( wp_unslash( $_POST['location_id'] ?? '' ) );

		$module = $this->modules['google-reviews'] ?? null;

		if ( ! $module ) {
			SOC_Response::error( 'Google Reviews module not available.' );
		}

		$module->save_account_location( $account_id, $location_id );

		SOC_Logger::write( 'general', 'Google Reviews account/location saved.' );

		SOC_Response::success( array( 'message' => 'Settings saved.' ) );
	}

	/**
	 * Test the Google Reviews connection against a Place ID.
	 */
	private function handle_google_reviews_test(): void {
		$place_id = sanitize_text_field( wp_unslash( $_POST['place_id'] ?? '' ) );

		$module = $this->modules['google-reviews'] ?? null;

		if ( ! $module ) {
			SOC_Response::error( 'Google Reviews module not available.' );
		}

		$result = $module->test_connection( $place_id );

		SOC_Logger::write( 'general', 'Google Reviews connection test: ' . ( $result['ok'] ? 'OK' : 'FAILED — ' . ( $result['error'] ?? '' ) ) );

		if ( $result['ok'] ) {
			SOC_Response::success( $result );
		} else {
			SOC_Response::error( $result['error'] ?? 'Connection failed.' );
		}
	}
}

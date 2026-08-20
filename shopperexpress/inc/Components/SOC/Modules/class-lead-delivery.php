<?php
/**
 * SOC Lead Delivery Module
 *
 * Provides a dashboard panel for configuring ADF lead delivery (email vs API)
 * and reviewing the lead delivery log.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Modules;

use App\Components\Base\ADF_Api_Client;
use App\Components\SOC\Contracts\SOC_Module;
use App\Components\SOC\Support\SOC_Cache;

/**
 * Class Lead_Delivery
 *
 * SOC module that manages ADFXML lead delivery settings and displays a
 * paginated, filterable log of all lead submissions.
 */
class Lead_Delivery implements SOC_Module {

	/**
	 * Number of log rows to load per page.
	 */
	const PER_PAGE = 25;

	/**
	 * @return string
	 */
	public function get_slug(): string {
		return 'lead-delivery';
	}

	/**
	 * @return string
	 */
	public function get_label(): string {
		return 'Lead Delivery';
	}

	/**
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-email-alt';
	}

	/**
	 * Collect module data.
	 *
	 * @param bool $force_refresh Bypass cache when true.
	 * @return array
	 */
	public function collect( bool $force_refresh = false ): array {
		if ( $force_refresh ) {
			SOC_Cache::forget( $this->get_slug(), 'data' );
		}

		$cached = SOC_Cache::get( $this->get_slug(), 'data' );
		if ( $cached ) {
			// Always replace logs with fresh data (they change every submission).
			$cached['logs']  = $this->fetch_logs();
			$cached['stats'] = $this->fetch_stats();
			return $cached;
		}

		$client = new ADF_Api_Client();

		$data = array(
			'delivery_method'   => get_option( 'adf_delivery_method', 'email' ),
			'api_endpoint'      => get_option( ADF_Api_Client::OPTION_ENDPOINT, '' ),
			'dealer_id'         => get_option( ADF_Api_Client::OPTION_DEALER_ID, '' ),
			'api_configured'    => $client->is_configured(),
			'fallback_email'    => (bool) get_option( 'adf_api_fallback_email', 0 ),
			'timeout'           => (int) get_option( ADF_Api_Client::OPTION_TIMEOUT, 10 ),
			'secret_key_masked' => $this->mask_secret_key(),
			'site_name'         => get_option( 'adf_site_name', get_bloginfo( 'name' ) ),
			'notify_admin'      => (bool) get_option( 'adf_notify_admin_on_failure', 0 ),
			'notify_email'      => get_option( 'adf_notify_email', get_option( 'admin_email' ) ),
			'max_retries'       => (int) get_option( 'adf_max_retries', 3 ),
			'dedup_minutes'     => (int) get_option( 'adf_dedup_minutes', 0 ),
			'wpforms_form_ids'  => get_option( 'adf_wpforms_form_ids', '' ),
			'logs'              => $this->fetch_logs(),
			'stats'             => $this->fetch_stats(),
		);

		SOC_Cache::set( $this->get_slug(), 'data', $data, 120 );

		return $data;
	}

	/**
	 * Render the module view.
	 *
	 * @param array $data Collected data.
	 * @return void
	 */
	public function render( array $data ): void {
		require get_template_directory() . '/inc/Components/SOC/views/lead-delivery.php';
	}

	// -------------------------------------------------------------------------
	// Public helpers (used by SOC_Ajax handlers)
	// -------------------------------------------------------------------------

	/**
	 * Fetch a page of log rows with optional status filter.
	 *
	 * @param string $status  Filter by status: 'all'|'success'|'failed'|'pending'.
	 * @param int    $page    1-based page number.
	 * @return array{rows: array, total: int, per_page: int, page: int}
	 */
	public function fetch_logs( string $status = 'all', int $page = 1, string $search = '' ): array {
		global $wpdb;

		$table  = $wpdb->prefix . 'adf_lead_log';
		$offset = ( max( 1, $page ) - 1 ) * self::PER_PAGE;

		$where_parts = array();
		$args        = array();

		if ( 'all' !== $status && in_array( $status, array( 'success', 'failed', 'pending' ), true ) ) {
			$where_parts[] = 'status = %s';
			$args[]        = $status;
		}

		$search = trim( $search );
		if ( '' !== $search ) {
			$like           = '%' . $wpdb->esc_like( $search ) . '%';
			$where_parts[]  = '( email LIKE %s OR first_name LIKE %s OR last_name LIKE %s OR phone LIKE %s )';
			$args           = array_merge( $args, array( $like, $like, $like, $like ) );
		}

		$where = $where_parts ? 'WHERE ' . implode( ' AND ', $where_parts ) : '';

		$count_sql = "SELECT COUNT(*) FROM `{$table}` {$where}";
		$total     = (int) ( $args ? $wpdb->get_var( $wpdb->prepare( $count_sql, ...$args ) ) : $wpdb->get_var( $count_sql ) );

		$rows_sql = "SELECT id, submitted_at, site_name, form_name, lead_source, first_name, last_name,
		                    email, phone, delivery_method, api_endpoint, response_code, response_body,
		                    status, retry_count, error_message, adfxml_payload
		             FROM `{$table}` {$where}
		             ORDER BY submitted_at DESC
		             LIMIT %d OFFSET %d";

		$rows_args   = array_merge( $args, array( self::PER_PAGE, $offset ) );
		$rows        = $wpdb->get_results( $wpdb->prepare( $rows_sql, ...$rows_args ), ARRAY_A );

		return array(
			'rows'     => $rows ?: array(),
			'total'    => $total,
			'per_page' => self::PER_PAGE,
			'page'     => $page,
		);
	}

	/**
	 * Fetch aggregate stats for the stats cards.
	 *
	 * @return array{total_24h: int, success_24h: int, failed_24h: int, total_7d: int, failed_7d: int}
	 */
	public function fetch_stats(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'adf_lead_log';

		$row = $wpdb->get_row(
			"SELECT
				SUM( submitted_at >= DATE_SUB( NOW(), INTERVAL 24 HOUR ) )                                    AS total_24h,
				SUM( submitted_at >= DATE_SUB( NOW(), INTERVAL 24 HOUR ) AND status = 'success' )             AS success_24h,
				SUM( submitted_at >= DATE_SUB( NOW(), INTERVAL 24 HOUR ) AND status = 'failed' )              AS failed_24h,
				SUM( submitted_at >= DATE_SUB( NOW(), INTERVAL 7 DAY  ) )                                     AS total_7d,
				SUM( submitted_at >= DATE_SUB( NOW(), INTERVAL 7 DAY  ) AND status = 'failed' )               AS failed_7d
			FROM `{$table}`",
			ARRAY_A
		);

		return array(
			'total_24h'   => (int) ( $row['total_24h']   ?? 0 ),
			'success_24h' => (int) ( $row['success_24h'] ?? 0 ),
			'failed_24h'  => (int) ( $row['failed_24h']  ?? 0 ),
			'total_7d'    => (int) ( $row['total_7d']    ?? 0 ),
			'failed_7d'   => (int) ( $row['failed_7d']   ?? 0 ),
		);
	}

	/**
	 * Retry a previously failed lead by its log ID.
	 *
	 * @param int $log_id Row ID in adf_lead_log.
	 * @return array{success: bool, message: string}
	 */
	public function retry_lead( int $log_id ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'adf_lead_log';
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d", $log_id ), ARRAY_A );

		if ( ! $row ) {
			return array( 'success' => false, 'message' => 'Log entry not found.' );
		}

		if ( empty( $row['adfxml_payload'] ) ) {
			return array( 'success' => false, 'message' => 'No payload stored for this lead.' );
		}

		$fields = array(
			'first_name' => $row['first_name'],
			'last_name'  => $row['last_name'],
			'email'      => $row['email'],
			'phone'      => $row['phone'],
			'form_name'  => $row['form_name'],
		);

		$result = wps_dispatch_adf( $row['adfxml_payload'], $fields );

		// Update retry count on the original row.
		$wpdb->update(
			$table,
			array( 'retry_count' => (int) $row['retry_count'] + 1 ),
			array( 'id' => $log_id ),
			array( '%d' ),
			array( '%d' )
		);

		return array(
			'success' => $result['success'],
			'message' => $result['success'] ? 'Lead re-sent successfully.' : ( 'Retry failed: ' . $result['error_message'] ),
		);
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Return a masked version of the stored secret key for display.
	 *
	 * @return string e.g. "sk_****5f2a" or "(not set)"
	 */
	private function mask_secret_key(): string {
		$key = ( new ADF_Api_Client() )->get_secret_key();
		if ( '' === $key ) {
			return '';
		}
		$len = strlen( $key );
		if ( $len <= 4 ) {
			return str_repeat( '*', $len );
		}
		return str_repeat( '*', $len - 4 ) . substr( $key, -4 );
	}
}

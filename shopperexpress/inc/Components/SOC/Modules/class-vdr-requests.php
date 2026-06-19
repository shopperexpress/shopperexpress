<?php
/**
 * SOC VDR Requests Module
 *
 * Displays a log of all VDR (Vehicle Detail Report) API calls with stats.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Modules;

use App\Components\SOC\Contracts\SOC_Module;
use App\Components\SOC\Support\SOC_Cache;

/**
 * Class VDR_Requests
 */
class VDR_Requests implements SOC_Module {

	const PER_PAGE = 25;

	public function get_slug(): string {
		return 'vdr-requests';
	}

	public function get_label(): string {
		return 'VDR Requests';
	}

	public function get_icon(): string {
		return 'dashicons-media-document';
	}

	public function collect( bool $force_refresh = false ): array {
		if ( $force_refresh ) {
			SOC_Cache::forget( $this->get_slug(), 'data' );
		}

		$cached = SOC_Cache::get( $this->get_slug(), 'data' );
		if ( $cached ) {
			$cached['logs']  = $this->fetch_logs();
			$cached['stats'] = $this->fetch_stats();
			return $cached;
		}

		$data = array(
			'logs'  => $this->fetch_logs(),
			'stats' => $this->fetch_stats(),
		);

		SOC_Cache::set( $this->get_slug(), 'data', $data, 60 );

		return $data;
	}

	public function render( array $data ): void {
		require get_template_directory() . '/inc/Components/SOC/views/vdr-requests.php';
	}

	/**
	 * Fetch a page of VDR log rows.
	 *
	 * @param string $result  'all'|'success'|'error'
	 * @param int    $page    1-based page number.
	 * @return array{rows: array, total: int, per_page: int, page: int}
	 */
	public function fetch_logs( string $result = 'all', int $page = 1 ): array {
		global $wpdb;

		$table  = $wpdb->prefix . 'vdr_log';
		$offset = ( max( 1, $page ) - 1 ) * self::PER_PAGE;

		$where = '';
		$args  = array();

		if ( 'all' !== $result && in_array( $result, array( 'success', 'error' ), true ) ) {
			$where  = 'WHERE result = %s';
			$args[] = $result;
		}

		$count_sql = "SELECT COUNT(*) FROM `{$table}` {$where}";
		$total     = (int) ( $args ? $wpdb->get_var( $wpdb->prepare( $count_sql, ...$args ) ) : $wpdb->get_var( $count_sql ) );

		$rows_sql = "SELECT id, requested_at, vin, dealer_name, site_name, result, http_code, from_cache
		             FROM `{$table}` {$where}
		             ORDER BY requested_at DESC
		             LIMIT %d OFFSET %d";

		$rows_args = array_merge( $args, array( self::PER_PAGE, $offset ) );
		$rows      = $wpdb->get_results( $wpdb->prepare( $rows_sql, ...$rows_args ), ARRAY_A ) ?: array();

		return array(
			'rows'     => $rows,
			'total'    => $total,
			'per_page' => self::PER_PAGE,
			'page'     => $page,
		);
	}

	/**
	 * Fetch summary stats.
	 */
	public function fetch_stats(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'vdr_log';

		return array(
			'total_24h'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE requested_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)" ),
			'success_24h' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE result = 'success' AND requested_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)" ),
			'error_24h'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE result = 'error' AND requested_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)" ),
			'cached_24h'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE from_cache = 1 AND requested_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)" ),
			'total_7d'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE requested_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)" ),
			'error_7d'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE result = 'error' AND requested_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)" ),
		);
	}
}

<?php
/**
 * SOC AI VDP Log Module
 *
 * Displays a log of all AI VDP Description generation attempts with stats.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Modules;

use App\Components\SOC\Contracts\SOC_Module;
use App\Components\SOC\Support\SOC_Cache;

/**
 * Class AI_Vdp_Log
 */
class AI_Vdp_Log implements SOC_Module {

	const PER_PAGE = 25;

	public function get_slug(): string {
		return 'ai-vdp-log';
	}

	public function get_label(): string {
		return 'AI VDP Log';
	}

	public function get_icon(): string {
		return 'dashicons-edit-page';
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
		require get_template_directory() . '/inc/Components/SOC/views/ai-vdp-log.php';
	}

	/**
	 * Fetch a page of AI VDP log rows.
	 *
	 * @param string $status 'all'|'success'|'error'
	 * @param int    $page   1-based page number.
	 * @return array{rows: array, total: int, per_page: int, page: int}
	 */
	public function fetch_logs( string $status = 'all', int $page = 1 ): array {
		global $wpdb;

		$table  = $wpdb->prefix . 'ai_vdp_log';
		$offset = ( max( 1, $page ) - 1 ) * self::PER_PAGE;

		$where = '';
		$args  = array();

		if ( 'all' !== $status && in_array( $status, array( 'success', 'error' ), true ) ) {
			$where  = 'WHERE status = %s';
			$args[] = $status;
		}

		$count_sql = "SELECT COUNT(*) FROM `{$table}` {$where}";
		$total     = (int) ( $args ? $wpdb->get_var( $wpdb->prepare( $count_sql, ...$args ) ) : $wpdb->get_var( $count_sql ) );

		$rows_sql = "SELECT id, logged_at, post_id, post_type, vin, vehicle, status, reason, trigger_source
		             FROM `{$table}` {$where}
		             ORDER BY logged_at DESC
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

		$table = $wpdb->prefix . 'ai_vdp_log';

		return array(
			'total_24h'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE logged_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)" ),
			'success_24h' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE status = 'success' AND logged_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)" ),
			'error_24h'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE status = 'error' AND logged_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)" ),
			'total_7d'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE logged_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)" ),
			'error_7d'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE status = 'error' AND logged_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)" ),
		);
	}
}

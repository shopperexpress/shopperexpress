<?php
/**
 * SOC Overview Module
 *
 * Read-only landing page: aggregates a few headline numbers and a
 * "needs attention" list from the other modules. Owns no settings.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Modules;

use App\Components\Base\ADF_Api_Client;
use App\Components\SOC\Contracts\SOC_Module;

/**
 * Class Overview
 */
class Overview implements SOC_Module {

	/**
	 * @return string
	 */
	public function get_slug(): string {
		return 'overview';
	}

	/**
	 * @return string
	 */
	public function get_label(): string {
		return 'Overview';
	}

	/**
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-dashboard';
	}

	/**
	 * Not cached itself — it only composes data the other modules already
	 * cache (lead stats is one cheap aggregate query).
	 *
	 * @param bool $force_refresh Bypass the underlying module caches.
	 * @return array
	 */
	public function collect( bool $force_refresh = false ): array {
		$system = ( new System_Status() )->collect( $force_refresh );
		$cron   = ( new Cron_Manager() )->collect( $force_refresh );
		$api    = ( new Api_Settings() )->collect( $force_refresh );
		$stats  = ( new Lead_Delivery() )->fetch_stats();

		$delivery_method = get_option( 'adf_delivery_method', 'email' );
		$adf_api_ok      = ( new ADF_Api_Client() )->is_configured();

		$alerts = array();
		$add    = static function ( string $level, string $text, string $slug ) use ( &$alerts ) {
			$alerts[] = array(
				'level' => $level,
				'text'  => $text,
				'url'   => admin_url( 'admin.php?page=soc-' . $slug ),
			);
		};

		if ( empty( $system['php_ok'] ) ) {
			$add( 'fail', 'PHP version is below 8.0.', 'system-status' );
		}
		if ( $api['api_mode_enabled'] && ( ! $api['api_key_set'] || '' === $api['api_url'] ) ) {
			$add( 'fail', 'API mode is on but the Intice API URL or key is missing.', 'api-settings' );
		}
		if ( $api['api_mode_enabled'] && ! $api['cache_enabled'] ) {
			$add( 'warn', 'Intice Nexus cache is disabled — every request hits the API.', 'api-settings' );
		}
		if ( $stats['failed_24h'] > 0 ) {
			$add( 'fail', sprintf( '%d lead(s) failed delivery in the last 24 hours.', $stats['failed_24h'] ), 'lead-delivery' );
		}
		if ( 'api' === $delivery_method && ! $adf_api_ok ) {
			$add( 'fail', 'ADF delivery is set to API but the endpoint/secret is not configured.', 'lead-delivery' );
		}
		if ( ! empty( $cron['overdue_count'] ) ) {
			$add( 'warn', sprintf( '%d cron event(s) are overdue.', $cron['overdue_count'] ), 'cron-manager' );
		}
		if ( ! empty( $cron['disable_wp_cron'] ) ) {
			$add( 'warn', 'DISABLE_WP_CRON is set — make sure a real cron triggers wp-cron.php.', 'cron-manager' );
		}
		if ( ! empty( $system['wp_debug'] ) ) {
			$add( 'warn', 'WP_DEBUG is on.', 'system-status' );
		}
		if ( empty( $system['uploads_writable'] ) ) {
			$add( 'warn', 'The uploads directory is not writable.', 'system-status' );
		}

		return array(
			'api_mode'        => (bool) $api['api_mode_enabled'],
			'cache_enabled'   => (bool) $api['cache_enabled'],
			'php_version'     => $system['php_version'] ?? '',
			'wp_version'      => $system['wp_version'] ?? '',
			'mysql_version'   => $system['mysql_version'] ?? '',
			'leads'           => $stats,
			'delivery_method' => $delivery_method,
			'cron_total'      => (int) ( $cron['total'] ?? 0 ),
			'cron_overdue'    => (int) ( $cron['overdue_count'] ?? 0 ),
			'alerts'          => $alerts,
			'collected_at'    => current_time( 'mysql' ),
		);
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function render( array $data ): void {
		$view = get_template_directory() . '/inc/Components/SOC/views/overview.php';
		if ( file_exists( $view ) ) {
			include $view;
		}
	}
}

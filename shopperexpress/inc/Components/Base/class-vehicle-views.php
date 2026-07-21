<?php
/**
 * Vehicle Views Component
 *
 * Tracks all-time VDP view counts per VIN, independent of standard ACF
 * mode (WP posts) or Intice Nexus API mode (no WP posts for inventory).
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Vehicle Views Component
 */
class Vehicle_Views implements Theme_Component {

	/**
	 * Current schema version. Bump this when columns are added/changed.
	 */
	const TABLE_VERSION = 1;

	/**
	 * Name of the cookie used to dedupe views per visitor.
	 */
	const COOKIE_NAME = 'wps_viewed_vins';

	/**
	 * How long a viewed-VIN is remembered before it counts again.
	 */
	const COOKIE_TTL = DAY_IN_SECONDS;

	/**
	 * Register component
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'after_setup_theme', array( $this, 'maybe_create_table' ) );
	}

	/**
	 * Create the vehicle view counts table if it does not exist yet.
	 *
	 * @return void
	 */
	public function maybe_create_table(): void {
		global $wpdb;

		$table           = $wpdb->prefix . 'vehicle_view_counts';
		$current_version = (int) get_option( 'vehicle_view_counts_table_version', 0 );

		if ( $current_version >= self::TABLE_VERSION ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE `{$table}` (
			id               bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			vin              varchar(32)          NOT NULL DEFAULT '',
			view_count       bigint(20) UNSIGNED  NOT NULL DEFAULT 0,
			first_viewed_at  datetime             NOT NULL DEFAULT CURRENT_TIMESTAMP,
			last_viewed_at   datetime             NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY vin (vin)
		) {$charset_collate};";

		dbDelta( $sql );

		update_option( 'vehicle_view_counts_table_version', self::TABLE_VERSION );
	}

	/**
	 * Record a VDP view for a given VIN, deduped per visitor via cookie.
	 *
	 * @param string $vin Vehicle VIN.
	 * @return void
	 */
	public static function record_view( string $vin ): void {
		$vin = strtoupper( trim( $vin ) );

		if ( '' === $vin || headers_sent() ) {
			return;
		}

		$already_viewed = self::visitor_already_viewed( $vin );

		self::remember_visitor_view( $vin );

		if ( $already_viewed ) {
			return;
		}

		global $wpdb;

		$table = $wpdb->prefix . 'vehicle_view_counts';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO `{$table}` (vin, view_count, first_viewed_at, last_viewed_at)
				VALUES (%s, 1, NOW(), NOW())
				ON DUPLICATE KEY UPDATE view_count = view_count + 1, last_viewed_at = NOW()",
				$vin
			)
		);
	}

	/**
	 * Get the all-time view count for a VIN.
	 *
	 * @param string $vin Vehicle VIN.
	 * @return int
	 */
	public static function get_view_count( string $vin ): int {
		$vin = strtoupper( trim( $vin ) );

		if ( '' === $vin ) {
			return 0;
		}

		global $wpdb;

		$table = $wpdb->prefix . 'vehicle_view_counts';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT view_count FROM `{$table}` WHERE vin = %s",
				$vin
			)
		);

		return (int) $count;
	}

	/**
	 * Whether this visitor has already been counted for this VIN recently.
	 *
	 * @param string $vin Vehicle VIN.
	 * @return bool
	 */
	private static function visitor_already_viewed( string $vin ): bool {
		if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return false;
		}

		$viewed = json_decode( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ), true );

		if ( ! is_array( $viewed ) ) {
			return false;
		}

		return isset( $viewed[ $vin ] ) && (int) $viewed[ $vin ] > time();
	}

	/**
	 * Persist that this visitor has viewed the VIN, pruning expired entries.
	 *
	 * @param string $vin Vehicle VIN.
	 * @return void
	 */
	private static function remember_visitor_view( string $vin ): void {
		$viewed = array();

		if ( ! empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			$decoded = json_decode( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ), true );

			if ( is_array( $decoded ) ) {
				$viewed = $decoded;
			}
		}

		$expires_at = time() + self::COOKIE_TTL;

		// Prune expired entries so the cookie doesn't grow unbounded.
		$viewed = array_filter(
			$viewed,
			function ( $expiry ) {
				return (int) $expiry > time();
			}
		);

		$viewed[ $vin ] = $expires_at;

		setcookie(
			self::COOKIE_NAME,
			wp_json_encode( $viewed ),
			array(
				'expires'  => $expires_at,
				'path'     => '/',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
	}
}

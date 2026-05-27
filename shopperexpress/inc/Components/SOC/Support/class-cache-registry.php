<?php

namespace App\Components\SOC\Support;

/**
 * Discovers and describes post-type-based transient caches used by the theme API layer.
 *
 * Pattern from class-api.php:
 *   - ACF option `{post_type}_transient`        → stores the active transient key (standard)
 *   - ACF option `{post_type}_transient_custom`  → stores the active transient key (sorted)
 *   - Transient prefix: `v1/vehicles/{post_type}`
 *   - TTL: 24 * HOUR_IN_SECONDS
 *   - Global enable/disable: ACF option `cache_api`
 */
class Cache_Registry {

	const TRANSIENT_PREFIX = 'v1/vehicles/';
	const ALL_VEHICLES_KEY = 'v1/vehicles/';
	const TTL              = 86400; // 24 hours

	/**
	 * Known post types that participate in the theme API cache.
	 * Used as baseline; dynamic detection supplements this list.
	 */
	private static array $known_types = array(
		'listings',
		'used-listings',
		'service-specials',
		'parts-specials',
		'lease-offers',
		'finance-offers',
		'conditional-offers',
	);

	/**
	 * Return all cacheable post types — known list merged with any type
	 * that has a `{post_type}_transient` ACF option already set.
	 */
	public static function get_post_types(): array {
		$detected = self::detect_from_acf();
		$merged   = array_unique( array_merge( self::$known_types, $detected ) );

		// Filter to only types that actually exist in WordPress.
		return array_values( array_filter( $merged, static fn( $t ) => post_type_exists( $t ) ) );
	}

	/**
	 * Scan wp_options for `{post_type}_transient` option names set by the API layer.
	 */
	private static function detect_from_acf(): array {
		global $wpdb;

		$rows = $wpdb->get_col(
			"SELECT option_name
			 FROM {$wpdb->options}
			 WHERE option_name LIKE 'options\_%\_transient'
			   AND option_name NOT LIKE '%\_transient\_custom'
			   AND option_value != ''"
		);

		$types = array();
		foreach ( $rows as $row ) {
			// `options_{post_type}_transient` → strip prefix/suffix
			$name = preg_replace( '/^options_(.+)_transient$/', '$1', $row );
			if ( $name && $name !== $row ) {
				$types[] = $name;
			}
		}

		return $types;
	}

	/**
	 * Build a full status descriptor for a single post type.
	 *
	 * @return array{
	 *   post_type: string,
	 *   label: string,
	 *   prefix: string,
	 *   acf_key: string,
	 *   acf_key_custom: string,
	 *   cache_enabled: bool,
	 *   transient_key: string,
	 *   transient_key_custom: string,
	 *   ttl: int,
	 *   status: 'valid'|'expired'|'missing'|'stale',
	 *   last_generated: string,
	 *   expires_at: string,
	 *   size_bytes: int,
	 *   transient_count: int,
	 * }
	 */
	public static function describe( string $post_type ): array {
		global $wpdb;

		$acf_key        = $post_type . '_transient';
		$acf_key_custom = $post_type . '_transient_custom';
		$cache_enabled  = (bool) get_field( 'cache_api', 'options' );
		$transient_key  = (string) get_field( $acf_key, 'option' );

		$status         = 'missing';
		$last_generated = '';
		$expires_at     = '';
		$size_bytes     = 0;
		$generated_ts   = 0;

		if ( $transient_key ) {
			// Check the timeout record to determine expiry.
			$timeout_row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT option_value FROM {$wpdb->options}
					 WHERE option_name = %s",
					'_transient_timeout_' . $transient_key
				)
			);

			if ( $timeout_row ) {
				$expires_ts     = (int) $timeout_row->option_value;
				$expires_at     = wp_date( 'Y-m-d H:i:s', $expires_ts );
				$generated_ts   = $expires_ts - self::TTL;
				$last_generated = wp_date( 'Y-m-d H:i:s', $generated_ts );

				if ( $expires_ts > time() ) {
					$age_hours = ( time() - $generated_ts ) / HOUR_IN_SECONDS;
					$status    = $age_hours > 20 ? 'stale' : 'valid';
				} else {
					$status = 'expired';
				}

				// Approximate size of the transient value in the DB.
				$size_row   = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT LENGTH(option_value) FROM {$wpdb->options}
						 WHERE option_name = %s",
						'_transient_' . $transient_key
					)
				);
				$size_bytes = (int) $size_row;
			} else {
				// ACF key set but timeout row gone — transient expired/evicted.
				$status = 'expired';
			}
		}

		// Count all transients matching the prefix pattern.
		$prefix          = self::TRANSIENT_PREFIX . $post_type;
		$transient_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options}
				 WHERE option_name LIKE %s",
				'_transient_timeout_' . $wpdb->esc_like( $prefix ) . '%'
			)
		);

		// Resolve who generated the cache.
		// If SOC triggered the regen within 5 minutes of the transient's generated_ts, credit that user.
		// Otherwise the cache was built automatically (cron/import/REST without auth).
		$generated_by = 'after import';
		$trigger      = get_option( 'soc_pt_generated_by_' . $post_type, array() );
		if ( ! empty( $trigger['user'] ) && ! empty( $trigger['at'] ) && $generated_ts > 0 ) {
			if ( abs( $generated_ts - (int) $trigger['at'] ) <= 5 * MINUTE_IN_SECONDS ) {
				$generated_by = $trigger['user'];
			}
		}

		return array(
			'post_type'            => $post_type,
			'label'                => self::get_label( $post_type ),
			'prefix'               => $prefix,
			'acf_key'              => $acf_key,
			'acf_key_custom'       => $acf_key_custom,
			'cache_enabled'        => $cache_enabled,
			'transient_key'        => $transient_key,
			'transient_key_custom' => (string) get_field( $acf_key_custom, 'option' ),
			'ttl'                  => self::TTL,
			'status'               => $status,
			'last_generated'       => $last_generated,
			'expires_at'           => $expires_at,
			'size_bytes'           => $size_bytes,
			'transient_count'      => $transient_count,
			'generated_by'         => $generated_by,
		);
	}

	/**
	 * List raw transient keys for a post type (for the "view keys" action).
	 */
	public static function list_keys( string $post_type ): array {
		global $wpdb;

		$prefix = self::TRANSIENT_PREFIX . $post_type;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					REPLACE(option_name, '_transient_timeout_', '') AS transient_key,
					FROM_UNIXTIME(option_value)                      AS expires_at,
					option_value < UNIX_TIMESTAMP()                  AS is_expired
				 FROM {$wpdb->options}
				 WHERE option_name LIKE %s
				 ORDER BY option_value DESC
				 LIMIT 50",
				'_transient_timeout_' . $wpdb->esc_like( $prefix ) . '%'
			),
			ARRAY_A
		);
	}

	/**
	 * Delete all transients (including stale) for a post type prefix.
	 * Also clears the ACF transient key option.
	 *
	 * @return int Number of option rows deleted.
	 */
	public static function clear( string $post_type ): int {
		global $wpdb;

		$prefix = $wpdb->esc_like( self::TRANSIENT_PREFIX . $post_type );

		$deleted = (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options}
				 WHERE option_name LIKE %s
				    OR option_name LIKE %s",
				'_transient_timeout_' . $prefix . '%',
				'_transient_' . $prefix . '%'
			)
		);

		// Reset the ACF pointer fields.
		update_field( $post_type . '_transient', '', 'option' );
		update_field( $post_type . '_transient_custom', '', 'option' );

		// Clear the SOC-tracked generator so the next build shows correctly.
		delete_option( 'soc_pt_generated_by_' . $post_type );

		return $deleted;
	}

	/**
	 * Delete only expired transients for a post type.
	 *
	 * @return int Number of option rows deleted.
	 */
	public static function flush_expired( string $post_type ): int {
		global $wpdb;

		$prefix  = $wpdb->esc_like( self::TRANSIENT_PREFIX . $post_type );
		$now     = time();
		$deleted = 0;

		$expired_timeouts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options}
				 WHERE option_name LIKE %s
				   AND option_value < %d",
				'_transient_timeout_' . $prefix . '%',
				$now
			),
			ARRAY_A
		);

		foreach ( $expired_timeouts as $row ) {
			$value_key = str_replace( '_transient_timeout_', '_transient_', $row['option_name'] );
			$deleted  += (int) $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name = %s OR option_name = %s",
					$row['option_name'],
					$value_key
				)
			);
		}

		return $deleted;
	}

	/**
	 * Flush all expired theme API transients across all post types.
	 */
	public static function flush_all_expired(): int {
		global $wpdb;

		$prefix  = $wpdb->esc_like( self::TRANSIENT_PREFIX );
		$now     = time();
		$deleted = 0;

		$expired_timeouts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options}
				 WHERE option_name LIKE %s
				   AND option_value < %d",
				'_transient_timeout_' . $prefix . '%',
				$now
			),
			ARRAY_A
		);

		foreach ( $expired_timeouts as $row ) {
			$value_key = str_replace( '_transient_timeout_', '_transient_', $row['option_name'] );
			$deleted  += (int) $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name = %s OR option_name = %s",
					$row['option_name'],
					$value_key
				)
			);
		}

		return $deleted;
	}

	private static function get_label( string $post_type ): string {
		$obj = get_post_type_object( $post_type );
		return $obj ? $obj->labels->singular_name : ucwords( str_replace( '-', ' ', $post_type ) );
	}
}

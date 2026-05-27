<?php
/**
 * SOC Cache
 *
 * Provides a simple static cache utility using WordPress transients for module-based storage.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Support;

/**
 * Class SOC_Cache
 *
 * Static caching class to manage per-module caches using WordPress transients API.
 */
class SOC_Cache {

	/**
	 * Cache key prefix for all SOC entries.
	 */
	const PREFIX = 'soc_';

	/**
	 * Default TTL (time-to-live) for cached entries in seconds (5 minutes).
	 */
	const DEFAULT_TTL = 5 * MINUTE_IN_SECONDS;

	/**
	 * Retrieve a cached value.
	 *
	 * @param string $module Module or component name for scoping cache keys.
	 * @param string $key    Arbitrary cache key.
	 * @return mixed|null    Returns the cached value, or false if not set/expired.
	 */
	public static function get( string $module, string $key ): mixed {
		// Attempt to get the cached value for this module/key combination.
		return get_transient( self::build_key( $module, $key ) );
	}

	/**
	 * Store a value in the cache.
	 *
	 * @param string $module Module or component name for scoping cache keys.
	 * @param string $key    Arbitrary cache key.
	 * @param mixed  $value  Value to be cached. Can be any serializable value.
	 * @param int    $ttl    Time-to-live in seconds (default: 5 minutes).
	 * @return void
	 */
	public static function set( string $module, string $key, mixed $value, int $ttl = self::DEFAULT_TTL ): void {
		// Store the value in the WordPress transient system.
		set_transient( self::build_key( $module, $key ), $value, $ttl );
	}

	/**
	 * Remove a single cached entry.
	 *
	 * @param string $module Module or component name for scoping cache keys.
	 * @param string $key    Arbitrary cache key.
	 * @return void
	 */
	public static function forget( string $module, string $key ): void {
		// Delete the transient for this module/key combination.
		delete_transient( self::build_key( $module, $key ) );
	}

	/**
	 * Remove all cached entries for a given module.
	 *
	 * @param string $module Module or component name whose cache should be flushed.
	 * @return void
	 */
	public static function forget_module( string $module ): void {
		global $wpdb;

		// Build a SQL LIKE pattern for all transients matching the module prefix.
		$like = '_transient_' . $wpdb->esc_like( self::PREFIX . $module ) . '%';

		// Delete all matching transients directly from the options table.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$like
			)
		);
	}

	/**
	 * Build the transient key for a module + key pair.
	 *
	 * Ensures the transient key is unique for the module and key, using a hash to avoid length limits.
	 * Maximum length is capped at 172 characters, as per WP transient requirements.
	 *
	 * @param string $module Module or component name.
	 * @param string $key    Arbitrary cache key.
	 * @return string        Final transient key.
	 */
	public static function build_key( string $module, string $key ): string {
		// Concatenate prefix, module, and hashed key for uniqueness.
		$built = self::PREFIX . $module . '_' . md5( $key );

		// Cap the key length for compatibility with WordPress DB schema.
		return substr( $built, 0, 172 );
	}
}

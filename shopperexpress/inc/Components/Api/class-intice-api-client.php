<?php
/**
 * Intice Nexus API Client
 *
 * Stateless HTTP client for the Intice Nexus vehicle API.
 * All responses are cached in WordPress transients.
 *
 * Usage:
 *   $client = \App\Components\Api\Intice_Api_Client::instance();
 *   $result = $client->get_vehicles( [ 'make' => 'Toyota', 'page' => 1 ] );
 *
 * @package Shopperexpress
 */

namespace App\Components\Api;

use App\Components\SOC\Modules\Api_Settings;

/**
 * Class Intice_Api_Client
 */
class Intice_Api_Client {

	const CACHE_TTL_VEHICLES = 10 * MINUTE_IN_SECONDS;
	const CACHE_TTL_VEHICLE  = 15 * MINUTE_IN_SECONDS;
	const CACHE_TTL_META     = 30 * MINUTE_IN_SECONDS;
	const CACHE_PREFIX       = 'intice_api_';
	const CACHE_PREFIX_STALE = 'intice_stale_';
	const STALE_TTL          = 2 * HOUR_IN_SECONDS;

	/**
	 * Option storing a registry of known cache keys (key => [group, expires_at]).
	 *
	 * Reading cache status via raw SQL against wp_options is blind whenever a
	 * persistent object cache (Redis/Memcached) is active, since transients then
	 * live in the object cache, not the options table. This registry is written
	 * in PHP at set-time and read back the same way regardless of storage backend,
	 * so status/flush stay correct on any hosting setup.
	 */
	const REGISTRY_OPTION = 'intice_api_cache_registry';

	/**
	 * Singleton instance.
	 *
	 * @var static|null
	 */
	private static ?self $instance = null;

	/**
	 * Set to true during background regen to bypass stale-cache fallback and force a fresh API fetch.
	 *
	 * @var bool
	 */
	private static bool $regenerating = false;

	/**
	 * Base API URL (without trailing slash, without /api/v1).
	 *
	 * @var string
	 */
	private string $base_url;

	/**
	 * API key for X-API-KEY header.
	 *
	 * @var string
	 */
	private string $api_key;

	/**
	 * Private constructor — use instance().
	 */
	private function __construct() {
		$raw            = get_option( Api_Settings::OPTION_API_URL, '' );
		$this->base_url = rtrim( preg_replace( '#/api/v1$#', '', rtrim( $raw, '/' ) ), '/' );
		$this->api_key  = get_option( Api_Settings::OPTION_API_KEY, '' );
	}

	/**
	 * Return (or create) the singleton instance.
	 *
	 * @return static
	 */
	public static function instance(): static {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	// ─── Public API ───────────────────────────────────────────────────────────

	/**
	 * Get a paginated list of vehicles.
	 *
	 * Supported filters: make, model, trim, condition,
	 *   year_from, year_to, price_from, price_to, mileage_max,
	 *   sold, certified, page, per_page, mode.
	 *
	 * @param array $filters Query parameters to forward to the API.
	 * @return array|WP_Error Decoded response array or WP_Error on failure.
	 */
	public function get_vehicles( array $filters = array() ) {
		// Ensure mode=full is always present (overwriting if needed)
		$filters['mode'] = 'full';

		$live_key  = self::CACHE_PREFIX . 'vehicles_' . md5( serialize( $filters ) );
		$stale_key = self::CACHE_PREFIX_STALE . 'vehicles_' . md5( serialize( $filters ) );

		if ( ! self::$regenerating ) {
			if ( ! $this->is_cache_enabled() ) {
				return $this->request( 'GET', '/api/v1/vehicles', $filters );
			}

			$cached = get_transient( $live_key );
			if ( false !== $cached ) {
				return $cached;
			}

			// Serve stale data while background regeneration is in progress.
			$stale = get_transient( $stale_key );
			if ( false !== $stale ) {
				return $stale;
			}
		}

		$response = $this->request( 'GET', '/api/v1/vehicles', $filters );

		if ( is_wp_error( $response ) ) {
			$stale = get_transient( $stale_key );

			return false !== $stale ? $stale : $response;
		}

		if ( self::$regenerating || $this->is_cache_enabled() ) {
			set_transient( $live_key, $response, self::CACHE_TTL_VEHICLES );
			set_transient( $stale_key, $response, self::STALE_TTL );
			self::track_key( $live_key, 'vehicles', self::CACHE_TTL_VEHICLES );
			self::track_key( $stale_key, 'vehicles', self::STALE_TTL );
		}

		return $response;
	}

	/**
	 * Get a single vehicle by VIN.
	 *
	 * @param string $vin 17-character VIN.
	 * @return array|WP_Error Decoded response array or WP_Error on failure.
	 */
	public function get_vehicle( string $vin ) {
		$vin       = strtoupper( trim( $vin ) );
		$live_key  = self::CACHE_PREFIX . 'vehicle_' . $vin;
		$stale_key = self::CACHE_PREFIX_STALE . 'vehicle_' . $vin;

		if ( ! self::$regenerating ) {
			if ( ! $this->is_cache_enabled() ) {
				return $this->request( 'GET', '/api/v1/vehicles/' . rawurlencode( $vin ) );
			}

			$cached = get_transient( $live_key );
			if ( false !== $cached ) {
				return $cached;
			}

			$stale = get_transient( $stale_key );
			if ( false !== $stale ) {
				return $stale;
			}
		}

		$response = $this->request( 'GET', '/api/v1/vehicles/' . rawurlencode( $vin ) );

		if ( is_wp_error( $response ) ) {
			$stale = get_transient( $stale_key );

			return false !== $stale ? $stale : $response;
		}

		if ( self::$regenerating || $this->is_cache_enabled() ) {
			set_transient( $live_key, $response, self::CACHE_TTL_VEHICLE );
			set_transient( $stale_key, $response, self::STALE_TTL );
			self::track_key( $live_key, 'vehicle', self::CACHE_TTL_VEHICLE );
			self::track_key( $stale_key, 'vehicle', self::STALE_TTL );
		}

		return $response;
	}

	/**
	 * Get filter metadata: makes, models, year range, price range, conditions.
	 *
	 * @return array|WP_Error Decoded response array or WP_Error on failure.
	 */
	public function get_meta() {
		$live_key  = self::CACHE_PREFIX . 'meta';
		$stale_key = self::CACHE_PREFIX_STALE . 'meta';

		if ( ! self::$regenerating ) {
			if ( ! $this->is_cache_enabled() ) {
				return $this->request( 'GET', '/api/v1/meta' );
			}

			$cached = get_transient( $live_key );
			if ( false !== $cached ) {
				return $cached;
			}

			$stale = get_transient( $stale_key );
			if ( false !== $stale ) {
				return $stale;
			}
		}

		$response = $this->request( 'GET', '/api/v1/meta' );

		if ( is_wp_error( $response ) ) {
			$stale = get_transient( $stale_key );

			return false !== $stale ? $stale : $response;
		}

		if ( self::$regenerating || $this->is_cache_enabled() ) {
			set_transient( $live_key, $response, self::CACHE_TTL_META );
			set_transient( $stale_key, $response, self::STALE_TTL );
			self::track_key( $live_key, 'meta', self::CACHE_TTL_META );
			self::track_key( $stale_key, 'meta', self::STALE_TTL );
		}

		return $response;
	}

	/**
	 * Get a matching-vehicle count only, without fetching full vehicle payloads.
	 *
	 * Same filters as get_vehicles() (make, model, trim, condition, year_from,
	 * year_to, ...), but requests per_page=1 and reads meta.total instead of
	 * forcing mode=full — used for homepage/menu inventory counts where only
	 * the number matters, not the vehicle data itself.
	 *
	 * @param array $filters Query parameters to forward to the API.
	 * @return int|\WP_Error Vehicle count or WP_Error on failure.
	 */
	public function get_vehicles_count( array $filters = array() ) {
		unset( $filters['mode'] );
		$filters['per_page'] = 1;

		$live_key  = self::CACHE_PREFIX . 'vehicles_count_' . md5( serialize( $filters ) );
		$stale_key = self::CACHE_PREFIX_STALE . 'vehicles_count_' . md5( serialize( $filters ) );

		if ( ! self::$regenerating ) {
			if ( ! $this->is_cache_enabled() ) {
				$response = $this->request( 'GET', '/api/v1/vehicles', $filters );

				return is_wp_error( $response ) ? $response : (int) ( $response['meta']['total'] ?? 0 );
			}

			$cached = get_transient( $live_key );
			if ( false !== $cached ) {
				return $cached;
			}

			$stale = get_transient( $stale_key );
			if ( false !== $stale ) {
				return $stale;
			}
		}

		$response = $this->request( 'GET', '/api/v1/vehicles', $filters );

		if ( is_wp_error( $response ) ) {
			$stale = get_transient( $stale_key );

			return false !== $stale ? $stale : $response;
		}

		$count = (int) ( $response['meta']['total'] ?? 0 );

		if ( self::$regenerating || $this->is_cache_enabled() ) {
			set_transient( $live_key, $count, self::CACHE_TTL_VEHICLES );
			set_transient( $stale_key, $count, self::STALE_TTL );
			self::track_key( $live_key, 'vehicles', self::CACHE_TTL_VEHICLES );
			self::track_key( $stale_key, 'vehicles', self::STALE_TTL );
		}

		return $count;
	}

	/**
	 * Flush live cache with stale-while-revalidate: copies current transients to a stale
	 * prefix (2h TTL) so existing data keeps being served while a background cron regenerates
	 * the fresh cache.
	 *
	 * @return int Number of live keys flushed.
	 */
	public function flush_cache(): int {
		return $this->flush_matching( null );
	}

	/**
	 * Flush a single cache group ('vehicles', 'vehicle', or 'meta') with the same
	 * stale-while-revalidate behavior as flush_cache() — existing data keeps being
	 * served from the stale copy while a background cron regenerates it.
	 *
	 * @param string $group
	 * @return int Number of live keys flushed.
	 */
	public function flush_group( string $group ): int {
		return $this->flush_matching( array( $group ) );
	}

	/**
	 * Shared flush implementation: copies matching live transients to a stale
	 * prefix (2h TTL) before deleting them, then schedules a background regen.
	 *
	 * @param string[]|null $groups Restrict to these groups, or null for all.
	 * @return int Number of live keys flushed.
	 */
	private function flush_matching( ?array $groups ): int {
		// Use get_transient()/delete_transient() (via the key registry) rather than raw SQL
		// against wp_options, since a persistent object cache (Redis/Memcached) stores
		// transients outside the options table entirely — raw DELETEs would silently no-op.
		$registry = self::get_registry();
		$flushed  = 0;

		foreach ( $registry as $key => $meta ) {
			if ( 0 !== strpos( $key, self::CACHE_PREFIX ) ) {
				continue;
			}

			if ( null !== $groups && ! in_array( $meta['group'] ?? '', $groups, true ) ) {
				continue;
			}

			$value = get_transient( $key );

			if ( false !== $value ) {
				$stale_key = str_replace( self::CACHE_PREFIX, self::CACHE_PREFIX_STALE, $key );
				set_transient( $stale_key, $value, self::STALE_TTL );
				self::track_key( $stale_key, $meta['group'] ?? '', self::STALE_TTL );
			}

			delete_transient( $key );
			self::untrack_key( $key );
			++$flushed;
		}

		// Schedule immediate background regeneration.
		if ( ! wp_next_scheduled( 'intice_cache_regen' ) ) {
			wp_schedule_single_event( time(), 'intice_cache_regen' );
		}

		spawn_cron();

		return $flushed;
	}

	/**
	 * Regenerate the default cache entries (called by WP cron).
	 * Uses $regenerating flag to bypass stale fallback and force fresh API calls.
	 *
	 * @return void
	 */
	public function regen_cache(): void {
		self::$regenerating = true;

		$this->get_meta();
		$this->get_vehicles( array() );
		$this->get_vehicles( array( 'condition' => 'new' ) );
		$this->get_vehicles( array( 'condition' => 'used' ) );

		self::$regenerating = false;
	}

	// ─── Cache registry (backend-agnostic status/flush) ──────────────────────

	/**
	 * Record a cache key so status/flush works regardless of storage backend
	 * (DB transients vs. persistent object cache).
	 *
	 * @param string $key   Transient key.
	 * @param string $group 'vehicles', 'vehicle', or 'meta'.
	 * @param int    $ttl   Seconds until expiry, from now.
	 * @return void
	 */
	public static function track_key( string $key, string $group, int $ttl ): void {
		$registry = get_option( self::REGISTRY_OPTION, array() );

		// Prune long-expired entries so the registry doesn't grow unbounded — unlike DB
		// transients (garbage-collected by WP core), registry rows aren't auto-removed
		// on natural expiry since we only track writes/explicit deletes, not backend TTLs.
		$now = time();
		foreach ( $registry as $existing_key => $meta ) {
			if ( ( $meta['expires_at'] ?? 0 ) < $now - self::STALE_TTL ) {
				unset( $registry[ $existing_key ] );
			}
		}

		$registry[ $key ] = array(
			'group'      => $group,
			'expires_at' => $now + $ttl,
		);
		update_option( self::REGISTRY_OPTION, $registry, false );
	}

	/**
	 * Remove a key from the registry (after delete_transient()).
	 *
	 * @param string $key Transient key.
	 * @return void
	 */
	public static function untrack_key( string $key ): void {
		$registry = get_option( self::REGISTRY_OPTION, array() );

		if ( isset( $registry[ $key ] ) ) {
			unset( $registry[ $key ] );
			update_option( self::REGISTRY_OPTION, $registry, false );
		}
	}

	/**
	 * Return the full cache key registry: key => [ group, expires_at ].
	 *
	 * @return array<string, array{group: string, expires_at: int}>
	 */
	public static function get_registry(): array {
		return get_option( self::REGISTRY_OPTION, array() );
	}

	// ─── Private helpers ──────────────────────────────────────────────────────

	/**
	 * Whether transient caching is currently enabled.
	 *
	 * @return bool
	 */
	private function is_cache_enabled(): bool {
		return (bool) get_option( Api_Settings::OPTION_CACHE_ENABLED, true );
	}

	/**
	 * Update vehicle fields via PATCH /api/v1/vehicles/{vin}.
	 *
	 * Top-level fields (year, make, price, etc.) are passed directly.
	 * Payload fields must be nested under a 'payload' key.
	 * Busts the per-VIN transient cache on success.
	 *
	 * @param string $vin  Uppercase 17-char VIN.
	 * @param array  $data Associative array of fields to update.
	 * @return array|\WP_Error Updated vehicle data or WP_Error on failure.
	 */
	public function update_vehicle( string $vin, array $data ) {
		$vin    = strtoupper( trim( $vin ) );
		$result = $this->request( 'PATCH', '/api/v1/vehicles/' . rawurlencode( $vin ), array(), $data );

		if ( ! is_wp_error( $result ) ) {
			delete_transient( self::CACHE_PREFIX . 'vehicle_' . $vin );
			delete_transient( self::CACHE_PREFIX_STALE . 'vehicle_' . $vin );
			self::untrack_key( self::CACHE_PREFIX . 'vehicle_' . $vin );
			self::untrack_key( self::CACHE_PREFIX_STALE . 'vehicle_' . $vin );
		}

		return $result;
	}

	/**
	 * Trash a vehicle via DELETE /api/v1/vehicles/{vin} (soft-delete on the Nexus side).
	 * Busts the per-VIN transient cache on success.
	 *
	 * @param string $vin Uppercase 17-char VIN.
	 * @return array|\WP_Error Decoded response array or WP_Error on failure.
	 */
	public function delete_vehicle( string $vin ) {
		$vin    = strtoupper( trim( $vin ) );
		$result = $this->request( 'DELETE', '/api/v1/vehicles/' . rawurlencode( $vin ) );

		if ( ! is_wp_error( $result ) ) {
			delete_transient( self::CACHE_PREFIX . 'vehicle_' . $vin );
			delete_transient( self::CACHE_PREFIX_STALE . 'vehicle_' . $vin );
			self::untrack_key( self::CACHE_PREFIX . 'vehicle_' . $vin );
			self::untrack_key( self::CACHE_PREFIX_STALE . 'vehicle_' . $vin );
		}

		return $result;
	}

	// ─── HTTP layer ───────────────────────────────────────────────────────────

	private function request( string $method, string $path, array $params = array(), array $body = array() ) {
		if ( empty( $this->base_url ) || empty( $this->api_key ) ) {
			return new \WP_Error(
				'intice_not_configured',
				__( 'Intice API URL or key is not configured.', 'shopperexpress' )
			);
		}

		$url = $this->base_url . $path;

		if ( ! empty( $params ) ) {
			$url = add_query_arg( array_filter( $params, fn( $v ) => $v !== null && $v !== '' ), $url );
		}

		$request_args = array(
			'method'    => $method,
			'timeout'   => 15,
			'sslverify' => false,
			'headers'   => array(
				'X-API-KEY'    => $this->api_key,
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			),
		);

		if ( ! empty( $body ) ) {
			$request_args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $request_args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $code < 200 || $code >= 300 ) {
			$message = $data['error'] ?? $data['message'] ?? "HTTP {$code}";

			return new \WP_Error( 'intice_api_error', $message, array( 'status' => $code ) );
		}

		if ( null === $data ) {
			return new \WP_Error( 'intice_json_error', __( 'Invalid JSON response from Intice API.', 'shopperexpress' ) );
		}

		return $data;
	}
}

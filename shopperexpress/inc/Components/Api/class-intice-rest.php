<?php
/**
 * Intice Nexus REST Proxy
 *
 * Registers WP REST endpoints that proxy the Intice Nexus API and return
 * the same JSON shape the existing SRP JavaScript expects.
 *
 * Endpoints:
 *   GET /wp-json/v1/intice/vehicles/{post_type}
 *   GET /wp-json/v1/intice/meta
 *
 * @package Shopperexpress
 */

namespace App\Components\Api;

use App\Components\SOC\Modules\Api_Settings;
use App\Components\SOC\Support\SOC_Logger;
use App\Components\Theme_Component;

/**
 * Class Intice_Rest
 */
class Intice_Rest implements Theme_Component {

	/**
	 * TTL for the assembled SRP response cache (rendered HTML + terms + sort).
	 */
	const CACHE_TTL = 15 * MINUTE_IN_SECONDS;

	/**
	 * Prefix for the named per-post-type/per-sort SRP transients.
	 */
	const CACHE_PREFIX = 'intice_srp_';

	/**
	 * Prefix for the stale-while-revalidate copy of an SRP transient.
	 */
	const CACHE_PREFIX_STALE = 'intice_srp_stale_';

	/**
	 * Option storing the state of the last/current atomic "rebuild all" run —
	 * see queue_rebuild_all()/handle_rebuild_all().
	 */
	const OPTION_REBUILD_STATE = 'intice_cache_rebuild_state';

	/**
	 * A 'running' state older than this is treated as dead (e.g. a PHP fatal
	 * mid-run left it stuck) so a new rebuild can be queued instead of wedging
	 * forever on a run that will never update its own state again.
	 */
	const REBUILD_STALE_AFTER = 10 * MINUTE_IN_SECONDS;

	/**
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'intice_cache_regen', array( $this, 'handle_cache_regen' ) );
		add_action( 'intice_srp_cache_regen', array( $this, 'handle_srp_cache_regen' ), 10, 2 );
		add_action( 'intice_vdp_cache_regen', array( $this, 'handle_vdp_cache_regen' ) );
		add_action( 'intice_cache_rebuild_all', array( $this, 'handle_rebuild_all' ) );
		add_action( 'acf/save_post', array( $this, 'maybe_flush_custom_sort_cache' ), 20 );
	}

	/**
	 * Flush the "Custom sort" SRP cache when the Theme Options page is saved.
	 *
	 * The assembled SRP response is cached per post-type/sort-mode
	 * (see cache_key()), but that key doesn't depend on the actual sort
	 * rule contents — without this, changing/enabling `custom_sort_listings`
	 * or `custom_sort_used-listings` in ACF silently has no visible effect
	 * until the 15-minute TTL expires.
	 *
	 * @param int|string $post_id ACF passes 'options' for options-page saves.
	 * @return void
	 */
	public function maybe_flush_custom_sort_cache( $post_id ): void {
		if ( 'options' !== $post_id ) {
			return;
		}

		self::clear_cache( 'listings', true );
		self::clear_cache( 'used-listings', true );
	}

	/**
	 * WP cron callback: regenerate default Intice API cache entries.
	 *
	 * @return void
	 */
	public function handle_cache_regen(): void {
		Intice_Api_Client::instance()->regen_cache();
	}

	/**
	 * WP cron callback: regenerate a single VIN's cache entry (VDP stale-while-revalidate).
	 *
	 * @param string $vin 17-character VIN.
	 * @return void
	 */
	public function handle_vdp_cache_regen( string $vin ): void {
		Intice_Api_Client::instance()->regen_vehicle_cache( $vin );
	}

	/**
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			'v1',
			'/intice/vehicles/(?P<post_type>[a-zA-Z0-9-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_vehicles' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'v1',
			'/intice/meta',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_meta' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'v1',
			'/intice/cache/refresh',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_cache_refresh' ),
				'permission_callback' => array( $this, 'verify_intice_api_key' ),
			)
		);
	}

	/**
	 * Permission callback for /intice/cache/refresh — Nexus authenticates with
	 * the same X-API-KEY it issues for this site's own API access (stored here
	 * via SOC → API Settings), sent in reverse for this inbound notification.
	 *
	 * @param \WP_REST_Request $request
	 * @return bool
	 */
	public function verify_intice_api_key( \WP_REST_Request $request ): bool {
		$provided = $request->get_header( 'x-api-key' );
		$expected = get_option( Api_Settings::OPTION_API_KEY, '' );

		return ! empty( $expected ) && is_string( $provided ) && hash_equals( $expected, $provided );
	}

	/**
	 * POST /wp-json/v1/intice/cache/refresh
	 *
	 * Called by Nexus right after an import run finishes for this site. Queues
	 * the atomic "rebuild all" job (see queue_rebuild_all()/handle_rebuild_all())
	 * instead of flushing anything here — visitors keep seeing exactly what
	 * they were seeing until every group has been fetched and rebuilt
	 * successfully, at which point the whole set flips to new data in one pass.
	 *
	 * @return \WP_REST_Response
	 */
	public function handle_cache_refresh(): \WP_REST_Response {
		$queued = $this->queue_rebuild_all();

		SOC_Logger::write(
			'cache',
			$queued
				? 'Intice cache rebuild queued after Nexus import.'
				: 'Intice cache rebuild already in progress — Nexus import notification ignored.'
		);

		return rest_ensure_response( array( 'queued' => $queued ) );
	}

	/**
	 * Queue the atomic "rebuild all" job.
	 *
	 * By default, guarded against double-queueing while one is already running —
	 * this is what the Nexus webhook uses, so rapid-fire import notifications
	 * don't pile up overlapping runs. The SOC "Flush All Intice Cache" button
	 * passes $force = true: an admin explicitly clicking it wants a fresh run
	 * started regardless of whatever's in flight, not to be told to wait. A
	 * forced queue can't truly kill an already-running PHP process, but since
	 * both runs fetch independently and the newer one commits last, the net
	 * effect is a fresh rebuild either way — at worst some duplicated Nexus
	 * requests from the overlap.
	 *
	 * @param string $by    Attribution for the "Cleared By" column once this run
	 *                      succeeds — a user's email for a manual trigger, or
	 *                      Api_Settings::CLEARED_BY_IMPORT for the Nexus webhook.
	 * @param bool   $force Bypass the already-running guard.
	 * @return bool True if a rebuild was queued, false if one was already running
	 *              and $force was not set.
	 */
	public function queue_rebuild_all( string $by = Api_Settings::CLEARED_BY_IMPORT, bool $force = false ): bool {
		$state = self::get_rebuild_state();
		$stale = ( $state['started_at'] ?? 0 ) < time() - self::REBUILD_STALE_AFTER;

		if ( ! $force && 'running' === ( $state['status'] ?? '' ) && ! $stale ) {
			return false;
		}

		update_option( self::OPTION_REBUILD_STATE . '_queued_by', $by, false );

		if ( ! wp_next_scheduled( 'intice_cache_rebuild_all' ) ) {
			wp_schedule_single_event( time(), 'intice_cache_rebuild_all' );
		}

		spawn_cron();

		return true;
	}

	/**
	 * WP-Cron callback: fetch fresh data for every cache group into memory first,
	 * and only commit it to the cache if every single fetch/build step succeeds —
	 * so a visitor either keeps seeing the complete pre-rebuild data set, or sees
	 * the complete post-rebuild one, never a mix of the two. On any failure,
	 * nothing is written and the failure is recorded for SOC to display.
	 *
	 * @return void
	 */
	public function handle_rebuild_all(): void {
		$client = Intice_Api_Client::instance();
		// No unfiltered ('vehicles', no condition) fetch here — nothing in the live
		// site reads that variant (SRP/feed always filter by condition=new|used),
		// it's only ever warmed by the old regen_cache()/single-group "Build Cache"
		// path. Combining new+used into one request made it heavier than either
		// condition alone and an unnecessary single point of failure for the
		// whole atomic rebuild.
		$steps = array(
			array( 'meta', false, null ),
			array( 'vehicles', 'new', array( 'condition' => 'new' ) ),
			array( 'vehicles', 'used', array( 'condition' => 'used' ) ),
		);

		self::set_rebuild_state(
			array(
				'status'      => 'running',
				'started_at'  => time(),
				'finished_at' => null,
				// 3 raw fetches (meta, vehicles new/used) + 4 SRP builds
				// (new/used x default/custom-sort) + 1 feed build = 8 bump points below.
				'steps_total' => 8,
				'steps_done'  => 0,
				'failed_step' => null,
				'error'       => null,
			)
		);

		// Hard-purge the per-VIN VDP cache up front, unconditionally — not gated
		// behind the other 8 steps succeeding. There's no bulk way to know which
		// VINs changed/were deleted in Nexus, so the only correct response to "an
		// admin asked us to refresh everything" is to force every VDP page to
		// re-fetch on its next visit instead of keep serving a vehicle that may no
		// longer exist (Intice_Api_Client::get_vehicle() already turns a genuine
		// 404 from Nexus into an immediate cache purge — this just stops waiting
		// for that to happen naturally on the next visit/regen cycle).
		Intice_Api_Client::purge_group( 'vehicle' );

		$raw = array();

		foreach ( $steps as [ $kind, $key, $filters ] ) {
			$result = 'meta' === $kind ? $client->fetch_meta_raw() : $client->fetch_vehicles_raw( $filters );

			if ( is_wp_error( $result ) ) {
				$this->fail_rebuild( $kind . ( false === $key ? '' : ":{$key}" ), $result->get_error_message() );
				return;
			}

			$raw[ $kind . '_' . $key ] = $result;
			$this->bump_rebuild_progress();
		}

		$new_vehicles = $raw['vehicles_new']['data'] ?? array();
		$used_vehicles = $raw['vehicles_used']['data'] ?? array();

		$srp_groups = array(
			array( 'listings', false, $new_vehicles ),
			array( 'used-listings', false, $used_vehicles ),
			array( 'listings', true, $new_vehicles ),
			array( 'used-listings', true, $used_vehicles ),
		);

		$assembled = array();

		foreach ( $srp_groups as [ $post_type, $custom_sort, $vehicles ] ) {
			$output = $this->assemble_srp_payload( $vehicles, $post_type, $custom_sort, '' );

			if ( is_wp_error( $output ) ) {
				$this->fail_rebuild( self::cache_key( $post_type, $custom_sort ), $output->get_error_message() );
				return;
			}

			$assembled[] = array( $post_type, $custom_sort, $output );
			$this->bump_rebuild_progress();
		}

		$feed_output = $this->assemble_feed_payload( $new_vehicles, $used_vehicles );

		if ( is_wp_error( $feed_output ) ) {
			$this->fail_rebuild( 'vehicles-feed', $feed_output->get_error_message() );
			return;
		}

		$this->bump_rebuild_progress();

		// Every fetch/build above succeeded — commit everything. No network I/O
		// happens in this loop, so no visitor can observe a half-committed state.
		$client->commit_meta( $raw['meta_'] );
		$client->commit_vehicles( array( 'condition' => 'new' ), $raw['vehicles_new'] );
		$client->commit_vehicles( array( 'condition' => 'used' ), $raw['vehicles_used'] );

		foreach ( $assembled as [ $post_type, $custom_sort, $output ] ) {
			$this->commit_srp( $post_type, $custom_sort, $output );
		}

		$this->commit_srp( 'vehicles-feed', false, $feed_output );

		\wps_clear_listings_count_cache();

		$by = get_option( self::OPTION_REBUILD_STATE . '_queued_by', Api_Settings::CLEARED_BY_IMPORT );
		Api_Settings::mark_cache_cleared( Api_Settings::ALL_CACHE_GROUP_KEYS, $by );

		self::set_rebuild_state(
			array_merge(
				self::get_rebuild_state(),
				array(
					'status'      => 'success',
					'finished_at' => time(),
				)
			)
		);

		SOC_Logger::write( 'cache', 'Intice cache rebuild completed — all groups updated together.' );
	}

	/**
	 * Record a failed rebuild step — no cache was touched, so old data keeps serving.
	 *
	 * @param string $step
	 * @param string $error
	 * @return void
	 */
	private function fail_rebuild( string $step, string $error ): void {
		self::set_rebuild_state(
			array_merge(
				self::get_rebuild_state(),
				array(
					'status'      => 'failed',
					'finished_at' => time(),
					'failed_step' => $step,
					'error'       => $error,
				)
			)
		);

		SOC_Logger::write( 'cache', "Intice cache rebuild failed at [{$step}]: {$error} — no data was changed." );
	}

	/**
	 * @return void
	 */
	private function bump_rebuild_progress(): void {
		$state                 = self::get_rebuild_state();
		$state['steps_done'] = ( $state['steps_done'] ?? 0 ) + 1;
		self::set_rebuild_state( $state );
	}

	/**
	 * @return array{status:string,started_at:?int,finished_at:?int,steps_total:int,steps_done:int,failed_step:?string,error:?string}
	 */
	public static function get_rebuild_state(): array {
		return get_option(
			self::OPTION_REBUILD_STATE,
			array(
				'status'      => '',
				'started_at'  => null,
				'finished_at' => null,
				'steps_total' => 0,
				'steps_done'  => 0,
				'failed_step' => null,
				'error'       => null,
			)
		);
	}

	/**
	 * @param array $state
	 * @return void
	 */
	private static function set_rebuild_state( array $state ): void {
		update_option( self::OPTION_REBUILD_STATE, $state, false );
	}

	// ─── Handlers ─────────────────────────────────────────────────────────────

	/**
	 * Proxy GET /wp-json/v1/intice/vehicles/{post_type}
	 *
	 * Returns same shape as the existing WP vehicles endpoint so the SRP JS
	 * works without any changes:
	 * { vehicles: [ { html, terms, price, payment, year, title, link, search, photo } ] }
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_vehicles( \WP_REST_Request $request ): \WP_REST_Response {
		$post_type = sanitize_key( $request->get_param( 'post_type' ) );

		if ( ! in_array( $post_type, array( 'listings', 'used-listings', 'vehicles-feed' ), true ) ) {
			return new \WP_REST_Response( array( 'error' => 'Post type not supported in API mode.' ), 400 );
		}

		$custom_sort = ! empty( $_REQUEST['sort'] ) && 'custom' === $_REQUEST['sort'] && 'vehicles-feed' !== $post_type;
		$clear       = ! empty( $_REQUEST['clear'] );
		$loged       = ! empty( $_REQUEST['loged'] ) ? $_REQUEST['loged'] : '';
		$cache_key   = self::cache_key( $post_type, $custom_sort );
		$stale_key   = self::stale_cache_key( $post_type, $custom_sort );

		if ( ! $clear && $this->is_cache_enabled() ) {
			$cached = get_transient( $cache_key );

			if ( false !== $cached ) {
				return rest_ensure_response( $cached );
			}

			// Serve stale data immediately while a background cron rebuilds the live cache,
			// same stale-while-revalidate pattern as Intice_Api_Client.
			$stale = get_transient( $stale_key );

			if ( false !== $stale ) {
				$this->schedule_srp_regen( $post_type, $custom_sort );

				return rest_ensure_response( $stale );
			}
		}

		$output = 'vehicles-feed' === $post_type
			? $this->build_feed_payload()
			: $this->build_srp_payload( $post_type, $custom_sort, $loged );

		if ( is_wp_error( $output ) ) {
			return new \WP_REST_Response(
				array(
					'error'    => $output->get_error_message(),
					'vehicles' => array(),
				),
				502
			);
		}

		if ( $this->is_cache_enabled() ) {
			$this->store_srp_cache( $post_type, $custom_sort, $output );
		}

		return rest_ensure_response( $output );
	}

	/**
	 * Rebuild one SRP cache entry (post_type/sort combo) — used both as the
	 * WP-Cron background-regen callback and as the "Build Cache" on-demand
	 * handler (see Api_Settings::warm_api_cache_group()), so an upstream
	 * failure is reported back to whichever caller needs to know instead of
	 * being swallowed silently.
	 *
	 * @param string $post_type
	 * @param bool   $custom_sort
	 * @return true|\WP_Error
	 */
	public function handle_srp_cache_regen( string $post_type, bool $custom_sort ) {
		// Force the underlying Intice_Api_Client calls to bypass their own still-valid
		// stale copy and hit Nexus for real — otherwise this "regen" can just re-read
		// the same stale vehicle list and re-cache it under a fresh SRP TTL.
		$output = Intice_Api_Client::force_fresh(
			function () use ( $post_type, $custom_sort ) {
				return 'vehicles-feed' === $post_type
					? $this->build_feed_payload()
					: $this->build_srp_payload( $post_type, $custom_sort, '' );
			}
		);

		if ( is_wp_error( $output ) ) {
			return $output;
		}

		$this->store_srp_cache( $post_type, $custom_sort, $output );

		return true;
	}

	/**
	 * Fetch (via the cached client), then assemble, vehicles for a post type/sort
	 * combo into the SRP JS response shape. Used by the live request path and the
	 * single-group background regen cron callback — both want the client's own
	 * cache-or-fetch behavior. The batch rebuilder (handle_rebuild_all()) instead
	 * calls assemble_srp_payload() directly with data it already fetched raw,
	 * since it must hold every group in memory before committing any of them.
	 *
	 * @param string $post_type
	 * @param bool   $custom_sort
	 * @param string $loged
	 * @return array|\WP_Error
	 */
	private function build_srp_payload( string $post_type, bool $custom_sort, string $loged ) {
		$client     = Intice_Api_Client::instance();
		$conditions = array(
			'listings'      => 'new',
			'used-listings' => 'used',
		);

		$condition = isset( $conditions[ $post_type ] ) ? $conditions[ $post_type ] : '';

		$api_data = $client->get_vehicles( array( 'condition' => $condition ) );

		if ( is_wp_error( $api_data ) ) {
			return $api_data;
		}

		return $this->assemble_srp_payload( $api_data['data'] ?? array(), $post_type, $custom_sort, $loged );
	}

	/**
	 * Filter, sort, and render an already-fetched raw vehicle list for a post
	 * type/sort combo into the SRP JS response shape. Pure — does not touch the
	 * cache or Nexus itself, so it's safe to call speculatively before deciding
	 * whether to commit anything (see handle_rebuild_all()).
	 *
	 * @param array  $raw_vehicles Raw Intice vehicle arrays (e.g. $api_data['data']).
	 * @param string $post_type
	 * @param bool   $custom_sort
	 * @param string $loged
	 * @return array|\WP_Error
	 */
	private function assemble_srp_payload( array $raw_vehicles, string $post_type, bool $custom_sort, string $loged ) {
		$raw_vehicles = self::apply_vehicle_filters( $raw_vehicles, $post_type );

		if ( $custom_sort ) {
			$sort_rules = $this->get_custom_sort_rules( $post_type );

			if ( ! empty( $sort_rules ) ) {
				$raw_vehicles = $this->apply_custom_sort( $raw_vehicles, $sort_rules );
			}
		}

		// Build VIN → WP permalink map in one DB query.
		$vin_permalink_map = $this->build_vin_permalink_map( $post_type, $raw_vehicles );

		// Get filter term keys configured for this post type.
		$tax_keys = array_keys( wps_tax( $post_type ) );

		$vehicles = array();

		foreach ( $raw_vehicles as $vehicle ) {
			$vin       = strtoupper( $vehicle['vin'] ?? '' );
			$year      = $vehicle['year'] ?? '';
			$make      = $vehicle['make'] ?? '';
			$model     = $vehicle['model'] ?? '';
			$trim      = \App\resolve_vehicle_field( $vehicle['payload'], 'trim' );
			$permalink = $vin_permalink_map[ $vin ] ?? '#';

			// Build terms array for the JS filter engine.
			$terms = $this->build_terms( $vehicle, $tax_keys );

			// Add vin/stock for autocomplete search.
			if ( $vin ) {
				$terms['vin'] = array( $vin );
			}
			if ( ! empty( $vehicle['stock'] ) ) {
				$terms['stock'] = array( $vehicle['stock'] );
			}

			// Render the card HTML.
			ob_start();
			get_template_part(
				'template-parts/content-listings-api',
				null,
				array(
					'vehicle'   => $vehicle,
					'post_type' => $post_type,
					'permalink' => $permalink,
					'loged'     => $loged,
				)
			);
			$html = ob_get_clean();

			$vehicles[] = array(
				'title'   => trim( "{$year} {$make} {$model}" ),
				'link'    => $permalink,
				'search'  => trim( "{$year} {$make} {$model} {$trim}" ),
				'terms'   => $terms,
				'html'    => $html,
				'price'   => (float) ( $this->get_sort_field_value( $vehicle, 'price' ) ?? 0 ),
				'payment' => (float) ( $this->get_sort_field_value( $vehicle, 'loan_payment_sort' ) ?? 0 ),
				'year'    => (string) $year,
				// Resolved directly from payload.use_images_list, same as the
				// rendered card in 'html' above — see \App\resolve_vehicle_gallery().
				'photo'   => \App\resolve_vehicle_gallery( $vehicle )[0]['url'] ?? ( $vehicle['thumb'] ?? ( $vehicle['image'] ?? '' ) ),
			);
		}

		return array(
			'vehicles' => $vehicles,
			'cards'    => $this->build_custom_cards( $post_type ),
		);
	}

	/**
	 * Build the "Custom Cards" list from the Options page repeater
	 * (same source/markup as WP-mode Api::get_vehicles_data()) for
	 * post types shown on this SRP.
	 *
	 * @param string $post_type
	 * @return array
	 */
	private function build_custom_cards( string $post_type ): array {
		$cards = array();

		if ( ! have_rows( 'custom_cards', 'options' ) ) {
			return $cards;
		}

		while ( have_rows( 'custom_cards', 'options' ) ) {
			the_row();

			if ( ! get_sub_field( 'active' ) ) {
				continue;
			}

			$start_date = get_sub_field( 'start_date' );
			$end_date   = get_sub_field( 'end_date' );
			$today      = current_time( 'Ymd' );

			if ( ( $start_date && $today < $start_date ) || ( $end_date && $today > $end_date ) ) {
				continue;
			}

			$show_on = get_sub_field( 'show_on' );

			if ( 'all' !== $show_on && $show_on !== $post_type ) {
				continue;
			}

			ob_start();
			get_template_part( 'template-parts/content-card' );
			$html = ob_get_clean();

			$cards[] = array(
				'position' => get_sub_field( 'show_after' ),
				'html'     => $html,
			);
		}

		return $cards;
	}

	/**
	 * Build a flat, combined new+used vehicles feed from the Nexus API.
	 *
	 * Unlike build_srp_payload(), this returns only scalar fields (no rendered
	 * HTML card, no taxonomy terms) for a lightweight export — mirrors
	 * Api::generate_vehicles_feed_data() in WP mode.
	 *
	 * @return array|\WP_Error
	 */
	private function build_feed_payload() {
		$client = Intice_Api_Client::instance();

		// Fetch new/used separately (same as build_srp_payload()) rather than one
		// unfiltered 'mode=full' call — an unconditioned request returns the full
		// combined inventory in one go, which on a real dealer's data volume can
		// exceed the client's hardcoded 15s HTTP timeout (class-intice-api-client.php).
		// Each condition call reuses the same Layer-1 cache as the existing SRP
		// endpoints, so this is also cheaper once that cache is warm.
		$raw_vehicles = array();
		$last_error   = null;

		foreach ( array( 'new', 'used' ) as $condition ) {
			$api_data = $client->get_vehicles( array( 'condition' => $condition ) );

			if ( is_wp_error( $api_data ) ) {
				$last_error = $api_data;
				continue;
			}

			$raw_vehicles = array_merge( $raw_vehicles, $api_data['data'] ?? array() );
		}

		if ( empty( $raw_vehicles ) && $last_error ) {
			return $last_error;
		}

		return $this->assemble_feed_payload_from( $raw_vehicles );
	}

	/**
	 * Build the flat feed shape from already-fetched new+used vehicle arrays —
	 * pure, no cache/Nexus access, used by handle_rebuild_all() to assemble the
	 * feed from data it already holds in memory.
	 *
	 * @param array $new_vehicles  Raw Intice vehicle arrays, condition=new.
	 * @param array $used_vehicles Raw Intice vehicle arrays, condition=used.
	 * @return array|\WP_Error
	 */
	private function assemble_feed_payload( array $new_vehicles, array $used_vehicles ) {
		return $this->assemble_feed_payload_from( array_merge( $new_vehicles, $used_vehicles ) );
	}

	/**
	 * @param array $raw_vehicles
	 * @return array|\WP_Error
	 */
	private function assemble_feed_payload_from( array $raw_vehicles ) {
		$vehicles = array();

		foreach ( $raw_vehicles as $vehicle ) {
			$vin       = strtoupper( $vehicle['vin'] ?? '' );
			$post_type = 'used' === ( $vehicle['condition'] ?? '' ) ? 'used-listings' : 'listings';

			$vehicles[] = array(
				'vin'        => $vin,
				'year'       => $vehicle['year'] ?? '',
				'make'       => $vehicle['make'] ?? '',
				'model'      => $vehicle['model'] ?? '',
				'trim'       => \App\resolve_vehicle_field( $vehicle, 'trim' ),
				'drivetrain' => \App\resolve_vehicle_field( $vehicle, 'drivetrain' ),
				'body_style' => \App\resolve_vehicle_field( $vehicle, 'body_style' ),
				'price'      => (float) ( $this->get_sort_field_value( $vehicle, 'price' ) ?? 0 ),
				'photo'      => \App\resolve_vehicle_gallery( $vehicle )[0]['url'] ?? ( $vehicle['thumb'] ?? ( $vehicle['image'] ?? '' ) ),
				'link'       => $vin ? Intice_VDP::vdp_url( $vin, $post_type, $vehicle ) : '',
			);
		}

		return array( 'vehicles' => $vehicles );
	}

	/**
	 * Write both the live and stale-fallback transients for an SRP payload.
	 *
	 * @param string $post_type
	 * @param bool   $custom_sort
	 * @param array  $output
	 * @return void
	 */
	private function store_srp_cache( string $post_type, bool $custom_sort, array $output ): void {
		$cache_key = self::cache_key( $post_type, $custom_sort );
		$stale_key = self::stale_cache_key( $post_type, $custom_sort );
		$group     = 'srp_' . $post_type . ( $custom_sort ? '_custom' : '' );

		set_transient( $cache_key, $output, self::CACHE_TTL );
		set_transient( $stale_key, $output, Intice_Api_Client::STALE_TTL );
		Intice_Api_Client::track_key( $cache_key, $group, self::CACHE_TTL );
		Intice_Api_Client::track_key( $stale_key, $group, Intice_Api_Client::STALE_TTL );
	}

	/**
	 * Public entry to store_srp_cache() for callers outside this class
	 * (handle_rebuild_all()'s commit phase).
	 *
	 * @param string $post_type
	 * @param bool   $custom_sort
	 * @param array  $output
	 * @return void
	 */
	public function commit_srp( string $post_type, bool $custom_sort, array $output ): void {
		$this->store_srp_cache( $post_type, $custom_sort, $output );
	}

	/**
	 * Schedule (or reuse an already-pending) background regen for one SRP combo.
	 *
	 * @param string $post_type
	 * @param bool   $custom_sort
	 * @return void
	 */
	private function schedule_srp_regen( string $post_type, bool $custom_sort ): void {
		$args = array( $post_type, $custom_sort );

		if ( ! wp_next_scheduled( 'intice_srp_cache_regen', $args ) ) {
			wp_schedule_single_event( time(), 'intice_srp_cache_regen', $args );
		}

		spawn_cron();
	}

	/**
	 * Proxy GET /wp-json/v1/intice/meta
	 *
	 * Returns Intice filter metadata (makes, models, year/price ranges, conditions).
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_meta( \WP_REST_Request $request ): \WP_REST_Response {
		$client = Intice_Api_Client::instance();
		$meta   = $client->get_meta();

		if ( is_wp_error( $meta ) ) {
			return new \WP_REST_Response( array( 'error' => $meta->get_error_message() ), 502 );
		}

		return rest_ensure_response( $meta );
	}

	// ─── Private helpers ──────────────────────────────────────────────────────

	/**
	 * Build a VIN → permalink map for all vehicles.
	 *
	 * In API mode: returns /post-type/{VIN} URLs directly (no DB query needed,
	 * and works even after WP posts are deleted).
	 *
	 * In WP mode (fallback): looks up WP post permalinks via a single meta query.
	 *
	 * @param string $post_type
	 * @param array  $vehicles
	 * @return array<string,string>
	 */
	private function build_vin_permalink_map( string $post_type, array $vehicles ): array {
		$vins = array_filter( array_column( $vehicles, 'vin' ) );

		if ( empty( $vins ) ) {
			return array();
		}

		// API mode: generate VIN-based URLs directly — no WP post lookup required.
		if ( \App\is_api_mode() ) {
			// Index vehicles by VIN for O(1) lookup.
			$by_vin = array();
			foreach ( $vehicles as $v ) {
				$v_vin = strtoupper( $v['vin'] ?? '' );
				if ( $v_vin ) {
					$by_vin[ $v_vin ] = $v;
				}
			}

			$map = array();
			foreach ( $vins as $vin ) {
				$vin         = strtoupper( $vin );
				$map[ $vin ] = \App\Components\Api\Intice_VDP::vdp_url( $vin, $post_type, $by_vin[ $vin ] ?? array() );
			}
			return $map;
		}

		// WP mode fallback: find existing posts by VIN meta.
		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => 'vin_number',
						'value'   => array_map( 'strtoupper', $vins ),
						'compare' => 'IN',
					),
				),
			)
		);

		$map = array();
		foreach ( $posts as $post_id ) {
			$vin = strtoupper( get_post_meta( $post_id, 'vin_number', true ) );
			if ( $vin ) {
				$map[ $vin ] = get_permalink( $post_id );
			}
		}
		wp_reset_postdata();

		return $map;
	}

	/**
	 * Build a terms array for the JS filter engine from an Intice vehicle array.
	 *
	 * Iterates the wps_tax keys for this post type and maps each to the
	 * corresponding Intice API field via TERM_MAP.
	 *
	 * @param array    $vehicle   Intice vehicle data array.
	 * @param string[] $tax_keys  Filter term keys from wps_tax().
	 * @return array
	 */
	private function build_terms( array $vehicle, array $tax_keys ): array {
		$terms   = array();
		$payload = $vehicle['payload'] ?? array();

		foreach ( $tax_keys as $key ) {
			// "Key Features" is multi-value, not a scalar field — VehicleApiResource
			// groups it by heading: [{heading, features:[{feature, ranking, id}, ...]}].
			// Mirrors the legacy WP-mode flattening in class-api.php (is_array($field)
			// && $key == 'features' → array of $item['feature']) so the SRP filter
			// checkbox list gets the same flat list of feature names either way.
			if ( 'features' === $key && ! empty( $vehicle['features'] ) && is_array( $vehicle['features'] ) ) {
				$flat = array();
				foreach ( $vehicle['features'] as $group ) {
					foreach ( $group['features'] ?? array() as $item ) {
						if ( ! empty( $item['feature'] ) ) {
							$flat[] = (string) $item['feature'];
						}
					}
				}

				if ( ! empty( $flat ) ) {
					$terms[ $key ] = $flat;
				}

				continue;
			}

			// Strip post-type suffixes (e.g. 'make-listings' → 'make').
			$base = preg_replace( '/-(?:listings|used-listings)$/', '', $key );
			// Also try underscore variant (body_style vs body-style).
			$base_underscore = str_replace( '-', '_', $base );

			// Check the dealer-mapped `payload` bag first — dealer edits/overrides
			// are merged into `payload` server-side (see
			// Intice_Api_Client::update_vehicle()), so it takes precedence over
			// the raw top-level Nexus field (mirrors resolve_vehicle_field()).
			$value = $payload[ $base ]
				?? $payload[ $base_underscore ]
				?? $vehicle[ $base ]
				?? $vehicle[ $base_underscore ]
				?? null;

			// Cast before checking emptiness — a boolean `false` (e.g. certified)
			// fails the strict `!== ''` check against the raw value (different
			// types never compare equal) and slips through, only to cast to an
			// empty string and produce a blank, unlabeled filter option.
			$string_value = $value !== null ? (string) $value : null;

			if ( $string_value !== null && $string_value !== '' ) {
				$terms[ $key ] = array( $string_value );
			}
		}

		// Always include condition and certified for filter compatibility.
		if ( ! empty( $vehicle['condition'] ) && ! isset( $terms['condition'] ) ) {
			$terms['condition'] = array( (string) $vehicle['condition'] );
		}

		// Prefer payload.certified (raw import value) over the top-level field —
		// some dealer feeds map the source column's actual certification
		// program name (e.g. "Honda Certified", "GM Certified Pre-Owned") into
		// payload, while Nexus's top-level `certified` is always a plain
		// boolean. The JS filter engine renders term values verbatim as the
		// filter option label (see getAllFilters() in app.js), so casting a
		// boolean straight to string produced "1"/"" as the visible label —
		// use the real program name when the source gives us one, and only
		// fall back to a generic "Certified" label when all we have is a
		// true/false flag.
		$certified = $payload['certified'] ?? ( $vehicle['certified'] ?? null );
		if ( $certified !== null ) {
			$certified_string = trim( (string) $certified );
			$is_flag_value    = in_array( strtolower( $certified_string ), array( '', '0', '1', 'true', 'false', 'yes', 'no' ), true );

			if ( $is_flag_value ) {
				if ( filter_var( $certified, FILTER_VALIDATE_BOOLEAN ) ) {
					$terms['certified'] = array( 'Certified' );
				}
			} else {
				$terms['certified'] = array( $certified_string );
			}
		}

		// Safety net: strip any null/empty entries that slipped through above so the
		// JS filter engine (which calls .toLowerCase() on every term value) never
		// chokes on a null.
		foreach ( $terms as $key => $values ) {
			$terms[ $key ] = array_values(
				array_filter(
					$values,
					static function ( $value ) {
						return $value !== null && $value !== '';
					}
				)
			);

			if ( empty( $terms[ $key ] ) ) {
				unset( $terms[ $key ] );
			}
		}

		return $terms;
	}

	/**
	 * Read the "Custom sort" ACF repeater rules for a post type (Options page).
	 *
	 * Same source used by the WP-mode sort in class-api.php, so the rules
	 * configured in SOC/Theme Options apply identically in API mode.
	 *
	 * @param string $post_type
	 * @return array<int,array{field:string,order:string,numeric:bool}>
	 */
	private function get_custom_sort_rules( string $post_type ): array {
		$sort_rules = array();

		if ( have_rows( 'custom_sort_' . $post_type, 'option' ) ) {
			while ( have_rows( 'custom_sort_' . $post_type, 'option' ) ) {
				the_row();

				$field = get_sub_field( 'field' );
				$order = strtoupper( get_sub_field( 'order' ) ) === 'DESC' ? 'DESC' : 'ASC';

				if ( $field ) {
					$field        = 'price_sort' === $field ? 'price' : $field;
					$sort_rules[] = array(
						'field'   => $field,
						'order'   => $order,
						'numeric' => in_array( $field, array( 'price', 'mileage', 'year', 'dealer_special' ), true ),
					);
				}
			}
		}

		return $sort_rules;
	}

	/**
	 * Resolve a sort field's value from a raw Intice vehicle array.
	 *
	 * Checks the top-level vehicle fields first, then falls back to the
	 * dealer-mapped `payload` bag (engine, loan_payment_sort, dateinstock, etc.
	 * live there rather than at the top level). A top-level value of 0/''
	 * is treated as "not usable" rather than "present" — some dealer import
	 * mappings leave a top-level column dead (e.g. price_sort=0) while the
	 * real value still lives in payload, so an isset-only check (`??`) would
	 * lock in the dead value and never look further.
	 *
	 * @param array  $vehicle
	 * @param string $field
	 * @return mixed
	 */
	private static function get_sort_field_value( array $vehicle, string $field ) {
		$payload = $vehicle['payload'] ?? array();

		switch ( $field ) {
			case 'price':
				return self::first_usable(
					array(
						$payload['price_sort'] ?? null,
						$payload['price'] ?? null,
					)
				);
			case 'original_price':
				return self::first_usable(
					array(
						$vehicle['original_price'] ?? null,
						$payload['original_price'] ?? null,
						$payload['msrp'] ?? null,
					)
				);
			case 'vehicle-status':
				// Nexus exposes this as top-level 'status' (aliased from the
				// 'vehicle_status' column) and, if a dealer template maps it,
				// as payload['vehicle_status'] — 'vehicle-status' (hyphen) is
				// only ever a legacy/manual payload key from older imports.
				return self::first_usable(
					array(
						$vehicle['status'] ?? null,
						$payload['vehicle_status'] ?? null,
						$payload['vehicle-status'] ?? null,
					)
				);
			case 'dateinstock':
				// Nexus's real field is 'date_in_stock' (DB column + payload
				// fallback key) — 'dateinstock' is only a CSV-import alias,
				// never the field name data actually lives under.
				return self::first_usable(
					array(
						$vehicle['date_in_stock'] ?? null,
						$payload['date_in_stock'] ?? null,
						$payload['dateinstock'] ?? null,
					)
				);
			case 'loan_payment_sort':
				return self::first_usable(
					array(
						$payload['loan_payment_sort'] ?? null,
						$payload['loan_payment'] ?? null,
					)
				);
			case 'certified':
				// Mirror build_terms()'s priority so a "certified" filter/sort rule
				// matches the same value the SRP filter facet shows the user: the
				// payload's certification program name (e.g. "Acura Certified")
				// takes priority over the top-level field.
				return self::first_usable(
					array(
						$payload['certified'] ?? null,
						$vehicle['certified'] ?? null,
					)
				);
			default:
				return self::first_usable(
					array(
						$vehicle[ $field ] ?? null,
						$payload[ $field ] ?? null,
					)
				);
		}
	}

	/**
	 * Return the first non-empty candidate, or null if all are empty.
	 *
	 * @param array $candidates
	 * @return mixed
	 */
	private static function first_usable( array $candidates ) {
		foreach ( $candidates as $candidate ) {
			if ( ! empty( $candidate ) ) {
				return $candidate;
			}
		}

		return null;
	}

	/**
	 * Apply multi-level custom sort rules to a raw Intice vehicles array.
	 *
	 * Mirrors the usort logic in class-api.php::generate_vehicles_data_new()
	 * so "Custom sort" behaves identically whether vehicles come from WP
	 * posts or the Nexus API.
	 *
	 * @param array $vehicles   Raw vehicle arrays from Intice_Api_Client::get_vehicles().
	 * @param array $sort_rules Rules from get_custom_sort_rules().
	 * @return array
	 */
	private function apply_custom_sort( array $vehicles, array $sort_rules ): array {
		if ( empty( $sort_rules ) || empty( $vehicles ) ) {
			return $vehicles;
		}

		$normalize = function ( $value, $numeric ) {
			if ( $value === null || $value === '' ) {
				return $numeric ? INF : '';
			}

			if ( $numeric ) {
				return (float) preg_replace( '/[^0-9.-]/', '', (string) $value );
			}

			return mb_strtolower( (string) $value );
		};

		usort(
			$vehicles,
			function ( $a, $b ) use ( $sort_rules, $normalize ) {
				foreach ( $sort_rules as $rule ) {
					$field   = $rule['field'];
					$order   = $rule['order'];
					$numeric = $rule['numeric'];

					$val_a = $normalize( $this->get_sort_field_value( $a, $field ), $numeric );
					$val_b = $normalize( $this->get_sort_field_value( $b, $field ), $numeric );

					if ( $val_a == $val_b ) {
						continue;
					}

					if ( 'ASC' === $order ) {
						return $val_a <=> $val_b;
					}

					return $val_b <=> $val_a;
				}

				return 0;
			}
		);

		return $vehicles;
	}

	/**
	 * Exclude vehicles that don't match every configured filter row for this post type
	 * (rows are ANDed together). Rows are configured in SOC → API Settings → Filters.
	 *
	 * Public/static so callers that need the *real* (post-exclusion) vehicle count
	 * outside the SRP request lifecycle — e.g. get_listings_count() for the ACF
	 * model-slider count badge — apply the exact same rules instead of drifting out
	 * of sync with what the results grid actually shows.
	 *
	 * @param array  $vehicles  Raw vehicle arrays from Intice_Api_Client::get_vehicles().
	 * @param string $post_type 'listings' or 'used-listings'.
	 * @return array
	 */
	public static function apply_vehicle_filters( array $vehicles, string $post_type ): array {
		$rows = Api_Settings::get_active_filters( $post_type );

		if ( empty( $rows ) || empty( $vehicles ) ) {
			return $vehicles;
		}

		return array_values(
			array_filter(
				$vehicles,
				function ( $vehicle ) use ( $rows ) {
					foreach ( $rows as $row ) {
						if ( ! self::filter_row_matches( $vehicle, $row ) ) {
							return false;
						}
					}
					return true;
				}
			)
		);
	}

	/**
	 * Evaluate a single filter row against one vehicle.
	 *
	 * Public/static so callers (e.g. get_listings_count() for the ACF model-slider
	 * count badge) can match arbitrary ad-hoc rows (year/make/model/trim equality)
	 * against an already-fetched vehicle list instead of issuing a fresh Nexus
	 * request per slide.
	 *
	 * @param array $vehicle Raw Intice vehicle array.
	 * @param array $row     {field, custom_key, operator, value}.
	 * @return bool
	 */
	public static function filter_row_matches( array $vehicle, array $row ): bool {
		$field = 'custom' === $row['field'] ? $row['custom_key'] : $row['field'];
		$field = 'price_sort' === $field ? 'price' : $field;

		$actual   = self::get_sort_field_value( $vehicle, $field );
		$operator = $row['operator'];
		$expected = $row['value'];

		if ( in_array( $operator, array( '>=', '<=', '>', '<' ), true ) ) {
			$actual_num   = (float) preg_replace( '/[^0-9.-]/', '', (string) $actual );
			$expected_num = (float) preg_replace( '/[^0-9.-]/', '', (string) $expected );

			switch ( $operator ) {
				case '>=':
					return $actual_num >= $expected_num;
				case '<=':
					return $actual_num <= $expected_num;
				case '>':
					return $actual_num > $expected_num;
				case '<':
					return $actual_num < $expected_num;
			}
		}

		if ( 'empty' === $operator || 'not_empty' === $operator ) {
			$is_empty = '' === trim( (string) $actual );
			return 'empty' === $operator ? $is_empty : ! $is_empty;
		}

		if ( in_array( $operator, array( 'len_eq', 'len_gt', 'len_lt' ), true ) ) {
			$actual_len   = mb_strlen( trim( (string) $actual ) );
			$expected_len = (int) preg_replace( '/[^0-9-]/', '', (string) $expected );

			switch ( $operator ) {
				case 'len_eq':
					return $actual_len === $expected_len;
				case 'len_gt':
					return $actual_len > $expected_len;
				case 'len_lt':
					return $actual_len < $expected_len;
			}
		}

		$matches = mb_strtolower( trim( (string) $actual ) ) === mb_strtolower( trim( (string) $expected ) );

		return '!=' === $operator ? ! $matches : $matches;
	}

	/**
	 * Build the named transient key for a post type / sort-mode combination.
	 *
	 * @param string $post_type   'listings' or 'used-listings'.
	 * @param bool   $custom_sort Whether this is the "custom sort" variant.
	 * @return string
	 */
	public static function cache_key( string $post_type, bool $custom_sort ): string {
		return self::CACHE_PREFIX . $post_type . ( $custom_sort ? '_custom' : '_default' );
	}

	/**
	 * Build the stale-while-revalidate transient key for a post type / sort-mode combination.
	 *
	 * @param string $post_type   'listings' or 'used-listings'.
	 * @param bool   $custom_sort Whether this is the "custom sort" variant.
	 * @return string
	 */
	public static function stale_cache_key( string $post_type, bool $custom_sort ): string {
		return self::CACHE_PREFIX_STALE . $post_type . ( $custom_sort ? '_custom' : '_default' );
	}

	/**
	 * Clear the assembled SRP cache for a post type / sort-mode combination, using the
	 * same stale-while-revalidate pattern as Intice_Api_Client::flush_cache(): the current
	 * value is preserved as a 2h stale copy (so the site keeps serving it) while a
	 * background cron rebuilds the live cache.
	 *
	 * @param string $post_type   'listings' or 'used-listings'.
	 * @param bool   $custom_sort Whether to clear the "custom sort" variant.
	 * @return int 1 if a transient was deleted, 0 otherwise.
	 */
	public static function clear_cache( string $post_type, bool $custom_sort ): int {
		$cache_key = self::cache_key( $post_type, $custom_sort );
		$stale_key = self::stale_cache_key( $post_type, $custom_sort );
		$group     = 'srp_' . $post_type . ( $custom_sort ? '_custom' : '' );

		$value = get_transient( $cache_key );

		if ( false !== $value ) {
			set_transient( $stale_key, $value, Intice_Api_Client::STALE_TTL );
			Intice_Api_Client::track_key( $stale_key, $group, Intice_Api_Client::STALE_TTL );
		}

		$deleted = delete_transient( $cache_key ) ? 1 : 0;
		Intice_Api_Client::untrack_key( $cache_key );

		$args = array( $post_type, $custom_sort );

		if ( ! wp_next_scheduled( 'intice_srp_cache_regen', $args ) ) {
			wp_schedule_single_event( time(), 'intice_srp_cache_regen', $args );
		}

		spawn_cron();

		return $deleted;
	}

	/**
	 * Whether the assembled SRP response should be cached.
	 *
	 * Shares the same on/off toggle as Intice_Api_Client's raw API cache.
	 *
	 * @return bool
	 */
	private function is_cache_enabled(): bool {
		return (bool) get_option( Api_Settings::OPTION_CACHE_ENABLED, true );
	}
}

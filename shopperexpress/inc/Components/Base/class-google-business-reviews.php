<?php
/**
 * Google Reviews — Business Profile API (paginated) with Places API (New) fallback.
 *
 * Primary source: Google Business Profile ("My Business") API v4 reviews,
 * reached via an OAuth2-connected app + a specific account/location. This
 * supports real pagination (up to 50 reviews per page).
 *
 * Fallback source (used when no Business Profile connection is configured):
 * Places API (New) places.get with a field mask. Google caps place.reviews
 * at 5 entries and never returns a page token for them — there is no
 * server-side pagination to build there, by Google's design.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class Google_Business_Reviews
 *
 * @package App\Components\Base
 */
class Google_Business_Reviews implements Theme_Component {

	const OPTION_CLIENT_ID     = 'google_reviews_client_id';
	const OPTION_CLIENT_SECRET = 'google_reviews_client_secret';
	const OPTION_ACCOUNT_ID    = 'google_reviews_account_id';
	const OPTION_LOCATION_ID   = 'google_reviews_location_id';
	const OPTION_PLACE_ID      = 'google_reviews_place_id';
	const OPTION_PLACE_CID     = 'google_reviews_place_cid';
	const OPTION_REFRESH_TOKEN = 'google_reviews_refresh_token';
	const OPTION_PLACES_KEY    = 'google_places_api_key';
	const OPTION_SALT          = 'google_reviews_key_salt';

	const OAUTH_SCOPE = 'https://www.googleapis.com/auth/business.manage';

	const TOKEN_TRANSIENT    = 'google_reviews_access_token';
	const BUSINESS_CACHE_TTL = 15 * MINUTE_IN_SECONDS;
	const PLACES_CACHE_TTL   = DAY_IN_SECONDS;
	const PLACES_API_BASE    = 'https://places.googleapis.com/v1/places/';
	const PLACES_FIELD_MASK  = 'id,rating,userRatingCount,reviews';
	const PLACE_ID_REGEX     = '/^[A-Za-z0-9_-]{10,255}$/';

	// Safety cap on how many extra Business Profile API pages get_reviews()
	// will auto-fetch chasing a caller-supplied $min_count, so a huge/odd
	// requested count can't turn one request into an unbounded fetch loop.
	const MAX_AUTO_FETCH_PAGES = 5;

	// When a $keyword filter is set but the caller didn't request a specific
	// $min_count, still auto-fetch pages (up to MAX_AUTO_FETCH_PAGES) until
	// at least this many keyword matches are found — a single 20-review page
	// often contains zero matches for a narrow phrase even when the location
	// has plenty of them further back.
	const MIN_KEYWORD_RESULTS = 5;

	// Background full-history cache (see Google_Reviews_Cron / sync_all_reviews()).
	// A specific phrase like "oil change" can legitimately not appear anywhere
	// in the newest ~120 reviews (the live on-demand pagination cap below) even
	// though the location has dozens of matches further back in its history —
	// generic keywords like "service" happen to show up early just because
	// they're common words, giving the false impression that filtering "works"
	// for some keywords and not others. The cron keeps a full snapshot of every
	// review so keyword filtering can search the whole history instead.
	const FULL_CACHE_OPTION         = 'google_reviews_full_cache';
	const FULL_CACHE_UPDATED_OPTION = 'google_reviews_full_cache_updated';
	const FULL_SYNC_MAX_PAGES       = 100;

	// Guards the "Sync Now" button (see start_background_sync()) against being
	// fired twice while a sync is already running in the background — the sync
	// itself can take a couple of minutes (up to 100 sequential Google API
	// calls), and it runs via a scheduled WP Cron event so the admin is free to
	// navigate away instead of waiting on the request. The TTL is a safety net
	// that self-clears the lock if a sync ever dies without reaching the
	// Google_Reviews_Cron::run() cleanup (e.g. a fatal error or a killed request).
	const SYNC_LOCK_TRANSIENT = 'google_reviews_sync_lock';
	const SYNC_LOCK_TTL       = 15 * MINUTE_IN_SECONDS;

	// Minimum star rating a review must have to be surfaced by filter_reviews()
	// — configurable from the SOC panel instead of the hardcoded "5 stars only"
	// this started as. Clamped to 1-5.
	const OPTION_MIN_RATING = 'google_reviews_min_rating';

	/**
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	// -------------------------------------------------------------------------
	// Settings — consumed by the SOC "Google Reviews" module/view.
	// -------------------------------------------------------------------------

	/**
	 * @return array
	 */
	public function get_settings(): array {
		$places_key = $this->get_places_api_key();

		return array(
			'client_id'         => get_option( self::OPTION_CLIENT_ID, '' ),
			'client_secret'     => $this->get_client_secret(),
			'has_secret'        => '' !== get_option( self::OPTION_CLIENT_SECRET, '' ),
			'account_id'        => get_option( self::OPTION_ACCOUNT_ID, '' ),
			'location_id'       => get_option( self::OPTION_LOCATION_ID, '' ),
			'place_id'          => get_option( self::OPTION_PLACE_ID, '' ),
			'is_connected'      => $this->is_connected(),
			'oauth_start_url'   => rest_url( 'v1/google-reviews/oauth/start' ),
			'redirect_uri'      => rest_url( 'v1/google-reviews/oauth/callback' ),
			'places_key_set'    => '' !== $places_key,
			'places_key_masked' => $this->mask_key( $places_key ),
			'full_cache_count'  => is_array( get_option( self::FULL_CACHE_OPTION, null ) ) ? count( get_option( self::FULL_CACHE_OPTION, array() ) ) : null,
			'full_cache_synced' => (int) get_option( self::FULL_CACHE_UPDATED_OPTION, 0 ),
			'sync_in_progress'  => $this->is_sync_running(),
			'min_rating'        => $this->get_min_rating(),
		);
	}

	/**
	 * @return int Minimum star rating (1-5) a review must have to be shown/schema'd.
	 */
	public function get_min_rating(): int {
		return max( 1, min( 5, (int) get_option( self::OPTION_MIN_RATING, 5 ) ) );
	}

	/**
	 * @param int $rating Minimum star rating (1-5) a review must have to be shown/schema'd.
	 * @return void
	 */
	public function save_min_rating( int $rating ): void {
		update_option( self::OPTION_MIN_RATING, max( 1, min( 5, $rating ) ) );
		$this->flush_live_cache();
	}

	/**
	 * Save the OAuth client credentials.
	 *
	 * @param string $client_id     OAuth client ID.
	 * @param string $client_secret OAuth client secret — pass '' to keep the existing stored value.
	 * @return void
	 */
	public function save_oauth_client( string $client_id, string $client_secret ): void {
		update_option( self::OPTION_CLIENT_ID, sanitize_text_field( $client_id ) );

		if ( '' !== $client_secret ) {
			update_option( self::OPTION_CLIENT_SECRET, $this->encrypt( $client_secret ) );
		}
	}

	/**
	 * Save the chosen Business Profile account/location (picked from the discovery dropdowns).
	 *
	 * @param string $account_id  Business Profile account resource name (e.g. "accounts/1234567890").
	 * @param string $location_id Business Profile location resource name (e.g. "locations/1234567890").
	 * @return void
	 */
	public function save_account_location( string $account_id, string $location_id ): void {
		update_option( self::OPTION_ACCOUNT_ID, sanitize_text_field( $account_id ) );
		update_option( self::OPTION_LOCATION_ID, sanitize_text_field( $location_id ) );

		$place_id = $this->fetch_location_place_id( $location_id );
		update_option( self::OPTION_PLACE_ID, is_wp_error( $place_id ) ? '' : $place_id );
		delete_option( self::OPTION_PLACE_CID );

		$this->flush_reviews_cache();

		if ( ! wp_next_scheduled( Google_Reviews_Cron::HOOK ) ) {
			wp_schedule_event( time(), 'hourly', Google_Reviews_Cron::HOOK );
		}

		// Populate the full-history keyword-search cache right away instead of
		// leaving keyword-filtered widgets empty until the next hourly cron run.
		// flush_reviews_cache() just cleared any stale lock's cache, but not the
		// lock transient itself — a manual sync could theoretically be running
		// at this exact moment, so still go through the guarded entry point
		// rather than scheduling a duplicate run directly.
		$this->start_background_sync();
	}

	/**
	 * Look up the Place ID backing a Business Profile location, so the "Review
	 * us on Google" / "Show More Reviews" links keep working without asking
	 * anyone to type a Place ID in by hand.
	 *
	 * @param string $location_id Business Profile location resource name (e.g. "locations/1234567890").
	 * @return string|\WP_Error
	 */
	private function fetch_location_place_id( string $location_id ) {
		if ( ! preg_match( '#^locations/[0-9]+$#', $location_id ) ) {
			return new \WP_Error( 'invalid_location_id', __( 'Invalid Business Profile location ID.', 'shopperexpress' ) );
		}

		$access_token = $this->get_access_token();
		if ( is_wp_error( $access_token ) ) {
			return $access_token;
		}

		$url = sprintf(
			'https://mybusinessbusinessinformation.googleapis.com/v1/%s?readMask=metadata',
			$location_id
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array( 'Authorization' => 'Bearer ' . $access_token ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			return new \WP_Error( 'location_get_error', $body['error']['message'] ?? "HTTP {$code}" );
		}

		return $body['metadata']['placeId'] ?? '';
	}

	/**
	 * @param string $api_key Places API key — pass '' to keep the existing stored value.
	 * @return void
	 */
	public function save_places_api_key( string $api_key ): void {
		if ( '' === $api_key ) {
			return;
		}
		update_option( self::OPTION_PLACES_KEY, $this->encrypt( $api_key ) );
	}

	/**
	 * Disconnect — forget the stored refresh token and chosen account/location.
	 *
	 * @return void
	 */
	public function disconnect(): void {
		delete_option( self::OPTION_REFRESH_TOKEN );
		delete_option( self::OPTION_ACCOUNT_ID );
		delete_option( self::OPTION_LOCATION_ID );
		delete_option( self::OPTION_PLACE_ID );
		delete_option( self::OPTION_PLACE_CID );
		delete_transient( self::TOKEN_TRANSIENT );
		$this->flush_reviews_cache();
	}

	/**
	 * Forget every live per-request reviews cache entry (REST proxy responses)
	 * so a setting change (source switch, min rating, etc.) takes effect
	 * immediately instead of waiting out the old cache's TTL (up to 24h).
	 * Does NOT touch the full-history sync cache (see flush_reviews_cache()) —
	 * that snapshot is expensive to rebuild (up to 100 Google API calls) and
	 * stays valid across settings that only change how it's filtered.
	 *
	 * @return void
	 */
	private function flush_live_cache(): void {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_google_reviews_data_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_google_reviews_data_' ) . '%'
			)
		);

		// The direct DELETE above only clears the DB row — on sites running a
		// persistent object cache (Redis/Memcached), get_transient() would keep
		// serving the stale value from cache until it naturally evicts. Flush
		// so the change takes effect immediately.
		wp_cache_flush();
	}

	/**
	 * Forget the live cache AND the full-history sync cache — used when the
	 * data source itself changes (account/location, disconnect) since the
	 * previous location's cached reviews are no longer relevant.
	 *
	 * @return void
	 */
	private function flush_reviews_cache(): void {
		$this->flush_live_cache();
		delete_option( self::FULL_CACHE_OPTION );
		delete_option( self::FULL_CACHE_UPDATED_OPTION );
	}

	/**
	 * Test the connection by requesting a single review for the given place.
	 *
	 * @param string $place_id Google Place ID to test against (used for the Places API fallback path).
	 * @return array{ok: bool, error?: string, source?: string, rating?: float, total_review_count?: int}
	 */
	public function test_connection( string $place_id ): array {
		if ( '' === $place_id ) {
			$place_id = get_option( self::OPTION_PLACE_ID, '' );
		}

		$data = $this->get_reviews( $place_id );

		if ( is_wp_error( $data ) ) {
			return array(
				'ok'    => false,
				'error' => $data->get_error_message(),
			);
		}

		return array(
			'ok'                 => true,
			'source'             => $data['source'],
			'rating'             => $data['average_rating'],
			'total_review_count' => $data['total_review_count'],
		);
	}

	/**
	 * @return bool Whether a refresh token is stored.
	 */
	public function is_connected(): bool {
		return '' !== get_option( self::OPTION_REFRESH_TOKEN, '' );
	}

	// -------------------------------------------------------------------------
	// Discovery — list the accounts/locations reachable by the connected OAuth app.
	// -------------------------------------------------------------------------

	/**
	 * @return array<int, array{id: string, name: string}>|\WP_Error
	 */
	public function list_accounts() {
		$access_token = $this->get_access_token();
		if ( is_wp_error( $access_token ) ) {
			return $access_token;
		}

		$response = wp_remote_get(
			'https://mybusinessaccountmanagement.googleapis.com/v1/accounts',
			array(
				'timeout' => 15,
				'headers' => array( 'Authorization' => 'Bearer ' . $access_token ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			return new \WP_Error( 'accounts_api_error', $body['error']['message'] ?? "HTTP {$code}" );
		}

		$accounts = array();
		foreach ( $body['accounts'] ?? array() as $account ) {
			$accounts[] = array(
				'id'   => $account['name'] ?? '',
				'name' => $account['accountName'] ?? ( $account['name'] ?? '' ),
			);
		}

		return $accounts;
	}

	/**
	 * @param string $account_id Account resource name (e.g. "accounts/1234567890").
	 * @return array<int, array{id: string, name: string}>|\WP_Error
	 */
	public function list_locations( string $account_id ) {
		if ( ! preg_match( '#^accounts/[0-9]+$#', $account_id ) ) {
			return new \WP_Error( 'invalid_account_id', __( 'Invalid Business Profile account ID.', 'shopperexpress' ) );
		}

		$access_token = $this->get_access_token();
		if ( is_wp_error( $access_token ) ) {
			return $access_token;
		}

		// Note: $account_id (e.g. "accounts/123") is a resource path and must
		// keep its literal "/" — rawurlencode()'ing the whole string turns it
		// into "accounts%2F123", which Google's API responds to with a 404.
		$url = sprintf(
			'https://mybusinessbusinessinformation.googleapis.com/v1/%s/locations?readMask=name,title&pageSize=100',
			$account_id
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array( 'Authorization' => 'Bearer ' . $access_token ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			return new \WP_Error( 'locations_api_error', $body['error']['message'] ?? "HTTP {$code}" );
		}

		$locations = array();
		foreach ( $body['locations'] ?? array() as $location ) {
			$locations[] = array(
				'id'   => $location['name'] ?? '',
				'name' => $location['title'] ?? ( $location['name'] ?? '' ),
			);
		}

		return $locations;
	}

	// -------------------------------------------------------------------------
	// REST routes
	// -------------------------------------------------------------------------

	/**
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			'v1',
			'/google-reviews',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_reviews' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					// Optional: falls back to the Place ID resolved from the
					// connected Business Profile location when omitted.
					'place_id'   => array(
						'required'          => false,
						'validate_callback' => function ( $value ) {
							return '' === $value || ( is_string( $value ) && preg_match( self::PLACE_ID_REGEX, $value ) );
						},
					),
					'page_token' => array( 'required' => false ),
					'lang'       => array( 'required' => false ),
					'keyword'    => array( 'required' => false ),
					'min_count'  => array( 'required' => false ),
				),
			)
		);

		register_rest_route(
			'v1',
			'/google-reviews/oauth/start',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_oauth_start' ),
				// Public on purpose: this link is meant to be sent to and opened
				// directly by the Business Profile owner/manager, who is not a
				// WP user. It only redirects to Google's own consent screen —
				// it never exposes the client secret or any stored token.
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'v1',
			'/google-reviews/oauth/callback',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_oauth_callback' ),
				// Public on purpose: Google redirects the *approver's* browser here
				// (the Business Profile owner/manager, not necessarily a WP user).
				// The one-time state token minted in rest_oauth_start() and
				// checked here still rejects stale/forged/replayed callbacks.
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * GET /wp-json/v1/google-reviews?place_id=...&page_token=...&lang=...
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function rest_get_reviews( \WP_REST_Request $request ): \WP_REST_Response {
		$place_id = sanitize_text_field( (string) $request->get_param( 'place_id' ) );
		if ( '' === $place_id ) {
			$place_id = get_option( self::OPTION_PLACE_ID, '' );
		}

		$page_token = sanitize_text_field( (string) $request->get_param( 'page_token' ) );
		$lang       = sanitize_text_field( (string) $request->get_param( 'lang' ) );
		$keyword    = sanitize_text_field( (string) $request->get_param( 'keyword' ) );
		$min_count  = (int) $request->get_param( 'min_count' );

		$cache_key = 'google_reviews_data_' . md5( $place_id . '|' . $page_token . '|' . $lang . '|' . $keyword . '|' . $min_count );

		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return rest_ensure_response( $cached );
		}

		$data = $this->get_reviews( $place_id, $page_token, $lang, $keyword, $min_count );

		if ( is_wp_error( $data ) ) {
			return new \WP_REST_Response( array( 'error' => $data->get_error_message() ), 502 );
		}

		$ttl = 'business_profile' === $data['source'] ? self::BUSINESS_CACHE_TTL : self::PLACES_CACHE_TTL;
		set_transient( $cache_key, $data, $ttl );

		return rest_ensure_response( $data );
	}

	/**
	 * GET /wp-json/v1/google-reviews/oauth/start — redirect to Google's consent screen.
	 *
	 * @return void
	 */
	public function rest_oauth_start(): void {
		$client_id = get_option( self::OPTION_CLIENT_ID, '' );

		if ( '' === $client_id ) {
			wp_safe_redirect( add_query_arg( 'oauth_error', 'Client ID is not configured.', $this->soc_page_url() ) );
			exit;
		}

		// One-time token: only an admin can mint it (this route requires
		// manage_options), but whoever holds the resulting link can complete
		// the flow — the Business Profile owner/manager is usually not a WP user.
		$state = wp_generate_password( 32, false );
		set_transient( 'google_reviews_oauth_state_' . $state, 1, 30 * MINUTE_IN_SECONDS );

		$params = array(
			'client_id'     => $client_id,
			'redirect_uri'  => rest_url( 'v1/google-reviews/oauth/callback' ),
			'response_type' => 'code',
			'scope'         => self::OAUTH_SCOPE,
			'access_type'   => 'offline',
			'prompt'        => 'consent',
			'state'         => $state,
		);

		wp_redirect( 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query( $params ) );
		exit;
	}

	/**
	 * GET /wp-json/v1/google-reviews/oauth/callback — exchange the auth code for a refresh token.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return void
	 */
	public function rest_oauth_callback( \WP_REST_Request $request ): void {
		$settings_url = $this->soc_page_url();

		$state           = (string) $request->get_param( 'state' );
		$state_transient = 'google_reviews_oauth_state_' . $state;

		if ( '' === $state || false === get_transient( $state_transient ) ) {
			wp_safe_redirect( add_query_arg( 'oauth_error', 'Invalid or expired OAuth state.', $settings_url ) );
			exit;
		}

		// Single-use: burn the token immediately so the link can't be replayed.
		delete_transient( $state_transient );

		$error = (string) $request->get_param( 'error' );
		if ( '' !== $error ) {
			wp_safe_redirect( add_query_arg( 'oauth_error', sanitize_text_field( $error ), $settings_url ) );
			exit;
		}

		$code = (string) $request->get_param( 'code' );
		if ( '' === $code ) {
			wp_safe_redirect( add_query_arg( 'oauth_error', 'Missing authorization code.', $settings_url ) );
			exit;
		}

		$client_id     = get_option( self::OPTION_CLIENT_ID, '' );
		$client_secret = $this->get_client_secret();

		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'timeout' => 15,
				'body'    => array(
					'code'          => $code,
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
					'redirect_uri'  => rest_url( 'v1/google-reviews/oauth/callback' ),
					'grant_type'    => 'authorization_code',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_safe_redirect( add_query_arg( 'oauth_error', rawurlencode( $response->get_error_message() ), $settings_url ) );
			exit;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['refresh_token'] ) ) {
			wp_safe_redirect( add_query_arg( 'oauth_error', rawurlencode( $body['error_description'] ?? 'No refresh token returned by Google.' ), $settings_url ) );
			exit;
		}

		update_option( self::OPTION_REFRESH_TOKEN, $this->encrypt( $body['refresh_token'] ) );
		delete_transient( self::TOKEN_TRANSIENT );

		wp_safe_redirect( add_query_arg( 'connected', '1', $settings_url ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// Reviews — Business Profile primary, Places API (New) fallback.
	// -------------------------------------------------------------------------

	/**
	 * @param string $place_id   Google Place ID (used for the fallback path and CTA links).
	 * @param string $page_token Business Profile pagination token from a prior response.
	 * @param string $lang       BCP-47 language code for the Places API fallback. Defaults to the site locale.
	 * @param string $keyword    Optional comma-separated keyword(s) — only reviews whose text
	 *                            contains at least one of them (case-insensitive) are returned.
	 * @param int    $min_count  Optional. Business Profile source only: keep auto-fetching
	 *                            further pages (up to self::MAX_AUTO_FETCH_PAGES extra) until
	 *                            at least this many filtered reviews are collected, or Google
	 *                            runs out of pages. Each raw page can yield very few (or zero)
	 *                            5-star/has-text reviews, so a single page is often not enough.
	 * @return array{source: string, reviews: array, average_rating: float, total_review_count: int, next_page_token?: string}|\WP_Error
	 */
	public function get_reviews( string $place_id, string $page_token = '', string $lang = '', string $keyword = '', int $min_count = 0 ) {
		if ( ! preg_match( self::PLACE_ID_REGEX, $place_id ) ) {
			return new \WP_Error( 'invalid_place_id', __( 'Invalid Google Place ID.', 'shopperexpress' ) );
		}

		$use_business_profile = $this->is_connected() && get_option( self::OPTION_ACCOUNT_ID, '' ) && get_option( self::OPTION_LOCATION_ID, '' );

		if ( $use_business_profile ) {
			$data = $this->get_reviews_business_profile( $page_token );
		} else {
			$data = $this->get_reviews_places( $place_id, $lang );
		}

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Only surface 5-star reviews that have written text — matches what
		// gets rendered in the widget and what backs the Review JSON-LD.
		// average_rating/total_review_count are left untouched: those are
		// Google's real aggregate stats for the business, not a count of
		// what's displayed in the list below.
		$data['reviews'] = $this->filter_reviews( $data['reviews'], $keyword );

		// A keyword filter needs to search the *whole* review history, not
		// just the handful of pages the live on-demand fetch below is willing
		// to chase — that fetch is capped low to keep page loads fast, which
		// is fine for the general "show me some 5-star reviews" case but too
		// shallow for a narrow phrase like "oil change". When the background
		// cron (see sync_all_reviews()) has already built a full snapshot,
		// use it instead so the filter covers every review Google has.
		if ( $use_business_profile && '' !== $keyword ) {
			$full_cache = $this->get_full_cache();
			if ( null !== $full_cache ) {
				$data['reviews'] = $this->filter_reviews( $full_cache, $keyword );
				return $data;
			}
		}

		// Business Profile pages are 20 raw reviews each, most of which the
		// filter above can drop (not 5-star, or no text) — a single page can
		// legitimately filter down to just 1-2 results even when the location
		// has plenty of qualifying reviews further back. Keep pulling pages
		// until the caller's desired count is met instead of surfacing a
		// near-empty first page and relying on "Load More" to fill it in.
		//
		// A keyword filter narrows things further still — a specific phrase
		// like "oil change" may not appear in any of the 20 *most recent*
		// reviews even though the location has plenty of matches further
		// back, so a page's worth of matches can legitimately be zero. When
		// no explicit $min_count was requested, still search a handful of
		// pages for keyword matches instead of giving up after page one.
		$effective_min_count = $min_count;
		if ( '' !== $keyword && $effective_min_count < self::MIN_KEYWORD_RESULTS ) {
			$effective_min_count = self::MIN_KEYWORD_RESULTS;
		}

		$extra_pages = 0;
		while (
			$use_business_profile
			&& $effective_min_count > 0
			&& count( $data['reviews'] ) < $effective_min_count
			&& ! empty( $data['next_page_token'] )
			&& $extra_pages < self::MAX_AUTO_FETCH_PAGES
		) {
			$next = $this->get_reviews_business_profile( $data['next_page_token'] );
			++$extra_pages;

			if ( is_wp_error( $next ) ) {
				break;
			}

			$data['reviews']         = array_merge( $data['reviews'], $this->filter_reviews( $next['reviews'], $keyword ) );
			$data['next_page_token'] = $next['next_page_token'];
		}

		return $data;
	}

	/**
	 * Paginate through every Business Profile review page (up to
	 * self::FULL_SYNC_MAX_PAGES as a hard safety cap) and cache the raw,
	 * unfiltered result so keyword-filtered queries can search the whole
	 * review history instead of just the newest ones. Meant to be called
	 * from a WP Cron job (see Google_Reviews_Cron), not from a page load.
	 *
	 * @return array|\WP_Error The full unfiltered review list, or WP_Error on failure.
	 */
	public function sync_all_reviews() {
		if ( ! $this->is_connected() || ! get_option( self::OPTION_ACCOUNT_ID, '' ) || ! get_option( self::OPTION_LOCATION_ID, '' ) ) {
			return new \WP_Error( 'not_connected', __( 'Google Business Profile is not connected.', 'shopperexpress' ) );
		}

		$reviews    = array();
		$page_token = '';
		$pages      = 0;

		do {
			$page = $this->get_reviews_business_profile( $page_token );
			if ( is_wp_error( $page ) ) {
				// Keep whatever was already fetched rather than throwing away a
				// partial-but-useful snapshot because a later page failed.
				break;
			}

			$reviews    = array_merge( $reviews, $page['reviews'] );
			$page_token = $page['next_page_token'];
			++$pages;
		} while ( '' !== $page_token && $pages < self::FULL_SYNC_MAX_PAGES );

		update_option( self::FULL_CACHE_OPTION, $reviews, false );
		update_option( self::FULL_CACHE_UPDATED_OPTION, time(), false );

		return $reviews;
	}

	/**
	 * @return array|null The cached full review list, or null if the cron hasn't populated it yet.
	 */
	private function get_full_cache(): ?array {
		$cache = get_option( self::FULL_CACHE_OPTION, null );
		return is_array( $cache ) && ! empty( $cache ) ? $cache : null;
	}

	/**
	 * @return bool Whether a background full-history sync is currently running.
	 */
	public function is_sync_running(): bool {
		return false !== get_transient( self::SYNC_LOCK_TRANSIENT );
	}

	/**
	 * Kick off sync_all_reviews() in the background via a scheduled WP Cron
	 * event, instead of running it synchronously in the request that triggered
	 * it — a full sync can take up to ~100 sequential Google API calls, far
	 * too long to hold an admin-ajax request (or the admin's browser tab) open
	 * for. Locked so a second click while one is already running is rejected
	 * rather than starting a redundant, overlapping sync.
	 *
	 * @return true|\WP_Error
	 */
	public function start_background_sync() {
		if ( $this->is_sync_running() ) {
			return new \WP_Error( 'sync_in_progress', __( 'A review sync is already running in the background — check back in a few minutes.', 'shopperexpress' ) );
		}

		set_transient( self::SYNC_LOCK_TRANSIENT, time(), self::SYNC_LOCK_TTL );
		wp_schedule_single_event( time(), Google_Reviews_Cron::HOOK );

		return true;
	}

	/**
	 * Keep only reviews that meet the configured minimum star rating (see
	 * get_min_rating()), have non-empty text, and (when a keyword filter is
	 * set) mention at least one of the given keywords.
	 *
	 * @param array  $reviews Normalized review rows (see normalize_places_review()
	 *                         / get_reviews_business_profile()).
	 * @param string $keyword Optional comma-separated keyword(s), case-insensitive.
	 * @return array
	 */
	private function filter_reviews( array $reviews, string $keyword = '' ): array {
		$min_rating = $this->get_min_rating();
		$keywords   = array_values(
			array_filter( array_map( 'trim', explode( ',', $keyword ) ) )
		);

		return array_values(
			array_filter(
				$reviews,
				static function ( $review ) use ( $keywords, $min_rating ) {
					$rating = (int) ( $review['rating'] ?? 0 );
					$text   = trim( wp_strip_all_tags( (string) ( $review['text'] ?? '' ) ) );

					if ( $rating < $min_rating || '' === $text ) {
						return false;
					}

					if ( empty( $keywords ) ) {
						return true;
					}

					foreach ( $keywords as $needle ) {
						if ( false !== stripos( $text, $needle ) ) {
							return true;
						}
					}

					return false;
				}
			)
		);
	}

	/**
	 * Fetch a page of reviews from the Business Profile ("My Business") API v4.
	 * Supports real pagination (up to 50 reviews per page).
	 *
	 * @param string $page_token Opaque pagination token from a prior response.
	 * @return array{source: string, reviews: array, average_rating: float, total_review_count: int, next_page_token: string}|\WP_Error
	 */
	private function get_reviews_business_profile( string $page_token = '' ) {
		$account_id  = get_option( self::OPTION_ACCOUNT_ID, '' );
		$location_id = get_option( self::OPTION_LOCATION_ID, '' );

		if ( ! preg_match( '#^accounts/[0-9]+$#', $account_id ) || ! preg_match( '#^locations/[0-9]+$#', $location_id ) ) {
			return new \WP_Error( 'invalid_account_location', __( 'Invalid Business Profile account/location ID.', 'shopperexpress' ) );
		}

		$access_token = $this->get_access_token();
		if ( is_wp_error( $access_token ) ) {
			return $access_token;
		}

		$query = array( 'pageSize' => 20 );
		if ( '' !== $page_token ) {
			$query['pageToken'] = $page_token;
		}

		// Note: $account_id/$location_id (e.g. "accounts/123", "locations/456")
		// are resource paths and must keep their literal "/" — rawurlencode()
		// would turn them into "accounts%2F123", which Google's API 404s on.
		$url = sprintf(
			'https://mybusiness.googleapis.com/v4/%s/%s/reviews?%s',
			$account_id,
			$location_id,
			http_build_query( $query )
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array( 'Authorization' => 'Bearer ' . $access_token ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			return new \WP_Error( 'reviews_api_error', $body['error']['message'] ?? "HTTP {$code}" );
		}

		$stars_map = array(
			'STAR_RATING_UNSPECIFIED' => 0,
			'ONE'                     => 1,
			'TWO'                     => 2,
			'THREE'                   => 3,
			'FOUR'                    => 4,
			'FIVE'                    => 5,
		);

		$place_id = get_option( self::OPTION_PLACE_ID, '' );
		$cid_hex  = '' !== $place_id ? $this->get_place_cid( $place_id ) : '';
		if ( is_wp_error( $cid_hex ) ) {
			$cid_hex = '';
		}

		$reviews = array();
		foreach ( $body['reviews'] ?? array() as $review ) {
			$review_id = $review['reviewId'] ?? '';
			if ( '' === $review_id && ! empty( $review['name'] ) ) {
				$name_parts = explode( '/', $review['name'] );
				$review_id  = end( $name_parts );
			}

			$reviews[] = array(
				'rating'                         => $stars_map[ $review['starRating'] ?? '' ] ?? 0,
				'text'                           => $review['comment'] ?? '',
				'relativePublishTimeDescription' => '',
				'publishTime'                    => $review['createTime'] ?? '',
				'googleMapsURI'                  => ( '' !== $review_id && '' !== $cid_hex ) ? $this->build_review_permalink( $cid_hex, $review_id ) : '',
				'authorAttribution'              => array(
					'displayName' => $review['reviewer']['displayName'] ?? __( 'Google user', 'shopperexpress' ),
					'photoURI'    => $review['reviewer']['profilePhotoUrl'] ?? '',
				),
			);
		}

		return array(
			'source'             => 'business_profile',
			'reviews'            => $reviews,
			'average_rating'     => (float) ( $body['averageRating'] ?? 0 ),
			'total_review_count' => (int) ( $body['totalReviewCount'] ?? count( $reviews ) ),
			'next_page_token'    => (string) ( $body['nextPageToken'] ?? '' ),
		);
	}

	/**
	 * Fetch rating + up to 5 reviews for a place via Places API (New).
	 *
	 * Google Places API (New) caps `reviews` at 5 entries and provides no
	 * page token for them — this is a documented platform limit, not
	 * something that can be worked around with a different request shape.
	 *
	 * @param string $place_id Google Place ID.
	 * @param string $lang     BCP-47 language code for review text (e.g. "en"). Defaults to the site locale.
	 * @return array{source: string, reviews: array, average_rating: float, total_review_count: int}|\WP_Error
	 */
	private function get_reviews_places( string $place_id, string $lang = '' ) {
		$api_key = $this->get_places_api_key();
		if ( '' === $api_key ) {
			return new \WP_Error( 'not_configured', __( 'Neither a Business Profile connection nor a Places API key is configured. Go to Operation Center → Google Reviews.', 'shopperexpress' ) );
		}

		if ( '' === $lang ) {
			$lang = substr( get_locale(), 0, 2 ) ?: 'en';
		}

		$url = self::PLACES_API_BASE . rawurlencode( $place_id ) . '?languageCode=' . rawurlencode( $lang );

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'X-Goog-Api-Key'   => $api_key,
					'X-Goog-FieldMask' => self::PLACES_FIELD_MASK,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			return new \WP_Error( 'places_api_error', $body['error']['message'] ?? "HTTP {$code}" );
		}

		$reviews = array_map( array( $this, 'normalize_places_review' ), $body['reviews'] ?? array() );

		return array(
			'source'             => 'places',
			'reviews'            => $reviews,
			'average_rating'     => (float) ( $body['rating'] ?? 0 ),
			'total_review_count' => (int) ( $body['userRatingCount'] ?? 0 ),
		);
	}

	/**
	 * Map a Places API (New) review object onto the shape the frontend template expects.
	 *
	 * @param array $review Raw review object from Places API (New).
	 * @return array
	 */
	private function normalize_places_review( array $review ): array {
		return array(
			'rating'                         => (int) ( $review['rating'] ?? 0 ),
			'text'                           => $review['text']['text'] ?? '',
			'relativePublishTimeDescription' => $review['relativePublishTimeDescription'] ?? '',
			'publishTime'                    => $review['publishTime'] ?? '',
			'googleMapsURI'                  => $review['googleMapsUri'] ?? '',
			'authorAttribution'              => array(
				'displayName' => $review['authorAttribution']['displayName'] ?? __( 'Google user', 'shopperexpress' ),
				'photoURI'    => $review['authorAttribution']['photoUri'] ?? '',
			),
		);
	}

	/**
	 * Get a valid access token, refreshing (and caching) it as needed.
	 *
	 * @return string|\WP_Error
	 */
	private function get_access_token() {
		$cached = get_transient( self::TOKEN_TRANSIENT );
		if ( false !== $cached && '' !== $cached ) {
			return $cached;
		}

		$refresh_token = $this->get_refresh_token();
		if ( '' === $refresh_token ) {
			return new \WP_Error( 'not_connected', __( 'Google Business Profile is not connected. Go to Operation Center → Google Reviews.', 'shopperexpress' ) );
		}

		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'timeout' => 15,
				'body'    => array(
					'client_id'     => get_option( self::OPTION_CLIENT_ID, '' ),
					'client_secret' => $this->get_client_secret(),
					'refresh_token' => $refresh_token,
					'grant_type'    => 'refresh_token',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['access_token'] ) ) {
			return new \WP_Error( 'token_refresh_failed', $body['error_description'] ?? __( 'Failed to refresh Google access token.', 'shopperexpress' ) );
		}

		$ttl = max( 60, (int) ( $body['expires_in'] ?? 3600 ) - 60 );
		set_transient( self::TOKEN_TRANSIENT, $body['access_token'], $ttl );

		return $body['access_token'];
	}

	/**
	 * @return string Admin URL of the SOC "Google Reviews" panel.
	 */
	private function soc_page_url(): string {
		return admin_url( 'admin.php?page=soc-google-reviews' );
	}

	// -------------------------------------------------------------------------
	// Encryption helpers (AES-256-CBC via OpenSSL) — same approach as ADF_Api_Client.
	// -------------------------------------------------------------------------

	/**
	 * @return string
	 */
	private function get_client_secret(): string {
		$encrypted = get_option( self::OPTION_CLIENT_SECRET, '' );
		return '' === $encrypted ? '' : $this->decrypt( $encrypted );
	}

	/**
	 * @return string
	 */
	private function get_refresh_token(): string {
		$encrypted = get_option( self::OPTION_REFRESH_TOKEN, '' );
		return '' === $encrypted ? '' : $this->decrypt( $encrypted );
	}

	/**
	 * @return string
	 */
	private function get_places_api_key(): string {
		$encrypted = get_option( self::OPTION_PLACES_KEY, '' );
		return '' === $encrypted ? '' : $this->decrypt( $encrypted );
	}

	/**
	 * Resolve (and cache) the numeric Google "CID" for a place, needed to build
	 * per-review permalinks (the Business Profile API exposes no direct review
	 * URL). Uses the legacy Place Details endpoint's `url` field, which returns
	 * a `https://maps.google.com/?cid={decimal}` link.
	 *
	 * @param string $place_id Google Place ID.
	 * @return string|\WP_Error Hex-encoded CID.
	 */
	private function get_place_cid( string $place_id ) {
		$cached = get_option( self::OPTION_PLACE_CID, array() );
		if ( is_array( $cached ) && ( $cached['place_id'] ?? '' ) === $place_id && ! empty( $cached['cid_hex'] ) ) {
			return $cached['cid_hex'];
		}

		$api_key = $this->get_places_api_key();
		if ( '' === $api_key ) {
			return new \WP_Error( 'not_configured', __( 'A Places API key is required to resolve review permalinks.', 'shopperexpress' ) );
		}

		$url = add_query_arg(
			array(
				'place_id' => $place_id,
				'fields'   => 'url',
				'key'      => $api_key,
			),
			'https://maps.googleapis.com/maps/api/place/details/json'
		);

		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body     = json_decode( wp_remote_retrieve_body( $response ), true );
		$maps_url = $body['result']['url'] ?? '';

		if ( ! preg_match( '/[?&]cid=(\d+)/', $maps_url, $matches ) ) {
			return new \WP_Error( 'cid_not_found', __( 'Could not resolve the place CID for review permalinks.', 'shopperexpress' ) );
		}

		$cid_hex = $this->decimal_to_hex( $matches[1] );
		update_option( self::OPTION_PLACE_CID, array( 'place_id' => $place_id, 'cid_hex' => $cid_hex ), false );

		return $cid_hex;
	}

	/**
	 * Convert an arbitrary-precision decimal string to lowercase hex, without
	 * relying on bcmath/gmp (long division by 16, base-10 digit by digit).
	 *
	 * @param string $decimal Decimal digits only.
	 * @return string
	 */
	private function decimal_to_hex( string $decimal ): string {
		if ( ! ctype_digit( $decimal ) ) {
			return '';
		}

		$hex = '';
		while ( '0' !== $decimal ) {
			$remainder = 0;
			$quotient  = '';

			for ( $i = 0, $len = strlen( $decimal ); $i < $len; $i++ ) {
				$value     = $remainder * 10 + (int) $decimal[ $i ];
				$digit     = intdiv( $value, 16 );
				$remainder = $value % 16;

				if ( '' !== $quotient || 0 !== $digit ) {
					$quotient .= (string) $digit;
				}
			}

			$hex     = dechex( $remainder ) . $hex;
			$decimal = '' === $quotient ? '0' : $quotient;
		}

		return '' === $hex ? '0' : $hex;
	}

	/**
	 * Build the (undocumented) Google Maps deep link that opens directly on a
	 * single review, from the place's CID and the review's own ID. Reverse
	 * engineered from real Maps URLs — two nested length-delimited protobuf
	 * messages, base64-encoded (unpadded) into the `data=` route param.
	 *
	 * @param string $cid_hex   Hex-encoded place CID (see get_place_cid()).
	 * @param string $review_id Review ID (Business Profile API `reviewId`, e.g. "rp_h:...").
	 * @return string
	 */
	private function build_review_permalink( string $cid_hex, string $review_id ): string {
		$level3 = $this->protobuf_string_field( 1, $review_id );
		$level2 = $this->protobuf_varint_field( 1, 2 )
			. $this->protobuf_varint_field( 2, 0 )
			. $this->protobuf_string_field( 5, $level3 );
		$level2_b64 = rtrim( base64_encode( $level2 ), '=' );

		$level1     = $this->protobuf_string_field( 1, $level2_b64 )
			. $this->protobuf_varint_field( 2, 1 );
		$level1_b64 = rtrim( base64_encode( $level1 ), '=' );

		return sprintf(
			'https://www.google.com/maps/reviews/data=!4m8!14m7!1m6!2m5!1s%s!2m1!1s0x0:0x%s!3m1!1s2@1:%s||',
			rawurlencode( $level1_b64 ),
			$cid_hex,
			rawurlencode( $level2_b64 )
		);
	}

	/**
	 * @param int    $field_number
	 * @param string $value
	 * @return string Raw protobuf bytes.
	 */
	private function protobuf_string_field( int $field_number, string $value ): string {
		return chr( ( $field_number << 3 ) | 2 ) . $this->protobuf_varint( strlen( $value ) ) . $value;
	}

	/**
	 * @param int $field_number
	 * @param int $value
	 * @return string Raw protobuf bytes.
	 */
	private function protobuf_varint_field( int $field_number, int $value ): string {
		return chr( ( $field_number << 3 ) | 0 ) . $this->protobuf_varint( $value );
	}

	/**
	 * @param int $value
	 * @return string Raw protobuf varint bytes.
	 */
	private function protobuf_varint( int $value ): string {
		$bytes = '';
		do {
			$byte  = $value & 0x7f;
			$value >>= 7;
			if ( $value ) {
				$byte |= 0x80;
			}
			$bytes .= chr( $byte );
		} while ( $value );

		return $bytes;
	}

	/**
	 * Mask an API key leaving only first/last 4 chars visible.
	 *
	 * @param string $key
	 * @return string
	 */
	private function mask_key( string $key ): string {
		$len = strlen( $key );
		if ( 0 === $len ) {
			return '';
		}
		if ( $len < 10 ) {
			return str_repeat( '•', $len );
		}
		return substr( $key, 0, 4 ) . str_repeat( '•', $len - 8 ) . substr( $key, -4 );
	}

	/**
	 * @param string $value Plain text.
	 * @return string
	 */
	private function encrypt( string $value ): string {
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return base64_encode( $value );
		}
		$key = $this->get_or_create_salt();
		$iv  = openssl_random_pseudo_bytes( 16 );
		$enc = openssl_encrypt( $value, 'AES-256-CBC', $key, 0, $iv );
		return base64_encode( $iv . $enc );
	}

	/**
	 * @param string $stored Encrypted value.
	 * @return string
	 */
	private function decrypt( string $stored ): string {
		if ( ! function_exists( 'openssl_decrypt' ) ) {
			$decoded = base64_decode( $stored, true );
			return false !== $decoded ? $decoded : '';
		}
		$key     = $this->get_or_create_salt();
		$decoded = base64_decode( $stored, true );
		if ( false === $decoded || strlen( $decoded ) < 17 ) {
			return '';
		}
		$iv  = substr( $decoded, 0, 16 );
		$enc = substr( $decoded, 16 );
		$dec = openssl_decrypt( $enc, 'AES-256-CBC', $key, 0, $iv );
		return false !== $dec ? $dec : '';
	}

	/**
	 * @return string 32-byte key.
	 */
	private function get_or_create_salt(): string {
		$salt = get_option( self::OPTION_SALT, '' );
		if ( strlen( $salt ) < 32 ) {
			$salt = wp_generate_password( 32, true, true );
			update_option( self::OPTION_SALT, $salt );
		}
		return substr( $salt, 0, 32 );
	}
}

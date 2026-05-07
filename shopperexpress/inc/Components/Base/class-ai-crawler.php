<?php
/**
 * WordPress AI Crawler.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;
use WP_Query;

/**
 * Class AI_Crawler
 *
 * Automatic website crawling and ingestion system for the AI Chat knowledge base.
 *
 * Pipeline overview:
 *   1. Discover URLs   — sitemap (primary) → internal link crawl (fallback) → seed list
 *   2. Fetch & extract — HTTP GET, strip noise, extract title/content/meta
 *   3. Normalize       — map to structured schema with department/OEM/model detection
 *   4. Deduplicate     — md5(url + content) hash check; skip unchanged pages
 *   5. Persist         — upsert website-content CPT with ACF fields + post_meta
 *   6. Embed           — generate OpenAI embedding, store in _website_content_embedding
 *   7. Cache bust      — clear object-cache group "ai" so AI chat picks up new data
 *
 * Cron schedule:
 *   - ai_crawler_full        — daily,   discovers + ingests all pages
 *   - ai_crawler_incremental — every 2 hours, re-crawls a rolling subset of oldest pages
 *   - ai_crawler_embed_queue — every 15 minutes, processes pending embedding queue
 */
class AI_Crawler implements Theme_Component {

	// -------------------------------------------------------------------------
	// Constants
	// -------------------------------------------------------------------------

	/** CPT slug for ingested website pages. */
	private const CPT = 'website-content';

	/** Object-cache group (shared with AI class — busted together). */
	private const CACHE_GROUP = 'ai';

	/** Object-cache key for the crawled-page index. */
	private const CACHE_KEY_INDEX = 'website_content_pages';

	/** Post-meta key for content hash (duplicate detection). */
	private const META_HASH = '_content_hash';

	/** Post-meta key for embedding vector (JSON-encoded float[]). */
	private const META_EMBEDDING = '_website_content_embedding';

	/** Post-meta key for the source URL (also stored in ACF page_url). */
	private const META_URL = '_crawl_source_url';

	/** Option key for the embed-pending queue (array of post IDs). */
	private const OPTION_EMBED_QUEUE = 'ai_crawler_embed_queue';

	/** Option key tracking which URLs are already known (url → post_id map). */
	private const OPTION_URL_MAP = 'ai_crawler_url_map';

	/** Maximum pages fetched per incremental-crawl batch. */
	private const BATCH_SIZE = 15;

	/** HTTP request timeout in seconds. */
	private const HTTP_TIMEOUT = 15;

	/** Maximum body characters kept for embedding / prompt context. */
	private const MAX_BODY_CHARS = 5000;

	// -------------------------------------------------------------------------
	// Properties
	// -------------------------------------------------------------------------

	/**
	 * OpenAI API key, loaded from ACF options on demand.
	 *
	 * @var string
	 */
	private string $api_key = '';

	/**
	 * Allowed host(s) to crawl (derived from home_url at runtime).
	 *
	 * @var string
	 */
	private string $allowed_host = '';

	// -------------------------------------------------------------------------
	// Registration
	// -------------------------------------------------------------------------

	/**
	 * Register WordPress hooks and cron schedules.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		// Register custom cron intervals.
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ) );

		// Schedule cron jobs on activation / first boot.
		add_action( 'init', array( $this, 'maybe_schedule_crons' ) );

		// Cron callbacks.
		add_action( 'ai_crawler_full', array( $this, 'run_full_crawl' ) );
		add_action( 'ai_crawler_incremental', array( $this, 'run_incremental_crawl' ) );
		add_action( 'ai_crawler_embed_queue', array( $this, 'process_embed_queue' ) );

		// Admin-triggered manual crawl via admin-post.
		add_action( 'admin_post_ai_crawler_manual', array( $this, 'handle_manual_crawl' ) );

		// WP-CLI compat: register commands if CLI is running.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'ai-crawler full', array( $this, 'run_full_crawl' ) );
			\WP_CLI::add_command( 'ai-crawler incremental', array( $this, 'run_incremental_crawl' ) );
		}
	}

	// -------------------------------------------------------------------------
	// REST API — route registration
	// -------------------------------------------------------------------------

	/**
	 * Registers all debug/testing REST routes under /wp-json/ai-crawler/v1/.
	 *
	 * All endpoints are open (no auth) to ease local debugging.
	 * Lock them down with current_user_can() before deploying to production.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {

		$ns = 'ai-crawler/v1';

		$routes = array(
			'/test'              => array( $this, 'rest_test' ),
			'/discover'          => array( $this, 'rest_discover' ),
			'/crawl/full'        => array( $this, 'rest_crawl_full' ),
			'/crawl/incremental' => array( $this, 'rest_crawl_incremental' ),
			'/queue'             => array( $this, 'rest_queue' ),
			'/debug/url'         => array( $this, 'rest_debug_url' ),
		);

		foreach ( $routes as $route => $callback ) {
			register_rest_route(
				$ns,
				$route,
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => $callback,
					'permission_callback' => array( $this, 'can_access_rest' ),
				)
			);
		}

		register_rest_route(
			$ns,
			'/crawl/single',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_crawl_single' ),
				'permission_callback' => array( $this, 'can_access_rest' ),
				'args'                => array(
					'url' => array(
						'required'          => true,
						'sanitize_callback' => 'esc_url_raw',
						'validate_callback' => function ( $v ) {
							return filter_var( $v, FILTER_VALIDATE_URL ) !== false;
						},
					),
				),
			)
		);

		register_rest_route(
			$ns,
			'/debug/post/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_debug_post' ),
				'permission_callback' => array( $this, 'can_access_rest' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'validate_callback' => fn( $v ) => is_numeric( $v ) && (int) $v > 0,
					),
				),
			)
		);
	}

	/**
	 * Check if the current user has access to the REST API.
	 *
	 * @return bool
	 */
	public function can_access_rest( \WP_REST_Request $request ): bool {

		if ( ! current_user_can( 'manage_options' ) ) {
			// return false;
		}

		return true;
	}

	// -------------------------------------------------------------------------
	// REST API — handlers
	// -------------------------------------------------------------------------

	/**
	 * GET /test — system health check.
	 *
	 * @param \WP_REST_Request $request REST request (unused).
	 * @return \WP_REST_Response
	 */
	public function rest_test( \WP_REST_Request $request ): \WP_REST_Response { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$this->boot();

		$pending_urls = (array) get_option( 'ai_crawler_pending_urls', array() );
		$embed_queue  = (array) get_option( self::OPTION_EMBED_QUEUE, array() );
		$url_map      = (array) get_option( self::OPTION_URL_MAP, array() );

		return rest_ensure_response(
			array(
				'status'          => 'ok',
				'time'            => gmdate( 'Y-m-d H:i:s' ),
				'crawler_enabled' => (bool) get_field( 'ai_crawler_enabled', 'option' ),
				'api_key_set'     => ! empty( $this->api_key ),
				'allowed_host'    => $this->allowed_host,
				'cron'            => array(
					'ai_crawler_full'        => $this->cron_status( 'ai_crawler_full' ),
					'ai_crawler_incremental' => $this->cron_status( 'ai_crawler_incremental' ),
					'ai_crawler_embed_queue' => $this->cron_status( 'ai_crawler_embed_queue' ),
				),
				'queue_sizes'     => array(
					'pending_urls' => count( $pending_urls ),
					'embed_queue'  => count( $embed_queue ),
					'known_pages'  => count( $url_map ),
				),
			)
		);
	}

	/**
	 * GET /discover — run URL discovery and return full annotated URL list.
	 *
	 * @param \WP_REST_Request $request REST request (unused).
	 * @return \WP_REST_Response
	 */
	public function rest_discover( \WP_REST_Request $request ): \WP_REST_Response { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$this->boot();

		$sitemap_url   = home_url( '/sitemap_index.xml' );
		$sitemap_urls  = $this->fetch_sitemap_urls( $sitemap_url );
		$used_fallback = false;
		$link_urls     = array();

		if ( empty( $sitemap_urls ) ) {
			$used_fallback = true;
			$link_urls     = $this->crawl_internal_links( home_url( '/' ) );
		}

		$raw_urls = $used_fallback ? $link_urls : $sitemap_urls;

		// Annotate each URL with allow/reject decision.
		$annotated = array();
		foreach ( $raw_urls as $url ) {
			$annotated[] = array(
				'url'     => $url,
				'allowed' => $this->is_allowed_url( $url ),
				'reason'  => $this->describe_url_decision( $url ),
			);
		}

		// Seeds.
		$seed_raw    = (string) get_field( 'ai_crawler_seed_urls', 'option' );
		$seed_urls   = array();
		$seeds_added = array();
		if ( ! empty( $seed_raw ) ) {
			$seed_urls = array_filter( array_map( 'trim', explode( "\n", $seed_raw ) ) );
			foreach ( $seed_urls as $seed ) {
				if ( $this->is_allowed_url( $seed ) ) {
					$seeds_added[] = $seed;
				}
			}
		}

		$allowed  = array();
		$rejected = array();
		foreach ( $annotated as $a ) {
			if ( $a['allowed'] ) {
				$allowed[] = $a['url'];
			} else {
				$rejected[] = $a;
			}
		}

		return rest_ensure_response(
			array(
				'source'         => $used_fallback ? 'internal_links' : 'sitemap',
				'sitemap_url'    => $sitemap_url,
				'total_raw'      => count( $raw_urls ),
				'total_allowed'  => count( $allowed ),
				'total_rejected' => count( $rejected ),
				'allowed_urls'   => $allowed,
				'rejected_urls'  => $rejected,
				'seed_urls'      => array_values( $seed_urls ),
				'seeds_added'    => $seeds_added,
				'all_annotated'  => $annotated,
			)
		);
	}

	/**
	 * POST /crawl/full — trigger a full crawl synchronously (first batch only).
	 *
	 * @param \WP_REST_Request $request REST request (unused).
	 * @return \WP_REST_Response
	 */
	public function rest_crawl_full( \WP_REST_Request $request ): \WP_REST_Response { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$this->boot();

		if ( ! get_field( 'ai_crawler_enabled', 'option' ) ) {
			return rest_ensure_response(
				array(
					'status' => 'skipped',
					'reason' => 'Crawler is disabled in ACF options (ai_crawler_enabled = false).',
				)
			);
		}

		$before_map = count( (array) get_option( self::OPTION_URL_MAP, array() ) );
		$this->run_full_crawl();
		$after_map = count( (array) get_option( self::OPTION_URL_MAP, array() ) );

		$pending = (array) get_option( 'ai_crawler_pending_urls', array() );

		return rest_ensure_response(
			array(
				'status'           => 'completed',
				'pages_before'     => $before_map,
				'pages_after'      => $after_map,
				'pages_upserted'   => $after_map - $before_map,
				'pending_urls'     => count( $pending ),
				'embed_queue_size' => count( (array) get_option( self::OPTION_EMBED_QUEUE, array() ) ),
				'note'             => 'First batch processed synchronously. Remaining pages queued for incremental crawl.',
			)
		);
	}

	/**
	 * POST /crawl/incremental — trigger an incremental crawl synchronously.
	 *
	 * @param \WP_REST_Request $request REST request (unused).
	 * @return \WP_REST_Response
	 */
	public function rest_crawl_incremental( \WP_REST_Request $request ): \WP_REST_Response { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$this->boot();

		$before_map = count( (array) get_option( self::OPTION_URL_MAP, array() ) );
		$this->run_incremental_crawl();
		$after_map = count( (array) get_option( self::OPTION_URL_MAP, array() ) );

		return rest_ensure_response(
			array(
				'status'            => 'completed',
				'pages_before'      => $before_map,
				'pages_after'       => $after_map,
				'pages_upserted'    => $after_map - $before_map,
				'pending_remaining' => count( (array) get_option( 'ai_crawler_pending_urls', array() ) ),
				'embed_queue_size'  => count( (array) get_option( self::OPTION_EMBED_QUEUE, array() ) ),
			)
		);
	}

	/**
	 * POST /crawl/single — crawl a single URL and return a full debug trace.
	 *
	 * @param \WP_REST_Request $request REST request; expects JSON body with "url".
	 * @return \WP_REST_Response
	 */
	public function rest_crawl_single( \WP_REST_Request $request ): \WP_REST_Response {
		$this->boot();

		$url   = (string) $request->get_param( 'url' );
		$trace = $this->ingest_url_debug( $url );

		return rest_ensure_response( $trace );
	}

	/**
	 * GET /queue — return current state of all crawler queues.
	 *
	 * @param \WP_REST_Request $request REST request (unused).
	 * @return \WP_REST_Response
	 */
	public function rest_queue( \WP_REST_Request $request ): \WP_REST_Response { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$pending = (array) get_option( 'ai_crawler_pending_urls', array() );
		$embed_q = (array) get_option( self::OPTION_EMBED_QUEUE, array() );
		$url_map = (array) get_option( self::OPTION_URL_MAP, array() );

		// Enrich embed queue with post titles for readability.
		$embed_enriched = array();
		foreach ( $embed_q as $post_id ) {
			$post             = get_post( (int) $post_id );
			$embed_enriched[] = array(
				'post_id'        => (int) $post_id,
				'post_title'     => $post instanceof \WP_Post ? $post->post_title : '(missing)',
				'embedding_done' => ! empty( get_post_meta( (int) $post_id, self::META_EMBEDDING, true ) ),
			);
		}

		return rest_ensure_response(
			array(
				'pending_urls'  => $pending,
				'pending_count' => count( $pending ),
				'embed_queue'   => $embed_enriched,
				'embed_count'   => count( $embed_q ),
				'url_map'       => $url_map,
				'url_map_count' => count( $url_map ),
			)
		);
	}

	/**
	 * GET /debug/url?url=... — explain why a URL is allowed or rejected.
	 *
	 * @param \WP_REST_Request $request REST request; expects "url" query param.
	 * @return \WP_REST_Response
	 */
	public function rest_debug_url( \WP_REST_Request $request ): \WP_REST_Response {
		$this->boot();

		$url    = (string) $request->get_param( 'url' );
		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		$host   = (string) wp_parse_url( $url, PHP_URL_HOST );

		$checks = array(
			'input_url'     => $url,
			'allowed_host'  => $this->allowed_host,
			'parsed_scheme' => $scheme,
			'parsed_host'   => $host,
			'scheme_ok'     => in_array( $scheme, array( 'http', 'https' ), true ),
			'host_matches'  => $host === $this->allowed_host,
			'is_allowed'    => $this->is_allowed_url( $url ),
		);

		if ( empty( $url ) ) {
			$checks['decision'] = 'REJECTED — empty URL.';
		} elseif ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			$checks['decision'] = 'REJECTED — scheme "' . $scheme . '" is not http or https.';
		} elseif ( $host !== $this->allowed_host ) {
			$checks['decision'] = 'REJECTED — host "' . $host . '" does not match allowed host "' . $this->allowed_host . '".';
		} else {
			$checks['decision'] = 'ALLOWED — host and scheme both pass.';
		}

		// Check whether the URL is already known (in url_map).
		$url_map       = (array) get_option( self::OPTION_URL_MAP, array() );
		$known_post_id = isset( $url_map[ $url ] ) ? (int) $url_map[ $url ] : null;
		$stored_hash   = $known_post_id ? get_post_meta( $known_post_id, self::META_HASH, true ) : null;
		$last_crawled  = $known_post_id ? get_field( 'last_crawled', $known_post_id ) : null;

		$checks['known_post_id'] = $known_post_id;
		$checks['stored_hash']   = $stored_hash;
		$checks['last_crawled']  = $last_crawled;

		// Quick sitemap probe — just check whether url appears in sitemap.
		$sitemap_urls         = $this->fetch_sitemap_urls( home_url( '/sitemap.xml' ) );
		$checks['in_sitemap'] = in_array( $url, $sitemap_urls, true );

		return rest_ensure_response( $checks );
	}

	/**
	 * GET /debug/post/{id} — inspect a stored website-content post.
	 *
	 * @param \WP_REST_Request $request REST request; expects {id} route param.
	 * @return \WP_REST_Response
	 */
	public function rest_debug_post( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post instanceof \WP_Post ) {
			return rest_ensure_response(
				array(
					'error'   => 'Post not found.',
					'post_id' => $post_id,
				)
			);
		}

		$embedding_raw = get_post_meta( $post_id, self::META_EMBEDDING, true );
		$embedding_arr = $embedding_raw ? json_decode( $embedding_raw, true ) : null;

		return rest_ensure_response(
			array(
				'post_id'        => $post_id,
				'post_type'      => $post->post_type,
				'post_title'     => $post->post_title,
				'post_status'    => $post->post_status,
				'post_modified'  => $post->post_modified,
				'content_length' => strlen( $post->post_content ),
				'acf_fields'     => array(
					'page_url'        => get_field( 'page_url', $post_id ),
					'title'           => get_field( 'title', $post_id ),
					'department'      => get_field( 'department', $post_id ),
					'oem'             => get_field( 'oem', $post_id ),
					'model'           => get_field( 'model', $post_id ),
					'summary'         => get_field( 'summary', $post_id ),
					'important_facts' => get_field( 'important_facts', $post_id ),
					'primary_cta'     => get_field( 'primary_cta', $post_id ),
					'secondary_cta'   => get_field( 'secondary_cta', $post_id ),
					'page_category'   => get_field( 'page_category', $post_id ),
					'last_crawled'    => get_field( 'last_crawled', $post_id ),
				),
				'meta'           => array(
					'content_hash' => get_post_meta( $post_id, self::META_HASH, true ),
					'source_url'   => get_post_meta( $post_id, self::META_URL, true ),
				),
				'embedding'      => array(
					'exists'         => ! empty( $embedding_raw ),
					'dimensions'     => is_array( $embedding_arr ) ? count( $embedding_arr ) : 0,
					'first_5_values' => is_array( $embedding_arr ) ? array_slice( $embedding_arr, 0, 5 ) : array(),
				),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Debug pipeline — single-URL trace
	// -------------------------------------------------------------------------

	/**
	 * Runs the full ingestion pipeline for one URL and returns a detailed trace.
	 *
	 * Used by POST /crawl/single so every step is observable.
	 *
	 * @param string $url Absolute URL to ingest.
	 * @return array Structured debug trace.
	 */
	private function ingest_url_debug( string $url ): array {
		$trace = array(
			'url'    => $url,
			'steps'  => array(),
			'result' => null,
			'errors' => array(),
		);

		// Step 0 — URL validation.
		if ( ! $this->is_allowed_url( $url ) ) {
			$trace['result']  = 'rejected';
			$trace['errors']  = array( $this->describe_url_decision( $url ) );
			$trace['steps'][] = array(
				'step'   => 'url_check',
				'passed' => false,
				'reason' => $this->describe_url_decision( $url ),
			);
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[AI_Crawler] /crawl/single rejected: ' . $url . ' — ' . $this->describe_url_decision( $url ) );
			return $trace;
		}
		$trace['steps'][] = array(
			'step'   => 'url_check',
			'passed' => true,
			'reason' => 'URL belongs to allowed host.',
		);

		// Step 1 — Fetch.
		$html = $this->fetch_html( $url );
		if ( null === $html ) {
			$trace['result']  = 'fetch_failed';
			$trace['errors']  = array( 'wp_remote_get failed or returned non-200.' );
			$trace['steps'][] = array(
				'step'   => 'fetch',
				'passed' => false,
				'reason' => 'HTTP request failed or non-200 response.',
			);
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[AI_Crawler] /crawl/single fetch failed: ' . $url );
			return $trace;
		}
		$trace['steps'][] = array(
			'step'        => 'fetch',
			'passed'      => true,
			'html_length' => strlen( $html ),
		);

		// Step 2 — Extract.
		$extracted        = $this->extract_content( $html, $url );
		$trace['steps'][] = array(
			'step'             => 'extract',
			'passed'           => ! empty( $extracted['content'] ),
			'title'            => $extracted['title'],
			'content_length'   => strlen( $extracted['content'] ),
			'meta_description' => $extracted['meta_description'],
		);
		if ( empty( $extracted['content'] ) ) {
			$trace['result'] = 'no_content';
			$trace['errors'] = array( 'Extraction returned empty content — page may be JS-rendered or all noise.' );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[AI_Crawler] /crawl/single empty content: ' . $url );
			return $trace;
		}

		// Step 3 — Deduplicate.
		$hash             = md5( $url . $extracted['content'] );
		$url_map          = (array) get_option( self::OPTION_URL_MAP, array() );
		$existing_post_id = isset( $url_map[ $url ] ) ? (int) $url_map[ $url ] : 0;
		$stored_hash      = $existing_post_id > 0 ? get_post_meta( $existing_post_id, self::META_HASH, true ) : null;
		$unchanged        = $stored_hash === $hash;

		$trace['steps'][] = array(
			'step'             => 'deduplicate',
			'hash'             => $hash,
			'existing_post_id' => $existing_post_id,
			'stored_hash'      => $stored_hash,
			'unchanged'        => $unchanged,
			'action'           => $unchanged ? 'skipped (content unchanged)' : ( $existing_post_id ? 'will update' : 'will insert' ),
		);

		if ( $unchanged ) {
			$trace['result'] = 'skipped_unchanged';
			return $trace;
		}

		// Step 4 — Normalize.
		$record           = $this->normalize( $extracted, $url );
		$trace['steps'][] = array(
			'step'           => 'normalize',
			'passed'         => true,
			'department'     => $record['department'],
			'oem'            => $record['oem'],
			'model'          => $record['model'],
			'page_category'  => $record['page_category'],
			'primary_cta'    => $record['primary_cta'],
			'secondary_cta'  => $record['secondary_cta'],
			'summary_length' => strlen( $record['summary'] ),
			'facts_count'    => substr_count( $record['important_facts'], "\n" ) + ( empty( $record['important_facts'] ) ? 0 : 1 ),
		);

		// Step 5 — Upsert.
		$post_id = $this->upsert_post( $record, $url, $hash, $existing_post_id );
		if ( ! $post_id ) {
			$trace['result']  = 'upsert_failed';
			$trace['errors']  = array( 'wp_insert_post / wp_update_post returned WP_Error or 0.' );
			$trace['steps'][] = array(
				'step'   => 'upsert',
				'passed' => false,
				'reason' => 'wp_insert_post/wp_update_post failed.',
			);
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[AI_Crawler] /crawl/single upsert failed: ' . $url );
			return $trace;
		}

		// Update URL map and embed queue.
		$url_map[ $url ] = $post_id;
		update_option( self::OPTION_URL_MAP, $url_map, false );
		$queue   = (array) get_option( self::OPTION_EMBED_QUEUE, array() );
		$queue[] = $post_id;
		update_option( self::OPTION_EMBED_QUEUE, array_unique( $queue ), false );
		wp_cache_delete( self::CACHE_KEY_INDEX, self::CACHE_GROUP );

		$trace['steps'][] = array(
			'step'      => 'upsert',
			'passed'    => true,
			'post_id'   => $post_id,
			'operation' => $existing_post_id ? 'updated' : 'inserted',
		);

		$trace['result'] = $existing_post_id ? 'updated' : 'inserted';
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[AI_Crawler] /crawl/single success: ' . $url . ' → post_id=' . $post_id );

		return $trace;
	}

	// -------------------------------------------------------------------------
	// Debug helpers
	// -------------------------------------------------------------------------

	/**
	 * Returns a human-readable explanation for why a URL is allowed or rejected.
	 *
	 * @param string $url URL to describe.
	 * @return string
	 */
	private function describe_url_decision( string $url ): string {
		if ( empty( $url ) ) {
			return 'REJECTED — empty URL.';
		}
		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return 'REJECTED — scheme "' . $scheme . '" is not http or https.';
		}
		$host = (string) wp_parse_url( $url, PHP_URL_HOST );
		if ( $host !== $this->allowed_host ) {
			return 'REJECTED — host "' . $host . '" !== allowed host "' . $this->allowed_host . '".';
		}
		return 'ALLOWED — host and scheme both pass.';
	}

	/**
	 * Returns a summary of a scheduled cron event for the /test endpoint.
	 *
	 * @param string $hook WP cron hook name.
	 * @return array
	 */
	private function cron_status( string $hook ): array {
		$next = wp_next_scheduled( $hook );
		return array(
			'scheduled'  => (bool) $next,
			'next_run'   => $next ? gmdate( 'Y-m-d H:i:s', $next ) : null,
			'in_seconds' => $next ? max( 0, $next - time() ) : null,
		);
	}

	// -------------------------------------------------------------------------
	// Cron scheduling
	// -------------------------------------------------------------------------

	/**
	 * Adds custom cron recurrence intervals.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array
	 */
	public function add_cron_schedules( array $schedules ): array {
		$schedules['every_15_minutes'] = array(
			'interval' => 900,
			'display'  => __( 'Every 15 Minutes', 'shopperexpress' ),
		);
		$schedules['every_2_hours']    = array(
			'interval' => 7200,
			'display'  => __( 'Every 2 Hours', 'shopperexpress' ),
		);
		return $schedules;
	}

	/**
	 * Schedules all cron events if not already scheduled.
	 *
	 * @return void
	 */
	public function maybe_schedule_crons(): void {
		if ( ! wp_next_scheduled( 'ai_crawler_full' ) ) {
			wp_schedule_event( time(), 'daily', 'ai_crawler_full' );
		}
		if ( ! wp_next_scheduled( 'ai_crawler_incremental' ) ) {
			wp_schedule_event( time(), 'every_2_hours', 'ai_crawler_incremental' );
		}
		if ( ! wp_next_scheduled( 'ai_crawler_embed_queue' ) ) {
			wp_schedule_event( time(), 'every_15_minutes', 'ai_crawler_embed_queue' );
		}
	}

	// -------------------------------------------------------------------------
	// Public cron entry points
	// -------------------------------------------------------------------------

	/**
	 * Full crawl: discover all site URLs then ingest in batches.
	 *
	 * @return void
	 */
	public function run_full_crawl(): void {
		$this->boot();

		if ( ! get_field( 'ai_crawler_enabled', 'option' ) ) {
			return;
		}
		$urls = $this->discover_urls();

		if ( empty( $urls ) ) {
			return;
		}
		// Process in batches; remaining URLs are stored so the incremental crawl
		// can pick them up without a single long-running request.
		$batches = array_chunk( $urls, self::BATCH_SIZE );

		foreach ( $batches as $batch ) {
			$this->ingest_batch( $batch );
			// Yield to avoid PHP time-limit issues during CLI/cron execution.
			if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
				break; // Process only first batch per cron tick; remainder next run.
			}
		}
		// Store remaining batches for incremental pick-up.
		if ( count( $batches ) > 1 ) {
			$remaining = array_merge( ...array_slice( $batches, 1 ) );
			update_option( 'ai_crawler_pending_urls', $remaining, false );
		} else {
			delete_option( 'ai_crawler_pending_urls' );
		}
	}

	/**
	 * Incremental crawl: process pending URLs or re-crawl oldest pages.
	 *
	 * @return void
	 */
	public function run_incremental_crawl(): void {
		$this->boot();

		// First drain any URLs left over from the last full-crawl batch split.
		$pending = (array) get_option( 'ai_crawler_pending_urls', array() );
		if ( ! empty( $pending ) ) {
			$batch = array_splice( $pending, 0, self::BATCH_SIZE );
			update_option( 'ai_crawler_pending_urls', $pending, false );
			$this->ingest_batch( $batch );
			return;
		}

		// Re-crawl pages with oldest last_crawled timestamps.
		$stale = $this->get_stale_page_urls( self::BATCH_SIZE );
		if ( ! empty( $stale ) ) {
			$this->ingest_batch( $stale );
		}
	}

	/**
	 * Process pending embedding queue (up to 10 items per run to avoid timeouts).
	 *
	 * @return void
	 */
	public function process_embed_queue(): void {
		$this->api_key = (string) get_field( 'ai_api_key', 'option' );
		if ( empty( $this->api_key ) ) {
			return;
		}

		$queue = (array) get_option( self::OPTION_EMBED_QUEUE, array() );
		if ( empty( $queue ) ) {
			return;
		}

		$batch = array_splice( $queue, 0, 10 );
		update_option( self::OPTION_EMBED_QUEUE, $queue, false );

		foreach ( $batch as $post_id ) {
			$post = get_post( (int) $post_id );
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$this->generate_and_store_embedding( $post );
		}

		// Bust AI cache so fresh embeddings are picked up immediately.
		wp_cache_delete( self::CACHE_KEY_INDEX, self::CACHE_GROUP );
	}

	/**
	 * Admin-POST handler for manual crawl trigger from settings UI.
	 *
	 * @return void
	 */
	public function handle_manual_crawl(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'shopperexpress' ) );
		}
		check_admin_referer( 'ai_crawler_manual' );
		$this->run_full_crawl();
		wp_safe_redirect( add_query_arg( 'ai_crawler', 'done', wp_get_referer() ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// URL discovery
	// -------------------------------------------------------------------------

	/**
	 * Discovers crawlable URLs via sitemap, then internal link fallback.
	 *
	 * @return string[] Absolute URLs belonging to allowed_host.
	 */
	private function discover_urls(): array {
		$urls = $this->fetch_sitemap_urls( home_url( '/sitemap.xml' ) );

		if ( empty( $urls ) ) {
			$urls = $this->crawl_internal_links( home_url( '/' ) );
		}

		// Merge any manually seeded URLs from ACF options.
		$seed_raw = (string) get_field( 'ai_crawler_seed_urls', 'option' );
		if ( ! empty( $seed_raw ) ) {
			$seeds = array_filter( array_map( 'trim', explode( "\n", $seed_raw ) ) );
			foreach ( $seeds as $seed ) {
				if ( $this->is_allowed_url( $seed ) ) {
					$urls[] = $seed;
				}
			}
		}

		return array_values( array_unique( $urls ) );
	}

	/**
	 * Recursively fetches URLs from a sitemap XML (handles sitemap index files).
	 *
	 * @param string $sitemap_url Absolute URL of the sitemap.
	 * @param int    $depth       Recursion depth guard.
	 * @return string[]
	 */
	private function fetch_sitemap_urls( string $sitemap_url, int $depth = 0 ): array {
		if ( $depth > 3 ) {
			return array();
		}

		$response = wp_remote_get(
			$sitemap_url,
			array(
				'timeout'    => self::HTTP_TIMEOUT,
				'user-agent' => 'ShopperExpress AI Crawler/1.0',
				'sslverify'  => ( 'https' === parse_url( $sitemap_url, PHP_URL_SCHEME ) ),
				'headers'    => $this->get_basic_auth_headers(),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return array();
		}

		// Suppress XML parse warnings; we handle invalid markup gracefully.
		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $body );
		libxml_clear_errors();

		if ( false === $xml ) {
			return array();
		}

		$urls = array();

		// Sitemap index: contains <sitemap> children.
		if ( isset( $xml->sitemap ) ) {
			foreach ( $xml->sitemap as $child ) {
				$loc = trim( (string) $child->loc );
				if ( ! empty( $loc ) ) {
					$child_urls = $this->fetch_sitemap_urls( $loc, $depth + 1 );
					$urls       = array_merge( $urls, $child_urls );
				}
			}
			return $urls;
		}

		// Regular sitemap: contains <url> children.
		if ( isset( $xml->url ) ) {
			foreach ( $xml->url as $entry ) {
				$loc = trim( (string) $entry->loc );
				if ( $this->is_allowed_url( $loc ) ) {
					$urls[] = $loc;
				}
			}
		}

		return $urls;
	}

	/**
	 * Crawls internal links starting from a seed page (BFS, single level).
	 *
	 * @param string $seed_url Starting URL.
	 * @return string[]
	 */
	private function crawl_internal_links( string $seed_url ): array {
		$response = wp_remote_get(
			$seed_url,
			array(
				'timeout'    => self::HTTP_TIMEOUT,
				'user-agent' => 'ShopperExpress AI Crawler/1.0',
				'sslverify'  => ( 'https' === parse_url( $seed_url, PHP_URL_SCHEME ) ),
				'headers'    => $this->get_basic_auth_headers(),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		$html = wp_remote_retrieve_body( $response );
		if ( empty( $html ) ) {
			return array();
		}

		$urls = array();
		if ( preg_match_all( '/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $matches ) ) {
			foreach ( $matches[1] as $href ) {
				$abs = $this->resolve_url( $href, $seed_url );
				if ( $abs && $this->is_allowed_url( $abs ) ) {
					$urls[] = $abs;
				}
			}
		}

		return array_values( array_unique( $urls ) );
	}

	// -------------------------------------------------------------------------
	// Ingestion pipeline
	// -------------------------------------------------------------------------

	/**
	 * Ingests a batch of URLs: fetch → extract → normalize → upsert → queue embed.
	 *
	 * @param string[] $urls Absolute URLs to process.
	 * @return void
	 */
	private function ingest_batch( array $urls ): void {
		foreach ( $urls as $url ) {
			$this->ingest_url( $url );
		}
	}

	/**
	 * Full pipeline for a single URL.
	 *
	 * @param string $url Absolute URL.
	 * @return void
	 */
	private function ingest_url( string $url ): void {
		// Fetch.
		$html = $this->fetch_html( $url );

		if ( null === $html ) {
			return;
		}

		// Extract raw fields.
		$extracted = $this->extract_content( $html, $url );

		if ( empty( $extracted['content'] ) ) {
			return;
		}

		// Deduplicate.
		$hash             = md5( $url . $extracted['content'] );
		$url_map          = (array) get_option( self::OPTION_URL_MAP, array() );
		$existing_post_id = isset( $url_map[ $url ] ) ? (int) $url_map[ $url ] : 0;

		if ( $existing_post_id > 0 ) {
			$stored_hash = get_post_meta( $existing_post_id, self::META_HASH, true );
			if ( $stored_hash === $hash ) {
				return; // Unchanged — skip entirely.
			}
		}

		// Normalize to structured schema.
		$record = $this->normalize( $extracted, $url );

		// Upsert CPT.
		$post_id = $this->upsert_post( $record, $url, $hash, $existing_post_id );
		if ( ! $post_id ) {
			return;
		}

		// Update URL → post_id map.
		$url_map[ $url ] = $post_id;
		update_option( self::OPTION_URL_MAP, $url_map, false );

		// Queue embedding generation (processed async by embed-queue cron).
		$queue   = (array) get_option( self::OPTION_EMBED_QUEUE, array() );
		$queue[] = $post_id;
		update_option( self::OPTION_EMBED_QUEUE, array_unique( $queue ), false );

		// Bust AI index cache immediately so re-queries see new post.
		wp_cache_delete( self::CACHE_KEY_INDEX, self::CACHE_GROUP );
	}

	// -------------------------------------------------------------------------
	// Fetch
	// -------------------------------------------------------------------------

	/**
	 * Fetches and returns raw HTML for a URL. Returns null on failure.
	 *
	 * @param string $url Absolute URL.
	 * @return string|null
	 */
	private function fetch_html( string $url ): ?string {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => self::HTTP_TIMEOUT,
				'user-agent' => 'ShopperExpress AI Crawler/1.0',
				'headers'    => array_merge( array( 'Accept' => 'text/html' ), $this->get_basic_auth_headers() ),
				'sslverify'  => ( 'https' === parse_url( $url, PHP_URL_SCHEME ) ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== (int) $code ) {
			return null;
		}

		return wp_remote_retrieve_body( $response );
	}

	// -------------------------------------------------------------------------
	// HTTP helpers
	// -------------------------------------------------------------------------

	/**
	 * Returns Authorization header array for HTTP Basic Auth if constants are defined.
	 *
	 * @return array
	 */
	private function get_basic_auth_headers(): array {
		if (
			defined( 'AI_API_BASIC_USER' ) && defined( 'AI_API_BASIC_PASS' ) &&
			'' !== AI_API_BASIC_USER && '' !== AI_API_BASIC_PASS
		) {
			return array(
				'Authorization' => 'Basic ' . base64_encode( AI_API_BASIC_USER . ':' . AI_API_BASIC_PASS ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			);
		}
		return array();
	}

	// -------------------------------------------------------------------------
	// Extraction
	// -------------------------------------------------------------------------

	/**
	 * Extracts structured content from raw HTML.
	 *
	 * @param string $html Raw page HTML.
	 * @param string $url  Source URL (used for context only).
	 * @return array{ title: string, content: string, meta_description: string }
	 */
	private function extract_content( string $html, string $url ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		libxml_use_internal_errors( true );
		$dom = new \DOMDocument();
		$dom->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) );
		libxml_clear_errors();

		$xpath = new \DOMXPath( $dom );

		// ── Title ─────────────────────────────────────────────────────────────
		// Prefer first H1; fall back to <title> tag.
		$h1_nodes = $xpath->query( '//h1[1]' );
		$title    = '';
		if ( $h1_nodes && $h1_nodes->length > 0 ) {
			$title = trim( $h1_nodes->item( 0 )->textContent );
		}
		if ( empty( $title ) ) {
			$title_nodes = $xpath->query( '//title' );
			if ( $title_nodes && $title_nodes->length > 0 ) {
				$title = trim( $title_nodes->item( 0 )->textContent );
			}
		}

		// ── Meta description ──────────────────────────────────────────────────
		$meta_desc  = '';
		$meta_nodes = $xpath->query( '//meta[@name="description"]/@content' );
		if ( $meta_nodes && $meta_nodes->length > 0 ) {
			$meta_desc = trim( $meta_nodes->item( 0 )->nodeValue );
		}

		// ── Main content ──────────────────────────────────────────────────────
		// Remove noise elements before extracting text.
		$noise_tags = array(
			'script',
			'style',
			'nav',
			'footer',
			'header',
			'noscript',
			'iframe',
			'form',
		);
		foreach ( $noise_tags as $tag ) {
			$nodes  = $dom->getElementsByTagName( $tag );
			$remove = array();
			foreach ( $nodes as $node ) {
				$remove[] = $node;
			}
			foreach ( $remove as $node ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$node->parentNode->removeChild( $node );
			}
		}

		// Also remove common nav/aside landmark elements by class/id heuristics.
		$noise_selectors = array(
			'//*[contains(@class,"nav")]',
			'//*[contains(@class,"menu")]',
			'//*[contains(@class,"sidebar")]',
			'//*[contains(@class,"breadcrumb")]',
			'//*[contains(@id,"cookie")]',
			'//*[contains(@class,"cookie")]',
		);
		foreach ( $noise_selectors as $sel ) {
			$nodes  = $xpath->query( $sel );
			$remove = array();
			if ( $nodes ) {
				foreach ( $nodes as $node ) {
					$remove[] = $node;
				}
			}
			foreach ( $remove as $node ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				if ( $node->parentNode ) {
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$node->parentNode->removeChild( $node );
				}
			}
		}

		// Extract <main> if present; otherwise fall back to <body>.
		$main_nodes = $xpath->query( '//main' );
		$content    = '';
		if ( $main_nodes && $main_nodes->length > 0 ) {
			$content = $main_nodes->item( 0 )->textContent;
		} else {
			$body_nodes = $dom->getElementsByTagName( 'body' );
			if ( $body_nodes->length > 0 ) {
				$content = $body_nodes->item( 0 )->textContent;
			}
		}

		// Normalise whitespace.
		$content = preg_replace( '/\s+/', ' ', $content );
		$content = trim( (string) $content );
		$content = substr( $content, 0, self::MAX_BODY_CHARS );

		return array(
			'title'            => $title,
			'content'          => $content,
			'meta_description' => $meta_desc,
		);
	}

	// -------------------------------------------------------------------------
	// Normalization
	// -------------------------------------------------------------------------

	/**
	 * Maps raw extracted data into the structured website-content schema.
	 *
	 * @param array  $extracted Extracted fields from extract_content().
	 * @param string $url       Canonical page URL.
	 * @return array Structured record ready for upsert.
	 */
	private function normalize( array $extracted, string $url ): array {
		$title   = $extracted['title'];
		$content = $extracted['content'];
		$meta    = $extracted['meta_description'];

		$department    = $this->detect_department( $url, $title, $content );
		$oem           = $this->detect_oem( $url, $title, $content );
		$model         = $this->detect_model( $url, $title, $content );
		$page_category = $this->detect_page_category( $url, $title );
		$primary_cta   = $this->detect_primary_cta( $department );
		$secondary_cta = $this->detect_secondary_cta( $department );

		if ( ! empty( $meta ) ) {
			$summary = $meta;
		} else {
			$summary = $this->generate_summary( $content );
		}

		$facts = $this->extract_facts( $content, $department );

		return array(
			'url'             => $url,
			'title'           => $title,
			'content'         => $content,
			'summary'         => $summary,
			'important_facts' => $facts,
			'department'      => $department,
			'oem'             => $oem,
			'model'           => $model,
			'page_category'   => $page_category,
			'primary_cta'     => $primary_cta,
			'secondary_cta'   => $secondary_cta,
			'last_crawled'    => gmdate( 'Y-m-d H:i:s' ),
		);
	}

	/**
	 * Detects which dealership department a page belongs to.
	 *
	 * @param string $url     Page URL.
	 * @param string $title   Page title.
	 * @param string $content Page text content.
	 * @return string One of: Service, Finance, Trade, Parts, Sales, General.
	 */
	private function detect_department( string $url, string $title, string $content ): string {
		$haystack = strtolower( $url . ' ' . $title . ' ' . substr( $content, 0, 500 ) );

		$map = array(
			'Service' => array(
				'service',
				'repair',
				'maintenance',
				'oil change',
				'tire',
				'brake',
				'recall',
				'mechanic',
			),
			'Finance' => array(
				'finance',
				'financing',
				'loan',
				'apr',
				'credit',
				'payment',
				'apply',
			),
			'Trade'   => array(
				'trade',
				'trade-in',
				'sell your',
				'value your',
			),
			'Parts'   => array(
				'parts',
				'accessories',
				'oem parts',
			),
			'Sales'   => array(
				'new car',
				'used car',
				'inventory',
				'model',
				'trim',
				'buy',
				'shop',
			),
		);

		foreach ( $map as $dept => $keywords ) {
			foreach ( $keywords as $kw ) {
				if ( str_contains( $haystack, $kw ) ) {
					return $dept;
				}
			}
		}

		return 'General';
	}

	/**
	 * Detects OEM/make from URL, title, and content.
	 *
	 * @param string $url     Page URL.
	 * @param string $title   Page title.
	 * @param string $content Page content snippet.
	 * @return string|null
	 */
	private function detect_oem( string $url, string $title, string $content ): ?string {
		$makes = array(
			'acura',
			'alfa romeo',
			'audi',
			'bmw',
			'buick',
			'cadillac',
			'chevrolet',
			'chrysler',
			'dodge',
			'fiat',
			'ford',
			'genesis',
			'gmc',
			'honda',
			'hyundai',
			'infiniti',
			'jaguar',
			'jeep',
			'kia',
			'land rover',
			'lexus',
			'lincoln',
			'maserati',
			'mazda',
			'mercedes-benz',
			'mercedes',
			'mini',
			'mitsubishi',
			'nissan',
			'porsche',
			'ram',
			'subaru',
			'tesla',
			'toyota',
			'volkswagen',
			'volvo',
		);

		$haystack = strtolower( $url . ' ' . $title . ' ' . substr( $content, 0, 300 ) );

		foreach ( $makes as $make ) {
			if ( str_contains( $haystack, $make ) ) {
				return ucwords( $make );
			}
		}

		return null;
	}

	/**
	 * Detects vehicle model from URL, title, and content.
	 *
	 * @param string $url     Page URL.
	 * @param string $title   Page title.
	 * @param string $content Page content snippet.
	 * @return string|null
	 */
	private function detect_model( string $url, string $title, string $content ): ?string {
		$models = array(
			'civic',
			'accord',
			'cr-v',
			'pilot',
			'odyssey',
			'ridgeline',
			'hr-v',
			'camry',
			'corolla',
			'rav4',
			'highlander',
			'tacoma',
			'tundra',
			'4runner',
			'sienna',
			'prius',
			'f-150',
			'mustang',
			'explorer',
			'escape',
			'expedition',
			'bronco',
			'ranger',
			'silverado',
			'equinox',
			'traverse',
			'tahoe',
			'suburban',
			'altima',
			'sentra',
			'rogue',
			'pathfinder',
			'murano',
			'titan',
			'elantra',
			'sonata',
			'tucson',
			'santa fe',
			'palisade',
			'soul',
			'sportage',
			'sorento',
			'telluride',
			'wrangler',
			'grand cherokee',
			'compass',
			'gladiator',
			'sierra',
			'yukon',
			'outback',
			'forester',
			'crosstrek',
			'legacy',
			'ascent',
		);

		$haystack = strtolower( $url . ' ' . $title . ' ' . substr( $content, 0, 300 ) );

		foreach ( $models as $model ) {
			if ( str_contains( $haystack, $model ) ) {
				return ucwords( $model );
			}
		}

		return null;
	}

	/**
	 * Categorises a page based on URL path patterns.
	 *
	 * @param string $url   Page URL.
	 * @param string $title Page title (unused; reserved for future scoring).
	 * @return string
	 */
	private function detect_page_category( string $url, string $title ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$path = strtolower( (string) wp_parse_url( $url, PHP_URL_PATH ) );

		$category_map = array(
			'specials'  => array(
				'special',
				'offer',
				'deal',
				'promo',
				'incentive',
			),
			'inventory' => array(
				'inventory',
				'stock',
				'used',
				'new-car',
				'new-vehicle',
			),
			'service'   => array(
				'service',
				'repair',
				'maintenance',
				'schedule',
			),
			'finance'   => array(
				'finance',
				'financing',
				'credit',
				'apply',
			),
			'trade'     => array(
				'trade',
				'trade-in',
				'value',
			),
			'parts'     => array(
				'parts',
				'accessories',
			),
			'about'     => array(
				'about',
				'contact',
				'hours',
				'location',
				'directions',
			),
			'research'  => array(
				'research',
				'review',
				'compare',
				'blog',
				'news',
			),
		);

		foreach ( $category_map as $category => $keywords ) {
			foreach ( $keywords as $kw ) {
				if ( str_contains( $path, $kw ) ) {
					return $category;
				}
			}
		}

		return 'general';
	}

	/**
	 * Generates a plain-text summary from the first ~500 chars of content.
	 *
	 * @param string $content Full page content.
	 * @return string
	 */
	private function generate_summary( string $content ): string {
		$snippet = substr( $content, 0, 500 );
		// Trim to last sentence boundary if possible.
		$pos = strrpos( $snippet, '.' );
		if ( $pos > 100 ) {
			$snippet = substr( $snippet, 0, $pos + 1 );
		}
		return trim( $snippet );
	}

	/**
	 * Extracts important facts from page content using department-specific signal words.
	 *
	 * Returns up to 5 relevant sentences as a newline-separated string.
	 *
	 * @param string $content    Full page content.
	 * @param string $department Detected department.
	 * @return string Newline-separated fact sentences.
	 */
	private function extract_facts( string $content, string $department ): string {
		$signal_words = array(
			'Service' => array(
				'appointment',
				'coupon',
				'special',
				'certified',
				'warranty',
			),
			'Finance' => array(
				'%',
				'apr',
				'payment',
				'approved',
				'credit',
				'rate',
			),
			'Trade'   => array(
				'value',
				'estimate',
				'kelley',
				'kbb',
				'carmax',
			),
			'Parts'   => array(
				'genuine',
				'oem',
				'accessory',
				'warranty',
			),
			'Sales'   => array(
				'msrp',
				'invoice',
				'starting at',
				'lease',
				'rebate',
			),
			'General' => array(
				'hours',
				'phone',
				'address',
				'open',
				'closed',
			),
		);

		$signals   = isset( $signal_words[ $department ] ) ? $signal_words[ $department ] : $signal_words['General'];
		$sentences = preg_split( '/(?<=[.!?])\s+/', $content );
		$facts     = array();

		if ( ! $sentences ) {
			return '';
		}

		foreach ( $sentences as $sentence ) {
			$lower = strtolower( $sentence );
			foreach ( $signals as $signal ) {
				if ( str_contains( $lower, $signal ) && strlen( $sentence ) > 20 && strlen( $sentence ) < 300 ) {
					$facts[] = trim( $sentence );
					break;
				}
			}
			if ( count( $facts ) >= 5 ) {
				break;
			}
		}

		return implode( "\n", $facts );
	}

	/**
	 * Returns the primary CTA label for the given department.
	 *
	 * @param string $department Detected department.
	 * @return string|null
	 */
	private function detect_primary_cta( string $department ): ?string {
		$cta_map = array(
			'Service' => 'Schedule Service',
			'Finance' => 'Apply for Financing',
			'Trade'   => 'Get a Trade-In Value',
			'Parts'   => 'Order Parts',
			'Sales'   => 'View Inventory',
		);

		return isset( $cta_map[ $department ] ) ? $cta_map[ $department ] : null;
	}

	/**
	 * Returns the secondary CTA label for the given department.
	 *
	 * @param string $department Detected department.
	 * @return string|null
	 */
	private function detect_secondary_cta( string $department ): ?string {
		$cta_map = array(
			'Service' => 'View Service Specials',
			'Finance' => 'View Current Offers',
			'Trade'   => 'Contact a Sales Advisor',
			'Sales'   => 'Get a Quote',
		);

		return isset( $cta_map[ $department ] ) ? $cta_map[ $department ] : null;
	}

	// -------------------------------------------------------------------------
	// Persistence
	// -------------------------------------------------------------------------

	/**
	 * Upserts a website-content CPT post for the given record.
	 *
	 * @param array  $record           Normalised record from normalize().
	 * @param string $url              Canonical page URL.
	 * @param string $hash             Content hash for deduplication.
	 * @param int    $existing_post_id Existing post ID (0 = create new).
	 * @return int|null Post ID on success, null on failure.
	 */
	private function upsert_post( array $record, string $url, string $hash, int $existing_post_id ): ?int {
		if ( ! empty( $record['title'] ) ) {
			$post_title = sanitize_text_field( $record['title'] );
		} else {
			$post_title = sanitize_text_field( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		}

		$post_data = array(
			'post_type'    => self::CPT,
			'post_title'   => $post_title,
			'post_content' => wp_kses_post( $record['content'] ),
			'post_status'  => 'publish',
		);

		if ( $existing_post_id > 0 ) {
			$post_data['ID'] = $existing_post_id;
			$result          = wp_update_post( $post_data, true );
		} else {
			$result = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $result ) || ! $result ) {
			return null;
		}

		$post_id = (int) $result;

		// Update ACF fields.
		update_field( 'page_url', esc_url_raw( $url ), $post_id );
		update_field( 'title', sanitize_text_field( $record['title'] ), $post_id );
		update_field( 'department', sanitize_text_field( $record['department'] ), $post_id );
		update_field( 'oem', sanitize_text_field( (string) $record['oem'] ), $post_id );
		update_field( 'model', sanitize_text_field( (string) $record['model'] ), $post_id );
		update_field( 'summary', sanitize_textarea_field( $record['summary'] ), $post_id );
		update_field( 'important_facts', sanitize_textarea_field( $record['important_facts'] ), $post_id );
		update_field( 'primary_cta', sanitize_text_field( (string) $record['primary_cta'] ), $post_id );
		update_field( 'secondary_cta', sanitize_text_field( (string) $record['secondary_cta'] ), $post_id );
		update_field( 'last_crawled', sanitize_text_field( $record['last_crawled'] ), $post_id );
		update_field( 'page_category', sanitize_text_field( $record['page_category'] ), $post_id );

		// Update post_meta fields.
		update_post_meta( $post_id, self::META_HASH, $hash );
		update_post_meta( $post_id, self::META_URL, esc_url_raw( $url ) );

		return $post_id;
	}

	// -------------------------------------------------------------------------
	// Embedding generation
	// -------------------------------------------------------------------------

	/**
	 * Generates and stores an OpenAI embedding for a website-content post.
	 *
	 * Input text: title + summary + important_facts + first 2000 chars of content.
	 *
	 * @param \WP_Post $post Post to embed.
	 * @return void
	 */
	private function generate_and_store_embedding( \WP_Post $post ): void {
		$acf_title = (string) get_field( 'title', $post->ID );
		$title     = ! empty( $acf_title ) ? $acf_title : $post->post_title;
		$summary   = (string) get_field( 'summary', $post->ID );
		$facts     = (string) get_field( 'important_facts', $post->ID );
		$content   = substr( $post->post_content, 0, 2000 );

		$parts = array_filter( array( $title, $summary, $facts, $content ) );
		$text  = implode( "\n", $parts );

		if ( empty( $text ) ) {
			return;
		}

		$embedding = $this->get_embedding( $text );
		if ( empty( $embedding ) ) {
			return;
		}

		update_post_meta( $post->ID, self::META_EMBEDDING, wp_json_encode( $embedding ) );

		// Bust embedding cache for this post and the full index.
		wp_cache_delete( 'website_content_embedding_' . $post->ID, self::CACHE_GROUP );
		wp_cache_delete( self::CACHE_KEY_INDEX, self::CACHE_GROUP );
	}

	// -------------------------------------------------------------------------
	// OpenAI embedding API
	// -------------------------------------------------------------------------

	/**
	 * Calls OpenAI embeddings API for a given text string.
	 *
	 * Returns an empty array on any failure so callers can safely check empty().
	 *
	 * @param string $text Text to embed.
	 * @return float[]
	 */
	private function get_embedding( string $text ): array {
		if ( empty( $this->api_key ) ) {
			return array();
		}

		$response = wp_remote_post(
			'https://api.openai.com/v1/embeddings',
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model' => 'text-embedding-3-small',
						'input' => substr( $text, 0, 8000 ),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! isset( $data['data'][0]['embedding'] ) || ! is_array( $data['data'][0]['embedding'] ) ) {
			return array();
		}

		return array_map( 'floatval', $data['data'][0]['embedding'] );
	}

	// -------------------------------------------------------------------------
	// Stale-page detection
	// -------------------------------------------------------------------------

	/**
	 * Returns URLs of the N oldest-crawled website-content posts for re-crawl.
	 *
	 * @param int $limit Maximum results.
	 * @return string[]
	 */
	private function get_stale_page_urls( int $limit ): array {
		$query = new WP_Query(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => 'modified',
				'order'          => 'ASC',
				'fields'         => 'ids',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'     => array(
					array(
						'key'     => self::META_URL,
						'compare' => 'EXISTS',
					),
				),
			)
		);

		$urls = array();
		foreach ( $query->posts as $post_id ) {
			$url = get_post_meta( (int) $post_id, self::META_URL, true );
			if ( $url ) {
				$urls[] = (string) $url;
			}
		}

		return $urls;
	}

	// -------------------------------------------------------------------------
	// Utilities
	// -------------------------------------------------------------------------

	/**
	 * Initialises per-request state (allowed_host, api_key).
	 *
	 * @return void
	 */
	private function boot(): void {
		$this->allowed_host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		$this->api_key      = (string) get_field( 'ai_api_key', 'option' );
	}

	/**
	 * Returns true when a URL belongs to the allowed host and uses HTTP/HTTPS.
	 *
	 * @param string $url URL to check.
	 * @return bool
	 */
	private function is_allowed_url( string $url ): bool {
		if ( empty( $url ) ) {
			return false;
		}
		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return false;
		}
		$host = (string) wp_parse_url( $url, PHP_URL_HOST );
		return $host === $this->allowed_host;
	}

	/**
	 * Resolves a possibly-relative href to an absolute URL.
	 *
	 * @param string $href Link href attribute value.
	 * @param string $base Page URL this href was found on.
	 * @return string|null Absolute URL, or null if un-resolvable.
	 */
	private function resolve_url( string $href, string $base ): ?string {
		$href = trim( $href );
		if ( empty( $href )
			|| str_starts_with( $href, '#' )
			|| str_starts_with( $href, 'javascript:' )
			|| str_starts_with( $href, 'mailto:' )
		) {
			return null;
		}
		if ( str_starts_with( $href, 'http' ) ) {
			return $href;
		}
		$parsed_base = wp_parse_url( $base );
		$scheme      = isset( $parsed_base['scheme'] ) ? $parsed_base['scheme'] : 'https';
		$host        = isset( $parsed_base['host'] ) ? $parsed_base['host'] : '';
		if ( str_starts_with( $href, '//' ) ) {
			return $scheme . ':' . $href;
		}
		if ( str_starts_with( $href, '/' ) ) {
			return $scheme . '://' . $host . $href;
		}
		$base_path = isset( $parsed_base['path'] ) ? dirname( $parsed_base['path'] ) : '/';
		return $scheme . '://' . $host . rtrim( $base_path, '/' ) . '/' . $href;
	}
}

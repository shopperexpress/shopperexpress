<?php
/**
 * WordPress AI.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;
use WP_Query;

/**
 * Class AI
 *
 * Hybrid AI chat system for car dealership websites.
 *
 * Knowledge hierarchy (highest to lowest priority):
 *   1. FAQ         — embeddings-based semantic search, high-confidence answers
 *   2. Website     — crawled page content with metadata + optional embeddings
 *   3. General     — general automotive guidance (prompt-level, no extra context)
 *   4. Contact     — conversion fallback when nothing else applies
 */
class AI implements Theme_Component {

	// -------------------------------------------------------------------------
	// Constants
	// -------------------------------------------------------------------------

	/** Maximum FAQ records fed into cosine ranking. */
	private const FAQ_LIMIT = 3;

	/**
	 * Minimum cosine similarity score for an FAQ match to be considered
	 * "high-confidence". Below this threshold the system falls back to
	 * website content before answering.
	 */
	private const FAQ_CONFIDENCE_THRESHOLD = 0.75;

	/** Maximum characters of page body injected into the prompt per page. */
	private const WEBSITE_CONTENT_MAX_CHARS = 2000;

	/** Maximum website-content pages injected per prompt. */
	private const WEBSITE_CONTEXT_LIMIT = 3;

	/**
	 * Minimum combined relevance score (cosine + metadata boost) a website
	 * page must reach before it is included in the prompt context.
	 * Prevents injecting completely unrelated pages when embeddings are active.
	 */
	private const WEBSITE_MIN_SCORE = 0.10;

	/** Rate-limit window in seconds. */
	private const RATE_LIMIT_WINDOW = 60;

	/** Maximum requests allowed per IP within the window. */
	private const RATE_LIMIT_MAX = 10;

	// Intent labels.
	private const INTENT_FAQ       = 'faq';
	private const INTENT_WEBSITE   = 'website';
	private const INTENT_INVENTORY = 'inventory';
	private const INTENT_SPECIALS  = 'specials';

	// Cache group shared by all object-cache keys in this class.
	private const CACHE_GROUP = 'ai';

	/**
	 * CPT slug used for crawled website pages.
	 * Must match the slug registered in the CPT declaration exactly.
	 * The WordPress save_post_{post_type} hook replaces hyphens with
	 * underscores automatically, so we keep the canonical hyphenated slug here
	 * and derive the hook name separately.
	 */
	private const WEBSITE_CONTENT_CPT = 'website-content';

	// -------------------------------------------------------------------------
	// Properties
	// -------------------------------------------------------------------------

	/**
	 * OpenAI API key loaded from ACF options on every request.
	 *
	 * @var string
	 */
	private string $api_key = '';

	// -------------------------------------------------------------------------
	// Registration
	// -------------------------------------------------------------------------

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_ajax_ai', array( $this, 'handle_request' ) );
		add_action( 'wp_ajax_nopriv_ai', array( $this, 'handle_request' ) );

		// save_post_{post_type} — WordPress converts hyphens to underscores in the
		// dynamic portion of this hook name, so 'website-content' fires as
		// 'save_post_website-content'. We register both to be safe across WP versions.
		add_action( 'save_post_faq', array( $this, 'on_save_faq' ), 10, 3 );
		add_action( 'save_post_website-content', array( $this, 'on_save_website_content' ), 10, 3 );
		add_action(
			'rest_api_init',
			function () {

				$manage_options = current_user_can( 'manage_options' );
				$manage_options = '__return_true';

				register_rest_route(
					'ai/v1',
					'/test',
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'ai_rest_test' ),
						'permission_callback' => $manage_options,
					)
				);

				register_rest_route(
					'ai/v1',
					'/inventory',
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'ai_get_inventory_feed' ),
						'permission_callback' => $manage_options,
					)
				);

				register_rest_route(
					'ai/v1',
					'/specials',
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'ai_get_specials_feed' ),
						'permission_callback' => $manage_options,
					)
				);

				register_rest_route(
					'ai/v1',
					'/debug-website',
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'ai_debug_website_handler' ),
						'permission_callback' => $manage_options,
					)
				);

				register_rest_route(
					'ai/v1',
					'/debug-faq',
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'ai_debug_faq_handler' ),
						'permission_callback' => $manage_options,
					)
				);

				register_rest_route(
					'ai/v1',
					'/debug-inventory',
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'ai_debug_inventory_handler' ),
						'permission_callback' => $manage_options,
					)
				);

				register_rest_route(
					'ai/v1',
					'/debug-specials',
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'ai_debug_specials_handler' ),
						'permission_callback' => $manage_options,
					)
				);
			}
		);
	}

	/**
	 * GET /wp-json/ai/v1/debug-website?q=…
	 *
	 * Temporary diagnostic endpoint — shows the full website_handler pipeline:
	 * intent detection, embedding, page scoring, context string, and final prompt.
	 * Remove once testing is complete.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function ai_debug_website_handler( \WP_REST_Request $request ): \WP_REST_Response {

		$this->api_key = (string) get_field( 'ai_api_key', 'option' );

		$question = sanitize_text_field( $request->get_param( 'q' ) ?? 'What are your service hours' );

		// ── 1. Intent detection ───────────────────────────────────────────────
		$intent = $this->detect_intent( $question );

		// ── 2. Embedding ─────────────────────────────────────────────────────
		$embedding     = $this->get_embedding( $question );
		$has_embedding = ! empty( $embedding );

		// ── 3. Raw page load + scoring ────────────────────────────────────────
		$pages = $this->load_website_content_pages();

		$scored = array();
		foreach ( $pages as $page ) {
			$score = 0.0;
			if ( $has_embedding && ! empty( $page['embedding'] ) ) {
				$score = $this->cosine_similarity( $embedding, $page['embedding'] );
			}
			$boost    = $this->compute_metadata_boost( $question, $page );
			$scored[] = array(
				'title'         => $page['title'],
				'department'    => $page['department'],
				'has_embedding' => ! empty( $page['embedding'] ),
				'cosine'        => round( $score, 4 ),
				'boost'         => round( $boost, 4 ),
				'combined'      => round( $score + $boost, 4 ),
				'passes_min'    => ( $score + $boost ) >= self::WEBSITE_MIN_SCORE,
			);
		}

		usort( $scored, fn( $a, $b ) => $b['combined'] <=> $a['combined'] );
		$top5 = array_slice( $scored, 0, 5 );

		$pages_with_embedding    = count( array_filter( $scored, fn( $p ) => $p['has_embedding'] ) );
		$pages_passing_threshold = count( array_filter( $scored, fn( $p ) => $p['passes_min'] ) );

		// ── 4. Context string (as website_handler builds it) ──────────────────
		$context = $this->fetch_website_context( $question, $embedding );

		// ── 5. Final prompt ───────────────────────────────────────────────────
		$prompt = $this->build_website_prompt( $question, $context );

		// ── 6. AI response ────────────────────────────────────────────────────
		$ai_response = $this->ask_ai( $prompt );

		return rest_ensure_response(
			array(
				'question'                => $question,
				'detected_intent'         => $intent,
				'question_embedded'       => $has_embedding,
				'total_pages'             => count( $pages ),
				'pages_with_embedding'    => $pages_with_embedding,
				'pages_passing_min_score' => $pages_passing_threshold,
				'website_min_score'       => self::WEBSITE_MIN_SCORE,
				'top_5_scored_pages'      => $top5,
				'context_length'          => strlen( $context ),
				'context_empty'           => empty( $context ),
				'prompt_length'           => strlen( $prompt ),
				'ai_response'             => $ai_response,
			)
		);
	}

	/**
	 * GET /wp-json/ai/v1/debug-faq?q=…
	 *
	 * Diagnostic endpoint for the FAQ pipeline. Shows embedding coverage,
	 * top cosine scores, assembled context, and the final AI response.
	 * Remove once testing is complete.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function ai_debug_faq_handler( \WP_REST_Request $request ): \WP_REST_Response {

		$this->api_key = (string) get_field( 'ai_api_key', 'option' );

		$question = sanitize_text_field( $request->get_param( 'q' ) ?? 'Do you offer financing' );

		// ── 1. Intent ─────────────────────────────────────────────────────────
		$intent = $this->detect_intent( $question );

		// ── 2. Embedding ─────────────────────────────────────────────────────
		$embedding     = $this->get_embedding( $question );
		$has_embedding = ! empty( $embedding );

		// ── 3. FAQ scoring ────────────────────────────────────────────────────
		$faqs        = $this->get_faq_embeddings();
		$scored_faqs = $this->score_faqs_by_similarity( $embedding, $faqs );
		$top5        = array_slice( $scored_faqs, 0, 5 );
		$top_score   = isset( $scored_faqs[0]['score'] ) ? (float) $scored_faqs[0]['score'] : 0.0;

		$top5_formatted = array_map(
			fn( $s ) => array(
				'score'    => round( $s['score'], 4 ),
				'question' => $s['faq']['question'],
				'category' => $s['faq']['category'],
				'intent'   => $s['faq']['intent'],
			),
			$top5
		);

		// ── 4. Context + prompt ───────────────────────────────────────────────
		$faq_result  = $this->fetch_faq_context( $question, $embedding );
		$faq_context = $faq_result['context'];
		$prompt      = $this->build_faq_prompt( $question, $faq_context );

		// ── 5. AI response ────────────────────────────────────────────────────
		$ai_response = $this->ask_ai( $prompt );

		return rest_ensure_response(
			array(
				'question'             => $question,
				'detected_intent'      => $intent,
				'question_embedded'    => $has_embedding,
				'total_faqs'           => count( $faqs ),
				'top_score'            => $top_score,
				'confidence_threshold' => self::FAQ_CONFIDENCE_THRESHOLD,
				'high_confidence_hit'  => $top_score >= self::FAQ_CONFIDENCE_THRESHOLD,
				'top_5_scored_faqs'    => $top5_formatted,
				'context_length'       => strlen( $faq_context ),
				'context_empty'        => empty( $faq_context ),
				'prompt_length'        => strlen( $prompt ),
				'ai_response'          => $ai_response,
			)
		);
	}

	/**
	 * GET /wp-json/ai/v1/debug-inventory?q=…
	 *
	 * Diagnostic endpoint for the inventory pipeline. Shows AI-extracted filters,
	 * the REST payload returned, and the final AI response.
	 * Remove once testing is complete.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function ai_debug_inventory_handler( \WP_REST_Request $request ): \WP_REST_Response {

		$this->api_key = (string) get_field( 'ai_api_key', 'option' );

		$question = sanitize_text_field( $request->get_param( 'q' ) ?? 'Show me used Honda under 25000' );

		// ── 1. Intent ─────────────────────────────────────────────────────────
		$intent = $this->detect_intent( $question );

		// ── 2. Filter extraction ──────────────────────────────────────────────
		$filters = $this->ai_extract_inventory_filters( $question );

		// ── 3. Inventory fetch ────────────────────────────────────────────────
		$context = $this->fetch_inventory_context( $question );

		// ── 4. Prompt + response ──────────────────────────────────────────────
		$inventory_json = ! empty( $context ) ? (string) wp_json_encode( $context ) : '';
		$prompt         = $this->build_inventory_prompt( $question, $inventory_json );
		$ai_response    = $this->convert_markdown_links( $this->ask_ai( $prompt ) );

		return rest_ensure_response(
			array(
				'question'            => $question,
				'detected_intent'     => $intent,
				'extracted_filters'   => $filters,
				'inventory_count'     => $context['count'] ?? 0,
				'vehicles_in_payload' => count( $context['vehicles'] ?? array() ),
				'context_length'      => strlen( $inventory_json ),
				'prompt_length'       => strlen( $prompt ),
				'ai_response'         => $ai_response,
			)
		);
	}

	/**
	 * GET /wp-json/ai/v1/debug-specials?q=…
	 *
	 * Diagnostic endpoint for the specials pipeline. Shows AI-extracted filters,
	 * the specials payload returned, and the final AI response.
	 * Remove once testing is complete.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function ai_debug_specials_handler( \WP_REST_Request $request ): \WP_REST_Response {

		$this->api_key = (string) get_field( 'ai_api_key', 'option' );

		$question = sanitize_text_field( $request->get_param( 'q' ) ?? 'Show me Honda lease specials' );

		// ── 1. Intent ─────────────────────────────────────────────────────────
		$intent = $this->detect_intent( $question );

		// ── 2. Filter extraction ──────────────────────────────────────────────
		$filters = $this->build_specials_query( $question );

		// ── 3. Specials fetch ─────────────────────────────────────────────────
		$context = $this->fetch_specials_context( $question );

		// ── 4. Prompt + response ──────────────────────────────────────────────
		$specials_json = ! empty( $context['specials'] ) ? (string) wp_json_encode( $context ) : '';
		$prompt        = $this->build_specials_prompt( $question, $specials_json );
		$ai_response   = $this->convert_markdown_links( $this->ask_ai( $prompt ) );

		return rest_ensure_response(
			array(
				'question'            => $question,
				'detected_intent'     => $intent,
				'extracted_filters'   => $filters,
				'specials_count'      => $context['count'] ?? 0,
				'specials_in_payload' => count( $context['specials'] ?? array() ),
				'applied_filters'     => $context['applied_filters'] ?? array(),
				'context_length'      => strlen( $specials_json ),
				'prompt_length'       => strlen( $prompt ),
				'ai_response'         => $ai_response,
			)
		);
	}

	/**
	 * Builds the inventory query based on the provided parameters.
	 *
	 * @param array $params Parameters for the query.
	 *
	 * @return array Query arguments.
	 */
	public function build_inventory_query( $params ) {

		$meta_query = array( 'relation' => 'AND' );

		if ( ! empty( $params['make'] ) ) {
			$meta_query[] = array(
				'key'     => 'make',
				'value'   => sanitize_text_field( $params['make'] ),
				'compare' => '=',
			);
		}

		if ( ! empty( $params['model'] ) ) {
			$meta_query[] = array(
				'key'     => 'model',
				'value'   => sanitize_text_field( $params['model'] ),
				'compare' => '=',
			);
		}

		if ( ! empty( $params['body_type'] ) ) {
			$meta_query[] = array(
				'key'     => 'bodystyle',
				'value'   => sanitize_text_field( $params['body_type'] ),
				'compare' => 'LIKE',
			);
		}

		if ( ! empty( $params['interior_color'] ) ) {
			$meta_query[] = array(
				'key'     => 'interior_color',
				'value'   => sanitize_text_field( $params['interior_color'] ),
				'compare' => '=',
			);
		}

		if ( ! empty( $params['condition'] ) ) {
			$meta_query[] = array(
				'key'     => 'condition',
				'value'   => sanitize_text_field( $params['condition'] ),
				'compare' => '=',
			);
		}

		if ( ! empty( $params['year'] ) ) {
			$meta_query[] = array(
				'key'     => 'year',
				'value'   => (int) $params['year'],
				'type'    => 'NUMERIC',
				'compare' => '=',
			);
		}

		if ( ! empty( $params['min_price'] ) && ! empty( $params['max_price'] ) ) {
			$meta_query[] = array(
				'key'     => 'original_price',
				'value'   => array( (float) $params['min_price'], (float) $params['max_price'] ),
				'type'    => 'NUMERIC',
				'compare' => 'BETWEEN',
			);
		} elseif ( ! empty( $params['min_price'] ) ) {
			$meta_query[] = array(
				'key'     => 'original_price',
				'value'   => (float) $params['min_price'],
				'type'    => 'NUMERIC',
				'compare' => '>=',
			);
		} elseif ( ! empty( $params['max_price'] ) ) {
			$meta_query[] = array(
				'key'     => 'original_price',
				'value'   => (float) $params['max_price'],
				'type'    => 'NUMERIC',
				'compare' => '<=',
			);
		}

		// When condition is unspecified search both new and used inventory.
		if ( ! empty( $params['post_type'] ) ) {
			$post_type = sanitize_text_field( $params['post_type'] );
		} else {
			$post_type = array( 'listings', 'used-listings' );
		}

		return array(
			'post_type'      => $post_type,
			'posts_per_page' => $params['limit'] ?? 20,
			'meta_query'     => $meta_query,
			'no_found_rows'  => false,
		);
	}

	// -------------------------------------------------------------------------
	// Test REST endpoint
	// -------------------------------------------------------------------------

	/**
	 * GET /wp-json/ai/v1/test — system health check (admin only).
	 *
	 * Returns a snapshot of the AI component's runtime state:
	 *   - API key presence
	 *   - ACF model/prompt option status
	 *   - FAQ and website-content post counts
	 *   - Rate-limit configuration
	 *   - Specials and inventory transient cache status
	 *
	 * @param \WP_REST_Request $request REST request (unused).
	 * @return \WP_REST_Response
	 */
	public function ai_rest_test( \WP_REST_Request $request ): \WP_REST_Response { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

		$this->api_key = (string) get_field( 'ai_api_key', 'option' );

		$faq_count = (int) wp_count_posts( 'faq' )->publish;
		$wc_count  = (int) wp_count_posts( self::WEBSITE_CONTENT_CPT )->publish;

		$specials_cached  = false !== get_transient( 'ai_specials_full_v1' );
		$inventory_sample = false !== get_transient( 'ai_inv_' . md5( '' . 20 ) );

		return rest_ensure_response(
			array(
				'status'         => 'ok',
				'time'           => gmdate( 'Y-m-d H:i:s' ),
				'api_key_set'    => ! empty( $this->api_key ),
				'acf_options'    => array(
					'inventory_prompt_set' => ! empty( get_field( 'ai_inventory_promt', 'option' ) ),
					'intent_prompt_set'    => ! empty( get_field( 'ai_intent_promt', 'option' ) ),
				),
				'knowledge_base' => array(
					'faq_count'                => $faq_count,
					'website_content_count'    => $wc_count,
					'faq_confidence_threshold' => self::FAQ_CONFIDENCE_THRESHOLD,
					'website_min_score'        => self::WEBSITE_MIN_SCORE,
					'website_context_limit'    => self::WEBSITE_CONTEXT_LIMIT,
				),
				'rate_limit'     => array(
					'window_seconds' => self::RATE_LIMIT_WINDOW,
					'max_requests'   => self::RATE_LIMIT_MAX,
				),
				'cache'          => array(
					'specials_warmed'  => $specials_cached,
					'inventory_warmed' => $inventory_sample,
				),
			)
		);
	}

	/**
	 * Get inventory feed.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function ai_get_inventory_feed( \WP_REST_Request $request ) {

		$params = $request->get_params();
		if ( empty( $params ) ) {
			$params = array(
				'limit' => 20,
			);
		}

		$cache_key = 'ai_inventory_' . md5( json_encode( $params ) );

		$cached = get_transient( $cache_key );
		if ( $cached ) {
			return $cached;
		}

		$args = $this->build_inventory_query( $params );

		$query = new WP_Query( $args );

		$vehicles = array();

		foreach ( $query->posts as $post ) {

			$vehicles[] = array(
				'id'             => $post->ID,
				'title'          => get_the_title( $post ),
				'url'            => get_permalink( $post->ID ),
				'vin'            => get_field( 'vin_number', $post->ID ),
				'make'           => get_field( 'make', $post->ID ),
				'model'          => get_field( 'model', $post->ID ),
				'year'           => get_field( 'year', $post->ID ),
				'price'          => (float) get_field( 'original_price', $post->ID ),
				'body_type'      => get_field( 'bodystyle', $post->ID ),
				'condition'      => get_field( 'condition', $post->ID ),
				'interior_color' => get_field( 'interior_color', $post->ID ),
				'exterior_color' => get_field( 'exterior_color', $post->ID ),
			);
		}

		$response = array(
			'count'    => (int) $query->found_posts,
			'vehicles' => $vehicles,
		);

		set_transient( $cache_key, $response, 10 * MINUTE_IN_SECONDS );

		return $response;
	}

	// -------------------------------------------------------------------------
	// Specials REST feed
	// -------------------------------------------------------------------------

	/**
	 * REST GET /wp-json/ai/v1/specials
	 *
	 * Fetches all active specials, applies optional query-param filters, and
	 * returns a normalized payload.  Results are cached as a single transient
	 * of the full normalized set; per-request filtering is done in PHP so only
	 * one WP_Query round-trip is ever needed per cache window.
	 *
	 * Supported query params (all optional):
	 *   make, model, trim, year, condition, post_type,
	 *   min_payment, max_payment, active_only (1|0, default 1)
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return array
	 */
	public function ai_get_specials_feed( \WP_REST_Request $request ): array {

		$params = $request->get_params();

		$all_specials = $this->load_all_specials();

		$filters = array(
			'make'        => sanitize_text_field( $params['make'] ?? '' ),
			'model'       => sanitize_text_field( $params['model'] ?? '' ),
			'trim'        => sanitize_text_field( $params['trim'] ?? '' ),
			'year'        => ! empty( $params['year'] ) ? (int) $params['year'] : 0,
			'condition'   => sanitize_text_field( $params['condition'] ?? '' ),
			'post_type'   => sanitize_text_field( $params['post_type'] ?? '' ),
			'min_payment' => ! empty( $params['min_payment'] ) ? (float) $params['min_payment'] : 0.0,
			'max_payment' => ! empty( $params['max_payment'] ) ? (float) $params['max_payment'] : 0.0,
			'active_only' => '0' !== ( $params['active_only'] ?? '1' ),
		);

		$filtered = $this->apply_specials_filters( $all_specials, $filters );
		$limit    = min( (int) ( $params['limit'] ?? 20 ), 50 );
		$sliced   = array_slice( $filtered, 0, $limit );

		$applied = array_filter(
			$filters,
			fn( $v ) => '' !== $v && 0 !== $v && 0.0 !== $v && true !== $v
		);

		return array(
			'count'           => count( $sliced ),
			'specials'        => $sliced,
			'applied_filters' => $applied,
		);
	}

	/**
	 * Loads and normalises all specials from every offer CPT.
	 *
	 * Results are cached for 10 minutes in a transient.  Cache is intentionally
	 * shared across all filter combinations — per-request filtering happens in
	 * PHP after the cache hit.
	 *
	 * @return array[] Normalized special records.
	 */
	private function load_all_specials(): array {

		$cached = get_transient( 'ai_specials_full_v1' );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$query = new WP_Query(
			array(
				'post_type'      => array( 'offers', 'lease-offers', 'finance-offers', 'conditional-offers', 'service-offers' ),
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'no_found_rows'  => true,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$specials = array();

		foreach ( $query->posts as $post ) {
			$specials[] = $this->normalize_special( $post );
		}

		wp_reset_postdata();

		set_transient( 'ai_specials_full_v1', $specials, 10 * MINUTE_IN_SECONDS );

		return $specials;
	}

	/**
	 * Normalises one offer post into a consistent structure regardless of CPT.
	 *
	 * Post-type mapping:
	 *   lease-offers       → type = 'lease'
	 *   finance-offers     → type = 'finance'
	 *   conditional-offers → type = 'conditional'
	 *   offers             → type = 'general'
	 *
	 * Expiration uses the `end_date` ACF field (stored as a text string).
	 * The `active` flag is true when end_date is absent or >= today.
	 *
	 * @param \WP_Post $post Raw post object.
	 * @return array Normalized special record.
	 */
	private function normalize_special( \WP_Post $post ): array {

		$type_map = array(
			'lease-offers'       => 'lease',
			'finance-offers'     => 'finance',
			'conditional-offers' => 'conditional',
			'service-offers'     => 'service',
			'offers'             => 'general',
		);

		$post_type    = $post->post_type;
		$offer_type   = $type_map[ $post_type ] ?? 'general';
		$end_date_raw = (string) get_field( 'end_date', $post->ID );
		$today        = gmdate( 'Y-m-d' );
		$expiration   = $this->parse_offer_date( $end_date_raw );
		$active       = empty( $expiration ) || $expiration >= $today;

		// Payment: lease-offers use `payment`; finance-offers use `per_1000` as proxy.
		$payment = null;
		if ( 'lease' === $offer_type ) {
			$payment = $this->parse_numeric_field( get_field( 'payment', $post->ID ) );
		} elseif ( 'finance' === $offer_type ) {
			$payment = $this->parse_numeric_field( get_field( 'per_1000', $post->ID ) );
		}

		// Due at signing: lease uses `due_at_signing`; finance uses `down`.
		$due_at_signing = null;
		if ( 'lease' === $offer_type ) {
			$due_at_signing = $this->parse_numeric_field( get_field( 'due_at_signing', $post->ID ) );
		} elseif ( 'finance' === $offer_type ) {
			$due_at_signing = $this->parse_numeric_field( get_field( 'down', $post->ID ) );
		}

		// Mileage: lease uses yearly_excess_mileage; fall back to total_excess_mileage.
		$mileage_raw = get_field( 'yearly_excess_mileage', $post->ID );
		if ( empty( $mileage_raw ) ) {
			$mileage_raw = get_field( 'total_excess_mileage', $post->ID );
		}
		$mileage = $this->parse_numeric_field( $mileage_raw );

		// Price / MSRP.
		$price = $this->parse_numeric_field( get_field( 'msrp', $post->ID ) );

		// CTA text: prefer search_inventory_button, fall back to post title.
		$cta = (string) get_field( 'search_inventory_button', $post->ID );
		if ( empty( $cta ) ) {
			$cta = get_the_title( $post );
		}

		// URL: prefer weburl ACF field, fall back to post permalink.
		$url = (string) get_field( 'weburl', $post->ID );
		if ( empty( $url ) ) {
			$url = (string) get_permalink( $post->ID );
		}

		return array(
			'id'              => $post->ID,
			'title'           => get_the_title( $post ),
			'post_type'       => $offer_type,
			'make'            => (string) get_field( 'make', $post->ID ),
			'model'           => (string) get_field( 'model', $post->ID ),
			'trim'            => (string) get_field( 'trim', $post->ID ),
			'year'            => (int) get_field( 'year', $post->ID ),
			'payment'         => $payment,
			'term'            => $this->parse_int_field( get_field( 'term', $post->ID ) ),
			'due_at_signing'  => $due_at_signing,
			'mileage'         => null !== $mileage ? (int) $mileage : null,
			'price'           => $price,
			'condition'       => (string) get_field( 'condition', $post->ID ),
			'expiration_date' => $expiration,
			'active'          => $active,
			'vin_match'       => get_field( 'vin_match', $post->ID ) ? get_field( 'vin_match', $post->ID ) : array(),
			'url'             => $url,
			'cta'             => $cta,
		);
	}

	/**
	 * Applies structured filters to the normalised specials array (AND logic).
	 *
	 * The `active_only` filter enforces expiration_date >= today.
	 * All string comparisons are case-insensitive partial matches to handle
	 * minor inconsistencies in stored data.
	 *
	 * @param array $specials Full normalized specials array.
	 * @param array $filters  Filter map from ai_get_specials_feed().
	 * @return array Filtered specials.
	 */
	private function apply_specials_filters( array $specials, array $filters ): array {

		$today = gmdate( 'Y-m-d' );

		return array_values(
			array_filter(
				$specials,
				function ( $s ) use ( $filters, $today ) {

					// Active / expiration gate.
					if ( $filters['active_only'] ) {
						if ( ! $s['active'] ) {
							return false;
						}
						// Belt-and-suspenders: also reject when expiration is explicitly past.
						if ( ! empty( $s['expiration_date'] ) && $s['expiration_date'] < $today ) {
							return false;
						}
					}

					if ( ! empty( $filters['make'] ) ) {
						if ( stripos( $s['make'], $filters['make'] ) === false ) {
							return false;
						}
					}

					if ( ! empty( $filters['model'] ) ) {
						if ( stripos( $s['model'], $filters['model'] ) === false
							&& stripos( $s['title'], $filters['model'] ) === false
						) {
							return false;
						}
					}

					if ( ! empty( $filters['trim'] ) ) {
						if ( stripos( $s['trim'], $filters['trim'] ) === false ) {
							return false;
						}
					}

					if ( ! empty( $filters['year'] ) ) {
						if ( (int) $s['year'] !== $filters['year'] ) {
							return false;
						}
					}

					if ( ! empty( $filters['condition'] ) ) {
						if ( stripos( (string) $s['condition'], $filters['condition'] ) === false ) {
							return false;
						}
					}

					if ( ! empty( $filters['post_type'] ) ) {
						if ( strtolower( $filters['post_type'] ) !== $s['post_type'] ) {
							return false;
						}
					}

					if ( ! empty( $filters['min_payment'] ) && null !== $s['payment'] ) {
						if ( $s['payment'] < $filters['min_payment'] ) {
							return false;
						}
					}

					if ( ! empty( $filters['max_payment'] ) && null !== $s['payment'] ) {
						if ( $s['payment'] > $filters['max_payment'] ) {
							return false;
						}
					}

					return true;
				}
			)
		);
	}

	/**
	 * Normalises a date string from an ACF text field to Y-m-d format.
	 *
	 * Accepts common variants: Y-m-d, m/d/Y, d-m-Y.
	 * Returns empty string when the value cannot be parsed.
	 *
	 * @param string $raw Raw date string from ACF.
	 * @return string Normalised Y-m-d date or empty string.
	 */
	private function parse_offer_date( string $raw ): string {

		$raw = trim( $raw );

		if ( empty( $raw ) ) {
			return '';
		}

		// Already Y-m-d.
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw ) ) {
			return $raw;
		}

		// Try strtotime for common formats (m/d/Y etc.).
		$ts = strtotime( $raw );
		if ( false !== $ts ) {
			return gmdate( 'Y-m-d', $ts );
		}

		return '';
	}

	/**
	 * Parses a field value to int, returning null when absent or non-numeric.
	 *
	 * @param mixed $value Raw field value.
	 * @return int|null
	 */
	private function parse_int_field( $value ): ?int {

		$f = $this->parse_numeric_field( $value );
		return null !== $f ? (int) $f : null;
	}

	// -------------------------------------------------------------------------
	// Public AJAX entry point
	// -------------------------------------------------------------------------

	/**
	 * Main AJAX handler — validates input, enforces rate-limit, dispatches.
	 *
	 * @return void
	 */
	public function handle_request(): void {

		$nonce = sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'shopperexpress_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Nonce verification failed' ), 403 );
		}

		if ( $this->is_rate_limited() ) {
			wp_send_json_error( array( 'message' => 'Too many requests. Please wait a moment.' ), 429 );
		}

		$this->api_key = (string) get_field( 'ai_api_key', 'option' );
		if ( empty( $this->api_key ) ) {
			wp_send_json_error( array( 'message' => 'Service unavailable.' ), 503 );
		}

		$type = sanitize_text_field( wp_unslash( $_REQUEST['type'] ?? '' ) );
		if ( 'faq' !== $type ) {
			wp_send_json_error( array( 'message' => 'Invalid request type.' ), 400 );
		}

		$question = sanitize_text_field( wp_unslash( $_REQUEST['question'] ?? '' ) );
		$question = substr( trim( $question ), 0, 500 );

		if ( empty( $question ) ) {
			wp_send_json_error( array( 'message' => 'Question is required.' ), 400 );
		}

		wp_send_json_success( $this->handle_question( $question ) );
	}

	// -------------------------------------------------------------------------
	// Intent routing
	// -------------------------------------------------------------------------

	/**
	 * Main conversation entry point.
	 *
	 * Detects intent, retrieves context, renders response — each through its own
	 * dedicated method so every stage is independently testable.
	 *
	 * @param string $question Sanitized user question.
	 * @return array{ message: string }
	 */
	public function handle_question( string $question ): array {

		$intent = $this->detect_intent( $question );

		return $this->route_by_intent( $question, $intent );
	}

	/**
	 * Backward-compat alias — existing callers of chat_handler() keep working.
	 *
	 * @param string $question Sanitized user question.
	 * @return array{ message: string }
	 */
	public function chat_handler( string $question ): array {
		return $this->handle_question( $question );
	}

	/**
	 * Dispatches to the correct context-fetch + render pipeline for each intent.
	 *
	 * Keeping routing isolated from both detection and rendering lets every
	 * branch be tested without touching unrelated code.
	 *
	 * @param string $question Sanitized user question.
	 * @param string $intent   One of the INTENT_* constants.
	 * @return array{ message: string }
	 */
	private function route_by_intent( string $question, string $intent ): array {

		switch ( $intent ) {
			case self::INTENT_INVENTORY:
				return $this->inventory_handler( $question );

			case self::INTENT_SPECIALS:
				return $this->specials_handler( $question );

			case self::INTENT_WEBSITE:
				return $this->website_handler( $question );

			case self::INTENT_FAQ:
				return $this->faq_handler( $question );

			default:
				return $this->contact_fallback_handler();
		}
	}

	/**
	 * AI-based intent classifier (production version).
	 *
	 * @param string $question
	 * @return string
	 */
	private function detect_intent( string $question ): string {

		// ─────────────────────────────────────────────
		// CACHE (avoid repeated AI calls)
		// ─────────────────────────────────────────────
		$cache_key = 'ai_intent_' . md5( strtolower( trim( $question ) ) );
		$cached    = get_transient( $cache_key );

		if ( $cached ) {
			return $cached;
		}

		// ─────────────────────────────────────────────
		// STRICT CLASSIFICATION PROMPT
		// ─────────────────────────────────────────────
		$prompt = $this->build_intent_prompt( $question );

		if ( empty( $prompt ) ) {
			return self::INTENT_FAQ;
		}

		// ─────────────────────────────────────────────
		// AI REQUEST
		// ─────────────────────────────────────────────
		$response = wp_remote_post(
			'https://api.openai.com/v1/responses',
			array(
				'timeout' => 5,
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model' => 'gpt-4.1-mini',
						'input' => $prompt,
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'AI INTENT API ERROR: ' . $response->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return self::INTENT_WEBSITE;
		}

		// ─────────────────────────────────────────────
		// RESPONSE PARSING (SAFE)
		// ─────────────────────────────────────────────
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		$raw = $body['output'][0]['content'][0]['text'] ?? '';

		if ( empty( $raw ) ) {
			error_log( 'AI INTENT EMPTY RESPONSE | HTTP: ' . wp_remote_retrieve_response_code( $response ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return self::INTENT_WEBSITE;
		}

		// Normalize AI output (very important)
		$word = strtoupper(
			trim(
				preg_replace( '/[^A-Z]/i', '', $raw )
			)
		);

		// ─────────────────────────────────────────────
		// MAP RESULT
		// ─────────────────────────────────────────────
		$map = array(
			'INVENTORY' => self::INTENT_INVENTORY,
			'SPECIALS'  => self::INTENT_SPECIALS,
			'WEBSITE'   => self::INTENT_WEBSITE,
			'FAQ'       => self::INTENT_FAQ,
		);

		if ( ! isset( $map[ $word ] ) ) {

			error_log( 'AI INTENT UNKNOWN: ' . $word . ' | RAW: ' . $raw );

			// Default to WEBSITE (dealership info) — more useful than FAQ when intent is unclear.
			$result = self::INTENT_WEBSITE;

		} else {
			$result = $map[ $word ];
		}

		// ─────────────────────────────────────────────
		// CACHE RESULT (1 HOUR)
		// ─────────────────────────────────────────────
		set_transient( $cache_key, $result, HOUR_IN_SECONDS );

		return $result;
	}


	// -------------------------------------------------------------------------
	// Intent handlers — one per intent, each owns context + render
	// -------------------------------------------------------------------------

	/**
	 * FAQ intent handler.
	 *
	 * Embeds the question, scores FAQ records by cosine similarity, and answers
	 * from FAQ knowledge when confidence is high enough. Falls back to
	 * website_handler when the top FAQ score is below FAQ_CONFIDENCE_THRESHOLD,
	 * implementing the documented knowledge hierarchy.
	 *
	 * @param string $question User question.
	 * @return array{ message: string }
	 */
	private function faq_handler( string $question ): array {

		$question_embedding = $this->get_embedding( $question );

		if ( empty( $question_embedding ) ) {
			return $this->contact_fallback_handler();
		}

		$faq_result  = $this->fetch_faq_context( $question, $question_embedding );
		$faq_context = $faq_result['context'];
		$top_score   = $faq_result['top_score'];

		// Knowledge hierarchy: fall back to website content when FAQ confidence is low.
		if ( $top_score < self::FAQ_CONFIDENCE_THRESHOLD ) {
			return $this->website_handler( $question, $question_embedding );
		}

		return array( 'message' => $this->process_contact_tags( $this->render_faq_response( $question, $faq_context ) ) );
	}

	/**
	 * WEBSITE intent handler.
	 *
	 * Embeds the question (or reuses a pre-computed embedding passed from
	 * faq_handler during a confidence fallback), retrieves the best-matching
	 * crawled website pages, and answers using only dealership website content.
	 * FAQ records are not consulted.
	 *
	 * @param string $question           User question.
	 * @param array  $question_embedding Optional pre-computed embedding (avoids a second API call).
	 * @return array{ message: string }
	 */
	private function website_handler( string $question, array $question_embedding = array() ): array {

		if ( empty( $question_embedding ) ) {
			$question_embedding = $this->get_embedding( $question );
			// If embedding fails, fetch_website_context falls back to metadata-boost
			// scoring only — still better than a generic contact fallback.
		}

		$website_context = $this->fetch_website_context( $question, $question_embedding );

		if ( empty( $website_context ) ) {
			return $this->contact_fallback_handler();
		}

		return array( 'message' => $this->process_contact_tags( $this->render_website_response( $question, $website_context ) ) );
	}

	/**
	 * INVENTORY intent handler.
	 *
	 * Uses AI-extracted filters to query the live inventory feed, then renders
	 * a structured vehicle list response.
	 *
	 * @param string $question User question.
	 * @return array{ message: string }
	 */
	private function inventory_handler( string $question ): array {

		$context = $this->fetch_inventory_context( $question );

		return array( 'message' => $this->process_contact_tags( $this->render_inventory_response( $question, $context ) ) );
	}

	/**
	 * SPECIALS intent handler.
	 *
	 * Extracts offer filters (via AI), queries the specials feed, and renders
	 * a structured offers response. Does not share a prompt with inventory.
	 *
	 * @param string $question User question.
	 * @return array{ message: string }
	 */
	private function specials_handler( string $question ): array {

		$context = $this->fetch_specials_context( $question );

		return array( 'message' => $this->process_contact_tags( $this->render_specials_response( $question, $context ) ) );
	}

	// -------------------------------------------------------------------------
	// Context retrieval — one method per intent, returns raw context data
	// -------------------------------------------------------------------------

	/**
	 * Retrieves and scores FAQ records for the given question.
	 *
	 * Accepts the pre-computed question embedding so the caller's embedding
	 * is reused and no second OpenAI round-trip is made.
	 *
	 * Returns both the formatted context string and the top cosine score so
	 * faq_handler can enforce FAQ_CONFIDENCE_THRESHOLD before using the context.
	 *
	 * @param string $question           User question.
	 * @param array  $question_embedding Pre-computed embedding vector.
	 * @return array{ context: string, top_score: float }
	 */
	private function fetch_faq_context( string $question, array $question_embedding ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$faqs        = $this->get_faq_embeddings();
		$scored_faqs = $this->score_faqs_by_similarity( $question_embedding, $faqs );
		$top_score   = isset( $scored_faqs[0]['score'] ) ? (float) $scored_faqs[0]['score'] : 0.0;
		$top_faqs    = array_column( array_slice( $scored_faqs, 0, self::FAQ_LIMIT ), 'faq' );

		return array(
			'context'   => $this->build_faq_context( $top_faqs ),
			'top_score' => $top_score,
		);
	}

	// -------------------------------------------------------------------------
	// Response rendering — one method per intent, each calls ask_ai()
	// -------------------------------------------------------------------------

	/**
	 * Generates a response using only FAQ context.
	 *
	 * @param string $question    User question.
	 * @param string $faq_context Formatted FAQ pairs (context string from fetch_faq_context()).
	 * @return string AI-generated response text.
	 */
	private function render_faq_response( string $question, string $faq_context ): string {

		$prompt = $this->build_faq_prompt( $question, $faq_context );

		return $this->ask_ai( $prompt );
	}

	/**
	 * Generates a response using only crawled website content.
	 *
	 * @param string $question        User question.
	 * @param string $website_context Formatted page blocks from fetch_website_context().
	 * @return string AI-generated response text.
	 */
	private function render_website_response( string $question, string $website_context ): string {

		$prompt = $this->build_website_prompt( $question, $website_context );

		return $this->ask_ai( $prompt );
	}

	/**
	 * Generates a structured vehicle-list response from inventory data.
	 *
	 * @param string $question User question.
	 * @param array  $context  Normalized inventory payload from fetch_inventory_context().
	 * @return string AI-generated HTML response text.
	 */
	private function render_inventory_response( string $question, array $context ): string {

		$inventory_json = ! empty( $context ) ? (string) wp_json_encode( $context ) : '';
		$prompt         = $this->build_inventory_prompt( $question, $inventory_json );
		$message        = $this->ask_ai( $prompt );

		return $this->convert_markdown_links( $message );
	}

	/**
	 * Generates a structured specials/offers response.
	 *
	 * @param string $question User question.
	 * @param array  $context  Normalized specials payload from fetch_specials_context().
	 * @return string AI-generated HTML response text.
	 */
	private function render_specials_response( string $question, array $context ): string {

		$specials      = array_slice( $context['specials'] ?? array(), 0, 5 );
		$specials_json = ! empty( $specials ) ? (string) wp_json_encode( $context ) : '';
		$prompt        = $this->build_specials_prompt( $question, $specials_json );
		$message       = $this->ask_ai( $prompt );
		$message       = $this->convert_markdown_links( $message );
		$message       = $this->inject_specials_links( $message, $specials );

		return $message;
	}

	/**
	 * Replaces [SPECIAL_LINK:{id}] tokens in AI output with real anchor tags.
	 *
	 * The AI outputs tokens like [SPECIAL_LINK:4821] instead of URLs. This
	 * method does an exact ID lookup against the specials data and replaces
	 * each token with <a href="...">View Offer</a>. Tokens with unknown IDs
	 * are removed silently.
	 *
	 * @param string $html     AI-generated HTML.
	 * @param array  $specials Normalised specials array (each item has 'id' and 'url').
	 * @return string HTML with resolved anchor tags.
	 */
	/**
	 * Strips any AI-invented links from the response and appends real offer
	 * cards built directly from the specials data. The AI is no longer trusted
	 * to produce correct URLs — PHP owns all link generation.
	 *
	 * @param string $html     AI-generated HTML (links stripped).
	 * @param array  $specials Normalised specials (max 5, already sliced).
	 * @return string HTML with real offer links appended.
	 */
	private function inject_specials_links( string $html, array $specials ): string {

		if ( empty( $specials ) ) {
			return $html;
		}

		// Remove any <a> tags the AI invented so no fake URLs survive.
		$html = preg_replace( '/<a\b[^>]*>(.*?)<\/a>/is', '$1', $html );

		// Collect valid URLs in order.
		$urls = array();
		foreach ( $specials as $special ) {
			$url = $special['url'] ?? '';
			if ( ! empty( $url ) ) {
				$urls[] = esc_url( $url );
			}
		}

		if ( empty( $urls ) ) {
			return $html;
		}

		// Inject each URL into the matching special-offer__actions div by position.
		$index = 0;
		$html  = preg_replace_callback(
			'/<div class="special-offer__actions">(.*?)<\/div>/is',
			function ( $matches ) use ( $urls, &$index ) {
				if ( isset( $urls[ $index ] ) ) {
					$link = '<a href="' . $urls[ $index ] . '" class="btn btn-primary">View Offer</a>';
					++$index;
					return '<div class="special-offer__actions">' . $link . '</div>';
				}
				++$index;
				return $matches[0];
			},
			$html
		);

		// Append any remaining links if there are more specials than cards.
		for ( $i = $index; $i < count( $urls ); $i++ ) {
			$html .= '<p><a href="' . $urls[ $i ] . '" class="btn btn-primary">View Offer</a></p>';
		}

		return $html;
	}

	// -------------------------------------------------------------------------
	// Contact fallback
	// -------------------------------------------------------------------------

	/**
	 * Returns a contact-modal fallback response.
	 *
	 * @return array{ message: string }
	 */
	private function contact_fallback_handler(): array {

		$html  = '<p>' . esc_html__( "I'm not sure I can answer that accurately. Let me connect you with our team.", 'shopperexpress' ) . '</p>';
		$html .= '<div class="ai-chat__contact-btn-wrap">';
		$html .= '<button type="button" class="btn btn-primary btn-lg" data-toggle="modal" data-target="#aiContactModal">'
		. esc_html__( 'Contact Us', 'shopperexpress' )
		. '</button>';
		$html .= '</div>';

		return array( 'message' => $html );
	}

	// -------------------------------------------------------------------------
	// FAQ: embeddings, similarity, context building
	// -------------------------------------------------------------------------

	/**
	 * Loads all published FAQ posts with their stored embeddings.
	 *
	 * Results are object-cached for one hour. Cache is busted on FAQ save via
	 * on_save_faq(). If a post is missing an embedding (e.g. newly published
	 * before the save hook ran), it is embedded on-the-fly and stored.
	 *
	 * @return array
	 */
	private function get_faq_embeddings(): array {

		$cache_key = 'faq_embeddings';
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'faq',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'no_found_rows'  => true,
				'fields'         => 'all',
			)
		);

		$faqs = array();

		foreach ( $query->posts as $post ) {

			$raw = get_post_meta( $post->ID, '_faq_embedding', true );

			if ( empty( $raw ) ) {
				// Generate on-the-fly for posts saved before the hook existed.
				$text   = $post->post_title . ' ' . wp_strip_all_tags( $post->post_content );
				$vector = $this->get_embedding( $text );

				if ( empty( $vector ) ) {
					continue;
				}

				$raw = wp_json_encode( $vector );
				update_post_meta( $post->ID, '_faq_embedding', $raw );
			}

			$embedding = json_decode( $raw, true );

			if ( ! is_array( $embedding ) || empty( $embedding ) ) {
				continue;
			}

			$faqs[] = array(
				'question'  => $post->post_title,
				'answer'    => wp_kses_post( $post->post_content ),
				'embedding' => $embedding,
				'intent'    => (string) get_field( 'intent', $post->ID ),
				'category'  => (string) get_field( 'category', $post->ID ),
				'oem'       => (string) get_field( 'oem', $post->ID ),
				'model'     => (string) get_field( 'model', $post->ID ),
				'cta_type'  => (string) get_field( 'cta_type', $post->ID ),
				'page'      => (string) get_field( 'page', $post->ID ),
			);
		}

		wp_reset_postdata();

		wp_cache_set( $cache_key, $faqs, self::CACHE_GROUP, HOUR_IN_SECONDS );

		return $faqs;
	}

	/**
	 * Returns all FAQs scored by cosine similarity to the question embedding,
	 * sorted highest to lowest.
	 *
	 * @param array $question_embedding Embedding vector for the question.
	 * @param array $faqs              All FAQ records (must contain 'embedding' key).
	 * @return array[] Each element: [ 'score' => float, 'faq' => array ]
	 */
	private function score_faqs_by_similarity( array $question_embedding, array $faqs ): array {

		$scored = array();

		foreach ( $faqs as $faq ) {
			if ( empty( $faq['embedding'] ) || ! is_array( $faq['embedding'] ) ) {
				continue;
			}

			$scored[] = array(
				'score' => $this->cosine_similarity( $question_embedding, $faq['embedding'] ),
				'faq'   => $faq,
			);
		}

		usort( $scored, fn( $a, $b ) => $b['score'] <=> $a['score'] );

		return $scored;
	}

	/**
	 * Assembles a structured context block from the top FAQ matches.
	 *
	 * @param array $faqs Top-ranked FAQ records.
	 * @return string Plain-text context string for prompt injection.
	 */
	private function build_faq_context( array $faqs ): string {

		$context = '';

		foreach ( $faqs as $faq ) {
			$context .= "Q: {$faq['question']}\n";
			$context .= "A: {$faq['answer']}\n";

			foreach ( array( 'intent', 'category', 'oem', 'model', 'cta_type', 'page' ) as $field ) {
				if ( ! empty( $faq[ $field ] ) ) {
					$context .= ucfirst( $field ) . ": {$faq[$field]}\n";
				}
			}

			$context .= "\n---\n\n";
		}

		return $context;
	}

	// -------------------------------------------------------------------------
	// Website content retrieval
	// -------------------------------------------------------------------------

	/**
	 * Retrieves and scores crawled website pages relevant to the question.
	 *
	 * Accepts a pre-computed question embedding so the caller (website_handler or
	 * faq_handler) does not need to make a second OpenAI API call.
	 *
	 * Scoring strategy (combined):
	 *   - Cosine similarity between question embedding and page embedding (primary)
	 *   - Metadata keyword boost: department / OEM / model match (secondary)
	 *
	 * Pages below WEBSITE_MIN_SCORE are excluded to avoid injecting noise.
	 *
	 * @param string $question           User question (used for metadata boost).
	 * @param array  $question_embedding Pre-computed embedding vector (may be empty).
	 * @return string Formatted context string, or empty string if nothing useful found.
	 */
	private function fetch_website_context( string $question, array $question_embedding = array() ): string {

		$pages = $this->load_website_content_pages();

		if ( empty( $pages ) ) {
			return '';
		}

		$has_embedding = ! empty( $question_embedding );
		$scored        = array();

		foreach ( $pages as $page ) {
			$score = 0.0;

			if ( $has_embedding && ! empty( $page['embedding'] ) ) {
				$score = $this->cosine_similarity( $question_embedding, $page['embedding'] );
			}

			$score += $this->compute_metadata_boost( $question, $page );

			// Skip pages with no meaningful relevance when we have embeddings to judge by.
			if ( $has_embedding && $score < self::WEBSITE_MIN_SCORE ) {
				continue;
			}

			$scored[] = array(
				'score' => $score,
				'page'  => $page,
			);
		}

		if ( empty( $scored ) ) {
			return '';
		}

		usort( $scored, fn( $a, $b ) => $b['score'] <=> $a['score'] );

		$top = array_slice( $scored, 0, self::WEBSITE_CONTEXT_LIMIT );

		return $this->build_website_context_string( array_column( $top, 'page' ) );
	}

	/**
	 * Loads all published website-content posts with ACF metadata and embeddings.
	 *
	 * Cached for 30 minutes; busted on on_save_website_content().
	 *
	 * Field priority for the indexable body text:
	 *   1. `summary` ACF field
	 *   2. `important_facts` ACF field
	 *   3. Raw post_content (stripped of HTML)
	 *
	 * This ensures every published page has usable body text even when ACF
	 * fields are only partially filled.
	 *
	 * @return array
	 */
	private function load_website_content_pages(): array {

		$cache_key = 'website_content_pages';
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$query = new WP_Query(
			array(
				'post_type'      => self::WEBSITE_CONTENT_CPT,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'no_found_rows'  => true,
				'fields'         => 'all',
			)
		);

		$pages = array();

		foreach ( $query->posts as $post ) {
			$pages[] = $this->build_page_record( $post );
		}

		wp_reset_postdata();

		wp_cache_set( $cache_key, $pages, self::CACHE_GROUP, 30 * MINUTE_IN_SECONDS );

		return $pages;
	}

	/**
	 * Builds a single normalised page record from a WP_Post and its ACF fields.
	 *
	 * Separated from the loop in load_website_content_pages() so the same
	 * normalisation logic can be reused by on_save_website_content().
	 *
	 * @param \WP_Post $post The post object.
	 * @return array Normalised page record.
	 */
	private function build_page_record( \WP_Post $post ): array {

		// ── Body text (for prompt injection) ─────────────────────────────────
		$summary = get_field( 'summary', $post->ID );
		$facts   = get_field( 'important_facts', $post->ID );

		$summary_text = '';
		if ( ! empty( $summary ) ) {
			$summary_text = is_array( $summary ) ? implode( ' ', $summary ) : (string) $summary;
		}

		$facts_text = '';
		if ( ! empty( $facts ) ) {
			$facts_text = is_array( $facts ) ? implode( ' ', $facts ) : (string) $facts;
		}

		// Always fall back to post_content so no page is silently skipped.
		if ( ! empty( $summary_text ) ) {
			$body = $summary_text;
		} elseif ( ! empty( $facts_text ) ) {
			$body = $facts_text;
		} else {
			$body = wp_strip_all_tags( $post->post_content );
		}

		// ── Embedding ─────────────────────────────────────────────────────────
		$raw_embedding = get_post_meta( $post->ID, '_website_content_embedding', true );
		$embedding     = array();

		if ( ! empty( $raw_embedding ) && is_string( $raw_embedding ) ) {
			$decoded = json_decode( $raw_embedding, true );
			if ( is_array( $decoded ) && ! empty( $decoded ) ) {
				$embedding = $decoded;
			}
		}

		// ── Title: prefer ACF 'title' field, fall back to post_title ─────────
		$acf_title = (string) get_field( 'title', $post->ID );
		$title     = ! empty( $acf_title ) ? $acf_title : $post->post_title;

		return array(
			'post_id'       => $post->ID,
			'title'         => $title,
			'url'           => (string) get_field( 'page_url', $post->ID ),
			'body'          => $body,
			'department'    => (string) get_field( 'department', $post->ID ),
			'oem'           => (string) get_field( 'oem', $post->ID ),
			'model'         => (string) get_field( 'model', $post->ID ),
			'page_category' => (string) get_field( 'page_category', $post->ID ),
			'primary_cta'   => (string) get_field( 'primary_cta', $post->ID ),
			'secondary_cta' => (string) get_field( 'secondary_cta', $post->ID ),
			'last_crawled'  => (string) get_field( 'last_crawled', $post->ID ),
			'embedding'     => $embedding,
		);
	}

	/**
	 * Computes a metadata keyword boost score (0.0–0.6) for one page.
	 *
	 * Adds to the cosine similarity score so pages with strong metadata matches
	 * are preferred even when their body embeddings are only moderately similar.
	 *
	 * @param string $question User question (not yet lowercased).
	 * @param array  $page     Normalised page record.
	 * @return float Boost in range [0.0, 0.6].
	 */
	private function compute_metadata_boost( string $question, array $page ): float {

		$q     = strtolower( $question );
		$boost = 0.0;

		$dept_keywords = array(
			'service' => array( 'service', 'oil', 'repair', 'maintenance', 'tire', 'brake', 'recall', 'battery', 'wiper', 'inspection', 'coolant' ),
			'finance' => array( 'finance', 'financing', 'loan', 'credit', 'apr', 'apply', 'pre-approv', 'pre approv' ),
			'trade'   => array( 'trade', 'trade-in', 'trade in', 'appraisal', 'sell my', 'my car' ),
			'parts'   => array( 'parts', 'part', 'accessori', 'order', 'replacement', 'spare' ),
			'sales'   => array( 'buy', 'purchase', 'price', 'cost', 'new car', 'used car', 'test drive' ),
			'general' => array(),
		);

		$dept = strtolower( $page['department'] ?? '' );

		if ( ! empty( $dept ) && isset( $dept_keywords[ $dept ] ) ) {
			foreach ( $dept_keywords[ $dept ] as $kw ) {
				if ( strpos( $q, $kw ) !== false ) {
					$boost += 0.2;
					break;
				}
			}
		}

		if ( ! empty( $page['oem'] ) && stripos( $q, $page['oem'] ) !== false ) {
			$boost += 0.2;
		}

		if ( ! empty( $page['model'] ) && stripos( $q, $page['model'] ) !== false ) {
			$boost += 0.2;
		}

		return min( $boost, 0.6 );
	}

	/**
	 * Assembles the formatted context block from ranked website pages.
	 *
	 * Each page emits its metadata header + truncated body. The `url`,
	 * `page_category`, `secondary_cta`, and `last_crawled` fields are included
	 * so the prompt template and AI can reference them when relevant.
	 *
	 * @param array $pages Ordered (highest score first) page records.
	 * @return string Context string ready for prompt injection.
	 */
	private function build_website_context_string( array $pages ): string {

		$context = '';

		foreach ( $pages as $page ) {

			$body = trim( substr( $page['body'], 0, self::WEBSITE_CONTENT_MAX_CHARS ) );

			if ( ! empty( $page['title'] ) ) {
				$context .= "Page: {$page['title']}\n";
			}

			if ( ! empty( $page['url'] ) ) {
				$context .= "URL: {$page['url']}\n";
			}

			if ( ! empty( $page['department'] ) ) {
				$context .= "Department: {$page['department']}\n";
			}

			if ( ! empty( $page['page_category'] ) ) {
				$context .= "Category: {$page['page_category']}\n";
			}

			if ( ! empty( $page['oem'] ) ) {
				$context .= "OEM: {$page['oem']}\n";
			}

			if ( ! empty( $page['model'] ) ) {
				$context .= "Model: {$page['model']}\n";
			}

			if ( ! empty( $page['last_crawled'] ) ) {
				$context .= "Last Updated: {$page['last_crawled']}\n";
			}

			if ( ! empty( $body ) ) {
				$context .= "Content:\n{$body}\n";
			}

			if ( ! empty( $page['primary_cta'] ) ) {
				$context .= "Primary CTA: {$page['primary_cta']}\n";
			}

			if ( ! empty( $page['secondary_cta'] ) ) {
				$context .= "Secondary CTA: {$page['secondary_cta']}\n";
			}

			$context .= "\n---\n\n";
		}

		return $context;
	}

	/**
	 * Extracts inventory filters from a question.
	 *
	 * @param string $question User question.
	 * @return array Inventory filters.
	 */
	private function ai_extract_inventory_filters( string $question ): array {

		$prompt_template = 'You are a vehicle inventory filter extraction engine.

		Extract filters from the user query.

		RETURN ONLY VALID RAW JSON.
		NO markdown.
		NO code blocks.
		NO explanations.
		NO backticks.

		OUTPUT FORMAT MUST BE EXACTLY THIS:

		{
		\"make\": \"\",
		\"model\": \"\",
		\"body_type\": \"\",
		\"interior_color\": \"\",
		\"exterior_color\": \"\",
		\"condition\": \"\",
		\"post_type\": \"\",
		\"min_price\": 0,
		\"max_price\": 0,
		\"year\": 0
		}

		RULES:
		- If value is missing → use \"\" or 0
		- Normalize makes:
		chevy → Chevrolet
		vw → Volkswagen
		merc → Mercedes-Benz

		- Normalize body types:
		suv, sedan, truck, coupe, hatchback, wagon, van

		CONDITION / POST TYPE RULES:
		- If user says: used, pre-owned, second hand → 
		\"condition\": \"used\"
		\"post_type\": \"used-listings\"

		- If user says: new, brand new →
		\"condition\": \"new\"
		\"post_type\": \"listings\"

		- If condition is not specified:
		\"condition\": \"\"
		\"post_type\": \"\"

		PRICE RULES:
		- Prices must be numeric only
		- Convert:
		20k → 20000
		25 grand → 25000

		BUDGET RULE:
		- Phrases like:
		under 20000
		below 30000
		budget 25000
		up to 40000
		max 18000

		must populate:
		\"max_price\"

		IMPORTANT:
		If user only specifies budget, still return valid filters.

		Do NOT include any text before or after JSON.

		EXAMPLES:

		Input: \"I want a used Honda under 25000\"

		Output:
		{
		\"make\": \"Honda\",
		\"model\": \"\",
		\"body_type\": \"\",
		\"interior_color\": \"\",
		\"exterior_color\": \"\",
		\"condition\": \"used\",
		\"post_type\": \"used-listings\",
		\"min_price\": 0,
		\"max_price\": 25000,
		\"year\": 0
		}

		Input: \"new SUV under 40000\"

		Output:
		{
		\"make\": \"\",
		\"model\": \"\",
		\"body_type\": \"suv\",
		\"interior_color\": \"\",
		\"exterior_color\": \"\",
		\"condition\": \"new\",
		\"post_type\": \"listings\",
		\"min_price\": 0,
		\"max_price\": 40000,
		\"year\": 0
		}

		Now process this:

		[question]';

		$prompt_template = get_field( 'ai_inventory_filter_promt', 'option' ) ? get_field( 'ai_inventory_filter_promt', 'option' ) : $prompt_template;

		$prompt = str_replace( '[question]', $question, $prompt_template );

		$response = wp_remote_post(
			'https://api.openai.com/v1/responses',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model' => 'gpt-4.1-mini',
						'input' => $prompt,
					)
				),
			)
		);

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		$json = $body['output'][0]['content'][0]['text'] ?? '{}';

		return json_decode( $json, true ) ?: array();
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
	// Inventory data retrieval
	// -------------------------------------------------------------------------

	/**
	 * Fetches live inventory and applies parse → filter → sort → slice pipeline.
	 *
	 * Returns JSON { count, vehicles } so the AI can report totals without
	 * receiving an unbounded payload.
	 *
	 * @param string $question User question.
	 * @return string JSON-encoded payload or empty string on failure.
	 */
	private function fetch_inventory_context( string $question, int $limit = 20 ): array {

		// 1. Cache check first — avoid AI round-trip on warm cache.
		$cache_key = 'ai_inv_' . md5( $question . $limit );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		// 3. AI extracts filters.
		$filters = $this->ai_extract_inventory_filters( $question );

		// 4. Call REST API with GET params.
		$url = add_query_arg(
			array_merge(
				$filters,
				array(
					'limit' => $limit,
				)
			),
			home_url( '/wp-json/ai/v1/inventory' )
		);

		$response = wp_remote_get(
			$url,
			array(
				'sslverify' => ( 'https' === parse_url( $url, PHP_URL_SCHEME ) ),
				'headers'   => $this->get_basic_auth_headers(),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'count'    => 0,
				'vehicles' => array(),
				'error'    => 'API error',
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		set_transient( $cache_key, $data, 10 * MINUTE_IN_SECONDS );

		return $data;
	}


	/**
	 * Fetches active specials from the local REST endpoint, with a short transient cache.
	 *
	 * Filters are extracted via AI (build_specials_query) so natural-language
	 * phrasing is parsed accurately without a brittle regex list.
	 *
	 * @param string $question User question.
	 * @return array{ count: int, specials: array, applied_filters: array }
	 */
	private function fetch_specials_context( string $question ): array {

		$filters = $this->build_specials_query( $question );

		$cache_key = 'ai_spec_' . md5( $question );
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) && ! empty( $cached['specials'] ) ) {
			return $cached;
		}

		// Call the data layer directly — avoids HTTP loopback failures on
		// local/staging environments with SSL or auth issues.
		$all_specials = $this->load_all_specials();

		$normalized_filters = array(
			'make'        => sanitize_text_field( $filters['make'] ?? '' ),
			'model'       => sanitize_text_field( $filters['model'] ?? '' ),
			'trim'        => sanitize_text_field( $filters['trim'] ?? '' ),
			'year'        => ! empty( $filters['year'] ) ? (int) $filters['year'] : 0,
			'condition'   => sanitize_text_field( $filters['condition'] ?? '' ),
			'post_type'   => sanitize_text_field( $filters['post_type'] ?? '' ),
			'min_payment' => ! empty( $filters['min_payment'] ) ? (float) $filters['min_payment'] : 0.0,
			'max_payment' => ! empty( $filters['max_payment'] ) ? (float) $filters['max_payment'] : 0.0,
			'active_only' => true,
		);

		$filtered = $this->apply_specials_filters( $all_specials, $normalized_filters );
		$sliced   = array_slice( $filtered, 0, 20 );

		$applied = array_filter(
			$normalized_filters,
			fn( $v ) => '' !== $v && 0 !== $v && 0.0 !== $v && true !== $v
		);

		$data = array(
			'count'           => count( $sliced ),
			'specials'        => $sliced,
			'applied_filters' => $applied,
		);

		set_transient( $cache_key, $data, 10 * MINUTE_IN_SECONDS );

		return $data;
	}

	/**
	 * Extracts structured specials filters from a natural-language question via AI.
	 *
	 * Replaces the old regex-based parse_specials_filters(). Returns a flat array
	 * of query params compatible with the /wp-json/ai/v1/specials REST endpoint.
	 * Falls back to an empty array on any API failure so the REST call still runs
	 * with no filters (returns all active specials).
	 *
	 * @param string $question User question.
	 * @return array
	 */
	private function build_specials_query( string $question ): array {

		if ( empty( $this->api_key ) ) {
			return array();
		}

		$prompt = $this->build_specials_filter_prompt( $question );

		$response = wp_remote_post(
			'https://api.openai.com/v1/responses',
			array(
				'timeout' => 5,
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model' => 'gpt-4.1-mini',
						'input' => $prompt,
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$json = $body['output'][0]['content'][0]['text'] ?? '{}';
		$data = json_decode( $json, true );

		if ( ! is_array( $data ) ) {
			return array();
		}

		// Map AI output keys to REST endpoint param names and sanitize types.
		$filters = array();

		if ( ! empty( $data['make'] ) ) {
			$filters['make'] = sanitize_text_field( $data['make'] );
		}
		if ( ! empty( $data['model'] ) ) {
			$filters['model'] = sanitize_text_field( $data['model'] );
		}
		if ( ! empty( $data['trim'] ) ) {
			$filters['trim'] = sanitize_text_field( $data['trim'] );
		}
		if ( ! empty( $data['year'] ) ) {
			$filters['year'] = (int) $data['year'];
		}
		if ( ! empty( $data['condition'] ) ) {
			$filters['condition'] = sanitize_text_field( $data['condition'] );
		}
		if ( ! empty( $data['post_type'] ) ) {
			$filters['post_type'] = sanitize_text_field( $data['post_type'] );
		}
		if ( ! empty( $data['max_payment'] ) ) {
			$filters['max_payment'] = (float) $data['max_payment'];
		}
		if ( ! empty( $data['min_payment'] ) ) {
			$filters['min_payment'] = (float) $data['min_payment'];
		}

		return $filters;
	}

	/**
	 * Backward-compat alias for any code still calling parse_specials_filters().
	 *
	 * @param string $question User question.
	 * @return array
	 */
	private function parse_specials_filters( string $question ): array {
		return $this->build_specials_query( $question );
	}

	// -------------------------------------------------------------------------
	// Prompt builders — one per pipeline stage, all prompts hardcoded in PHP.
	// Move individual templates to ACF option fields (ai_*_promt) to override.
	// -------------------------------------------------------------------------

	/**
	 * Builds the intent-classification prompt.
	 *
	 * Returns ONLY one word: INVENTORY | SPECIALS | WEBSITE | FAQ.
	 * No answer is generated here — routing only.
	 *
	 * Placeholder: [question]
	 *
	 * @param string $question User question.
	 * @return string Fully assembled prompt.
	 */
	private function build_intent_prompt( string $question ): string {

		$acf = (string) get_field( 'ai_intent_promt', 'option' );

		$template = ! empty( $acf ) ? $acf : <<<'PROMPT'
			You are a strict intent classifier for a car dealership assistant.

			Return ONLY one exact word — no punctuation, no explanation, no extra text:

			INVENTORY
			SPECIALS
			WEBSITE
			FAQ

			CLASSIFICATION RULES (apply in order, stop at first match):

			1. SERVICE override — classify as WEBSITE when the question contains ANY of:
			service, maintenance, repair, oil change, tire, brake, appointment, parts, recall, inspection
			This takes priority even if the question also contains: specials, coupons, offers, discounts

			2. SPECIALS — classify as SPECIALS ONLY for vehicle purchase financial offers:
			lease, finance, APR, 0%, monthly payment, cash back, incentive, rebate, purchase offer

			3. INVENTORY — classify as INVENTORY for:
			vehicle availability, search inventory, price filter, make/model/year/trim, new car, used car, in stock, what do you have

			4. WEBSITE — classify as WEBSITE for:
			dealership info, contact, hours, location, directions, service, parts, trade-in, appointment, about us

			5. FAQ — everything else

			User question:
			"""[question]"""
			PROMPT;

		return str_replace( '[question]', $question, $template );
	}

	/**
	 * Builds the FAQ response prompt.
	 *
	 * Answers ONLY from FAQ knowledge. No website content, no inventory data.
	 * Placeholders: [faq_context], [question]
	 *
	 * @param string $question    User question.
	 * @param string $faq_context Formatted FAQ Q&A pairs.
	 * @return string Fully assembled prompt.
	 */
	private function build_faq_prompt( string $question, string $faq_context ): string {

		$acf = (string) get_field( 'ai_faq_promt', 'option' );

		$template = ! empty( $acf ) ? $acf : <<<'PROMPT'
			You are an FAQ assistant for a car dealership website.
			Answer ONLY using the FAQ pairs provided below.
			Do NOT use general knowledge. Do NOT invent answers.
			If the FAQ does not contain enough information to answer, respond with:
			Click the button below to open the contact form and connect with a dealer. [SALES]

			CONTACT ESCALATION RULES:
			When you cannot answer from FAQ data, end your response with exactly ONE of:
			- [SALES]   — buying, pricing, availability, financing
			- [SERVICE] — service, repair, maintenance, oil change, tire, brake
			- [PARTS]   — parts, accessories, replacement
			Default escalation: [SALES]

			FAQ Knowledge Base:
			[faq_context]

			User question: [question]
			PROMPT;

		return str_replace(
			array( '[faq_context]', '[question]' ),
			array( $faq_context, $question ),
			$template
		);
	}

	/**
	 * Builds the website content response prompt.
	 *
	 * Answers ONLY from crawled dealership website pages.
	 * No FAQ data, no inventory data.
	 *
	 * Placeholders: [website_context], [question]
	 *
	 * @param string $question        User question.
	 * @param string $website_context Formatted page blocks from fetch_website_context().
	 * @return string Fully assembled prompt.
	 */
	private function build_website_prompt( string $question, string $website_context ): string {

		$acf = (string) get_field( 'ai_website_promt', 'option' );

		$template = ! empty( $acf ) ? $acf : <<<'PROMPT'
		You are a dealership information assistant.
		Answer ONLY using the website content provided below.
		Do NOT use general knowledge. Do NOT invent facts about the dealership.
		If the content does not answer the question, respond with:
		Click the button below to open the contact form and connect with a dealer. [SALES]

		RULES:
		- Use only explicit facts from the content blocks
		- Do not mention pages, URLs, or data sources in your answer
		- Include CTAs only when explicitly present in the context
		- Keep answers concise and factual

		CONTACT ESCALATION RULES:
		When you cannot answer from the provided content, end your response with exactly ONE of:
		- [SALES]   — buying, pricing, availability
		- [SERVICE] — service, repair, maintenance, appointment
		- [PARTS]   — parts, accessories
		Default escalation: [SALES]

		Dealership Website Content:
		[website_context]

		User question: [question]
		PROMPT;

		return str_replace(
			array( '[website_context]', '[question]' ),
			array( $website_context, $question ),
			$template
		);
	}

	/**
	 * Builds the inventory response prompt.
	 *
	 * Answers ONLY from live inventory data. No specials, no FAQ.
	 * Renders max 3 vehicles as an HTML list with real permalink IDs.
	 *
	 * Placeholders: [inventory_context], [question]
	 *
	 * @param string $question          User question.
	 * @param string $inventory_context JSON-encoded inventory payload.
	 * @return string Fully assembled prompt.
	 */
	private function build_inventory_prompt( string $question, string $inventory_context ): string {

		$template = 'You are a dealership inventory assistant.

		Answer ONLY using the inventory data provided below.
		Do NOT invent vehicles, prices, availability, or URLs.

		------------------------------------
		INVENTORY DATA
		------------------------------------

		[inventory_context]

		------------------------------------
		OUTPUT RULES
		------------------------------------

		If vehicles are found:
		1. First sentence must state the exact count from the data.
		Example: "There are 61 vehicles currently in stock."
		2. Render ONLY the first 3 vehicles as an HTML list.
		3. Vehicle link — use the "url" field from the data exactly as-is, never construct your own URL:
		<a href="URL_FROM_DATA">View Vehicle</a>
		4. If inventory_context is empty → say "No exact match was found." then suggest contacting the dealership.

		Format:

		Available models:
		<ul>
		<li>
			<strong>YEAR MAKE MODEL</strong>
			– PRICE (if present and greater than 0)
			<a href="URL_FROM_DATA">View Vehicle</a>
		</li>
		</ul>

		RULES:
		- Never render more than 3 vehicles
		- Never truncate HTML tags
		- Always close all tags
		- Use only fields present in the data
		- Skip PRICE if it is 0 or missing

		------------------------------------
		STRICT FINAL RULE

		If inventory_context is empty → ask user to contact dealership
		Otherwise ALWAYS render the vehicles list — never say "no match" when data is present

		User question: [question]';

		$acf = (string) get_field( 'ai_inventory_promt', 'option' );
		if ( ! empty( $acf ) ) {
			$template = $acf;
		}

		return str_replace(
			array( '[inventory_context]', '[question]' ),
			array( $inventory_context, $question ),
			$template
		);
	}

	/**
	 * Builds the specials / offers response prompt.
	 *
	 * Answers ONLY from structured specials data. No inventory vehicles.
	 * Renders matching offers with payment, term, expiration, and CTA link.
	 *
	 * Placeholders: [specials_context], [question]
	 *
	 * @param string $question       User question.
	 * @param string $specials_context JSON-encoded specials payload.
	 * @return string Fully assembled prompt.
	 */
	private function build_specials_prompt( string $question, string $specials_context ): string {

		$acf = (string) get_field( 'ai_specials_promt', 'option' );

		$template = ! empty( $acf ) ? $acf : <<<'PROMPT'
		You are a helpful dealership website assistant.

		Use ONLY the specials data provided below to answer the user's question.

		Your goal is to present matching specials in a friendly, easy-to-read format.

		Return valid HTML only. Do not use markdown. Do not invent offers, pricing, terms, expiration dates, or links.

		FORMATTING RULES:
		- Start with a short helpful sentence.
		- Display each matching offer as a separate card or paragraph.
		- Include the most useful details when available:
		  - Year
		  - Make
		  - Model
		  - Trim
		  - Offer type
		  - Monthly payment
		  - Term
		  - Due at signing
		  - Expiration date
		  - CTA link
		- If a value is missing, omit that line.
		- Do not show raw JSON.
		- Do not use technical labels like post_type.
		- Limit the response to the best 3 to 5 matching offers.
		- Do not output any links or URLs. CTA links are added automatically by the system after your response.
		- If no specials match, politely say that no matching specials were found and recommend viewing all current specials or contacting the dealership.

		EXAMPLE OUTPUT STYLE:
		<p>Here are the current Honda Civic lease specials I found:</p>
		<div class="special-offer">
		  <h3>2026 Honda Civic LX Lease Offer</h3>
		  <p><strong>Payment:</strong> $219/month</p>
		  <p><strong>Term:</strong> 36 months</p>
		  <p><strong>Due at signing:</strong> $3,899</p>
		  <p><strong>Expires:</strong> July 6, 2026</p>
		  <div class="special-offer__actions">
		    <p><a href="#" data-post_id="123">View</a></p>
		  </div>
		</div>

		CONTACT ESCALATION RULES:
		End your response with exactly ONE of:
		- [SALES]   — purchase, financing questions
		- [SERVICE] — service offers, maintenance specials
		Default escalation: [SALES]

		Matched specials: [specials_context]

		User question: [question]
		PROMPT;

		return str_replace(
			array( '[specials_context]', '[question]' ),
			array( $specials_context, $question ),
			$template
		);
	}

	/**
	 * Builds the specials filter extraction prompt.
	 *
	 * Used by build_specials_query() to extract structured REST params from a
	 * natural-language question. Returns JSON only — no user-facing answer.
	 *
	 * Placeholder: [question]
	 *
	 * @param string $question User question.
	 * @return string Fully assembled prompt.
	 */
	private function build_specials_filter_prompt( string $question ): string {

		$template = <<<'PROMPT'
		You are a specials search filter extraction engine for a car dealership website.

		Your job is to extract structured filters from the user's question so the system can search available specials.

		Return ONLY valid raw JSON. Do not include markdown, code blocks, comments, explanations, or text outside the JSON.

		Always return this exact JSON structure:
		{ "make": "", "model": "", "trim": "", "year": 0, "condition": "", "post_type": "", "min_payment": 0, "max_payment": 0 }

		EXTRACTION RULES:
		- Use "" for missing text values.
		- Use 0 for missing numeric values.
		- Normalize common make names:
		  - honda → Honda
		  - toyota → Toyota
		  - chevy → Chevrolet
		  - vw → Volkswagen
		  - merc → Mercedes-Benz

		Condition values:
		- Only use: "new", "used", or ""
		- new, brand new → "new"
		- used, pre-owned, second hand, certified pre-owned, cpo → "used"
		- If not stated, use ""

		Post type values:
		- Only use: "lease", "finance", "conditional", "service", or ""
		- lease, leasing, monthly lease → "lease"
		- finance, financing, APR, loan, payment offer → "finance"
		- service, oil change, maintenance, repair, coupon → "service"
		- conditional, rebate, incentive, bonus cash, loyalty, conquest, military, college grad → "conditional"
		- If not stated, use ""

		Payment rules:
		- Payment values must be numeric only.
		- Do not include $, commas, or text.
		- "under $300/month" → "max_payment": 300
		- "less than $300" → "max_payment": 300
		- "$300 or less" → "max_payment": 300
		- "around $300" → "max_payment": 300
		- "at least $200/month" → "min_payment": 200
		- "between $200 and $350" → "min_payment": 200, "max_payment": 350

		Do not guess values that are not clearly stated.

		User question: "[question]"
		PROMPT;
		$template = get_field( 'ai_specials_filter_promt', 'option' ) ? get_field( 'ai_specials_filter_promt', 'option' ) : $template;
		return str_replace( '[question]', $question, $template );
	}

	// -------------------------------------------------------------------------
	// Post-processing
	// -------------------------------------------------------------------------

	/**
	 * Replaces [SALES] / [PARTS] / [SERVICE] tags in AI output with a modal button.
	 *
	 * @param string $message Raw AI response text.
	 * @return string Processed HTML message.
	 */
	private function process_contact_tags( string $message ): string {

		if ( empty( $message ) ) {
			return $this->contact_fallback_handler()['message'];
		}

		if ( ! preg_match( '/\[(SALES|PARTS|SERVICE)\]/', $message, $match ) ) {
			return $message;
		}

		$map = array(
			'SALES'   => '#aiContactModal-ai_sales',
			'PARTS'   => '#aiContactModal-ai_parts',
			'SERVICE' => '#aiContactModal-ai_service',
		);

		$target  = isset( $map[ $match[1] ] ) ? $map[ $match[1] ] : '#aiContactModal';
		$message = (string) preg_replace( '/\[(SALES|PARTS|SERVICE)\]/', '', $message );

		$button  = '<div class="ai-chat__contact-btn-wrap">';
		$button .= '<button type="button" class="btn btn-primary btn-lg" data-toggle="modal" data-target="' . esc_attr( $target ) . '">'
			. esc_html__( 'Contact me', 'shopperexpress' )
			. '</button>';
		$button .= '</div>';

		return $message . "\n" . $button;
	}

	/**
	 * Sanitizes AI-generated anchor tags in inventory/specials responses.
	 *
	 * The AI is instructed to output full WordPress permalink URLs directly in
	 * <a href="..."> tags. This function escapes those hrefs to prevent any
	 * unintended output, and strips any legacy /vehicle?id= placeholder URLs
	 * that an older prompt version may have produced.
	 *
	 * @param string $message Raw AI response text.
	 * @return string
	 */
	private function convert_markdown_links( string $message ): string {

		// Remove legacy placeholder links that an old prompt variant may emit.
		$message = preg_replace_callback(
			'/<a\s+href="\/vehicle\?id=(\d+)">([^<]+)<\/a>/i',
			function ( array $matches ): string {
				$vehicle_id = (int) $matches[1];
				$label      = $matches[2];
				$url        = $vehicle_id ? get_permalink( $vehicle_id ) : false;
				return $url
					? '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>'
					: esc_html( $label );
			},
			$message
		);

		// Escape hrefs in any real permalink <a> tags the AI outputs.
		$message = preg_replace_callback(
			'/<a\s+href="([^"]+)">([^<]+)<\/a>/i',
			function ( array $matches ): string {
				return '<a href="' . esc_url( $matches[1] ) . '">' . esc_html( $matches[2] ) . '</a>';
			},
			$message
		);

		return $message;
	}

	// -------------------------------------------------------------------------
	// Embedding save hooks
	// -------------------------------------------------------------------------

	/**
	 * Regenerates and stores the FAQ embedding when a FAQ post is saved.
	 *
	 * The API key is loaded here directly so this hook works regardless of
	 * whether handle_request() was called first.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function on_save_faq( int $post_id, \WP_Post $post ): void {

		if ( wp_is_post_revision( $post_id ) || 'auto-draft' === $post->post_status ) {
			return;
		}

		$this->api_key = (string) get_field( 'ai_api_key', 'option' );

		if ( empty( $this->api_key ) ) {
			return;
		}

		$fields = array_filter(
			array(
				$post->post_title,
				wp_strip_all_tags( $post->post_content ),
				(string) get_field( 'dealer_id', $post_id ),
				(string) get_field( 'type', $post_id ),
				(string) get_field( 'intent', $post_id ),
				(string) get_field( 'page', $post_id ),
				(string) get_field( 'context', $post_id ),
				(string) get_field( 'cta_type', $post_id ),
				(string) get_field( 'category', $post_id ),
				(string) get_field( 'oem', $post_id ),
				(string) get_field( 'model', $post_id ),
			)
		);

		$embedding = $this->get_embedding( implode( ' ', $fields ) );

		if ( ! empty( $embedding ) ) {
			update_post_meta( $post_id, '_faq_embedding', wp_json_encode( $embedding ) );
			wp_cache_delete( 'faq_embeddings', self::CACHE_GROUP );
		}
	}

	/**
	 * Regenerates and stores the website-content embedding when a page is saved.
	 *
	 * Embedding text priority:
	 *   title + summary → title + important_facts → title + post_content
	 *
	 * The hook is registered for both slug variants (hyphen and underscore) to
	 * ensure it fires regardless of how WordPress normalises the CPT slug.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function on_save_website_content( int $post_id, \WP_Post $post ): void {

		if ( wp_is_post_revision( $post_id ) || 'auto-draft' === $post->post_status ) {
			return;
		}

		// Guard: ensure this is actually the correct CPT in case both hook
		// variants fire for a different post type.
		if ( ! in_array( $post->post_type, array( 'website-content', 'website_content' ), true ) ) {
			return;
		}

		// Build the embedding text using the same priority as build_page_record().
		$summary = get_field( 'summary', $post_id );
		$facts   = get_field( 'important_facts', $post_id );

		$summary_text = '';
		if ( ! empty( $summary ) ) {
			$summary_text = is_array( $summary ) ? implode( ' ', $summary ) : (string) $summary;
		}

		$facts_text = '';
		if ( ! empty( $facts ) ) {
			$facts_text = is_array( $facts ) ? implode( ' ', $facts ) : (string) $facts;
		}

		if ( ! empty( $summary_text ) ) {
			$body = $summary_text;
		} elseif ( ! empty( $facts_text ) ) {
			$body = $facts_text;
		} else {
			$body = wp_strip_all_tags( $post->post_content );
		}

		$text = trim( $post->post_title . ' ' . $body );

		if ( empty( $text ) ) {
			return;
		}

		$embedding = $this->get_embedding( $text );

		if ( ! empty( $embedding ) && is_array( $embedding ) ) {
			update_post_meta( $post_id, '_website_content_embedding', wp_json_encode( $embedding ) );
			wp_cache_delete( 'website_content_pages', self::CACHE_GROUP );
		}
	}

	// -------------------------------------------------------------------------
	// Rate limiting
	// -------------------------------------------------------------------------

	/**
	 * Per-IP rate limiter backed by the WordPress object cache.
	 *
	 * Returns true when the caller has exceeded RATE_LIMIT_MAX requests within
	 * the RATE_LIMIT_WINDOW second window.
	 *
	 * @return bool
	 */
	private function is_rate_limited(): bool {

		$key   = 'ai_rate_' . md5( $this->get_client_ip() );
		$count = (int) wp_cache_get( $key, self::CACHE_GROUP );

		if ( $count >= self::RATE_LIMIT_MAX ) {
			return true;
		}

		wp_cache_set( $key, $count + 1, self::CACHE_GROUP, self::RATE_LIMIT_WINDOW );

		return false;
	}

	/**
	 * Returns the client IP address.
	 *
	 * Checks Cloudflare, then X-Forwarded-For, then REMOTE_ADDR.
	 * Only trust proxy headers if you control the proxy layer.
	 *
	 * @return string
	 */
	private function get_client_ip(): string {

		$headers = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'REMOTE_ADDR',
		);

		foreach ( $headers as $key ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			$raw = isset( $_SERVER[ $key ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) : '';
			if ( ! empty( $raw ) ) {
				return trim( explode( ',', $raw )[0] );
			}
		}

		return 'unknown';
	}

	// -------------------------------------------------------------------------
	// OpenAI API primitives
	// -------------------------------------------------------------------------

	/**
	 * Returns the embedding vector for the given text string.
	 *
	 * Always returns a flat numeric array or an empty array on failure —
	 * never returns a non-vector structure that could crash cosine_similarity().
	 *
	 * @param string $text Input text (truncated to 8 000 chars before sending).
	 * @return float[]
	 */
	private function get_embedding( string $text ): array {

		if ( empty( $text ) || empty( $this->api_key ) ) {
			return array();
		}

		$response = wp_remote_post(
			'https://api.openai.com/v1/embeddings',
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $this->api_key,
				),
				'body'    => (string) wp_json_encode(
					array(
						'model' => 'text-embedding-3-small',
						'input' => substr( $text, 0, 8000 ),
					)
				),
				'timeout' => 20,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body      = json_decode( wp_remote_retrieve_body( $response ), true );
		$embedding = $body['data'][0]['embedding'] ?? array();

		if ( ! is_array( $embedding ) || empty( $embedding ) ) {
			return array();
		}

		return $embedding;
	}

	/**
	 * Sends a prompt to gpt-4o-mini and returns the response text.
	 *
	 * @param string $prompt Fully assembled prompt string.
	 * @return string AI response text, or empty string on any failure.
	 */
	private function ask_ai( string $prompt ): string {

		if ( empty( $prompt ) || empty( $this->api_key ) ) {
			return '';
		}

		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $this->api_key,
				),
				'body'    => (string) wp_json_encode(
					array(
						'model'       => 'gpt-4o-mini',
						'messages'    => array(
							array(
								'role'    => 'user',
								'content' => $prompt,
							),
						),
						'temperature' => 0.2,
						'max_tokens'  => 600,
					)
				),
				'timeout' => 20,
			)
		);

		if ( is_wp_error( $response ) ) {
			return '';
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return isset( $body['choices'][0]['message']['content'] )
			? (string) $body['choices'][0]['message']['content']
			: '';
	}

	// -------------------------------------------------------------------------
	// Math utilities
	// -------------------------------------------------------------------------

	/**
	 * Computes cosine similarity between two numeric vectors.
	 *
	 * Returns 0.0 if either vector is empty or has zero magnitude.
	 * Uses the shorter vector's length when dimensions differ.
	 *
	 * @param float[] $a First vector.
	 * @param float[] $b Second vector.
	 * @return float Similarity in [0, 1] for unit-norm embeddings.
	 */
	private function cosine_similarity( array $a, array $b ): float {

		if ( empty( $a ) || empty( $b ) ) {
			return 0.0;
		}

		$dot    = 0.0;
		$norm_a = 0.0;
		$norm_b = 0.0;
		$count  = min( count( $a ), count( $b ) );

		for ( $i = 0; $i < $count; $i++ ) {
			$dot    += $a[ $i ] * $b[ $i ];
			$norm_a += $a[ $i ] * $a[ $i ];
			$norm_b += $b[ $i ] * $b[ $i ];
		}

		if ( 0.0 === $norm_a || 0.0 === $norm_b ) {
			return 0.0;
		}

		return $dot / ( sqrt( $norm_a ) * sqrt( $norm_b ) );
	}

	// -------------------------------------------------------------------------
	// Field utilities
	// -------------------------------------------------------------------------

	/**
	 * Parses a potentially formatted numeric string (e.g. "$19,995") to float.
	 *
	 * @param mixed $value Raw field value from the feed.
	 * @return float|null Null when the value is absent or non-numeric.
	 */
	private function parse_numeric_field( $value ): ?float {

		if ( null === $value || '' === (string) $value ) {
			return null;
		}

		$cleaned = preg_replace( '/[^0-9.]/', '', (string) $value );

		return is_numeric( $cleaned ) ? (float) $cleaned : null;
	}

	// -------------------------------------------------------------------------
	// Back-compat alias
	// -------------------------------------------------------------------------

	/**
	 * Public alias for external callers that previously referenced update_faq_embedding().
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @param bool     $update  Whether this is an update (unused).
	 * @return void
	 */
	public function update_faq_embedding( int $post_id, \WP_Post $post, bool $update ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		$this->on_save_faq( $post_id, $post );
	}
}

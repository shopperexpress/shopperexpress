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
	private const INTENT_UNKNOWN   = 'unknown';

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
			}
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
				'key'     => 'price',
				'value'   => array( (float) $params['min_price'], (float) $params['max_price'] ),
				'type'    => 'NUMERIC',
				'compare' => 'BETWEEN',
			);
		} elseif ( ! empty( $params['min_price'] ) ) {
			$meta_query[] = array(
				'key'     => 'price',
				'value'   => (float) $params['min_price'],
				'type'    => 'NUMERIC',
				'compare' => '>=',
			);
		} elseif ( ! empty( $params['max_price'] ) ) {
			$meta_query[] = array(
				'key'     => 'price',
				'value'   => (float) $params['max_price'],
				'type'    => 'NUMERIC',
				'compare' => '<=',
			);
		}

		return array(
			'post_type'      => 'listings',
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
					'prompt_set' => ! empty( get_field( 'ai_promt', 'option' ) ),
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
				'price'          => (float) get_field( 'price', $post->ID ),
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
				'post_type'      => array( 'offers', 'lease-offers', 'finance-offers', 'conditional-offers' ),
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

		wp_send_json_success( $this->chat_handler( $question ) );
	}

	// -------------------------------------------------------------------------
	// Intent routing
	// -------------------------------------------------------------------------

	/**
	 * Routes the question to the appropriate handler after intent detection.
	 *
	 * @param string $question Sanitized user question.
	 * @return array{ message: string }
	 */
	public function chat_handler( string $question ): array {

		$intent = $this->detect_intent( $question );

		switch ( $intent ) {
			case self::INTENT_INVENTORY:
				return $this->inventory_handler( $question );

			case self::INTENT_SPECIALS:
				return $this->specials_handler( $question );

			case self::INTENT_WEBSITE:
			case self::INTENT_FAQ:
				return $this->knowledge_handler( $question );

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
		// API KEY
		// ─────────────────────────────────────────────
		if ( empty( $this->api_key ) ) {
			$this->api_key = (string) get_field( 'ai_api_key', 'option' );
		}

		if ( empty( $this->api_key ) ) {
			return self::INTENT_FAQ;
		}

		// ─────────────────────────────────────────────
		// STRICT CLASSIFICATION PROMPT
		// ─────────────────────────────────────────────
		$prompt = <<<TXT
			You are a strict classification system.

			You MUST return ONLY one word:

			INVENTORY
			SPECIALS
			WEBSITE
			FAQ

			No punctuation.
			No explanation.
			No extra text.

			Class rules:

			INVENTORY:
			- vehicles
			- car search
			- price
			- availability
			- stock
			- filters (make, model, year, trim)

			SPECIALS:
			- lease
			- finance
			- APR
			- monthly payment
			- offers
			- incentives
			- discounts

			WEBSITE:
			- service
			- parts
			- trade-in
			- contact
			- dealership info
			- hours
			- location

			FAQ:
			- everything else

			User question:
			"""$question"""
			TXT;

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
			return self::INTENT_FAQ;
		}

		// ─────────────────────────────────────────────
		// RESPONSE PARSING (SAFE)
		// ─────────────────────────────────────────────
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		$raw = $body['output'][0]['content'][0]['text'] ?? '';

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

			$result = self::INTENT_FAQ;

		} else {
			$result = $map[ $word ];
		}

		// ─────────────────────────────────────────────
		// CACHE RESULT (1 HOUR)
		// ─────────────────────────────────────────────
		set_transient( $cache_key, $result, HOUR_IN_SECONDS );

		return $result;
	}

	/**
	 * Returns true if the question contains a vehicle make found in the specials
	 * REST feed or a generic body-type keyword.
	 *
	 * Makes are pulled live from load_all_specials() (transient-cached) so the
	 * list always reflects what the dealer actually carries, without a separate
	 * HTTP round-trip.
	 *
	 * @param string $q Lowercase question string.
	 * @return bool
	 */
	private function question_references_vehicle( string $q ): bool {

		// Body-type terms that are always valid regardless of inventory.
		$body_types = array( 'suv', 'sedan', 'truck', 'coupe', 'convertible', 'hatchback', 'minivan', 'van', 'wagon', 'pickup' );

		foreach ( $body_types as $term ) {
			if ( preg_match( '/\b' . preg_quote( $term, '/' ) . '\b/', $q ) ) {
				return true;
			}
		}

		// Derive makes from the cached specials feed.
		$specials = $this->load_all_specials();
		$makes    = array();

		foreach ( $specials as $special ) {
			$make = strtolower( trim( $special['make'] ?? '' ) );
			if ( '' !== $make ) {
				$makes[ $make ] = true;
				// Allow "chevy" as alias for "chevrolet".
				if ( 'chevrolet' === $make ) {
					$makes['chevy'] = true;
				}
				// Allow "mercedes" as alias for "mercedes-benz".
				if ( str_starts_with( $make, 'mercedes' ) ) {
					$makes['mercedes'] = true;
				}
				// Allow "vw" as alias for "volkswagen".
				if ( 'volkswagen' === $make ) {
					$makes['vw'] = true;
				}
			}
		}

		foreach ( array_keys( $makes ) as $make ) {
			if ( preg_match( '/\b' . preg_quote( $make, '/' ) . '\b/', $q ) ) {
				return true;
			}
		}

		return false;
	}

	// -------------------------------------------------------------------------
	// Knowledge handler (FAQ + Website hybrid)
	// -------------------------------------------------------------------------

	/**
	 * Unified handler for FAQ and website-intent questions.
	 *
	 * The question is embedded once here. That same vector is reused for both
	 * FAQ scoring and website-page scoring to avoid a second API call.
	 *
	 * Priority:
	 *   1. Embed the question.
	 *   2. Score FAQs by cosine similarity.
	 *   3. If top FAQ score >= FAQ_CONFIDENCE_THRESHOLD → answer from FAQ only.
	 *   4. Otherwise enrich with the best matching website pages.
	 *   5. If no website pages match either → answer from FAQ context alone.
	 *
	 * @param string $question User question.
	 * @return array{ message: string }
	 */
	private function knowledge_handler( string $question ): array {

		$question_embedding = $this->get_embedding( $question );

		if ( empty( $question_embedding ) ) {
			return $this->contact_fallback_handler();
		}

		// ── FAQ scoring ───────────────────────────────────────────────────────
		$faqs          = $this->get_faq_embeddings();
		$scored_faqs   = $this->score_faqs_by_similarity( $question_embedding, $faqs );
		$top_faq_score = isset( $scored_faqs[0]['score'] ) ? (float) $scored_faqs[0]['score'] : 0.0;
		$top_faqs      = array_column( array_slice( $scored_faqs, 0, self::FAQ_LIMIT ), 'faq' );
		$faq_context   = $this->build_faq_context( $top_faqs );

		if ( $top_faq_score >= self::FAQ_CONFIDENCE_THRESHOLD ) {
			// High-confidence FAQ hit — no website lookup needed.
			$prompt = $this->build_faq_prompt( $question, $faq_context, '' );
		} else {
			// Low-confidence — pass the already-computed embedding to avoid a
			// second OpenAI call inside fetch_website_context().
			$website_context = $this->fetch_website_context( $question, $question_embedding );
			$prompt          = $this->build_faq_prompt( $question, $faq_context, $website_context );
		}

		return array( 'message' => $this->process_contact_tags( $this->ask_ai( $prompt ) ) );
	}

	// -------------------------------------------------------------------------
	// Inventory handler
	// -------------------------------------------------------------------------

	/**
	 * Handles inventory questions using a live feed.
	 *
	 * @param string $question User question.
	 * @return array{ message: string }
	 */
	private function inventory_handler( string $question ): array {

		$context = $this->fetch_inventory_context( $question );

		$prompt = $this->build_inventory_prompt(
			$question,
			! empty( $context ) ? (string) wp_json_encode( $context ) : '',
			''
		);

		$ai_message = $this->convert_markdown_links( $this->ask_ai( $prompt ) );

		return array(
			'message' => $this->process_contact_tags( $ai_message ),
		);
	}

	// -------------------------------------------------------------------------
	// Specials handler
	// -------------------------------------------------------------------------

	/**
	 * Handles specials / offers questions using the structured CPT feed.
	 *
	 * @param string $question User question.
	 * @return array{ message: string }
	 */
	private function specials_handler( string $question ): array {

		$data    = $this->fetch_specials_context( $question );
		$context = ! empty( $data['specials'] ) ? (string) wp_json_encode( $data ) : '';
		$prompt  = $this->build_inventory_prompt( $question, '', $context );

		return array( 'message' => $this->process_contact_tags( $this->convert_markdown_links( $this->ask_ai( $prompt ) ) ) );
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
	 * Accepts a pre-computed question embedding so knowledge_handler() does not
	 * need to make a second OpenAI API call.
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

		$prompt = "
			You are a data extraction engine.

			Extract vehicle inventory filters from the user text.

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
			\"min_price\": 0,
			\"max_price\": 0,
			\"year\": 0
			}

			RULES:
			- If value is missing → use \"\" or 0
			- Normalize makes (chevy → Chevrolet, vw → Volkswagen)
			- Normalize body types: suv, sedan, truck, coupe, hatchback
			- Prices must be numbers only (no $, no k)
			- Do NOT include any text before or after JSON
			- Do NOT wrap response in ``` or any formatting

			EXAMPLES:

			Input: \"Honda under 25000\"
			Output:
			{
			\"make\": \"Honda\",
			\"model\": \"\",
			\"body_type\": \"\",
			\"interior_color\": \"\",
			\"exterior_color\": \"\",
			\"condition\": \"\",
			\"min_price\": 0,
			\"max_price\": 25000,
			\"year\": 0
			}

			Now process this:

			\"$question\"
			";

		$response = wp_remote_post(
			'https://api.openai.com/v1/responses',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => json_encode(
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
	 * Extracts basic filters from the question (make/model/type) so the prompt
	 * receives a focused payload rather than the full catalogue.
	 *
	 * @param string $question User question.
	 * @return array{ count: int, specials: array, applied_filters: array }
	 */
	private function fetch_specials_context( string $question ): array {

		$filters = $this->parse_specials_filters( $question );

		$cache_key = 'ai_spec_' . md5( $question );
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$url = add_query_arg(
			array_merge(
				$filters,
				array(
					'active_only' => '1',
					'limit'       => 20,
				)
			),
			home_url( '/wp-json/ai/v1/specials' )
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
				'specials' => array(),
				'error'    => 'API error',
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) ) {
			$data = array(
				'count'    => 0,
				'specials' => array(),
			);
		}

		set_transient( $cache_key, $data, 10 * MINUTE_IN_SECONDS );

		return $data;
	}

	/**
	 * Extracts lightweight specials filters from a natural-language question.
	 *
	 * Only parses make, model, year, and offer type — enough to narrow the
	 * payload without a full AI round-trip for simple queries.
	 *
	 * @param string $question User question.
	 * @return array
	 */
	private function parse_specials_filters( string $question ): array {

		$q       = strtolower( $question );
		$filters = array();

		// Offer type keywords.
		if ( preg_match( '/\b(lease|leasing)\b/', $q ) ) {
			$filters['post_type'] = 'lease';
		} elseif ( preg_match( '/\b(finance|financing|apr|loan)\b/', $q ) ) {
			$filters['post_type'] = 'finance';
		}

		// Year.
		if ( preg_match( '/\b(20[0-9]{2})\b/', $q, $m ) ) {
			$filters['year'] = (int) $m[1];
		}

		// Makes (reuse the same list as inventory parser).
		$makes = array(
			'acura',
			'audi',
			'bmw',
			'buick',
			'cadillac',
			'chevrolet',
			'chevy',
			'chrysler',
			'dodge',
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
			'mazda',
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
			'vw',
		);

		foreach ( $makes as $make ) {
			if ( preg_match( '/\b' . preg_quote( $make, '/' ) . '\b/', $q ) ) {
				$filters['make'] = 'chevy' === $make ? 'Chevrolet' : ucwords( $make );
				break;
			}
		}

		// Monthly payment cap.
		if ( preg_match( '/\$?([\d,]+)\s*(?:\/|\s+per\s+)month/i', $question, $m ) ) {
			$filters['max_payment'] = (float) str_replace( ',', '', $m[1] );
		}

		return $filters;
	}

	// -------------------------------------------------------------------------
	// Prompt builders
	// -------------------------------------------------------------------------

	/**
	 * Assembles the FAQ + optional website-context prompt from the ACF template.
	 *
	 * Three placeholders in the `ai_promt` ACF option field are replaced:
	 *   [faq_context]     — top FAQ Q&A pairs
	 *   [website_context] — crawled page blocks (empty string when not used)
	 *   [question]        — the user's question
	 *
	 * @param string $question        User question.
	 * @param string $faq_context     Assembled FAQ context string.
	 * @param string $website_context Assembled website context string (may be empty).
	 * @return string Fully assembled prompt.
	 */
	private function build_faq_prompt( string $question, string $faq_context, string $website_context ): string {

		$template = (string) get_field( 'ai_promt', 'option' );

		if ( empty( $template ) ) {
			wp_send_json_error( array( 'message' => 'AI prompt not configured.' ), 503 );
			exit;
		}

		return str_replace(
			array( '[faq_context]', '[website_context]', '[question]' ),
			array( $faq_context, $website_context, $question ),
			$template
		);
	}

	/**
	 * Builds the inventory / specials prompt from the ACF template.
	 *
	 * Three placeholders in the `ai_inventory_promt` ACF option field are replaced:
	 *   [inventory_context] — JSON inventory payload (empty string when not used)
	 *   [specials_context]  — JSON specials payload (empty string when not used)
	 *   [question]          — the user's question
	 *
	 * @param string $question          User question.
	 * @param string $inventory_context JSON inventory payload or empty string.
	 * @param string $specials_context  JSON specials payload or empty string.
	 * @return string
	 */
	private function build_inventory_prompt( string $question, string $inventory_context, string $specials_context ): string {

		$template = (string) get_field( 'ai_inventory_promt', 'option' );

		if ( empty( $template ) ) {
			wp_send_json_error( array( 'message' => 'AI inventory prompt not configured.' ), 503 );
			exit;
		}

		return str_replace(
			array( '[inventory_context]', '[specials_context]', '[question]' ),
			array( $inventory_context, $specials_context, $question ),
			$template
		);
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
	 * Converts markdown links in AI output to HTML anchor tags.
	 *
	 * Matches [label](url) — skips the link entirely when URL is empty.
	 *
	 * @param string $message Raw AI response text.
	 * @return string
	 */
	private function convert_markdown_links( string $message ): string {
		return preg_replace_callback(
			'/<a\s+href="\/vehicle\?id=(\d+)">([^<]+)<\/a>/i',
			function ( array $matches ): string {

				$vehicle_id = (int) $matches[1];
				$label      = $matches[2];

				if ( ! $vehicle_id ) {
					return $label;
				}

				$url = get_permalink( $vehicle_id );

				if ( ! $url ) {
					return $label;
				}

				return '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
			},
			$message
		);
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

		$this->api_key = (string) get_field( 'ai_api_key', 'option' );

		if ( empty( $this->api_key ) ) {
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

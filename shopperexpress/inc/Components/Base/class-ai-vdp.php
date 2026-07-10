<?php
/**
 * AI VDP Description Population.
 *
 * Generates AI-enhanced Vehicle Detail Page descriptions after import.
 * Stores the result in ai_vdp_description post meta.
 * Never overwrites imported content.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class AI_VDP
 *
 * Toggle-controlled system that:
 *  1. Fires on pmxi_after_xml_import and save_post for listings / used-listings.
 *  2. Collects vehicle fields from ACF + raw post meta.
 *  3. Calls OpenAI (same API key as the AI chat component).
 *  4. Saves the result to ai_vdp_description — never touching imported content.
 */
class AI_VDP implements Theme_Component {

	// -------------------------------------------------------------------------
	// Constants
	// -------------------------------------------------------------------------

	/** Post meta key for the AI-generated description. */
	private const META_KEY = 'ai_vdp_description';

	/** WP option key for the enable/disable toggle. */
	private const OPTION_NAME = 'ai_vdp_description';

	/** Supported post types. */
	private const SUPPORTED_TYPES = array( 'listings', 'used-listings' );

	/**
	 * Required ACF / meta fields. Generation is skipped when any are empty.
	 */
	private const REQUIRED_FIELDS = array( 'year', 'make', 'model' );

	/** OpenAI chat model. */
	private const AI_MODEL = 'gpt-4o-mini';

	/** OpenAI chat completions endpoint. */
	private const API_ENDPOINT = 'https://api.openai.com/v1/chat/completions';

	/** HTTP timeout for the API call (seconds). */
	private const API_TIMEOUT = 30;

	// -------------------------------------------------------------------------
	// Registration
	// -------------------------------------------------------------------------

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		// WP All Import: fires once when a full import finishes.
		add_action( 'pmxi_after_xml_import', array( $this, 'on_import_complete' ), 20 );

		// Per-post hook for single-post saves / incremental imports.
		foreach ( self::SUPPORTED_TYPES as $post_type ) {
			add_action( "save_post_{$post_type}", array( $this, 'on_save_post' ), 20, 3 );
		}
	}

	// -------------------------------------------------------------------------
	// Hook Callbacks
	// -------------------------------------------------------------------------

	/**
	 * Fired when a WP All Import XML import completes.
	 * Batch-processes all published listings that have no AI description yet.
	 *
	 * @param int $import_id WP All Import import ID.
	 * @return void
	 */
	public function on_import_complete( int $import_id ): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$this->log( sprintf( 'Import #%d complete — starting AI VDP batch generation.', $import_id ) );

		foreach ( self::SUPPORTED_TYPES as $post_type ) {
			$this->batch_process_post_type( $post_type );
		}
	}

	/**
	 * Fired on save_post_{post_type} for supported types.
	 * Skips revisions, auto-saves, drafts, and posts that already have a description.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @param bool     $update  Whether this is an existing post being updated.
	 * @return void
	 */
	public function on_save_post( int $post_id, \WP_Post $post, bool $update ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! $this->is_enabled() ) {
			return;
		}

		if ( 'publish' !== $post->post_status ) {
			return;
		}

		// Do not regenerate — meta already cached.
		if ( ! empty( get_field( self::META_KEY, $post_id ) ) ) {
			return;
		}

		// Prevent recursive save_post firing caused by update_post_meta below.
		remove_action( "save_post_{$post->post_type}", array( $this, 'on_save_post' ), 20 );
		$this->process_post( $post_id );
		add_action( "save_post_{$post->post_type}", array( $this, 'on_save_post' ), 20, 3 );
	}

	// -------------------------------------------------------------------------
	// Processing
	// -------------------------------------------------------------------------

	/**
	 * Batch-process all published posts of a type that have no AI description.
	 *
	 * @param string $post_type Post type slug.
	 * @return void
	 */
	private function batch_process_post_type( string $post_type ): void {
		$post_ids = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => self::META_KEY,
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		$this->log( sprintf( 'Found %d %s posts without AI descriptions.', count( $post_ids ), $post_type ) );

		foreach ( $post_ids as $post_id ) {
			$this->process_post( (int) $post_id );
		}
	}

	/**
	 * Core processor: collect → validate → generate → store.
	 *
	 * @param int  $post_id Post ID to process.
	 * @param bool $force   When true, regenerate even if a description already exists.
	 * @return string|\WP_Error Generated HTML on success, WP_Error on failure.
	 */
	public function process_post( int $post_id, bool $force = false ): string|\WP_Error {
		// Return cached value when not forcing a regeneration.
		if ( ! $force ) {
			$cached = get_field( self::META_KEY, $post_id );

			if ( $cached ) {
				return $cached;
			}
		}

		$post_type = get_post_type( $post_id );

		if ( ! in_array( $post_type, self::SUPPORTED_TYPES, true ) ) {
			return new \WP_Error(
				'unsupported_type',
				sprintf( "Post type '%s' is not supported for AI VDP generation.", $post_type )
			);
		}

		$vehicle_data = $this->get_vehicle_data( $post_id );

		// Validate required fields before making an API call.
		foreach ( self::REQUIRED_FIELDS as $field ) {
			if ( empty( $vehicle_data[ $field ] ) ) {
				$this->log( sprintf( 'Post #%d: skipped — required field "%s" is empty.', $post_id, $field ) );

				return new \WP_Error(
					'missing_required_field',
					sprintf( "Required field '%s' is empty for post #%d.", $field, $post_id )
				);
			}
		}

		$html = $this->generate_ai_vdp_description( $vehicle_data );

		if ( is_wp_error( $html ) ) {
			$this->log( sprintf( 'Post #%d: API error — %s', $post_id, $html->get_error_message() ) );
			return $html;
		}

		if ( empty( $html ) ) {
			return new \WP_Error( 'empty_response', 'AI returned an empty response.' );
		}

		update_field( 'ai_vdp_description', $html, $post_id );
		update_post_meta( $post_id, 'ai_vdp_description', $html );

		return $html;
	}

	/**
	 * Collect vehicle data from ACF fields with raw post meta fallback.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, string> Associative array of vehicle attributes (empty keys removed).
	 */
	private function get_vehicle_data( int $post_id ): array {
		// Map output key => ACF field / meta key.
		$field_map = array(
			'year'         => 'year',
			'make'         => 'make',
			'model'        => 'model',
			'trim'         => 'trim',
			'mileage'      => 'mileage',
			'color'        => 'color',
			'engine'       => 'engine',
			'transmission' => 'transmission',
			'fuel_type'    => 'fuel_type',
		);

		$data = array();

		foreach ( $field_map as $key => $meta_key ) {
			// ACF get_field first; fall back to raw post meta for non-ACF imports.
			$value = function_exists( 'get_field' ) ? get_field( $meta_key, $post_id ) : null;

			if ( empty( $value ) ) {
				$value = get_post_meta( $post_id, $meta_key, true );
			}

			$data[ $key ] = is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
		}

		// Dealer-level fields from ACF options (global, not per-post).
		if ( function_exists( 'get_field' ) ) {
			$data['dealer_name'] = sanitize_text_field( (string) get_field( 'dealer_name', 'options' ) );
			$data['location']    = sanitize_text_field( (string) get_field( 'dealer_city', 'options' ) );
		}

		// Strip empty strings so required-field checks work correctly.
		return array_filter( $data, fn( $v ) => '' !== $v );
	}

	// -------------------------------------------------------------------------
	// AI API
	// -------------------------------------------------------------------------

	/**
	 * Build the prompt, call OpenAI, sanitize and wrap the result.
	 *
	 * @param array<string, string> $vehicle_data Vehicle attributes.
	 * @return string|\WP_Error Sanitized HTML string or WP_Error on failure.
	 */
	public function generate_ai_vdp_description( array $vehicle_data ): string|\WP_Error {
		$api_key = (string) get_field( 'ai_api_key', 'option' );

		if ( empty( $api_key ) ) {
			return new \WP_Error(
				'no_api_key',
				'OpenAI API key is not configured. Set it in Theme Options → General.'
			);
		}

		$response = wp_remote_post(
			self::API_ENDPOINT,
			array(
				'timeout' => self::API_TIMEOUT,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => (string) wp_json_encode(
					array(
						'model'       => self::AI_MODEL,
						'messages'    => array(
							array(
								'role'    => 'system',
								'content' => $this->build_system_prompt( $vehicle_data ),
							),
							array(
								'role'    => 'user',
								'content' => $this->build_vdp_prompt( $vehicle_data ),
							),
						),
						'max_tokens'  => 600,
						'temperature' => 0.7,
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'request_failed', $response->get_error_message() );
		}

		$http_code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 !== $http_code ) {
			return new \WP_Error(
				'api_http_error',
				sprintf( 'OpenAI returned HTTP %d: %s', $http_code, wp_remote_retrieve_body( $response ) )
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$text = $body['choices'][0]['message']['content'] ?? '';

		if ( empty( trim( $text ) ) ) {
			return new \WP_Error( 'empty_response', 'OpenAI returned an empty message content.' );
		}

		return $this->format_ai_response( $text, $vehicle_data );
	}

	/**
	 * System prompt: uses admin-configured value or falls back to built-in default.
	 *
	 * @param array<string, string> $vehicle_data Vehicle attributes.
	 * @return string
	 */
	private function build_system_prompt( array $vehicle_data ): string {
		$custom = trim( (string) get_field( 'ai_vdp_system_prompt', 'options' ) );

		if ( $custom ) {
			$dealer = ! empty( $vehicle_data['dealer_name'] ) ? $vehicle_data['dealer_name'] : 'our dealership';
			$city   = ! empty( $vehicle_data['location'] ) ? $vehicle_data['location'] : '';
			return str_replace( array( '{dealer}', '{city}' ), array( $dealer, $city ), $custom );
		}

		return $this->default_system_prompt( $vehicle_data );
	}

	/**
	 * Built-in system prompt (used when no custom prompt is configured).
	 *
	 * @param array<string, string> $vehicle_data Vehicle attributes.
	 * @return string
	 */
	private function default_system_prompt( array $vehicle_data ): string {
		$dealer = ! empty( $vehicle_data['dealer_name'] ) ? $vehicle_data['dealer_name'] : 'our dealership';
		$city   = ! empty( $vehicle_data['location'] ) ? " in {$vehicle_data['location']}" : '';

		return implode(
			' ',
			array(
				"You are an expert automotive copywriter for {$dealer}{$city}.",
				'Write compelling, SEO-optimized vehicle descriptions for dealership Vehicle Detail Pages (VDPs).',
				'Use confident, benefit-focused language. Be specific about the listed features.',
				'Do NOT mention pricing.',
				'Do NOT invent features that are not listed.',
				'Output clean HTML using only <p>, <ul>, and <li> tags. No headings, no markdown.',
			)
		);
	}

	/**
	 * User prompt: structured vehicle data + admin-configured or built-in closing instruction.
	 *
	 * @param array<string, string> $vehicle_data Vehicle attributes.
	 * @return string
	 */
	private function build_vdp_prompt( array $vehicle_data ): string {
		$vehicle_label = trim(
			implode(
				' ',
				array_filter(
					array(
						$vehicle_data['year'] ?? '',
						$vehicle_data['make'] ?? '',
						$vehicle_data['model'] ?? '',
						$vehicle_data['trim'] ?? '',
					)
				)
			)
		);

		$lines = array( 'Write a 2–3 paragraph VDP description for this vehicle:', '' );

		$field_labels = array(
			'year'         => 'Year',
			'make'         => 'Make',
			'model'        => 'Model',
			'trim'         => 'Trim',
			'mileage'      => 'Mileage',
			'color'        => 'Exterior Color',
			'engine'       => 'Engine',
			'transmission' => 'Transmission',
			'fuel_type'    => 'Fuel Type',
		);

		foreach ( $field_labels as $key => $label ) {
			if ( ! empty( $vehicle_data[ $key ] ) ) {
				$lines[] = "- {$label}: {$vehicle_data[$key]}";
			}
		}

		$lines[] = '';

		$custom_closing = trim( (string) get_field( 'ai_vdp_user_prompt', 'options' ) );
		if ( $custom_closing ) {
			$lines[] = str_replace( '{vehicle}', $vehicle_label, $custom_closing );
		} else {
			$lines[] = "Highlight the key selling points for a buyer considering the {$vehicle_label}. "
					. 'Naturally include the full vehicle name for SEO. '
					. 'Output only the HTML — no preamble, no closing remarks.';
		}

		return implode( "\n", $lines );
	}

	/**
	 * Sanitize the AI response and wrap it in the required markup.
	 * Strips markdown fences the model may include despite instructions.
	 *
	 * @param string                $text         Raw text from OpenAI.
	 * @param array<string, string> $vehicle_data Vehicle attributes (used for data attribute).
	 * @return string Sanitized HTML.
	 */
	private function format_ai_response( string $text, array $vehicle_data ): string {
		// Strip markdown code fences (```html ... ```).
		$text = (string) preg_replace( '/^```(?:html)?\s*/i', '', trim( $text ) );
		$text = (string) preg_replace( '/\s*```$/', '', $text );
		$text = trim( $text );

		$allowed_html = array(
			'p'      => array(),
			'ul'     => array( 'class' => true ),
			'ol'     => array( 'class' => true ),
			'li'     => array(),
			'strong' => array(),
			'em'     => array(),
			'br'     => array(),
		);

		$vehicle_label = esc_attr(
			trim(
				implode(
					' ',
					array_filter(
						array(
							$vehicle_data['year'] ?? '',
							$vehicle_data['make'] ?? '',
							$vehicle_data['model'] ?? '',
							$vehicle_data['trim'] ?? '',
						)
					)
				)
			)
		);

		return sprintf(
			'<div class="ai-vdp-description" data-vehicle="%s">%s</div>',
			$vehicle_label,
			wp_kses( $text, $allowed_html )
		);
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Returns true when the feature toggle is enabled.
	 *
	 * @return bool
	 */
	private function is_enabled(): bool {
		return (bool) get_field( self::OPTION_NAME, 'options' );
	}

	/**
	 * Write a prefixed entry to the WP debug log (only when WP_DEBUG_LOG is on).
	 *
	 * @param string $message Message to log.
	 * @return void
	 */
	private function log( string $message ): void {
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[AI_VDP] ' . $message );
		}
	}
}

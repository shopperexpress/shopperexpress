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
 */
class AI implements Theme_Component {

	private const FAQ_LIMIT = 3;
	/**
	 * The API key used for OpenAI requests.
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {

		add_action( 'wp_ajax_ai', array( $this, 'handle_request' ) );
		add_action( 'wp_ajax_nopriv_ai', array( $this, 'handle_request' ) );

		add_action( 'wp_ajax_ai_get_response', array( $this, 'ai_get_response' ) );
		add_action( 'wp_ajax_nopriv_ai_get_response', array( $this, 'ai_get_response' ) );

		add_action( 'save_post_faq', array( $this, 'update_faq_embedding' ), 10, 3 );
	}

	public function ai_get_response() {
		sleep( 3 );

		wp_send_json_success( array( 'answer' => 'Message Lorem ipsum dolor, sit amet consectetur adipisicing elit. Consequuntur saepe neque magni' ) );
	}

	/**
	 * Handle FAQ request.
	 *
	 * @return void
	 */
	public function handle_request(): void {

		$response      = array();
		$message       = array();
		$nonce         = ! empty( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		$type          = ! empty( $_REQUEST['type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['type'] ) ) : '';
		$this->api_key = get_field( 'ai_api_key', 'option' );

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'shopperexpress_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Nonce verification failed' ) );
		}

		if ( empty( $_REQUEST['type'] ) ) {
			$message = array( 'message' => 'Empty type' );
		}

		if ( empty( $this->api_key ) ) {
			$message = array( 'message' => 'Empty API key' );
		}

		switch ( $type ) {
			case 'faq':
				$question = ! empty( $_REQUEST['question'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['question'] ) ) : '';
				if ( empty( $question ) ) {
					$message = array( 'message' => 'Empty question' );
				}
				$response = $this->faq_handler( $question );
				break;
		}

		if ( empty( $response ) ) {
			wp_send_json_error( $message );
		} else {
			wp_send_json_success( $response );
		}
	}

	/**
	 * Handles FAQ AI requests.
	 *
	 * @param string $question The user's submitted question.
	 * @return array AI-generated answer and relevant FAQ context.
	 */
	public function faq_handler( $question ) {

		$question = sanitize_text_field( $question );

		$question_embedding = $this->get_embedding( $question );

		if ( empty( $question_embedding ) ) {
			wp_send_json_error( array( 'message' => 'Embedding error' ) );
		}

		$faqs = $this->get_faq_embeddings();

		if ( empty( $faqs ) ) {
			wp_send_json_error( array( 'message' => 'No FAQ data' ) );
		}

		$faqs = $this->find_similar_faqs( $question_embedding, $faqs );

		$context = $this->build_context( $faqs );

		$prompt = $this->build_faq_prompt( $question, $context );

		return array( 'message' => $this->ask_ai( $prompt ) );
	}

	/**
	 * Updates the FAQ embedding for a given post.
	 *
	 * If the post is a revision, the function returns without updating.
	 * Otherwise, it generates a new embedding for the post's title and content,
	 * and updates the post meta with the new embedding.
	 *
	 * @param int    $post_id The ID of the post to update.
	 * @param object $post    The post object.
	 * @param array  $update  Array of updated post data.
	 * @return void
	 */
	public function update_faq_embedding( $post_id, $post, $update ): void {

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$fields = array(
			$post->post_title,
			wp_strip_all_tags( $post->post_content ),
			get_field( 'dealer_id', $post_id ),
			get_field( 'type', $post_id ),
			get_field( 'intent', $post_id ),
			get_field( 'page', $post_id ),
			get_field( 'context', $post_id ),
			get_field( 'cta_type', $post_id ),
			get_field( 'category', $post_id ),
			get_field( 'oem', $post_id ),
			get_field( 'model', $post_id ),
		);
		$text   = implode( ' ', array_filter( $fields ) );

		$embedding = $this->get_embedding( $text );

		if ( ! empty( $embedding ) ) {
			update_post_meta( $post_id, '_faq_embedding', wp_json_encode( $embedding ) );
			wp_cache_delete( 'faq_embeddings', 'ai' );
		}
	}

	/**
	 * Retrieves FAQ embeddings from the cache or generates new ones.
	 *
	 * @return array Array of FAQ embeddings.
	 */
	private function get_faq_embeddings(): array {

		$cache = wp_cache_get( 'faq_embedding', 'ai' );

		if ( false !== $cache ) {
			return $cache;
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'faq',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			)
		);

		$faqs = array();

		foreach ( $query->posts as $post ) {

			$embedding = get_post_meta( $post->ID, '_faq_embedding', true );

			if ( ! $embedding ) {

				$text = $post->post_title . ' ' . wp_kses_post( $post->post_content );

				$vector = $this->get_embedding( $text );

				if ( empty( $vector ) ) {
					continue;
				}

				update_post_meta( $post->ID, '_faq_embedding', wp_json_encode( $vector ) );

				$embedding = wp_json_encode( $vector );
			}

			$faqs[] = array(
				'question'  => $post->post_title,
				'answer'    => wp_kses_post( $post->post_content ),
				'embedding' => json_decode( $embedding, true ),
				'intent'    => get_field( 'intent', $post->ID ),
				'category'  => get_field( 'category', $post->ID ),
				'oem'       => get_field( 'oem', $post->ID ),
				'model'     => get_field( 'model', $post->ID ),
				'cta_type'  => get_field( 'cta_type', $post->ID ),
				'page'      => get_field( 'page', $post->ID ),
			);
		}

		wp_reset_postdata();

		wp_cache_set( 'faq_embedding', $faqs, 'ai', HOUR_IN_SECONDS );

		return $faqs;
	}

	/**
	 * Finds and returns the most similar FAQs to the given question embedding.
	 *
	 * Computes cosine similarity between the question embedding and each FAQ embedding,
	 * sorts the results by similarity score, and returns the top FAQs up to the limit defined by FAQ_LIMIT.
	 *
	 * @param array $question_embedding The embedding vector of the question.
	 * @param array $faqs List of FAQ arrays, each containing 'embedding'.
	 * @return array Top ranked FAQs most similar to the question embedding.
	 */
	private function find_similar_faqs( array $question_embedding, array $faqs ): array {

		$scores = array();

		foreach ( $faqs as $faq ) {

			$score = $this->cosine_similarity(
				$question_embedding,
				$faq['embedding']
			);

			$scores[] = array(
				'score' => $score,
				'faq'   => $faq,
			);
		}

		usort(
			$scores,
			function ( $a, $b ) {
				return $b['score'] <=> $a['score'];
			}
		);

		$top = array_slice( $scores, 0, self::FAQ_LIMIT );

		return array_column( $top, 'faq' );
	}

	/**
	 * Computes the cosine similarity between two vectors.
	 *
	 * @param array $a The first vector.
	 * @param array $b The second vector.
	 * @return float The cosine similarity score.
	 */
	private function cosine_similarity( array $a, array $b ): float {

		$dot    = 0;
		$norm_a = 0;
		$norm_b = 0;

		$count = min( count( $a ), count( $b ) );

		for ( $i = 0; $i < $count; $i++ ) {

			$dot    += $a[ $i ] * $b[ $i ];
			$norm_a += $a[ $i ] * $a[ $i ];
			$norm_b += $b[ $i ] * $b[ $i ];
		}

		if ( 0 === $norm_a || 0 === $norm_b ) {
			return 0;
		}

		return $dot / ( sqrt( $norm_a ) * sqrt( $norm_b ) );
	}

	/**
	 * Builds the context string for the AI prompt.
	 *
	 * @param array $faqs List of FAQ arrays, each containing 'question' and 'answer'.
	 * @return string The context string.
	 */
	private function build_context( array $faqs ): string {

		$context = '';

		foreach ( $faqs as $faq ) {
			$context .= "Question: {$faq['question']}\n";
			$context .= "Answer: {$faq['answer']}\n";

			if ( ! empty( $faq['intent'] ) ) {
				$context .= "Intent: {$faq['intent']}\n";
			}

			if ( ! empty( $faq['category'] ) ) {
				$context .= "Category: {$faq['category']}\n";
			}

			if ( ! empty( $faq['oem'] ) ) {
				$context .= "OEM: {$faq['oem']}\n";
			}

			if ( ! empty( $faq['model'] ) ) {
				$context .= "Model: {$faq['model']}\n";
			}

			if ( ! empty( $faq['cta_type'] ) ) {
				$context .= "CTA: {$faq['cta_type']}\n";
			}

			if ( ! empty( $faq['page'] ) ) {
				$context .= "Page: {$faq['page']}\n";
			}

			$context .= "\n---\n\n";
		}

		return $context;
	}

	/**
	 * Builds the prompt for the AI request.
	 *
	 * @param string $question The user's question.
	 * @param string $faq_context The context string containing FAQ question-answer pairs.
	 * @return string The prompt string.
	 */
	private function build_faq_prompt( string $question, string $faq_context ): string {

		$promt = get_field( 'ai_promt', 'option' );
		$promt = str_replace(
			array( '[faq_context]', '[question]' ),
			array( $faq_context, $question ),
			$promt
		);

		if ( empty( $promt ) ) {
			wp_send_json_error( array( 'message' => 'AI promt empty' ) );
			exit;
		}

		return $promt;
	}

	/**
	 * Get the embedding vector for a given text using the OpenAI API.
	 *
	 * @param string $text The text to generate the embedding for.
	 * @return array The embedding vector as an array, or an empty array on failure.
	 */
	private function get_embedding( string $text ): array {

		$response = wp_remote_post(
			'https://api.openai.com/v1/embeddings',
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $this->api_key,
				),
				'body'    => wp_json_encode(
					array(
						'model' => 'text-embedding-3-small',
						'input' => $text,
					)
				),
				'timeout' => 20,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array( 'message' => 'AI embeddings request error' );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return $body['data'][0]['embedding'] ?? array();
	}

	/**
	 * Sends a request to the AI service with the given prompt and API key.
	 *
	 * @param string $prompt The prompt string to send to the AI service.
	 * @return string The AI service's response, or an error message.
	 */
	private function ask_ai( string $prompt = '' ): string {

		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $this->api_key,
				),
				'body'    => wp_json_encode(
					array(
						'model'       => 'gpt-4o-mini',
						'messages'    => array(
							array(
								'role'    => 'user',
								'content' => $prompt,
							),
						),
						'temperature' => 0.2,
					)
				),
				'timeout' => 20,
			)
		);

		if ( is_wp_error( $response ) ) {
			return 'AI request error';
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return $body['choices'][0]['message']['content'] ?? 'AI empty response';
	}
}

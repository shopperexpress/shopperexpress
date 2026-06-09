<?php
/**
 * Intice Nexus VDP — redirect-based single vehicle pages.
 *
 * Registers VIN-based rewrite rules so VDP pages work without WP posts:
 *   /listings/{VIN}       → VDP for new vehicles
 *   /used-listings/{VIN}  → VDP for used vehicles
 *
 * During the transition period (while WP posts still exist):
 *   • SRP cards in API mode already link to /listings/{VIN} via Intice_Rest.
 *   • If someone hits the old WP post URL in API mode, they get 301-redirected
 *     to the VIN-based URL.
 *
 * When WP posts are deleted in the future, only the rewrite rules remain —
 * no other code needs to change.
 *
 * @package Shopperexpress
 */

namespace App\Components\Api;

use App\Components\Theme_Component;

/**
 * Class Intice_VDP
 */
class Intice_VDP implements Theme_Component {

	const QUERY_VAR_VIN       = 'intice_vin';
	const QUERY_VAR_POST_TYPE = 'intice_post_type';
	const QUERY_VAR_SLUG      = 'intice_slug';
	const VIN_PATTERN         = '[A-HJ-NPR-Z0-9]{17}';

	/**
	 * Bump this version string whenever the rewrite rule pattern changes
	 * to force a one-time flush on the next request.
	 */
	const RULES_VERSION = 'v2-slug';

	/**
	 * @return void
	 */
	public function register(): void {
		add_filter( 'rewrite_rules_array', array( $this, 'prepend_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );

		// Priority 1 — fires before any other template_redirect hooks and
		// before WP's own template selection in template-loader.php.
		add_action( 'template_redirect', array( $this, 'maybe_serve_vdp' ), 1 );

		// Transition: redirect old WP post singles to VIN-based URL in API mode.
		add_action( 'template_redirect', array( $this, 'redirect_wp_single_to_vin_url' ), 2 );

		// Flush rewrite rules once when RULES_VERSION changes.
		// wp_loaded fires after all CPTs/taxonomies are registered so the
		// full rule set is built before flush_rewrite_rules() regenerates it.
		add_action( 'wp_loaded', array( $this, 'maybe_flush_rewrite_rules' ) );
	}

	/**
	 * Flush rewrite rules once when the rule pattern version changes.
	 * Bump RULES_VERSION constant above whenever rewrite patterns are updated.
	 *
	 * @return void
	 */
	public function maybe_flush_rewrite_rules(): void {
		if ( get_option( 'intice_vdp_rules_version' ) !== self::RULES_VERSION ) {
			flush_rewrite_rules();
			update_option( 'intice_vdp_rules_version', self::RULES_VERSION );
		}
	}

	/**
	 * Prepend VIN rewrite rules to the very beginning of the rules array
	 * so they are tested before any CPT or page rules.
	 *
	 * Using rewrite_rules_array filter guarantees position 0 regardless of
	 * what other plugins/CPTs register.
	 *
	 * @param array $rules
	 * @return array
	 */
	public function prepend_rewrite_rules( array $rules ): array {
		$new = array();

		foreach ( array( 'listings', 'used-listings' ) as $post_type ) {
			// Match SEO slugs: /listings/new-2026-honda-civic-sedan-lx-{vin}-{stock}/
			// Also matches legacy /listings/{VIN} URLs during transition.
			$new[ $post_type . '/([^/]+)/?$' ] =
				'index.php?' . self::QUERY_VAR_POST_TYPE . '=' . $post_type . '&' . self::QUERY_VAR_SLUG . '=$matches[1]';
		}

		return $new + $rules;
	}

	/**
	 * Register custom query vars so WP doesn't strip them.
	 *
	 * @param string[] $vars
	 * @return string[]
	 */
	public function add_query_vars( array $vars ): array {
		$vars[] = self::QUERY_VAR_VIN;
		$vars[] = self::QUERY_VAR_POST_TYPE;
		$vars[] = self::QUERY_VAR_SLUG;
		return $vars;
	}

	/**
	 * If the current request matches an Intice VIN URL, include the VDP template
	 * and exit — bypassing WP's own template selection entirely.
	 *
	 * Fires at template_redirect priority 1, before WP's template-loader.php
	 * runs its own template selection logic. This avoids is_404 conflicts caused
	 * by WP_Query finding no posts for our custom query vars.
	 *
	 * @return void
	 */
	public function maybe_serve_vdp(): void {
		global $wp;

		$slug      = $wp->query_vars[ self::QUERY_VAR_SLUG ]      ?? '';
		$post_type = $wp->query_vars[ self::QUERY_VAR_POST_TYPE ] ?? '';

		if ( ! $slug || ! $post_type ) {
			return;
		}

		// Extract 17-char VIN from slug (case-insensitive).
		// Handles both /listings/{VIN} and /listings/new-2026-make-model-trim-{vin}-stock.
		if ( ! preg_match( '/(?:^|-)([a-hj-npr-z0-9]{17})(?:-|$)/i', $slug, $m ) ) {
			return;
		}
		$vin = strtoupper( $m[1] );
		// Make VIN available via get_query_var() in the template.
		$wp->query_vars[ self::QUERY_VAR_VIN ] = $vin;

		$vdp_template = get_template_directory() . '/template-parts/single/single-listings-api.php';

		if ( ! file_exists( $vdp_template ) ) {
			return;
		}

		// Reset 404 and fake singular state so ConversionBlock, wp_head CSS
		// injection, and any is_single()/is_singular() calls work correctly.
		global $wp_query;
		$wp_query->is_404      = false;
		$wp_query->is_single   = true;
		$wp_query->is_singular = true;

		// is_singular( 'listings' ) also checks queried_object->post_type.
		if ( ! $wp_query->queried_object ) {
			$fake                       = new \stdClass();
			$fake->post_type            = $post_type;
			$wp_query->queried_object   = $fake;
		}

		status_header( 200 );

		include $vdp_template;
		exit;
	}

	/**
	 * During transition: 301-redirect old WP post singles to /post-type/{VIN}
	 * when API mode is enabled.
	 *
	 * Only runs for listings and used-listings post types.
	 *
	 * @return void
	 */
	public function redirect_wp_single_to_vin_url(): void {
		if ( ! \App\is_api_mode() ) {
			return;
		}

		if ( ! is_singular( array( 'listings', 'used-listings' ) ) ) {
			return;
		}

		$post_id   = get_the_id();
		$post_type = get_post_type( $post_id );
		$vin       = get_post_meta( $post_id, 'vin_number', true );

		if ( ! $vin || ! preg_match( '/^' . self::VIN_PATTERN . '$/i', $vin ) ) {
			return;
		}

		// Build SEO slug from WP post ACF fields.
		$vehicle = array(
			'year'       => get_field( 'year', $post_id ),
			'make'       => get_field( 'make', $post_id ),
			'model'      => get_field( 'model', $post_id ),
			'body_style' => get_field( 'body_style', $post_id ),
			'trim'       => get_field( 'trim', $post_id ),
			'stock'      => get_field( 'stock_number', $post_id ),
		);

		$vin_url = self::vdp_url( strtoupper( $vin ), $post_type, $vehicle );

		wp_redirect( $vin_url, 301 );
		exit;
	}

	// ─── Static helper ────────────────────────────────────────────────────────

	/**
	 * Build a VDP URL for a given VIN in API mode.
	 *
	 * With vehicle data: /listings/new-2026-honda-civic-sedan-lx-2hgfe2f26th599522-h599522/
	 * Without vehicle data (fallback): /listings/2HGFE2F26TH599522/
	 *
	 * @param string $vin
	 * @param string $post_type  'listings' or 'used-listings'
	 * @param array  $vehicle    Optional Intice vehicle array for SEO slug generation.
	 * @return string
	 */
	public static function vdp_url( string $vin, string $post_type, array $vehicle = array() ): string {
		if ( ! empty( $vehicle ) ) {
			return home_url( trailingslashit( $post_type ) . self::build_vdp_slug( $vin, $post_type, $vehicle ) . '/' );
		}
		return home_url( trailingslashit( $post_type ) . strtoupper( $vin ) . '/' );
	}

	/**
	 * Build SEO-friendly VDP slug: new-2026-honda-civic-sedan-lx-{vin}-{stock}
	 *
	 * @param string $vin
	 * @param string $post_type
	 * @param array  $vehicle
	 * @return string
	 */
	private static function build_vdp_slug( string $vin, string $post_type, array $vehicle ): string {
		$condition  = 'used-listings' === $post_type ? 'used' : 'new';
		$year       = $vehicle['year']       ?? '';
		$make       = $vehicle['make']       ?? '';
		$model      = $vehicle['model']      ?? '';
		$body_style = $vehicle['body_style'] ?? '';
		$trim       = $vehicle['trim']       ?? '';
		$stock      = $vehicle['stock']      ?? '';

		$parts = array_filter( array( $condition, $year, $make, $model, $body_style, $trim, strtolower( $vin ), $stock ) );

		return implode( '-', array_map( 'sanitize_title', $parts ) );
	}
}

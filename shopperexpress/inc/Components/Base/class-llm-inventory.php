<?php
/**
 * AI-readable inventory page (/llm/inventory/).
 *
 * Server-rendered, JS-free HTML listing of all eligible new/used/certified
 * inventory, for discovery by AI assistants and crawlers (incl. OAI-SearchBot).
 * Reuses the same data + eligibility rules as the SRP (Intice_Rest::apply_vehicle_filters()
 * in API mode, `post_status = publish` in CPT mode — see \App\get_llm_inventory_page())
 * so it never disagrees with what's actually on the site.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class Llm_Inventory
 */
class Llm_Inventory implements Theme_Component {

	const OPTION_ENABLED = 'shopperexpress_llm_inventory_enabled';

	const QUERY_VAR_INDEX    = 'llm_inventory';
	const QUERY_VAR_PAGE     = 'llm_inventory_page';
	const QUERY_VAR_LLMS_TXT = 'llm_inventory_llms_txt';
	const QUERY_VAR_SITEMAP  = 'llm_inventory_sitemap';

	const PER_PAGE = 50;

	/**
	 * Bump whenever the rewrite rule patterns below change, to force a one-time flush.
	 */
	const RULES_VERSION = 'v1';

	/**
	 * @return void
	 */
	public function register(): void {
		add_filter( 'rewrite_rules_array', array( $this, 'prepend_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );

		// Priority 1 — before WP's own template selection, same pattern as Intice_VDP.
		add_action( 'template_redirect', array( $this, 'maybe_serve' ), 1 );

		add_action( 'wp_loaded', array( $this, 'maybe_flush_rewrite_rules' ) );

		// Append our own child sitemap to Yoast's sitemap index (no-op if Yoast isn't active).
		add_filter( 'wpseo_sitemap_index', array( $this, 'add_to_sitemap_index' ) );
	}

	/**
	 * @return void
	 */
	public function maybe_flush_rewrite_rules(): void {
		if ( get_option( 'llm_inventory_rules_version' ) !== self::RULES_VERSION ) {
			flush_rewrite_rules();
			update_option( 'llm_inventory_rules_version', self::RULES_VERSION );
		}
	}

	/**
	 * @param array $rules
	 * @return array
	 */
	public function prepend_rewrite_rules( array $rules ): array {
		$new = array(
			'llm/inventory/page/([0-9]+)/?$' => 'index.php?' . self::QUERY_VAR_INDEX . '=1&' . self::QUERY_VAR_PAGE . '=$matches[1]',
			'llm/inventory/?$'               => 'index.php?' . self::QUERY_VAR_INDEX . '=1',
			'llm-inventory-sitemap\.xml$'     => 'index.php?' . self::QUERY_VAR_SITEMAP . '=1',
			'llms\.txt$'                      => 'index.php?' . self::QUERY_VAR_LLMS_TXT . '=1',
		);

		return $new + $rules;
	}

	/**
	 * @param string[] $vars
	 * @return string[]
	 */
	public function add_query_vars( array $vars ): array {
		$vars[] = self::QUERY_VAR_INDEX;
		$vars[] = self::QUERY_VAR_PAGE;
		$vars[] = self::QUERY_VAR_LLMS_TXT;
		$vars[] = self::QUERY_VAR_SITEMAP;

		return $vars;
	}

	/**
	 * Dispatch the three routes this component owns. Fires at template_redirect
	 * priority 1, so it bypasses WP_Query/is_404 entirely — same approach as
	 * Intice_VDP::maybe_serve_vdp().
	 *
	 * @return void
	 */
	public function maybe_serve(): void {
		global $wp;

		if ( ! empty( $wp->query_vars[ self::QUERY_VAR_LLMS_TXT ] ) ) {
			$this->serve_llms_txt();
			return;
		}

		if ( ! empty( $wp->query_vars[ self::QUERY_VAR_SITEMAP ] ) ) {
			$this->serve_sitemap();
			return;
		}

		if ( empty( $wp->query_vars[ self::QUERY_VAR_INDEX ] ) ) {
			return;
		}

		// Let other template_redirect AJAX/form handlers process POST requests first.
		if ( ! empty( $_POST['action'] ) ) {
			return;
		}

		if ( ! self::is_enabled() ) {
			return;
		}

		$page = max( 1, (int) ( $wp->query_vars[ self::QUERY_VAR_PAGE ] ?? 1 ) );
		$data = \App\get_llm_inventory_page( $page, self::PER_PAGE );

		status_header( 200 );
		header( 'Content-Type: text/html; charset=utf-8' );

		include get_template_directory() . '/template-parts/llm/inventory.php';
		exit;
	}

	/**
	 * @return void
	 */
	private function serve_llms_txt(): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		status_header( 200 );
		header( 'Content-Type: text/plain; charset=utf-8' );

		$name = wp_strip_all_tags( get_bloginfo( 'name' ) );

		// Plain-text (text/plain) output — esc_html() is used here only to satisfy
		// WPCS's output-escaping sniff; $name never contains markup at this point.
		echo '# ' . esc_html( $name ) . "\n\n";
		echo "> Machine-readable vehicle inventory for AI assistants and search crawlers.\n\n";
		echo '- [Vehicle Inventory](' . esc_url( home_url( '/llm/inventory/' ) ) . "): Current new, used, and certified inventory, server-rendered, paginated.\n";

		exit;
	}

	/**
	 * Child sitemap (urlset) listing every /llm/inventory/ page — referenced from
	 * Yoast's sitemap index via add_to_sitemap_index() below, and reachable
	 * directly regardless of whether Yoast is active.
	 *
	 * @return void
	 */
	private function serve_sitemap(): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		$data      = \App\get_llm_inventory_page( 1, self::PER_PAGE );
		$lastmod   = gmdate( 'c' );
		$urls      = array( home_url( '/llm/inventory/' ) );

		for ( $i = 2; $i <= $data['total_pages']; $i++ ) {
			$urls[] = home_url( '/llm/inventory/page/' . $i . '/' );
		}

		status_header( 200 );
		header( 'Content-Type: application/xml; charset=utf-8' );

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		foreach ( $urls as $url ) {
			echo "\t<url><loc>" . esc_url( $url ) . '</loc><lastmod>' . esc_html( $lastmod ) . "</lastmod></url>\n";
		}

		echo '</urlset>';

		exit;
	}

	/**
	 * Append a <sitemap> entry pointing at our child sitemap to Yoast's
	 * sitemap_index.xml. No-op when Yoast isn't installed (filter never fires).
	 *
	 * @param string $content Existing sitemap index entries.
	 * @return string
	 */
	public function add_to_sitemap_index( string $content ): string {
		if ( ! self::is_enabled() ) {
			return $content;
		}

		$content .= '<sitemap><loc>' . esc_url( home_url( '/llm-inventory-sitemap.xml' ) ) . '</loc><lastmod>' . esc_html( gmdate( 'c' ) ) . "</lastmod></sitemap>\n";

		return $content;
	}

	// ─── Toggle ───────────────────────────────────────────────────────────────

	/**
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return (bool) get_option( self::OPTION_ENABLED, false );
	}

	/**
	 * @param bool $enabled
	 * @return bool New state.
	 */
	public static function set_enabled( bool $enabled ): bool {
		update_option( self::OPTION_ENABLED, $enabled ? '1' : '0', false );

		return $enabled;
	}
}

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
	 * Inventory data older than this is flagged as possibly out of date. Normal
	 * operation keeps it far fresher: the vehicles cache is refetched after every
	 * Nexus import and whenever its 10-minute live / 2-hour stale copies lapse,
	 * so being past this means fetches from Nexus have been failing.
	 */
	const STALE_AFTER = 3 * HOUR_IN_SECONDS;

	/**
	 * Yoast SEO's option holding the md5 of the llms.txt it last wrote — Yoast
	 * only keeps regenerating the file while its content still matches.
	 */
	const YOAST_LLMS_HASH_OPTION = 'wpseo_llms_txt_content_hash';

	/**
	 * Heading of the section we add to Yoast's llms.txt (also used to find and replace it).
	 */
	const LLMS_SECTION_HEADING = '## Vehicle Inventory';

	/**
	 * Re-entrancy guard for sync_yoast_llms_txt() (it updates the hash option it listens to).
	 *
	 * @var bool
	 */
	private static bool $syncing_llms = false;

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

		// Yoast's own `*-sitemap.xml` rewrite catches llm-inventory-sitemap.xml before
		// ours does, so with Yoast active the sitemap has to be served through it.
		add_action( 'init', array( $this, 'register_yoast_sitemap' ) );

		// Yoast writes llms.txt as a physical file (so our /llms.txt route never runs)
		// and has no filter for its content — add our section right after it writes.
		add_action( 'add_option_' . self::YOAST_LLMS_HASH_OPTION, array( __CLASS__, 'sync_yoast_llms_txt' ) );
		add_action( 'update_option_' . self::YOAST_LLMS_HASH_OPTION, array( __CLASS__, 'sync_yoast_llms_txt' ) );
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

		// A page number past the end (e.g. after inventory shrank) is a real 404,
		// not a silent copy of the last page.
		$data['not_found'] = $page > $data['total_pages'];

		status_header( $data['not_found'] ? 404 : 200 );
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

		status_header( 200 );
		header( 'Content-Type: application/xml; charset=utf-8' );

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo $this->get_sitemap_urlset(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped while built.

		exit;
	}

	/**
	 * <urlset> listing every /llm/inventory/ page, lastmod = when the inventory
	 * data was last refreshed.
	 *
	 * @return string
	 */
	private function get_sitemap_urlset(): string {
		$data    = \App\get_llm_inventory_page( 1, self::PER_PAGE );
		$lastmod = gmdate( 'c', ! empty( $data['last_updated_ts'] ) ? (int) $data['last_updated_ts'] : time() );
		$xml     = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		for ( $i = 1; $i <= $data['total_pages']; $i++ ) {
			$url  = 1 === $i ? home_url( '/llm/inventory/' ) : home_url( '/llm/inventory/page/' . $i . '/' );
			$xml .= "\t<url><loc>" . esc_url( $url ) . '</loc><lastmod>' . esc_html( $lastmod ) . "</lastmod></url>\n";
		}

		return $xml . '</urlset>';
	}

	/**
	 * Register llm-inventory-sitemap.xml with Yoast's sitemap router.
	 *
	 * @return void
	 */
	public function register_yoast_sitemap(): void {
		if ( isset( $GLOBALS['wpseo_sitemaps'] ) && method_exists( $GLOBALS['wpseo_sitemaps'], 'register_sitemap' ) ) {
			$GLOBALS['wpseo_sitemaps']->register_sitemap( 'llm-inventory', array( $this, 'build_yoast_sitemap' ) );
		}
	}

	/**
	 * Yoast `wpseo_do_sitemap_llm-inventory` callback.
	 *
	 * @return void
	 */
	public function build_yoast_sitemap(): void {
		$sitemaps = $GLOBALS['wpseo_sitemaps'];

		if ( ! self::is_enabled() ) {
			$sitemaps->set_bad_sitemap( true );
			return;
		}

		$sitemaps->set_sitemap( $this->get_sitemap_urlset() );
	}

	/**
	 * Add (or, when the page is disabled, remove) our section in the llms.txt
	 * Yoast SEO generates. Only touches a file Yoast still owns (stored hash
	 * matches), and re-stores the hash afterwards so Yoast keeps managing and
	 * regenerating it — each regeneration triggers this again via the hash
	 * option hooks. A hand-made llms.txt is left alone.
	 *
	 * @return void
	 */
	public static function sync_yoast_llms_txt(): void {
		if ( self::$syncing_llms ) {
			return;
		}

		$stored_hash = (string) get_option( self::YOAST_LLMS_HASH_OPTION, '' );

		if ( '' === $stored_hash ) {
			return;
		}

		global $wp_filesystem;

		require_once ABSPATH . 'wp-admin/includes/file.php';

		if ( ! WP_Filesystem() || ! $wp_filesystem ) {
			return;
		}

		// Same path resolution as Yoast's WordPress_File_System_Adapter.
		$dir = get_home_path();
		if ( ! wp_is_writable( $dir ) && ! empty( $_SERVER['DOCUMENT_ROOT'] ) ) {
			$dir = sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) );
		}
		$file = trailingslashit( apply_filters( 'wpseo_llmstxt_filesystem_path', $dir ) ) . 'llms.txt'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Yoast's own filter.

		if ( ! $wp_filesystem->exists( $file ) ) {
			return;
		}

		$content = (string) $wp_filesystem->get_contents( $file );

		if ( md5( $content ) !== $stored_hash ) {
			return;
		}

		$heading = preg_quote( self::LLMS_SECTION_HEADING, '/' );
		$updated = rtrim( (string) preg_replace( '/\R*' . $heading . '\R.*?(?=\R## |\z)/s', '', $content ) ) . "\n";

		if ( self::is_enabled() ) {
			$updated .= "\n" . self::LLMS_SECTION_HEADING . "\n"
				. '- [Vehicle inventory](' . esc_url_raw( home_url( '/llm/inventory/' ) ) . '): Current new, used and certified vehicle inventory — server-rendered HTML, paginated, no JavaScript required.' . "\n";
		}

		if ( $updated === $content || ! $wp_filesystem->put_contents( $file, $updated, FS_CHMOD_FILE ) ) {
			return;
		}

		self::$syncing_llms = true;
		update_option( self::YOAST_LLMS_HASH_OPTION, md5( $updated ) );
		self::$syncing_llms = false;
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
		self::sync_yoast_llms_txt();

		return $enabled;
	}
}

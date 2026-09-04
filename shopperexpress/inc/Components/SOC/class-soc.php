<?php
/**
 * SOC
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC;

use App\Components\Theme_Component;
use App\Components\SOC\Contracts\SOC_Module;
use App\Components\SOC\Modules\System_Status;
use App\Components\SOC\Modules\Api_Health;
use App\Components\SOC\Modules\Cron_Manager;
use App\Components\SOC\Modules\Cache_Manager;
use App\Components\SOC\Modules\Database_Health;
use App\Components\SOC\Modules\Developer_Tools;
use App\Components\SOC\Modules\Import_Monitor_Panel;
use App\Components\SOC\Modules\Api_Settings;
use App\Components\SOC\Modules\Lead_Delivery;
use App\Components\SOC\Modules\VDR_Requests;
use App\Components\SOC\Modules\Json_Ld_Settings;
use App\Components\SOC\Modules\Google_Reviews;
use App\Components\SOC\Modules\AI_Vdp_Log;

/**
 * SOC Component
 *
 * @package Shopperexpress
 */
class SOC implements Theme_Component {

	/**
	 * Array of SOC modules.
	 *
	 * @var array
	 */
	private array $modules = array();

	/**
	 * Groups modules under a single admin menu entry, rendered as tabs.
	 * The first slug in each group's `modules` list is that group's "primary"
	 * module: it's the one the submenu link points to, and its slug is kept
	 * stable so existing hardcoded deep links (e.g. `page=soc-lead-delivery`
	 * in email notifications) keep resolving to the right group + tab.
	 *
	 * @var array<string, array{label: string, icon: string, modules: string[]}>
	 */
	private const GROUPS = array(
		'core'         => array(
			'label'   => 'System & Cache',
			'icon'    => 'dashicons-admin-tools',
			'modules' => array( 'system-status', 'cron-manager', 'cache-manager', 'database-health' ),
		),
		'integrations' => array(
			'label'   => 'API & Integrations',
			'icon'    => 'dashicons-rest-api',
			'modules' => array( 'api-settings', 'developer-tools', 'api-health' ),
		),
		'leads'        => array(
			'label'   => 'Leads & Vehicles',
			'icon'    => 'dashicons-groups',
			'modules' => array( 'lead-delivery', 'vdr-requests', 'import-monitor', 'ai-vdp-log' ),
		),
		'content'      => array(
			'label'   => 'SEO & Content',
			'icon'    => 'dashicons-media-code',
			'modules' => array( 'google-reviews', 'json-ld-settings' ),
		),
	);

	/**
	 * Register the component.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->boot_modules();

		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
		add_action( 'admin_head', array( $this, 'inject_submenu_icons' ) );
		add_filter( 'parent_file', array( $this, 'fix_parent_file' ) );
		add_filter( 'submenu_file', array( $this, 'fix_submenu_file' ) );

		( new SOC_Ajax( $this->modules ) )->register();
	}

	/**
	 * Boot the SOC modules.
	 *
	 * @return void
	 */
	private function boot_modules(): void {
		$module_classes = array(
			new System_Status(),
			new Api_Health(),
			new Cron_Manager(),
			new Cache_Manager(),
			new Database_Health(),
			new Developer_Tools(),
			new Import_Monitor_Panel(),
			new Api_Settings(),
			new Lead_Delivery(),
			new VDR_Requests(),
			new Json_Ld_Settings(),
			new Google_Reviews(),
			new AI_Vdp_Log(),
		);

		foreach ( $module_classes as $module ) {
			$this->modules[ $module->get_slug() ] = $module;
		}
	}

	/**
	 * Register the SOC admin menu — one visible entry per group instead of per module.
	 *
	 * Every module is registered as a genuine child of the "Operation Center"
	 * top-level menu and left registered — `remove_submenu_page()` was tried
	 * here but is NOT an option: WP's own access check for a page
	 * (`user_can_access_admin_page()`) works by searching `$submenu[$parent]`
	 * for the current page slug, so removing a non-primary module from that
	 * array also makes WP treat direct links to it as unauthorized and
	 * redirect to the dashboard — breaking exactly the deep links (tabs,
	 * bookmarks, emailed URLs) this grouping is supposed to preserve.
	 *
	 * Non-primary modules are instead hidden purely visually — see
	 * inject_submenu_icons(), which hides their `<li>` via JS after the menu
	 * renders — while staying fully registered for WP's access checks.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		$top_level_slug = 'soc-' . self::GROUPS['core']['modules'][0];

		add_menu_page(
			'Operation Center',
			'Operation Center',
			'manage_options',
			$top_level_slug,
			array( $this, 'render_dashboard' ),
			'dashicons-screenoptions',
			3
		);

		foreach ( self::GROUPS as $group ) {
			foreach ( $group['modules'] as $i => $module_slug ) {
				$is_primary = 0 === $i;
				$label      = $is_primary ? $group['label'] : $this->modules[ $module_slug ]->get_label();

				add_submenu_page(
					$top_level_slug,
					$label,
					$label,
					'manage_options',
					'soc-' . $module_slug,
					array( $this, 'render_dashboard' )
				);
			}
		}
	}

	/**
	 * Render the SOC dashboard.
	 *
	 * @return void
	 */
	public function render_dashboard(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Access denied.' );
		}

		list( $active_group, $active_slug ) = $this->resolve_active_module();

		$module = $this->modules[ $active_slug ] ?? reset( $this->modules );
		$data   = $module->collect();

		$group_tabs = array_map(
			fn( $slug ) => $this->modules[ $slug ],
			array_filter( self::GROUPS[ $active_group ]['modules'], fn( $slug ) => isset( $this->modules[ $slug ] ) )
		);

		$modules = $this->modules;
		// $active_slug, $active_group, $group_tabs, $module, $data, $modules are all available in the view scope.
		require get_template_directory() . '/inc/Components/SOC/views/dashboard.php';
	}

	/**
	 * Conditionally enqueue the assets required for the SOC (Operation Center) admin pages.
	 *
	 * This function ensures that SOC-specific CSS and JS are only loaded when a relevant
	 * Operation Center admin page is active, optimizing performance and avoiding unnecessary
	 * asset loading on other admin screens.
	 *
	 * @param string $hook The current admin page hook suffix.
	 *
	 * @return void
	 */
	public function maybe_enqueue_assets( string $hook ): void {
		if ( false === strpos( $hook, 'soc' ) ) {
			return;
		}

		( new SOC_Assets() )->enqueue();
	}

	/**
	 * Inject dashicon spans into the (visible) group submenu items, and hide
	 * the non-primary module items from the sidebar — both purely visual, via
	 * a small inline script. The underlying pages stay fully registered (see
	 * register_menu()) so direct links to a non-primary module keep working;
	 * only their `<li>` is hidden here.
	 *
	 * @return void
	 */
	public function inject_submenu_icons(): void {
		$icon_map    = array();
		$hidden_slugs = array();

		foreach ( self::GROUPS as $group ) {
			foreach ( $group['modules'] as $i => $module_slug ) {
				if ( 0 === $i ) {
					$icon_map[ 'soc-' . $module_slug ] = $group['icon'];
				} else {
					$hidden_slugs[] = 'soc-' . $module_slug;
				}
			}
		}
		?>
		<script>
		(function () {
			var iconMap     = <?php echo wp_json_encode( $icon_map ); ?>;
			var hiddenSlugs = <?php echo wp_json_encode( $hidden_slugs ); ?>;

			document.addEventListener('DOMContentLoaded', function () {
				Object.keys(iconMap).forEach(function (page) {
					var links = document.querySelectorAll(
						'#adminmenu .wp-submenu a[href*="page=' + page + '"]'
					);
					links.forEach(function (link) {
						var icon = document.createElement('span');
						icon.className = 'dashicons ' + iconMap[page];
						icon.style.cssText = 'font-size:16px;width:16px;height:16px;margin-right:6px;vertical-align:middle;line-height:1;';
						link.insertBefore(icon, link.firstChild);
					});
				});

				hiddenSlugs.forEach(function (page) {
					var links = document.querySelectorAll(
						'#adminmenu .wp-submenu a[href*="page=' + page + '"]'
					);
					links.forEach(function (link) {
						var item = link.closest('li');
						if (item) {
							item.style.display = 'none';
						}
					});
				});
			});
		}());
		</script>
		<?php
	}

	/**
	 * Resolve the active group + module from the current `page` query var.
	 *
	 * Looks the requested module slug up across all groups rather than trusting
	 * it to be a group's "primary" slug, so any direct module link (bookmarked,
	 * emailed, or an old submenu URL) still lands on the right group with that
	 * module's tab selected.
	 *
	 * @return array{0: string, 1: string} [group_key, module_slug]
	 */
	private function resolve_active_module(): array {
		$module_slug = $this->current_module_slug();
		$group_key   = $module_slug ? $this->find_group_for_module( $module_slug ) : null;

		if ( null !== $group_key ) {
			return array( $group_key, $module_slug );
		}

		$first_group = array_key_first( self::GROUPS );

		return array( $first_group, self::GROUPS[ $first_group ]['modules'][0] );
	}

	/**
	 * Extract the module slug from the current `page` query var (without validating it exists).
	 *
	 * @return string
	 */
	private function current_module_slug(): string {
		$page = sanitize_key( $_GET['page'] ?? '' );

		return str_replace( 'soc-', '', $page );
	}

	/**
	 * Find which group a module slug belongs to.
	 *
	 * @param string $module_slug Module slug to search for.
	 * @return string|null Group key, or null when not found in any group.
	 */
	private function find_group_for_module( string $module_slug ): ?string {
		foreach ( self::GROUPS as $group_key => $group ) {
			if ( in_array( $module_slug, $group['modules'], true ) ) {
				return $group_key;
			}
		}

		return null;
	}

	/**
	 * Belt-and-suspenders: force the top-level "Operation Center" menu parent
	 * for any SOC module page. WP's native `get_admin_page_parent()` should
	 * already resolve this correctly on its own (every module stays registered
	 * in `$submenu`), but pinning it explicitly avoids relying on that lookup
	 * succeeding in every WP version/edge case.
	 *
	 * @param string|null $parent_file Default parent file WP resolved (WP core passes null on some screens).
	 * @return string|null
	 */
	public function fix_parent_file( $parent_file ) {
		$group_key = $this->find_group_for_module( $this->current_module_slug() );

		if ( null === $group_key ) {
			return $parent_file;
		}

		return 'soc-' . self::GROUPS['core']['modules'][0];
	}

	/**
	 * Highlight the group's visible (primary) submenu item when viewing any
	 * module page within that group. Needed because WP's default resolution
	 * would set `$submenu_file` to the *current* module's own slug — correct
	 * for primary modules, but for a hidden non-primary one (e.g.
	 * `soc-cron-manager`) that slug has no visible `<li>` to mark "current"
	 * (see inject_submenu_icons(), which hides it), so nothing in the sidebar
	 * would otherwise show as active.
	 *
	 * @param string|null $submenu_file Default submenu file WP resolved (WP core passes null on some screens).
	 * @return string|null
	 */
	public function fix_submenu_file( $submenu_file ) {
		$group_key = $this->find_group_for_module( $this->current_module_slug() );

		if ( null === $group_key ) {
			return $submenu_file;
		}

		return 'soc-' . self::GROUPS[ $group_key ]['modules'][0];
	}
}

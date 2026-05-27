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
		);

		foreach ( $module_classes as $module ) {
			$this->modules[ $module->get_slug() ] = $module;
		}
	}

	/**
	 * Register the SOC admin menu.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_menu_page(
			'Operation Center',
			'Operation Center',
			'manage_options',
			'soc-system-status',
			array( $this, 'render_dashboard' ),
			'dashicons-screenoptions',
			3
		);

		foreach ( $this->modules as $module ) {
			add_submenu_page(
				'soc-system-status',
				$module->get_label(),
				$module->get_label(),
				'manage_options',
				'soc-' . $module->get_slug(),
				array( $this, 'render_dashboard' )
			);
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

		$active_slug = $this->resolve_active_tab();
		$module      = $this->modules[ $active_slug ] ?? reset( $this->modules );
		$data        = $module->collect();
		$modules     = $this->modules;
		// $active_slug, $module, $data, $modules are all available in the view scope.
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
	 * Inject dashicon spans into Operation Center submenu items via a small inline script.
	 *
	 * @return void
	 */
	public function inject_submenu_icons(): void {
		$map = array();
		foreach ( $this->modules as $slug => $module ) {
			$map[ 'soc-' . $slug ] = $module->get_icon();
		}
		?>
		<script>
		(function () {
			var map = <?php echo wp_json_encode( $map ); ?>;
			document.addEventListener('DOMContentLoaded', function () {
				Object.keys(map).forEach(function (page) {
					var links = document.querySelectorAll(
						'#adminmenu .wp-submenu a[href*="page=' + page + '"]'
					);
					links.forEach(function (link) {
						var icon = document.createElement('span');
						icon.className = 'dashicons ' + map[page];
						icon.style.cssText = 'font-size:16px;width:16px;height:16px;margin-right:6px;vertical-align:middle;line-height:1;';
						link.insertBefore(icon, link.firstChild);
					});
				});
			});
		}());
		</script>
		<?php
	}

	/**
	 * Resolve the active SOC tab.
	 *
	 * @return string
	 */
	private function resolve_active_tab(): string {
		$page = sanitize_key( $_GET['page'] ?? 'soc-dashboard' );

		return str_replace( 'soc-', '', $page ) ?: 'system-status';
	}
}

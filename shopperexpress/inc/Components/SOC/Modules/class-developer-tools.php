<?php
/**
 * SOC Developer Tools Module
 *
 * Provides developer diagnostic utilities inside Operation Center.
 * Currently hosts the VIN Checker (previously Tools → VIN Checker).
 *
 * UI only — all business logic lives in the service class (Vin_Admin).
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Modules;

use App\Components\Base\Vin_Admin;
use App\Components\SOC\Contracts\SOC_Module;

/**
 * Class Developer_Tools
 */
class Developer_Tools implements SOC_Module {

	/**
	 * VIN service instance — holds all business logic.
	 *
	 * @var Vin_Admin
	 */
	private Vin_Admin $vin;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->vin = new Vin_Admin();
	}

	/**
	 * Get the unique slug for this module.
	 *
	 * Used internally for reference, cache, and API endpoint naming.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'developer-tools';
	}

	/**
	 * Get the human-readable label for the module.
	 *
	 * Displayed in the admin UI sidebar or panel headers.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return 'VIN Checker';
	}

	/**
	 * Get the Dashicon class name for this module.
	 *
	 * Used to visually represent this tool in the admin interface.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-search';
	}

	/**
	 * Collect data needed to render the panel.
	 *
	 * @param bool $force_refresh Unused — history is always live.
	 * @return array
	 */
	public function collect( bool $force_refresh = false ): array {
		return array(
			'vin_history'         => $this->vin->get_history(),
			'shell_exec_disabled' => $this->vin->is_shell_exec_disabled(),
		);
	}

	/**
	 * Render the module view.
	 *
	 * @param array $data Collected data.
	 * @return void
	 */
	public function render( array $data ): void {
		require get_template_directory() . '/inc/Components/SOC/views/developer-tools.php';
	}

	// ─── VIN service delegates (called by SOC_Ajax) ───────────────────────────

	/**
	 * Perform a VIN lookup via Chromedata API.
	 *
	 * @param string $vin Validated 17-char VIN (uppercase).
	 * @return array|\WP_Error
	 */
	public function vin_lookup( string $vin ) {
		return $this->vin->lookup_vin( $vin );
	}

	/**
	 * Clear the current user's VIN history.
	 *
	 * @return void
	 */
	public function vin_clear_history(): void {
		$this->vin->clear_user_history();
	}

	/**
	 * Dispatch a background Chromedata API call for a VIN.
	 *
	 * @param string $vin Validated VIN.
	 * @return array|\WP_Error
	 */
	public function vin_run_background( string $vin ) {
		return $this->vin->run_background_for_vin( $vin );
	}

	/**
	 * Poll whether features_items has been populated for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public function vin_poll( int $post_id ): array {
		return $this->vin->poll_features( $post_id );
	}
}

<?php
/**
 * SOC General Settings Module
 *
 * Home for small site-wide switches that don't belong to a bigger feature
 * module (currently: the front-end Intice tool guard).
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Modules;

use App\Components\SOC\Contracts\SOC_Module;
use App\Components\SOC\Support\SOC_Cache;

/**
 * Class General_Settings
 */
class General_Settings implements SOC_Module {

	const OPTION_INTICE_GUARD = 'shopperexpress_intice_guard_enabled';

	/**
	 * @return string
	 */
	public function get_slug(): string {
		return 'general-settings';
	}

	/**
	 * @return string
	 */
	public function get_label(): string {
		return 'General Settings';
	}

	/**
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-admin-generic';
	}

	/**
	 * @param bool $force_refresh
	 * @return array
	 */
	public function collect( bool $force_refresh = false ): array {
		if ( $force_refresh ) {
			SOC_Cache::forget( $this->get_slug(), 'data' );
		}

		$cached = SOC_Cache::get( $this->get_slug(), 'data' );
		if ( false !== $cached ) {
			return $cached;
		}

		$data = array(
			'guard_enabled' => self::is_guard_enabled(),
		);

		SOC_Cache::set( $this->get_slug(), 'data', $data, 1 * MINUTE_IN_SECONDS );

		return $data;
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function render( array $data ): void {
		$view = get_template_directory() . '/inc/Components/SOC/views/general-settings.php';
		if ( file_exists( $view ) ) {
			include $view;
		}
	}

	// ─── Public helpers ───────────────────────────────────────────────────────

	/**
	 * Toggle the front-end "Intice tool unavailable" guard on/off — when
	 * disabled, intice-tool buttons (Leadmaker, Trademaker, Dealmaker,
	 * Loanmaker, …) fall back to their raw inline onclick handlers with no
	 * loading state or "temporarily unavailable" popup.
	 *
	 * @param bool $enabled
	 * @return bool New state.
	 */
	public function set_guard_enabled( bool $enabled ): bool {
		update_option( self::OPTION_INTICE_GUARD, $enabled ? '1' : '0', false );
		SOC_Cache::forget( $this->get_slug(), 'data' );
		return $enabled;
	}

	/**
	 * Whether the front-end intice tool-guard (loading/unavailable popup) is active.
	 *
	 * @return bool
	 */
	public static function is_guard_enabled(): bool {
		return (bool) get_option( self::OPTION_INTICE_GUARD, true );
	}
}

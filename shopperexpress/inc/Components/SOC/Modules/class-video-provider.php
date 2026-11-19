<?php
/**
 * SOC Video Provider Settings Module
 *
 * Controls the site-wide switch between the legacy Wistia video embeds and
 * the new VISTA platform. Stored media/video IDs are shared between both
 * providers — this setting only decides which embed markup/scripts get
 * generated for them.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Modules;

use App\Components\SOC\Contracts\SOC_Module;
use App\Components\SOC\Support\SOC_Cache;

/**
 * Class Video_Provider
 */
class Video_Provider implements SOC_Module {

	const OPTION_VIDEO_PROVIDER = 'shopperexpress_video_provider';

	const PROVIDER_WISTIA = 'wistia';
	const PROVIDER_VISTA  = 'vista';

	/**
	 * Production VISTA base URL — the single place the rest of the theme
	 * should read this from (template-parts, JS build config, etc.).
	 */
	const VISTA_BASE_URL = 'https://vista.intice.com';

	/**
	 * @return string
	 */
	public function get_slug(): string {
		return 'video-provider';
	}

	/**
	 * @return string
	 */
	public function get_label(): string {
		return 'Video Provider';
	}

	/**
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-video-alt3';
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
			'provider'       => $this->get_provider(),
			'vista_base_url' => self::VISTA_BASE_URL,
		);

		SOC_Cache::set( $this->get_slug(), 'data', $data, 1 * MINUTE_IN_SECONDS );

		return $data;
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function render( array $data ): void {
		$view = get_template_directory() . '/inc/Components/SOC/views/video-provider.php';
		if ( file_exists( $view ) ) {
			include $view;
		}
	}

	// ─── Public helpers ───────────────────────────────────────────────────────

	/**
	 * Current provider, always resolving to 'wistia' for a missing/invalid value.
	 *
	 * @return string 'wistia'|'vista'.
	 */
	public function get_provider(): string {
		$stored = get_option( self::OPTION_VIDEO_PROVIDER, self::PROVIDER_WISTIA );
		return self::PROVIDER_VISTA === $stored ? self::PROVIDER_VISTA : self::PROVIDER_WISTIA;
	}

	/**
	 * Switch the active video provider. Busts this module's own SOC cache and
	 * every downstream page/object/OPcache layer so already-rendered Wistia (or
	 * VISTA) embed markup isn't kept serving stale from a cache after the switch.
	 *
	 * @param string $provider 'wistia'|'vista'.
	 * @return string New provider.
	 */
	public function set_provider( string $provider ): string {
		$provider = self::PROVIDER_VISTA === $provider ? self::PROVIDER_VISTA : self::PROVIDER_WISTIA;

		update_option( self::OPTION_VIDEO_PROVIDER, $provider, false );
		SOC_Cache::forget( $this->get_slug(), 'data' );
		Api_Settings::flush_downstream_caches();

		return $provider;
	}
}

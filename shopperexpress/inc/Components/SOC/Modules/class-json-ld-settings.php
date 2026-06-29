<?php
/**
 * SOC JSON-LD Settings Module
 *
 * Provides a builder panel for configuring structured-data schema output
 * (schema.org/Vehicle and related types) without touching code.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Modules;

use App\Components\Base\JSON_LD;
use App\Components\SOC\Contracts\SOC_Module;

/**
 * Class Json_Ld_Settings
 *
 * SOC module that exposes the JSON-LD builder UI and persists field toggle
 * state in the WP option json_ld_field_config.
 */
class Json_Ld_Settings implements SOC_Module {

	/**
	 * @return string
	 */
	public function get_slug(): string {
		return 'json-ld-settings';
	}

	/**
	 * @return string
	 */
	public function get_label(): string {
		return 'JSON-LD Schema';
	}

	/**
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-editor-code';
	}

	/**
	 * Collect module data — returns the merged builder config.
	 *
	 * @param bool $force_refresh Unused; option reads are always fresh.
	 * @return array
	 */
	public function collect( bool $force_refresh = false ): array {
		return JSON_LD::get_config();
	}

	/**
	 * Render the builder panel view.
	 *
	 * @param array $data Collected config data.
	 * @return void
	 */
	public function render( array $data ): void {
		$view = get_template_directory() . '/inc/Components/SOC/views/json-ld-settings.php';
		if ( file_exists( $view ) ) {
			include $view;
		}
	}
}

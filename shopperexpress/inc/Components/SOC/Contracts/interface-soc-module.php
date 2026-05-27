<?php
/**
 * SOC Module Interface
 *
 * Defines the required methods for all SOC dashboard modules.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Contracts;

interface SOC_Module {
	/**
	 * Get the unique module slug for referencing in UI, cache keys, etc.
	 *
	 * @return string
	 */
	public function get_slug(): string;

	/**
	 * Human-friendly module label, shown in admin UI.
	 *
	 * @return string
	 */
	public function get_label(): string;

	/**
	 * Module icon for the admin dashboard (dashicon name).
	 *
	 * @return string
	 */
	public function get_icon(): string;

	/**
	 * Collect and return module data for dashboards, optionally forcing a cache refresh.
	 *
	 * @param bool $force_refresh Rebuild data and skip cache if true.
	 * @return array Module dataset.
	 */
	public function collect( bool $force_refresh = false ): array;

	/**
	 * Render module output, given the supplied dataset.
	 *
	 * @param array $data Data previously collected for display.
	 * @return void
	 */
	public function render( array $data ): void;
}

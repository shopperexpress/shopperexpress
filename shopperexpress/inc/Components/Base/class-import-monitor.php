<?php
/**
 * Import Monitor – Main Component.
 *
 * Bootstraps all sub-systems: ACF settings registration, WP All Import hooks,
 * cron scheduling, and the admin dashboard page.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class Import_Monitor
 *
 * Entry-point component wired into Theme::get_theme_components().
 * Delegates work to focused helper classes that live in the same namespace.
 *
 * @package App\Components\Base
 */
class Import_Monitor implements Theme_Component {

	// Option key used as the prefix for all monitor state stored in wp_options.
	const OPTION_PREFIX = 'wpim_';

	/**
	 * Register all hooks for every sub-system.
	 *
	 * @return void
	 */
	public function register(): void {
		( new Import_Monitor_ACF() )->register();
		( new Import_Monitor_Tracker() )->register();
		( new Import_Monitor_Cron() )->register();
		( new Import_Monitor_Dashboard() )->register();
	}
}

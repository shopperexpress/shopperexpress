<?php
/**
 * App\Components\Theme_Component interface.
 *
 * @package ThemeName
 */

namespace App\Components;

/**
 * Theme Component Interface.
 * Each theme component should implements this interface.
 */
interface Theme_Component {

	/**
	 * Adds required the action and filter hooks.
	 *
	 * @access public
	 * @return void
	 */
	public function register(): void;
}

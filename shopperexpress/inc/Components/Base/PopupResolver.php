<?php
/**
 * Popup Resolver.
 *
 * @package ThemeName
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class PopupResolver
 *
 * @package App\Components\Base
 */
class PopupResolver implements Theme_Component {


	private static $instance = null;
	private $popup           = null;
	private $resolved        = false;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
	}

	/**
	 * Get the instance of the class.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get the popup.
	 *
	 * @return array|bool
	 */
	public function get() {
		if ( $this->resolved ) {
			return $this->popup;
		}

		$this->resolved = true;

		if ( ! have_rows( 'cookie_modal', 'options' ) ) {
			return false;
		}

		$url = $_SERVER['REQUEST_URI'];

		while ( have_rows( 'cookie_modal', 'options' ) ) {
			the_row();

			if ( ! $this->enabled() ) {
				continue;
			}

			if ( $this->excluded( $url ) ) {
				continue;
			}

			if ( ! $this->included( $url ) ) {
				continue;
			}

			$this->popup = get_row( true );
			break;
		}

		return $this->popup;
	}

	/**
	 * Check if the popup is enabled.
	 *
	 * @return bool
	 */
	private function enabled(): bool {
		return (bool) get_sub_field( 'enable_popup' );
	}

	/**
	 * Check if the popup is included.
	 *
	 * @param string $url URL to check.
	 *
	 * @return bool
	 */
	private function included( string $url ): bool {
		$rules = get_sub_field( 'include_urls' );

		if ( empty( $rules ) ) {
			return true;
		}

		if ( ! is_array( $rules ) ) {
			$rules = array_filter( array_map( 'trim', explode( "\n", $rules ) ) );
		}

		$path = parse_url( $url, PHP_URL_PATH );

		foreach ( $rules as $rule ) {
			if ( $rule === '' ) {
				continue;
			}
			if ( stripos( $path, $rule ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if the popup is excluded.
	 *
	 * @param string $url URL to check.
	 *
	 * @return bool
	 */
	private function excluded( string $url ): bool {
		$rules = get_sub_field( 'exclude_urls' );

		if ( empty( $rules ) ) {
			return false;
		}

		if ( ! is_array( $rules ) ) {
				$rules = array_filter( array_map( 'trim', explode( "\n", $rules ) ) );
		}

		foreach ( $rules as $rule ) {
			if ( empty( $rule ) ) {
				continue;
			}

			$path = parse_url( $url, PHP_URL_PATH );
			if ( stripos( $path, $rule ) !== false ) {
				return true;
			}
		}

		return false;
	}
}

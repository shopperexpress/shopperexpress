<?php
/**
 * SOC Countdown Timers Module
 *
 * CRUD for reusable countdown timers. Timers are inserted with the
 * `[countdown id="slug"]` shortcode.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Modules;

use App\Components\Base\Countdown;
use App\Components\SOC\Contracts\SOC_Module;

/**
 * Class Countdown_Timers
 */
class Countdown_Timers implements SOC_Module {

	/**
	 * @return string
	 */
	public function get_slug(): string {
		return 'countdown-timers';
	}

	/**
	 * @return string
	 */
	public function get_label(): string {
		return 'Countdown Timers';
	}

	/**
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-clock';
	}

	/**
	 * @param bool $force_refresh Unused — data is read straight from the option.
	 * @return array
	 */
	public function collect( bool $force_refresh = false ): array {
		return array(
			'timers'   => Countdown::get_timers(),
			'defaults' => Countdown::defaults(),
		);
	}

	/**
	 * @param array $data Collected data.
	 * @return void
	 */
	public function render( array $data ): void {
		$view = get_template_directory() . '/inc/Components/SOC/views/countdown-timers.php';
		if ( file_exists( $view ) ) {
			include $view;
		}
	}

	/**
	 * Create or update a timer.
	 *
	 * @param string $slug Existing slug, or '' to create a new one from the name.
	 * @param array  $raw  Raw form input.
	 * @return string|\WP_Error Saved slug.
	 */
	public function save_timer( string $slug, array $raw ) {
		$timer = Countdown::sanitize( $raw );

		if ( '' === $timer['name'] ) {
			return new \WP_Error( 'name', 'Please enter a timer name.' );
		}

		if ( 'fixed' === $timer['type'] && '' === $timer['end'] ) {
			return new \WP_Error( 'end', 'Please set an end date and time for a fixed-date timer.' );
		}

		$timers = Countdown::get_timers();

		if ( '' === $slug ) {
			$base = sanitize_title( $timer['name'] );
			$base = '' !== $base ? $base : 'timer';
			$slug = $base;
			$i    = 2;
			while ( isset( $timers[ $slug ] ) ) {
				$slug = $base . '-' . $i;
				$i++;
			}
		} elseif ( ! isset( $timers[ $slug ] ) ) {
			return new \WP_Error( 'missing', 'Timer not found.' );
		}

		$timers[ $slug ] = $timer;
		update_option( Countdown::OPTION, $timers, false );

		return $slug;
	}

	/**
	 * Delete a timer.
	 *
	 * @param string $slug Timer slug.
	 * @return bool
	 */
	public function delete_timer( string $slug ): bool {
		$timers = Countdown::get_timers();

		if ( ! isset( $timers[ $slug ] ) ) {
			return false;
		}

		unset( $timers[ $slug ] );
		update_option( Countdown::OPTION, $timers, false );

		return true;
	}
}

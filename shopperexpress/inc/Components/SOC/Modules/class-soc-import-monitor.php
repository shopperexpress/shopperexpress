<?php
/**
 * SOC Import Monitor Module
 *
 * Renders the Import Monitor dashboard inside Operation Center.
 * All backend logic (tracking, cron, notifications, ACF) remains
 * in the original App\Components\Base\Import_Monitor_* classes.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Modules;

use App\Components\Base\Import_Monitor_Cron;
use App\Components\Base\Import_Monitor_Tracker;
use App\Components\SOC\Contracts\SOC_Module;
use App\Components\SOC\Support\SOC_Logger;

/**
 * Class Import_Monitor_Panel
 */
class Import_Monitor_Panel implements SOC_Module {
	/**
	 * Get the module slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'import-monitor';
	}

	/**
	 * Get the module label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return 'Import Monitor';
	}

	/**
	 * Get the module icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-update';
	}

	/**
	 * Collect live import state — never cached because state changes on every run.
	 *
	 * @param bool $force_refresh Unused.
	 * @return array
	 */
	public function collect( bool $force_refresh = false ): array {
		$monitoring_enabled = (bool) get_field( 'wpim_enabled', 'option' );
		$imports            = Import_Monitor_Tracker::get_all_imports();

		$rows     = array();
		$success  = 0;
		$failures = 0;
		$never    = 0;

		foreach ( $imports as $config ) {
			$id    = (int) $config['import_id'];
			$state = Import_Monitor_Tracker::get_state( $id );

			$rows[] = array(
				'config' => $config,
				'state'  => $state,
			);

			switch ( $state['status'] ?? '' ) {
				case 'success':
					++$success;
					break;
				case 'failure':
					++$failures;
					break;
				default:
					++$never;
			}
		}

		return array(
			'monitoring_enabled' => $monitoring_enabled,
			'rows'               => $rows,
			'total'              => count( $imports ),
			'success'            => $success,
			'failures'           => $failures,
			'never'              => $never,
			'next_cron'          => wp_next_scheduled( Import_Monitor_Cron::HOOK ),
		);
	}

	/**
	 * Render the Import Monitor panel view.
	 *
	 * @param array $data Collected data.
	 * @return void
	 */
	public function render( array $data ): void {
		require get_template_directory() . '/inc/Components/SOC/views/import-monitor.php';
	}

	// ─── AJAX delegate (called by SOC_Ajax) ──────────────────────────────────

	/**
	 * Toggle the "active" flag on a single monitored import in the ACF repeater.
	 *
	 * @param int  $import_id Import ID.
	 * @param bool $active    New active state.
	 * @return array|\WP_Error Result array on success, WP_Error on failure.
	 */
	public function toggle_import( int $import_id, bool $active ) {
		$imports = get_field( 'wpim_imports', 'option' );

		if ( ! is_array( $imports ) ) {
			return new \WP_Error( 'not_found', __( 'No imports configured.', 'shopperexpress' ) );
		}

		$updated = false;

		foreach ( $imports as &$row ) {
			if ( (int) ( $row['import_id'] ?? 0 ) === $import_id ) {
				$row['active'] = $active ? 1 : 0;
				$updated       = true;
				break;
			}
		}
		unset( $row );

		if ( ! $updated ) {
			return new \WP_Error( 'not_found', __( 'Import not found.', 'shopperexpress' ) );
		}

		update_field( 'wpim_imports', $imports, 'option' );

		SOC_Logger::write(
			'general',
			sprintf(
				'Import Monitor: import #%d toggled %s',
				$import_id,
				$active ? 'active' : 'inactive'
			)
		);

		return array(
			'import_id' => $import_id,
			'active'    => $active,
		);
	}
}

<?php
/**
 * SOC Log Viewer Module
 *
 * This module provides log file viewing, caching, clearing, and exporting
 * capabilities for the Shopperexpress admin.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Modules;

use App\Components\SOC\Contracts\SOC_Module;
use App\Components\SOC\Support\SOC_Cache;
use App\Components\SOC\Support\SOC_Logger;

/**
 * Class Log_Viewer
 *
 * Module for collecting, displaying, and managing various logs in the admin UI.
 */
class Log_Viewer implements SOC_Module {

	/**
	 * Get the unique slug identifier for this module.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'log-viewer';
	}

	/**
	 * Get human-readable label for UI.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return 'Logs';
	}

	/**
	 * Get the dashicon for this module.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-text-page';
	}

	/**
	 * Collect log information and return it as an array.
	 *
	 * @param bool $force_refresh  If true, will clear cache before collecting.
	 * @return array               Collected log info.
	 */
	public function collect( bool $force_refresh = false ): array {
		// If forced refresh, forget old cached data.
		if ( $force_refresh ) {
			SOC_Cache::forget( $this->get_slug(), 'data' );
		}

		// Try to use cached data if available.
		$cached = SOC_Cache::get( $this->get_slug(), 'data' );

		if ( false !== $cached ) {
			return $cached;
		}

		// All supported log types for this module.
		$log_types   = array( 'general', 'api', 'cron', 'cache', 'security', 'performance', 'debug' );
		$active_logs = array();

		// Gather info for each log type: file existence, size, lines.
		foreach ( $log_types as $type ) {
			// Determine file path for each log type.
			$path   = ( $type === 'debug' ) ? WP_CONTENT_DIR . '/debug.log' : SOC_Logger::log_path( $type );
			$exists = file_exists( $path );
			$lines  = 0;

			if ( $exists ) {
				// Use Php's SplFileObject to count lines efficiently.
				$file = new \SplFileObject( $path, 'r' );
				$file->seek( PHP_INT_MAX );
				$lines = $file->key();
				$file  = null;
			}

			$active_logs[] = array(
				'type'    => $type,
				'size_kb' => $exists ? round( filesize( $path ) / 1024, 2 ) : 0,
				'exists'  => $exists,
				'lines'   => $lines,
			);
		}

		// Determine which log is selected for viewing (via GET or default).
		$selected_type = sanitize_key( $_GET['log_type'] ?? 'general' );

		// Validate selected log type.
		if ( ! in_array( $selected_type, $log_types, true ) ) {
			$selected_type = 'general';
		}

		// Retrieve last 200 entries for the selected log.
		if ( $selected_type === 'debug' ) {
			$entries = $this->read_debug_log( 200 );
		} else {
			$entries = SOC_Logger::read( $selected_type, 200 );
		}

		// Prepare data for view rendering.
		$data = array(
			'log_types'     => $log_types,
			'active_logs'   => $active_logs,
			'selected_type' => $selected_type,
			'entries'       => $entries,
			'collected_at'  => current_time( 'mysql' ),
		);

		// Store in cache for 1 minute.
		SOC_Cache::set( $this->get_slug(), 'data', $data, MINUTE_IN_SECONDS );

		return $data;
	}

	/**
	 * Render the log view template in admin.
	 *
	 * @param array $data   Collected module data.
	 * @return void
	 */
	public function render( array $data ): void {
		require get_template_directory() . '/inc/Components/SOC/views/log-viewer.php';
	}

	/**
	 * Clear log file for a specific type.
	 *
	 * @param string $type  Log type to clear.
	 * @return bool         True if log cleared.
	 */
	public function clear( string $type ): bool {
		// Special handling for WordPress debug log.
		if ( $type === 'debug' ) {
			$path = WP_CONTENT_DIR . '/debug.log';
			if ( file_exists( $path ) ) {
				file_put_contents( $path, '' );
			}
			// Log the clearing event in the general log.
			SOC_Logger::write( 'general', 'Log cleared: debug' );
			return true;
		}

		// Use logger utility for other log types.
		SOC_Logger::clear( $type );
		SOC_Logger::write( 'general', 'Log cleared: ' . $type );

		return true;
	}

	/**
	 * Stream the contents of a log to the browser as a downloadable file.
	 *
	 * @param string $type  Log type to export.
	 * @return never        Exits upon completion.
	 */
	public function stream_export( string $type ): never {
		$log_types = array( 'general', 'api', 'cron', 'cache', 'security', 'performance', 'debug' );

		// Validate log type.
		if ( ! in_array( $type, $log_types, true ) ) {
			wp_die( 'Invalid log type.' );
		}

		// Resolve filesystem path for log.
		$path = ( $type === 'debug' ) ? WP_CONTENT_DIR . '/debug.log' : SOC_Logger::log_path( $type );

		// Check if log file exists before streaming.
		if ( ! file_exists( $path ) ) {
			wp_die( 'Log not found.' );
		}

		// Send HTTP headers for download.
		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="soc-' . $type . '-' . date( 'Y-m-d' ) . '.log"' );
		header( 'Content-Length: ' . filesize( $path ) );
		readfile( $path );
		exit;
	}

	/**
	 * Read lines from the WordPress debug log file.
	 *
	 * @param int $lines  How many lines to return from the end of the file.
	 * @return array      Log entries, newest last (in file order).
	 */
	private function read_debug_log( int $lines = 200 ): array {
		$path = WP_CONTENT_DIR . '/debug.log';

		// Return empty if debug log doesn't exist.
		if ( ! file_exists( $path ) ) {
			return array();
		}

		// Open file for reading.
		$file = new \SplFileObject( $path, 'r' );
		// Seek to the last line.
		$file->seek( PHP_INT_MAX );
		$total = $file->key();

		// Calculate where to start reading from to grab $lines lines.
		$start   = max( 0, $total - $lines );
		$entries = array();

		$file->seek( $start );

		// Read each line, trimming, and skip empties.
		while ( ! $file->eof() ) {
			$line = trim( $file->current() );
			if ( $line !== '' ) {
				$entries[] = $line;
			}
			$file->next();
		}

		$file = null;

		return $entries;
	}
}

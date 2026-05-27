<?php
/**
 * SOC Logger
 *
 * Provides file-based logging for various SOC operations in Shopperexpress.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Support;

/**
 * Class SOC_Logger
 *
 * Simple structured logger supporting multiple log types.
 */
class SOC_Logger {

	/**
	 * Directory where all log files are stored.
	 *
	 * @var string
	 */
	const LOG_DIR = WP_CONTENT_DIR . '/soc-logs';

	/**
	 * Supported log types (slugs).
	 *
	 * @var array
	 */
	const TYPES = array( 'api', 'cron', 'cache', 'security', 'performance', 'general' );

	/**
	 * Write a structured log entry.
	 *
	 * @param string $type    Log type (e.g., api, cron, etc).
	 * @param string $message Log message body (plain text).
	 * @param array  $context Optional extra context for JSON encoding (default: empty array).
	 * @return void
	 */
	public static function write( string $type, string $message, array $context = array() ): void {
		// Ensure the log directory exists and is protected.
		self::ensure_log_dir();

		// Get current GMT timestamp for entry.
		$timestamp = gmdate( 'Y-m-d H:i:s' );
		// Format the log type.
		$type_tag = strtoupper( $type );
		// Encode context as JSON if present.
		$ctx_json = ! empty( $context ) ? ' ' . wp_json_encode( $context ) : '';
		// Build log line.
		$line = "[{$timestamp}] [{$type_tag}] {$message}{$ctx_json}\n";

		// Append the log entry to its respective log file.
		file_put_contents( self::log_path( $type ), $line, FILE_APPEND | LOCK_EX );
	}

	/**
	 * Read the last N lines from a log file.
	 *
	 * @param string $type  Log type (slug).
	 * @param int    $lines Number of lines to read from the end (default 500).
	 * @return array<int, string> Array of log lines (each a string).
	 */
	public static function read( string $type, int $lines = 500 ): array {
		$path = self::log_path( $type );

		// If log doesn't exist, return empty array.
		if ( ! file_exists( $path ) ) {
			return array();
		}

		// Open log file in read mode.
		$file = new \SplFileObject( $path, 'r' );
		// Move to end to count total lines (using the key() as the 0-based line index).
		$file->seek( PHP_INT_MAX );
		$total = $file->key();

		// Compute starting line number for the last $lines entries.
		$start = max( 0, $total - $lines );
		$file->seek( $start );

		$result = array();
		// Read lines from start until EOF.
		while ( ! $file->eof() ) {
			$result[] = $file->fgets();
		}

		// Remove possible trailing empty lines.
		return array_filter( $result );
	}

	/**
	 * Truncate (clear) a log file.
	 *
	 * @param string $type Log type (slug).
	 * @return bool True if truncate succeeded, false otherwise.
	 */
	public static function clear( string $type ): bool {
		// Ensure the log directory exists.
		self::ensure_log_dir();

		// Overwrite the file with an empty string (truncate).
		return false !== file_put_contents( self::log_path( $type ), '' );
	}

	/**
	 * Return the absolute path to a log file for given type.
	 *
	 * @param string $type Log type (slug).
	 * @return string Absolute file path to the log file.
	 */
	public static function log_path( string $type ): string {
		// Sanitize the file name to prevent directory traversal and other vulns.
		return self::LOG_DIR . '/' . sanitize_file_name( $type ) . '.log';
	}

	/**
	 * Ensure the log directory exists and protect it from web access.
	 *
	 * Creates the log directory if needed, protects it with an .htaccess rule,
	 * and drops an index.php file to discourage directory browsing.
	 *
	 * @return void
	 */
	private static function ensure_log_dir(): void {
		// Make log directory if it doesn't exist.
		wp_mkdir_p( self::LOG_DIR );

		// Block web access via .htaccess (for Apache).
		$htaccess = self::LOG_DIR . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			file_put_contents( $htaccess, 'Deny from all' );
		}

		// Drop a blank index.php.
		$index = self::LOG_DIR . '/index.php';
		if ( ! file_exists( $index ) ) {
			file_put_contents( $index, '<?php // silence' );
		}
	}
}

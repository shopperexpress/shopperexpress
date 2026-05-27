<?php
/**
 * SOC Response
 *
 * Helper class for consistently sending AJAX JSON responses
 * for the Shopperexpress plugin's SOC module.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Support;

/**
 * Class SOC_Response
 *
 * Utility class providing standard methods to send
 * AJAX JSON success or error responses and immediately exit.
 */
class SOC_Response {

	/**
	 * Send a JSON success response and exit.
	 *
	 * @param array $data   Data to be returned in the response body.
	 * @param int   $status HTTP status code (default 200).
	 * @return never
	 */
	public static function success( array $data = array(), int $status = 200 ): never {
		wp_send_json_success( $data, $status );
	}

	/**
	 * Send a JSON error response and exit.
	 *
	 * @param string $message Error message to display.
	 * @param int    $status  HTTP status code (default 400).
	 * @return never
	 */
	public static function error( string $message = 'Error', int $status = 400 ): never {
		wp_send_json_error( array( 'message' => $message ), $status );
	}
}

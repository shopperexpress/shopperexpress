<?php
/**
 * ADF Lead Delivery — WP Cron Auto-Retry
 *
 * Registers a scheduled task that periodically retries failed ADF deliveries.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class ADF_Cron
 *
 * Schedules and handles automatic retry of failed ADF API lead deliveries.
 * Respects the configured max_retries limit and only runs when delivery method is 'api'.
 */
class ADF_Cron implements Theme_Component {

	/**
	 * WP Cron hook name.
	 */
	const HOOK = 'adf_auto_retry_failed_leads';

	/**
	 * Register component hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( self::HOOK, array( $this, 'run' ) );
		add_action( 'after_setup_theme', array( $this, 'schedule' ) );
	}

	/**
	 * Ensure the cron event is scheduled (runs every 15 minutes).
	 *
	 * @return void
	 */
	public function schedule(): void {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time(), 'adf_every_15_minutes', self::HOOK );
		}
	}

	/**
	 * Process failed leads: retry up to max_retries, skip duplicates.
	 *
	 * @return void
	 */
	public function run(): void {
		// Only meaningful when delivery method is API.
		if ( 'api' !== get_option( 'adf_delivery_method', 'email' ) ) {
			return;
		}

		$max_retries = (int) get_option( 'adf_max_retries', 3 );
		if ( $max_retries <= 0 ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'adf_lead_log';

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}`
				 WHERE status = 'failed' AND retry_count < %d
				 ORDER BY submitted_at ASC
				 LIMIT 20",
				$max_retries
			),
			ARRAY_A
		);

		if ( empty( $rows ) ) {
			return;
		}

		$client = new ADF_Api_Client();

		foreach ( $rows as $row ) {
			if ( empty( $row['adfxml_payload'] ) ) {
				continue;
			}

			$fields = array(
				'first_name'  => $row['first_name'],
				'last_name'   => $row['last_name'],
				'email'       => $row['email'],
				'phone'       => $row['phone'],
				'form_name'   => $row['form_name'],
				'lead_source' => $row['lead_source'] ?? '',
			);

			$result = $client->send( $row['adfxml_payload'], $fields );

			$new_status = $result['success'] ? 'success' : 'failed';

			$wpdb->update(
				$table,
				array(
					'status'        => $new_status,
					'retry_count'   => (int) $row['retry_count'] + 1,
					'response_code' => $result['response_code'],
					'response_body' => $result['response_body'],
					'error_message' => $result['error_message'],
				),
				array( 'id' => (int) $row['id'] ),
				array( '%s', '%d', '%d', '%s', '%s' ),
				array( '%d' )
			);

			// Notify admin if still failing and notifications are enabled.
			if ( ! $result['success'] ) {
				wps_adf_notify_admin_failure( $row );
			}
		}
	}

	/**
	 * Register the custom 15-minute cron interval.
	 *
	 * @param array $schedules Existing WP cron schedules.
	 * @return array
	 */
	public static function add_cron_interval( array $schedules ): array {
		$schedules['adf_every_15_minutes'] = array(
			'interval' => 15 * MINUTE_IN_SECONDS,
			'display'  => 'Every 15 Minutes (ADF Retry)',
		);
		return $schedules;
	}
}

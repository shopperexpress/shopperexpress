<?php
/**
 * Import Monitor – Notification Dispatcher.
 *
 * Handles email (wp_mail) and Slack (wp_remote_post) notifications.
 * Template variable replacement is applied to both channels.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

/**
 * Class Import_Monitor_Notifier
 *
 * Stateless helper – no hooks registered here; called directly by the tracker
 * and the cron checker.
 *
 * @package App\Components\Base
 */
class Import_Monitor_Notifier {

	/**
	 * Dispatch a notification for the given import event.
	 *
	 * @param array  $import_config  Row from the wpim_imports repeater.
	 * @param string $status         'success' | 'failure' | 'missing'.
	 * @param string $error_message  Error text (empty string for success).
	 * @param array  $counts         Optional record counts: created, updated, skipped.
	 * @return void
	 */
	public function dispatch( array $import_config, string $status, string $error_message = '', array $counts = array() ): void {
		$import_id = $import_config['import_id'] ?? '?';

		// Only suppress when the flag is explicitly set to false/0.
		// A missing key (e.g. row saved before the field existed) defaults to enabled.
		if ( 'success' === $status && isset( $import_config['notify_success'] ) && ! $import_config['notify_success'] ) {
			error_log( "[Import Monitor] dispatch skipped: notify_success=false for import #{$import_id}" );
			return;
		}
		if ( in_array( $status, array( 'failure', 'missing' ), true ) && isset( $import_config['notify_failure'] ) && ! $import_config['notify_failure'] ) {
			error_log( "[Import Monitor] dispatch skipped: notify_failure=false for import #{$import_id}" );
			return;
		}

		$vars       = $this->build_vars( $import_config, $status, $error_message, $counts );
		$is_success = 'success' === $status;
		$is_failure = in_array( $status, array( 'failure', 'missing' ), true );

		if ( $this->get_acf_bool( 'wpim_email_enabled' ) ) {
			$email_ok = ( $is_success && $this->get_acf_bool( 'wpim_email_notify_success' ) )
				|| ( $is_failure && $this->get_acf_bool( 'wpim_email_notify_failure' ) );
			if ( $email_ok ) {
				$this->send_email( $vars );
			}
		}

		if ( ! $this->get_acf_bool( 'wpim_slack_enabled' ) ) {
			error_log( "[Import Monitor] Slack skipped: wpim_slack_enabled=false for import #{$import_id}" );
			return;
		}

		$slack_ok = ( $is_success && $this->get_acf_bool( 'wpim_slack_notify_success' ) )
			|| ( $is_failure && $this->get_acf_bool( 'wpim_slack_notify_failure' ) );

		if ( ! $slack_ok ) {
			error_log( "[Import Monitor] Slack skipped: notify flag off for status={$status}, import #{$import_id}" );
			return;
		}

		$this->send_slack( $vars );
	}

	/**
	 * Read an ACF options-page boolean, falling back to raw wp_options when
	 * get_field() is unavailable or returns null (e.g. WP-CLI / early cron).
	 *
	 * ACF stores options-page fields as options_{field_name} in wp_options.
	 *
	 * @param string $field_name ACF field name (without "options_" prefix).
	 * @return bool
	 */
	private function get_acf_bool( string $field_name ): bool {
		if ( function_exists( 'get_field' ) ) {
			$val = get_field( $field_name, 'option' );
			if ( null !== $val ) {
				return (bool) $val;
			}
		}
		return (bool) get_option( 'options_' . $field_name );
	}

	/**
	 * Build replacement variable map.
	 *
	 * Supported template variables:
	 *   {import_id}     – numeric WP All Import ID
	 *   {import_name}   – human label from ACF config
	 *   {status}        – Success | Failure | Missing
	 *   {date}          – WP local datetime of this notification
	 *   {error_message} – error text or "N/A"
	 *   {site_url}      – bare hostname
	 *   {created}       – number of new posts created
	 *   {updated}       – number of existing posts updated
	 *   {skipped}       – number of records skipped
	 *
	 * @param array  $import_config Import row.
	 * @param string $status        Event status.
	 * @param string $error_message Error detail.
	 * @param array  $counts        created / updated / skipped counts.
	 * @return array<string,string>
	 */
	private function build_vars( array $import_config, string $status, string $error_message, array $counts = array() ): array {
		return array(
			'{import_id}'     => (string) ( $import_config['import_id'] ?? '' ),
			'{import_name}'   => (string) ( $import_config['import_name'] ?? '' ),
			'{status}'        => ucfirst( $status ),
			'{date}'          => current_time( 'Y-m-d H:i:s' ),
			'{error_message}' => $error_message ?: 'N/A',
			'{site_url}'      => parse_url( home_url(), PHP_URL_HOST ),
			'{created}'       => (string) ( $counts['created'] ?? 0 ),
			'{updated}'       => (string) ( $counts['updated'] ?? 0 ),
			'{skipped}'       => (string) ( $counts['skipped'] ?? 0 ),
		);
	}

	/**
	 * Replace template variables in a string.
	 *
	 * @param string               $template Raw template text.
	 * @param array<string,string> $vars  Variable map.
	 * @return string
	 */
	private function replace( string $template, array $vars ): string {
		return str_replace( array_keys( $vars ), array_values( $vars ), $template );
	}

	/**
	 * Send email notification.
	 *
	 * @param array<string,string> $vars Variable map.
	 * @return void
	 */
	private function send_email( array $vars ): void {
		$recipients_raw = function_exists( 'get_field' ) ? get_field( 'wpim_email_recipients', 'option' ) : get_option( 'options_wpim_email_recipients' );
		if ( empty( $recipients_raw ) ) {
			return;
		}

		$recipients = array_filter( array_map( 'trim', explode( ',', $recipients_raw ) ) );
		if ( empty( $recipients ) ) {
			return;
		}

		$subject_tpl = ( function_exists( 'get_field' ) ? get_field( 'wpim_email_subject', 'option' ) : get_option( 'options_wpim_email_subject' ) ) ?: '[{site_url}] Import {status}: {import_name}';
		$body_tpl    = ( function_exists( 'get_field' ) ? get_field( 'wpim_email_template', 'option' ) : get_option( 'options_wpim_email_template' ) ) ?: implode( "\n", array_keys( $vars ) );

		$subject = $this->replace( $subject_tpl, $vars );
		$body    = $this->replace( $body_tpl, $vars );

		wp_mail( $recipients, $subject, $body );
	}

	/**
	 * Send Slack notification via incoming webhook.
	 *
	 * @param array<string,string> $vars Variable map.
	 * @return void
	 */
	private function send_slack( array $vars ): void {
		$webhook = trim( (string) ( function_exists( 'get_field' ) ? get_field( 'wpim_slack_webhook', 'option' ) : get_option( 'options_wpim_slack_webhook' ) ) );

		if ( empty( $webhook ) ) {
			error_log( '[Import Monitor] Slack skipped: webhook URL is empty.' );
			return;
		}

		if ( ! filter_var( $webhook, FILTER_VALIDATE_URL ) ) {
			error_log( '[Import Monitor] Slack skipped: webhook URL failed FILTER_VALIDATE_URL — "' . $webhook . '"' );
			return;
		}

		if ( strpos( $webhook, 'hooks.slack.com' ) === false ) {
			error_log( '[Import Monitor] Slack skipped: webhook URL does not contain hooks.slack.com — "' . $webhook . '"' );
			return;
		}

		$msg_tpl = ( function_exists( 'get_field' ) ? get_field( 'wpim_slack_template', 'option' ) : get_option( 'options_wpim_slack_template' ) )
			?: '*[{site_url}] Import {status}*: {import_name} — {error_message}';

		$text = $this->replace( $msg_tpl, $vars );

		$payload = array(
			'text' => $text,
		);

		$raw_channel = function_exists( 'get_field' ) ? get_field( 'wpim_slack_channel', 'option' ) : get_option( 'options_wpim_slack_channel' );
		$channel     = trim( (string) $raw_channel );
		if ( ! empty( $channel ) ) {
			$payload['channel'] = $channel;
		}

		error_log( '[Import Monitor] Sending Slack notification to webhook (channel: ' . ( $channel ?: 'default' ) . ')' );

		// Use blocking=true: non-blocking requests are silently dropped when PHP
		// shuts down at the end of a long import request before the TCP buffer flushes.
		$response = wp_remote_post(
			$webhook,
			array(
				'headers'     => array( 'Content-Type' => 'application/json' ),
				'body'        => wp_json_encode( $payload ),
				'timeout'     => 15,
				'blocking'    => true,
				'data_format' => 'body',
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( '[Import Monitor] Slack request failed: ' . $response->get_error_message() );
		} else {
			$code = wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );
			if ( 200 !== (int) $code ) {
				error_log( "[Import Monitor] Slack returned HTTP {$code}: {$body}" );
			} else {
				error_log( '[Import Monitor] Slack notification sent successfully.' );
			}
		}
	}
}

<?php
/**
 * Import Monitor – ACF Options Page & Field Groups.
 *
 * Registers the "Import Monitor" sub-page under Theme Options and
 * programmatically creates every field group via acf_add_local_field_group().
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class Import_Monitor_ACF
 *
 * @package App\Components\Base
 */
class Import_Monitor_ACF implements Theme_Component {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'acf/init', array( $this, 'add_options_sub_page' ) );
		add_action( 'acf/init', array( $this, 'register_field_groups' ) );
	}

	/**
	 * Add "Import Monitor" sub-page under the existing Theme Options parent.
	 *
	 * @return void
	 */
	public function add_options_sub_page(): void {
		if ( ! function_exists( 'acf_add_options_sub_page' ) ) {
			return;
		}

		acf_add_options_sub_page(
			array(
				'page_title'  => 'Monitor Settings',
				'menu_title'  => 'Monitor Settings',
				'menu_slug'   => 'monitor-settings',
				'parent_slug' => 'wpim-dashboard',
				'capability'  => 'manage_options',
			)
		);
	}

	/**
	 * Register all ACF field groups for the Import Monitor options page.
	 *
	 * @return void
	 */
	public function register_field_groups(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		// -----------------------------------------------------------------------
		// Field group: Global Settings + Email + Slack
		// -----------------------------------------------------------------------
		acf_add_local_field_group(
			array(
				'key'                   => 'group_wpim_settings',
				'title'                 => 'Import Monitor Settings',
				'fields'                => array(

					// ── Global ──────────────────────────────────────────────────
					array(
						'key'           => 'field_wpim_enabled',
						'label'         => 'Enable Monitoring',
						'name'          => 'wpim_enabled',
						'type'          => 'true_false',
						'ui'            => 1,
						'ui_on_text'    => 'On',
						'ui_off_text'   => 'Off',
						'default_value' => 0,
						'instructions'  => 'Master switch for Import Monitor. When set to Off, no monitoring/tracking or notifications (Email/Slack) take place.',
						'wrapper'       => array( 'class' => 'wpim-field-wide' ),
					),

					// ── Email ────────────────────────────────────────────────────
					array(
						'key'           => 'field_wpim_email_enabled',
						'label'         => 'Enable Email Notifications',
						'name'          => 'wpim_email_enabled',
						'type'          => 'true_false',
						'ui'            => 1,
						'ui_on_text'    => 'On',
						'ui_off_text'   => 'Off',
						'default_value' => 0,
						'instructions'  => 'When on, Import Monitor will send Email notifications upon import success or failure.',
					),
					array(
						'key'               => 'field_wpim_email_notify_success',
						'label'             => 'Notify on Success (Email)',
						'name'              => 'wpim_email_notify_success',
						'type'              => 'true_false',
						'ui'                => 1,
						'ui_on_text'        => 'On',
						'ui_off_text'       => 'Off',
						'default_value'     => 1,
						'instructions'      => 'Send an email when an import completes successfully. Disable to receive only failure alerts.',
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_wpim_email_enabled',
									'operator' => '==',
									'value'    => '1',
								),
							),
						),
					),
					array(
						'key'               => 'field_wpim_email_notify_failure',
						'label'             => 'Notify on Failure (Email)',
						'name'              => 'wpim_email_notify_failure',
						'type'              => 'true_false',
						'ui'                => 1,
						'ui_on_text'        => 'On',
						'ui_off_text'       => 'Off',
						'default_value'     => 1,
						'instructions'      => 'Send an email when an import fails or is overdue.',
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_wpim_email_enabled',
									'operator' => '==',
									'value'    => '1',
								),
							),
						),
					),
					array(
						'key'               => 'field_wpim_email_recipients',
						'label'             => 'Recipient Emails',
						'name'              => 'wpim_email_recipients',
						'type'              => 'text',
						'instructions'      => 'Enter one or more recipient email addresses, separated by commas. These users will get notifications for monitored imports. E.g. "admin@example.com,dev@example.com".',
						'placeholder'       => 'admin@example.com, dev@example.com',
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_wpim_email_enabled',
									'operator' => '==',
									'value'    => '1',
								),
							),
						),
					),
					array(
						'key'               => 'field_wpim_email_subject',
						'label'             => 'Email Subject',
						'name'              => 'wpim_email_subject',
						'type'              => 'text',
						'default_value'     => '[{site_url}] Import {status}: {import_name}',
						'instructions'      => 'Set the subject line for notification emails. You can use variables, e.g. {site_url}, {status}, {import_name}.',
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_wpim_email_enabled',
									'operator' => '==',
									'value'    => '1',
								),
							),
						),
					),
					array(
						'key'               => 'field_wpim_email_template',
						'label'             => 'Email Template',
						'name'              => 'wpim_email_template',
						'type'              => 'textarea',
						'rows'              => 10,
						'instructions'      => 'Specify the email notification body template. Available variables: {import_id}, {import_name}, {status}, {date}, {error_message}, {site_url}. Use these placeholders within your message.',
						'default_value'     => "Import Monitor Report\n\nImport: {import_name} (ID: {import_id})\nStatus: {status}\nDate: {date}\nSite: {site_url}\n\nError: {error_message}",
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_wpim_email_enabled',
									'operator' => '==',
									'value'    => '1',
								),
							),
						),
					),

					// ── Slack ────────────────────────────────────────────────────
					array(
						'key'           => 'field_wpim_slack_enabled',
						'label'         => 'Enable Slack Notifications',
						'name'          => 'wpim_slack_enabled',
						'type'          => 'true_false',
						'ui'            => 1,
						'ui_on_text'    => 'On',
						'ui_off_text'   => 'Off',
						'default_value' => 0,
						'instructions'  => 'Enable to send notifications to a Slack channel when monitored imports complete or fail.',
					),
					array(
						'key'               => 'field_wpim_slack_notify_success',
						'label'             => 'Notify on Success (Slack)',
						'name'              => 'wpim_slack_notify_success',
						'type'              => 'true_false',
						'ui'                => 1,
						'ui_on_text'        => 'On',
						'ui_off_text'       => 'Off',
						'default_value'     => 1,
						'instructions'      => 'Send a Slack message when an import completes successfully. Disable to receive only failure alerts.',
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_wpim_slack_enabled',
									'operator' => '==',
									'value'    => '1',
								),
							),
						),
					),
					array(
						'key'               => 'field_wpim_slack_notify_failure',
						'label'             => 'Notify on Failure (Slack)',
						'name'              => 'wpim_slack_notify_failure',
						'type'              => 'true_false',
						'ui'                => 1,
						'ui_on_text'        => 'On',
						'ui_off_text'       => 'Off',
						'default_value'     => 1,
						'instructions'      => 'Send a Slack message when an import fails or is overdue.',
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_wpim_slack_enabled',
									'operator' => '==',
									'value'    => '1',
								),
							),
						),
					),
					array(
						'key'               => 'field_wpim_slack_webhook',
						'label'             => 'Slack Webhook URL',
						'name'              => 'wpim_slack_webhook',
						'type'              => 'url',
						'placeholder'       => 'https://hooks.slack.com/services/…',
						'instructions'      => 'Enter the Slack incoming webhook URL to send notifications to a channel. Create a webhook in your Slack workspace if you don\'t have one yet.',
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_wpim_slack_enabled',
									'operator' => '==',
									'value'    => '1',
								),
							),
						),
					),
					array(
						'key'               => 'field_wpim_slack_channel',
						'label'             => 'Slack Channel',
						'name'              => 'wpim_slack_channel',
						'type'              => 'text',
						'placeholder'       => '#imports',
						'instructions'      => 'Override the default channel for this webhook. Enter a channel name (#general) or user ID (@username). Leave blank to use the webhook\'s default channel.',
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_wpim_slack_enabled',
									'operator' => '==',
									'value'    => '1',
								),
							),
						),
					),
					array(
						'key'               => 'field_wpim_slack_template',
						'label'             => 'Slack Message Template',
						'name'              => 'wpim_slack_template',
						'type'              => 'textarea',
						'rows'              => 6,
						'instructions'      => 'Message body sent to Slack. You can use variables: {import_id}, {import_name}, {status}, {date}, {error_message}, {site_url}.',
						'default_value'     => "*[{site_url}] Import {status}*\nImport: {import_name} (ID: {import_id})\nDate: {date}\nError: {error_message}",
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_wpim_slack_enabled',
									'operator' => '==',
									'value'    => '1',
								),
							),
						),
					),

					// ── Cron interval ───────────────────────────────────────────
					array(
						'key'           => 'field_wpim_cron_interval',
						'label'         => 'Cron Check Interval (minutes)',
						'name'          => 'wpim_cron_interval',
						'type'          => 'number',
						'default_value' => 30,
						'min'           => 5,
						'max'           => 1440,
						'instructions'  => 'Controls how frequently (in minutes) Import Monitor\'s background cron job checks monitored imports for status/alerts. Minimum: 5 minutes. Maximum: 1440 (24 hours).',
					),
				),
				'location'              => array(
					array(
						array(
							'param'    => 'options_page',
							'operator' => '==',
							'value'    => 'monitor-settings',
						),
					),
				),
				'menu_order'            => 0,
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'left',
				'instruction_placement' => 'label',
			)
		);

		// -----------------------------------------------------------------------
		// Field group: Monitored Imports repeater
		// -----------------------------------------------------------------------
		acf_add_local_field_group(
			array(
				'key'                   => 'group_wpim_monitored',
				'title'                 => 'Monitored Imports',
				'fields'                => array(
					array(
						'key'          => 'field_wpim_imports',
						'label'        => 'Monitored Imports',
						'name'         => 'wpim_imports',
						'type'         => 'repeater',
						'layout'       => 'block',
						'button_label' => 'Add Import',
						'instructions' => 'Add each import you want to monitor. Each row represents a single import from WP All Import, configured below.',
						'sub_fields'   => array(

							array(
								'key'          => 'field_wpim_import_id',
								'label'        => 'Import ID',
								'name'         => 'import_id',
								'type'         => 'number',
								'required'     => 1,
								'instructions' => 'The numeric ID from WP All Import (visible in the import list URL). Example: /wp-admin/admin.php?page=pmxi-admin-import&id=3 → ID is 3.',
								'wrapper'      => array( 'width' => '20' ),
							),
							array(
								'key'          => 'field_wpim_import_name',
								'label'        => 'Import Name',
								'name'         => 'import_name',
								'type'         => 'text',
								'required'     => 1,
								'instructions' => 'A name for your reference. Should match or describe the import job in WP All Import.',
								'wrapper'      => array( 'width' => '30' ),
							),
							array(
								'key'           => 'field_wpim_check_mode',
								'label'         => 'Check Mode',
								'name'          => 'check_mode',
								'type'          => 'select',
								'choices'       => array(
									'on_completion'   => 'On Completion',
									'scheduled_check' => 'Scheduled Check',
								),
								'default_value' => 'on_completion',
								'instructions'  => 'Decide how Import Monitor should check this import: "On Completion" checks only when import completes. "Scheduled Check" will check at intervals.',
								'wrapper'       => array( 'width' => '25' ),
							),
							array(
								'key'               => 'field_wpim_schedule_interval',
								'label'             => 'Schedule Interval',
								'name'              => 'schedule_interval',
								'type'              => 'select',
								'choices'           => array(
									'hourly'     => 'Hourly',
									'daily'      => 'Daily',
									'twicedaily' => 'Twice Daily',
									'custom'     => 'Custom (use cron interval setting)',
								),
								'default_value'     => 'hourly',
								'instructions'      => 'If "Scheduled Check" is selected, choose how often to check. "Hourly" = every hour, "Daily" = every day, etc. "Custom" uses your global cron check interval.',
								'wrapper'           => array( 'width' => '25' ),
								'conditional_logic' => array(
									array(
										array(
											'field'    => 'field_wpim_check_mode',
											'operator' => '==',
											'value'    => 'scheduled_check',
										),
									),
								),
							),
							array(
								'key'          => 'field_wpim_row_divider',
								'label'        => '',
								'name'         => '',
								'type'         => 'message',
								'instructions' => 'The following options control notifications and thresholds for this individual import.',
								'wrapper'      => array( 'width' => '100' ),
							),
							array(
								'key'           => 'field_wpim_notify_success',
								'label'         => 'Notify on Success',
								'name'          => 'notify_success',
								'type'          => 'true_false',
								'ui'            => 1,
								'default_value' => 1,
								'instructions'  => 'If enabled, notification will be sent when this import job completes successfully.',
								'wrapper'       => array( 'width' => '25' ),
							),
							array(
								'key'           => 'field_wpim_notify_failure',
								'label'         => 'Notify on Failure',
								'name'          => 'notify_failure',
								'type'          => 'true_false',
								'ui'            => 1,
								'default_value' => 1,
								'instructions'  => 'If enabled, notify if this import fails or is incomplete according to monitoring.',
								'wrapper'       => array( 'width' => '25' ),
							),
							array(
								'key'           => 'field_wpim_max_age_hours',
								'label'         => 'Max Expected Age (hours)',
								'name'          => 'max_age_hours',
								'type'          => 'number',
								'default_value' => 24,
								'min'           => 1,
								'instructions'  => 'Alert if this job has not run within this many hours. Used only by "Scheduled Check".',
								'wrapper'       => array( 'width' => '25' ),
							),
							array(
								'key'           => 'field_wpim_import_active',
								'label'         => 'Active',
								'name'          => 'active',
								'type'          => 'true_false',
								'ui'            => 1,
								'default_value' => 1,
								'instructions'  => 'Set to Off to temporarily disable monitoring of this import. It will be skipped by Import Monitor.',
								'wrapper'       => array( 'width' => '25' ),
							),
						),
					),
				),
				'location'              => array(
					array(
						array(
							'param'    => 'options_page',
							'operator' => '==',
							'value'    => 'monitor-settings',
						),
					),
				),
				'menu_order'            => 10,
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'left',
				'instruction_placement' => 'label',
			)
		);
	}
}

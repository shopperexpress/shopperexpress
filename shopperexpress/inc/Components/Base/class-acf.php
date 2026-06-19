<?php
/**
 * ACF Component
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * ACF Component
 */
class ACF implements Theme_Component {

	/**
	 * Register component
	 *
	 * @return void
	 */
	public function register(): void {

		add_action( 'after_setup_theme', array( $this, 'maybe_create_lead_log_table' ) );
		add_action( 'after_setup_theme', array( $this, 'maybe_create_vdr_log_table' ) );

		add_action(
			'acf/init',
			function () {

				if ( get_field( 'use_new_databse_table', 'option' ) ) {

					if ( ! get_option( 'custom_tables_created' ) ) {
						update_option( 'custom_tables_created', 1 );
						$this->create_custom_tables();
					}

					add_action( 'acf/save_post', array( $this, 'save_acf_to_custom_table' ), 20 );
					add_action( 'pmxi_saved_post', array( $this, 'save_acf_to_custom_table' ), 10, 3 );
					add_action( 'before_delete_post', array( $this, 'acf_listings_delete' ) );
					add_action( 'acf/pre_load_value', array( $this, 'acf_listings_load' ), 20, 3 );
					add_filter( 'acf/load_field/name=acf_field_selector', array( $this, 'acf_field_selector' ), 10 );
				}
			}
		);
	}

	/**
	 * Create the ADF lead delivery log table if it does not exist yet.
	 *
	 * @return void
	 */
	/**
	 * Current schema version. Bump this when columns are added/changed.
	 */
	const ADF_LOG_TABLE_VERSION = 2;

	public function maybe_create_lead_log_table(): void {
		global $wpdb;

		$table           = $wpdb->prefix . 'adf_lead_log';
		$current_version = (int) get_option( 'adf_lead_log_table_version', 0 );

		if ( $current_version >= self::ADF_LOG_TABLE_VERSION ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// dbDelta adds missing columns to existing tables — safe to run on upgrades.
		$sql = "CREATE TABLE `{$table}` (
			id              bigint(20) UNSIGNED  NOT NULL AUTO_INCREMENT,
			submitted_at    datetime             NOT NULL DEFAULT CURRENT_TIMESTAMP,
			site_name       varchar(255)         NOT NULL DEFAULT '',
			form_name       varchar(255)         NOT NULL DEFAULT '',
			lead_source     varchar(500)         NOT NULL DEFAULT '',
			first_name      varchar(100)         NOT NULL DEFAULT '',
			last_name       varchar(100)         NOT NULL DEFAULT '',
			email           varchar(255)         NOT NULL DEFAULT '',
			phone           varchar(50)          NOT NULL DEFAULT '',
			delivery_method enum('email','api')  NOT NULL DEFAULT 'email',
			api_endpoint    varchar(500)         NOT NULL DEFAULT '',
			response_code   smallint(6)          NOT NULL DEFAULT 0,
			response_body   text                 NOT NULL,
			status          enum('success','failed','pending') NOT NULL DEFAULT 'pending',
			retry_count     tinyint(3) UNSIGNED  NOT NULL DEFAULT 0,
			error_message   text                 NOT NULL,
			adfxml_payload  mediumtext           NOT NULL,
			PRIMARY KEY (id),
			KEY status (status),
			KEY submitted_at (submitted_at),
			KEY email (email)
		) {$charset_collate};";

		dbDelta( $sql );

		update_option( 'adf_lead_log_table_version', self::ADF_LOG_TABLE_VERSION );
	}

	const VDR_LOG_TABLE_VERSION = 1;

	public function maybe_create_vdr_log_table(): void {
		global $wpdb;

		$table           = $wpdb->prefix . 'vdr_log';
		$current_version = (int) get_option( 'vdr_log_table_version', 0 );

		if ( $current_version >= self::VDR_LOG_TABLE_VERSION ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE `{$table}` (
			id          bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			requested_at datetime           NOT NULL DEFAULT CURRENT_TIMESTAMP,
			vin         varchar(17)         NOT NULL DEFAULT '',
			dealer_name varchar(255)        NOT NULL DEFAULT '',
			site_name   varchar(255)        NOT NULL DEFAULT '',
			result      enum('success','error') NOT NULL DEFAULT 'error',
			http_code   smallint(6)         NOT NULL DEFAULT 0,
			from_cache  tinyint(1)          NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			KEY vin (vin),
			KEY requested_at (requested_at),
			KEY result (result)
		) {$charset_collate};";

		dbDelta( $sql );

		update_option( 'vdr_log_table_version', self::VDR_LOG_TABLE_VERSION );
	}

	/**
	 * Create custom ACF table
	 *
	 * @return void
	 */
	private function create_custom_tables() {
		global $wpdb;

		$tables          = array( 'listings', 'used-listings' );
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		foreach ( $tables as $table ) {

			$full_table_name = $table;

			$exists = $wpdb->get_var(
				$wpdb->prepare(
					'SHOW TABLES LIKE %s',
					$full_table_name
				)
			);

			if ( $exists === $full_table_name ) {
				continue;
			}

			$sql = "CREATE TABLE `{$full_table_name}` (
            post_id int(20) NOT NULL,
            data json NOT NULL,
            PRIMARY KEY (post_id)
        ) {$charset_collate};";

			$wpdb->query( $sql );

		}
	}

	/**
	 * ACF field selector
	 *
	 * @param array $field Field.
	 *
	 * @return array
	 */
	public function acf_field_selector( $field ) {

		$group_keys = array(
			'group_66d718154c076',
			'group_66d5d748559df',
			'group_66d70dac7ba8b',
			'group_5ff8660abf8c0',
			'group_61dd34e304294',
			'group_64d55468367a0',
			'group_64e660ebbcb05',
			'group_65eafcb3db305',
		);

		$field['choices'] = array();

		foreach ( $group_keys as $group_key ) {
			$fields = acf_get_fields( $group_key );
			if ( ! $fields ) {
				continue;
			}

			foreach ( $fields as $f ) {
				$field['choices'][ $f['name'] ] = $f['label'] . " ({$f['name']})";
			}
		}

		return $field;
	}

	/**
	 * Save ACF fields to custom table
	 *
	 * @param int               $post_id Post ID.
	 * @param \SimpleXMLElement $xml_node XML node.
	 * @param bool              $is_update Is update.
	 *
	 * @return void
	 */
	public function save_acf_to_custom_table( $post_id, $xml_node = null, $is_update = null ) {

		$post_type = get_post_type( $post_id );
		if ( ! in_array( $post_type, array( 'listings', 'used-listings' ) ) ) {
			return;
		}

		$fields = 'used-listings' === $post_type ? acf_get_fields( 'group_65eafcb3db305' ) : acf_get_fields( 'group_5ff8660abf8c0' );
		if ( ! $fields ) {
			return;
		}

		$acf_group_names = array_map( fn( $f ) => $f['name'], $fields );
		$acf_data        = array();

		foreach ( $acf_group_names as $field_name ) {
			$value = $_POST['acf'][ $field_name ] ?? get_field( $field_name, $post_id );

			if ( $xml_node && isset( $xml_node->{$field_name} ) ) {
				$value = (string) $xml_node->{$field_name};
			}

			if ( $value === '' || $value === null ) {
				continue;
			}

			$field = get_field_object( $field_name, $post_id );
			if ( $field && ( $field['type'] === 'repeater' || $field['type'] === 'group' ) && ! is_array( $value ) ) {
				$value = maybe_unserialize( $value );
				if ( ! is_array( $value ) ) {
					$value = array();
				}
			}

			$acf_data[ $field_name ] = $value;
		}

		if ( empty( $acf_data ) ) {
			return;
		}

		global $wpdb;
		$table = $post_type;
		$row   = $wpdb->get_var( $wpdb->prepare( "SELECT data FROM $table WHERE post_id = %d", $post_id ) );
		$data  = $row ? json_decode( $row, true ) : array();
		$data  = array_merge( $data, $acf_data );

		$json = wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		if ( $row ) {
			$wpdb->update( $table, array( 'data' => $json ), array( 'post_id' => $post_id ), array( '%s' ), array( '%d' ) );
		} else {
			$wpdb->insert(
				$table,
				array(
					'post_id' => $post_id,
					'data'    => $json,
				),
				array( '%d', '%s' )
			);
		}
	}

	/**
	 * Delete ACF data from custom table
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function acf_listings_delete( $post_id ) {
		$post_type = get_post_type( $post_id );
		if ( ! in_array( $post_type, array( 'listings', 'used-listings' ) ) ) {
			return;
		}

		global $wpdb;
		$table = $post_type;

		$wpdb->delete(
			$table,
			array( 'post_id' => $post_id ),
			array( '%d' )
		);
	}

	/**
	 * Load ACF data from custom table
	 *
	 * @param mixed $null Null.
	 * @param int   $post_id Post ID.
	 * @param array $field Field.
	 *
	 * @return mixed
	 */
	public function acf_listings_load( $null, $post_id, $field ) {
		$post_type = get_post_type( $post_id );
		if ( ! in_array( $post_type, array( 'listings', 'used-listings' ) ) ) {
			return;
		}
		if ( 'listings' === $post_type ) {
			if ( $field['parent'] !== 'group_5ff8660abf8c0' ) {
				return $null;
			}
		} elseif ( 'used-listings' === $post_type ) {
			if ( $field['parent'] !== 'group_65eafcb3db305' ) {
				return $null;
			}
		}

		global $wpdb;
		$table     = $post_type;
		$cache_key = 'acf_' . $post_type . "_{$post_id}";
		$acf_data  = wp_cache_get( $cache_key );

		if ( $acf_data === false ) {
			$row      = $wpdb->get_var( $wpdb->prepare( "SELECT data FROM $table WHERE post_id = %d", $post_id ) );
			$acf_data = $row ? json_decode( $row, true ) : array();
			wp_cache_set( $cache_key, $acf_data );
		}

		$name = $field['name'];
		return $acf_data[ $name ] ?? $null;
	}
}

<?php
/**
 * Required plugins manager.
 * Ensures that declared required plugins are installed and activated.
 * Runs only in wp-admin to avoid any frontend performance impact.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class Required_Plugins
 *
 * @package App\Components\Base
 */
class Required_Plugins implements Theme_Component {

	/**
	 * Transient key used to throttle install checks.
	 * Check runs at most once per hour.
	 */
	const CHECK_TRANSIENT = 'shopperexpress_required_plugins_checked';

	/**
	 * Plugins that must be present and active.
	 * Each entry: plugin_file => plugin_slug (WordPress.org slug for installation).
	 *
	 * @var array<string, string>
	 */
	private array $required = array(
		'user-role-editor/user-role-editor.php' => 'user-role-editor',
	);

	/**
	 * Register hooks — admin only, no frontend cost.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_init', array( $this, 'maybe_run_check' ) );
		add_action( 'admin_notices', array( $this, 'render_notices' ) );
		add_action( 'network_admin_notices', array( $this, 'render_notices' ) );
	}

	/**
	 * Run the check at most once per hour via a transient.
	 * Skips silently when the filesystem is restricted.
	 *
	 * @return void
	 */
	public function maybe_run_check(): void {
		// Only administrators.
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		// Throttle: run at most once per hour.
		if ( get_transient( self::CHECK_TRANSIENT ) ) {
			return;
		}

		set_transient( self::CHECK_TRANSIENT, 1, HOUR_IN_SECONDS );

		foreach ( $this->required as $plugin_file => $slug ) {
			$this->ensure_plugin( $plugin_file, $slug );
		}
	}

	/**
	 * Ensure a single plugin is installed and active.
	 *
	 * @param string $plugin_file Relative path: folder/file.php.
	 * @param string $slug        WordPress.org plugin slug.
	 * @return void
	 */
	private function ensure_plugin( string $plugin_file, string $slug ): void {
		if ( $this->is_active( $plugin_file ) ) {
			return;
		}

		if ( ! $this->is_installed( $plugin_file ) ) {
			$installed = $this->install_plugin( $slug );

			if ( ! $installed ) {
				// Installation failed — admin notice will be rendered.
				return;
			}
		}

		$this->activate_plugin( $plugin_file );
	}

	/**
	 * Check whether the plugin is network-active or site-active.
	 *
	 * @param string $plugin_file Relative plugin path.
	 * @return bool
	 */
	private function is_active( string $plugin_file ): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $plugin_file )
			|| ( is_multisite() && is_plugin_active_for_network( $plugin_file ) );
	}

	/**
	 * Check whether the plugin file exists on disk.
	 *
	 * @param string $plugin_file Relative plugin path.
	 * @return bool
	 */
	private function is_installed( string $plugin_file ): bool {
		return file_exists( WP_PLUGIN_DIR . '/' . $plugin_file );
	}

	/**
	 * Install a plugin from WordPress.org using Plugin_Upgrader.
	 * Returns false when the filesystem is restricted or the request fails.
	 *
	 * @param string $slug WordPress.org plugin slug.
	 * @return bool
	 */
	private function install_plugin( string $slug ): bool {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		// Verify filesystem access before attempting anything.
		$filesystem_ok = \WP_Filesystem();
		if ( ! $filesystem_ok ) {
			$this->store_notice(
				$slug,
				sprintf(
					/* translators: %s: plugin slug */
					__( 'Shopperexpress: Cannot install required plugin "%s" — filesystem credentials required. Please install it manually.', 'shopperexpress' ),
					$slug
				),
				'warning'
			);
			return false;
		}

		// Fetch plugin info from the WordPress.org API.
		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => $slug,
				'fields' => array(
					'short_description' => false,
					'sections'          => false,
					'requires'          => false,
					'rating'            => false,
					'ratings'           => false,
					'downloaded'        => false,
					'last_updated'      => false,
					'added'             => false,
					'tags'              => false,
					'compatibility'     => false,
					'homepage'          => false,
					'donate_link'       => false,
				),
			)
		);

		if ( is_wp_error( $api ) ) {
			$this->store_notice(
				$slug,
				sprintf(
					/* translators: 1: plugin slug, 2: error message */
					__( 'Shopperexpress: Could not fetch plugin info for "%1$s": %2$s', 'shopperexpress' ),
					$slug,
					$api->get_error_message()
				),
				'error'
			);
			return false;
		}

		// Use a silent skin so nothing is echoed to the page.
		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $api->download_link );

		if ( is_wp_error( $result ) || is_wp_error( $skin->result ) || false === $result ) {
			$error_message = is_wp_error( $result )
				? $result->get_error_message()
				: __( 'Unknown error', 'shopperexpress' );

			$this->store_notice(
				$slug,
				sprintf(
					/* translators: 1: plugin slug, 2: error message */
					__( 'Shopperexpress: Installation of required plugin "%1$s" failed: %2$s', 'shopperexpress' ),
					$slug,
					$error_message
				),
				'error'
			);
			return false;
		}

		return true;
	}

	/**
	 * Activate a plugin.
	 * Stores an admin notice on failure; logs with error_log() for server-side visibility.
	 *
	 * @param string $plugin_file Relative plugin path.
	 * @return void
	 */
	private function activate_plugin( string $plugin_file ): void {
		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// On multisite, network-activate when the current user has that capability.
		$network_wide = is_multisite() && current_user_can( 'manage_network_plugins' );
		$result       = activate_plugin( $plugin_file, '', $network_wide );

		if ( is_wp_error( $result ) ) {
			$message = sprintf(
				/* translators: 1: plugin file, 2: error message */
				__( 'Shopperexpress: Activation of required plugin "%1$s" failed: %2$s', 'shopperexpress' ),
				$plugin_file,
				$result->get_error_message()
			);

			error_log( '[Shopperexpress] ' . $message );

			$this->store_notice( $plugin_file, $message, 'error' );
		}
	}

	/**
	 * Persist a notice in a site option so it survives the redirect
	 * that follows activation/installation.
	 *
	 * @param string $key     Unique key (slug or plugin file).
	 * @param string $message Human-readable message.
	 * @param string $type    'error' | 'warning' | 'success'.
	 * @return void
	 */
	private function store_notice( string $key, string $message, string $type = 'error' ): void {
		$notices         = get_option( 'shopperexpress_plugin_notices', array() );
		$notices[ $key ] = array(
			'message' => $message,
			'type'    => $type,
		);
		update_option( 'shopperexpress_plugin_notices', $notices, false );
	}

	/**
	 * Render any stored admin notices and clear them afterwards.
	 *
	 * @return void
	 */
	public function render_notices(): void {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		$notices = get_option( 'shopperexpress_plugin_notices', array() );

		if ( empty( $notices ) ) {
			return;
		}

		foreach ( $notices as $notice ) {
			$type    = in_array( $notice['type'], array( 'error', 'warning', 'success', 'info' ), true )
				? $notice['type']
				: 'error';
			$message = wp_kses_post( $notice['message'] );

			printf(
				'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
				esc_attr( $type ),
				$message
			);
		}

		// Clear after rendering — one-shot notices.
		delete_option( 'shopperexpress_plugin_notices' );
	}
}

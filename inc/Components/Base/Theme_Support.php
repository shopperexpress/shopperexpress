<?php
/**
 * Base theme support class.
 * Add theme support for some default WordPress options.
 *
 * @package ThemeName
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class Theme_Support
 *
 * @package App\Components\Base
 */
class Theme_Support implements Theme_Component {

	/**
	 * Adds required the action and filter hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'after_setup_theme', array( $this, 'defaults_support' ) );
		add_action( 'after_setup_theme', array( $this, 'content_width' ), 0 );
		add_action( 'after_setup_theme', array( $this, 'backup_images' ) );
		add_action( 'themecheck_checks_loaded', array( $this, 'disable_cheks' ) );
		add_action( 'admin_init', array( $this, 'options_capability' ) );
		// add_filter( 'nav_menu_css_class', [ $this, 'change_menu_classes' ], 10, 3 );
		add_action( 'save_post_page', array( $this, 'page_comment_status' ), 10, 3 );
		add_filter( 'the_password_form', array( $this, 'get_the_password_form' ) );

		/**
		 * Remove generator meta tag.
		 * Hide WordPress virsion
		 */
		remove_action( 'wp_head', 'wp_generator' );

		/**
		 * Allow tags in category description
		 */
		$this->remove_filters();
	}

	/**
	 * Add theme support for some default things.
	 *
	 * @return void
	 */
	public function defaults_support(): void {
		/**
		 * Automatic feed links theme support
		 *
		 * @link https://codex.wordpress.org/Automatic_Feed_Links
		 */
		add_theme_support( 'automatic-feed-links' );

		/**
		 * Load the theme’s translated strings.
		 *
		 * @link https://developer.wordpress.org/reference/functions/load_theme_textdomain/
		 */
		load_theme_textdomain( 'base', get_template_directory() . '/languages' );

		/**
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 *
		 * @link https://codex.wordpress.org/Title_Tag
		 */
		add_theme_support( 'title-tag' );

		/**
		 * This feature enables Post Thumbnails support for a theme.
		 * Note that you can optionally pass a second argument, $args,
		 * with an array of the Post Types for which you want to enable this feature.
		 *
		 * @link https://developer.wordpress.org/reference/functions/add_theme_support/#post-thumbnails
		 */
		add_theme_support( 'post-thumbnails' );
	}

	/**
	 * Set the content width in pixels, based on the theme's design and stylesheet.
	 *
	 * @link https://codex.wordpress.org/Content_Width
	 * @global int $content_width
	 * @return void
	 */
	public function content_width(): void {
		$GLOBALS['content_width'] = 900;
	}

	/**
	 * Disable theme checks.
	 *
	 * @return void
	 */
	public function disable_cheks(): void {
		$disabled_checks = array( 'TagCheck', 'Plugin_Territory', 'CustomCheck', 'EditorStyleCheck' );

		global $themechecks;

		foreach ( $themechecks as $key => $check ) {
			if ( is_object( $check ) && in_array( get_class( $check ), $disabled_checks, true ) ) {
				unset( $themechecks[ $key ] );
			}
		}
	}

	/**
	 * Add theme opstions capability, for view theme options page.
	 *
	 * @return void
	 */
	public function options_capability(): void {
		$role = get_role( 'administrator' );
		$role->add_cap( 'theme_options_view' );
	}

	/**
	 * Chenge menu classes in the theme
	 *
	 * @param array  $classes css classes array.
	 * @param object $item WP_Post object.
	 * @param object $args stdClass arguments object.
	 * @return array
	 */
	public function change_menu_classes( array $classes, object $item, object $args ): array {
		$classes = str_replace( array( 'current-menu-item', 'current-menu-parent', 'current-menu-ancestor' ), 'active', $classes );

		return $classes;
	}

	/**
	 * Allow tags in category description
	 *
	 * @return void
	 */
	public function remove_filters(): void {
		$filters = array( 'pre_term_description', 'pre_link_description', 'pre_link_notes', 'pre_user_description' );

		foreach ( $filters as $filter ) {
			remove_filter( $filter, 'wp_filter_kses' );
		}
	}

	/**
	 * Disable comments on pages by default
	 *
	 * @param  int    $post_ID the post id.
	 * @param  object $post post object.
	 * @param  bool   $update update boolean parametr.
	 * @return void
	 */
	public function page_comment_status( int $post_ID, object $post, bool $update ): void {
		if ( ! $update ) {
			remove_action( 'save_post_page', 'page_comment_status', 10 );
			wp_update_post(
				array(
					'ID'             => $post->ID,
					'comment_status' => 'closed',
				)
			);

			add_action( 'save_post_page', array( $this, 'page_comment_status' ), 10, 3 );
		}
	}

	/**
	 * Theme password form.
	 *
	 * @return string
	 */
	public function get_the_password_form(): string {
		global $post;

		$post    = get_post( $post ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$label   = esc_attr( 'pwbox-' . ( empty( $post->ID ) ? wp_rand() : $post->ID ) );
		$output  = '<form action="' . esc_url( site_url( 'wp-login.php?action=postpass', 'login_post' ) ) . '" class="post-password-form" method="post">';
		$output .= '<p>' . esc_html__( 'This content is password protected. To view it please enter your password below:', 'base' ) . '</p>';
		$output .= '<p><label for="' . esc_attr( $label ) . '">' . esc_html__( 'Password:', 'base' ) . '</label>';
		$output .= '<input name="post_password" id="' . esc_attr( $label ) . '" type="password" size="20" />';
		$output .= '<input type="submit" name="Submit" value="' . esc_html__( 'Submit', 'base' ) . '" /></p></form>';

		return $output;
	}

	/**
	 * Backup images.
	 *
	 * @return void
	 */
	public function backup_images(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'image_backup';

		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
        vin VARCHAR(64) NOT NULL,
        images LONGTEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (vin)
    ) {$charset_collate};";

		dbDelta( $sql );
	}
}

<?php
/**
 * Main theme functions file.
 * Here you can find some default checks and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Shopperexpress
 */

$theme_slug = 'shopperexpress';
$backup_dir = WP_CONTENT_DIR . '/themes/' . $theme_slug . '_vendor_backup';

add_filter(
	'upgrader_pre_install',
	function ( $return, $hook_extra, $upgrader = null ) use ( $theme_slug, $backup_dir ) {
		if ( isset( $hook_extra['theme'] ) && $hook_extra['theme'] === $theme_slug ) {
			$theme_dir = WP_CONTENT_DIR . '/themes/' . $theme_slug;
			if ( file_exists( $theme_dir . '/vendor' ) ) {
				if ( file_exists( $backup_dir ) ) {
					function rrmdir( $dir ) {
						if ( is_dir( $dir ) ) {
							$objects = scandir( $dir );
							foreach ( $objects as $object ) {
								if ( $object != '.' && $object != '..' ) {
									if ( is_dir( $dir . '/' . $object ) && ! is_link( $dir . '/' . $object ) ) {
										rrmdir( $dir . '/' . $object );
									} else {
										unlink( $dir . '/' . $object );
									}
								}
							}
							rmdir( $dir );
						}
					}
					rrmdir( $backup_dir );
				}
				rename( $theme_dir . '/vendor', $backup_dir );
			}
		}
		return $return;
	},
	10,
	3
);

add_filter(
	'upgrader_post_install',
	function ( $response, $hook_extra, $result = null ) use ( $theme_slug, $backup_dir ) {
		if ( isset( $hook_extra['theme'] ) && $hook_extra['theme'] === $theme_slug ) {
			$theme_dir = WP_CONTENT_DIR . '/themes/' . $theme_slug;

			$new_theme_dir = $result['destination'] ?? $theme_dir;

			if ( $new_theme_dir !== $theme_dir ) {
				if ( file_exists( $theme_dir ) ) {
					rrmdir( $theme_dir );
				}
				rename( $new_theme_dir, $theme_dir );
			}

			if ( file_exists( $backup_dir ) ) {
				rename( $backup_dir, $theme_dir . '/vendor' );
			}
		}
		return $response;
	},
	10,
	3
);

add_filter(
	'pre_set_site_transient_update_themes',
	function ( $transient ) use ( $theme_slug ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$current_version = $transient->checked[ $theme_slug ];
		$repo            = 'shopperexpress/shopperexpress';
		$response        = wp_remote_get( "https://api.github.com/repos/$repo/releases/latest" );

		if ( is_wp_error( $response ) ) {
			return $transient;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( ! $body || empty( $body->tag_name ) ) {
			return $transient;
		}

		$new_version = ltrim( $body->tag_name, 'v' );
		$zip_url     = $body->zipball_url;

		if ( version_compare( $current_version, $new_version, '<' ) ) {
			$transient->response[ $theme_slug ] = array(
				'theme'       => $theme_slug,
				'new_version' => $new_version,
				'package'     => $zip_url,
				'url'         => "https://github.com/$repo",
			);
		}

		return $transient;
	}
);
/**
 * Check PHP version.
 */
if ( version_compare( phpversion(), '7.2.5', '<' ) ) {
	add_action(
		'admin_notices',
		function () {
			?>
		<div class="notice notice-error">
			<p>
				<?php
				echo wp_kses(
					__( 'The minimum version of PHP is <strong>7.2</strong>. Please update the PHP version on your server and try again.', 'base' ),
					array(
						'strong' => array(),
					)
				);
				?>
			</p>
		</div>
			<?php
		}
	);

	return;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader
| for our application. We just need to utilize it! We'll require it
| into the script here so that we do not have to worry about the
| loading of any our classes "manually". Feels great to relax.
|
*/
$composer = get_template_directory() . '/vendor/autoload.php';

if ( ! file_exists( $composer ) ) {
	wp_die( wp_kses_post( __( 'Error. Composer autoloader required. Please run <code>composer install</code>.', 'base' ) ) );
}

require $composer;

/**
 * In case if ACF plugin is not installed, require acf fallback file.
 */
if ( ! class_exists( 'ACF' ) && ! is_admin() ) {
	require_once __DIR__ . '/inc/acf-fallback.php';
}

/**
 * Loop throught theme required files.
 */
array_map(
	function ( $file ) {
		$file = "./inc/{$file}.php";

		if ( ! locate_template( $file, true, true ) ) {
			wp_die(
				wp_kses_post(
					sprintf(
					// translators: %s file name.
						__( 'Error locating <code>%s</code> file.', 'base' ),
						$file
					)
				),
				'File not found'
			);
		}
	},
	array( 'helpers', 'theme-functions', 'ConversionBlock', 'Walker_Nav', 'Widget', 'Evox', 'Twilio' )
);

/**
 * Init theme classes using App\injector() helper.
 */
if ( class_exists( 'App\\Components\\Theme' ) ) {
	try {
		$theme = \App\injector()->make( \App\Components\Theme::class );
		$theme->register_components();
	} catch ( \Auryn\InjectionException | \Auryn\ConfigException $e ) {
		echo esc_html(
			sprintf(
				// translators: %s exception message.
				__( 'Exception caught: %s', 'base' ),
				$e->getMessage()
			)
		);
	}
}

flush_rewrite_rules();

add_action(
	'wp',
	function () {
		global $wp_query;

		if ( ! get_the_id() && ! is_archive() && ! is_admin() && ! empty( $wp_query->query['post_type'] ) ) {
			header( 'HTTP/1.1 301 Moved Permanently' );
			header( 'Location: /' . $wp_query->query['post_type'] );
		}
	}
);

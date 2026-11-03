<?php
/**
 * What the theme supports.
 *
 * @package shopperexpress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Declares the theme's features to WordPress.
 *
 * `add_editor_style()` loads the compiled stylesheet inside the block editor, so the WordPress
 * reset and core block styles the build produces apply there as well as on the front end.
 */
function shopperexpress_setup() {
	load_theme_textdomain( 'shopperexpress', get_theme_file_path( 'languages' ) );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	// Featured images. Register any extra sizes the templates need here, with add_image_size() —
	// but sparingly: WordPress generates every registered size for every upload from then on,
	// and existing uploads need `ddev wp media regenerate` to catch up.
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	// header.php renders the logo when one is set; the Customizer offers the control only for a
	// theme that declares this.
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 100,
			'width'       => 300,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	// No 'style' or 'script': WordPress builds those two tags without a `type` attribute for
	// every theme, so naming them here asks for something it already does.
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' )
	);

	// A path below the theme root and nothing more: get_editor_stylesheets() resolves it with
	// file_exists(), which a query string makes false — a versioned path is dropped silently.
	add_editor_style( 'assets/css/style.css' );
}
add_action( 'after_setup_theme', 'shopperexpress_setup' );

/**
 * Upper bound WordPress applies to embedded and uploaded media. Priority 0 runs it before the
 * theme support above, which is what core's own themes do.
 */
function shopperexpress_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'shopperexpress_content_width', 1200 );
}
add_action( 'after_setup_theme', 'shopperexpress_content_width', 0 );

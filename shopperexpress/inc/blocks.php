<?php
/**
 * Block registration.
 *
 * @package shopperexpress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Every block.json in the theme, a child theme's copy of a block winning over the parent's.
 *
 * The two directories are searched separately and the results keyed by the block's folder
 * name. get_theme_file_path() cannot do it: it chooses between child and parent by asking
 * file_exists() about the child's path, which is always false for a glob pattern.
 *
 * @return string[] Paths to block.json files.
 */
function shopperexpress_block_metadata_files() {
	$found = array();

	foreach ( array( get_template_directory(), get_stylesheet_directory() ) as $dir ) {
		$files = glob( "$dir/blocks/*/block.json" );

		foreach ( $files ? $files : array() as $file ) {
			$found[ basename( dirname( $file ) ) ] = $file;
		}
	}

	return array_values( $found );
}

/**
 * Versions a block's assets by what the build produced.
 *
 * WordPress takes the `?ver=` for a block's stylesheet and view script from the `version` in
 * block.json whenever no .asset.php supplies one, and the build writes an .asset.php only
 * beside an editor script — so those two would carry whatever literal that file holds,
 * unchanged from one deploy to the next. The newest mtime under build/ moves with every build.
 *
 * Reached for every registered block, core's included; those have no build/ beside them.
 *
 * @param array $metadata Block metadata read from block.json.
 * @return array
 */
function shopperexpress_block_asset_version( $metadata ) {
	$file = isset( $metadata['file'] ) && is_string( $metadata['file'] ) ? $metadata['file'] : '';

	if ( '' === $file ) {
		return $metadata;
	}

	$build = dirname( $file ) . '/build';

	if ( ! is_dir( $build ) ) {
		return $metadata;
	}

	$styles  = glob( "$build/*.css" );
	$scripts = glob( "$build/*.js" );
	$assets  = array_merge( $styles ? $styles : array(), $scripts ? $scripts : array() );
	// filemtime() answers false for a file that vanished between the glob and the stat.
	$times = array_filter( array_map( 'filemtime', $assets ) );

	if ( $times ) {
		$metadata['version'] = (string) max( $times );
	}

	return $metadata;
}
add_filter( 'block_type_metadata', 'shopperexpress_block_asset_version' );

/**
 * Registers the build's block metadata with WordPress.
 *
 * Without this, register_block_type() opens and parses every block.json on every request that
 * registers a block. The build writes them all into one manifest instead, which WordPress
 * reads once — and which opcache then keeps compiled, so repeat requests parse nothing.
 *
 * Registration still happens per block below: a collection supplies the metadata, it does not
 * register anything. A theme whose build has not run has no manifest, and the loop reads the
 * block.json files as before.
 */
function shopperexpress_register_block_collections() {
	// One directory unless a child theme is active, and then the child's blocks are their own
	// collection — each manifest describes only the blocks beside it.
	$directories = array_unique( array( get_template_directory(), get_stylesheet_directory() ) );

	foreach ( $directories as $dir ) {
		$manifest = "$dir/blocks/blocks-manifest.php";

		if ( file_exists( $manifest ) ) {
			wp_register_block_metadata_collection( "$dir/blocks", $manifest );
		}
	}
}

/**
 * Registers every block the build emitted.
 *
 * A block is a directory under blocks/ carrying a block.json. WordPress reads the metadata for
 * the render callback and the style and script handles, and enqueues those only on requests
 * where the block is actually present — which is why a block's assets are built as their own
 * entries, separate from the theme bundle.
 *
 * Adding a block means adding a directory under blocks/ at the theme root. Nothing here needs
 * to change.
 */
function shopperexpress_register_blocks() {
	shopperexpress_register_block_collections();

	foreach ( shopperexpress_block_metadata_files() as $metadata_file ) {
		register_block_type( dirname( $metadata_file ) );
	}

	shopperexpress_block_view_script_dependencies();
}
add_action( 'init', 'shopperexpress_register_blocks' );

/**
 * Makes the theme bundle a dependency of every block view script.
 *
 * The view scripts call jQuery and its plugins (slick, jcf), window.ajax and window.Handlebars
 * but import none of them: the bundle registers them once, and the build records no dependency
 * on the bundle for a script that only reaches it as a global. Without this the order of the
 * scripts would depend on the order they happen to be enqueued in.
 */
function shopperexpress_block_view_script_dependencies() {
	$scripts = wp_scripts();
	$roots   = array_unique( array( get_template_directory_uri() . '/blocks/', get_stylesheet_directory_uri() . '/blocks/' ) );

	foreach ( WP_Block_Type_Registry::get_instance()->get_all_registered() as $block ) {
		foreach ( $block->view_script_handles as $handle ) {
			$script = isset( $scripts->registered[ $handle ] ) ? $scripts->registered[ $handle ] : null;

			if ( ! $script || ! is_string( $script->src ) ) {
				continue;
			}

			foreach ( $roots as $root ) {
				if ( 0 === strpos( $script->src, $root ) && ! in_array( 'shopperexpress', $script->deps, true ) ) {
					$script->deps[] = 'shopperexpress';
				}
			}
		}
	}
}

/**
 * Reports blocks that cannot render.
 *
 * A block whose block.json carries an `acf` key is rendered by Advanced Custom Fields. Without
 * the plugin WordPress still registers it from the metadata, so it appears in the inserter and
 * then renders nothing — which reads as a broken theme rather than a missing dependency.
 */
function shopperexpress_missing_acf_notice() {
	if ( class_exists( 'ACF' ) ) {
		return;
	}

	// Only someone who can install the plugin can act on the notice.
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	// ACF themes ship their field groups in acf-json/ beside the blocks; a theme without it
	// has no ACF blocks to warn about, and the per-block metadata scan below runs on every
	// admin page view.
	if ( ! is_dir( get_theme_file_path( 'acf-json' ) ) ) {
		return;
	}

	foreach ( shopperexpress_block_metadata_files() as $metadata_file ) {
		// WordPress's own reader: it caches, and it does not trip the filesystem sniffs that
		// file_get_contents() does.
		$metadata = wp_json_file_decode( $metadata_file, array( 'associative' => true ) );

		if ( ! isset( $metadata['acf'] ) ) {
			continue;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'This theme\'s blocks are built with Advanced Custom Fields, which is not active. Install and activate ACF Pro for them to render.', 'shopperexpress' )
		);

		return;
	}
}
add_action( 'admin_notices', 'shopperexpress_missing_acf_notice' );

/**
 * Groups the theme's own blocks under one inserter category, ahead of the core ones.
 *
 * @param array $categories Registered block categories.
 * @return array
 */
function shopperexpress_block_category( $categories ) {
	array_unshift(
		$categories,
		array(
			'slug'  => 'shopperexpress',
			'title' => esc_html__( 'Theme blocks', 'shopperexpress' ),
		)
	);

	return $categories;
}
add_filter( 'block_categories_all', 'shopperexpress_block_category' );

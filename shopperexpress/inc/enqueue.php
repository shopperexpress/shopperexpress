<?php
/**
 * Front-end asset registration.
 *
 * @package shopperexpress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cache-busting version for a built asset.
 *
 * The build overwrites files in place — names carry no fingerprint — so the mtime is what
 * distinguishes one build from the next. Falls back to the theme version for a file the
 * build has not produced yet.
 *
 * @param string $relative_path Path below the theme root.
 * @return string
 */
function shopperexpress_asset_version( $relative_path ) {
	$file = get_theme_file_path( $relative_path );

	if ( ! file_exists( $file ) ) {
		return (string) wp_get_theme()->get( 'Version' );
	}

	return (string) filemtime( $file );
}

/**
 * Script handles the bundle depends on.
 *
 * The build writes an .asset.php beside each script naming the WordPress packages it imports.
 * jQuery is added unconditionally: webpack treats `jquery` as an external resolved from the
 * global scope, so the build records no dependency on it, and in a theme that global is the
 * copy WordPress ships.
 *
 * @param string $relative_path Path to the script, below the theme root.
 * @return array
 */
function shopperexpress_asset_dependencies( $relative_path ) {
	$asset_file = get_theme_file_path( preg_replace( '/\.js$/', '.asset.php', $relative_path ) );

	$declared = file_exists( $asset_file ) ? require $asset_file : array();

	return array_merge( array( 'jquery' ), isset( $declared['dependencies'] ) ? $declared['dependencies'] : array() );
}

/**
 * Registers the theme's stylesheet and bundle.
 *
 * The style.css at the theme root carries only the header WordPress identifies the theme by; the
 * compiled stylesheet is a build artefact under assets/, which is what actually has to be
 * enqueued. Both paths are fixed by config.json and covered by the generator's test suite;
 * nothing at runtime makes them visible from here.
 */
function shopperexpress_enqueue_assets() {
	wp_enqueue_style(
		'shopperexpress',
		get_theme_file_uri( 'assets/css/style.css' ),
		array(),
		shopperexpress_asset_version( 'assets/css/style.css' )
	);

	wp_enqueue_script(
		'shopperexpress',
		get_theme_file_uri( 'assets/js/app.js' ),
		shopperexpress_asset_dependencies( 'assets/js/app.js' ),
		shopperexpress_asset_version( 'assets/js/app.js' ),
		array(
			'in_footer' => false,
			'strategy'  => 'defer',
		)
	);

	// The asc-* tracking scripts and impl.js from the old theme (src/js/legacy/). In the footer, as
	// they were before: they read window.asc_datalayer and ascOpenAiAdsConfig, which wp_head prints.
	wp_enqueue_script(
		'shopperexpress/legacy',
		get_theme_file_uri( 'assets/js/legacy.js' ),
		shopperexpress_asset_dependencies( 'assets/js/legacy.js' ),
		shopperexpress_asset_version( 'assets/js/legacy.js' ),
		true
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'shopperexpress_enqueue_assets' );

/**
 * Enqueues the Tabs Slider block's own style/view script on single templates that render
 * the legacy template-parts/model-slider.php part directly (single-listings(-api), single-research,
 * single-offers, single-conditional-offers, single-service-offers).
 *
 * Those templates call get_template_part( 'template-parts/model', 'slider', ... ) outside the
 * block system entirely, so register_block_type()'s auto-enqueue (and shopperexpress_enqueue_flexible_block_assets()
 * above, which only covers pages/template-flexible.php) never fires for them. This reaches the
 * same build/style.css + build/view.js the acf/tabs-slider block declares in block.json, by handle,
 * instead of relying on the global app.js bundle for this markup.
 *
 * Runs on wp_enqueue_scripts, before get_header() prints the <head>, so the style lands in time.
 */
function shopperexpress_enqueue_model_slider_assets() {
	$post_types = array( 'listings', 'used-listings', 'research', 'offers', 'conditional-offers', 'service-offers' );

	if ( ! is_singular( $post_types ) ) {
		return;
	}

	if ( ! have_rows( 'slider', 'options' ) ) {
		return;
	}

	$block = WP_Block_Type_Registry::get_instance()->get_registered( 'acf/tabs-slider' );

	if ( ! $block ) {
		return;
	}

	foreach ( $block->style_handles as $handle ) {
		wp_enqueue_style( $handle );
	}

	foreach ( $block->view_script_handles as $handle ) {
		wp_enqueue_script( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'shopperexpress_enqueue_model_slider_assets' );

/**
 * Enqueues a block's assets for the flexible content layout that renders it.
 *
 * The flexible page template (pages/template-flexible.php) renders each "content_blocks" row
 * through a plain template part, never through render_block() — so WordPress never sees the
 * block in post content and never auto-enqueues the style and view script register_block_type()
 * registered for it from block.json. This reaches the same handles that auto-enqueue would have
 * used, by name, for every layout actually present on the page.
 *
 * Runs on wp_enqueue_scripts, before get_header() prints the <head>, so a style enqueued here
 * still lands before wp_head() rather than after the loop below it has already run.
 */
function shopperexpress_enqueue_flexible_block_assets() {
	if ( ! is_page_template( 'pages/template-flexible.php' ) ) {
		return;
	}

	$rows = get_field( 'content_blocks' );

	if ( ! $rows ) {
		return;
	}

	$registry = WP_Block_Type_Registry::get_instance();
	$layouts  = array_unique( shopperexpress_collect_flexible_layouts( $rows ) );

	foreach ( $layouts as $layout ) {
		$block = $registry->get_registered( 'acf/' . shopperexpress_flexible_layout_block( $layout ) );

		if ( ! $block ) {
			continue;
		}

		foreach ( $block->style_handles as $handle ) {
			wp_enqueue_style( $handle );
		}

		foreach ( $block->view_script_handles as $handle ) {
			wp_enqueue_script( $handle );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'shopperexpress_enqueue_flexible_block_assets' );

/**
 * Every layout name in a flexible content value, nested rows included.
 *
 * A layout can carry flexible content of its own (intro_section renders its "blocks" sub-rows
 * through further template parts), and those rows need their block's assets as much as the
 * top-level ones.
 *
 * @param array $rows Flexible content rows as get_field() returns them.
 * @return string[]
 */
function shopperexpress_collect_flexible_layouts( $rows ) {
	$layouts = array();

	foreach ( $rows as $key => $value ) {
		if ( 'acf_fc_layout' === $key && is_string( $value ) && $value ) {
			$layouts[] = $value;
		} elseif ( is_array( $value ) ) {
			$layouts = array_merge( $layouts, shopperexpress_collect_flexible_layouts( $value ) );
		}
	}

	return $layouts;
}

/**
 * The block directory under blocks/ that renders a flexible content layout.
 *
 * Layout names predate the blocks: most differ only in using underscores where the block uses
 * hyphens, and the rest are named differently altogether. The template part each layout renders
 * (template-parts/acf/acf-<layout>.php) is what links them.
 *
 * @param string $layout Flexible content layout name.
 * @return string
 */
function shopperexpress_flexible_layout_block( $layout ) {
	$aliases = array(
		'blocks'                    => 'intro-blocks',
		'full_width_image_section'  => 'full-width-image',
		'html'                      => 'html-block',
		'image_left_and_text_right' => 'image-left-text-right',
		'intro_section'             => 'video-section',
		'sub_footer_section'        => 'sub-footer',
		'text_and_video_slider'     => 'text-video-slider',
		'text_left_and_image_right' => 'text-left-image-right',
		'video'                     => 'video-section',
		'video_section'             => 'video-section',
	);

	return isset( $aliases[ $layout ] ) ? $aliases[ $layout ] : str_replace( '_', '-', $layout );
}

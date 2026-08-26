<?php
/**
 * ACF Block preview helper.
 *
 * @package ShopperExpress
 */

namespace App\Components\Gutenberg;

/**
 * Renders the block inserter preview and returns true so the caller can bail early.
 *
 * Usage inside any acf-blocks/ template:
 *
 *   if ( Block_Preview_Helper::render( $block ) ) { return; }
 *
 * ACF sets $block['data']['preview_image_help'] when the block inserter thumbnail
 * is being generated — that is the only signal we need for that case.
 *
 * Pass $force = true to also show the same image as a static editor-canvas
 * placeholder for the whole time the block is being edited — the block always
 * shows its preview image in the editor and only renders live markup on the
 * frontend, so editing never flickers between placeholder and partial content:
 *
 *   if ( $is_preview ) { Block_Preview_Helper::render( $block, true ); return; }
 */
class Block_Preview_Helper {

	/**
	 * Render the block preview image (inserter thumbnail, or forced editor placeholder).
	 *
	 * @param  array $block ACF $block array passed to the render callback.
	 * @param  bool  $force Render the placeholder even when ACF isn't requesting the inserter thumbnail.
	 * @return bool  True when preview was rendered (caller must return), false otherwise.
	 */
	public static function render( array $block, bool $force = false ): bool {
		if ( ! $force && empty( $block['data']['preview_image_help'] ) ) {
			return false;
		}

		$slug     = self::slug( $block );
		$relative = 'assets/images/block-previews/' . $slug . '.jpg';
		$abs      = get_theme_file_path( $relative );
		$title    = $block['title'] ?? $slug;

		if ( ! file_exists( $abs ) ) {
			$relative = 'assets/images/block-previews/default-preview.svg';
		}

		printf(
			'<img src="%s" style="width:100%%;height:auto;" alt="%s" />',
			esc_url( get_theme_file_uri( $relative ) ),
			esc_attr( $title . ' preview' )
		);

		return true;
	}

	/**
	 * Derive the file-system slug from the block name (e.g. "acf/block-logo" → "block-logo").
	 *
	 * @param  array $block ACF block array.
	 * @return string
	 */
	private static function slug( array $block ): string {
		return ltrim( str_replace( 'acf/', '', $block['name'] ?? '' ), '/' );
	}
}

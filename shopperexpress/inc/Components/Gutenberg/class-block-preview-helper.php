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
 * is being generated — that is the only signal we need; $is_preview is not
 * available in the include scope of acf_render_callback.
 */
class Block_Preview_Helper {

	/**
	 * Render the block preview if ACF is requesting the inserter thumbnail.
	 *
	 * @param  array $block ACF $block array passed to the render callback.
	 * @return bool  True when preview was rendered (caller must return), false otherwise.
	 */
	public static function render( array $block ): bool {
		if ( empty( $block['data']['preview_image_help'] ) ) {
			return false;
		}

		$slug     = self::slug( $block );
		$relative = 'assets/images/block-previews/' . $slug . '.jpg';
		$abs      = get_theme_file_path( $relative );
		$title    = $block['title'] ?? $slug;

		if ( file_exists( $abs ) ) {
			printf(
				'<img src="%s" style="width:100%%;height:auto;" alt="%s" />',
				esc_url( get_theme_file_uri( $relative ) ),
				esc_attr( $title . ' preview' )
			);
		} else {
			printf(
				'<div style="background:#f6f7f7;border:2px dashed #c3c4c7;padding:32px 20px;text-align:center;font-family:-apple-system,sans-serif;border-radius:4px;">' .
				'<strong style="display:block;font-size:14px;margin-bottom:4px;">%s</strong>' .
				'<em style="font-size:12px;color:#888;">Block preview — add %s to customise</em>' .
				'</div>',
				esc_html( $title ),
				esc_html( $relative )
			);
		}

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

<?php
/**
 * Render shortcodes in the Yoast SEO title / meta description preview (admin).
 *
 * On the front end `wpseo_title` / `wpseo_metadesc` are run through `do_shortcode()`
 * (see inc/theme-filters.php), but Yoast's editor metabox builds its snippet preview
 * in JavaScript from the raw field value, so editors saw the literal `[shortcode]`.
 * This resolves them server-side through a small AJAX endpoint and feeds the result
 * into Yoast's preview modifications. Stored values are never changed.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class Yoast_Admin_Shortcodes
 *
 * @package App\Components\Base
 */
class Yoast_Admin_Shortcodes implements Theme_Component {

	const ACTION = 'shopperexpress_preview_shortcodes';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'ajax_render' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ), 20 );
	}

	/**
	 * Enqueue the preview script on post edit screens when Yoast is active.
	 *
	 * @param string $hook Admin page hook.
	 * @return void
	 */
	public function enqueue( $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! defined( 'WPSEO_VERSION' ) ) {
			return;
		}

		$handle = 'shopperexpress-yoast-shortcodes';
		wp_register_script( $handle, '', array(), (string) time(), true );
		wp_enqueue_script( $handle );
		wp_add_inline_script(
			$handle,
			'window.shopperexpressYoastSC = ' . wp_json_encode(
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'action'  => self::ACTION,
					'nonce'   => wp_create_nonce( self::ACTION ),
					'postId'  => (int) get_the_ID(),
				)
			) . ';' . $this->script()
		);
	}

	/**
	 * AJAX: run do_shortcode() over the given strings in the context of a post.
	 *
	 * @return void
	 */
	public function ajax_render(): void {
		check_ajax_referer( self::ACTION, 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( ! current_user_can( 'edit_posts' ) || ( $post_id && ! current_user_can( 'edit_post', $post_id ) ) ) {
			wp_send_json_error( null, 403 );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per item below.
		$items = isset( $_POST['items'] ) && is_array( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : array();

		if ( $post_id ) {
			$GLOBALS['post'] = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			setup_postdata( $GLOBALS['post'] );
		}

		$out = array();
		foreach ( array_slice( $items, 0, 10 ) as $item ) {
			$item         = (string) $item;
			$out[ $item ] = wp_strip_all_tags( do_shortcode( $item ) );
		}

		wp_send_json_success( $out );
	}

	/**
	 * Inline JS.
	 *
	 * @return string
	 */
	private function script(): string {
		return <<<'JS'
(function () {
	var cfg = window.shopperexpressYoastSC, cache = {}, pending = {}, timer = null, NAME = 'ShopperShortcodes', tries = 0;
	if (!cfg) { return; }

	function flush() {
		var items = Object.keys(pending);
		pending = {};
		if (!items.length) { return; }
		var body = new URLSearchParams();
		body.append('action', cfg.action);
		body.append('nonce', cfg.nonce);
		body.append('post_id', cfg.postId);
		items.forEach(function (s) { body.append('items[]', s); });
		fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (!res || !res.success) { return; }
				Object.keys(res.data).forEach(function (k) { cache[k] = res.data[k]; });
				if (window.YoastSEO && YoastSEO.app && YoastSEO.app.pluginReloaded) {
					YoastSEO.app.pluginReloaded(NAME);
				}
			});
	}

	function modify(text) {
		if (typeof text !== 'string' || text.indexOf('[') === -1) { return text; }
		// Replace each shortcode-containing string with its rendered value (cached).
		if (Object.prototype.hasOwnProperty.call(cache, text)) { return cache[text]; }
		pending[text] = true;
		clearTimeout(timer);
		timer = setTimeout(flush, 400);
		return text;
	}

	function init() {
		if (!window.YoastSEO || !YoastSEO.app || !YoastSEO.app.registerPlugin) { if (++tries < 60) { setTimeout(init, 500); } return; }
		YoastSEO.app.registerPlugin(NAME, { status: 'ready' });
		['data_page_title', 'data_meta_desc', 'snippet_title', 'snippet_meta', 'title'].forEach(function (m) {
			YoastSEO.app.registerModification(m, modify, NAME, 20);
		});
	}
	init();
})();
JS;
	}
}

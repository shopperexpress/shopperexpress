<?php
/**
 * Theme related helper functions.
 *
 * @package Shopperexpress
 */

namespace App;

use Auryn\Injector;

/**
 * Get theme injector.
 *
 * @return Injector Auryn DI instance.
 */
function injector(): Injector {
	static $injector;

	if ( ! $injector ) {
		$injector = new Injector();
	}

	return $injector;
}

/**
 * Helper function. returns asset url
 *
 * @param  string $asset_path asset path.
 * @return string
 */
function asset_url( string $asset_path ): string {
	return get_template_directory_uri() . '/assets/dist/' . $asset_path;
}

/**
 * Helper function. returns asset url
 *
 * @param  string $asset_path asset path.
 * @return string
 */
function asset_url_old( string $asset_path ): string {
	return get_template_directory_uri() . '/assets/old/' . $asset_path;
}


/**
 * Returns true when the Intice API mode is active.
 *
 * Use this in templates to switch between WordPress data and the external API:
 *   if ( \App\is_api_mode() ) { ... }
 *
 * @return bool
 */
function is_api_mode(): bool {
	static $mode;

	if ( null === $mode ) {
		$mode = (bool) get_option(
			\App\Components\SOC\Modules\Api_Settings::OPTION_API_MODE,
			false
		);
	}

	return $mode;
}

/**
 * Resolve a vehicle's active image gallery straight from payload.use_images_list,
 * independent of Nexus's own (sometimes stale) active_image_list resolution.
 *
 * Nexus exposes both pre-normalized lists on every vehicle response —
 * images_primary / images_srp — each shaped as { url, is_background, is_reverse }.
 * We just pick between them ourselves using the raw import value instead of
 * trusting the server-resolved `images` field.
 *
 * @param array $vehicle Intice API vehicle object (list or single response).
 * @return array List of { url, is_background, is_reverse } items.
 */
function resolve_vehicle_gallery( array $vehicle ): array {
	$use = $vehicle['payload']['use_images_list'] ?? ( $vehicle['use_images_list'] ?? 'primary' );

	$list = in_array( $use, array( 'alternative', 'srp' ), true )
		? ( $vehicle['images_srp'] ?? array() )
		: ( $vehicle['images_primary'] ?? array() );

	if ( ! empty( $list ) ) {
		return $list;
	}

	// Fall back to Nexus's own resolved list, then a single thumb/image.
	if ( ! empty( $vehicle['images'] ) ) {
		return $vehicle['images'];
	}

	$fallback_url = $vehicle['image'] ?? ( $vehicle['thumb'] ?? '' );

	return $fallback_url ? array( array( 'url' => $fallback_url, 'is_background' => false, 'is_reverse' => false ) ) : array();
}

/**
 * Resolve a status badge's display text (with optional emoji prefix) and inline
 * color style, based on the `badge_color_rules` repeater (ACF, Options: Listings).
 *
 * Rules are matched case-insensitively as a substring of $text, in the order they
 * were entered — the first match wins. A rule's `show_on` choices (srp/vdp) and
 * `post_types` choices (listings/used-listings) can both restrict where it applies;
 * leaving either empty applies the rule everywhere. When nothing matches, the badge
 * falls back to its default CSS styling (empty style).
 *
 * @param string $text      Raw badge text, e.g. "In Stock", "Honda Certified".
 * @param string $context   'srp' or 'vdp', or '' to ignore each rule's `show_on`.
 * @param string $post_type 'listings' or 'used-listings', or '' to ignore each rule's `post_types`.
 * @return array{text: string, style: string}
 */
function resolve_badge_style( string $text, string $context = '', string $post_type = '' ): array {
	$text = trim( $text );

	if ( '' === $text ) {
		return array(
			'text'  => '',
			'style' => '',
		);
	}

	$rules = get_field( 'badge_color_rules', 'options' );

	if ( ! empty( $rules ) && is_array( $rules ) ) {
		foreach ( $rules as $rule ) {
			$match = trim( (string) ( $rule['match_text'] ?? '' ) );

			if ( '' === $match || false === stripos( $text, $match ) ) {
				continue;
			}

			$show_on = (array) ( $rule['show_on'] ?? array() );

			if ( ! empty( $show_on ) && $context && ! in_array( $context, $show_on, true ) ) {
				continue;
			}

			$post_types = (array) ( $rule['post_types'] ?? array() );

			if ( ! empty( $post_types ) && $post_type && ! in_array( $post_type, $post_types, true ) ) {
				continue;
			}

			$emoji = trim( (string) ( $rule['emoji'] ?? '' ) );
			$bg    = (string) ( $rule['bg_color'] ?? '' );
			$color = (string) ( $rule['text_color'] ?? '' );
			$style = '';

			if ( $bg ) {
				$style .= 'background-color:' . $bg . ';';
			}

			if ( $color ) {
				$style .= 'color:' . $color . ';';
			}

			return array(
				'text'  => $emoji ? $emoji . ' ' . $text : $text,
				'style' => $style,
			);
		}
	}

	return array(
		'text'  => $text,
		'style' => '',
	);
}

/**
 * Acf button helper.
 *
 * @param array  $button acf button array.
 * @param array  $classes array of button classes.
 * @param string $icon button icon html.
 */
function the_acf_button( array $button, array $classes = array(), string $icon = '' ) {
	$attributes  = ( $button['url'] ) ? ' href="' . $button['url'] . '"' : '';
	$attributes .= ( $button['target'] ) ? ' target="' . $button['target'] . '" rel="noreferrer"' : '';
	$attributes .= ( ! empty( $classes ) ) ? ' class="' . implode( ' ', $classes ) . '"' : '';

	$title = ( $button['title'] ) ? $button['title'] : '';

	$button = sprintf(
		'<a %s>%s %s</a>',
		$attributes,
		$title,
		$icon
	);

	echo wp_kses(
		$button,
		array(
			'a' => array(
				'href'   => true,
				'target' => true,
				'rel'    => true,
				'class'  => true,
			),
			'i' => array(
				'class' => true,
			),
		)
	);
}

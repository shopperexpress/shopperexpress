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

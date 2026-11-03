<?php
/**
 * Navigation menu locations.
 *
 * A location is a slot the theme renders; the menu that fills it is chosen in the admin under
 * Appearance → Menus. header.php and footer.php render nothing when their location is empty.
 *
 * @package shopperexpress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the locations header.php and footer.php render into.
 */
function shopperexpress_register_menus() {
	register_nav_menus(
		array(
			'primary' => esc_html__( 'Primary', 'shopperexpress' ),
			'footer'  => esc_html__( 'Footer', 'shopperexpress' ),
		)
	);
}
add_action( 'after_setup_theme', 'shopperexpress_register_menus' );

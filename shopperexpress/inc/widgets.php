<?php
/**
 * Widget areas.
 *
 * @package shopperexpress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the widget area sidebar.php renders into.
 */
function shopperexpress_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Sidebar', 'shopperexpress' ),
			'id'            => 'sidebar-1',
			'description'   => esc_html__( 'Widgets shown beside post and archive content.', 'shopperexpress' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'shopperexpress_widgets_init' );

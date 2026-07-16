<?php
/**
 * Theme sidebars.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class Sidebars
 *
 * @package App\Components\Base
 */
class Sidebars implements Theme_Component {


	/**
	 * Regisster sidebars.
	 *
	 * @access public
	 * @return void
	 */
	public function register(): void {
		add_action( 'widgets_init', array( $this, 'register_sidebars' ) );
	}

	/**
	 * Registers theme sidebars.
	 *
	 * @access public
	 * @return void
	 */
	public function register_sidebars(): void {
		register_sidebar(
			array(
				'id'            => 'footer-sidebar',
				'name'          => __( 'Footer Sidebar', 'shopperexpress' ),
				'before_widget' => '<div class="col-6 col-sm-4 col-lg-2 %2$s" id="%1$s">',
				'after_widget'  => '</div>',
				'before_title'  => '<h4>',
				'after_title'   => '</h4>',
			)
		);
		register_sidebar(
			array(
				'id'            => 'post-sidebar',
				'before_widget' => '<div class="%2$s" id="%1$s">',
				'after_widget'  => '</div>',
				'name'          => __( 'Post Sidebar', 'shopperexpress' ),
				'before_title'  => '<h4>',
				'after_title'   => '</h4>',
			)
		);
	}
}

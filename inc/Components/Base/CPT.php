<?php
/**
 * WordPress CPT.
 *
 * @package ThemeName
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class CPT
 *
 * @package App\Base\Component
 */
class CPT implements Theme_Component {

	/**
	 * Set post types.
	 *
	 * @var array
	 */
	public $set_post_types = array(
		'research'           => 'Research',
		'service-offers'     => 'Service Offers',
		'listings'           => 'Listings',
		'used-listings'      => 'Used Listings',
		'offers'             => 'Offers',
		'finance-offers'     => 'Finance Offers',
		'lease-offers'       => 'Lease Offers',
		'conditional-offers' => 'Conditional Offers',
	);

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( $this->set_post_types ) {
			foreach ( $this->set_post_types as $slug => $name ) {
				register_post_type( $slug, $this->get_args( $name ) );
			}
		}
	}

	/**
	 * Get post type args.
	 *
	 * @param string $name Post type name.
	 *
	 * @return array
	 */
	public function get_args( string $name ): array {

		$args = array(
			'label'               => esc_html( $name ),
			'description'         => sprintf(
			/* translators: %s: post type name */
				esc_html__( '%s Description', 'shopperexpress' ),
				esc_html( $name )
			),
			'labels'              => array(
				'name'                  => esc_html( $name ),
				'singular_name'         => esc_html( $name ),
				'menu_name'             => esc_html( $name ),
				'name_admin_bar'        => esc_html( $name ),
				'archives'              => esc_html__( 'Item Archives', 'shopperexpress' ),
				'attributes'            => esc_html__( 'Item Attributes', 'shopperexpress' ),
				'parent_item_colon'     => esc_html__( 'Parent Item:', 'shopperexpress' ),
				'all_items'             => sprintf(
				/* translators: %s: post type name */
					esc_html__( 'All %s', 'shopperexpress' ),
					esc_html( $name )
				),
				'add_new_item'          => esc_html__( 'Add New Item', 'shopperexpress' ),
				'add_new'               => esc_html__( 'Add New', 'shopperexpress' ),
				'new_item'              => esc_html__( 'New Item', 'shopperexpress' ),
				'edit_item'             => esc_html__( 'Edit Item', 'shopperexpress' ),
				'update_item'           => esc_html__( 'Update Item', 'shopperexpress' ),
				'view_item'             => esc_html__( 'View Item', 'shopperexpress' ),
				'view_items'            => esc_html__( 'View Items', 'shopperexpress' ),
				'search_items'          => esc_html__( 'Search Item', 'shopperexpress' ),
				'not_found'             => esc_html__( 'Not found', 'shopperexpress' ),
				'not_found_in_trash'    => esc_html__( 'Not found in Trash', 'shopperexpress' ),
				'featured_image'        => esc_html__( 'Featured Image', 'shopperexpress' ),
				'set_featured_image'    => esc_html__( 'Set featured image', 'shopperexpress' ),
				'remove_featured_image' => esc_html__( 'Remove featured image', 'shopperexpress' ),
				'use_featured_image'    => esc_html__( 'Use as featured image', 'shopperexpress' ),
				'insert_into_item'      => esc_html__( 'Insert into item', 'shopperexpress' ),
				'uploaded_to_this_item' => esc_html__( 'Uploaded to this item', 'shopperexpress' ),
				'items_list'            => esc_html__( 'Items list', 'shopperexpress' ),
				'items_list_navigation' => esc_html__( 'Items list navigation', 'shopperexpress' ),
				'filter_items_list'     => esc_html__( 'Filter items list', 'shopperexpress' ),
			),
			'supports'            => array( 'title', 'revisions', 'editor', 'excerpt', 'thumbnail' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 5,
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'page',
		);

		return $args;
	}
}

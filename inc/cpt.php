<?php

function list_taxonomies(){
	return [
		'year' 			 => __('Year', 'shopperexpress'),
		'make' 			 => __('Make', 'shopperexpress'),
		'model' 		 => __('Model', 'shopperexpress'),
		'body-style' 	 => __('Body Style', 'shopperexpress'),
		'mileage' 		 => __('Mileage', 'shopperexpress'),
		'transmission' 	 => __('Transmission', 'shopperexpress'),
		'condition' 	 => __('Condition', 'shopperexpress'),
		'location' 		 => __('Location', 'shopperexpress'),
		'drivetrain' 	 => __('Drivetrain', 'shopperexpress'),
		'engine' 		 => __('Engine', 'shopperexpress'),
		'exterior-color' => __('Exterior Color', 'shopperexpress'),
		'interior-color' => __('Interior Color', 'shopperexpress'),
		'stock-number' 	 => __('Stock Number', 'shopperexpress'),
		'vin-number' 	 => __('VIN Number', 'shopperexpress'),
		'trim' 			 => __('Trim', 'shopperexpress'),
		'lease-payment'  => __('Lease Payment', 'shopperexpress'),
		'loan-payment'   => __('Loan Payment', 'shopperexpress'),
		'down-payment'   => __('Down Payment', 'shopperexpress'),
		'leaseterm'      => __('LeaseTerm', 'shopperexpress'),
	];
}

function offers_list_taxonomies(){
	return [
		'year' 			 => __('Year', 'shopperexpress'),
		'make' 			 => __('Make', 'shopperexpress'),
		'model' 		 => __('Model', 'shopperexpress'),
		'body-style' 	 => __('Body Style', 'shopperexpress'),
		'transmission' 	 => __('Transmission', 'shopperexpress'),
		'condition' 	 => __('Condition', 'shopperexpress'),
		'location' 		 => __('Location', 'shopperexpress'),
		'drivetrain' 	 => __('Drivetrain', 'shopperexpress'),
		'engine' 		 => __('Engine', 'shopperexpress'),
		'exterior-color' => __('Exterior Color', 'shopperexpress'),
		'interior-color' => __('Interior Color', 'shopperexpress'),
		'trim' 			 => __('Trim', 'shopperexpress'),
		'lease-payment'  => __('Lease Payment', 'shopperexpress'),
		'loan-payment'   => __('Loan Payment', 'shopperexpress'),
		'down-payment'   => __('Down Payment', 'shopperexpress'),
		'leaseterm'      => __('LeaseTerm', 'shopperexpress'),
	];
}



// Register Custom Taxonomy
function custom_taxonomy() {

	foreach ( list_taxonomies() as $slug => $name ) {

		$labels = array(
			'name'                       => _x( $name, 'Taxonomy General Name', 'shopperexpress' ),
			'singular_name'              => _x( $name, 'Taxonomy Singular Name', 'shopperexpress' ),
			'menu_name'                  => __( $name, 'shopperexpress' ),
			'all_items'                  => __( 'All Items', 'shopperexpress' ),
			'parent_item'                => __( 'Parent Item', 'shopperexpress' ),
			'parent_item_colon'          => __( 'Parent Item:', 'shopperexpress' ),
			'new_item_name'              => __( 'New Item Name', 'shopperexpress' ),
			'add_new_item'               => __( 'Add New Item', 'shopperexpress' ),
			'edit_item'                  => __( 'Edit Item', 'shopperexpress' ),
			'update_item'                => __( 'Update Item', 'shopperexpress' ),
			'view_item'                  => __( 'View Item', 'shopperexpress' ),
			'separate_items_with_commas' => __( 'Separate items with commas', 'shopperexpress' ),
			'add_or_remove_items'        => __( 'Add or remove items', 'shopperexpress' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'shopperexpress' ),
			'popular_items'              => __( 'Popular Items', 'shopperexpress' ),
			'search_items'               => __( 'Search Items', 'shopperexpress' ),
			'not_found'                  => __( 'Not Found', 'shopperexpress' ),
			'no_terms'                   => __( 'No items', 'shopperexpress' ),
			'items_list'                 => __( 'Items list', 'shopperexpress' ),
			'items_list_navigation'      => __( 'Items list navigation', 'shopperexpress' ),
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => false,
			'public'                     => false,
			'show_ui'                    => true,
			'show_admin_column'          => false,
			'show_in_nav_menus'          => false,
			'show_tagcloud'              => false,
			'rewrite'                    => false,
		);
		register_taxonomy( $slug, array( 'listings'), $args );

	}
	foreach ( offers_list_taxonomies() as $slug => $name ) {

		$labels = array(
			'name'                       => _x( $name, 'Taxonomy General Name', 'shopperexpress' ),
			'singular_name'              => _x( $name, 'Taxonomy Singular Name', 'shopperexpress' ),
			'menu_name'                  => __( $name, 'shopperexpress' ),
			'all_items'                  => __( 'All Items', 'shopperexpress' ),
			'parent_item'                => __( 'Parent Item', 'shopperexpress' ),
			'parent_item_colon'          => __( 'Parent Item:', 'shopperexpress' ),
			'new_item_name'              => __( 'New Item Name', 'shopperexpress' ),
			'add_new_item'               => __( 'Add New Item', 'shopperexpress' ),
			'edit_item'                  => __( 'Edit Item', 'shopperexpress' ),
			'update_item'                => __( 'Update Item', 'shopperexpress' ),
			'view_item'                  => __( 'View Item', 'shopperexpress' ),
			'separate_items_with_commas' => __( 'Separate items with commas', 'shopperexpress' ),
			'add_or_remove_items'        => __( 'Add or remove items', 'shopperexpress' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'shopperexpress' ),
			'popular_items'              => __( 'Popular Items', 'shopperexpress' ),
			'search_items'               => __( 'Search Items', 'shopperexpress' ),
			'not_found'                  => __( 'Not Found', 'shopperexpress' ),
			'no_terms'                   => __( 'No items', 'shopperexpress' ),
			'items_list'                 => __( 'Items list', 'shopperexpress' ),
			'items_list_navigation'      => __( 'Items list navigation', 'shopperexpress' ),
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => false,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => false,
			'show_in_nav_menus'          => false,
			'show_tagcloud'              => false,
			'rewrite'                    => false,
		);
		register_taxonomy( $slug, array( 'listings', 'offers' ), $args );
	}

}
add_action( 'init', 'custom_taxonomy', 0 );

// Register Custom Post Type
function listings() {

	$labels = array(
		'name'                  => _x( 'Listings', 'Post Type General Name', 'shopperexpress' ),
		'singular_name'         => _x( 'Listings', 'Post Type Singular Name', 'shopperexpress' ),
		'menu_name'             => __( 'Listings', 'shopperexpress' ),
		'name_admin_bar'        => __( 'Listings', 'shopperexpress' ),
		'archives'              => __( 'Item Archives', 'shopperexpress' ),
		'attributes'            => __( 'Item Attributes', 'shopperexpress' ),
		'parent_item_colon'     => __( 'Parent Item:', 'shopperexpress' ),
		'all_items'             => __( 'All Listings', 'shopperexpress' ),
		'add_new_item'          => __( 'Add New Item', 'shopperexpress' ),
		'add_new'               => __( 'Add New', 'shopperexpress' ),
		'new_item'              => __( 'New Item', 'shopperexpress' ),
		'edit_item'             => __( 'Edit Item', 'shopperexpress' ),
		'update_item'           => __( 'Update Item', 'shopperexpress' ),
		'view_item'             => __( 'View Item', 'shopperexpress' ),
		'view_items'            => __( 'View Items', 'shopperexpress' ),
		'search_items'          => __( 'Search Item', 'shopperexpress' ),
		'not_found'             => __( 'Not found', 'shopperexpress' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'shopperexpress' ),
		'featured_image'        => __( 'Featured Image', 'shopperexpress' ),
		'set_featured_image'    => __( 'Set featured image', 'shopperexpress' ),
		'remove_featured_image' => __( 'Remove featured image', 'shopperexpress' ),
		'use_featured_image'    => __( 'Use as featured image', 'shopperexpress' ),
		'insert_into_item'      => __( 'Insert into item', 'shopperexpress' ),
		'uploaded_to_this_item' => __( 'Uploaded to this item', 'shopperexpress' ),
		'items_list'            => __( 'Items list', 'shopperexpress' ),
		'items_list_navigation' => __( 'Items list navigation', 'shopperexpress' ),
		'filter_items_list'     => __( 'Filter items list', 'shopperexpress' ),
	);
	$args = array(
		'label'                 => __( 'Listings', 'shopperexpress' ),
		'description'           => __( 'Listings Description', 'shopperexpress' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'revisions' ),
		'taxonomies'            => list_taxonomies(),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 5,
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => true,
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'page',
	);
	if ( $post_type_name = get_field( 'post_type_name', 'options' ) ) {
		$args['rewrite'] = array('slug' => $post_type_name,'with_front' => true);
	}
	register_post_type( 'listings', $args );

	$labels = array(
		'name'                  => _x( 'Offers', 'Post Type General Name', 'shopperexpress' ),
		'singular_name'         => _x( 'Offers', 'Post Type Singular Name', 'shopperexpress' ),
		'menu_name'             => __( 'Offers', 'shopperexpress' ),
		'name_admin_bar'        => __( 'Offers', 'shopperexpress' ),
		'archives'              => __( 'Item Archives', 'shopperexpress' ),
		'attributes'            => __( 'Item Attributes', 'shopperexpress' ),
		'parent_item_colon'     => __( 'Parent Item:', 'shopperexpress' ),
		'all_items'             => __( 'All Offers', 'shopperexpress' ),
		'add_new_item'          => __( 'Add New Item', 'shopperexpress' ),
		'add_new'               => __( 'Add New', 'shopperexpress' ),
		'new_item'              => __( 'New Item', 'shopperexpress' ),
		'edit_item'             => __( 'Edit Item', 'shopperexpress' ),
		'update_item'           => __( 'Update Item', 'shopperexpress' ),
		'view_item'             => __( 'View Item', 'shopperexpress' ),
		'view_items'            => __( 'View Items', 'shopperexpress' ),
		'search_items'          => __( 'Search Item', 'shopperexpress' ),
		'not_found'             => __( 'Not found', 'shopperexpress' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'shopperexpress' ),
		'featured_image'        => __( 'Featured Image', 'shopperexpress' ),
		'set_featured_image'    => __( 'Set featured image', 'shopperexpress' ),
		'remove_featured_image' => __( 'Remove featured image', 'shopperexpress' ),
		'use_featured_image'    => __( 'Use as featured image', 'shopperexpress' ),
		'insert_into_item'      => __( 'Insert into item', 'shopperexpress' ),
		'uploaded_to_this_item' => __( 'Uploaded to this item', 'shopperexpress' ),
		'items_list'            => __( 'Items list', 'shopperexpress' ),
		'items_list_navigation' => __( 'Items list navigation', 'shopperexpress' ),
		'filter_items_list'     => __( 'Filter items list', 'shopperexpress' ),
	);
	$args = array(
		'label'                 => __( 'Offers', 'shopperexpress' ),
		'description'           => __( 'Offers Description', 'shopperexpress' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'revisions' ),
		'taxonomies'            => offers_list_taxonomies(),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 5,
		'menu_icon' => 'dashicons-tag',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => true,
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'page',
	);
	register_post_type( 'offers', $args );

}
add_action( 'init', 'listings', 0 );
<?php
/**
 * Block: Team Section (Intice)
 *
 * Title: Team Section (Intice)
 * Description: Team member grid sourced live from Intice Nexus instead of ACF rows.
 * Keywords: team staff members intice nexus
 * Category: custom-acf-blocks
 * Icon: groups
 *
 * @package ShopperExpress
 */

use App\Components\Api\Intice_Api_Client;
use App\Components\SOC\Modules\Api_Settings;

if ( \App\Components\Gutenberg\Block_Preview_Helper::render( $block ) ) {
	return;
}

if ( $is_preview ) {
	\App\Components\Gutenberg\Block_Preview_Helper::render( $block, true );
	return;
}

if ( ! Api_Settings::is_team_mode_enabled() ) {
	if ( current_user_can( 'edit_theme_options' ) ) {
		echo '<p style="padding:16px;background:#fff3cd;border:1px solid #ffe69c;border-radius:6px;">'
			. esc_html__( 'Team Section (Intice) is inactive — enable "Team Members (Intice)" in SOC → API Settings to show this block.', 'shopperexpress' )
			. '</p>';
	}
	return;
}

$response       = Intice_Api_Client::instance()->get_team_members();
$members        = array();
$category_order = array();

if ( ! is_wp_error( $response ) ) {
	foreach ( (array) ( $response['data'] ?? array() ) as $member ) {
		$members[] = array(
			'photo_url' => $member['photo_url'] ?? '',
			'name'      => $member['name'] ?? '',
			'position'  => $member['position'] ?? '',
			'phone'     => $member['phone'] ?? '',
			'email'     => $member['email'] ?? '',
			'category'  => $member['category']['name'] ?? '',
			'bio'       => $member['bio'] ?? '',
		);
	}

	// Nexus already returns this ordered by the category's own sort_order
	// (Content → Team Members → Categories, drag-to-reorder) — pass it through
	// so the filter pills follow that order instead of the order members
	// happen to appear in.
	$category_order = array_values( array_filter( array_map(
		static fn ( $category ) => $category['name'] ?? '',
		(array) ( $response['meta']['categories'] ?? array() )
	) ) );
}

get_template_part(
	'template-parts/acf-shared/team-section-intice',
	null,
	array(
		'heading'            => get_field( 'heading' ),
		'description'        => get_field( 'description' ),
		'columns'            => get_field( 'columns' ),
		'members'            => $members,
		'category_order'     => $category_order,
		'footer_heading'     => get_field( 'footer_heading' ),
		'footer_button_text' => get_field( 'footer_button_text' ),
		'footer_button_url'  => get_field( 'footer_button_url' ),
		'anchor'             => $block['anchor'] ?? '',
	)
);

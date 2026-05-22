<?php
/**
 * Block: Sub Footer
 *
 * Title: Sub Footer
 * Description: Contact information block with addresses and schedule.
 * Keywords: footer contact address schedule
 * Category: custom-acf-blocks
 * Icon: building
 *
 * @package ShopperExpress
 */

if ( \App\Components\Gutenberg\Block_Preview_Helper::render( $block ) ) {
	return;
}

get_template_part(
	'template-parts/acf-shared/sub-footer',
	null,
	array(
		'main_title'     => get_field( 'main_title' ),
		'addresses_list' => get_field( 'addresses_list' ),
		'schedule_title' => get_field( 'schedule_title' ),
		'schedule_list'  => get_field( 'schedule_list' ),
	)
);

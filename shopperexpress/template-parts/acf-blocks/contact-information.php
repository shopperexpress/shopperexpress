<?php
/**
 * Block: Contact Information
 *
 * Title: Contact Information
 * Description: Contact details with phone, social, and weekly schedule.
 * Keywords: contact phone social schedule hours
 * Category: custom-acf-blocks
 * Icon: phone
 *
 * @package ShopperExpress
 */

if ( \App\Components\Gutenberg\Block_Preview_Helper::render( $block ) ) {
	return;
}

// Build contact list.
$contact_list = array();
if ( have_rows( 'contact_list' ) ) {
	while ( have_rows( 'contact_list' ) ) {
		the_row();
		$contact_list[] = array(
			'label'      => get_sub_field( 'label' ),
			'url'        => get_sub_field( 'url' ),
			'new_window' => get_sub_field( 'new_window' ),
			'icon'       => get_sub_field( 'icon' ),
		);
	}
}

// Build social media list.
$social_media = array();
if ( have_rows( 'social_media' ) ) {
	while ( have_rows( 'social_media' ) ) {
		the_row();
		$social_media[] = array(
			'label'      => get_sub_field( 'label' ),
			'url'        => get_sub_field( 'url' ),
			'new_window' => get_sub_field( 'new_window' ),
			'icon'       => get_sub_field( 'icon' ),
		);
	}
}

// Build schedule.
$days = array(
	'day_1' => 'Monday',
	'day_2' => 'Tuesday',
	'day_3' => 'Wednesday',
	'day_4' => 'Thursday',
	'day_5' => 'Friday',
	'day_6' => 'Saturday',
	'day_7' => 'Sunday',
);

$schedule = array();
foreach ( $days as $day_key => $day_label ) {
	if ( have_rows( $day_key ) ) {
		while ( have_rows( $day_key ) ) {
			the_row();
			$closed = get_sub_field( 'closed' );
			if ( $closed ) {
				$schedule[] = __( 'Closed', 'shopperexpress' );
			} else {
				$open  = get_sub_field( 'open' );
				$close = get_sub_field( 'close' );
				if ( $open && $close ) {
					$schedule[] = esc_html( $open ) . '&ndash;' . esc_html( $close );
				} else {
					$schedule[] = __( 'N/A', 'shopperexpress' );
				}
			}
		}
	} else {
		$schedule[] = __( 'N/A', 'shopperexpress' );
	}
}

get_template_part(
	'template-parts/acf-shared/contact-information',
	null,
	array(
		'heading_contact'  => get_field( 'heading_contact' ),
		'contact_list'     => $contact_list,
		'heading_social'   => get_field( 'heading_social' ),
		'social_media'     => $social_media,
		'heading_schedule' => get_field( 'heading_schedule' ),
		'schedule'         => $schedule,
		'days'             => $days,
	)
);

<?php
/**
 * Flexible Content Wrapper: Buttons
 *
 * @package ShopperExpress
 */

$buttons = array();
if ( have_rows( 'buttons' ) ) {
	while ( have_rows( 'buttons' ) ) {
		the_row();
		$buttons[] = array(
			'button_code' => get_sub_field( 'button_code' ),
		);
	}
}

get_template_part(
	'template-parts/acf-shared/buttons',
	null,
	array(
		'buttons' => $buttons,
	)
);

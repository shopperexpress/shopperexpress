<?php
/**
 * Flexible Content Wrapper: Info Accordion
 *
 * @package ShopperExpress
 */

$section_row = get_row_index();
$accordion   = array();

if ( have_rows( 'accordion' ) ) {
	while ( have_rows( 'accordion' ) ) {
		the_row();
		$accordion[] = array(
			'heading' => get_sub_field( 'heading' ),
			'text'    => get_sub_field( 'text' ),
		);
	}
}

get_template_part(
	'template-parts/acf-shared/info-accordion',
	null,
	array(
		'section_row' => $section_row,
		'accordion'   => $accordion,
	)
);

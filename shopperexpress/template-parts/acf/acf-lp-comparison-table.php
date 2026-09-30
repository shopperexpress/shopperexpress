<?php
/**
 * Flexible Content Wrapper: LP Comparison Table
 *
 * @package ShopperExpress
 */

$columns = array();
if ( have_rows( 'columns' ) ) {
	while ( have_rows( 'columns' ) ) {
		the_row();
		$columns[] = array(
			'label'     => get_sub_field( 'label' ),
			'logo'      => get_sub_field( 'logo' ),
			'highlight' => get_sub_field( 'highlight' ),
		);
	}
}

$rows = array();
if ( have_rows( 'rows' ) ) {
	while ( have_rows( 'rows' ) ) {
		the_row();
		$values = array();
		if ( have_rows( 'values' ) ) {
			while ( have_rows( 'values' ) ) {
				the_row();
				$values[] = get_sub_field( 'value' );
			}
		}
		$rows[] = array(
			'label'  => get_sub_field( 'label' ),
			'values' => $values,
		);
	}
}

get_template_part(
	'template-parts/acf-shared/lp-comparison-table',
	null,
	array(
		'subtitle' => get_sub_field( 'subtitle' ),
		'title'    => get_sub_field( 'title' ),
		'columns'  => $columns,
		'rows'     => $rows,
		'anchor'   => get_sub_field( 'anchor' ),
	)
);

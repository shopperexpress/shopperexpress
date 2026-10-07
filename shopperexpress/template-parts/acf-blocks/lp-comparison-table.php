<?php
/**
 * Block: LP Comparison Table
 *
 * Title: LP Comparison Table
 * Description: Landing page comparison table (N columns), row values render as a checkmark, "not available" cross, or free text.
 * Keywords: landing comparison table versus features
 * Category: custom-acf-blocks
 * Icon: editor-table
 *
 * @package ShopperExpress
 */

if ( \App\Components\Gutenberg\Block_Preview_Helper::render( $block ) ) {
	return;
}

if ( $is_preview ) {
	\App\Components\Gutenberg\Block_Preview_Helper::render( $block, true );
	return;
}

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
		'subtitle' => get_field( 'subtitle' ),
		'title'    => get_field( 'title' ),
		'columns'  => $columns,
		'rows'     => $rows,
		'anchor'   => $block['anchor'] ?? '',
	)
);

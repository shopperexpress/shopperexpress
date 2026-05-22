<?php
/**
 * Flexible Content Wrapper: Contact Widget
 *
 * @package ShopperExpress
 */

// Contact list.
$contact_list = array();
if ( have_rows( 'contact_list' ) ) {
	while ( have_rows( 'contact_list' ) ) {
		the_row();
		$contact_list[] = array(
			'label'      => get_sub_field( 'label' ),
			'new_window' => get_sub_field( 'new_window' ),
			'copy'       => get_sub_field( 'copy' ),
			'url'        => get_sub_field( 'url' ),
			'icon'       => get_sub_field( 'icon' ),
		);
	}
}

// Links.
$links = array();
if ( have_rows( 'links' ) ) {
	while ( have_rows( 'links' ) ) {
		the_row();
		$links[] = array(
			'link' => get_sub_field( 'link' ),
			'icon' => get_sub_field( 'icon' ),
		);
	}
}

// Social media.
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

// Tabs.
$rand = mt_rand( 1, 9999 );
$tabs = array();
if ( have_rows( 'tabs' ) ) {
	// First pass: collect tab titles.
	$tab_titles = array();
	while ( have_rows( 'tabs' ) ) {
		the_row();
		$tab_titles[] = array(
			'tab_title' => get_sub_field( 'tab_title' ),
			'layout'    => get_row_layout(),
		);
	}
	// Second pass: collect tab content.
	$tab_idx = 0;
	while ( have_rows( 'tabs' ) ) {
		the_row();
		$layout   = get_row_layout();
		$tab_data = array(
			'tab_title' => $tab_titles[ $tab_idx ]['tab_title'] ?? '',
			'layout'    => $layout,
		);

		if ( 'hours' === $layout ) {
			$schedule_list = array();
			while ( have_rows( 'schedule_list' ) ) {
				the_row();
				$sg_heading = get_sub_field( 'heading' );
				$sg_list    = array();
				while ( have_rows( 'list' ) ) {
					the_row();
					$sg_list[] = array(
						'day'  => get_sub_field( 'day' ),
						'time' => get_sub_field( 'time' ),
					);
				}
				$schedule_list[] = array(
					'heading' => $sg_heading,
					'list'    => $sg_list,
				);
			}
			$tab_data['heading']       = get_sub_field( 'heading' );
			$tab_data['schedule_list'] = $schedule_list;
		} elseif ( 'text' === $layout ) {
			$tab_data['text'] = get_sub_field( 'text' );
		} elseif ( 'list' === $layout ) {
			$lists = array();
			if ( have_rows( 'lists' ) ) {
				while ( have_rows( 'lists' ) ) {
					the_row();
					$list_items = array();
					while ( have_rows( 'list' ) ) {
						the_row();
						$list_items[] = array(
							'text' => get_sub_field( 'text' ),
							'icon' => get_sub_field( 'icon' ),
						);
					}
					$lists[] = array( 'list' => $list_items );
				}
			}
			$tab_data['lists'] = $lists;
		}

		$tabs[]   = $tab_data;
		$tab_idx++;
	}
}

get_template_part(
	'template-parts/acf-shared/contact-widget',
	null,
	array(
		'heading_contact' => get_sub_field( 'heading_contact' ),
		'contact_list'    => $contact_list,
		'links'           => $links,
		'heading_social'  => get_sub_field( 'heading_social' ),
		'social_media'    => $social_media,
		'rand'            => $rand,
		'tabs'            => $tabs,
	)
);

<?php
/**
 * Flexible Content Wrapper: Intro Section / Video Section
 *
 * @package ShopperExpress
 */

$block            = get_row_layout();
$is_video_section = ( 'video_section' === $block );

// Pre-render nested blocks HTML.
$blocks_html = '';
if ( have_rows( 'blocks' ) ) {
	ob_start();
	while ( have_rows( 'blocks' ) ) {
		the_row();
		if ( $is_video_section ) {
			$template_path = locate_template( 'template-parts/acf/acf-video.php' );
			if ( $template_path ) {
				get_template_part( 'template-parts/acf/acf-video' );
			} else {
				echo '<!-- Template for video_section not found -->';
			}
		} else {
			$row_layout    = get_row_layout();
			$template_path = locate_template( 'template-parts/acf/acf-' . $row_layout . '.php' );
			if ( $template_path ) {
				get_template_part( 'template-parts/acf/acf', $row_layout );
			} else {
				echo '<!-- Template for ' . $row_layout . ' not found -->';
			}
		}
	}
	$blocks_html = ob_get_clean();
}

get_template_part(
	'template-parts/acf-shared/intro-section',
	null,
	array(
		'is_video_section'   => $is_video_section,
		'icon'               => get_sub_field( 'icon' ),
		'title'              => get_sub_field( 'title' ),
		'subtitle'           => get_sub_field( 'subtitle' ),
		'image'              => get_sub_field( 'image' ),
		'bottom_button_code' => get_sub_field( 'bottom_button_code' ),
		'bottom_image'       => get_sub_field( 'bottom_image' ),
		'first_image'        => get_sub_field( 'first_image' ),
		'second_image'       => get_sub_field( 'second_image' ),
		'thrid_image'        => get_sub_field( 'thrid_image' ),
		'text'               => get_sub_field( 'text' ),
		'link'               => get_sub_field( 'link' ),
		'blocks_html'        => $blocks_html,
	)
);

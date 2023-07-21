<?php 
/*
Template Name: Flexible Page Template
*/
get_header();
if ( function_exists( 'wpcf7_enqueue_scripts' ) && get_field( 'load_cf_js' ) ) {
	wpcf7_enqueue_scripts();
}

$is_new_home_page_styles = get_field( 'new_home_page_styles' );
?>
<div <?php if (!$is_new_home_page_styles) echo 'id="page-container"'; ?> <?php if( get_field( 'page_style' ) == 2 ): ?> class="trade-value-page"<?php endif; ?>>
	<?php
	while ( have_rows('content_blocks') ) : the_row();
		get_template_part( 'blocks/acf/acf', get_row_layout());
	endwhile;
	?> 
</div>
<?php get_footer(); ?>
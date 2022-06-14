<?php 
/*
Template Name: Flexible Page Template
*/
get_header(); ?>
<div id="page-container" <?php if( get_field( 'page_style' ) == 2 ): ?> class="trade-value-page"<?php endif; ?>>
	<?php
	while ( have_rows('content_blocks') ) : the_row();
		get_template_part( 'blocks/acf/acf', get_row_layout());
	endwhile;
	?> 
</div>
<?php get_footer(); ?>
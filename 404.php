<?php
/**
 * Template part for displaying 404 page
 *
 * @package Shopperexpress
 */

get_header();
?>
<div class="section-404">
	<?php
	while ( have_rows( 'content_blocks_content_blocks', 'options' ) ) :
		the_row();
			get_template_part( 'template-parts/acf/acf', get_row_layout() );
	endwhile;
	?>
</div>
<?php get_footer(); ?>

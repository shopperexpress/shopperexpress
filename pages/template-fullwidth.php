<?php 
/*
Template Name: Full-Width Page Template
*/
get_header(); ?>
<div id="page-container" class="full-width-page">
	<?php
	while ( have_posts() ) : the_post();
		the_title( '<div class="title"><h1>', '</h1></div>' );
		the_post_thumbnail( 'full' );
		the_content();
	endwhile;
	wp_link_pages();
	?>
</div>
<?php get_footer(); ?>
<?php
/**
 * Template part for displaying page
 *
 * @package Shopperexpress
 */

get_header(); ?>
<div class="container-fluid">
	<div id="content">
		<?php
		while ( have_posts() ) :
			the_post();
			the_title( '<div class="title"><h1>', '</h1></div>' );
			the_post_thumbnail( 'full' );
			the_content();
		endwhile;
		wp_link_pages();
		?>
	</div>
	<?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>

<?php get_header(); ?>
<div class="container-fluid">
	<div id="content">
		<?php while ( have_posts() ) : the_post(); ?>
			<?php the_title( '<div class="title"><h1>', '</h1></div>' ); ?>
			<?php the_post_thumbnail( 'full' ); ?>
			<?php the_content(); ?>
			<?php edit_post_link( __( 'Edit', 'shopperexpress' ) ); ?>
		<?php endwhile; ?>
		<?php wp_link_pages(); ?>
		<?php comments_template(); ?>
	</div>
	<?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>
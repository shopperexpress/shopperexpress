<?php
/**
 * Template part for displaying search results
 *
 * @package Shopperexpress
 */

if ( have_posts() ) : ?>
	<?php get_header(); ?>
	<div class="container-fluid">
		<div id="content">
			<div class="title">
				<h1><?php printf( __( 'Search Results for: %s', 'shopperexpress' ), '<span>' . get_search_query() . '</span>' ); ?></h1>
			</div>
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<?php get_template_part( 'template-parts/content', get_post_type() ); ?>
			<?php endwhile; ?>
			<?php get_template_part( 'template-parts/pager' ); ?>
		</div>
		<?php get_sidebar(); ?>
	</div>
	<?php get_footer(); ?>
<?php else : ?>
	<?php get_template_part( '404' ); ?>
<?php endif; ?>

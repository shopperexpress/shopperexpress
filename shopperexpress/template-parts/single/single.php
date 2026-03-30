<?php
/**
 * Template for displaying single.
 *
 * @package Shopperexpress
 */

get_header(); ?>
<div class="blog-detail">
	<div class="container">
		<div id="content">
			<?php if ( function_exists( 'yoast_breadcrumb' ) && function_exists( 'custom_yoast_breadcrumbs_as_ol' ) ) : ?>
				<nav aria-label="breadcrumb">
					<?php yoast_breadcrumb(); ?>
				</nav>
				<?php
			endif;
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/content', get_post_type() );
				comments_template();
				get_template_part( 'template-parts/pager-single', get_post_type() );
			endwhile;
			?>
		</div>
		<?php if ( is_active_sidebar( 'post-sidebar' ) ) : ?>
			<aside id="sidebar">
				<?php dynamic_sidebar( 'post-sidebar' ); ?>
			</aside>
		<?php endif; ?>
	</div>
<?php get_template_part( 'template-parts/featured', 'posts' ); ?>
</div>
<?php get_footer(); ?>

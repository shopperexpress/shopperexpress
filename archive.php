<?php
/**
 * Template part for displaying archive
 *
 * @package Shopperexpress
 */

get_header();
?>
<div class="container-fluid">
	<div id="content">
		<?php if ( have_posts() ) : ?>
			<div class="title">
				<?php the_archive_title( '<h1>', '</h1>' ); ?>
			</div>
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/content', get_post_type() );
			endwhile;
			get_template_part( 'template-parts/pager' );
		else :
			get_template_part( 'template-parts/not_found' );
		endif;
		?>
	</div>
	<?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>

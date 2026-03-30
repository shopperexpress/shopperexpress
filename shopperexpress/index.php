<?php
/**
 * Template part for displaying archive
 *
 * @package Shopperexpress
 */

get_header(); ?>
<div class="blog-section">
	<div class="container">
		<div class="blog-section__header">
			<?php if ( function_exists( 'yoast_breadcrumb' ) && function_exists( 'custom_yoast_breadcrumbs_as_ol' ) ) : ?>
				<nav aria-label="breadcrumb">
					<?php yoast_breadcrumb(); ?>
				</nav>
			<?php endif; ?>
			<h1>
				<?php
				if ( is_archive() ) {
					the_archive_title();
				} else {
					echo get_the_title( get_option( 'page_for_posts' ) );
				}
				?>
			</h1>
		</div>
		<div id="content">
			<div class="blog-list">
				<?php
				if ( have_posts() ) :
					while ( have_posts() ) :
						the_post();
						?>
						<article <?php post_class( 'card-blog-post' ); ?> id="post-<?php the_ID(); ?>">
							<div class="card-blog-post__img">
								<?php
								if ( has_post_thumbnail() ) {
									the_post_thumbnail( '454x255' );
								} else {
									$default_post_image = get_field( 'default_post_image', 'option' );
									if ( $default_post_image ) {
										echo wp_kses_post( get_attachment_image( $default_post_image, '454x255' ) );
									}
								}
								?>
							</div>
							<div class="card-blog-post__body">
								<?php
								the_title( '<h3 class="card-blog-post__title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h3>' );
								the_excerpt();
								?>
								<div class="card-blog-post__meta">
									<?php
									echo get_avatar( get_the_author_meta( 'ID' ), 48, '', get_the_author(), array( 'class' => 'card-blog-post__avatar' ) );
									?>
									<p>
										<a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ), get_the_author_meta( 'user_nicename' ) ); ?>"><?php the_author(); ?></a>
										<br />
										<a href="<?php echo get_date_archive_link(); ?>" rel="bookmark">
											<time datetime="<?php echo get_the_date( 'Y-m-d' ); ?>"><?php the_date(); ?></time>
										</a>
									</p>
								</div>
							</div>
						</article>
						<?php
					endwhile;
					get_template_part( 'template-parts/pager' );
				else :
					get_template_part( 'template-parts/not_found' );
				endif;
				?>
			</div>
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

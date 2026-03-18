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
			<?php if ( function_exists( 'yoast_breadcrumb' ) ) : ?>
				<nav aria-label="breadcrumb">
					<?php
					$breadcrumbs = yoast_breadcrumb( '<ol class="breadcrumb">', '</ol>', false );
					if ( $breadcrumbs ) {
						$breadcrumbs = strip_tags( $breadcrumbs, '<a><span>' );
						$parts       = explode( '›', $breadcrumbs );

						echo '<ol class="breadcrumb">';
						foreach ( $parts as $index => $part ) {
							$part   = trim( $part );
							$isLast = ( array_key_last( $parts ) === $index );

							if ( $isLast ) {
								echo '<li class="breadcrumb-item active" aria-current="page">' . strip_tags( $part ) . '</li>';
							} else {
								echo '<li class="breadcrumb-item">' . $part . '</li>';
							}
						}
						echo '</ol>';
					}
					?>
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
	<?php
	$heading        = get_field( 'heading_featured_posts', 'option' );
	$featured_posts = get_field( 'featured_posts', 'option' );

	if ( $heading || $featured_posts ) :
		?>
		<div class="section-featured">
			<div class="container">
				<?php if ( $heading ) : ?>
					<h2><?php echo esc_html( $heading ); ?></h2>
					<?php
				endif;
				if ( $featured_posts ) :
					?>
					<div class="row">
						<?php
						foreach ( $featured_posts as $post_id ) :
							$post_thumbnail = get_the_post_thumbnail_url( $post_id, 'medium' ) ?: 'images/img-1.png';
							$categories     = get_the_category( $post_id );
							$category_name  = $categories ? $categories[0]->name : 'Uncategorized';

							$author_id   = get_post_field( 'post_author', $post_id );
							$author_name = get_the_author_meta( 'display_name', $author_id );
							$author_url  = get_author_posts_url( $author_id );
							$avatar      = get_avatar_url( $author_id, array( 'size' => 48 ) );
							$date        = get_the_date( 'F j, Y', $post_id );
							$date_iso    = get_the_date( 'Y-m-d', $post_id );
							?>
							<div class="col-md-4">
								<article class="card-post">
									<div class="card-post__img">
										<img src="<?php echo esc_url( $post_thumbnail ); ?>" alt="<?php echo esc_attr( get_the_title( $post_id ) ); ?>" />
									</div>
									<div class="card-post__body">
										<strong class="card-post__category"><?php echo esc_html( $category_name ); ?></strong>
										<h3 class="card-post__title">
											<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
												<?php echo esc_html( get_the_title( $post_id ) ); ?>
											</a>
										</h3>
										<div class="card-post__meta">
											<img class="card-post__avatar" src="<?php echo esc_url( $avatar ); ?>" alt="<?php echo esc_attr( $author_name ); ?>" />
											<p>
												<a href="<?php echo esc_url( $author_url ); ?>"><?php echo esc_html( $author_name ); ?></a><br />
												<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" rel="bookmark">
													<time datetime="<?php echo esc_attr( $date_iso ); ?>"><?php echo esc_html( $date ); ?></time>
												</a>
											</p>
										</div>
									</div>
								</article>
							</div>
							<?php
						endforeach;

						?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
</div>
<?php get_footer(); ?>

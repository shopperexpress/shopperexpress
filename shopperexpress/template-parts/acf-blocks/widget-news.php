<?php
/**
 * Title: Widget news
 * Keywords: Keyword 1, Keyword 2
 * Category: custom-acf-blocks
 *
 * @param  array $block The block settings and attributes.
 * @param  string $content The block inner HTML (empty).
 * @param  bool $is_preview True during AJAX preview.
 * @param  (int|string) $post_id The post ID this block is saved to.
 *
 * @package ThemeName
 */

$block_id = ! empty( $block['id'] ) ? 'block-' . $block['id'] : '';
if ( ! empty( $block['anchor'] ) ) {
	$block_id = $block['anchor'];
}

$class_names = array( 'widget-news' );
if ( ! empty( $block['className'] ) ) {
	$class_names[] = $block['className'];
}

$heading = get_field( 'heading' );
$link    = get_field( 'link' );

$query = new WP_Query(
	array(
		'post_type'      => 'post',
		'posts_per_page' => get_field( 'limit' ),
		'post_status'    => 'publish',
		'fields'         => 'ids',
		'post__not_in'   => array( get_the_ID() ),
	)
);

if ( $query->posts || $heading || $link ) :
	?>
	<div id="<?php echo esc_attr( $block_id ); ?>" class="<?php echo esc_attr( implode( ' ', $class_names ) ); ?>">
		<?php if ( $heading ) : ?>
			<h3><?php echo esc_html( $heading ); ?></h3>
			<?php
		endif;
		if ( $query->posts ) :
			?>
			<ul class="latest-news">
				<?php foreach ( $query->posts as $post_id ) : ?>
					<li>
						<?php
						$post = get_post( $post_id );
						if ( $post ) :
							$post_thumbnail_url = get_the_post_thumbnail_url( $post_id, 'medium' );
							$category           = get_the_category( $post_id );
							$category_name      = $category ? $category[0]->name : 'Uncategorized';
							$author_id          = $post->post_author;
							$author_name        = get_the_author_meta( 'display_name', $author_id );
							$author_url         = get_author_posts_url( $author_id );
							$post_date          = get_the_date( 'F j, Y', $post_id );
							$post_date_iso      = get_the_date( 'Y-m-d', $post_id );
							?>
							<article class="card-post">
								<?php if ( $post_thumbnail_url ) : ?>
									<div class="card-post__img">
										<img src="<?php echo esc_url( $post_thumbnail_url ); ?>" alt="<?php echo esc_attr( get_the_title( $post_id ) ); ?>" />
									</div>
								<?php endif; ?>
								<div class="card-post__body">
									<strong class="card-post__category"><?php echo esc_html( $category_name ); ?></strong>
									<h4 class="card-post__title">
										<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
											<?php echo esc_html( get_the_title( $post_id ) ); ?>
										</a>
									</h4>
									<div class="card-post__meta">
										<p>
											<a href="<?php echo esc_url( $author_url ); ?>"><?php echo esc_html( $author_name ); ?></a><br />
											<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" rel="bookmark">
												<time datetime="<?php echo esc_attr( $post_date_iso ); ?>"><?php echo esc_html( $post_date ); ?></time>
											</a>
										</p>
									</div>
								</div>
							</article>
						<?php endif; ?>
					</li>
					<?php
				endforeach;
				wp_reset_postdata();
				?>
			</ul>
			<?php
		endif;
		if ( $link ) :
			?>
			<div class="widget-news__footer">
				<?php echo App\the_acf_button( $link ); ?>
			</div>
		<?php endif; ?>
	</div>
<?php endif; ?>

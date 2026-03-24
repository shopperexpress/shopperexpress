<?php
/**
 * Template for displaying vehicle offer card.
 *
 * @param array $args Array of arguments containing post ID and other data.
 *
 * @package Shopperexpress
 */

?>
<div <?php post_class(); ?> id="post-<?php the_ID(); ?>">
	<div class="title">
		<?php
		if ( is_single() ) :
			the_title( '<h1>', '</h1>' );
		else :
			the_title( '<h2><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
		endif;
		?>
		<div class="meta-wrap">
			<p class="meta-info">
				<?php _e( 'by', 'shopperexpress' ); ?> <a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ), get_the_author_meta( 'user_nicename' ) ); ?>"><?php the_author(); ?></a>
				<br />
				<a href="<?php echo get_date_archive_link(); ?>" rel="bookmark">
					<time datetime="<?php echo get_the_date( 'Y-m-d' ); ?>">
						<?php the_date(); ?>
					</time>
				</a>
			</p>
			<?php
			if ( shortcode_exists( 'addtoany' ) ) {
				echo do_shortcode( '[addtoany buttons="facebook,twitter"]' );}
			?>
		</div>
	</div>
	<div class="content">
		<?php the_post_thumbnail( 'full' ); ?>
		<?php
		if ( is_single() ) :
			the_content();
		else :
			the_excerpt();
		endif;
		?>
	</div>
	<div class="meta">
		<ul>
			<li><?php _e( 'Posted in', 'shopperexpress' ); ?> <?php the_category( ', ' ); ?></li>
			<?php the_tags( __( '<li>Tags: ', 'shopperexpress' ), ', ', '</li>' ); ?>
		</ul>
	</div>
</div>

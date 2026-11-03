<?php
/**
 * Markup the templates share.
 *
 * Anything rendered by more than one template part belongs here — copies drift, and the one
 * nobody remembers to update is the one the client notices.
 *
 * @package shopperexpress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Prints the date, and optionally the author, for the current post.
 *
 * Pages carry neither: a date on a page is noise, and the author of an "About us" page is not
 * information anyone wants.
 *
 * @param bool $with_author Whether to name the author alongside the date.
 */
function shopperexpress_entry_meta( $with_author = false ) {
	if ( 'post' !== get_post_type() ) {
		return;
	}
	?>
	<p class="entry-meta">
		<time class="entry-date" datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
			<?php echo esc_html( get_the_date() ); ?>
		</time>

		<?php if ( $with_author ) : ?>
			<span class="entry-author"><?php the_author_posts_link(); ?></span>
		<?php endif; ?>
	</p>
	<?php
}

<?php
/**
 * Template part for displaying footer
 *
 * @package Shopperexpress
 */

?>
</main>
<footer id="footer">
	<div class="container-fluid">
		<?php if ( is_active_sidebar( 'footer-sidebar' ) ) : ?>
			<div class="row footer-holder">
				<?php dynamic_sidebar( 'footer-sidebar' ); ?>
				<div class="order-first order-md-last col-12 col-lg-6">
					<div class="search-bar-container">
						<?php
						if ( ! get_field( 'hide_search_form_footer', 'options' ) ) {
							get_template_part( 'template-parts/search-form' );
						}
						get_template_part( 'template-parts/spinning-icon-buttons', 'spinning-icon-buttons', array( 'post_id' => 'options' ) );
						?>
					</div>
				</div>
			</div>
		<?php endif; ?>
		<div class="row">
			<div class="col-sm-6">
				<span class="copyright"><?php esc_html_e( 'Version', 'shopperexpress' ); ?> <?php echo wp_get_theme()->get( 'Version' ); ?> © <?php echo date( 'Y' ); ?>
					<?php
					$link = get_field( 'copyright', 'options' );
					if ( $link ) {
						echo wps_get_link( $link );
					}
					?>
				</span>
			</div>
			<div class="col-sm-6">
				<?php
				$text_right_side = get_field( 'text_right_side', 'options' );
				if ( $text_right_side ) :
					?>
					<span class="by"><?php echo wp_kses_post( $text_right_side ); ?></span>
				<?php endif; ?>
			</div>
		</div>
	</div>
</footer>
</div>

<?php
get_template_part( 'template-parts/modals' );
get_template_part( 'template-parts/cookie', 'modal' );
do_action( 'filter_modal' );
wp_footer();
if ( is_singular( array( 'listings', 'used-listings' ) ) ) {
	echo do_shortcode( get_field( 'script', 'options' ) );
}
if ( is_singular( 'offers' ) ) {
	echo do_shortcode( get_field( 'offers_script', 'options' ) );
}
	the_field( 'for_script_footer', 'options' );
?>
</body>

</html>

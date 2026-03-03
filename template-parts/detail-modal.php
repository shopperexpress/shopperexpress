<?php
$post_type = ! empty( $args['post_type'] ) ? $args['post_type'] : 'listings';
if ( is_post_type_archive( 'used-listings' ) || is_singular( 'used-listings' ) || $post_type == 'used-listings' ) :
	$comment_footer = get_field( 'used_listings_comment_footer', 'option' );
else :
	$comment_footer = get_field( 'comment_footer', 'options' );
endif;
if ( $comment_footer ) : ?>
	<!-- Details Modal -->
	<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h3 class="modal-title"><?php esc_html_e( 'DETAILS', 'shopperexpress' ); ?></h3>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000">
							<path
								d="M480-424 284-228q-11 11-28 11t-28-11q-11-11-11-28t11-28l196-196-196-196q-11-11-11-28t11-28q11-11 28-11t28 11l196 196 196-196q11-11 28-11t28 11q11 11 11 28t-11 28L536-480l196 196q11 11 11 28t-11 28q-11 11-28 11t-28-11L480-424Z" />
						</svg>
					</button>
				</div>
				<div class="modal-body-wrap">
					<div class="modal-body">
						<div class="content-holder">
							<?php echo $comment_footer; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>

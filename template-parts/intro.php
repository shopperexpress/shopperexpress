<?php
$_id       = get_the_ID();
$post_type = get_post_type();

if ( is_singular( 'research' ) ) {
	$make  = get_the_terms( $_id, 'make-research' );
	$model = get_the_terms( $_id, 'model-research' );
} elseif ( in_array( get_post_type( $_id ), array( 'conditional-offers', 'lease-offers', 'finance-offers' ) ) ) {
	$make  = wps_get_term( $_id, 'make', '', 'field' );
	$model = wps_get_term( $_id, 'model', '', 'field' );
} else {
	$model = get_the_terms( $_id, 'model-' . $post_type );
	$make  = get_the_terms( $_id, 'make-' . $post_type );
}

$hide_header = false;

if ( $hide_header ) { ?>
	<div class="intro text-white" data-theme>
		<?php if ( get_field( 'intro_style', 'options' ) == 1 ) : ?>
			<div class="container-fluid">
				<div class="row">
					<div class="col-lg-3 item-col item-left">
						<?php
						if ( is_singular() ) :

							$link_btn = $text_btn = null;

							switch ( get_post_type() ) {

								case 'listings':
									if ( ! empty( $model ) ) {
										$link_btn = esc_url( add_query_arg( 'model[]', $model[0]->slug, get_post_type_archive_link( 'listings' ) ) );
										$text_btn = __( 'All', 'shopperexpress' ) . ' ' . $model[0]->name . ' ' . __( 'Inventory', 'shopperexpress' );
									}
									break;

								case 'offers':
									$link_btn = get_post_type_archive_link( 'offers' );
									$text_btn = __( 'All Offers', 'shopperexpress' );
									break;

								case 'research':
									if ( ! empty( $model ) ) {
										$link_btn = esc_url( add_query_arg( array( 'model-research[]' => $model[0]->slug ), get_post_type_archive_link( 'research' ) ) );
										$text_btn = __( 'All Research', 'shopperexpress' );
									}
									break;

								case 'service-offers':
									$link_btn = get_post_type_archive_link( 'service-offers' );
									$text_btn = __( 'All Offers', 'shopperexpress' );
									break;

							}
							if ( $link_btn && $text_btn ) :
								?>
								<p>
									<a href="<?php echo $link_btn; ?>">
										<?php echo esc_html( $text_btn ); ?>
									</a>
								</p>
								<?php
							endif;
						endif;
						?>
					</div>
					<div class="col-lg-6 text-center">
						<div class="text-holder">
							<?php
							$pid = get_page_by_title( 'Special Offers' );
							if ( is_post_type_archive( 'offers' ) ) {
								the_field( 'information', $pid->ID );
							} else {
								the_field( 'information', 'options' );
							}
							?>
							<h1>
								<?php
								switch ( get_post_type() ) {
									case 'service-offers':
										esc_html_e( 'Service Offers', 'shopperexpress' );
										break;

									default:
										if ( is_single() && ! empty( $make ) && ! empty( $model ) ) :
											if ( is_object( $make[0] ) && is_object( $make[0] ) ) {
												echo $make[0]->name . ' ' . $model[0]->name;
											} else {
												echo $make . ' ' . $model;
											}
									elseif ( is_post_type_archive( 'offers' ) ) :
											the_field( 'title', $pid->ID );
										else :
											the_field( 'title', 'options' );
									endif;
										break;
								}
								?>
							</h1>
						</div>
					</div>
					<?php
					if ( is_user_logged_in() ) :
						$user = wp_get_current_user();
						?>
						<div class="col-lg-3 item-col item-right">
							<span class="customer"><?php echo $user->user_email; ?> <strong class="customer-name"><?php echo the_field( 'zip', 'user_' . $user->ID ); ?></strong></span>
						</div>
					<?php endif; ?>
					<div class="col-12 btn-holder text-center">
						<?php
						switch ( get_post_type() ) {
							case 'service-offers':
								the_field( 'header_link-service-offers', 'options' );
								break;

							default:
								the_field( 'link_header', 'options' );
								break;
						}
						if ( $video_id = get_field( 'video_id', 'options' ) ) :
							?>
							<span class="wistia_embed wistia_async_<?php echo $video_id; ?> popover=true popoverContent=link" style="display: inline; position: relative;">
								<a class="btn btn-link" href="#"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="m426-330 195-125q14-9 14-25t-14-25L426-630q-15-10-30.5-1.5T380-605v250q0 18 15.5 26.5T426-330Zm54 250q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/></svg><?php _e( 'watch video', 'shopperexpress' ); ?></a>
							</span>
						<?php endif; ?>
					</div>
				</div>
				<?php if ( $powered = get_field( 'powered', 'options' ) ) : ?>
					<div class="col-12">
						<span class="by"><?php echo $powered; ?></span>
					</div>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<div class="container">
				<div class="row">
					<div class="col-md-10 col-lg-9 m-auto text-center">
						<div class="text-holder">
							<?php the_field( 'information', 'options' ); ?>
							<h1>
							<?php
							if ( is_single() ) :
								echo $make . ' ' . $model;
else :
	the_field( 'title', 'options' );
endif;
?>
</h1>
						</div>
					</div>
					<div class="col-12 btn-holder text-center">
						<?php
						if ( $link = get_field( 'link_header', 'options' ) ) {
							echo $link; }
						if ( $video_id = get_field( 'video_id', 'options' ) ) :
							?>
							<span class="wistia_embed wistia_async_<?php echo $video_id; ?> popover=true popoverAnimateThumbnail=true videoFoam=true popoverContent=link" style="display: inline; position: relative;">
								<a class="btn btn-link" href="#"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="m426-330 195-125q14-9 14-25t-14-25L426-630q-15-10-30.5-1.5T380-605v250q0 18 15.5 26.5T426-330Zm54 250q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/></svg><?php _e( 'watch video', 'shopperexpress' ); ?></a>
							</span>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endif ?>
	</div>
	<?php } ?>

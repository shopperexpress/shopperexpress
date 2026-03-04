<?php
$post_type = ! empty( $args['post_type'] ) ? $args['post_type'] : get_post_type();

if ( get_field( 'show_banner-' . $post_type, 'options' ) == true ) :
	if ( get_field( 'show-' . $post_type, 'options' ) == 1 ) :
		?>
		<div class="card-banner">
			<?php the_field( 'for_code-' . $post_type, 'options' ); ?>
		</div>
	<?php else : ?>
		<div class="se-sbp-widget">
			<div class="se-sbp-widget__holder">
				<div class="se-sbp-widget__header">
					<div class="se-sbp-widget__header-holder">
						<span class="se-sbp-widget__header-img">
							<img src="<?php echo App\asset_url( 'images/icon-label-white.svg' ); ?>" alt="image description">
						</span>
						<div class="se-sbp-widget__header-holder-text">
							<strong class="se-sbp-widget__title"><?php esc_html_e( 'Want to Shop By Payment?', 'shopperexpress' ); ?></strong>
							<strong class="se-sbp-widget__subtitle"><?php esc_html_e( 'Get a real payment on any vehicle of your choice.', 'shopperexpress' ); ?></strong>
						</div>
					</div>
					<div class="se-sbp-widget__label">
						<span class="se-sbp-widget__label-img">
							<img src="<?php echo App\asset_url( 'images/icon-lock.svg' ); ?>" alt="image description">
						</span>
						<div class="se-sbp-widget__label-holder">
							<span class="se-sbp-widget__label-text"><?php esc_html_e( 'NO S.S.N or D.O.B. NEEDED', 'shopperexpress' ); ?></span>
							<span class="se-sbp-widget__label-text"><?php esc_html_e( 'NO IMPACT TO YOUR CREDIT SCORE', 'shopperexpress' ); ?></span>
						</div>
					</div>
				</div>
				<div class="se-sbp-widget__body">
					<?php
					$body = ! empty( $_GET['body-style'] ) ? $_GET['body-style'] : null;
					if ( $body ) {
						$body = is_array( $body ) ? $body : explode( ',', $body );
					}
					while ( have_rows( 'widget', 'options' ) ) :
						the_row();
						?>
						<div class="se-sbp-widget__check-list">
							<?php
							foreach ( get_sub_field( 'body_style' ) as $term ) :
								?>
								<div class="se-sbp-widget__check-list-item">
									<label class="se-sbp-widget__check-list-label">
										<input class="se-sbp-widget__check" type="checkbox" name="body-style" value="<?php echo $term->slug; ?>" 
										<?php
										if ( $body && in_array( $term->slug, $body ) ) {
											checked( true );}
										?>
										>
										<?php echo $term->name; ?>
									</label>
								</div>
								<?php
							endforeach;
							?>
						</div>
					<?php endwhile; ?>
					<div class="se-sbp-widget__range">
						<div class="se-sbp-widget__range-holder">
							<h3 class="se-sbp-widget__range-title"><?php esc_html_e( 'Target Payment Range', 'shopperexpress' ); ?></h3>
							<input class="se-sbp-widget__range-input" type="text" readonly value="$450-$500">
						</div>
						<input class="se-sbp-widget__range-slider" id="se-sbp-widget-range" data-jcf='{"range": "min"}' data-currency="$" data-range-value="50" value="450" min="50" max="2000" step="1" type="range">
					</div>

					<div class="se-sbp-widget__btn-holder">
						<a class="se-sbp-widget__btn btn-shop-by-payment" onclick="javascript:inticeAllEvents.launchLOM('519','VEH-INTEREST VIN');" href="#"><?php esc_html_e( 'Get Pre-Qualified', 'shopperexpress' ); ?> &<span><?php esc_html_e( 'Shop by Payment', 'shopperexpress' ); ?></span></a>
					</div>
				</div>
			</div>
		</div>
		<?php
	endif;
endif;

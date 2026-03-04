<!-- Evo slider modal -->
<div class="popup-holder">
	<div id="evo-slider-modal" class="lightbox">
		<div class="evo-slider-wrapp">
			<div class="container">
				<div class="heading text-center mb-5">
					<img class="decor-icon" src="<?php echo esc_url( App\asset_url( 'images/360.svg' ) ); ?>" alt="image description" />
					<h3><?php esc_html_e( '360° Spin', 'shopperexpress' ); ?></h3>
				</div>
				<?php
					$evo_iframe_ration = get_field( 'evo_iframe_ration', 'option' );

					$width  = 'custom' === $evo_iframe_ration ? get_field( 'custom_width', 'option' ) : 1024;
					$height = 'custom' === $evo_iframe_ration ? get_field( 'custom_height', 'option' ) : 768;

				if ( have_rows( 'extspinvifs' ) ) :
					?>
					<div class="evo-slider-holder">
						<div class="evo-slider-btn-overlay">
							<span class="evo-slider-btn-click" aria-hidden="true">
								<img src="<?php echo esc_url( App\asset_url( 'images/click-and-drag.svg' ) ); ?>" alt="click and drag" />
							</span>
						</div>
						<div class="evo-slider">
							<?php
							while ( have_rows( 'extspinvifs' ) ) :
								the_row();
								$spin = get_sub_field( 'spin' );
								if ( $spin ) :
									?>
										<div class="slide">
											<iframe class="ratio-<?php echo esc_attr( $evo_iframe_ration ); ?>" src="<?php echo $spin; ?>" title="360 video" width="<?php echo esc_attr( $width ); ?>" height="<?php echo esc_attr( $height ); ?>" frameborder="0" loading="lazy"></iframe>
										</div>
										<?php
									endif;
								endwhile;
							?>
						</div>
					</div>
			<?php endif; ?>
			</div>
		</div>
	</div>
</div>

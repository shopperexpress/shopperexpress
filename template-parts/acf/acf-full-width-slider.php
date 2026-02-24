<?php
$remove_paddings = get_sub_field( 'remove_paddings' );
$slider_speed    = get_sub_field( 'slider_speed' ) ? get_sub_field( 'slider_speed' ) : 500;
$autoplay_speed  = get_sub_field( 'autoplay_speed' ) ? get_sub_field( 'autoplay_speed' ) : 5000;
if ( have_rows( 'slider' ) ) :
	?>
	<section class="section-full-width-slider
	<?php
	if ( $remove_paddings ) :
		?>
		py-0<?php endif; ?>">
		<div class="container">
			<div class="full-width-image-slider slick-item" data-speed="<?php echo esc_html( $slider_speed ); ?>" data-autoplay-speed="<?php echo esc_html( $autoplay_speed ); ?>">
				<?php
				$today = date( 'Ymd' );
				while ( have_rows( 'slider' ) ) :
					the_row();
					$image      = get_sub_field( 'image' );
					$start_date = get_sub_field( 'start_date' );
					$end_date   = get_sub_field( 'end_date' );
					$ariaLabel  = get_sub_field( 'arialabel' );

					if ( $image && $start_date && $end_date && $today >= $start_date && $today <= $end_date ) :
						$image_mobile    = get_sub_field( 'image_mobile' ) ?: $image;
						$url             = get_sub_field( 'url' );
						$open_in_new_tab = get_sub_field( 'open_in_new_tab' );

						ob_start();
						?>
						<picture>
							<source srcset="<?php echo esc_url( $image_mobile['url'] ); ?>" media="(max-width: 767px)" />
							<source srcset="<?php echo esc_url( $image['url'] ); ?>" />
							<?php echo wp_kses_post( get_attachment_image( $image['id'] ) ); ?>
						</picture>
						<?php
						$output = ob_get_clean();
						?>
						<div>
							<?php if ( $url ) : ?>
								<a href="<?php echo esc_url( $url ); ?>" 
								<?php
								if ( $open_in_new_tab ) :
									?>
									target="_blank" <?php endif; ?> aria-label="<?php echo esc_attr( $ariaLabel ); ?>">
									<?php echo $output; ?>
								</a>
							<?php else : ?>
								<?php echo $output; ?>
							<?php endif; ?>
						</div>
						<?php
					endif;
				endwhile;
				?>
			</div>
		</div>
	</section>
<?php endif; ?>

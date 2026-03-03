<?php
$post_type      = ! empty( $args['post_type'] ) ? $args['post_type'] : get_post_type();
$post_id        = get_the_id();
$vehicle_status = get_field( 'vehicle-status', $post_id );

if ( get_field( 'show_banner_single-' . $post_type, 'options' ) == true ) :

	$items = array();
	while ( have_rows( 'banner_single-' . $post_type . '_banner_slider_vdp', 'options' ) ) :
		the_row();
		$show   = true;
		$filter = get_sub_field( 'filter' );
		$value  = get_sub_field( 'value' );
		if ( $value && $field != 'none' ) {
			$field = get_field( $filter, $post_id );
			$show  = $field == $value ? true : false;
		}

		if ( get_sub_field( 'active' ) && $show ) :
			$items[] = array(
				'desktop_banner_image' => get_sub_field( 'desktop_banner_image' ),
				'mobile_banner_image'  => get_sub_field( 'mobile_banner_image' ),
				'landing_page_url'     => get_sub_field( 'landing_page_url' ),
				'alt_text'             => get_sub_field( 'alt_text' ),
				'open_in_new_tab'      => get_sub_field( 'open_in_new_tab' ),
			);
		endif;
	endwhile;

	if ( $items ) :
		?>
		<div class="visual visual--banner" data-banner>
			<div class="visual-holder">
				<div class="visual-slider visual-slider-srp slick-item" data-speed="500" data-autoplay-speed="5000">
					<?php
					foreach ( $items as $item ) :
						$desktop_banner_image = $item['desktop_banner_image'];
						$mobile_banner_image  = $item['mobile_banner_image'];
						$landing_page_url     = $item['landing_page_url'];
						$alt_text             = $item['alt_text'];
						$open_in_new_tab      = $item['open_in_new_tab'];

						?>
						<div class="slide">
							<a href="<?php echo esc_url( $landing_page_url ); ?>" aria-label="<?php echo esc_attr( $alt_text ); ?>" 
							<?php
							if ( $open_in_new_tab ) :
								?>
								target="_blank" <?php endif; ?>>
								<div class="bg-image bg-cover mobile-bg" style="background-image: url(<?php echo esc_url( $mobile_banner_image['url'] ); ?>)"></div>
								<div class="bg-image bg-cover desktop-bg" style="background-image: url(<?php echo esc_url( $desktop_banner_image['url'] ); ?>)"></div>
							</a>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
	endif;
endif;
?>

<?php
$buttons        = get_sub_field( 'buttons' );
$show_slider    = get_sub_field( 'show_slider' );
$sort_offers_by = get_sub_field( 'sort_offers_by' );
$html_offers    = $htmlField = '';
$no_margin      = get_sub_field( 'no_margin' );
$slider_speed   = get_sub_field( 'slider_speed' ) ? get_sub_field( 'slider_speed' ) : 500;
$autoplay_speed = get_sub_field( 'autoplay_speed' ) ? get_sub_field( 'autoplay_speed' ) : 5000;
ob_start();
if ( have_rows( 'custom_overlay' ) ) :
	?>
	<div class="row">
		<?php
		$i = 1;
		while ( have_rows( 'custom_overlay' ) ) :
			the_row();
			$overlay = get_sub_field( 'overlay' );
			while ( have_rows( 'group' ) ) :
				the_row();
				$active           = get_sub_field( 'active' );
				$start_date       = get_sub_field( 'start_date' );
				$end_date         = get_sub_field( 'end_date' );
				$landing_page_url = get_sub_field( 'landing_page_url' );
				$alt_text         = get_sub_field( 'alt_text' );

				$today = date( 'Ymd' );
				if ( $start_date && $end_date ) {
					if ( $today >= $start_date && $today <= $end_date ) {
						$today = true;
					} else {
						$today = false;
					}
				} else {
					$today = true;
				}
			endwhile;

			if ( $active && $today && $overlay && $i <= 2 ) :
				?>
				<div class="col">
					<a href="<?php echo esc_url( $landing_page_url ); ?>">
						<img src="<?php echo esc_url( $overlay['url'] ); ?>" alt="<?php echo esc_html( $alt_text ); ?>">
					</a>
				</div>
				<?php
				++$i;
			endif;
		endwhile;
		?>
	</div>
	<?php
endif;
$htmlField = ob_get_contents();
ob_end_clean();
if ( $show_slider != 2 ) {

	$args = array(
		'post_type'      => 'offers',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'field'          => 'ids',
	);
	switch ( $sort_offers_by ) {
		case 'date':
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
			break;
		case 'payment_lowest':
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = 'lease_payment';
			$args['order']    = 'ASC';
			break;
		case 'payment_highest':
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = 'lease_payment';
			$args['order']    = 'DESC';
			break;
		case 'priority':
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = 'priority';
			$args['order']    = 'ASC';
			break;
	}
	$query = new WP_Query( $args );

	if ( $query->posts ) {
		ob_start();
		foreach ( $query->posts as $post_id ) :
			?>
			<div class="slide">
				<a href="<?php echo get_permalink( $post_id ); ?>" aria-label="<?php esc_html_e( 'Shop now', 'shopperexpress' ); ?> <?php echo get_the_title( $post_id ); ?>">
					<div class="bg-image bg-cover mobile-bg" style="background-image: url(<?php echo get_field( 'intro_slider_img_sm', $post_id ); ?>)"></div>
					<div class="bg-image bg-cover desktop-bg" style="background-image: url(<?php echo get_field( 'intro_slider_img', $post_id ); ?>)"></div>
					<?php
					$intro_slider_overlay_sm = get_field( 'intro_slider_overlay_sm', $post_id );
					$intro_slider_overlay    = get_field( 'intro_slider_overlay', $post_id );

					if ( $intro_slider_overlay && $intro_slider_overlay_sm ) :
						?>
						<div class="image-overlay">
							<picture>
								<source srcset="<?php echo $intro_slider_overlay_sm; ?>" media="(max-width: 1024px)">
								<source srcset="<?php echo $intro_slider_overlay; ?>">
								<img src="<?php echo $intro_slider_overlay; ?>" alt="image description">
							</picture>
						</div>
					<?php endif; ?>
				</a>
				<?php if ( $htmlField ) : ?>
					<div class="slider-detail-content">
						<?php echo do_shortcode( $htmlField ); ?>
					</div>
				<?php endif; ?>
			</div>
			<?php
		endforeach;
		wp_reset_query();
		$html_offers .= ob_get_contents();
		ob_end_clean();
	}
}
$html_manually = '';

if ( $show_slider != 1 ) {
	ob_start();
	while ( have_rows( 'slider' ) ) :
		the_row();
		$start_date = get_sub_field( 'start_date' );
		$end_date   = get_sub_field( 'end_date' );
		$today      = date( 'Ymd' );
		if ( $start_date && $end_date ) {
			if ( $today >= $start_date && $today <= $end_date ) {
				$today = true;
			} else {
				$today = false;
			}
		} else {
			$today = true;
		}
		if ( get_sub_field( 'active' ) && $today ) :
			$landing_page_url         = get_sub_field( 'landing_page_url' );
			$mobile_image_url         = get_sub_field( 'mobile_image_url' );
			$desktop_background_image = get_sub_field( 'desktop_background_image' );
			$desktop_overlay_image    = get_sub_field( 'desktop_overlay_image' );
			$intro_slider_overlay_sm  = get_sub_field( 'intro_slider_overlay_sm' );
			$alt_text                 = get_sub_field( 'alt_text' );
			$video_type               = get_sub_field( 'video_type' );
			if ( $video_type == 'html5' ) {
				$video = get_sub_field( 'video_url' );
			} else {
				$video = get_sub_field( 'video_id' );
			}
			?>
			<div class="slide">
				<a href="<?php echo esc_url( $landing_page_url ); ?>" aria-label="<?php echo esc_attr( $alt_text ); ?>">
					<?php if ( $video ) : ?>
						<div data-video='{"type": "<?php echo $video_type; ?>", "video": "<?php echo $video; ?>", "title": "<?php the_sub_field( 'video_title' ); ?>", "autoplay": true, "loop": true}'></div>
					<?php elseif ( $mobile_image_url || $desktop_background_image ) : ?>
						<div class="bg-image bg-cover mobile-bg" style="background-image: url(<?php echo esc_url( $mobile_image_url['url'] ); ?>)"></div>
						<div class="bg-image bg-cover desktop-bg" style="background-image: url(<?php echo esc_url( $desktop_background_image['url'] ); ?>)"></div>
					<?php endif; ?>
					<?php if ( $desktop_overlay_image && $intro_slider_overlay_sm ) : ?>
						<div class="image-overlay">
							<picture>
								<source srcset="<?php echo $intro_slider_overlay_sm['url']; ?>" media="(max-width: 1024px)">
								<source srcset="<?php echo esc_url( $desktop_overlay_image['url'] ); ?>">
								<img src="<?php echo esc_url( $desktop_overlay_image['url'] ); ?>" alt="<?php echo esc_attr( $alt_text ); ?>">
							</picture>
						</div>
					<?php endif; ?>
				</a>
				<?php if ( $htmlField ) : ?>
					<div class="slider-detail-content">
						<?php echo do_shortcode( $htmlField ); ?>
					</div>
				<?php endif; ?>
			</div>
			<?php
		endif;
	endwhile;
	$html_manually .= ob_get_contents();
	ob_end_clean();
}

switch ( $show_slider ) {
	case 1:
		$html = $html_offers;
		break;
	case 2:
		$html = $html_manually;
		break;
	case 3:
		$html = $html_offers . $html_manually;
		break;
	case 4:
		$html = $html_manually . $html_offers;
		break;
}

if ( $html ) :
	?>
	<div class="visual
	<?php
	if ( $no_margin ) :
		?>
		m-0<?php endif; ?>">
		<div class="visual-holder">
			<div class="visual-slider slick-item" data-speed="<?php echo esc_html( $slider_speed ); ?>" data-autoplay-speed="<?php echo esc_html( $autoplay_speed ); ?>">
				<?php echo $html; ?>
			</div>
			<div class="slick-controls">
				<div class="buttons-holder">
					<button class="slick-control slick-play-pause" aria-label="<?php _e( 'Play/pause', 'shopperexpress' ); ?>">
						<svg class="indicator" viewBox="0 0 40 40">
							<circle class="progress-circle" cx="20" cy="20" r="16" fill="none" pathLength="40" style="stroke-dashoffset: 40"></circle>
						</svg>
						<span class="icon-play">
							<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
								<path
									d="M320-273v-414q0-17 12-28.5t28-11.5q5 0 10.5 1.5T381-721l326 207q9 6 13.5 15t4.5 19q0 10-4.5 19T707-446L381-239q-5 3-10.5 4.5T360-233q-16 0-28-11.5T320-273Z" />
							</svg>
						</span>
						<span class="icon-pause">
							<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
								<path
									d="M640-200q-33 0-56.5-23.5T560-280v-400q0-33 23.5-56.5T640-760q33 0 56.5 23.5T720-680v400q0 33-23.5 56.5T640-200Zm-320 0q-33 0-56.5-23.5T240-280v-400q0-33 23.5-56.5T320-760q33 0 56.5 23.5T400-680v400q0 33-23.5 56.5T320-200Z" />
							</svg>
						</span>
					</button>
				</div>
			</div>
			<div class="dots-holder slick-item"></div>
		</div>
		<div class="search-bar-container">
			<?php
			if ( get_sub_field( 'hide_search_form' ) != true ) {
				get_template_part( 'template-parts/search-form' );
			}
			?>
			<?php get_template_part( 'template-parts/spinning-icon-buttons' ); ?>
		</div>
	</div>
<?php endif; ?>

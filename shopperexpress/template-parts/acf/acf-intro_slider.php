<?php
/**
 * Flexible Content Wrapper: Intro Slider
 *
 * @package ShopperExpress
 */

$buttons        = get_sub_field( 'buttons' );
$show_slider    = get_sub_field( 'show_slider' );
$sort_offers_by = get_sub_field( 'sort_offers_by' );
$no_margin      = get_sub_field( 'no_margin' );
$slider_speed   = get_sub_field( 'slider_speed' ) ?: 500;
$autoplay_speed = get_sub_field( 'autoplay_speed' ) ?: 5000;

// Build overlay HTML.
$overlay_html = '';
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
$overlay_html = ob_get_clean();

// Build offers slides HTML.
$html_offers = '';
if ( $show_slider != 2 ) {
	$query_args = array(
		'post_type'      => 'offers',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'field'          => 'ids',
	);
	switch ( $sort_offers_by ) {
		case 'date':
			$query_args['orderby'] = 'date';
			$query_args['order']   = 'DESC';
			break;
		case 'payment_lowest':
			$query_args['orderby']  = 'meta_value_num';
			$query_args['meta_key'] = 'lease_payment';
			$query_args['order']    = 'ASC';
			break;
		case 'payment_highest':
			$query_args['orderby']  = 'meta_value_num';
			$query_args['meta_key'] = 'lease_payment';
			$query_args['order']    = 'DESC';
			break;
		case 'priority':
			$query_args['orderby']  = 'meta_value_num';
			$query_args['meta_key'] = 'priority';
			$query_args['order']    = 'ASC';
			break;
	}
	$query = new WP_Query( $query_args );

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
				<?php if ( $overlay_html ) : ?>
					<div class="slider-detail-content">
						<?php echo do_shortcode( $overlay_html ); ?>
					</div>
					<?php
				endif;

				$disclosure_slider = get_field( 'disclosure_slider', $post_id );

				if ( $disclosure_slider ) {
					get_template_part( 'template-parts/components/btn', 'disclosure', array( 'disclosure' => $disclosure_slider ) );
				}
				?>
			</div>
			<?php
		endforeach;
		wp_reset_query();
		$html_offers = ob_get_clean();
	}
}

// Build manual slides HTML.
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
				<?php if ( $overlay_html ) : ?>
					<div class="slider-detail-content">
						<?php echo do_shortcode( $overlay_html ); ?>
					</div>
					<?php
				endif;
				$disclosure = get_sub_field( 'disclosure' );

				if ( $disclosure ) {
					get_template_part( 'template-parts/components/btn', 'disclosure', array( 'disclosure' => $disclosure ) );
				}

				?>
			</div>
			<?php
		endif;
	endwhile;
	$html_manually = ob_get_clean();
}

get_template_part(
	'template-parts/acf-shared/intro-slider',
	null,
	array(
		'no_margin'          => $no_margin,
		'slider_speed'       => $slider_speed,
		'autoplay_speed'     => $autoplay_speed,
		'hide_search_form'   => get_sub_field( 'hide_search_form' ),
		'html_offers_slides' => $html_offers,
		'html_manual_slides' => $html_manually,
		'show_slider'        => $show_slider,
		'overlay_html'       => $overlay_html,
	)
);

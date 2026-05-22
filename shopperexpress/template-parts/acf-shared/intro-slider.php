<?php
/**
 * Shared: Intro Slider
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type bool   $no_margin           Add m-0 class.
 *   @type int    $slider_speed        Transition speed ms.
 *   @type int    $autoplay_speed      Autoplay interval ms.
 *   @type bool   $hide_search_form    Hide search form.
 *   @type string $html_offers_slides  Pre-rendered HTML for offer slides.
 *   @type string $html_manual_slides  Pre-rendered HTML for manual slides.
 *   @type int    $show_slider         1=offers only, 2=manual only, 3=offers+manual, 4=manual+offers.
 *   @type string $overlay_html        Pre-rendered overlay row HTML.
 * }
 */

$no_margin          = $args['no_margin'] ?? false;
$slider_speed       = $args['slider_speed'] ?? 500;
$autoplay_speed     = $args['autoplay_speed'] ?? 5000;
$hide_search_form   = $args['hide_search_form'] ?? false;
$html_offers_slides = $args['html_offers_slides'] ?? '';
$html_manual_slides = $args['html_manual_slides'] ?? '';
$show_slider        = $args['show_slider'] ?? 1;
$overlay_html       = $args['overlay_html'] ?? '';

switch ( $show_slider ) {
	case 1:
		$html = $html_offers_slides;
		break;
	case 2:
		$html = $html_manual_slides;
		break;
	case 3:
		$html = $html_offers_slides . $html_manual_slides;
		break;
	case 4:
		$html = $html_manual_slides . $html_offers_slides;
		break;
	default:
		$html = $html_offers_slides;
}

if ( $html ) :
	?>
	<div class="visual<?php echo $no_margin ? ' m-0' : ''; ?>">
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
			if ( ! $hide_search_form ) {
				get_template_part( 'template-parts/search-form' );
			}
			?>
			<?php get_template_part( 'template-parts/spinning-icon-buttons' ); ?>
		</div>
	</div>
<?php endif; ?>

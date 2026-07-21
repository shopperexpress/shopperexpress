<?php
/**
 * Template part for displaying the "X people have viewed this vehicle" badge.
 *
 * Rendered on new (listings) and used (used-listings) VDPs, right after the
 * payment-info block. Controlled by the "VDP View Count Badge" settings on
 * the Options: Listings admin page (active toggle + minimum view threshold,
 * configured separately for New and Used).
 *
 * @package Shopperexpress
 */

use App\Components\Base\Vehicle_Views;

$vin       = ! empty( $args['vin'] ) ? $args['vin'] : '';
$post_type = ! empty( $args['post_type'] ) ? $args['post_type'] : get_post_type();

if ( ! $vin ) {
	return;
}

$suffix = 'used-listings' === $post_type ? '_used' : '_new';

$active    = get_field( 'view_count_badge_active' . $suffix, 'option' );
$threshold = (int) get_field( 'view_count_badge_threshold' . $suffix, 'option' );

if ( ! $active ) {
	return;
}

$view_count = Vehicle_Views::get_view_count( $vin );

if ( $view_count <= $threshold ) {
	return;
}

$title_text = get_field( 'view_count_badge_title' . $suffix, 'option' );
$title_text = '' !== $title_text ? $title_text : __( 'This is going to sell soon.', 'shopperexpress' );

$subtitle_text = get_field( 'view_count_badge_subtitle' . $suffix, 'option' );
$subtitle_text = '' !== $subtitle_text ? $subtitle_text : __( '{count} people have recently viewed it.', 'shopperexpress' );
$subtitle_text = str_replace( '{count}', number_format_i18n( $view_count ), $subtitle_text );

$text_color  = get_field( 'view_count_badge_text_color' . $suffix, 'option' );
$font_size   = (int) get_field( 'view_count_badge_font_size' . $suffix, 'option' );
$font_weight = get_field( 'view_count_badge_font_weight' . $suffix, 'option' );
$font_family = get_field( 'view_count_badge_font_family' . $suffix, 'option' );

$title_style = array();
if ( $text_color ) {
	$title_style[] = 'color:' . $text_color;
}
if ( $font_size ) {
	$title_style[] = 'font-size:' . $font_size . 'px';
}
if ( $font_weight ) {
	$title_style[] = 'font-weight:' . $font_weight;
}
if ( $font_family ) {
	$title_style[] = 'font-family:"' . $font_family . '",sans-serif';
}
$title_style_attr = $title_style ? ' style="' . esc_attr( implode( ';', $title_style ) ) . '"' : '';

$subtitle_color       = get_field( 'view_count_badge_subtitle_color' . $suffix, 'option' );
$subtitle_font_size   = (int) get_field( 'view_count_badge_subtitle_font_size' . $suffix, 'option' );
$subtitle_font_weight = get_field( 'view_count_badge_subtitle_font_weight' . $suffix, 'option' );
$subtitle_font_family = get_field( 'view_count_badge_subtitle_font_family' . $suffix, 'option' );

$subtitle_style = array();
if ( $subtitle_color ) {
	$subtitle_style[] = 'color:' . $subtitle_color;
}
if ( $subtitle_font_size ) {
	$subtitle_style[] = 'font-size:' . $subtitle_font_size . 'px';
}
if ( $subtitle_font_weight ) {
	$subtitle_style[] = 'font-weight:' . $subtitle_font_weight;
}
if ( $subtitle_font_family ) {
	$subtitle_style[] = 'font-family:"' . $subtitle_font_family . '",sans-serif';
}
$subtitle_style_attr = $subtitle_style ? ' style="' . esc_attr( implode( ';', $subtitle_style ) ) . '"' : '';

$icon = get_field( 'view_count_badge_icon' . $suffix, 'option' );
?>
<div class="info-block__description view-count-badge">
	<div class="view-count-badge__text">
		<strong<?php echo $title_style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_attr() output above. ?>><?php echo esc_html( $title_text ); ?></strong>
		<span<?php echo $subtitle_style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_attr() output above. ?>><?php echo esc_html( $subtitle_text ); ?></span>
	</div>
	<?php if ( ! empty( $icon['url'] ) ) : ?>
		<?php render_acf_image_icon( $icon, 'view-count-badge__icon' ); ?>
	<?php else : ?>
		<svg class="view-count-badge__icon" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" width="32" height="32" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">
			<!-- knob -->
			<line x1="50" y1="20" x2="50" y2="26" />
			<circle cx="50" cy="16" r="4" stroke-width="3.5" />
			<!-- dial -->
			<circle cx="50" cy="58" r="32" />
			<circle cx="50" cy="58" r="26" />
			<!-- hour ticks -->
			<g stroke-width="3">
				<line x1="50" y1="34" x2="50" y2="30" />
				<line x1="50" y1="86" x2="50" y2="82" />
				<line x1="26" y1="58" x2="22" y2="58" />
				<line x1="78" y1="58" x2="82" y2="58" />
				<line x1="33" y1="41" x2="30" y2="38" />
				<line x1="70" y1="78" x2="73" y2="81" />
				<line x1="67" y1="41" x2="70" y2="38" />
				<line x1="30" y1="78" x2="27" y2="81" />
			</g>
			<!-- hands -->
			<line x1="50" y1="58" x2="50" y2="40" />
			<line x1="50" y1="58" x2="38" y2="66" />
			<!-- motion lines -->
			<line x1="24" y1="24" x2="18" y2="16" />
			<line x1="58" y1="14" x2="62" y2="6" />
			<line x1="88" y1="42" x2="96" y2="38" />
		</svg>
	<?php endif; ?>
</div>

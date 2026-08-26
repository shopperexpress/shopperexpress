<?php
/**
 * Block: Video Section
 *
 * Title: Video Section
 * Description: Product/video section with preferred layout and nested video articles.
 * Keywords: video section products preferred
 * Category: custom-acf-blocks
 * Icon: format-video
 *
 * @package ShopperExpress
 */

if ( \App\Components\Gutenberg\Block_Preview_Helper::render( $block ) ) {
	return;
}

if ( $is_preview ) {
	\App\Components\Gutenberg\Block_Preview_Helper::render( $block, true );
	return;
}

// Pre-render nested video article blocks.
$blocks_html = '';
if ( have_rows( 'blocks' ) ) {
	ob_start();
	while ( have_rows( 'blocks' ) ) {
		the_row();
		$content        = get_sub_field( 'content' );
		$code_for_video = get_sub_field( 'code_for_video' );
		$link           = get_sub_field( 'link' );

		if ( $content || $link || $code_for_video ) :
			?>
			<article class="product-article">
				<?php if ( $content || $link ) : ?>
					<div class="text-box">
						<?php
						echo $content;
						if ( $link ) {
							echo wps_get_link( $link, 'more' ); }
						?>
					</div>
					<?php
				endif;
				if ( $code_for_video ) :
					?>
					<div class="video-box">
						<?php echo $code_for_video; ?>
					</div>
				<?php endif; ?>
			</article>
			<?php
		endif;
	}
	$blocks_html = ob_get_clean();
}

get_template_part(
	'template-parts/acf-shared/intro-section',
	null,
	array(
		'is_video_section'   => true,
		'icon'               => get_field( 'icon' ),
		'title'              => get_field( 'title' ),
		'subtitle'           => get_field( 'subtitle' ),
		'image'              => get_field( 'image' ),
		'bottom_button_code' => get_field( 'bottom_button_code' ),
		'bottom_image'       => get_field( 'bottom_image' ),
		'first_image'        => get_field( 'first_image' ),
		'second_image'       => get_field( 'second_image' ),
		'thrid_image'        => get_field( 'thrid_image' ),
		'text'               => get_field( 'text' ),
		'link'               => get_field( 'link' ),
		'blocks_html'        => $blocks_html,
	)
);

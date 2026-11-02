<?php
/**
 * Template part for displaying detail slider
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Shopperexpress
 */

$args          = array(
	'post_type'  => ! empty( $args['post_type'] ) ? $args['post_type'] : get_post_type(),
	'evoxnumber' => ! empty( $args['evoxnumber'] ) ? $args['evoxnumber'] : null,
);
$get_post_type = ! empty( $args['post_type'] ) ? $args['post_type'] : get_post_type();
$evoxnumber    = ! empty( $args['evoxnumber'] ) ? $args['evoxnumber'] : null;

switch ( $get_post_type ) {
	case 'research':
		$slider_field = 'imagelistpath';
		$config_field = 'research_slider';
		$field        = 'image_url';
		break;

	default:
		$slider_field = 'imagelist';
		$config_field = 'service-offers_slider';
		$field        = 'image';
		break;
}

if ( have_rows( $slider_field ) ) :
	?>
	<div class="detail-slider-holder">
		<div class="detail-slider-wrapper">
			<?php
			$slider         = get_field( $config_field, 'options' );
			$autoplay       = ! empty( $slider['autoplay'] ) ? 'true' : 'false';
			$autoplay_speed = ! empty( $slider['autoplay_speed'] ) ? $slider['autoplay_speed'] * 60 * 60 : 3000;
			?>
			<div class="detail-slider" data-autoplay="<?php echo esc_attr( $autoplay ); ?>" data-autoplay-speed="<?php echo esc_attr( $autoplay_speed ); ?>">
				<?php
				while ( have_rows( $slider_field ) ) :
					the_row();
					$image = get_sub_field( $field );
					if ( $image ) :
						$image_background = get_sub_field( 'image_background' );
						?>
						<div class="slide bg-cover" style="background-image: url(<?php echo esc_url( get_field( 'background_image', 'option' ) ); ?>)">
							<a href="<?php echo esc_url( $image ); ?>" data-fancybox="img-gallery"><img src="<?php echo esc_url( $image ); ?>" alt="image"></a>
						</div>
						<?php
					endif;
				endwhile;
				?>
			</div>
		</div>
		<div class="detail-slider-nav">
			<button type="button" class="slider-nav-arrow slider-nav-prev" aria-label="<?php esc_attr_e( 'Previous thumbnails', 'shopperexpress' ); ?>">
				<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px"><path d="m432-480 156 156q11 11 11 28t-11 28q-11 11-28 11t-28-11L348-452q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l184-184q11-11 28-11t28 11q11 11 11 28t-11 28L432-480Z" /></svg>
			</button>
			<div class="slider-nav-wrap">
				<div class="slider-nav-holder">
					<?php
					while ( have_rows( $slider_field ) ) :
						the_row();
						$image = get_sub_field( $field );
						if ( $image ) :
							$image_background = get_sub_field( 'image_background' );
							?>
							<div class="slide bg-cover">
								<img src="<?php echo esc_url( $image ); ?>" srcset="<?php echo esc_url( $image ); ?> 2x" alt="image">
							</div>
							<?php
						endif;
					endwhile;
					?>
				</div>
			</div>
			<button type="button" class="slider-nav-arrow slider-nav-next" aria-label="<?php esc_attr_e( 'Next thumbnails', 'shopperexpress' ); ?>">
				<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px"><path d="M504-480 348-636q-11-11-11-28t11-28q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L404-268q-11 11-28 11t-28-11q-11-11-11-28t11-28l156-156Z" /></svg>
			</button>
		</div>
		<?php if ( have_rows( 'extspinvifs' ) ) : ?>
			<a href="#evo-slider-modal" data-type="iframe" data-fancybox class="btn-spin btn-explore-vehicle spin-evo" style="display: none;">
				<svg class="svg-360" x="0px" y="0px" viewBox="0 0 49 32" xml:space="preserve"><path d="M16.666 5.326c-0.332 0-0.656-0.053-0.972-0.158s-0.595-0.246-0.836-0.421l-0.57 1.113c0.308 0.223 0.675 0.393 1.104 0.511s0.862 0.176 1.302 0.176c0.609 0 1.106-0.095 1.492-0.285s0.674-0.441 0.864-0.755c0.19-0.314 0.285-0.651 0.285-1.013 0-0.542-0.201-0.988-0.601-1.339-0.281-0.245-0.659-0.398-1.125-0.472l1.427-1.626v-0.95h-4.431v1.176h2.697l-1.296 1.483v0.959h0.66c0.416 0 0.717 0.069 0.904 0.207s0.28 0.329 0.28 0.57c0 0.253-0.105 0.454-0.316 0.602s-0.5 0.221-0.868 0.221zM21.332 6.19c0.422 0.238 0.922 0.357 1.501 0.357 0.452 0 0.859-0.085 1.221-0.258s0.648-0.416 0.859-0.732c0.211-0.316 0.316-0.689 0.316-1.117 0-0.397-0.098-0.744-0.294-1.040s-0.457-0.524-0.782-0.687c-0.326-0.163-0.693-0.244-1.103-0.244-0.302 0-0.577 0.040-0.828 0.121s-0.466 0.202-0.646 0.362c-0.035 0.031-0.053 0.081-0.086 0.114 0.018-0.591 0.168-1.056 0.474-1.371 0.326-0.335 0.76-0.502 1.302-0.502 0.217 0 0.425 0.023 0.624 0.068s0.386 0.128 0.561 0.249l0.542-1.076c-0.223-0.139-0.488-0.246-0.796-0.321s-0.627-0.113-0.958-0.113c-0.639 0-1.201 0.133-1.687 0.398s-0.865 0.651-1.139 1.157c-0.274 0.507-0.412 1.127-0.412 1.863 0 0.675 0.116 1.247 0.348 1.713s0.559 0.821 0.981 1.058zM21.78 4.028c0.093-0.141 0.221-0.255 0.384-0.339s0.353-0.127 0.57-0.127c0.326 0 0.587 0.086 0.782 0.258s0.294 0.402 0.294 0.692c0 0.187-0.045 0.352-0.136 0.497s-0.216 0.255-0.375 0.33c-0.16 0.076-0.342 0.113-0.547 0.113-0.235 0-0.434-0.041-0.597-0.122s-0.29-0.193-0.38-0.335c-0.090-0.141-0.135-0.3-0.135-0.475 0-0.187 0.046-0.351 0.14-0.493zM29.873 0.384c-0.407-0.256-0.872-0.384-1.397-0.384-0.518 0-0.981 0.128-1.388 0.384s-0.73 0.627-0.968 1.113c-0.238 0.485-0.357 1.077-0.357 1.776s0.119 1.292 0.357 1.777c0.238 0.485 0.561 0.856 0.968 1.112s0.869 0.384 1.388 0.384c0.525 0 0.99-0.127 1.397-0.384s0.729-0.627 0.968-1.112c0.238-0.486 0.357-1.077 0.357-1.777s-0.119-1.291-0.357-1.776c-0.238-0.486-0.561-0.857-0.968-1.113zM29.556 4.44c-0.106 0.302-0.25 0.523-0.434 0.665s-0.399 0.212-0.646 0.212c-0.235 0-0.446-0.071-0.633-0.212s-0.334-0.364-0.443-0.665c-0.109-0.301-0.163-0.69-0.163-1.166s0.054-0.865 0.163-1.166c0.109-0.301 0.256-0.523 0.443-0.665s0.398-0.213 0.633-0.213c0.247 0 0.462 0.071 0.646 0.213s0.329 0.364 0.434 0.665c0.106 0.302 0.158 0.69 0.158 1.166s-0.053 0.865-0.158 1.166zM34.81 2.364c0.141-0.232 0.212-0.492 0.212-0.782s-0.071-0.551-0.212-0.787c-0.142-0.235-0.333-0.42-0.574-0.556s-0.51-0.204-0.805-0.204c-0.29 0-0.555 0.068-0.796 0.204s-0.433 0.321-0.574 0.556c-0.142 0.235-0.213 0.497-0.213 0.787s0.071 0.55 0.213 0.782c0.141 0.233 0.333 0.418 0.574 0.556s0.506 0.208 0.796 0.208c0.295 0 0.564-0.069 0.805-0.208s0.432-0.324 0.574-0.556zM33.865 2.311c-0.127 0.075-0.268 0.113-0.425 0.113-0.163 0-0.307-0.036-0.434-0.108s-0.226-0.171-0.298-0.294c-0.072-0.123-0.109-0.269-0.109-0.439s0.036-0.316 0.109-0.443c0.072-0.127 0.172-0.226 0.298-0.298s0.271-0.109 0.434-0.109c0.157 0 0.298 0.036 0.425 0.109s0.226 0.173 0.298 0.302c0.072 0.13 0.109 0.276 0.109 0.439s-0.036 0.306-0.109 0.43c-0.072 0.124-0.171 0.223-0.298 0.299zM13.624 15.943l3.163 3.163c-3.811-0.689-6.751-1.713-8.819-3.071s-3.102-2.524-3.102-3.497c0-1.297 1.733-2.838 5.2-4.622 0.962-0.495 2.033-0.914 3.194-1.272l-1.406-4.607c-1.716 0.515-3.334 1.138-4.829 1.895-4.683 2.372-7.025 5.241-7.025 8.606 0 2.798 1.683 5.271 5.048 7.42s7.643 3.568 12.833 4.257l-4.257 4.379 3.406 3.406 9.731-9.731-9.731-9.731-3.406 3.406zM41.631 3.932c-1.683-0.852-3.511-1.546-5.474-2.092l-2.013 4.444c1.663 0.418 3.145 0.962 4.446 1.631 3.467 1.784 5.2 3.325 5.2 4.622 0 0.933-0.841 1.997-2.524 3.193s-4.085 2.2-7.207 3.011v4.987c4.338-0.973 7.856-2.453 10.552-4.44s4.044-4.237 4.044-6.751c0-3.365-2.342-6.234-7.025-8.606z" />
				</svg>
				<span><?php esc_html_e( 'Explore This Vehicle', 'shopperexpress' ); ?></span>
			</a>
		<?php endif; ?>
	</div>
	<?php
endif;
?>

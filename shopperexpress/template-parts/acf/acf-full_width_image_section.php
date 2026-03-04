<?php
if ( $image = get_sub_field( 'image' ) ) :
	$image_mobile = get_sub_field( 'image_mobile' ) ? get_sub_field( 'image_mobile' ) : $image;
	?>
	<div class="section-full-width-image
	<?php
	if ( get_sub_field( 'remove_paddings' ) ) :
		?>
		py-0<?php endif; ?>">
		<div class="container">
						<div class="img-holder">
				<?php if ( $url = get_sub_field( 'url' ) ) : ?>
					<a href="<?php echo esc_url( $url ); ?>"
					<?php
					if ( get_sub_field( 'open_in_new_tab' ) ) :
						?>
						target="_blank"<?php endif; ?>>
						<picture>
							<source srcset="<?php echo esc_url( $image_mobile['url'] ); ?>" media="(max-width: 767px)" />
							<source srcset="<?php echo esc_url( $image['url'] ); ?>" />
							<?php echo wp_kses_post( get_attachment_image( $image['id'] ) ); ?>
						</picture>
					</a>
				<?php else : ?>
					<picture>
						<source srcset="<?php echo esc_url( $image_mobile['url'] ); ?>" media="(max-width: 767px)" />
						<source srcset="<?php echo esc_url( $image['url'] ); ?>" />
						<?php echo wp_kses_post( get_attachment_image( $image['id'] ) ); ?>
					</picture>
				<?php endif; ?>
			</div>
		</div>
	</div>
<?php endif; ?>

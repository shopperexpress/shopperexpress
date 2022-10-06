<section class="section-get-offer bg-cover"<?php if( $background_image = get_sub_field( 'background_image' ) ): ?> style="background-image: url(<?php echo $background_image['url']; ?>);"<?php endif; ?>>
	<div class="container">
		<div class="row">
			<?php if ( $text = get_sub_field( 'text' ) ): ?>
				<div class="col-md-12 text-white text-block">
					<?php echo $text; ?>
				</div>
			<?php endif; ?>
			<div class="col-md-12">
				<?php the_sub_field( 'for_code' ); ?>
			</div>
		</div>
	</div>
</section>
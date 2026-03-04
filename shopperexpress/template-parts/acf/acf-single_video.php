<!-- video section -->
<?php if ( $video_code = get_sub_field( 'video_code' ) ) : ?>
	<div class="video-section
	<?php
	if ( get_sub_field( 'remove_margin' ) ) :
		?>
		my-0<?php endif; ?>">
		<div class="container">
			<div class="video-block">
				<?php echo $video_code; ?>          
			</div>
		</div>
	</div>
<?php endif; ?>

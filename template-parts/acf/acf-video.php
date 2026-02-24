<?php 
$content = get_sub_field( 'content' );
$code_for_video = get_sub_field( 'code_for_video' );
$link = get_sub_field( 'link' );

if( $content || $link || $code_for_video ):
	?>
	<!-- product article -->
	<article class="product-article">
		<?php if ( $content || $link ): ?>
			<div class="text-box">
				<?php 
				echo $content;
				if( $link ){ echo wps_get_link( $link, 'more' ); }
				?>
			</div>
			<?php
		endif;
		if( $code_for_video ):
			?>
			<div class="video-box">
				<?php echo $code_for_video; ?>
			</div>
		<?php endif; ?>
	</article>
<?php endif; ?>

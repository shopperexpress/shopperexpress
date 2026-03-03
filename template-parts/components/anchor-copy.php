<ul class="anchor-list right-list">
	<li>
		<a href="#" onclick="jQuery('#copyLinkModal').modal('show');" data-toggle="tooltip" data-placement="top" title="Share this vehicle" aria-label="Share this vehicle">
			<img src="<?php echo App\asset_url( 'images/icon-share-black.svg' ); ?>" alt="image description" />
			<span class="decor-line" data-theme></span>
		</a>
	</li>
	<?php if ( shortcode_exists( 'favorite_button' ) && ! empty( $args['favorite'] ) ) : ?>
		<li>
			<?php
			ob_start();
			echo do_shortcode( '[favorite_button]' );
			$button_html = ob_get_clean();
			echo str_replace( '<button', '<button aria-label="Add to favorite" title="Add to favorite"', $button_html );
			?>
		</li>
	<?php endif; ?>
</ul>

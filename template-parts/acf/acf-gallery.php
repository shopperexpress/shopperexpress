<?php
$gray_background = get_sub_field( 'gray_background' ) ? ' bg-gray' : null;
$full_width      = get_sub_field( 'full_width' ) ? null : ' container';
$size            = $full_width ? array( 328, 250 ) : 'full';
$text            = get_sub_field( 'text' );
$images          = get_sub_field( 'gallery' );
$remove_paddings = get_sub_field( 'remove_paddings' );

if ( $images || $text ) :
	?>
	<section class="gallery-grid<?php echo $gray_background; ?>
	<?php
	if ( get_sub_field( 'remove_paddings' ) ) :
		?>
		py-0<?php endif; ?>">
		<?php
		echo $text;

		if ( $images ) :
			?>
			<ul class="gallery-grid__list list-unstyled<?php echo $full_width; ?>">
				<?php foreach ( $images as $image ) : ?>
					<li><a href="<?php echo esc_url( $image['url'] ); ?>" data-fancybox="grid-gallery-<?php the_row_index(); ?>"><?php echo wp_get_attachment_image( $image['id'], $size ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
<?php endif; ?>

<?php
$post_type  = ! empty( $args['post_type'] ) && ! in_array( $args['post_type'], array( 'listings', 'used-listings' ) ) ? $args['post_type'] . '_' : '';
$location   = ! empty( $args['location'] ) ? $args['location'] : '';
$vin_number = ! empty( $args['vin_number'] ) ? $args['vin_number'] : '';
if ( have_rows( $post_type . 'menu', 'options' ) ) : ?>
	<ul class="anchor-list">
		<?php
		while ( have_rows( $post_type . 'menu', 'options' ) ) :
			the_row();

			if ( $image = get_sub_field( 'image' ) ) :

				$event   = get_event_script( get_sub_field( 'event' ), $location, $vin_number );
				$tooltip = get_sub_field( 'tooltip' ) ? get_sub_field( 'tooltip' ) : $image['alt'];
				?>
				<li><a href="#" aria-label="<?php echo $tooltip; ?>" onclick="javascript:inticeAllEvents.<?php echo $event; ?>" data-toggle="tooltip" data-placement="top" title="<?php echo $tooltip; ?>"><?php echo wp_get_attachment_image( $image['id'], 'full' ); ?><span class="decor-line"></span></a></li>
				<?php
			endif;
		endwhile;
		?>
	</ul>
<?php endif; ?>

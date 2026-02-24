<?php
/**
 * Displays the appropriate image or gallery based on the post type and settings.
 *
 * @param string $post_type The type of post to check.
 * @param int    $post_id   The current post ID.
 */

$post_id         = ! empty( $args['post_id'] ) ? absint( $args['post_id'] ) : get_the_id();
$post_type       = ! empty( $args['post_type'] ) ? $args['post_type'] : get_post_type( $post_id );
$data_id         = ! empty( $args['data_id'] ) ? $args['data_id'] : '';
$use_images_list = get_field( 'use_images_list', $post_id );

$images_count = ! empty( $post_type ) && $post_type == 'used-listings'
	? get_field( 'images_count_used', 'options' )
	: get_field( 'images_count', 'options' );

// Default to 1 image if $images_count is empty.
$images_count = ! empty( $images_count ) ? absint( $images_count ) : 1;

// Retrieve the gallery or primary image based on $images_count.
if ( in_array( $post_type, array( 'finance-offers', 'lease-offers', 'conditional-offers' ) ) ) {
	if ( ! empty( $data_id ) ) {
		$primaryimageurl = get_field( 'thumbnail', $data_id );
	} else {
		$primaryimageurl = ! empty( get_field( 'gallery', $post_id )[0]['image_url'] ) ? get_field( 'gallery', $post_id )[0]['image_url'] : null;
	}
} else {
	$primaryimageurl = get_field( 'primaryimageurl', $post_id );
}
switch ( $post_type ) {
	case 'research':
		$primaryimageurl = array();
		$acf_field       = 'imagelistpath';
		$field           = 'image_url';
		$data_id         = $post_id;
		$images_count    = 2;
		$primaryimageurl = ! empty( get_field( 'imagelistpath', $post_id )[0]['image_url'] ) ? get_field( 'imagelistpath', $post_id ) : null;
		break;
	case 'offers':
	case 'finance-offers':
	case 'lease-offers':
	case 'conditional-offers':
		$acf_field    = 'gallery';
		$field        = 'image_url';
		$data_id      = $post_id;
		$images_count = 2;
		break;
	default:
		$acf_field = 'imageurllist';
		$field     = 'url';
		break;
}
if ( $images_count === 1 || get_field( 'use_primary_image_url', 'option' ) ) {
	$gallery = $primaryimageurl;
} elseif ( ! empty( $data_id ) ) {
	while ( have_rows( $acf_field, $data_id ) ) :
		the_row();
		if ( $url = get_sub_field( $field ) ) {
			$gallery[] = array(
				'image_url'        => $url,
				'image_background' => get_sub_field( 'image_background' ),
				'image_reverse'    => get_sub_field( 'image_reverse' ),
			);
		}
		endwhile;
} else {
	$gallery = 'alternative' === $use_images_list ? get_field( 'gallery_srp', $post_id ) : get_field( 'gallery', $post_id );
}

$vin_number = get_field( 'vin_number', $post_id );

if ( $vin_number ) {
	if ( ! empty( $gallery ) && ! empty( $gallery[0]['image_url'] ) ) {
		set_backup_images( $vin_number, $gallery );
	} else {
		$gallery = get_backup_images( $vin_number );
	}
}

?>
<div class="detail-slider-holder">
	<div class="detail-slider">
		<?php
		if ( ! empty( $gallery ) ) {
			if ( $images_count === 1 ) {
				$gallery = is_array( $gallery ) ? $gallery[0]['image_url'] : $gallery;
				?>
				<div class="slide">
					<a href="<?php echo get_the_permalink( $post_id ); ?>">
						<img src="<?php echo esc_url( str_replace( 'http://', 'https://', $gallery ) ); ?>" srcset="<?php echo esc_url( str_replace( 'http://', 'https://', $gallery ) ); ?> 2x" alt="<?php esc_attr_e( 'image description', 'shopperexpress' ); ?>">
					</a>
				</div>
				<?php
			} else {
				$i = 0;
				foreach ( $gallery as $index => $image ) {
					if ( ! empty( $image['image_url'] ) && ( $index + 1 ) <= $images_count ) {
						$image_background = $image['image_background'];
						$image_reverse    = $image['image_reverse'];
						?>
						<div class="slide
						<?php
						if ( $image_background ) :
							?>
							bg-cover" style="background-image: url(<?php echo get_field( 'background_image', 'option' ); ?>)<?php endif; ?>">
							<a href="<?php echo get_the_permalink( $post_id ); ?>" 
							<?php
							if ( $image_reverse ) :
								?>
								class="reverse-image" <?php endif; ?>>
								<img src="<?php echo esc_url( str_replace( 'http://', 'https://', $image['image_url'] ) ); ?>" srcset="<?php echo esc_url( str_replace( 'http://', 'https://', $image['image_url'] ) ); ?> 2x" alt="<?php esc_attr_e( 'image description', 'shopperexpress' ); ?>">
							</a>
						</div>
						<?php
						++$i;
					}
				}
				if ( 0 === $i ) {
					?>
						<a href="<?php echo get_the_permalink( $post_id ); ?>">
							<?php echo default_image( 'slide', $post_type ); ?>
						</a>
					<?php
				}
			}
		} else {
			?>
			<a href="<?php echo get_the_permalink( $post_id ); ?>">
				<?php echo default_image( 'slide', $post_type ); ?>
			</a>
			<?php
		}
		?>
	</div>
</div>

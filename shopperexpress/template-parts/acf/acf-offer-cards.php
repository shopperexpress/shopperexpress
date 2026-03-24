<?php
/**
 * Offer Cards
 *
 * @package ShopperExpress
 */

$show_offers_from = get_sub_field( 'show_offers_from' );
switch ( $show_offers_from ) {
	case 'both':
		$post_type = array( 'service-offers', 'offers' );
		break;
	default:
		$post_type = $show_offers_from;
		break;
}
$args = array(
	'post_type'      => $post_type,
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'fields'         => 'ids',
);

$query = new WP_Query( $args );

if ( $query->posts ) :
	?>
	<section class="section-offer-cards">
		<div class="container">
			<div class="offers-slider-wrap">
				<div class="offers-slider slick-item">
					<?php
					foreach ( $query->posts as $post_id ) :
						$image = '';
						$alt   = get_the_title( $post_id );
						switch ( get_post_type( $post_id ) ) {
							case 'service-offers':
								$image = get_field( 'offerimage', $post_id );
								break;
							case 'offers':
								$gallery = get_field( 'gallery', $post_id );
								$image   = ! empty( $gallery[0]['image_url'] ) ? $gallery[0]['image_url'] : '';
								break;
						}
						if ( $image ) :
							?>
							<div class="slide">
								<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
									<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $alt ); ?>" />
								</a>
							</div>
							<?php
						endif;
					endforeach;
					?>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>

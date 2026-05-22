<?php
/**
 * Shared: Offer Cards
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type string $show_offers_from  Post type slug or 'both'.
 *   @type string $offers_order      'offers_first' or 'service_first' (used when 'both').
 * }
 *
 * Note: WP_Query is intentionally inside this shared template because it
 * depends only on scalar config fields and cannot be meaningfully pre-fetched
 * without duplicating the entire query logic.
 */

$show_offers_from = $args['show_offers_from'] ?? 'offers';
$offers_order     = $args['offers_order'] ?? 'offers_first';
$final_ids        = array();

switch ( $show_offers_from ) {

	case 'both':
		$offers = get_posts(
			array(
				'post_type'      => 'offers',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$service_offers = get_posts(
			array(
				'post_type'      => 'service-offers',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		if ( 'offers_first' === $offers_order ) {
			$final_ids = array_merge( $offers, $service_offers );
		} else {
			$final_ids = array_merge( $service_offers, $offers );
		}
		break;

	default:
		$final_ids = get_posts(
			array(
				'post_type'      => $show_offers_from,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		break;
}

$query_args = array(
	'post_type'      => 'any',
	'post_status'    => 'publish',
	'post__in'       => $final_ids,
	'orderby'        => 'post__in',
	'posts_per_page' => -1,
	'fields'         => 'ids',
);

$query = new WP_Query( $query_args );

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

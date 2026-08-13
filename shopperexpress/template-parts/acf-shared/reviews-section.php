<?php
/**
 * Shared: Reviews Section
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type string $heading      Section heading.
 *   @type string $description  Section intro text (allows inline markup/links).
 *   @type string $layout_style "list" (default) or "slider".
 *   @type string $place_id     Google Place ID — resolved server-side from the connected
 *                               Business Profile location (not an ACF field; see
 *                               App\Components\Base\Google_Business_Reviews::get_settings()).
 *   @type string $cta_text     "Review us on Google" button label.
 *   @type string $cta_url      "Review us on Google" button link.
 * }
 *
 * Note: rating, stars, count and the review list itself are populated at
 * runtime by the GoogleReviews JS module (assets/src/js/static/app.js), which
 * calls the theme's own /wp-json/v1/google-reviews REST proxy (see
 * App\Components\Base\Google_Business_Reviews). That proxy prefers the
 * Business Profile API (real pagination) when connected, falling back to the
 * Places API (New) — capped at 5 reviews with no pagination — otherwise.
 */

$heading      = $args['heading'] ?? '';
$description  = $args['description'] ?? '';
$layout_style = ! empty( $args['layout_style'] ) ? $args['layout_style'] : 'list';
$place_id     = $args['place_id'] ?? '';
$cta_text     = $args['cta_text'] ?? '';
$cta_url      = $args['cta_url'] ?? '#';
$is_slider    = 'slider' === $layout_style;

if ( $place_id ) :
	// Review JSON-LD — server-rendered from the same (already 5-star + has-text
	// filtered) data the widget displays, so search engines see it without
	// needing to run the client-side Google Reviews JS module.
	$reviews_data = ( new \App\Components\Base\Google_Business_Reviews() )->get_reviews( $place_id );

	if ( ! is_wp_error( $reviews_data ) && ! empty( $reviews_data['reviews'] ) ) :
		$dealer_id   = esc_url( home_url( '/' ) ) . '#dealer';
		$dealer_name = wp_strip_all_tags( get_field( 'dealer_name', 'options' ) ?: get_bloginfo( 'name' ) );
		$dealer_url  = esc_url( get_field( 'dealer_url', 'options' ) ?: home_url( '/' ) );

		$graph = array(
			array(
				'@type' => 'AutoDealer',
				'@id'   => $dealer_id,
				'name'  => $dealer_name,
				'url'   => $dealer_url,
			),
		);

		foreach ( $reviews_data['reviews'] as $_i => $_review ) {
			$_published = $_review['publishTime'] ?? '';
			$graph[]    = array(
				'@type'         => 'Review',
				'@id'           => esc_url( home_url( '/' ) ) . '#review-' . ( $_i + 1 ),
				'itemReviewed'  => array( '@id' => $dealer_id ),
				'author'        => array(
					'@type' => 'Person',
					'name'  => wp_strip_all_tags( $_review['authorAttribution']['displayName'] ?? __( 'Google user', 'shopperexpress' ) ),
				),
				'datePublished' => $_published ? gmdate( 'Y-m-d', strtotime( $_published ) ) : gmdate( 'Y-m-d' ),
				'reviewBody'    => wp_strip_all_tags( $_review['text'] ?? '' ),
				'reviewRating'  => array(
					'@type'       => 'Rating',
					'ratingValue' => (int) ( $_review['rating'] ?? 5 ),
					'bestRating'  => 5,
					'worstRating' => 1,
				),
			);
		}

		$review_schema = array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);
		?>
		<script type="application/ld+json">
			<?php echo wp_json_encode( $review_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?>
		</script>
		<?php
	endif;
	?>
	<section
		class="review-section<?php echo $is_slider ? ' review-section--slider' : ''; ?>"
		data-google-reviews
		data-google-reviews-style="<?php echo esc_attr( $layout_style ); ?>"
		data-google-reviews-place-id="<?php echo esc_attr( $place_id ); ?>">
		<div class="container">
			<?php if ( ! $is_slider && ( $heading || $description ) ) : ?>
				<div class="review-section__heading">
					<?php if ( $heading ) : ?>
						<h1><?php echo esc_html( $heading ); ?></h1>
					<?php endif; ?>
					<?php if ( $description ) : ?>
						<p><?php echo wp_kses_post( $description ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<div class="widget-review__head">
				<div class="widget-review__head-holder">
					<div class="widget-review__head-row">
						<img src="<?php echo esc_url( \App\asset_url( 'images/google.svg' ) ); ?>" alt="Google" />
						<h3><?php echo esc_html( $heading ? $heading : __( 'Reviews', 'shopperexpress' ) ); ?></h3>
					</div>
					<div class="widget-review__head-row">
						<strong class="rating" data-google-reviews-rating></strong>
						<span class="google-reviews__stars" data-google-reviews-stars></span>
						<span class="count" data-google-reviews-count></span>
					</div>
				</div>
				<?php if ( ! $is_slider && $cta_text ) : ?>
					<a class="btn" href="<?php echo esc_url( $cta_url ); ?>" data-google-reviews-cta target="_blank" rel="noopener"><?php echo esc_html( $cta_text ); ?></a>
				<?php endif; ?>
			</div>
			<?php if ( $is_slider ) : ?>
				<div class="reviews-slider" data-google-reviews-list></div>
			<?php else : ?>
				<div class="widget-review">
					<div class="widget-review__list" data-google-reviews-list></div>
					<div class="widget-review__btn-row" data-load-more-holder hidden>
						<a class="btn" data-more-reviews-link target="_blank" rel="noopener"><?php esc_html_e( 'Show More Reviews', 'shopperexpress' ); ?></a>
					</div>
				</div>
			<?php endif; ?>
			<?php if ( $is_slider ) : ?>
				<script type="text/x-handlebars-template" data-google-reviews-template="reviews">
					{{#each reviews}}
						<div class='slick-slide'>
							<div class='review-item review-item--vertical'>
								<div class='review-item__body'>
									{{{stars}}}
									<p>{{text}}</p>
									{{#if googleMapsURI}}
										<a href='{{googleMapsURI}}' target='_blank' rel='noopener noreferrer'><?php esc_html_e( 'read more', 'shopperexpress' ); ?></a>
									{{/if}}
								</div>
								<div class='review-item__head'>
									<div class='review-item__avatar'>
										<img src='{{authorAttribution.photoURI}}' alt='{{authorAttribution.displayName}} photo' referrerpolicy='no-referrer' />
										<span class='review-item__source' data-toggle='tooltip' data-placement='top' title='Posted on Google'>
											<img src='<?php echo esc_url( \App\asset_url( 'images/google-logo.svg' ) ); ?>' alt='Google' />
										</span>
									</div>
									<div class='review-item__head-holder'>
										<strong class='review-item__name'>
											{{#if googleMapsURI}}
												<a href='{{googleMapsURI}}' target='_blank' rel='noopener noreferrer'>
													<span class='text'>{{authorAttribution.displayName}}</span>
												</a>
											{{else}}
												<span class='text'>{{authorAttribution.displayName}}</span>
											{{/if}}
											<span data-toggle='tooltip' data-placement='top' title='Verified Customer'>
												<img src='<?php echo esc_url( \App\asset_url( 'images/verified.svg' ) ); ?>' aria-hidden='true' alt='' />
											</span>
										</strong>
										<span class='review-item__info'>
											<span data-toggle='tooltip' data-placement='top' title='{{publishTime}}'>
												{{relativePublishTimeDescription}}
											</span>
										</span>
									</div>
								</div>
							</div>
						</div>
					{{else}}
						<p>No reviews were found for this place yet.</p>
					{{/each}}
				</script>
			<?php else : ?>
				<script type="text/x-handlebars-template" data-google-reviews-template="reviews">
					{{#each reviews}}
						<div class='review-item'>
							<div class='review-item__head'>
								<div class='review-item__avatar'>
									{{#if googleMapsURI}}
										<a href='{{googleMapsURI}}' target='_blank' rel='noopener noreferrer'>
											<img src='{{authorAttribution.photoURI}}' alt='{{authorAttribution.displayName}} photo' referrerpolicy='no-referrer' />
										</a>
									{{else}}
										<img src='{{authorAttribution.photoURI}}' alt='{{authorAttribution.displayName}} photo' referrerpolicy='no-referrer' />
									{{/if}}
								</div>
								<div class='review-item__head-holder'>
									<strong class='review-item__name'>
										{{#if googleMapsURI}}
											<a href='{{googleMapsURI}}' target='_blank' rel='noopener noreferrer'>
												<span class='text'>{{authorAttribution.displayName}}</span>
											</a>
										{{else}}
											<span class='text'>{{authorAttribution.displayName}}</span>
										{{/if}}
										<span data-toggle='tooltip' data-placement='top' title='Verified Customer'>
											<img src='<?php echo esc_url( \App\asset_url( 'images/verified.svg' ) ); ?>' aria-hidden='true' alt='' />
										</span>
									</strong>
									<span class='review-item__info'>
										<span data-toggle='tooltip' data-placement='top' title='{{publishTime}}'>
											{{relativePublishTimeDescription}}
										</span>
										{{#if googleMapsURI}}
											on
											<a href='{{googleMapsURI}}' target='_blank' rel='noopener noreferrer'>Google</a>
										{{/if}}
									</span>
								</div>
							</div>
							<div class='review-item__body'>
								{{{stars}}}
								<p>{{text}}</p>
							</div>
						</div>
					{{else}}
						<p>No reviews were found for this place yet.</p>
					{{/each}}
				</script>
			<?php endif; ?>
		</div>
	</section>
<?php endif; ?>

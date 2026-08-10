<?php
/**
 * Shared: Reviews Section
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type string $heading     Section heading.
 *   @type string $description Section intro text (allows inline markup/links).
 *   @type string $place_id    Google Place ID — resolved server-side from the connected
 *                             Business Profile location (not an ACF field; see
 *                             App\Components\Base\Google_Business_Reviews::get_settings()).
 *   @type string $cta_text    "Review us on Google" button label.
 *   @type string $cta_url     "Review us on Google" button link.
 * }
 *
 * Note: rating, stars, count and the review list itself are populated at
 * runtime by the GoogleReviews JS module (assets/src/js/static/app.js), which
 * calls the theme's own /wp-json/v1/google-reviews REST proxy (see
 * App\Components\Base\Google_Business_Reviews). That proxy prefers the
 * Business Profile API (real pagination) when connected, falling back to the
 * Places API (New) — capped at 5 reviews with no pagination — otherwise.
 */

$heading     = $args['heading'] ?? '';
$description = $args['description'] ?? '';
$place_id    = $args['place_id'] ?? '';
$cta_text    = $args['cta_text'] ?? '';
$cta_url     = $args['cta_url'] ?? '#';

if ( $place_id ) :
	?>
	<section class="review-section" data-google-reviews data-google-reviews-place-id="<?php echo esc_attr( $place_id ); ?>">
		<div class="container">
			<?php if ( $heading || $description ) : ?>
				<div class="review-section__heading">
					<?php if ( $heading ) : ?>
						<h1><?php echo esc_html( $heading ); ?></h1>
					<?php endif; ?>
					<?php if ( $description ) : ?>
						<p><?php echo wp_kses_post( $description ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<div class="widget-review">
				<div class="widget-review__head">
					<div class="widget-review__head-holder">
						<div class="widget-review__head-row">
							<img src="<?php echo esc_url( \App\asset_url( 'images/google.svg' ) ); ?>" alt="Google" />
							<h3><?php esc_html_e( 'Reviews', 'shopperexpress' ); ?></h3>
						</div>
						<div class="widget-review__head-row">
							<strong class="rating" data-google-reviews-rating></strong>
							<span class="google-reviews__stars" data-google-reviews-stars></span>
							<span class="count" data-google-reviews-count></span>
						</div>
					</div>
					<?php if ( $cta_text ) : ?>
						<a class="btn" href="<?php echo esc_url( $cta_url ); ?>" data-google-reviews-cta target="_blank" rel="noopener"><?php echo esc_html( $cta_text ); ?></a>
					<?php endif; ?>
				</div>
				<div class="widget-review__list" data-google-reviews-list></div>
				<div class="widget-review__btn-row" data-load-more-holder hidden>
					<a class="btn" data-more-reviews-link target="_blank" rel="noopener"><?php esc_html_e( 'Show More Reviews', 'shopperexpress' ); ?></a>
				</div>
			</div>
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
		</div>
	</section>
<?php endif; ?>

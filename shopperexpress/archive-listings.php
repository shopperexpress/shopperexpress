<?php
/**
 * Template Name: Listings Template
 *
 * @package Shopperexpress
 */

$post_type   = ! empty( $args['post_type'] ) ? $args['post_type'] : 'listings';
$page_id     = ! empty( $args['page_id'] ) ? $args['page_id'] : '';
$offer_types = array( 'lease-offers', 'finance-offers', 'conditional-offers' );

$query_params = array_filter(
	array(
		'loged'                         => is_user_logged_in() ? 'true' : null,
		'hide-duplicates-' . $post_type => ! empty( $_REQUEST[ 'hide-duplicates-' . $post_type ] ) ? $_REQUEST[ 'hide-duplicates-' . $post_type ] : null,
		'similar'                       => ! empty( $_REQUEST['similar'] ) ? 'true' : 'false',
	)
);

$query_string = ! empty( $query_params ) ? '?' . http_build_query( $query_params ) : '';
$key          = '';
$value        = '';
if ( ! empty( $args['template'] ) ) {
	$key   = get_field( 'key', $page_id );
	$value = get_field( 'value', $page_id );
}
get_header();
?>
<div class="filter-section" data-vehicles="<?php echo home_url( 'wp-json/v1/vehicles/' . $post_type . $query_string ); ?>"
<?php
if ( $key && $value ) :
	?>
	data-key="<?php echo esc_html( $key ); ?>" data-value="<?php echo esc_html( $value ); ?>" <?php endif; ?>>
	<div class="main-holder">
		<a class="filter-opener" href="#">
			<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#212529">
				<path d="M440-160q-17 0-28.5-11.5T400-200v-240L168-736q-15-20-4.5-42t36.5-22h560q26 0 36.5 22t-4.5 42L560-440v240q0 17-11.5 28.5T520-160h-80Z" />
			</svg>
			<span><?php esc_html_e( 'Filter', 'shopperexpress' ); ?></span></a>
		<aside class="aside">
			<?php if ( in_array( $post_type, $offer_types ) ) : ?>
				<ul class="offer-categories">
					<?php foreach ( $offer_types as $type ) : ?>
						<li
						<?php
						if ( $type == $post_type ) :
							?>
							class="active" <?php endif; ?>>
							<a href="<?php echo home_url( $type ); ?>" class="btn btn-block"><?php echo str_replace( '-', ' ', $type ); ?></a>
							</li>
						<?php endforeach; ?>
				</ul>
				<?php
			endif;
			if ( in_array( $post_type, array( 'listings', 'used-listings', 'lease-offers' ) ) ) :

				$label_1 = $post_type == 'lease-offers' ? esc_attr__( 'Starting MSRP', 'shopperexpress' ) : esc_attr__( 'Price', 'shopperexpress' );
				$label_2 = $post_type == 'lease-offers' ? esc_attr__( 'Lease Payment', 'shopperexpress' ) : esc_attr__( 'Payment', 'shopperexpress' );

				if ( $post_type != 'lease-offers' ) :
					?>
					<div class="filter-result">
						<span class="result-detail"><?php esc_html_e( 'Showing', 'shopperexpress' ); ?> <strong class="result-current">0</strong> <?php esc_html_e( 'of', 'shopperexpress' ); ?> <strong class="result-total">0</strong> <span data-singular-text="result" data-plural-text="results"></span> <?php esc_html_e( 'found', 'shopperexpress' ); ?></span>
						<button type="button" class="btn btn-secondary btn-block btn-reset"><?php esc_html_e( 'show all vehicles', 'shopperexpress' ); ?></button>
					</div>
				<?php endif; ?>
				<?php if ( $post_type != 'lease-offers' ) : ?>
					<div class="filter-row range-row">
						<label for="range-price" class="filter-title"><?php echo $label_1; ?></label>
						<div class="range-box">
							<input id="range-price" value="0,55500" min="0" max="55500" step="1" type="range" multiple />
							<div class="value-row">
								<input class="range-input min-price" type="text" />
								<span class="sep"></span>
								<input class="range-input max-price" type="text" />
								<div class="range-error"></div>
							</div>
							<input type="hidden" name="value" />
						</div>
					</div>
				<?php endif; ?>
				<div class="filter-row range-row">
					<label for="range-payment" class="filter-title"><?php echo $label_2; ?></label>
					<div class="range-box">
						<input id="range-price" value="0,55500" min="0" max="55500" step="1" type="range" multiple />
						<div class="value-row">
							<input class="range-input min-price" type="text" />
							<span class="sep"></span>
							<input class="range-input max-price" type="text" />
							<div class="range-error"></div>
						</div>
						<input type="hidden" name="payment" />
					</div>
				</div>
			<?php endif; ?>
			<ul class="filter-list list-unstyled">
				<?php
				foreach ( wps_tax( $post_type ) as $key => $item ) :
					$icon  = $item['icon'];
					$label = $item['label'];
					?>
					<li>
						<a data-tab="tab-<?php echo $key; ?>" href="#filterSchedule" data-modal>
							<?php echo $icon; ?>
							<span class="category-title"><?php echo $label; ?></span>
						</a>
					</li>
					<?php
				endforeach;
				?>
			</ul>
		</aside>
		<div class="card-wrapp container-fluid">
			<?php
			$get_page_id = ! empty( $page_id ) && is_page() ? $page_id : 'options';
			if ( $get_page_id ) :
				?>
				<div class="page-description">
					<?php
					if ( function_exists( 'yoast_breadcrumb' ) && function_exists( 'custom_yoast_breadcrumbs_as_ol' ) ) {
						custom_yoast_breadcrumbs_as_ol();
					}

					$text = get_field( 'text', $get_page_id ) ? get_field( 'text', $get_page_id ) : get_field( 'description_' . $post_type, $get_page_id );

					echo wp_kses_post( $text );
					?>
				</div>
							<?php
			endif;
			$placement_banner = get_field( 'placement_banner-' . $post_type, 'options' ) ? get_field( 'placement_banner-' . $post_type, 'options' ) : 'top';

			if ( $placement_banner == 'top' ) {
				get_template_part( 'template-parts/banner', null, array( 'post_type' => $post_type ) );
			}

			$search        = ! empty( $_GET[ 'model-' . $post_type ] ) ? $_GET[ 'model-' . $post_type ] : null;
			$manual_slider = $dynamic_slider = '';
			ob_start();
			while ( have_rows( $post_type . '_banner_slider', 'options' ) ) :
				the_row();
				$start_date = get_sub_field( 'start_date' );
				$end_date   = get_sub_field( 'end_date' );
				$today      = date( 'Y-m-d' );
				if ( $start_date && $end_date ) {
					if ( $today >= $start_date && $today <= $end_date ) {
						$today = true;
					} else {
						$today = false;
					}
				} else {
					$today = true;
				}
				$search_key_word      = get_sub_field( 'search_key_word' );
				$desktop_banner_image = get_sub_field( 'desktop_banner_image' );
				$mobile_banner_image  = get_sub_field( 'mobile_banner_image' );
				$landing_page_url     = get_sub_field( 'landing_page_url' );
				$alt_text             = get_sub_field( 'alt_text' );
				$open_in_new_tab      = get_sub_field( 'open_in_new_tab' );

				if ( $today && get_sub_field( 'active' ) ) :
					?>
					<div class="slide" data-key-word="<?php echo esc_attr( $search_key_word ); ?>">
						<a href="<?php echo esc_url( $landing_page_url ); ?>" aria-label="<?php echo esc_attr( $alt_text ); ?>"
						<?php
						if ( $open_in_new_tab ) :
							?>
							target="_blank" <?php endif; ?>>
							<div class="bg-image bg-cover mobile-bg" style="background-image: url(<?php echo esc_url( $mobile_banner_image['url'] ); ?>)"></div>
							<div class="bg-image bg-cover desktop-bg" style="background-image: url(<?php echo esc_url( $desktop_banner_image['url'] ); ?>)"></div>
						</a>
					</div>
					<?php
				endif;
			endwhile;
			$manual_slider = ob_get_contents();
			ob_end_clean();
			ob_start();
			if ( get_field( 'dynamic_offers-' . $post_type, 'options' ) ) :
				$get_post_type        = $post_type == 'service-offers' ? 'service-offers' : 'offers';
				$query                = new WP_Query(
					array(
						'post_type'      => $get_post_type,
						'posts_per_page' => -1,
						'fields'         => 'ids',
					)
				);
				$desktop_banner_image = $mobile_banner_image = $search_key_word = '';
				if ( $query->posts ) :
					foreach ( $query->posts as $id ) :

						$search_key_word      = get_field( 'srp_banner_keyword', $id );
						$desktop_banner_image = get_field( 'srp_banner_desktop_1920x200', $id );
						$mobile_banner_image  = get_field( 'srp_banner_mobile_1200x628', $id );

						$alt_text = get_field( 'alt_text' );

						if ( $mobile_banner_image && $desktop_banner_image ) :
							?>
							<div class="slide" data-key-word="<?php echo esc_attr( $search_key_word ); ?>">
								<a href="<?php echo esc_url( get_permalink( $id ) ); ?>" aria-label="<?php echo esc_attr( $alt_text ); ?>">
									<div class="bg-image bg-cover mobile-bg" style="background-image: url(<?php echo esc_url( $mobile_banner_image ); ?>)"></div>
									<div class="bg-image bg-cover desktop-bg" style="background-image: url(<?php echo esc_url( $desktop_banner_image ); ?>)"></div>
								</a>
							</div>
							<?php
						endif;
					endforeach;
				endif;
			endif;
			wp_reset_postdata();
			$dynamic_slider = ob_get_contents();
			ob_end_clean();

			$sliders = get_field( 'banner_placement-' . $post_type, 'options' ) == 'before' ? $dynamic_slider . $manual_slider : $manual_slider . $dynamic_slider;

			if ( $sliders ) :
				?>
				<div class="visual" data-banner>
					<div class="visual-holder">
						<div class="visual-slider visual-slider-srp slick-item" data-speed="500" data-autoplay-speed="5000">
							<?php echo $sliders; ?>
						</div>
					</div>
				</div>
			<?php endif; ?>
			<div class="search-panel">
				<div class="sticky-panel">
					<div class="search-row" data-url="<?php echo esc_url( home_url( 'wp-json/v1/search/?post_type=' . $post_type ) ); ?>">
						<div class="icon">
							<span>
								<svg viewBox="0 0 40 40">
									<path d="M3,3 L37,37"></path>
								</svg>
							</span>
						</div>
						<?php
						switch ( $post_type ) {
							case 'offers':
								$placeholder = esc_html__( 'Search Year, Make, Model or Body Style', 'shopperexpress' );
								break;

							case 'research':
								$placeholder = esc_html__( 'Search Year, Make, Model or Trims', 'shopperexpress' );
								break;

							case 'service-offers':
								$placeholder = esc_html__( 'Search Department, Type, Expiration or Title', 'shopperexpress' );
								break;
							case 'conditional-offers':
							case 'finance-offers':
							case 'lease-offers':
								$placeholder = esc_html__( 'Search By Make or Model', 'shopperexpress' );
								break;

							default:
								$placeholder = esc_html__( 'Search Makes, Models, Stock Number or VIN', 'shopperexpress' );
								break;
						}
						?>
						<input type="search" name="search" class="form-control form-control-lg autocomplete" placeholder="<?php echo $placeholder; ?>">
						<div class="results-search-drop search-drop">
							<div class="ajax-drop">
								<ul class="autocomplete-results"></ul>
							</div>
						</div>
					</div>
					<a class="btn-top anchor" role="button" href="#main" aria-label="<?php _e( 'Go to top', 'shopperexpress' ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
							<path
								d="M440-647 244-451q-12 12-28 11.5T188-452q-11-12-11.5-28t11.5-28l264-264q6-6 13-8.5t15-2.5q8 0 15 2.5t13 8.5l264 264q11 11 11 27.5T772-452q-12 12-28.5 12T715-452L520-647v447q0 17-11.5 28.5T480-160q-17 0-28.5-11.5T440-200v-447Z" />
						</svg>
					</a>
				</div>
				<div class="search-holder">
					<ul class="selected-filters-list"></ul>
					<div class="select-box">
						<label class="text" for="sort"><?php esc_html_e( 'sort by', 'shopperexpress' ); ?>:</label>
						<select id="sort" class="sort" name="sort" data-jcf='{"fakeDropInBody": false}'>
							<option value=""><?php esc_html_e( 'choose option', 'shopperexpress' ); ?></option>
							<?php
							if ( in_array( $post_type, array( 'conditional-offers', 'finance-offers', 'lease-offers' ) ) ) :
								?>
								<?php
								switch ( $post_type ) {
									case 'conditional-offers':
										?>
										<option value="highest-payment"><?php esc_html_e( 'Highest Offer', 'shopperexpress' ); ?></option>
										<option value="lowest-payment"><?php esc_html_e( 'Lowest Offer', 'shopperexpress' ); ?></option>
										<option value="model"><?php esc_html_e( 'Model Name', 'shopperexpress' ); ?></option>
										<?php
										break;
									case 'finance-offers':
										?>
										<option value="highest-payment"><?php esc_html_e( 'Highest APR', 'shopperexpress' ); ?></option>
										<option value="lowest-payment"><?php esc_html_e( 'Lowest APR', 'shopperexpress' ); ?></option>
										<option value="model"><?php esc_html_e( 'Model Name', 'shopperexpress' ); ?></option>
										<?php
										break;
									case 'lease-offers':
										?>
										<option value="highest-payment"><?php esc_html_e( 'Highest Payment', 'shopperexpress' ); ?></option>
										<option value="lowest-payment"><?php esc_html_e( 'Lowest Payment', 'shopperexpress' ); ?></option>
										<option value="model"><?php esc_html_e( 'Model Name', 'shopperexpress' ); ?></option>
										<?php
										break;
								}
								?>
							<?php else : ?>
								<?php if ( ! is_post_type_archive( 'offers' ) && ! is_post_type_archive( 'service-offers' ) ) : ?>
									<option value="highest"><?php esc_html_e( 'Highest Price', 'shopperexpress' ); ?></option>
									<option value="lowest"><?php esc_html_e( 'Lowest Price', 'shopperexpress' ); ?></option>
								<?php else : ?>
									<option value="priority"><?php esc_html_e( 'Priority', 'shopperexpress' ); ?></option>
								<?php endif; ?>
								<option value="highest-payment"><?php esc_html_e( 'Highest Payment', 'shopperexpress' ); ?></option>
								<option value="lowest-payment"><?php esc_html_e( 'Lowest Payment', 'shopperexpress' ); ?></option>
								<option value="newest"><?php esc_html_e( 'Newest First', 'shopperexpress' ); ?></option>
								<option value="oldest"><?php esc_html_e( 'Oldest First', 'shopperexpress' ); ?></option>
								<?php if ( is_post_type_archive( 'listings' ) ) : ?>
									<option value="unique"><?php esc_html_e( 'Show Unique Only', 'shopperexpress' ); ?></option>
									<option value="dateinstock-new"><?php esc_html_e( 'Newest in stock', 'shopperexpress' ); ?></option>
									<option value="dateinstock-old"><?php esc_html_e( 'Oldest in stock', 'shopperexpress' ); ?></option>
									<option value="all"><?php esc_html_e( 'Show All', 'shopperexpress' ); ?></option>
								<?php endif; ?>
							<?php endif; ?>
						</select>
					</div>
				</div>
			</div>
			<?php $class = in_array( $post_type, $offer_types ) ? 'd-grid-row' : 'row'; ?>
			<div class="<?php echo esc_attr( $class ); ?>"></div>
			<div class="loader-holder">
				<div class="loader"><?php esc_html_e( 'Loading', 'shopperexpress' ); ?>...</div>
			</div>
			<div class="pagination-holder">
				<div class="pagination">
					<a href="#" class="btn-prev">
						<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
							<path
								d="m432-480 156 156q11 11 11 28t-11 28q-11 11-28 11t-28-11L348-452q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l184-184q11-11 28-11t28 11q11 11 11 28t-11 28L432-480Z" />
						</svg>
						<span class="sr-only"><?php esc_html_e( 'Previous page', 'shopperexpress' ); ?></span></a>
					<div class="pagination-text"><?php esc_html_e( 'Page', 'shopperexpress' ); ?> <span class="curr-page">1</span> of <span class="total-pages"></span></div>
					<a href="#" class="btn-next">
						<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
							<path
								d="M504-480 348-636q-11-11-11-28t11-28q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L404-268q-11 11-28 11t-28-11q-11-11-11-28t11-28l156-156Z" />
						</svg>
						<span class="sr-only"><?php esc_html_e( 'Next page', 'shopperexpress' ); ?></span></a>
				</div>
			</div>
			<?php
			get_template_part(
				'template-parts/accordion',
				null,
				array(
					'post_type' => $post_type,
					'page_id'   => $page_id,
				)
			);
			if ( 'bottom' === $placement_banner ) {
				get_template_part( 'template-parts/banner', null, array( 'post_type' => $post_type ) );
			}
			if ( 'listings' === $post_type || ( is_post_type_archive( 'listings' ) || is_post_type_archive( 'offers' ) ) ) {
				echo wp_kses_post( do_shortcode( get_field( 'comment_footer', 'options' ) ) );
			} elseif ( 'used-listings' === $post_type || is_post_type_archive( 'used-listings' ) ) {
				echo wp_kses_post( do_shortcode( get_field( 'used_listings_comment_footer', 'options' ) ) );
			}
			?>
		</div>
	</div>
</div>
<?php get_footer(); ?>

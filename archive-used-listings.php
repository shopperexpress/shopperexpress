<?php
/*
Template Name: Listings Template
*/
get_header();

// $permalink = get_post_type_archive_link('listings');

$listings_name = str_replace(get_home_url(), '', get_post_type_archive_link('used-listings'));
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
	$protocol = 'https://';
} else {
	$protocol = 'http://';
}

$post_type = 'used-listings';

$permalink = $protocol . $_SERVER['HTTP_HOST'] . $listings_name;

$search = !empty($_GET['search']) ? $_GET['search'] : null;

$args = array(
	'post_type'   => $post_type,
	'post_status' => 'publish',
	'ignore_sticky_posts' => true,
	'posts_per_page'         => -1,
	's' => $search
);

$query = new WP_Query( $args );

$price = $payment = [];

while ( $query->have_posts() ) : $query->the_post();
	$price[] = get_field( 'price' );
	$payment[wps_get_term( get_the_id(), 'lease-payment_' . $post_type)] = wps_get_term( get_the_id(), 'lease-payment_' . $post_type);
	$payment[wps_get_term( get_the_id(), 'loan-payment_' . $post_type)] = wps_get_term( get_the_id(), 'loan-payment_' . $post_type);
endwhile;
wp_reset_query();
$get_query = wps_listings( 1 );
?>
<form action="<?php echo $permalink; ?>" class="filter-section">
	<input type="hidden" name="ptype" value="<?php echo $post_type; ?>">
	<div class="main-holder">
		<a class="filter-opener" href="#"><i class="material-icons"><?php _e('filter_alt','shopperexpress'); ?></i></a>
		<aside class="aside">
			<div class="filter-result">
				<span class="result-detail">Showing <strong class="result-current"><?php if ( $get_query > 0 ) : echo 1; else : 0; endif; ?>-<?php if ( $get_query > 23 ) : echo 24; else: echo $get_query; endif; ?></strong> of <strong class="result-total"><?php echo $get_query; ?></strong> Vehicles</span>
				<button type="button" class="btn btn-secondary btn-block btn-reset"><?php _e('show all vehicles','shopperexpress'); ?></button>
			</div>
			<?php
			if ( !empty($price) && count(array_unique($price)) > 1 && is_float(floatval(min(array_filter($price)))) ) :
				$val = explode(',',$_GET['value']);
			$val_min = !empty( $val[0] ) ? number_format($val[0]) : number_format(min(array_filter($price)));
			$val_max = !empty( $val[1] ) ? number_format($val[1]) : number_format(max($price));
			?>
			<div class="filter-row range-row">
				<label for="range-price" class="filter-title"><?php _e('Price','shopperexpress'); ?></label>
				<div class="range-box">
					<div class="value-row">
						<span class="range-value">$<span class="min-price"><?php echo $val_min; ?></span></span>
						<span class="range-value">$<span class="max-price"><?php echo $val_max; ?></span></span>
					</div>
					<?php $val_1 = !empty( $val[0] ) ? $val[0] . ', ' . $val[1] : intval(min(array_filter($price))) . ', ' . intval(max($price)); ?>
					<input id="range-price" value="<?php echo $val_1; ?>" min="<?php echo str_replace(',', '',number_format(min(array_filter($price)))); ?>" max="<?php echo max($price); ?>" step="10" type="range" multiple >
					<input type="hidden" name="value">
				</div>
			</div>
			<?php
		endif;
		if ( !empty($payment) && count(array_unique($payment)) > 2 && is_float(floatval(min(array_filter($payment)))) ) :
			$val = explode(',',$_GET['payment']);
		$payment_min = intval(min(array_filter($payment)));
		$payment_max = intval(max($payment));
		$val_min = !empty( $val[0] ) ? $val[0] : number_format(min(array_filter($payment)));
		$val_max = !empty( $val[1] ) ? $val[1] : number_format(max($payment));
		$val_min = intval($val_min) >= $payment_min && intval($val_min) <= $payment_max ? $val_min : $payment_min;
		$val_max = intval($val_max) <= $payment_max && intval($val_max) >= $payment_min ? $val_max : $payment_max;
		?>
		<div class="filter-row range-row">
			<label for="range-payment" class="filter-title"><?php _e('Payment','shopperexpress'); ?></label>
			<div class="range-box">
				<div class="value-row">
					<span class="range-value">$<span class="min-price"><?php echo number_format( $payment_min ); ?></span></span>
					<span class="range-value">$<span class="max-price"><?php echo number_format( $payment_max ); ?></span></span>
				</div>
				<?php $val_1 = !empty( $val_min ) ? $val_min . ', ' . $val_max : $payment_min . ', ' . $payment_max; ?>
				<input id="range-payment" value="<?php echo $val_1; ?>" min="<?php echo $payment_min; ?>" max="<?php echo $payment_max; ?>" step="10" type="range" multiple >
				<input type="hidden" name="payment">
			</div>
		</div>
	<?php endif; ?>
	<?php
	new_filter();
	?>
</aside>

<div class="card-wrapp container-fluid">
	<?php
	if ( get_field( 'show_banner', 'options' ) == true ) :
		if ( get_field( 'show', 'options' ) == 1 ) :
			?>
			<div class="card-banner">
				<?php the_field( 'for_code_listings', 'options' ); ?>
			</div>
		<?php else : ?>
			<div class="se-sbp-widget">
				<div class="se-sbp-widget__holder">
					<div class="se-sbp-widget__header">
						<div class="se-sbp-widget__header-holder">
							<span class="se-sbp-widget__header-img">
								<img src="<?php echo get_template_directory_uri(); ?>/images/icon-label-white.svg" alt="image description">
							</span>
							<div class="se-sbp-widget__header-holder-text">
								<strong class="se-sbp-widget__title"><?php esc_html_e( 'Want to Shop By Payment?', 'shopperexpress' ); ?></strong>
								<strong class="se-sbp-widget__subtitle"><?php esc_html_e( 'Get a real payment on any vehicle of your choice.', 'shopperexpress' ); ?></strong>
							</div>
						</div>
						<div class="se-sbp-widget__label">
							<span class="se-sbp-widget__label-img">
								<img src="<?php echo get_template_directory_uri(); ?>/images/icon-lock.svg" alt="image description">
							</span>
							<div class="se-sbp-widget__label-holder">
								<span class="se-sbp-widget__label-text"><?php esc_html_e( 'NO S.S.N or D.O.B. NEEDED', 'shopperexpress' ); ?></span>
								<span class="se-sbp-widget__label-text"><?php esc_html_e( 'NO IMPACT TO YOUR CREDIT SCORE', 'shopperexpress' ); ?></span>
							</div>
						</div>
					</div>
					<div class="se-sbp-widget__body">
						<?php
						$body = !empty( $_GET['body-style'] ) ? $_GET['body-style'] : null;
						if ( $body ) {
							$body = is_array( $body ) ? $body : explode( ',', $body );
						}
						while ( have_rows( 'widget', 'options' ) ) : the_row();
							?>
							<div class="se-sbp-widget__check-list">
								<?php
								foreach ( get_sub_field( 'body_style' ) as $term ) :
									?>
									<div class="se-sbp-widget__check-list-item">
										<label class="se-sbp-widget__check-list-label">
											<input class="se-sbp-widget__check" type="checkbox" name="body-style" value="<?php echo $term->slug; ?>" <?php if ( $body && in_array( $term->slug, $body) ) checked( true );  ?>>
											<?php echo $term->name; ?>
										</label>
									</div>
									<?php
								endforeach;
								?>
							</div>
						<?php endwhile; ?>
						<?php if ( !empty($payment) && count(array_unique($payment)) > 2 ) : ?>
						<div class="se-sbp-widget__range">
							<div class="se-sbp-widget__range-holder">
								<h3 class="se-sbp-widget__range-title"><?php esc_html_e( 'Target Payment Range', 'shopperexpress' ); ?></h3>
								<input class="se-sbp-widget__range-input" type="text" readonly value="$450-$500">
							</div>
							<input class="se-sbp-widget__range-slider" id="se-sbp-widget-range" data-jcf='{"range": "min"}' data-currency="$" data-range-value="50" value="450" min="<?php echo intval(min(array_filter($payment))); ?>" max="<?php echo intval(max($payment)); ?>" step="10" type="range">
						</div>
					<?php endif; ?>
					<div class="se-sbp-widget__btn-holder">
						<a class="se-sbp-widget__btn btn-shop-by-payment" onclick="javascript:inticeAllEvents.launchLOM('519','VEH-INTEREST VIN');" href="#"><?php esc_html_e( 'Get Pre-Qualified', 'shopperexpress' ); ?> &<span><?php esc_html_e( 'Shop by Payment', 'shopperexpress' ); ?></span></a>
					</div>
				</div>
				<!-- <span class="se-sbp-widget__info"><?php esc_html_e( 'Skip pre-qualify', 'shopperexpress' ); ?> <a href="#"><?php esc_html_e( 'and just shop by payment', 'shopperexpress' ); ?></a>.</span> -->
			</div>
		</div>
		<?php
	endif;
endif;
?>
<div class="search-panel">
	<div class="sticky-panel">
		<div class="search-row" data-action="<?php echo add_query_arg( ['autocomplete' => 1] , $permalink ); ?>">
			<i class="icon material-icons">search</i>
			<input type="search" name="search" class="form-control form-control-lg autocomplete" data-src="<?php echo add_query_arg( ['autocomplete' => 1] , $permalink ); ?>" placeholder="<?php _e('Search Makes, Models or Keywords','shopperexpress'); ?>">
			<div class="ajax-drop">
				<strong><?php _e('sugestions','shopperexpress'); ?></strong>
				<ul class="autocomplete-results"></ul>
			</div>
		</div>
		<a class="btn-top anchor" href="#main"><i class="material-icons">arrow_upward</i></a>
	</div>
	<div class="search-holder">
		<ul class="selected-filters-list"></ul>
		<div class="select-box">
			<span class="text"><?php _e('sort by:','shopperexpress'); ?></span>
			<select class="sort" name="sort" data-jcf='{"fakeDropInBody": false}'>
				<option value=""><?php _e('choose option','shopperexpress'); ?></option>
				<option value="highest"><?php _e('Highest Price','shopperexpress'); ?></option>
				<option value="lowest"><?php _e('Lowest Price','shopperexpress'); ?></option>
				<option value="recommended"><?php _e('Recommended','shopperexpress'); ?></option>
			</select>
		</div>
	</div>
</div>
<div class="row" id="load">
	<?php wps_listings( null, $post_type ); ?>
</div>
<?php if ( $query->max_num_pages > 1 ): ?>
	<div class="loader-holder">
		<div class="loader"><?php _e('Loading','shopperexpress'); ?>...</div>
	</div>
<?php endif; ?>
</div>
</div>
</form>
<?php
add_action('wp_footer', 'new_filter_modal');
get_footer();
?>

<?php
/*
Template Name: Listings Template
*/
get_header();

$permalink = get_post_type_archive_link('listings');
$search = !empty($_GET['search']) ? $_GET['search'] : null;

$args = array(
	'post_type'   => 'listings',
	'post_status' => 'publish',
	'ignore_sticky_posts' => true,
	'posts_per_page'         => -1,
	's' => $search
);

$query = new WP_Query( $args );

$price = $payment = [];

while ( $query->have_posts() ) : $query->the_post();
	$price[] = get_field( 'price' );
	$payment[wps_get_term( get_the_id(), 'lease-payment')] = wps_get_term( get_the_id(), 'lease-payment');
	$payment[wps_get_term( get_the_id(), 'loan-payment')] = wps_get_term( get_the_id(), 'loan-payment');
endwhile;
wp_reset_query();
?>
<form action="<?php echo $permalink; ?>" class="filter-section">
	<div class="main-holder">
		<a class="filter-opener" href="#"><i class="material-icons"><?php _e('filter_alt','shopperexpress'); ?></i></a>
		<aside class="aside">
			<?php if ( !empty($price) && count(array_unique($price)) > 1 ) : ?>
				<div class="filter-row range-row">
					<label for="range-price" class="filter-title"><?php _e('Price','shopperexpress'); ?></label>
					<div class="range-box">
						<div class="value-row">
							<span class="range-value">$<span class="min-price"><?php echo number_format(min(array_filter($price))); ?></span></span>
							<span class="range-value">$<span class="max-price"><?php echo number_format(max($price)); ?></span></span>
						</div>
						<input id="range-price" value="<?php echo str_replace(',', '',number_format(min(array_filter($price)))); ?>, <?php echo max($price); ?>" min="<?php echo str_replace(',', '',number_format(min(array_filter($price)))); ?>" max="<?php echo max($price); ?>" step="10" type="range" multiple >
						<input type="hidden" name="value">
					</div>
				</div>
				<?php
			endif;
			if ( !empty($payment) && count(array_unique($payment)) > 1 ) :
				?>
				<div class="filter-row range-row">
					<label for="range-payment" class="filter-title"><?php _e('Payment','shopperexpress'); ?></label>
					<div class="range-box">
						<div class="value-row">
							<span class="range-value">$<span class="min-price"><?php echo number_format(min(array_filter($payment))); ?></span></span>
							<span class="range-value">$<span class="max-price"><?php echo number_format(max($payment)); ?></span></span>
						</div>
						<input id="range-payment" value="<?php echo intval(min(array_filter($payment))); ?>, <?php echo intval(max($payment)); ?>" min="<?php echo min(array_filter($payment)); ?>" max="<?php echo max($payment); ?>" step="10" type="range" multiple >
						<input type="hidden" name="payment">
					</div>
				</div>
			<?php endif; ?>
			<?php 
			echo child_automotive_listing_generate_search_dropdown(['condition','year','body-style' , 'make', 'model','drivetrain', 'trim' , 'engine' , 'transmission' , 'exterior-color'],3);
			?>
		</aside>
		<div class="card-wrapp container-fluid">
			<div class="card-banner">
				<?php the_field( 'for_code_listings', 'options' ); ?>
			</div>
			<div class="search-panel">
				<div class="sticky-panel">
					<div class="search-row" data-action="<?php echo add_query_arg( ['autocomplete' => 1] , get_permalink() ); ?>">
						<i class="icon material-icons">search</i>
						<input type="search" name="search" class="form-control form-control-lg autocomplete" data-src="<?php echo add_query_arg( ['autocomplete' => 1] , get_permalink() ); ?>" placeholder="<?php _e('Search Makes, Models or Keywords','shopperexpress'); ?>">
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
				<?php 
				$args = array(
					'post_type'   		  => 'listings',
					'post_status' 		  => 'publish',
					'ignore_sticky_posts' => true,
					'posts_per_page'      => -1,
				);

				if ( !empty(filter_args()) ) {
					$args['tax_query'] = filter_args();
				}

				$terms = ['condition' ,'year','body-style' , 'make', 'model','drivetrain', 'trim' , 'engine' , 'transmission' , 'exterior-color'];
				$filter = [];
				$query1 = new WP_Query( $args );

				while ( $query1->have_posts() ) : $query1->the_post();
					foreach ( $terms as $term ) {
						$taxonomy = get_the_terms( get_the_id(), $term );
						if ( !empty( $taxonomy ) ){
							$taxonomy = array_shift( $taxonomy );
							$slug = $term == 'year' ? 'yr' : $term;
							$filter[$slug][$taxonomy->slug] = $taxonomy->name;
						}
					}
				endwhile;
				wp_reset_query();

				echo '<div class="json-data" style="display: none;">' . json_encode([$filter]) . '</div>';

				$args['posts_per_page']	= 24;

				$query = new WP_Query( $args );

				while ( $query->have_posts() ) : $query->the_post();
					get_template_part( 'blocks/content-listing');
				endwhile;
				wp_reset_query();
				?>
			</div>
			<?php if ( $query->max_num_pages > 1 ): ?>
				<a href="<?php echo add_query_arg(['next' => 2] , $permalink); ?>" class="btn-more hidden"></a>
				<div class="loader-holder">
					<div class="loader"><?php _e('Loading','shopperexpress'); ?>...</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</form>
<?php get_footer(); ?>

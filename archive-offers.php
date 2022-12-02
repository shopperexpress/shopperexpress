<?php
/*
Template Name: Offers Template
*/
get_header();

$permalink = get_the_permalink();
$search = !empty($_GET['search']) ? $_GET['search'] : null;

$args = array(
	'post_type'   => 'offers',
	'post_status' => 'publish',
	'ignore_sticky_posts' => true,
	'posts_per_page' => -1,
	's' => $search
);

$query = new WP_Query( $args );

$price = [];

while ( $query->have_posts() ) : $query->the_post();
	$price[] = get_field( 'price' );
endwhile;
wp_reset_query();

$condition = !empty($_GET['condition']) ? $_GET['condition'] : null; ?>
<form action="<?php echo $permalink; ?>" class="filter-section">
	<div class="main-holder">
		<a class="filter-opener" href="#"><i class="material-icons"><?php _e('filter_alt','shopperexpress'); ?></i></a>
		<aside class="aside">
			<input type="hidden" name="ptype" value="offers">
			<?php
			new_filter( [ 'year', 'make', 'model', 'body-style' ] );
			?>
		</aside>
		<div class="card-wrapp container-fluid">
			<div class="card-banner">
				<?php the_field( 'for_code_offers', 'options' ); ?>
			</div>
			<div class="search-panel">
				<div class="sticky-panel">
					<div class="search-row" data-action="<?php echo add_query_arg( ['autocomplete' => 1, 'ptype' => 'offers'] , get_permalink() ); ?>">
						<i class="icon material-icons">search</i>
						<input type="search" name="search" class="form-control form-control-lg autocomplete" data-src="<?php echo add_query_arg( ['autocomplete' => 1, 'ptype' => 'offers'] , get_permalink() ); ?>" placeholder="<?php _e('Search Makes, Models or Keywords','shopperexpress'); ?>">
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
					'post_type'   		  => 'offers',
					'post_status' 		  => 'publish',
					'ignore_sticky_posts' => true,
					'posts_per_page'      => 24,
					
				);

				if ( $condition ) {
					$args['tax_query'] = [
						[
							'taxonomy' => 'condition',
							'field'    => 'slug',
							'terms'    => [$condition],
						]
					];
				}

				if ( !empty(filter_args()) ) {
					$args['tax_query'] = filter_args();
				}

				$query = new WP_Query( $args );

				while ( $query->have_posts() ) : $query->the_post();
					get_template_part( 'blocks/content-offers');
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
<?php
add_action('wp_footer', 'new_filter_modal');
get_footer();
?>
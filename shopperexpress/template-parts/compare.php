<?php
/**
 * Compare popup, compare modal and handlebars templates for vehicle/offer cards.
 *
 * @package Shopperexpress
 */

$_compare_post_type = get_field( 'post_type' );
if ( empty( $_compare_post_type ) ) {
	$_compare_post_type = is_post_type_archive() ? get_queried_object()->name : get_post_type( get_the_id() );
}

if ( ! in_array( $_compare_post_type, array( 'listings', 'used-listings', 'offers', 'lease-offers' ), true ) ) {
	return;
}
?>
<!-- Compare Popup -->
<div class="compare-popup">
	<div class="compare-popup__head">
		<strong class="compare-popup__title"><?php esc_html_e( 'Add up to 4 more vehicles', 'shopperexpress' ); ?></strong>
		<button class="compare-popup__close" type="button" aria-label="<?php esc_attr_e( 'Close', 'shopperexpress' ); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px">
				<path d="M480-424 284-228q-11 11-28 11t-28-11q-11-11-11-28t11-28l196-196-196-196q-11-11-11-28t11-28q11-11 28-11t28 11l196 196 196-196q11-11 28-11t28 11q11 11 11 28t-11 28L536-480l196 196q11 11 11 28t-11 28q-11 11-28 11t-28-11L480-424Z"></path>
			</svg>
		</button>
	</div>
	<div class="compare-popup__body">
		<ul class="compare-popup__list"></ul>
		<span class="compare-popup__text"><?php esc_html_e( 'Select 2 - 5 Vehicles to Compare', 'shopperexpress' ); ?></span>
		<span class="compare-popup__info d-none">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
				<path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"></path>
			</svg>
			<?php esc_html_e( 'Maximum of 5 Vehicles Compared.', 'shopperexpress' ); ?>
		</span>
	</div>
	<div class="compare-popup__footer">
		<button class="btn-compare" type="button" data-toggle="modal" data-target="#compareModal">
			<?php esc_html_e( 'Compare', 'shopperexpress' ); ?>
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
				<path d="M4,11V13H16L10.5,18.5L11.92,19.92L19.84,12L11.92,4.08L10.5,5.5L16,11H4Z"></path>
			</svg>
		</button>
	</div>
</div>

<!-- Popup item -->
<script id="compare-popup-item-template" type="text/x-handlebars-template">
	<li>
		<img src="{{photo}}" alt="{{title}}">
		<button class="compare-popup__list-btn-del" type="button" aria-label="<?php esc_attr_e( 'Delete item', 'shopperexpress' ); ?>" data-postid="{{postid}}" data-posttype="{{posttype}}">
			<svg aria-hidden="true" viewBox="0 0 24 24" aria-hidden="true">
				<path d="M14.59 8 12 10.59 9.41 8 8 9.41 10.59 12 8 14.59 9.41 16 12 13.41 14.59 16 16 14.59 13.41 12 16 9.41zM12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2m0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8"></path>
			</svg>
		</button>
	</li>
</script>

<!-- Shared spec rows -->
<script id="compare-spec-rows-template" type="text/x-handlebars-partial">
	<div>
		<strong class="compare-table__title">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
				<path d="M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z"></path>
			</svg>
			<?php esc_html_e( 'Featured Highlights', 'shopperexpress' ); ?>
		</strong>
	</div>
	<div>
		<span class="compare-table__vin" data-term="vin" data-value-prefix="<?php esc_attr_e( 'VIN: ', 'shopperexpress' ); ?>" data-empty-value=""></span>
	</div>
	<div data-term="year">
		<span class="compare-table__subtitle"><?php esc_html_e( 'Year', 'shopperexpress' ); ?></span>
		<span class="compare-table__text"></span>
	</div>
	<div data-term="make">
		<span class="compare-table__subtitle"><?php esc_html_e( 'Make', 'shopperexpress' ); ?></span>
		<span class="compare-table__text"></span>
	</div>
	<div data-term="model">
		<span class="compare-table__subtitle"><?php esc_html_e( 'Model', 'shopperexpress' ); ?></span>
		<span class="compare-table__text"></span>
	</div>
	<div data-term="trim">
		<span class="compare-table__subtitle"><?php esc_html_e( 'Trim', 'shopperexpress' ); ?></span>
		<span class="compare-table__text"></span>
	</div>
	<div data-term="body-style">
		<span class="compare-table__subtitle"><?php esc_html_e( 'Body Style', 'shopperexpress' ); ?></span>
		<span class="compare-table__text"></span>
	</div>
	<div data-term="exterior_color">
		<span class="compare-table__subtitle"><?php esc_html_e( 'Exterior Color', 'shopperexpress' ); ?></span>
		<span class="compare-table__text"></span>
	</div>
	<div data-term="interior_color">
		<span class="compare-table__subtitle"><?php esc_html_e( 'Interior Color', 'shopperexpress' ); ?></span>
		<span class="compare-table__text"></span>
	</div>
	<div data-term="drivetrain">
		<span class="compare-table__subtitle"><?php esc_html_e( 'Drive Train', 'shopperexpress' ); ?></span>
		<span class="compare-table__text"></span>
	</div>
	<div data-term="engine">
		<span class="compare-table__subtitle"><?php esc_html_e( 'Engine', 'shopperexpress' ); ?></span>
		<span class="compare-table__text"></span>
	</div>
	<div data-term="fuel_type">
		<span class="compare-table__subtitle"><?php esc_html_e( 'Fuel', 'shopperexpress' ); ?></span>
		<span class="compare-table__text"></span>
	</div>
	<div data-term="vehicle-status">
		<span class="compare-table__subtitle"><?php esc_html_e( 'Vehicle Status', 'shopperexpress' ); ?></span>
		<span class="compare-table__text"></span>
	</div>
</script>

<!-- Column for a vehicle -->
<script id="compare-column-template" type="text/x-handlebars-template">
	<div class="compare-table__column">
		<div>
			<div class="card-compare">
				<button class="card-compare__btn-del" type="button" aria-label="<?php esc_attr_e( 'Delete item', 'shopperexpress' ); ?>" data-postid="{{postid}}" data-posttype="{{posttype}}">
					<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" aria-hidden="true">
						<path d="M16 0c-8.822 0-16 7.178-16 16s7.178 16 16 16c8.822 0 16-7.178 16-16s-7.178-16-16-16zM16 30.221c-7.842 0-14.223-6.379-14.223-14.221s6.38-14.223 14.223-14.223 14.221 6.38 14.221 14.223-6.379 14.221-14.221 14.221zM17.272 15.995l4.394-4.393c0.226-0.226 0.314-0.555 0.231-0.864s-0.324-0.549-0.632-0.632c-0.309-0.083-0.638 0.006-0.863 0.232l-4.393 4.394-4.394-4.394c-0.35-0.344-0.912-0.342-1.258 0.005s-0.349 0.909-0.005 1.258l4.394 4.393-4.394 4.394c-0.232 0.224-0.325 0.557-0.244 0.869s0.326 0.556 0.638 0.638c0.312 0.082 0.645-0.011 0.869-0.244l4.394-4.393 4.393 4.393c0.225 0.229 0.556 0.319 0.866 0.237s0.553-0.325 0.635-0.635-0.008-0.641-0.237-0.866l-4.393-4.394z"/>
					</svg>
				</button>
				<div class="card-compare__image">
					<img src="{{photo}}" alt="{{title}}">
				</div>
				<div class="card-compare__body">
					<div class="card-compare__row">
						<span class="card-compare__price">{{price}}</span>
						{{{favoriteButton}}}
					</div>
					<strong class="card-compare__title"><a href="{{link}}">{{title}}</a></strong>
				</div>
			</div>
		</div>
		{{> compareSpecRows}}
	</div>
</script>

<!-- First empty slot -->
<script id="compare-empty-column-template" type="text/x-handlebars-template">
	<div class="compare-table__column is-empty">
		<div>
			<div class="card-compare-add">
				<strong class="card-compare-add__title"><?php esc_html_e( 'Add', 'shopperexpress' ); ?> <span>{{remaining}}</span> <?php esc_html_e( 'More Vehicles', 'shopperexpress' ); ?></strong>
				<button class="btn-compare" type="button" data-dismiss="modal"><?php esc_html_e( 'Continue Shopping', 'shopperexpress' ); ?></button>
			</div>
		</div>
		{{> compareSpecRows}}
	</div>
</script>

<!-- Remaining empty slots -->
<script id="compare-skeleton-column-template" type="text/x-handlebars-template">
	<div class="compare-table__column is-empty">
		<div>
			<div class="compare-skeleton">
				<div class="square w-100"></div>
				<div class="compare-skeleton__body">
					<div class="compare-skeleton__row">
						<div class="line w-50"></div>
						<div class="circle"></div>
					</div>
					<div class="compare-skeleton__row">
						<div class="line w-100"></div>
					</div>
				</div>
			</div>
		</div>
		{{> compareSpecRows}}
	</div>
</script>

<!-- Compare Modal -->
<div class="modal fade compare-modal" id="compareModal" tabindex="-1" aria-labelledby="compareModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			<div class="modal-header">
				<h3 class="modal-title" id="compareModalLabel"><?php esc_html_e( 'Compare', 'shopperexpress' ); ?></h3>
				<button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'shopperexpress' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M480-424 284-228q-11 11-28 11t-28-11q-11-11-11-28t11-28l196-196-196-196q-11-11-11-28t11-28q11-11 28-11t28 11l196 196 196-196q11-11 28-11t28 11q11 11 11 28t-11 28L536-480l196 196q11 11 11 28t-11 28q-11 11-28 11t-28-11L480-424Z"/></svg>
				</button>
			</div>
			<div class="modal-body-wrap">
				<div class="modal-body">
					<div class="table-responsive">
						<div class="compare-table"></div>
						<div class="compare-table__switcher custom-control custom-switch">
							<input type="checkbox" class="custom-control-input" id="compareSwitcher" role="switch">
							<label class="custom-control-label" for="compareSwitcher"><?php esc_html_e( 'Hide Similarities', 'shopperexpress' ); ?></label>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

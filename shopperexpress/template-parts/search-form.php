<div class="search-bar js-search-bar">
	<form action="<?php echo get_post_type_archive_link( 'listings' ); ?>" role="search" class="search-bar-form search-row" data-url="<?php echo home_url( 'wp-json/v1/search' ); ?>" data-redirect-url="<?php echo get_post_type_archive_link( 'listings' ); ?>">
		<div class="icon">
			<span>
				<svg viewBox="0 0 40 40">
					<path d="M3,3 L37,37"></path>
				</svg>
			</span>
		</div>
		<input class="form-control autocomplete" type="search" aria-label="<?php esc_html_e( 'Search Makes, Models, Stock Number or VIN', 'shopperexpress' ); ?>" placeholder="<?php esc_html_e( 'Search Makes, Models, Stock Number or VIN', 'shopperexpress' ); ?>" name="search" />
		<div class="results-search-drop search-drop">
			<div class="ajax-drop search-drop">
				<div class="search-buttons-holder">
					<a class="search-link-new search-link" href="<?php echo home_url( 'listings' ); ?>">
						<?php esc_html_e( 'Click Here to Search All New', 'shopperexpress' ); ?>: <span class="search-text"></span>
					</a>
					<a class="search-link-used search-link" href="<?php echo home_url( 'used-listings' ); ?>">
						<?php esc_html_e( 'Click Here to Search All Used', 'shopperexpress' ); ?>: <span class="search-text"></span>
					</a>
				</div>
				<strong><?php esc_html_e( 'suggestions', 'shopperexpress' ); ?></strong>
				<ul class="autocomplete-results"></ul>
			</div>
		</div>
	</form>
</div>

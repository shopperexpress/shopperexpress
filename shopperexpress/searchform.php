<?php
/**
 * Template part for displaying search form
 *
 * @package Shopperexpress
 */

$sq = get_search_query() ? get_search_query() : __( 'Enter search terms&hellip;', 'shopperexpress' ); ?>
<form method="get" class="search-form" action="<?php echo esc_url( home_url() ); ?>" >
	<fieldset>
		<input type="search" name="s" placeholder="<?php echo esc_attr( $sq ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" />
		<input type="submit" value="<?php echo esc_attr( _e( 'Search', 'shopperexpress' ) ); ?>" />
	</fieldset>
</form>

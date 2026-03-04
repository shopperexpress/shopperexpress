<?php
/**
 * Template part for displaying sidebar
 *
 * @package Shopperexpress
 */

if ( is_active_sidebar( 'default-sidebar' ) ) : ?>
	<aside id="sidebar">
		<?php dynamic_sidebar( 'default-sidebar' ); ?>
	</aside>
<?php endif; ?>

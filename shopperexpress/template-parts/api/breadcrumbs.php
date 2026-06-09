<?php
/**
 * VDP breadcrumbs for API mode.
 *
 * Accepts $args:
 *   vehicle   (array)  — Intice API vehicle object
 *   post_type (string) — 'listings' or 'used-listings'
 *
 * @package Shopperexpress
 */

$vehicle      = $args['vehicle']   ?? array();
$post_type    = $args['post_type'] ?? 'listings';
$archive_link = home_url( $post_type );

$year      = $vehicle['year']      ?? '';
$condition = $vehicle['condition'] ?? '';
$make      = $vehicle['make']      ?? '';
$model     = $vehicle['model']     ?? '';
?>
<div class="detail-top-row">
	<ol class="breadcrumbs">
		<li><a href="<?php echo esc_url( add_query_arg( 'year', $year, $archive_link ) ); ?>"><?php echo esc_html( $year ); ?></a></li>
		<li><a href="<?php echo esc_url( add_query_arg( 'condition', $condition, $archive_link ) ); ?>"><?php echo esc_html( $condition ); ?></a></li>
		<li><a href="<?php echo esc_url( add_query_arg( 'make', $make, $archive_link ) ); ?>"><?php echo esc_html( mb_strimwidth( $make, 0, 10, '...' ) ); ?></a></li>
		<li><a href="<?php echo esc_url( add_query_arg( 'model', $model, $archive_link ) ); ?>"><?php echo esc_html( mb_strimwidth( $model, 0, 15, '...' ) ); ?></a></li>
	</ol>
</div>

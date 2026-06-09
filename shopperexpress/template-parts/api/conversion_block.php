<?php
/**
 * API-mode ConversionBlock renderer — SRP only, listings & used-listings.
 *
 * Accepts $args:
 *   vehicle   (array)  — Intice API vehicle object
 *   post_type (string) — 'listings' or 'used-listings'
 *
 * @package Shopperexpress
 */

if ( ! class_exists( 'ConversionBlock' ) ) {
	return;
}

$vehicle   = $args['vehicle']   ?? array();
$post_type = $args['post_type'] ?? '';
$vin       = strtoupper( $vehicle['vin'] ?? '' );

if ( ! $vin || ! in_array( $post_type, array( 'listings', 'used-listings' ), true ) ) {
	return;
}

// SRP only — location is null (no 'single_' suffix).
get_template_part(
	'template-parts/ConversionBlock',
	null,
	array(
		'vin'       => $vin,
		'location'  => null,
		'post_id'   => $vin,
		'post_type' => $post_type,
	)
);

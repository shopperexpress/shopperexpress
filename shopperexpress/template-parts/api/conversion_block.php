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

$vehicle   = $args['vehicle'] ?? array();
$post_type = $args['post_type'] ?? '';
$vin       = strtoupper( $vehicle['vin'] ?? '' );
$single    = $args['single'] ?? '';
if ( ! $vin || ! in_array( $post_type, array( 'listings', 'used-listings' ), true ) ) {
	return;
}

// SRP only — no 'single_' location prefix.
// Pass the already-fetched vehicle data to avoid a redundant API call per card.
get_template_part(
	'template-parts/ConversionBlock',
	null,
	array(
		'vin'         => $vin,
		'location'    => $single,
		'post_id'     => 0,
		'post_type'   => $post_type,
		'api_vehicle' => $vehicle,
	)
);

<?php
/**
 * Compact SRP-card incentive-offer badge.
 *
 * Reads pre-fetched My DealMaker offers from the already-loaded SRP vehicle
 * list response (`payload.incentive_offers`) — no per-card API call. Shows a
 * plain badge (no detail modal, unlike the VDP widget) since many of these
 * render on one page and duplicate modal IDs would collide.
 *
 * Accepts $args:
 *   vehicle (array) — Intice API vehicle object (has `payload.incentive_offers`)
 *
 * @package Shopperexpress
 */

$vehicle = $args['vehicle'] ?? array();
$payload = $vehicle['payload'] ?? array();
$offers  = $payload['incentive_offers'] ?? array();

if ( ! get_field( 'api_new_car_incentives', 'option' ) || empty( $offers ) || ! is_array( $offers ) ) {
	return;
}

$json = intice_normalize_incentive_offers( intice_map_nexus_incentive_offers( $offers ) );

if ( empty( $json ) ) {
	return;
}

// SRP card is compact — surface only the single best (highest cash) offer.
$top = $json[0];
?>
<div class="conditional-offers conditional-offers--badge">
	<span class="conditional-offers__badge-text"><?php echo esc_html( $top['ProgramName'] ); ?></span>
	<span class="conditional-offers__badge-cash">$<?php echo esc_html( $top['ProgramCash'] ); ?></span>
</div>

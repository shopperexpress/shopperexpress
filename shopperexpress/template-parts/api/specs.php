<?php
/**
 * VDP spec table for API mode.
 *
 * Accepts $args:
 *   vehicle (array) — Intice API vehicle object
 *
 * @package Shopperexpress
 */

$vehicle = $args['vehicle'] ?? array();

$specs = array(
	__( 'Mileage', 'shopperexpress' )      => $vehicle['mileage']         ? number_format( (int) $vehicle['mileage'] ) . ' mi' : '',
	__( 'Condition', 'shopperexpress' )    => $vehicle['condition']        ?? '',
	__( 'Drivetrain', 'shopperexpress' )   => $vehicle['drivetrain']       ?? '',
	__( 'Body Style', 'shopperexpress' )   => $vehicle['body_style']       ?? '',
	__( 'Fuel Type', 'shopperexpress' )    => $vehicle['fuel_type']        ?? '',
	__( 'Transmission', 'shopperexpress' ) => $vehicle['transmission']     ?? '',
	__( 'Ext. Color', 'shopperexpress' )   => $vehicle['exterior_color']   ?? '',
	__( 'Int. Color', 'shopperexpress' )   => $vehicle['interior_color']   ?? '',
	__( 'Stock #', 'shopperexpress' )      => $vehicle['stock']            ?? '',
	__( 'VIN', 'shopperexpress' )          => strtoupper( $vehicle['vin'] ?? '' ),
);

$specs = array_filter( $specs );

if ( empty( $specs ) ) {
	return;
}
?>
<div class="card-detail">
	<ul class="detail-list">
		<?php foreach ( $specs as $label => $value ) : ?>
			<li class="detail-item">
				<span class="detail-label"><?php echo esc_html( $label ); ?></span>
				<span class="detail-value"><?php echo esc_html( $value ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
</div>

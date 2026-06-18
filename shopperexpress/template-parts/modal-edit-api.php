<?php
/**
 * Edit modal for API mode VDP.
 *
 * Renders a plain HTML form pre-filled from the Intice Nexus vehicle array.
 * On save, JS posts to admin-ajax.php → wps_api_save_vehicle.
 *
 * Accepts $args:
 *   vehicle   (array)  — Intice API vehicle object
 *   post_type (string) — 'listings' or 'used-listings'
 *
 * @package Shopperexpress
 */

if ( ! wps_check_current_usser() ) {
	return;
}

$vehicle  = $args['vehicle'] ?? array();
$post_type = $args['post_type'] ?? 'listings';

if ( empty( $vehicle ) ) {
	return;
}

$vin     = strtoupper( $vehicle['vin'] ?? '' );
$payload = $vehicle['payload'] ?? array();
$title   = trim( implode( ' ', array_filter( array(
	$vehicle['year'] ?? '',
	$vehicle['make'] ?? '',
	$vehicle['model'] ?? '',
	$vehicle['trim'] ?? '',
) ) ) );

/**
 * Helper: render a text input or textarea field row.
 */
function _api_modal_field( string $label, string $name, string $value, string $type = 'text' ): void {
	?>
	<div class="acf-field">
		<div class="acf-label"><label><?php echo esc_html( $label ); ?></label></div>
		<div class="acf-input">
			<?php if ( 'textarea' === $type ) : ?>
				<textarea
					name="<?php echo esc_attr( $name ); ?>"
					class="acf-input-text"
					rows="3"
				><?php echo esc_textarea( $value ); ?></textarea>
			<?php else : ?>
				<input
					type="<?php echo esc_attr( $type ); ?>"
					name="<?php echo esc_attr( $name ); ?>"
					value="<?php echo esc_attr( $value ); ?>"
					class="acf-input-text"
				>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

$tabs = array(
	'general'     => array(
		'label'  => __( 'General info', 'shopperexpress' ),
		'fields' => array(
			array( 'label' => 'Year',              'name' => 'year',             'value' => $vehicle['year'] ?? '' ),
			array( 'label' => 'Make',              'name' => 'make',             'value' => $vehicle['make'] ?? '' ),
			array( 'label' => 'Model',             'name' => 'model',            'value' => $vehicle['model'] ?? '' ),
			array( 'label' => 'Trim',              'name' => 'trim',             'value' => $vehicle['trim'] ?? '' ),
			array( 'label' => 'Condition',         'name' => 'condition',        'value' => $vehicle['condition'] ?? '' ),
			array( 'label' => 'Stock #',           'name' => 'stock',            'value' => $vehicle['stock'] ?? '' ),
			array( 'label' => 'Mileage',           'name' => 'mileage',          'value' => $vehicle['mileage'] ?? '' ),
			array( 'label' => 'Ext. Color',        'name' => 'exterior_color',   'value' => $vehicle['exterior_color'] ?? '' ),
			array( 'label' => 'Int. Color',        'name' => 'interior_color',   'value' => $vehicle['interior_color'] ?? '' ),
			array( 'label' => 'Body Style',        'name' => 'body_style',       'value' => $vehicle['body_style'] ?? '' ),
			array( 'label' => 'Drivetrain',        'name' => 'drivetrain',       'value' => $vehicle['drivetrain'] ?? '' ),
			array( 'label' => 'Transmission',      'name' => 'transmission',     'value' => $vehicle['transmission'] ?? '' ),
			array( 'label' => 'Fuel Type',         'name' => 'fuel_type',        'value' => $vehicle['fuel_type'] ?? '' ),
			array( 'label' => 'Certified',         'name' => 'certified',        'value' => $vehicle['certified'] ?? '' ),
			array( 'label' => 'Sold',              'name' => 'sold',             'value' => $vehicle['sold'] ?? '' ),
			array( 'label' => 'Status',            'name' => 'special field 3',  'value' => $payload['special field 3'] ?? '' ),
			array( 'label' => 'Information',       'name' => 'information',      'value' => $payload['information'] ?? '' ),
			array( 'label' => 'Message',           'name' => 'message',          'value' => $payload['message'] ?? '' ),
		),
	),
	'payment'     => array(
		'label'  => __( 'Payment', 'shopperexpress' ),
		'fields' => array(
			array( 'label' => 'Lease Payment',     'name' => 'lease_payment',    'value' => $payload['lease_payment'] ?? '' ),
			array( 'label' => 'Loan Payment',      'name' => 'loan_payment',     'value' => $payload['loan_payment'] ?? '' ),
			array( 'label' => 'Loan Payment Sort', 'name' => 'loan_payment_sort','value' => $payload['loan_payment_sort'] ?? '' ),
			array( 'label' => 'Down Payment',      'name' => 'down_payment',     'value' => $payload['down_payment'] ?? '' ),
			array( 'label' => 'Lease Term',        'name' => 'leaseterm',        'value' => $payload['leaseterm'] ?? '' ),
			array( 'label' => 'Loan Term',         'name' => 'loanterm',         'value' => $payload['loanterm'] ?? '' ),
			array( 'label' => 'Loan APR',          'name' => 'loanapr',          'value' => $payload['loanapr'] ?? '' ),
			array( 'label' => 'Total of Payments', 'name' => 'totalofpmts',      'value' => $payload['totalofpmts'] ?? '' ),
		),
	),
	'pricing'     => array(
		'label'  => __( 'Pricing', 'shopperexpress' ),
		'fields' => array(
			array( 'label' => 'Price',             'name' => 'price',            'value' => $vehicle['price'] ?? '' ),
			array( 'label' => 'MSRP',              'name' => 'msrp',             'value' => $vehicle['msrp'] ?? '' ),
			array( 'label' => 'Invoice Amount',    'name' => 'invoiceamount',    'value' => $payload['invoiceamount'] ?? '' ),
			array( 'label' => 'Internet Price',    'name' => 'internetprice',    'value' => $payload['internetprice'] ?? '' ),
			array( 'label' => 'Custom Price 1',    'name' => 'customprice1',     'value' => $payload['customprice1'] ?? '' ),
			array( 'label' => 'Custom Price 2',    'name' => 'customprice2',     'value' => $payload['customprice2'] ?? '' ),
			array( 'label' => 'Custom Price 3',    'name' => 'customprice3',     'value' => $payload['customprice3'] ?? '' ),
		),
	),
	'mechanical'  => array(
		'label'  => __( 'Mechanical', 'shopperexpress' ),
		'fields' => array(
			array( 'label' => 'Engine',            'name' => 'engine',              'value' => $payload['engine'] ?? '' ),
			array( 'label' => 'Engine Cylinders',  'name' => 'enginecylinders',     'value' => $payload['enginecylinders'] ?? '' ),
			array( 'label' => 'Engine Block',      'name' => 'engineblock',         'value' => $payload['engineblock'] ?? '' ),
			array( 'label' => 'Displacement',      'name' => 'enginedisplacement',  'value' => $payload['enginedisplacement'] ?? '' ),
			array( 'label' => 'Trans. Speed',      'name' => 'transmission_speed',  'value' => $payload['transmission_speed'] ?? '' ),
			array( 'label' => 'Wheelbase Code',    'name' => 'wheelbase_code',      'value' => $payload['wheelbase_code'] ?? '' ),
		),
	),
	'fuel'        => array(
		'label'  => __( 'Fuel', 'shopperexpress' ),
		'fields' => array(
			array( 'label' => 'City MPG',          'name' => 'city_mpg',         'value' => $payload['city_mpg'] ?? '' ),
			array( 'label' => 'Highway MPG',       'name' => 'highway_mpg',      'value' => $payload['highway_mpg'] ?? '' ),
			array( 'label' => 'EPA Class',         'name' => 'epaclassification','value' => $payload['epaclassification'] ?? '' ),
		),
	),
	'description' => array(
		'label'  => __( 'Description', 'shopperexpress' ),
		'fields' => array(
			array( 'label' => 'Vehicle Overview',  'name' => 'vehicle_overview',  'value' => $payload['vehicle_overview'] ?? '' ),
			array( 'label' => 'Certified URL',     'name' => 'certified_custom_url','value' => $payload['certified_custom_url'] ?? '' ),
			array( 'label' => 'Market Class',      'name' => 'marketclass',       'value' => $payload['marketclass'] ?? '' ),
			array( 'label' => 'Doors',             'name' => 'doors',             'value' => $payload['doors'] ?? '' ),
			array( 'label' => 'Passenger Cap.',    'name' => 'passengercapacity', 'value' => $payload['passengercapacity'] ?? '' ),
			array( 'label' => 'Ext Color Hex',     'name' => 'extcolorhexcode',   'value' => $payload['extcolorhexcode'] ?? '' ),
			array( 'label' => 'Int Color Hex',     'name' => 'intcolorhexcode',   'value' => $payload['intcolorhexcode'] ?? '' ),
		),
	),
	'seo'         => array(
		'label'  => __( 'SEO', 'shopperexpress' ),
		'fields' => array(
			array( 'label' => 'SEO Title',       'name' => 'seo_title',       'value' => $payload['seo_title'] ?? '',       'type' => 'text' ),
			array( 'label' => 'SEO Description', 'name' => 'seo_description', 'value' => $payload['seo_description'] ?? '', 'type' => 'textarea' ),
			array( 'label' => 'SEO Image URL',   'name' => 'seo_image',       'value' => $payload['seo_image'] ?? '',       'type' => 'text' ),
		),
	),
);
?>
<!-- Edit Modal (API mode) -->
<div class="modal fade modal-edit" id="editModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="editModalApiLabel" aria-hidden="true">
	<div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="editModalApiLabel"><?php echo esc_html( $title ); ?></h5>
				<div class="btn-row">
					<button type="button" class="btn btn-secondary" data-dismiss="modal" aria-label="Close">
						<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ffffff"><path d="M200-120q-33 0-56.5-23.5T120-200v-120q0-17 11.5-28.5T160-360q17 0 28.5 11.5T200-320v120h560v-560H200v120q0-17-11.5-28.5T160-600q-17 0-28.5-11.5T120-640v-120q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm266-320H160q-17 0-28.5-11.5T120-480q0-17 11.5-28.5T160-520h306l-74-74q-12-12-11.5-28t11.5-28q12-12 28.5-12.5T449-651l143 143q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L449-309q-12 12-28.5 11.5T392-310q-11-12-11.5-28t11.5-28l74-74Z"/></svg>
						<?php esc_html_e( 'exit', 'shopperexpress' ); ?>
					</button>
					<button type="button" class="btn btn-primary" aria-label="Save" data-save>
						<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ffffff"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h447q16 0 30.5 6t25.5 17l114 114q11 11 17 25.5t6 30.5v447q0 33-23.5 56.5T760-120H200Zm560-526L646-760H200v560h560v-446ZM480-240q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35ZM280-560h280q17 0 28.5-11.5T600-600v-80q0-17-11.5-28.5T560-720H280q-17 0-28.5 11.5T240-680v80q0 17 11.5 28.5T280-560Zm-80-86v446-560 114Z"/></svg>
						<?php esc_html_e( 'Save', 'shopperexpress' ); ?>
					</button>
				</div>
				<div class="alert bg-danger text-white alert-dismissible fade" role="alert">
					<strong><?php esc_html_e( 'Please check the form - one or more fields are filled in incorrectly.', 'shopperexpress' ); ?></strong>
					<button type="button" class="close close-alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div class="alert bg-success text-white alert-dismissible fade" role="alert">
					<strong><?php esc_html_e( 'Data saved successfully!', 'shopperexpress' ); ?></strong>
					<button type="button" class="close close-alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
			</div>
			<div class="modal-body">
				<form id="api-edit-form" class="acf-form" method="post">
					<input type="hidden" name="vin"   value="<?php echo esc_attr( $vin ); ?>">
					<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'wps_api_save_vehicle' ) ); ?>">

					<!-- Vehicle card header -->
					<div class="card-horizontal">
						<?php if ( ! empty( $vehicle['image'] ) ) : ?>
							<div class="card-img">
								<img src="<?php echo esc_url( $vehicle['image'] ); ?>" alt="<?php echo esc_attr( $title ); ?>">
							</div>
						<?php endif; ?>
						<div class="card-body">
							<h2 class="card-title">
								<span><?php echo esc_html( ( $vehicle['year'] ?? '' ) . ' ' . ( $vehicle['make'] ?? '' ) ); ?></span>
								<?php echo esc_html( ( $vehicle['model'] ?? '' ) . ' ' . ( $vehicle['trim'] ?? '' ) ); ?>
							</h2>
							<dl class="detail-info">
								<dt><?php esc_html_e( 'VIN', 'shopperexpress' ); ?>:</dt>
								<dd class="vin"><?php echo esc_html( $vin ); ?></dd>
								<?php if ( $vehicle['stock'] ?? '' ) : ?>
									<dt><?php esc_html_e( 'Stock', 'shopperexpress' ); ?>:</dt>
									<dd><?php echo esc_html( $vehicle['stock'] ); ?></dd>
								<?php endif; ?>
							</dl>
						</div>
					</div>

					<!-- Tabs -->
					<div class="info-tabs-wrapp">
						<div class="info-tabs-holder">
							<ul class="nav info-tabs" role="tablist">
								<?php $first = true; foreach ( $tabs as $key => $tab ) : ?>
									<li role="presentation">
										<button
											class="<?php echo $first ? 'active' : ''; ?>"
											id="api-info-<?php echo esc_attr( $key ); ?>-tab"
											data-toggle="tab"
											data-target="#api-info-<?php echo esc_attr( $key ); ?>"
											type="button"
											role="tab">
											<?php echo esc_html( $tab['label'] ); ?>
										</button>
									</li>
								<?php $first = false; endforeach; ?>
							</ul>
						</div>
					</div>
					<div class="tab-content">
						<?php $first = true; foreach ( $tabs as $key => $tab ) : ?>
							<div class="tab-pane fade <?php echo $first ? 'show active' : ''; ?>" id="api-info-<?php echo esc_attr( $key ); ?>" role="tabpanel">
								<?php foreach ( $tab['fields'] as $f ) : ?>
									<?php _api_modal_field( $f['label'], $f['name'], $f['value'], $f['type'] ?? 'text' ); ?>
								<?php endforeach; ?>
							</div>
						<?php $first = false; endforeach; ?>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

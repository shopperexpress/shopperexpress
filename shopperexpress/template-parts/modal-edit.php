<?php
/**
 * Template part for displaying modal edit
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Shopperexpress
 */

$title   = ! empty( $args['title'] ) ? $args['title'] : get_the_title();
$post_id = get_the_id();
if ( wps_check_current_usser() ) :
	?>
	<!-- Edit Modal -->
	<div class="modal fade modal-edit" id="editModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="editModalLabel"><?php echo esc_html( $title ); ?></h5>
					<div class="btn-row">
						<button type="button" class="btn btn-secondary" data-dismiss="modal" aria-label="Close">
							<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ffffff">
								<path
									d="M200-120q-33 0-56.5-23.5T120-200v-120q0-17 11.5-28.5T160-360q17 0 28.5 11.5T200-320v120h560v-560H200v120q0 17-11.5 28.5T160-600q-17 0-28.5-11.5T120-640v-120q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm266-320H160q-17 0-28.5-11.5T120-480q0-17 11.5-28.5T160-520h306l-74-74q-12-12-11.5-28t11.5-28q12-12 28.5-12.5T449-651l143 143q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L449-309q-12 12-28.5 11.5T392-310q-11-12-11.5-28t11.5-28l74-74Z" />
							</svg>
							<?php esc_html_e( 'exit', 'shopperexpress' ); ?>
						</button>
						<button type="button" class="btn btn-danger" aria-label="Trash" data-toggle="modal" data-target="#confirmDelete">
							<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ffffff">
								<path
									d="M280-120q-33 0-56.5-23.5T200-200v-520q-17 0-28.5-11.5T160-760q0-17 11.5-28.5T200-800h160q0-17 11.5-28.5T400-840h160q17 0 28.5 11.5T600-800h160q17 0 28.5 11.5T800-760q0 17-11.5 28.5T760-720v520q0 33-23.5 56.5T680-120H280Zm400-600H280v520h400v-520ZM400-280q17 0 28.5-11.5T440-320v-280q0-17-11.5-28.5T400-640q-17 0-28.5 11.5T360-600v280q0 17 11.5 28.5T400-280Zm160 0q17 0 28.5-11.5T600-320v-280q0-17-11.5-28.5T560-640q-17 0-28.5 11.5T520-600v280q0 17 11.5 28.5T560-280ZM280-720v520-520Z" />
							</svg>
							<?php esc_html_e( 'trash', 'shopperexpress' ); ?>
						</button>
						<button type="button" class="btn btn-primary" aria-label="Clear Cache" data-clear="<?php echo esc_attr( get_post_type() ); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ffffff">
								<path
									d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h447q16 0 30.5 6t25.5 17l114 114q11 11 17 25.5t6 30.5v447q0 33-23.5 56.5T760-120H200Zm560-526L646-760H200v560h560v-446ZM480-240q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35ZM280-560h280q17 0 28.5-11.5T600-600v-80q0-17-11.5-28.5T560-720H280q-17 0-28.5 11.5T240-680v80q0 17 11.5 28.5T280-560Zm-80-86v446-560 114Z" />
							</svg>
							<?php esc_html_e( 'Clear Cache', 'shopperexpress' ); ?>
						</button>
						<button type="button" class="btn btn-primary" aria-label="Save" data-save>
							<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ffffff">
								<path
									d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h447q16 0 30.5 6t25.5 17l114 114q11 11 17 25.5t6 30.5v447q0 33-23.5 56.5T760-120H200Zm560-526L646-760H200v560h560v-446ZM480-240q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35ZM280-560h280q17 0 28.5-11.5T600-600v-80q0-17-11.5-28.5T560-720H280q-17 0-28.5 11.5T240-680v80q0 17 11.5 28.5T280-560Zm-80-86v446-560 114Z" />
							</svg>
							<?php esc_html_e( 'Save', 'shopperexpress' ); ?>
						</button>
					</div>
					<div class="alert bg-danger text-white alert-dismissible fade" role="alert">
						<strong><?php esc_html_e( 'Please check the form - one or more fields are filled in incorrectly.', 'shopperexpress' ); ?></strong>
						<button type="button" class="close close-alert" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="alert bg-success text-white alert-dismissible fade" role="alert">
						<strong><?php esc_html_e( 'Data saved successfully!', 'shopperexpress' ); ?></strong>
						<button type="button" class="close close-alert" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
				</div>
				<div class="modal-body">
					<form id="custom-acf-form" class="acf-form" method="post">
						<?php wp_nonce_field( 'custom_acf_form_action', 'custom_acf_form_nonce' ); ?>
						<input type="hidden" name="custom_acf_form_submitted" value="1">
						<div class="card-horizontal">
							<?php
							$image   = $reverse = $image_background = '';
							$gallery = get_field( 'gallery' );
							if ( ! empty( $gallery[0] ) ) {
								$image            = $gallery[0]['image_url'];
								$image_background = $gallery[0]['image_background'] ? get_field( 'background_image', 'option' ) : '';
								$reverse          = $gallery[0]['image_reverse'] ? 'reverse-image' : '';
							}
							$image = ! empty( $image ) ? $image : wp_get_attachment_url( absint( get_field( get_post_type() . '_default_image', 'option' ) ) );
							if ( $image ) :
								?>
								<div class="card-img <?php echo esc_attr( $reverse ); ?>"
								<?php
								if ( $image_background ) :
									?>
									style="background-image: url('<?php echo esc_url( $image_background ); ?>')" <?php endif; ?>>
									<img src="<?php echo esc_url( $image ); ?>" alt="<?php esc_html_e( 'image description', 'shopperexpress' ); ?>">
								</div>
							<?php endif; ?>
							<div class="card-body">
								<h2 class="card-title">
									<span><?php the_field( 'year' ); ?> <?php the_field( 'make' ); ?></span>
									<?php the_field( 'model' ); ?> <?php the_field( 'trim' ); ?>
								</h2>
								<?php
								$vin_number   = get_field( 'vin_number' );
								$stock_number = get_field( 'stock_number' );

								if ( $stock_number or $vin_number ) :
									?>
									<dl class="detail-info">
										<?php if ( $vin_number ) : ?>
											<dt><?php esc_html_e( 'VIN', 'shopperexpress' ); ?>:</dt>
											<dd class="vin"><?php echo esc_html( $vin_number ); ?></dd>
											<?php
										endif;
										if ( $stock_number ) :
											?>
											<dt><?php esc_html_e( 'Stock', 'shopperexpress' ); ?>:</dt>
											<dd><?php echo esc_html( $stock_number ); ?></dd>
										<?php endif; ?>
									</dl>
								<?php endif; ?>
							</div>
						</div>
						<?php
						$tabs = array(
							'general'     => array(
								'label'  => esc_html__( 'general info', 'shopperexpress' ),
								'fields' => array(
									'information',
									'message',
									'sold',
									'vehicle-status',
									'mileage',
									'miles_display',
									'comment1',
									'comment2',
									'comment3',
									'comment4',
									'comment5',
									'status_code',
									'dateinstock',
									'exterior_color',
									'interiortype',
									'interior_color',
									'transmission',
									'bodystyle',
									'body_style',
									'trim',
									'model',
									'make',
									'year',
									'condition',
									'stock_number',
									'vin_number',
									'url',
									'vdp_description',
								),
							),
							'fuel'        => array(
								'label'  => esc_html__( 'fuel', 'shopperexpress' ),
								'fields' => array(
									'highway_mpg',
									'city_mpg',
									'epaclassification',
									'fuel_type',
								),
							),
							'mechanical'  => array(
								'label'  => esc_html__( 'mechanical', 'shopperexpress' ),
								'fields' => array(
									'wheelbase_code',
									'enginedisplacement',
									'transmission_speed',
									'transmission_description',
									'engineblock',
									'enginecylinders',
									'engine',
									'enginesizeuom',
									'drivetrain',
								),
							),
							'payment'     => array(
								'label'  => esc_html__( 'payment', 'shopperexpress' ),
								'fields' => array(
									'lease_payment',
									'loan_payment',
									'loan_payment_sort',
									'loan_payment_sort_1',
									'down_payment',
									'leaseterm',
									'loanterm',
									'loanapr',
									'totalofpmts',
								),
							),
							'pricing'     => array(
								'label'  => esc_html__( 'pricing', 'shopperexpress' ),
								'fields' => array(
									'price',
									'price_sort',
									'original_price',
									'invoiceamount',
									'defaultbookvalue',
									'internetprice',
									'customprice1',
									'customprice2',
									'customprice3',
									'pack',
									'holdback',
									'cost',
								),
							),
							'media'       => array(
								'label'  => esc_html__( 'media', 'shopperexpress' ),
								'fields' => array(
									'primaryimageurl',
									'primarythumburl',
									'use_images_list',
									'gallery',
									'gallery_srp',
								),
							),
							'description' => array(
								'label'  => esc_html__( 'description', 'shopperexpress' ),
								'fields' => array(
									'vehicle_overview',
									'photo_timestamp',
									'passengercapacity',
									'marketclass',
									'factory_codes',
									'certified',
									'certified_custom_url',
									'int_color_code',
									'ext_color_code',
									'modelnumber',
									'doors',
									'features',
									'extcolorhexcode',
									'intcolorhexcode',
									'ext_color_generic',
									'int_color_generic',
								),
							),
						);
						?>
						<div class="info-tabs-wrapp">
							<div class="info-tabs-holder">
								<ul class="nav info-tabs" role="tablist">
									<?php foreach ( $tabs as $key => $tab ) : ?>
										<li role="presentation">
											<button
												class="<?php echo $key === 'general' ? 'active' : ''; ?>"
												id="info-<?php echo $key; ?>-tab"
												data-toggle="tab"
												data-target="#info-<?php echo $key; ?>"
												type="button"
												role="tab"
												aria-controls="info-<?php echo $key; ?>"
												aria-selected="<?php echo $key === 'general' ? 'true' : 'false'; ?>">
												<?php echo $tab['label']; ?>
											</button>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						</div>
						<div class="tab-content">
							<?php foreach ( $tabs as $key => $tab ) : ?>
								<div class="tab-pane fade <?php echo $key === 'general' ? 'show active' : ''; ?>" id="info-<?php echo $key; ?>" role="tabpanel" aria-labelledby="info-<?php echo $key; ?>-tab">
									<?php
									if ( ! empty( $tab['fields'] ) ) {
										foreach ( $tab['fields'] as $field ) {
											if ( in_array( $field, array( 'gallery', 'features', 'vdp_description' ) ) ) {
												ob_start();
												acf_form(
													array(
														'post_id' => $post_id,
														'fields' => array( $field ),
														'form' => false,
														'return' => '',
														'ajax' => false,
													)
												);
												$form_html = ob_get_clean();

												if ( $form_html ) {
													echo preg_replace( '#<div id="acf-form-data".*?</div>#s', '', $form_html );
												}
											} else {
												$field          = get_field_object( $field, $post_id );
												$field['value'] = get_field( $field['name'], $post_id );
												acf_render_field_wrap( $field );
											}
										}
									}
									?>
								</div>
							<?php endforeach; ?>
						</div>
						<input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
					</form>
				</div>
			</div>
		</div>
	</div>
	<!-- Confirm delete Modal -->
	<div class="modal fade modal-confirm" data-backdrop="static" data-keyboard="false" id="confirmDelete" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-sm modal-dialog-scrollable modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-body">
					<p class="text-center mb-0"><?php esc_html_e( 'Are you sure you want to delete the data?', 'shopperexpress' ); ?></p>
				</div>
				<div class="modal-footer">
					<button class="btn btn-secondary" type="button" data-dismiss="modal"><?php esc_html_e( 'no', 'shopperexpress' ); ?></button>
					<button class="btn btn-danger" data-post-id="<?php the_ID(); ?>" type="button" id="confirmYes"><?php esc_html_e( 'yes', 'shopperexpress' ); ?></button>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>

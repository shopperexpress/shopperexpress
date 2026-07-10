<?php
/**
 * Template for displaying vehicle offer card.
 *
 * @param array $args Array of arguments containing post ID and other data.
 *
 * @package Shopperexpress
 */

$vin       = ! empty( $args['vin'] ) ? $args['vin'] : null;
$location  = $args['location'] ?? '';
$post_id   = ! empty( $args['post_id'] ) ? $args['post_id'] : get_the_id();
$post_type = get_post_type( $post_id ) ?: ( $args['post_type'] ?? '' );

$is_api = \App\is_api_mode();

if ( $is_api ) {
	// In API mode post_type always comes from args; get_post_type() won't work
	// without a real WP post.
	$post_type = $args['post_type'] ?? $post_type;

	// Use pre-fetched vehicle if passed (e.g. from SRP card loop) to avoid extra API call.
	if ( ! empty( $args['api_vehicle'] ) && is_array( $args['api_vehicle'] ) ) {
		$api_vehicle = $args['api_vehicle'];
	} elseif ( $vin ) {
		$_api_res    = \App\Components\Api\Intice_Api_Client::instance()->get_vehicle( $vin );
		$api_vehicle = ( ! is_wp_error( $_api_res ) && ! empty( $_api_res['data'] ) && is_array( $_api_res['data'] ) ) ? $_api_res['data'] : null;
	} else {
		$api_vehicle = null;
	}

	// Use VIN as the page-unique identifier for data-post attributes and popup IDs.
	$identifier = $vin ?: $post_id;

} else {
	$api_vehicle = null;
	$identifier  = $post_id;
}

while ( have_rows( $location . 'colors', 'options' ) ) :
	the_row();
	$primary_color = get_sub_field( 'primary_color' );
	$style         = 'new' === get_sub_field( 'style' ) ? 'widget--mini' : '';
endwhile;

if ( have_rows( $location . 'buttons_conversion', 'options' ) ) :
	?>
	<span class="intice_bFrame">
		<div class="widgetbox <?php echo esc_attr( $style ); ?>">
			<div class="widgetcon--buttons">
				<?php
				while ( have_rows( $location . 'buttons_conversion', 'options' ) ) :
					the_row();

					$layout = get_row_layout();

					if ( $layout == 'button_1' ) :
						$active           = get_sub_field( 'active' );
						$active_1         = get_sub_field( 'active_1' );
						$active_2         = get_sub_field( 'active_2' );
						$asc_element_type = get_sub_field( 'asc_element_type' );
						$asc_type         = ( $asc_element_type ) ? $asc_element_type : 'banner';
						$asc_type_attr    = ' data-asc-type="' . esc_attr( $asc_type ) . '"';
						$show             = true;

						if ( in_array( $post_type, array( 'listings', 'used-listings' ), true ) ) {
							$show_on = get_sub_field( 'show_on' );

							if ( $show_on ) {
								if ( 'both' === $show_on ) {
									$show = true;
								} else {
									$show = $show_on === $post_type;
								}
							}
						}
						if ( ( $active || $active_1 || $active_2 ) && $show ) :
							?>
							<div class="widget--buttons__holder showWidget fonttype1">
								<?php
								$mobile_button_text_1 = get_sub_field( 'mobile_button_text_1' );
								$form_id              = get_sub_field( 'form_id_1' );
								$button_type          = get_sub_field( 'button_type_1' );
								$url                  = $button_type == 'form' ? '' : get_url_with_fields( $is_api ? $vin : $post_id, '-' . $post_type, get_sub_field( 'url' ) );
								$url_1                = $url ? ' href="' . $url . '" ' : null;
								$onclick              = array();

								while ( have_rows( 'events_1' ) ) :
									the_row();
									$onclick[] = get_sub_field( 'event' );
								endwhile;

								if ( ( $mobile_button_text_1 || $url_1 || $onclick ) && $active_1 ) :
									?>
									<a
									<?php if ( $button_type == 'form' ) : ?>
										data-target="#buttonModal" data-post="<?php echo $identifier; ?>" data-toggle="modal" data-form="<?php echo $form_id; ?>"
									<?php endif; ?>
										<?php
										echo $url_1;
										if ( ! empty( $onclick ) ) :
											?>
										onclick="<?php echo implode( ' ', $onclick ); ?>" <?php endif; ?><?php echo esc_attr( $asc_type_attr ); ?> class="mobile-button" style="color: <?php echo $primary_color; ?>;border: 2px solid <?php echo $primary_color; ?>;">
										<?php echo esc_html( $mobile_button_text_1 ); ?>
									</a>
									<?php
								endif;

								$mobile_button_text_2 = get_sub_field( 'mobile_button_text_2' );
								$form_id              = get_sub_field( 'form_id_2' );
								$button_type          = get_sub_field( 'button_type_2' );
								$url                  = $button_type == 'form' ? '' : get_url_with_fields( $is_api ? $vin : $post_id, '-' . $post_type, get_sub_field( 'url' ) );
								$url_2                = $url ? ' href="' . $url . '" ' : null;
								$onclick              = array();

								while ( have_rows( 'events_2' ) ) :
									the_row();
									$onclick[] = get_sub_field( 'event' );
								endwhile;

								if ( ( $mobile_button_text_2 || $url_2 || $onclick ) && $active_2 ) :
									?>
									<a
									<?php if ( $button_type == 'form' ) : ?>
										data-target="#buttonModal" data-post="<?php echo $identifier; ?>" data-toggle="modal" data-form="<?php echo $form_id; ?>"
									<?php endif; ?>
										<?php
										echo $url_2;
										if ( ! empty( $onclick ) ) :
											?>
										onclick="<?php echo implode( ' ', $onclick ); ?>" <?php endif; ?><?php echo esc_attr( $asc_type_attr ); ?> class="mobile-button" style="color: <?php echo $primary_color; ?>;border: 2px solid <?php echo $primary_color; ?>;"><?php echo esc_html( $mobile_button_text_2 ); ?></a>
									<?php
								endif;

								$desktop_button_text = get_sub_field( 'desktop_button_text' );
								$form_id             = get_sub_field( 'form_id' );
								$button_type         = get_sub_field( 'button_type' );
								$url                 = $button_type == 'form' ? '' : get_url_with_fields( $is_api ? $vin : $post_id, '-' . $post_type, get_sub_field( 'url' ) );
								$url                 = $url ? ' href="' . $url . '" ' : null;
								$onclick             = array();
								while ( have_rows( 'events' ) ) :
									the_row();
									$onclick[] = get_sub_field( 'event' );
								endwhile;

								if ( ( $desktop_button_text || $url || $onclick ) && $active ) :
									?>
									<a
										<?php if ( $button_type == 'form' ) : ?>
										data-target="#buttonModal" data-post="<?php echo $identifier; ?>" data-toggle="modal" data-form="<?php echo $form_id; ?>"
									<?php endif; ?>
									<?php
										echo $url;
									if ( ! empty( $onclick ) ) :
										?>
										onclick="<?php echo implode( ' ', $onclick ); ?>" <?php endif; ?><?php echo esc_attr( $asc_type_attr ); ?> class="desktop-button" style="color: <?php echo $primary_color; ?>;border: 2px solid <?php echo $primary_color; ?>;"><?php echo esc_html( $desktop_button_text ); ?></a>
							</div>
									<?php
							endif;
						endif;
					elseif ( $layout == 'button_2' ) :
						if ( have_rows( 'buttons' ) ) :
							?>
							<div class="widget--buttons__holder">
								<?php
								while ( have_rows( 'buttons' ) ) :
									the_row();
									if ( get_sub_field( 'active' ) == true ) :

										$button_content_image = get_sub_field( 'button_content_image' );
										$onclick              = array();
										if ( get_sub_field( 'enable_image_content' ) ) {
											$title    = $button_content_image;
											$subtitle = '';
										} else {
											$title    = get_sub_field( 'title' );
											$subtitle = get_sub_field( 'subtitle' );
										}

										while ( have_rows( 'events' ) ) :
											the_row();
											$onclick[] = str_replace( 'VIN', $vin, get_sub_field( 'event' ) );
										endwhile;
										?>
										<button type="button" data-asc-type="banner" class="iconhover widget--buttons__item showWidget"
											<?php
											if ( $onclick ) :
												?>
											onclick="<?php echo implode( ' ', $onclick ); ?>" <?php endif; ?>>
											<?php
											$icon = get_sub_field( 'icon' );
											if ( $icon && ! empty( $icon['url'] ) ) :
												?>
												<div class="widget--buttons__icon">
													<?php render_acf_image_icon( $icon ); ?>
												</div>
												<?php
											endif;
											if ( get_sub_field( 'enable_image_content' ) ) :
												echo '<img src="' . esc_url( $title['url'] ) . '" style="width: 100%;display: block;max-height: 30px;object-position: left center;object-fit: contain;">';
											elseif ( $title || $subtitle ) :
												?>
												<span class="widget--buttons__text fonttype2" style="display: block;line-height: 1;text-transform: uppercase;">
													<?php if ( $subtitle ) : ?>
														<span class="widget--buttons__small fonttype5" style="text-transform: none;padding-bottom: 1px;display: block;"><?php echo esc_html( $subtitle ); ?></span>
														<?php
													endif;
													echo wp_kses_post( $title );
													?>
												</span>
												<?php
											endif;
											?>
										</button>
										<?php
									endif;
								endwhile;
								?>
							</div>
							<?php
						endif;
					elseif ( $layout == 'button_3' ) :
						if ( have_rows( 'buttons' ) ) :
							?>
							<div class="widget--buttons__holder">
								<?php
								while ( have_rows( 'buttons' ) ) :
									the_row();
									if ( get_sub_field( 'active' ) == true ) :
										$hide_on_mobile = get_sub_field( 'hide_on_mobile' ) ? 'desktop-button' : null;
										$onclick        = array();
										while ( have_rows( 'event' ) ) :
											the_row();
											$onclick[] = str_replace( 'VIN', $vin, get_sub_field( 'event' ) );
										endwhile;
										?>
										<div class="<?php echo $hide_on_mobile; ?>" style="width:100%;display: flex;">
											<?php if ( get_sub_field( 'choose_button_type' ) == 'Image' ) : ?>
												<div class="buttonimgbox showImage">
													<?php if ( $image = get_sub_field( 'image' ) ) : ?>
														<img src="<?php echo $image['url']; ?>" class="imghover" style="margin-bottom: 6px;cursor:pointer;"
															<?php
															if ( $onclick ) :
																?>
															onclick="<?php echo implode( ' ', $onclick ); ?>" <?php endif; ?>>
													<?php endif; ?>
												</div>
											<?php else : ?>
												<button type="button" data-asc-type="banner"
													<?php
													if ( $onclick ) :
														?>
													onclick="<?php echo implode( ' ', $onclick ); ?>" <?php endif; ?> class="iconhover fonttype3 widget--buttons__item showCustom">
													<span class="widget--buttons__text" style="text-transform: uppercase;">
														<?php
														$icon = get_sub_field( 'icon' );
														if ( $icon ) :
															?>
															<div class="widget--buttons__icon">
																<?php render_acf_image_icon( $icon ); ?>
															</div>
															<?php
														endif;

														the_sub_field( 'button_text' );
														?>
													</span>
												</button>
											<?php endif; ?>
										</div>
										<?php
									endif;
								endwhile;
								?>
							</div>
							<?php
						endif;
					elseif ( $layout == 'button_4' ) :
						if ( get_sub_field( 'active' ) == true ) :
							$vehicle_type = get_sub_field( 'vehicle_type' );
							$vehicle_type = $vehicle_type == 'all' ? $post_type : $vehicle_type;
							if ( $vehicle_type == $post_type ) :

								$show_finance = get_sub_field( 'show_finance' );
								$show_lease   = get_sub_field( 'show_lease' );

								if ( $is_api ) {
									$api_payload   = $api_vehicle['payload'] ?? array();
									$lease_payment = $api_payload['lease payment'] ?? '';
									$loan_payment  = $api_payload['loan payment'] ?? '';
								} elseif ( in_array( $post_type, array( 'finance-offers', 'lease-offers', 'conditional-offers' ) ) ) {
									$lease_payment = get_field( 'payment', $post_id );
									$loan_payment  = get_field( 'payment', $post_id );
								} else {
									$lease_payment = get_field( 'lease_payment', $post_id );
									$loan_payment  = get_field( 'loan_payment', $post_id );
								}

								$hide_on_mobile = get_sub_field( 'hide_on_mobile' ) ? 'desktop-button' : null;


								$onclick = array();
								while ( have_rows( 'events' ) ) :
									the_row();
									$onclick[] = str_replace( 'VIN', $vin, get_sub_field( 'event' ) );
								endwhile;
								$events_finance = array();
								while ( have_rows( 'events_finance' ) ) :
									the_row();
									$events_finance[] = str_replace( 'VIN', $vin, get_sub_field( 'event' ) );
								endwhile;
								$events_finance = ! empty( $events_finance ) ? ' onclick="' . implode( ' ', $events_finance ) . '"' : '';
								$events_lease   = array();
								while ( have_rows( 'events_lease' ) ) :
									the_row();
									$events_lease[] = str_replace( 'VIN', $vin, get_sub_field( 'event' ) );
								endwhile;
								$events_lease    = ! empty( $events_lease ) ? ' onclick="' . implode( ' ', $events_lease ) . '"' : '';
								$popup_text      = get_sub_field( 'popup_text', false, false );
								$popup_text_body = str_replace( 'post_id', $identifier, $popup_text );
								if ( $is_api && ! empty( $api_vehicle ) ) {
									$_payload        = $api_vehicle['payload'] ?? array();
									$_sc_map         = array(
										'price'          => $api_vehicle['price'] ?? '',
										'year'           => $api_vehicle['year'] ?? '',
										'make'           => $api_vehicle['make'] ?? '',
										'model'          => $api_vehicle['model'] ?? '',
										'trim'           => $api_vehicle['trim'] ?? '',
										'lease_payment'  => $_payload['lease_payment'] ?? '',
										'loan_term'      => $_payload['loanterm'] ?? '',
										'loan_apr'       => $_payload['loanapr'] ?? '',
										'lease_term'     => $_payload['leaseterm'] ?? '',
										'due_at_signing' => $_payload['down_payment'] ?? '',
										'total_of_payments' => $_payload['totalofpmts'] ?? '',
									);
									$popup_text_body = preg_replace_callback(
										'/\[(\w+)(?:\s[^\]]+)?\]/',
										function ( $matches ) use ( $_sc_map ) {
											$value = $_sc_map[ $matches[1] ] ?? '';
											return "<span class='js-is-empty'>" . esc_html( (string) $value ) . '</span>';
										},
										$popup_text_body
									);
									$popup_text      = wpautop( $popup_text_body );
								} else {
									$popup_text = wpautop( do_shortcode( $popup_text_body ) );
								}

								$show_banner_1 = $show_banner_2 = false;

								$onclick = ( ( $show_finance && $loan_payment ) || $show_lease && $lease_payment ) ? '' : ' onclick="' . implode( ' ', $onclick ) . '"';

								?>
								<div style="position: relative;width: 100%;margin-bottom: 6px;" class="showWidget <?php echo $hide_on_mobile; ?>">
									<?php if ( get_sub_field( 'show_popup' ) && $popup_text ) : ?>
										<div id="block-<?php echo $identifier; ?>" class="block_popup block-<?php echo $identifier; ?>">
											<span class="widget--popup__close" onclick="document.getElementById('block-<?php echo $identifier; ?>').style.display = 'none';"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
											<div class="widgetbox__popup-text js-is-empty-parent1">
												<?php echo $popup_text; ?>
											</div>
										</div>
									<?php endif; ?>
									<button class="widget--btn paymentbtn showWidget" type="button" data-asc-type="banner" <?php echo $onclick; ?>>
										<span class="widget--btn__body">
											<span class="widget--btn__row">
												<?php if ( $show_finance && $loan_payment ) : ?>
													<span class="widget--btn__col" <?php echo $events_finance; ?>>
														<span class="widget--btn__text" style="font-size: 11px;"><?php the_sub_field( 'loan_header' ); ?></span>
														<span class="widget--btn__price">
															<span class="widget--btn__price-sup">$</span>
															<span class="widget--btn__num finance-num"><?php echo intval( $loan_payment ); ?></span>
															<span class="widget--btn__price-sub">/mo.</span>
														</span>
													</span>
													<?php
												else :
													$show_banner_1 = true;
												endif;
												if ( $show_lease && $lease_payment ) :
													?>
													<span class="widget--btn__col" <?php echo $events_lease; ?>>
														<span class="widget--btn__text" style="font-size: 11px;"><?php the_sub_field( 'lease_header' ); ?></span>
														<span class="widget--btn__price">
															<span class="widget--btn__price-sup">$</span>
															<span class="widget--btn__num lease-num"><?php echo intval( $lease_payment ); ?></span>
															<span class="widget--btn__price-sub">/mo.</span>
														</span>
													</span>
													<?php
												else :
													$show_banner_2 = true;
												endif;
												if ( $show_banner_1 && $show_banner_2 ) :
													?>
													<span class="widget--btn__col">
														<span class="widget--btn__text" style="font-size: 11px;">Personalize</span>
														<span class="widget--btn__price">
															<span class="widget--btn__num lease-num">My Payment</span>
														</span>
													</span>
												<?php endif; ?>
											</span>
											<div class="widget--btn__text-holder">
								<?php if ( $disclosure = get_sub_field( 'disclosure' ) ) : ?>
							<span class="widget--btn__text showWidget" style="font-size: 11px;"><?php echo esc_html( $disclosure ); ?></span>
						<?php endif; ?>
						<span class="showWidget widget--popup__opener"><i class="fa fa-question-circle-o" aria-hidden="true" onclick="document.getElementById('block-<?php echo $identifier; ?>').style.display = 'block';"></i></span>
						</div>
										</span>
										<?php
										$primary_button_text = get_sub_field( 'primary_button_text' );
										if ( $primary_button_text && empty( $style ) ) :
											?>
											<span class="widget--btn__footer fonttype3"><?php echo esc_html( $primary_button_text ); ?></span>
										<?php endif; ?>
									</button>
								</div>
								<?php
							endif;
						endif;
					endif;
				endwhile;
				?>
			</div>
		</div>
	</span>
<?php endif; ?>

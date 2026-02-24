<?php
$vin      = ! empty( $args['vin'] ) ? $args['vin'] : null;
$location = $args['location'];
$post_id  = ! empty( $args['post_id'] ) ? $args['post_id'] : get_the_id();

while ( have_rows( $location . 'colors', 'options' ) ) :
	the_row();
	$primary_color = get_sub_field( 'primary_color' );
endwhile;

if ( have_rows( $location . 'buttons_conversion', 'options' ) ) :
	?>
	<span class="intice_bFrame">
		<div class="widgetbox">
			<div class="widgetcon--buttons" style="box-sizing: border-box;display: -webkit-box;display: -ms-flexbox;display: flex;-ms-flex-wrap: wrap;flex-wrap: wrap;-webkit-box-pack: justify;-ms-flex-pack: justify;justify-content: space-between;">
				<?php
				while ( have_rows( $location . 'buttons_conversion', 'options' ) ) :
					the_row();

					$layout = get_row_layout();

					if ( $layout == 'button_1' ) :
						$active   = get_sub_field( 'active' );
						$active_1 = get_sub_field( 'active_1' );
						$active_2 = get_sub_field( 'active_2' );
						if ( $active || $active_1 || $active_2 ) :
							?>
							<div class="widget--buttons__holder showWidget fonttype1" style="box-sizing: border-box;display: -webkit-box;display: -ms-flexbox;display: flex;-ms-flex-wrap: wrap;flex-wrap: wrap;-webkit-box-pack: justify;-ms-flex-pack: justify;justify-content: space-between;width: calc(100% + 6px);margin: 0 -3px;">
								<?php
								$mobile_button_text_1 = get_sub_field( 'mobile_button_text_1' );
								$form_id              = get_sub_field( 'form_id_1' );
								$button_type          = get_sub_field( 'button_type_1' );
								$url                  = $button_type == 'form' ? '' : get_url_with_fields( $post_id, '-' . get_post_type( $post_id ), get_sub_field( 'url' ) );
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
										data-target="#buttonModal" data-post="<?php echo $post_id; ?>" data-toggle="modal" data-form="<?php echo $form_id; ?>"
									<?php endif; ?>
										<?php
										echo $url_1;
										if ( ! empty( $onclick ) ) :
											?>
										onclick="<?php echo implode( ' ', $onclick ); ?>" <?php endif; ?> class="mobile-button" style="-webkit-transition: box-shadow .4s cubic-bezier(.25,.8,.25,1),background-color .4s cubic-bezier(.25,.8,.25,1);transition:box-shadow .4s cubic-bezier(.25,.8,.25,1),background-color .4s cubic-bezier(.25,.8,.25,1);box-sizing: border-box;color: <?php echo $primary_color; ?>;border: 2px solid <?php echo $primary_color; ?>;border-radius: 5px;padding: 13px 5px;text-align: center;line-height: 1;min-width: calc(50% - 6px);width: calc(50% - 6px);min-height: 44px;margin: 0 3px 6px;text-decoration: none;text-transform: uppercase;cursor: pointer;outline: none;-webkit-box-flex: 1;-ms-flex-positive: 1;flex-grow: 1;">
										<?php echo esc_html( $mobile_button_text_1 ); ?>
									</a>
									<?php
								endif;

								$mobile_button_text_2 = get_sub_field( 'mobile_button_text_2' );
								$form_id              = get_sub_field( 'form_id_2' );
								$button_type          = get_sub_field( 'button_type_2' );
								$url                  = $button_type == 'form' ? '' : get_url_with_fields( $post_id, '-' . get_post_type( $post_id ), get_sub_field( 'url' ) );
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
										data-target="#buttonModal" data-post="<?php echo $post_id; ?>" data-toggle="modal" data-form="<?php echo $form_id; ?>"
									<?php endif; ?>
										<?php
										echo $url_2;
										if ( ! empty( $onclick ) ) :
											?>
										onclick="<?php echo implode( ' ', $onclick ); ?>" <?php endif; ?> class="mobile-button" style="-webkit-transition:box-shadow .4s cubic-bezier(.25,.8,.25,1),background-color .4s cubic-bezier(.25,.8,.25,1);transition:box-shadow .4s cubic-bezier(.25,.8,.25,1),background-color .4s cubic-bezier(.25,.8,.25,1);box-sizing: border-box;background: #fff;color: <?php echo $primary_color; ?>;border: 2px solid <?php echo $primary_color; ?>;border-radius: 5px;padding: 13px 5px;text-align: center;line-height: 1;min-width: calc(50% - 6px);width: calc(50% - 6px);min-height: 44px;margin: 0 3px 6px;text-decoration: none;text-transform: uppercase;cursor: pointer;outline: none;-webkit-box-flex: 1;-ms-flex-positive: 1;flex-grow: 1;"><?php echo esc_html( $mobile_button_text_2 ); ?></a>
									<?php
								endif;

								$desktop_button_text = get_sub_field( 'desktop_button_text' );
								$form_id             = get_sub_field( 'form_id' );
								$button_type         = get_sub_field( 'button_type' );
								$url                 = $button_type == 'form' ? '' : get_url_with_fields( $post_id, '-' . get_post_type( $post_id ), get_sub_field( 'url' ) );
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
										data-target="#buttonModal" data-post="<?php echo $post_id; ?>" data-toggle="modal" data-form="<?php echo $form_id; ?>"
									<?php endif; ?>
									<?php
										echo $url;
									if ( ! empty( $onclick ) ) :
										?>
										onclick="<?php echo implode( ' ', $onclick ); ?>" <?php endif; ?> class="desktop-button" style="-webkit-transition:box-shadow .4s cubic-bezier(.25,.8,.25,1),background-color .4s cubic-bezier(.25,.8,.25,1);transition:box-shadow .4s cubic-bezier(.25,.8,.25,1),background-color .4s cubic-bezier(.25,.8,.25,1);box-sizing: border-box;background: #fff;color: <?php echo $primary_color; ?>;border: 2px solid <?php echo $primary_color; ?>;border-radius: 5px;padding: 13px 5px;text-align: center;line-height: 1;min-width: calc(50% - 6px);width: calc(50% - 6px);min-height: 44px;margin: 0 3px 6px;text-decoration: none;text-transform: uppercase;cursor: pointer;outline: none;-webkit-box-flex: 1;-ms-flex-positive: 1;flex-grow: 1;"><?php echo esc_html( $desktop_button_text ); ?></a>
							</div>
									<?php
							endif;
						endif;
					elseif ( $layout == 'button_2' ) :
						if ( have_rows( 'buttons' ) ) :
							?>
							<div class="widget--buttons__holder" style="box-sizing: border-box;display: -webkit-box;display: -ms-flexbox;display: flex;-ms-flex-wrap: wrap;flex-wrap: wrap;-webkit-box-pack: justify;-ms-flex-pack: justify;justify-content: space-between;width: calc(100% + 6px);margin: 0 -3px;">
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
										<button type="button" class="iconhover widget--buttons__item showWidget"
											<?php
											if ( $onclick ) :
												?>
											onclick="<?php echo implode( ' ', $onclick ); ?>" <?php endif; ?> style="-webkit-transition: box-shadow .4s cubic-bezier(.25,.8,.25,1),background-color .4s cubic-bezier(.25,.8,.25,1);transition: box-shadow .4s cubic-bezier(.25,.8,.25,1),background-color .4s cubic-bezier(.25,.8,.25,1);box-sizing: border-box;border-radius: 5px;text-decoration: none;padding: 10px 8px 10px 15px;margin: 0 3px 6px;min-width: calc(50% - 6px);width: calc(50% - 6px);display: -webkit-box;display: -ms-flexbox;display: flex;-webkit-box-align: center;-ms-flex-align: center;align-items: center;-webkit-box-flex: 1;-ms-flex-positive: 1;flex-grow: 1;border: none;cursor: pointer;outline: none;text-align: left;">
											<?php
											$icon = get_sub_field( 'icon' );
											if ( $icon && ! empty( $icon['url'] ) ) :
												?>
												<img class="widget--buttons__icon" src="<?php echo $icon['url']; ?>" alt="icon" style="width: 24px;height: 24px;-ms-flex-negative: 0;flex-shrink: 0;margin-right: 6px;">
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
							<div class="widget--buttons__holder" style="box-sizing: border-box;display: -webkit-box;display: -ms-flexbox;display: flex;-ms-flex-wrap: wrap;flex-wrap: wrap;-webkit-box-pack: justify;-ms-flex-pack: justify;justify-content: space-between;width:100%;margin: 0px;">
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
												<button type="button"
													<?php
													if ( $onclick ) :
														?>
													onclick="<?php echo implode( ' ', $onclick ); ?>" <?php endif; ?> class="iconhover fonttype3 widget--buttons__item showCustom" style="-webkit-transition:box-shadow .4s cubic-bezier(.25,.8,.25,1),background-color .4s cubic-bezier(.25,.8,.25,1);transition:box-shadow .4s cubic-bezier(.25,.8,.25,1),background-color .4s cubic-bezier(.25,.8,.25,1);box-sizing: border-box;margin: 0 0px 6px;border-radius: 5px;text-decoration: none;padding: 7px 10px 5px;margin-bottom: 6px;min-width: 50%;display: -webkit-box;display: -ms-flexbox;display: flex;-webkit-box-align: center;-ms-flex-align: center;align-items: center;-webkit-box-pack: center;-ms-flex-pack: center;justify-content: center;border: none;cursor: pointer;outline: none;text-align: center;min-height: 48px;width: 100%;line-height: 1;letter-spacing: 1px;">
													<span class="widget--buttons__text" style="text-transform: uppercase;">
														<?php if ( $icon = get_sub_field( 'icon' ) ) : ?>
															<img class="widget--buttons__icon showIcon" src="<?php echo $icon['url']; ?>" alt="Build Your Deal" style="width: 24px;height: 24px;-ms-flex-negative: 0;flex-shrink: 0;margin-right: 6px;vertical-align: middle;">
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
							$post_type    = get_post_type( $post_id );
							$vehicle_type = get_sub_field( 'vehicle_type' );
							$vehicle_type = $vehicle_type == 'all' ? $post_type : $vehicle_type;
							if ( $vehicle_type == $post_type ) :

								$show_finance = get_sub_field( 'show_finance' );
								$show_lease   = get_sub_field( 'show_lease' );
								if ( in_array( $post_type, array( 'finance-offers', 'lease-offers', 'conditional-offers' ) ) ) {
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
								$popup_text    = get_sub_field( 'popup_text', false, false );
								$popup_text    = wpautop( do_shortcode( str_replace( 'post_id', $post_id, $popup_text ) ) );
								$show_banner_1 = $show_banner_2 = false;

								?>
								<div style="position: relative;width: 100%;margin-bottom: 6px;" class="showWidget <?php echo $hide_on_mobile; ?>">
									<?php if ( get_sub_field( 'show_popup' ) && $popup_text ) : ?>
										<span class="showWidget" style="position: absolute;right:0%;top: 10px;width: 40px;height: 40px;text-align: center;z-index: 1;cursor: pointer;"><i class="fa fa-question-circle-o" aria-hidden="true" style="font-size: 19px;color: #bfbfbf !important;" onclick="document.getElementById('block-<?php echo $post_id; ?>').style.display = 'block';"></i></span>
										<div id="block-<?php echo $post_id; ?>" class="block_popup block-<?php echo $post_id; ?>">
											<span onclick="document.getElementById('block-<?php echo $post_id; ?>').style.display = 'none';" style="color: black;position: absolute;top: -6px;right: -2px;"><i class="fa fa-times-circle" aria-hidden="true" style="font-size: 24px;"></i></span>
											<div class="widgetbox__popup-text js-is-empty-parent">
												<?php echo $popup_text; ?>
											</div>
										</div>
									<?php endif; ?>
									<button
										<?php
										if ( $onclick ) :
											?>
										onclick="<?php echo implode( ' ', $onclick ); ?>" <?php endif; ?> class="widget--btn paymentbtn showWidget" type="button" style="cursor:pointer;"
										<?php
										if ( $onclick ) :
											?>
										onclick="<?php echo implode( ' ', $onclick ); ?>" <?php endif; ?>>
										<span class="widget--btn__body widget--btn__body">
											<span class="widget--btn__row">
												<?php
												if ( $show_finance && $loan_payment ) :
													?>
													<span class="widget--btn__col">
														<span class="widget--btn__text" style="font-size: 11px;"><?php the_sub_field( 'loan_header' ); ?></span>
														<span class="widget--btn__price">
															<span class="widget--btn__price-sup widget--btn__price-sup">$</span>
															<span class="widget--btn__num widget--btn__num finance-num"><?php echo intval( $loan_payment ); ?></span>
															<span class="widget--btn__price-sub widget--btn__price-sub">/mo.</span>
														</span>
													</span>
													<?php
												else :
													$show_banner_1 = true;
												endif;
												if ( $show_lease && $lease_payment ) :
													?>
													<span class="widget--btn__col">
														<span class="widget--btn__text" style="font-size: 11px;"><?php the_sub_field( 'lease_header' ); ?></span>
														<span class="widget--btn__price">
															<span class="widget--btn__price-sup widget--btn__price-sup">$</span>
															<span class="widget--btn__num widget--btn__num lease-num"><?php echo intval( $lease_payment ); ?></span>
															<span class="widget--btn__price-sub widget--btn__price-sub">/mo.</span>
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
															<span class="widget--btn__num widget--btn__num lease-num">My Payment</span>
														</span>
													</span>
												<?php endif; ?>
											</span>
											<?php if ( $disclosure = get_sub_field( 'disclosure' ) ) : ?>
												<span class="widget--btn__text showWidget" style="font-size: 11px;"><?php echo esc_html( $disclosure ); ?></span>
											<?php endif; ?>
										</span>
										<?php if ( $primary_button_text = get_sub_field( 'primary_button_text' ) ) : ?>
											<span class="widget--btn__footer widget--btn__footer fonttype3"><?php echo esc_html( $primary_button_text ); ?></span>
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

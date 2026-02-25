<?php
/**
 * Template part for displaying ACF Contact Widget
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Shopper_Express
 */

?>
<section class="contact-widget">
	<div class="container">
		<div class="holder">
			<div class="row">
				<div class="col-md-6 col-xl-4">
					<div class="widget-content">
						<?php
						$heading_contact = get_sub_field( 'heading_contact' );

						if ( $heading_contact ) :
							?>
							<h2><?php echo esc_html( $heading_contact ); ?></h2>
						<?php endif; ?>
						<?php
						if ( have_rows( 'contact_list' ) ) :
							?>
							<ul class="service-contacts list-unstyled">
								<?php
								while ( have_rows( 'contact_list' ) ) :
									the_row();
									$label      = get_sub_field( 'label' );
									$new_window = get_sub_field( 'new_window' ) ? ' target="_blank" ' : '';
									$copy       = get_sub_field( 'copy' );
									$url        = $copy ? '#' : get_sub_field( 'url' );
									$icon       = get_sub_field( 'icon' );

									if ( $url && $label ) :

										?>
										<li>
											<a href="<?php echo $url; ?>" class="link"
												<?php
												echo $new_window;
												if ( $copy ) :
													?>
												data-copied="Copied" data-clipboard-text="<?php echo esc_attr( $label ); ?>" <?php endif; ?>>
												<?php if ( $icon ) : ?>
													<span class="icon">
														<?php echo $icon; ?>
													</span>
												<?php endif; ?>
												<?php echo $label; ?>
												<?php if ( $copy ) : ?>
													<span class="copy">
														<span class="btn-text"><?php esc_html_e( 'Copy', 'shopperexpress' ); ?></span>
														<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 -960 960 960" fill="#000000">
															<path
																d="M360-240q-33 0-56.5-23.5T280-320v-480q0-33 23.5-56.5T360-880h360q33 0 56.5 23.5T800-800v480q0 33-23.5 56.5T720-240H360Zm0-80h360v-480H360v480ZM200-80q-33 0-56.5-23.5T120-160v-520q0-17 11.5-28.5T160-720q17 0 28.5 11.5T200-680v520h400q17 0 28.5 11.5T640-120q0 17-11.5 28.5T600-80H200Zm160-240v-480 480Z" />
														</svg>
													</span>
												<?php endif; ?>
											</a>
										</li>
										<?php
									endif;
								endwhile;
								?>
							</ul>
						<?php endif; ?>
					</div>
				</div>
				<div class="col-md-6 col-xl-4">
					<?php if ( have_rows( 'links' ) ) : ?>
						<ul class="widget-links list-unstyled">
							<?php
							while ( have_rows( 'links' ) ) :
								the_row();
								$link = get_sub_field( 'link' );

								if ( $link ) :
									?>
									<li>
										<a href="<?php echo esc_url( $link['url'] ); ?>" target="<?php echo esc_attr( $link['target'] ); ?>">
											<span class="icon">
												<?php the_sub_field( 'icon' ); ?>
											</span>
											<?php
											echo esc_html( $link['title'] );
											?>
										</a>
									</li>
									<?php
								endif;
							endwhile;
							?>
						</ul>
					<?php endif; ?>
					<div class="social-holder">
						<?php
						$heading_social = get_sub_field( 'heading_social' );
						if ( $heading_social ) :
							?>
							<h3><?php echo esc_html( $heading_social ); ?></h3>
							<?php
						endif;
						if ( have_rows( 'social_media' ) ) :
							?>
							<ul class="widget-socials list-unstyled">
								<?php
								while ( have_rows( 'social_media' ) ) :
									the_row();
									$label      = get_sub_field( 'label' );
									$url        = get_sub_field( 'url' );
									$new_window = get_sub_field( 'new_window' ) ? ' target="_blank" ' : '';
									$icon       = get_sub_field( 'icon' );

									if ( $url && $icon ) :
										?>
										<li>
											<a href="<?php echo esc_url( $url ); ?>" <?php echo $new_window; ?> aria-label="Go to our <?php echo esc_attr( $label ); ?> page">
												<?php echo $icon; ?>
											</a>
										</li>
										<?php
									endif;
								endwhile;
								?>
							</ul>
						<?php endif; ?>
					</div>
				</div>
				<div class="col-xl-4">
					<?php
					$rand = mt_rand( 1, 9999 );
					if ( have_rows( 'tabs' ) ) :
						?>
						<ul class="widget-tabs nav" id="infoTab-<?php echo esc_attr( $rand ); ?>" role="tablist">
							<?php
							while ( have_rows( 'tabs' ) ) :
								the_row();
								$row = get_row_index() . $rand;
								?>
								<li role="presentation">
									<button class="nav-link
								<?php
								if ( 1 === get_row_index() ) :
									?>
								active<?php endif; ?>" id="<?php echo esc_attr( $row ); ?>tab" data-toggle="tab" data-target="#tab<?php echo esc_attr( $row ); ?>" type="button" role="tab" aria-controls="tab-<?php echo esc_attr( $row ); ?>" aria-selected="
								<?php
								if ( 1 === get_row_index() ) {
									echo 'true';
								} else {
									echo 'false';
								}
								?>
								">
										<?php the_sub_field( 'tab_title' ); ?>
									</button>
								</li>
							<?php endwhile; ?>
						</ul>
						<div class="tab-content" id="infoTabContent-<?php echo esc_attr( $rand ); ?>">
							<?php
							while ( have_rows( 'tabs' ) ) :
								the_row();
								$row    = get_row_index() . $rand;
								$layout = get_row_layout();
								$active = 1 === get_row_index() ? ' show active' : '';
								?>
								<div class="tab-pane fade <?php echo esc_attr( $active ); ?>" id="tab<?php echo esc_attr( $row ); ?>" role="tabpanel" aria-labelledby="tab<?php echo esc_attr( $row ); ?>">
									<?php
									if ( $layout == 'hours' ) :
										$heading = get_sub_field( 'heading' );
										if ( $heading ) :
											?>
											<h3><?php echo esc_html( $heading ); ?></h3>
											<?php
										endif;
										while ( have_rows( 'schedule_list' ) ) :
											the_row();
											$heading = get_sub_field( 'heading' );

											if ( $heading ) :
												?>
												<h4><?php echo esc_html( $heading ); ?></h4>
												<?php
											endif;
											if ( have_rows( 'list' ) ) :
												?>
												<ul class="schedule-list list-unstyled">
													<?php
													while ( have_rows( 'list' ) ) :
														the_row();
														$day  = get_sub_field( 'day' );
														$time = get_sub_field( 'time' );

														if ( $day || $time ) :
															?>
															<li><span><?php echo esc_html( $day ); ?></span> <span><?php echo esc_html( $time ); ?></span></li>
															<?php
														endif;
													endwhile;
													?>
												</ul>
												<?php
											endif;
										endwhile;
									elseif ( $layout == 'text' ) :
										the_sub_field( 'text' );
									elseif ( $layout == 'list' ) :
										if ( have_rows( 'lists' ) ) :
											?>
											<div class="list-columns">
												<?php
												while ( have_rows( 'lists' ) ) :
													the_row();

													?>
													<ul class="widget-about-list list-unstyled">
														<?php
														while ( have_rows( 'list' ) ) :
															the_row();
															$text = get_sub_field( 'text' );
															$icon = get_sub_field( 'icon' );

															if ( $text || $icon ) :
																?>
																<li>
																	<?php
																	echo $icon;
																	echo esc_html( $text );
																	?>
																</li>
																<?php
															endif;
														endwhile;
														?>
													</ul>
												<?php endwhile; ?>
											</div>
											<?php
										endif;
									endif;
									?>
								</div>
								<?php
							endwhile;
							?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</section>

<?php
/**
 * Shared: Contact Widget
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type string $heading_contact  Contact column heading.
 *   @type array  $contact_list     Array of items: label, new_window (bool), copy (bool), url, icon (HTML).
 *   @type array  $links            Array of items: link (ACF link array), icon (HTML).
 *   @type string $heading_social   Social heading.
 *   @type array  $social_media     Array of items: label, url, new_window (bool), icon (HTML).
 *   @type int    $rand             Random seed for tab IDs.
 *   @type array  $tabs             Array of tab items, each with:
 *                                  tab_title (HTML), layout ('hours'|'text'|'list'),
 *                                  heading (string), schedule_list (array), text (HTML),
 *                                  lists (array of list column arrays).
 * }
 */

$heading_contact = $args['heading_contact'] ?? '';
$contact_list    = $args['contact_list'] ?? array();
$links           = $args['links'] ?? array();
$heading_social  = $args['heading_social'] ?? '';
$social_media    = $args['social_media'] ?? array();
$rand            = $args['rand'] ?? mt_rand( 1, 9999 );
$tabs            = $args['tabs'] ?? array();
?>
<section class="contact-widget">
	<div class="container">
		<div class="holder">
			<div class="row">
				<div class="col-md-6 col-xl-4">
					<div class="widget-content">
						<?php if ( $heading_contact ) : ?>
							<h2><?php echo esc_html( $heading_contact ); ?></h2>
						<?php endif; ?>
						<?php if ( ! empty( $contact_list ) ) : ?>
							<ul class="service-contacts list-unstyled">
								<?php foreach ( $contact_list as $item ) : ?>
									<?php
									$label      = $item['label'] ?? '';
									$new_window = ! empty( $item['new_window'] ) ? ' target="_blank" ' : '';
									$copy       = ! empty( $item['copy'] );
									$url        = $copy ? '#' : ( $item['url'] ?? '' );
									$icon       = $item['icon'] ?? '';

									if ( $url && $label ) :
										?>
										<li>
											<a href="<?php echo $url; ?>" class="link"
												<?php echo $new_window; ?>
												<?php if ( $copy ) : ?>
												data-copied="Copied" data-clipboard-text="<?php echo esc_attr( $label ); ?>"
												<?php endif; ?>>
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
									?>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				</div>
				<div class="col-md-6 col-xl-4">
					<?php if ( ! empty( $links ) ) : ?>
						<ul class="widget-links list-unstyled">
							<?php foreach ( $links as $link_item ) : ?>
								<?php
								$link = $link_item['link'] ?? null;
								$icon = $link_item['icon'] ?? '';
								if ( $link ) :
									?>
									<li>
										<a href="<?php echo esc_url( $link['url'] ); ?>" target="<?php echo esc_attr( $link['target'] ); ?>">
											<span class="icon">
												<?php echo $icon; ?>
											</span>
											<?php echo esc_html( $link['title'] ); ?>
										</a>
									</li>
									<?php
								endif;
								?>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<div class="social-holder">
						<?php if ( $heading_social ) : ?>
							<h3><?php echo esc_html( $heading_social ); ?></h3>
						<?php endif; ?>
						<?php if ( ! empty( $social_media ) ) : ?>
							<ul class="widget-socials list-unstyled">
								<?php foreach ( $social_media as $item ) : ?>
									<?php
									$label      = $item['label'] ?? '';
									$url        = $item['url'] ?? '';
									$new_window = ! empty( $item['new_window'] ) ? ' target="_blank" ' : '';
									$icon       = $item['icon'] ?? '';

									if ( $url && $icon ) :
										?>
										<li>
											<a href="<?php echo esc_url( $url ); ?>" <?php echo $new_window; ?> aria-label="Go to our <?php echo esc_attr( $label ); ?> page">
												<?php echo $icon; ?>
											</a>
										</li>
										<?php
									endif;
									?>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				</div>
				<div class="col-xl-4">
					<?php if ( ! empty( $tabs ) ) : ?>
						<ul class="widget-tabs nav" id="infoTab-<?php echo esc_attr( $rand ); ?>" role="tablist">
							<?php foreach ( $tabs as $ti => $tab ) : ?>
								<?php
								$tab_index = ( $ti + 1 ) . $rand;
								$active    = 0 === $ti ? 'true' : 'false';
								?>
								<li role="presentation">
									<button class="nav-link<?php echo 0 === $ti ? ' active' : ''; ?>" id="<?php echo esc_attr( $tab_index ); ?>tab" data-toggle="tab" data-target="#tab<?php echo esc_attr( $tab_index ); ?>" type="button" role="tab" aria-controls="tab<?php echo esc_attr( $tab_index ); ?>" aria-selected="<?php echo esc_attr( $active ); ?>">
										<?php echo $tab['tab_title'] ?? ''; ?>
									</button>
								</li>
							<?php endforeach; ?>
						</ul>
						<div class="tab-content" id="infoTabContent-<?php echo esc_attr( $rand ); ?>">
							<?php foreach ( $tabs as $ti => $tab ) : ?>
								<?php
								$tab_index = ( $ti + 1 ) . $rand;
								$layout    = $tab['layout'] ?? '';
								$active    = 0 === $ti ? ' show active' : '';
								?>
								<div class="tab-pane fade<?php echo esc_attr( $active ); ?>" id="tab<?php echo esc_attr( $tab_index ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $tab_index ); ?>tab">
									<?php if ( 'hours' === $layout ) : ?>
										<?php if ( ! empty( $tab['heading'] ) ) : ?>
											<h3><?php echo esc_html( $tab['heading'] ); ?></h3>
										<?php endif; ?>
										<?php foreach ( $tab['schedule_list'] ?? array() as $schedule_group ) : ?>
											<?php if ( ! empty( $schedule_group['heading'] ) ) : ?>
												<h4><?php echo esc_html( $schedule_group['heading'] ); ?></h4>
											<?php endif; ?>
											<?php if ( ! empty( $schedule_group['list'] ) ) : ?>
												<ul class="schedule-list list-unstyled">
													<?php foreach ( $schedule_group['list'] as $sli ) : ?>
														<?php
														$day  = $sli['day'] ?? '';
														$time = $sli['time'] ?? '';
														if ( $day || $time ) :
															?>
															<li><span><?php echo esc_html( $day ); ?></span> <span><?php echo esc_html( $time ); ?></span></li>
															<?php
														endif;
														?>
													<?php endforeach; ?>
												</ul>
											<?php endif; ?>
										<?php endforeach; ?>
									<?php elseif ( 'text' === $layout ) : ?>
										<?php echo $tab['text'] ?? ''; ?>
									<?php elseif ( 'list' === $layout ) : ?>
										<?php if ( ! empty( $tab['lists'] ) ) : ?>
											<div class="list-columns">
												<?php foreach ( $tab['lists'] as $list_col ) : ?>
													<ul class="widget-about-list list-unstyled">
														<?php foreach ( $list_col['list'] ?? array() as $li ) : ?>
															<?php
															$li_text = $li['text'] ?? '';
															$li_icon = $li['icon'] ?? '';
															if ( $li_text || $li_icon ) :
																?>
																<li>
																	<?php
																	echo $li_icon;
																	echo esc_html( $li_text );
																	?>
																</li>
																<?php
															endif;
															?>
														<?php endforeach; ?>
													</ul>
												<?php endforeach; ?>
											</div>
										<?php endif; ?>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</section>

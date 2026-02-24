<?php
/**
 * Template part for displaying ACF Contact Information
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Shopper_Express
 */

?>
<section class="contact-information">
	<div class="container">
		<div class="holder">
			<div class="row">
				<div class="col-md-4 col-xl-3">
					<?php
					$heading = get_sub_field( 'heading_contact' );
					if ( $heading ) :
						?>
						<h3><?php echo esc_html( $heading ); ?></h3>
						<?php
					endif;
					if ( have_rows( 'contact_list' ) ) :
						?>
						<ul class="list-unstyled contact-list">
							<?php
							while ( have_rows( 'contact_list' ) ) :
								the_row();

								$label      = get_sub_field( 'label' );
								$url        = get_sub_field( 'url' );
								$new_window = get_sub_field( 'new_window' ) ? ' target="_blank"' : '';

								?>
								<li>
									<?php if ( ! empty( $url ) ) : ?>
										<a href="<?php echo $url; ?>" <?php echo $new_window; ?>>
											<?php
											the_sub_field( 'icon' );
											echo esc_html( $label );
											?>
										</a>
										<?php
									else :
										the_sub_field( 'icon' );
										echo esc_html( $label );
									endif;
									?>
								</li>
							<?php endwhile; ?>
						</ul>
					<?php endif; ?>
				</div>
				<div class="col-md-4 col-xl-3"></div>
				<div class="col-md-4 col-xl-3">
						<?php
						$heading = get_sub_field( 'heading_social' );
						if ( $heading ) :
							?>
							<h3><?php echo esc_html( $heading ); ?></h3>
							<?php
						endif;
						if ( have_rows( 'social_media' ) ) :
							?>
							<ul class="list-unstyled contact-list">
								<?php
								while ( have_rows( 'social_media' ) ) :
									the_row();

									$label      = get_sub_field( 'label' );
									$url        = get_sub_field( 'url' );
									$new_window = get_sub_field( 'new_window' ) ? ' target="_blank"' : '';

									?>
									<li>
										<?php if ( ! empty( $url ) ) : ?>
											<a href="<?php echo $url; ?>" <?php echo $new_window; ?>>
												<?php
												the_sub_field( 'icon' );
												echo esc_html( $label );
												?>
											</a>
											<?php
										else :
											the_sub_field( 'icon' );
											echo esc_html( $label );
										endif;
										?>
									</li>
								<?php endwhile; ?>
							</ul>
					<?php endif; ?>
				</div>
				<div class="col-md-4 col-xl-3 ml-xl-auto schedule-col">
					<div class="schedule-wrapp">
						<?php
						$heading_schedule = get_sub_field( 'heading_schedule' );
						if ( $heading_schedule ) :
							?>
							<h3><?php echo esc_html( $heading_schedule ); ?></h3>
						<?php endif; ?>
						<?php
						$days = array(
							'day_1' => 'Monday',
							'day_2' => 'Tuesday',
							'day_3' => 'Wednesday',
							'day_4' => 'Thursday',
							'day_5' => 'Friday',
							'day_6' => 'Saturday',
							'day_7' => 'Sunday',
						);

						$schedule = array();

						foreach ( $days as $day_key => $day_label ) {
							if ( have_rows( $day_key ) ) {
								while ( have_rows( $day_key ) ) :
																the_row();
																$closed = get_sub_field( 'closed' );
									if ( $closed ) {
										$schedule[] = __( 'Closed', 'shopperexpress' );
									} else {
										$open  = get_sub_field( 'open' );
										$close = get_sub_field( 'close' );
										if ( $open && $close ) {
											$schedule[] = esc_html( $open ) . '&ndash;' . esc_html( $close );
										} else {
											$schedule[] = __( 'N/A', 'shopperexpress' );
										}
									}
								endwhile;
							} else {
								$schedule[] = __( 'N/A', 'shopperexpress' );
							}
						}
						?>
						<ul class="list-unstyled schedule-list">
							<li>
								<?php echo implode( '<br />', array_values( $days ) ); ?>
							</li>
							<li>
								<?php echo implode( '<br />', $schedule ); ?>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

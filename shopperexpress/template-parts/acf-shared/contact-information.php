<?php
/**
 * Shared: Contact Information
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type string $heading_contact   Contact column heading.
 *   @type array  $contact_list      Array of contact items with keys:
 *                                   label, url, new_window (bool), icon (HTML).
 *   @type string $heading_social    Social column heading.
 *   @type array  $social_media      Array of social items (same keys as contact_list).
 *   @type string $heading_schedule  Schedule column heading.
 *   @type array  $schedule          Flat array of schedule time strings (7 items, Mon–Sun).
 *   @type array  $days              Associative array day_key => day_label.
 * }
 */

$heading_contact  = $args['heading_contact'] ?? '';
$contact_list     = $args['contact_list'] ?? array();
$heading_social   = $args['heading_social'] ?? '';
$social_media     = $args['social_media'] ?? array();
$heading_schedule = $args['heading_schedule'] ?? '';
$schedule         = $args['schedule'] ?? array();
$days             = $args['days'] ?? array(
	'day_1' => 'Monday',
	'day_2' => 'Tuesday',
	'day_3' => 'Wednesday',
	'day_4' => 'Thursday',
	'day_5' => 'Friday',
	'day_6' => 'Saturday',
	'day_7' => 'Sunday',
);
?>
<section class="contact-information">
	<div class="container">
		<div class="holder">
			<div class="row">
				<div class="col-md-4 col-xl-3">
					<?php if ( $heading_contact ) : ?>
						<h3><?php echo esc_html( $heading_contact ); ?></h3>
					<?php endif; ?>
					<?php if ( ! empty( $contact_list ) ) : ?>
						<ul class="list-unstyled contact-list">
							<?php foreach ( $contact_list as $item ) : ?>
								<?php
								$label      = $item['label'] ?? '';
								$url        = $item['url'] ?? '';
								$new_window = ! empty( $item['new_window'] ) ? ' target="_blank"' : '';
								$icon       = $item['icon'] ?? '';
								?>
								<li>
									<?php if ( ! empty( $url ) ) : ?>
										<a href="<?php echo $url; ?>" <?php echo $new_window; ?>>
											<?php
											echo $icon;
											echo esc_html( $label );
											?>
										</a>
										<?php
									else :
										echo $icon;
										echo esc_html( $label );
									endif;
									?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
				<div class="col-md-4 col-xl-3"></div>
				<div class="col-md-4 col-xl-3">
					<?php if ( $heading_social ) : ?>
						<h3><?php echo esc_html( $heading_social ); ?></h3>
					<?php endif; ?>
					<?php if ( ! empty( $social_media ) ) : ?>
						<ul class="list-unstyled contact-list">
							<?php foreach ( $social_media as $item ) : ?>
								<?php
								$label      = $item['label'] ?? '';
								$url        = $item['url'] ?? '';
								$new_window = ! empty( $item['new_window'] ) ? ' target="_blank"' : '';
								$icon       = $item['icon'] ?? '';
								?>
								<li>
									<?php if ( ! empty( $url ) ) : ?>
										<a href="<?php echo $url; ?>" <?php echo $new_window; ?>>
											<?php
											echo $icon;
											echo esc_html( $label );
											?>
										</a>
										<?php
									else :
										echo $icon;
										echo esc_html( $label );
									endif;
									?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
				<div class="col-md-4 col-xl-3 ml-xl-auto schedule-col">
					<div class="schedule-wrapp">
						<?php if ( $heading_schedule ) : ?>
							<h3><?php echo esc_html( $heading_schedule ); ?></h3>
						<?php endif; ?>
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

<?php
/**
 * Shared: Sub Footer Section
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type string $main_title      Section heading.
 *   @type array  $addresses_list  Array of address groups, each with 'title' and 'list'
 *                                 (array of items with keys: url, svg_icon, text).
 *   @type string $schedule_title  Schedule column heading.
 *   @type array  $schedule_list   Array of items with key 'text'.
 * }
 */

$main_title     = $args['main_title'] ?? '';
$addresses_list = $args['addresses_list'] ?? array();
$schedule_title = $args['schedule_title'] ?? '';
$schedule_list  = $args['schedule_list'] ?? array();
?>
<section class="contact-information">
	<div class="container">
		<div class="holder">
			<?php
			if ( $main_title ) {
				echo '<h2>' . $main_title . '</h2>';}
			?>
			<div class="row">
				<?php if ( ! empty( $addresses_list ) ) : ?>
					<?php foreach ( $addresses_list as $address ) : ?>
						<div class="col-md-4 col-xl-3">
							<?php
							if ( ! empty( $address['title'] ) ) {
								echo '<h3>' . $address['title'] . '</h3>';}
							?>
							<?php
							if ( ! empty( $address['list'] ) ) :
								$list = $address['list'];
								?>
								<ul class="list-unstyled contact-list">
									<?php foreach ( $list as $li ) : ?>
										<li>
											<?php
											if ( ! empty( $li['url'] ) ) {
												echo '<a href="' . $li['url'] . '" target="_blank">';
											}
											if ( ! empty( $li['svg_icon'] ) ) {
												echo $li['svg_icon'];
											}
											echo $li['text'];
											if ( ! empty( $li['url'] ) ) {
												echo '</a>';
											}
											?>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
				<div class="col-md-4 col-xl-3 ml-xl-auto schedule-col">
					<div class="schedule-wrapp">
						<?php
						if ( $schedule_title ) {
							echo '<h3>' . $schedule_title . '</h3>';}
						?>
						<?php if ( ! empty( $schedule_list ) ) : ?>
							<ul class="list-unstyled schedule-list">
								<?php foreach ( $schedule_list as $li ) : ?>
									<li>
										<?php echo $li['text']; ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

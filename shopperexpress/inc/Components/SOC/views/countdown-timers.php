<?php
/**
 * SOC Countdown Timers view
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit;

use App\Components\Base\Countdown;

$timers   = $data['timers'] ?? array();
$defaults = $data['defaults'] ?? Countdown::defaults();

$type_labels = array(
	'fixed'     => __( 'Fixed date', 'shopperexpress' ),
	'evergreen' => __( 'Evergreen', 'shopperexpress' ),
	'daily'     => __( 'Daily', 'shopperexpress' ),
);

$type_options = array(
	'fixed'     => array(
		'desc' => __( 'Everyone counts down to the same moment.', 'shopperexpress' ),
		'icon' => '<rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 10h18M8 3v4M16 3v4"></path>',
	),
	'evergreen' => array(
		'desc' => __( 'Each visitor’s own countdown from first visit.', 'shopperexpress' ),
		'icon' => '<circle cx="12" cy="8" r="4"></circle><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"></path>',
	),
	'daily'     => array(
		'desc' => __( 'To midnight (site time), every day.', 'shopperexpress' ),
		'icon' => '<path d="M20 12a8 8 0 1 1-2.3-5.7M20 4v5h-5"></path>',
	),
);

$style_options = array(
	'tiles'   => __( 'Tiles', 'shopperexpress' ),
	'inline'  => __( 'Inline', 'shopperexpress' ),
	'minimal' => __( 'Minimal text', 'shopperexpress' ),
);

$expired_options = array(
	'hide'    => array(
		__( 'Hide the timer', 'shopperexpress' ),
		__( 'Removes it from the page.', 'shopperexpress' ),
	),
	'message' => array(
		__( 'Show a message', 'shopperexpress' ),
		__( 'Replace it with text, e.g. “Offer ended”.', 'shopperexpress' ),
	),
	'keep'    => array(
		__( 'Keep showing zeros', 'shopperexpress' ),
		__( 'Stays on the page at 00:00.', 'shopperexpress' ),
	),
);

$clock_icon = '<circle cx="12" cy="13" r="8"></circle><path d="M12 9v4l2.5 2.5M9 2h6"></path>';

$text_swatches = array(
	array( '', __( 'Theme default', 'shopperexpress' ), '#1d2327' ),
	array( '#ffffff', __( 'White', 'shopperexpress' ), '#ffffff' ),
);

$bg_swatches = array(
	array( '', __( 'Theme default', 'shopperexpress' ), '#f1f1f1' ),
	array( '#2271b1', __( 'Blue', 'shopperexpress' ), '#2271b1' ),
	array( '#1d2327', __( 'Dark', 'shopperexpress' ), '#1d2327' ),
	array( '#c2410c', __( 'Orange', 'shopperexpress' ), '#c2410c' ),
);

$now = new DateTimeImmutable( 'now', wp_timezone() );
?>

<div id="soc-action-notice" class="soc-notice" role="alert"></div>

<div class="soc-cd">

	<?php if ( empty( $timers ) ) : ?>
		<section class="soc-cd__card soc-cd__empty">
			<div class="soc-cd__empty-icon">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><?php echo $clock_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup. ?></svg>
			</div>
			<div>
				<h3><?php esc_html_e( 'No timers yet', 'shopperexpress' ); ?></h3>
				<p><?php esc_html_e( 'Saved timers appear here with their type, status and a shortcode to copy.', 'shopperexpress' ); ?></p>
			</div>
			<button type="button" id="soc-cd-add" class="button button-primary"><?php esc_html_e( 'Add timer', 'shopperexpress' ); ?></button>
		</section>
	<?php else : ?>
		<?php
		$rows       = array();
		$count      = array(
			'all'     => 0,
			'active'  => 0,
			'expired' => 0,
		);
		$icon_copy  = '<rect x="9" y="9" width="11" height="11" rx="2"></rect><path d="M5 15V5a2 2 0 0 1 2-2h8"></path>';
		$unit_short = array(
			'minutes' => 'min',
			'hours'   => 'h',
			'days'    => 'd',
		);
		$now_ts     = $now->getTimestamp();

		foreach ( $timers as $slug => $timer ) {
			$timer  = array_merge( $defaults, $timer );
			$ended  = false;
			$ends   = '';
			$ends_s = '';

			if ( 'fixed' === $timer['type'] ) {
				$end = $timer['end'] ? date_create_immutable_from_format( 'Y-m-d H:i:s', $timer['end'], wp_timezone() ) : false;

				if ( $end ) {
					$ts    = $end->getTimestamp();
					$ended = $ts < $now_ts;
					$ends  = wp_date( 'M j, Y · H:i', $ts );
					$left  = abs( $ts - $now_ts );
					$days  = (int) floor( $left / DAY_IN_SECONDS );
					$hours = (int) floor( ( $left % DAY_IN_SECONDS ) / HOUR_IN_SECONDS );
					$mins  = (int) floor( ( $left % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS );

					if ( $ended ) {
						/* translators: %s: human readable time difference */
						$ends_s = sprintf( __( 'Ended %s ago', 'shopperexpress' ), human_time_diff( $ts, $now_ts ) );
					} elseif ( $days > 0 ) {
						/* translators: 1: days, 2: hours */
						$ends_s = sprintf( __( '%1$dd %2$dh left', 'shopperexpress' ), $days, $hours );
					} else {
						/* translators: 1: hours, 2: minutes */
						$ends_s = sprintf( __( '%1$dh %2$dm left', 'shopperexpress' ), $hours, $mins );
					}
				}
			} elseif ( 'evergreen' === $timer['type'] ) {
				$ends   = $timer['duration'] . ' ' . ( $unit_short[ $timer['duration_unit'] ] ?? $timer['duration_unit'] ) . ' ' . __( 'per visitor', 'shopperexpress' );
				$ends_s = __( 'From first visit', 'shopperexpress' );
			} else {
				$ends   = __( 'Every day · midnight', 'shopperexpress' );
				$ends_s = __( 'Site time', 'shopperexpress' );
			}

			$note = '';
			if ( $ended ) {
				$notes = array(
					'hide'    => __( 'Hidden on site', 'shopperexpress' ),
					'message' => __( 'Shows expired message', 'shopperexpress' ),
					'keep'    => __( 'Showing zeros', 'shopperexpress' ),
				);
				$note  = $notes[ $timer['expired_action'] ] ?? $notes['hide'];
			}

			++$count['all'];
			++$count[ $ended ? 'expired' : 'active' ];

			$rows[] = compact( 'slug', 'timer', 'ended', 'ends', 'ends_s', 'note' );
		}
		?>

		<div class="soc-cd__toolbar">
			<div class="soc-cd__tabs" id="soc-cd-tabs">
				<button type="button" class="is-active" data-filter="all"><?php esc_html_e( 'All', 'shopperexpress' ); ?> <span>(<?php echo (int) $count['all']; ?>)</span></button>
				<span aria-hidden="true">|</span>
				<button type="button" data-filter="active"><?php esc_html_e( 'Active', 'shopperexpress' ); ?> <span>(<?php echo (int) $count['active']; ?>)</span></button>
				<span aria-hidden="true">|</span>
				<button type="button" data-filter="expired"><?php esc_html_e( 'Expired', 'shopperexpress' ); ?> <span>(<?php echo (int) $count['expired']; ?>)</span></button>
			</div>
			<div class="soc-cd__toolbar-search">
				<label for="soc-cd-search" class="screen-reader-text"><?php esc_html_e( 'Search timers', 'shopperexpress' ); ?></label>
				<input type="search" id="soc-cd-search" class="soc-cd__search" placeholder="<?php esc_attr_e( 'Search timers', 'shopperexpress' ); ?>">
			</div>
			<button type="button" id="soc-cd-add" class="button button-primary"><?php esc_html_e( 'Add timer', 'shopperexpress' ); ?></button>
		</div>

		<section class="soc-cd__card soc-cd__tablewrap">
			<table class="soc-cd__tb">
				<thead>
					<tr>
						<th style="width:22%;"><?php esc_html_e( 'Name', 'shopperexpress' ); ?></th>
						<th><?php esc_html_e( 'Type', 'shopperexpress' ); ?></th>
						<th><?php esc_html_e( 'Ends', 'shopperexpress' ); ?></th>
						<th><?php esc_html_e( 'Status', 'shopperexpress' ); ?></th>
						<th><?php esc_html_e( 'Shortcode', 'shopperexpress' ); ?></th>
						<th class="soc-cd__tb-actions"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'shopperexpress' ); ?></span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<?php
						$slug      = $row['slug'];
						$timer     = $row['timer'];
						$shortcode = '[countdown id="' . $slug . '"]';
						$name      = $timer['name'] ?: $slug;
						?>
						<tr class="<?php echo $row['ended'] ? 'is-ended' : ''; ?>" data-cd-slug="<?php echo esc_attr( $slug ); ?>" data-cd-status="<?php echo $row['ended'] ? 'expired' : 'active'; ?>" data-cd-search="<?php echo esc_attr( strtolower( $name . ' ' . $slug ) ); ?>" data-cd-timer="<?php echo esc_attr( wp_json_encode( $timer ) ); ?>">
							<td>
								<a href="#" class="soc-cd__nm soc-cd-edit"><?php echo esc_html( $name ); ?></a>
								<?php if ( $row['note'] ) : ?>
									<span class="soc-cd__sub"><?php echo esc_html( $row['note'] ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<span class="soc-cd__ty">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><?php echo $type_options[ $timer['type'] ]['icon'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup. ?></svg>
									<?php echo esc_html( $type_labels[ $timer['type'] ] ?? $timer['type'] ); ?>
								</span>
							</td>
							<td>
								<span class="soc-cd__ends"><?php echo esc_html( $row['ends'] ); ?></span>
								<span class="soc-cd__sub"><?php echo esc_html( $row['ends_s'] ); ?></span>
							</td>
							<td>
								<span class="soc-cd__badge <?php echo $row['ended'] ? 'soc-cd__badge--ended' : 'soc-cd__badge--active'; ?>">
									<i></i><?php echo $row['ended'] ? esc_html__( 'Expired', 'shopperexpress' ) : esc_html__( 'Active', 'shopperexpress' ); ?>
								</span>
							</td>
							<td>
								<span class="soc-cd__sc">
									<?php echo esc_html( $shortcode ); ?>
									<button type="button" class="soc-cd__ib soc-cd__ib--copy soc-cd-copy" data-shortcode="<?php echo esc_attr( $shortcode ); ?>" aria-label="<?php esc_attr_e( 'Copy shortcode', 'shopperexpress' ); ?>">
										<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><?php echo $icon_copy; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup. ?></svg>
									</button>
								</span>
							</td>
							<td class="soc-cd__tb-actions">
								<button type="button" class="soc-cd__edit soc-cd-edit"><?php esc_html_e( 'Edit', 'shopperexpress' ); ?></button>
								<button type="button" class="soc-cd__ib soc-cd-duplicate" aria-label="<?php esc_attr_e( 'Duplicate', 'shopperexpress' ); ?>" title="<?php esc_attr_e( 'Duplicate', 'shopperexpress' ); ?>">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><?php echo $icon_copy; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup. ?></svg>
								</button>
								<button type="button" class="soc-cd__ib soc-cd__ib--danger soc-cd-delete" aria-label="<?php esc_attr_e( 'Delete', 'shopperexpress' ); ?>" title="<?php esc_attr_e( 'Delete', 'shopperexpress' ); ?>">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"></path></svg>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
					<tr class="soc-cd__none" hidden><td colspan="6"><?php esc_html_e( 'No timers match.', 'shopperexpress' ); ?></td></tr>
				</tbody>
			</table>
		</section>
	<?php endif; ?>

	<section class="soc-cd__card" id="soc-cd-form" style="display:none;" data-defaults="<?php echo esc_attr( wp_json_encode( $defaults ) ); ?>">
		<input type="hidden" id="soc-cd-slug" value="">

		<div class="soc-cd__card-head">
			<h3 id="soc-cd-form-title"><?php esc_html_e( 'New timer', 'shopperexpress' ); ?></h3>
			<span id="soc-cd-form-state"><?php esc_html_e( 'Unsaved', 'shopperexpress' ); ?></span>
		</div>

		<div class="soc-cd__body">
			<div class="soc-cd__main">

				<div class="soc-cd__sec">
					<h4 class="soc-cd__step"><span class="soc-cd__num">1</span><?php esc_html_e( 'Timer type', 'shopperexpress' ); ?></h4>
					<div class="soc-cd__opts">
						<?php foreach ( $type_options as $value => $opt ) : ?>
							<label class="soc-cd__opt">
								<input type="radio" name="soc-cd-type" value="<?php echo esc_attr( $value ); ?>">
								<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><?php echo $opt['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup. ?></svg>
								<span class="soc-cd__ot"><?php echo esc_html( $type_labels[ $value ] ); ?></span>
								<span class="soc-cd__od"><?php echo esc_html( $opt['desc'] ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
					<div class="soc-cd__fields">
						<div>
							<label class="soc-cd__lab" for="soc-cd-name"><?php esc_html_e( 'Name', 'shopperexpress' ); ?></label>
							<input type="text" id="soc-cd-name" class="soc-cd__inp" placeholder="<?php esc_attr_e( 'e.g. Black Friday', 'shopperexpress' ); ?>">
							<p class="soc-cd__help"><?php esc_html_e( 'Only visible in the admin.', 'shopperexpress' ); ?></p>
						</div>
						<div data-cd-show="fixed">
							<label class="soc-cd__lab" for="soc-cd-end"><?php esc_html_e( 'Ends', 'shopperexpress' ); ?></label>
							<input type="datetime-local" id="soc-cd-end" class="soc-cd__inp">
							<p class="soc-cd__help"><?php esc_html_e( 'Site timezone', 'shopperexpress' ); ?></p>
						</div>
						<div data-cd-show="evergreen">
							<label class="soc-cd__lab" for="soc-cd-duration"><?php esc_html_e( 'Duration', 'shopperexpress' ); ?></label>
							<div class="soc-cd__inline">
								<input type="number" id="soc-cd-duration" min="1" step="1" class="soc-cd__inp">
								<select id="soc-cd-duration-unit" class="soc-cd__inp">
									<option value="minutes"><?php esc_html_e( 'Minutes', 'shopperexpress' ); ?></option>
									<option value="hours"><?php esc_html_e( 'Hours', 'shopperexpress' ); ?></option>
									<option value="days"><?php esc_html_e( 'Days', 'shopperexpress' ); ?></option>
								</select>
							</div>
							<p class="soc-cd__help"><?php esc_html_e( 'Shared by every place it is used. Changing it restarts the timer for all visitors.', 'shopperexpress' ); ?></p>
						</div>
					</div>
				</div>

				<div class="soc-cd__sec">
					<h4 class="soc-cd__step"><span class="soc-cd__num">2</span><?php esc_html_e( 'Appearance', 'shopperexpress' ); ?></h4>
					<div class="soc-cd__stack">
						<div>
							<label class="soc-cd__lab" for="soc-cd-label"><?php esc_html_e( 'Label', 'shopperexpress' ); ?></label>
							<input type="text" id="soc-cd-label" class="soc-cd__inp soc-cd__inp--narrow" placeholder="<?php esc_attr_e( 'e.g. Offer ends in', 'shopperexpress' ); ?>">
						</div>

						<div>
							<span class="soc-cd__lab"><?php esc_html_e( 'Style', 'shopperexpress' ); ?></span>
							<div class="soc-cd__opts">
								<?php foreach ( $style_options as $value => $label ) : ?>
									<label class="soc-cd__opt">
										<input type="radio" name="soc-cd-style" value="<?php echo esc_attr( $value ); ?>">
										<span class="soc-cd__sample">
											<?php if ( 'tiles' === $value ) : ?>
												<i></i><i></i><i></i><i></i>
											<?php elseif ( 'inline' === $value ) : ?>
												<b>03 : 14 : 27 : 09</b>
											<?php else : ?>
												<span>3d 14h 27m</span>
											<?php endif; ?>
										</span>
										<span class="soc-cd__ot"><?php echo esc_html( $label ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="soc-cd__row">
							<div>
								<span class="soc-cd__lab"><?php esc_html_e( 'Units', 'shopperexpress' ); ?></span>
								<div class="soc-cd__pills">
									<?php foreach ( Countdown::UNITS as $unit ) : ?>
										<label class="soc-cd__pill"><input type="checkbox" class="soc-cd-unit" value="<?php echo esc_attr( $unit ); ?>"><?php echo esc_html( ucfirst( $unit ) ); ?></label>
									<?php endforeach; ?>
								</div>
								<p class="soc-cd__help"><?php esc_html_e( 'Hidden larger units roll into the next smaller one.', 'shopperexpress' ); ?></p>
							</div>
							<div>
								<span class="soc-cd__lab"><?php esc_html_e( 'Alignment', 'shopperexpress' ); ?></span>
								<input type="hidden" id="soc-cd-align" value="center">
								<div class="soc-cd__seg">
									<button type="button" class="soc-cd-align" data-align="left" aria-label="<?php esc_attr_e( 'Align left', 'shopperexpress' ); ?>"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h10M4 18h13"></path></svg></button>
									<button type="button" class="soc-cd-align" data-align="center" aria-label="<?php esc_attr_e( 'Align center', 'shopperexpress' ); ?>"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M7 12h10M5 18h14"></path></svg></button>
									<button type="button" class="soc-cd-align" data-align="right" aria-label="<?php esc_attr_e( 'Align right', 'shopperexpress' ); ?>"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M10 12h10M7 18h13"></path></svg></button>
								</div>
							</div>
						</div>

						<div class="soc-cd__row">
							<div>
								<span class="soc-cd__lab"><?php esc_html_e( 'Text color', 'shopperexpress' ); ?></span>
								<div class="soc-cd__swatches" data-cd-color="text">
									<?php foreach ( $text_swatches as $sw ) : ?>
										<button type="button" class="soc-cd__sw" data-value="<?php echo esc_attr( $sw[0] ); ?>" aria-label="<?php echo esc_attr( $sw[1] ); ?>" title="<?php echo esc_attr( $sw[1] ); ?>" style="background:<?php echo esc_attr( $sw[2] ); ?>"></button>
									<?php endforeach; ?>
									<input type="text" class="soc-cd-colorpicker" data-key="text" data-default-color="" aria-label="<?php esc_attr_e( 'Custom text color', 'shopperexpress' ); ?>">
								</div>
							</div>
							<div>
								<span class="soc-cd__lab"><?php esc_html_e( 'Tile background', 'shopperexpress' ); ?></span>
								<div class="soc-cd__swatches" data-cd-color="bg">
									<?php foreach ( $bg_swatches as $sw ) : ?>
										<button type="button" class="soc-cd__sw" data-value="<?php echo esc_attr( $sw[0] ); ?>" aria-label="<?php echo esc_attr( $sw[1] ); ?>" title="<?php echo esc_attr( $sw[1] ); ?>" style="background:<?php echo esc_attr( $sw[2] ); ?>"></button>
									<?php endforeach; ?>
									<input type="text" class="soc-cd-colorpicker" data-key="bg" data-default-color="" aria-label="<?php esc_attr_e( 'Custom tile background', 'shopperexpress' ); ?>">
								</div>
							</div>
						</div>
						<input type="hidden" id="soc-cd-text-value" value="">
						<input type="hidden" id="soc-cd-bg-value" value="">
					</div>
				</div>

				<div class="soc-cd__sec">
					<h4 class="soc-cd__step"><span class="soc-cd__num">3</span><?php esc_html_e( 'When it ends', 'shopperexpress' ); ?></h4>
					<div class="soc-cd__opts">
						<?php foreach ( $expired_options as $value => $opt ) : ?>
							<label class="soc-cd__opt">
								<input type="radio" name="soc-cd-expired-action" value="<?php echo esc_attr( $value ); ?>">
								<span class="soc-cd__ot"><?php echo esc_html( $opt[0] ); ?></span>
								<span class="soc-cd__od"><?php echo esc_html( $opt[1] ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
					<div class="soc-cd__fields" data-cd-show-action="message">
						<div>
							<label class="soc-cd__lab" for="soc-cd-expired-text"><?php esc_html_e( 'Expired message', 'shopperexpress' ); ?></label>
							<input type="text" id="soc-cd-expired-text" class="soc-cd__inp" placeholder="<?php esc_attr_e( 'This offer has ended.', 'shopperexpress' ); ?>">
						</div>
					</div>
				</div>
			</div>

			<aside class="soc-cd__aside">
				<h4 class="soc-cd__eyebrow"><?php esc_html_e( 'Live preview', 'shopperexpress' ); ?></h4>
				<div class="soc-cd__stage" id="soc-cd-stage" data-theme="dark">
					<div id="soc-cd-preview"></div>
				</div>
				<div class="soc-cd__toggle" id="soc-cd-stage-toggle">
					<button type="button" data-theme="dark"><?php esc_html_e( 'Dark background', 'shopperexpress' ); ?></button>
					<button type="button" data-theme="light"><?php esc_html_e( 'Light background', 'shopperexpress' ); ?></button>
				</div>
				<div class="soc-cd__shortcode">
					<span class="soc-cd__lab"><?php esc_html_e( 'Shortcode', 'shopperexpress' ); ?></span>
					<code id="soc-cd-shortcode" data-empty="<?php esc_attr_e( 'Available after saving', 'shopperexpress' ); ?>"><?php esc_html_e( 'Available after saving', 'shopperexpress' ); ?></code>
				</div>
				<p class="soc-cd__help"><?php esc_html_e( 'Sample numbers; the real timer counts in the visitor’s browser.', 'shopperexpress' ); ?></p>
			</aside>
		</div>

		<div class="soc-cd__footer">
			<button type="button" id="soc-cd-cancel" class="button"><?php esc_html_e( 'Cancel', 'shopperexpress' ); ?></button>
			<button type="button" id="soc-cd-save" class="button button-primary"><?php esc_html_e( 'Save timer', 'shopperexpress' ); ?></button>
		</div>
	</section>

	<div class="soc-cd-dialog" id="soc-cd-delete-dialog" hidden>
		<div class="soc-cd-dialog__backdrop" data-cd-dialog-close></div>
		<div class="soc-cd-dialog__box" role="alertdialog" aria-modal="true" aria-labelledby="soc-cd-dialog-title" aria-describedby="soc-cd-dialog-text">
			<div class="soc-cd-dialog__icon">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"></path></svg>
			</div>
			<h3 id="soc-cd-dialog-title"><?php esc_html_e( 'Delete this timer?', 'shopperexpress' ); ?></h3>
			<p id="soc-cd-dialog-text">
				<strong id="soc-cd-dialog-name"></strong>
				<?php esc_html_e( 'will be removed permanently. Any shortcode using it will stop showing the timer.', 'shopperexpress' ); ?>
			</p>
			<div class="soc-cd-dialog__actions">
				<button type="button" class="button" data-cd-dialog-close><?php esc_html_e( 'Cancel', 'shopperexpress' ); ?></button>
				<button type="button" class="button soc-cd-dialog__danger" id="soc-cd-delete-confirm"><?php esc_html_e( 'Delete timer', 'shopperexpress' ); ?></button>
			</div>
		</div>
	</div>
</div>

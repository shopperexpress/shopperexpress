<?php
/**
 * SOC toggle row — one on/off setting in a `.soc-toggle-list`.
 *
 * Expects $soc_row:
 *   id         string  Checkbox id (JS binds its change handler to it).
 *   title      string
 *   on         bool    Current state.
 *   note_on    string  Short line under the title when on / off.
 *   note_off   string
 *   desc_on    string  Tooltip text (ⓘ) when on / off.
 *   desc_off   string
 *   status_on  string  Optional, default "Active" / "Inactive".
 *   status_off string
 *   tone       string  Optional pill tone override: ok|off|warn.
 *   status     string  Optional pill text override (with tone).
 *   note       string  Optional note override.
 *
 * The data-* attributes let SOC.setToggleRow() flip the row client-side.
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit;

$soc_row = wp_parse_args(
	$soc_row,
	array(
		'id'         => '',
		'title'      => '',
		'on'         => false,
		'note_on'    => '',
		'note_off'   => '',
		'desc_on'    => '',
		'desc_off'   => '',
		'status_on'  => __( 'Active', 'shopperexpress' ),
		'status_off' => __( 'Inactive', 'shopperexpress' ),
		'tone'       => '',
		'status'     => '',
		'note'       => '',
	)
);

$soc_row_on     = (bool) $soc_row['on'];
$soc_row_tone   = $soc_row['tone'] ? $soc_row['tone'] : ( $soc_row_on ? 'ok' : 'off' );
$soc_row_status = $soc_row['status'] ? $soc_row['status'] : ( $soc_row_on ? $soc_row['status_on'] : $soc_row['status_off'] );
$soc_row_note   = $soc_row['note'] ? $soc_row['note'] : ( $soc_row_on ? $soc_row['note_on'] : $soc_row['note_off'] );
$soc_row_desc   = $soc_row_on ? $soc_row['desc_on'] : $soc_row['desc_off'];
$soc_row_tip_id = $soc_row['id'] . '-tip';
?>
<div
	class="soc-api-mode-card soc-toggle-row <?php echo $soc_row_on ? 'soc-api-mode-card--api' : 'soc-api-mode-card--wp'; ?>"
	data-note-on="<?php echo esc_attr( $soc_row['note_on'] ); ?>"
	data-note-off="<?php echo esc_attr( $soc_row['note_off'] ); ?>"
	data-desc-on="<?php echo esc_attr( $soc_row['desc_on'] ); ?>"
	data-desc-off="<?php echo esc_attr( $soc_row['desc_off'] ); ?>"
	data-status-on="<?php echo esc_attr( $soc_row['status_on'] ); ?>"
	data-status-off="<?php echo esc_attr( $soc_row['status_off'] ); ?>"
>
	<div class="soc-toggle-row__body">
		<div class="soc-toggle-row__head">
			<strong class="soc-toggle-row__title"><?php echo esc_html( $soc_row['title'] ); ?></strong>
			<?php if ( $soc_row_desc ) : ?>
				<button
					type="button"
					class="soc-toggle-row__info"
					aria-label="<?php echo esc_attr( sprintf( /* translators: %s: setting name. */ __( 'About %s', 'shopperexpress' ), $soc_row['title'] ) ); ?>"
					aria-describedby="<?php echo esc_attr( $soc_row_tip_id ); ?>"
					aria-expanded="false"
				>
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><line x1="12" y1="11" x2="12" y2="16"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
				</button>
			<?php endif; ?>
		</div>
		<div class="soc-toggle-row__note"><?php echo esc_html( $soc_row_note ); ?></div>
	</div>

	<span class="soc-toggle-row__pill soc-toggle-row__pill--<?php echo esc_attr( $soc_row_tone ); ?>"><?php echo esc_html( $soc_row_status ); ?></span>

	<label class="soc-toggle-row__switch">
		<input
			type="checkbox"
			role="switch"
			id="<?php echo esc_attr( $soc_row['id'] ); ?>"
			aria-label="<?php echo esc_attr( $soc_row['title'] ); ?>"
			<?php checked( $soc_row_on ); ?>
		/>
		<span class="soc-toggle-row__track" aria-hidden="true"></span>
	</label>

	<?php if ( $soc_row_desc ) : ?>
		<div role="tooltip" id="<?php echo esc_attr( $soc_row_tip_id ); ?>" class="soc-toggle-row__tip" hidden><?php echo esc_html( $soc_row_desc ); ?></div>
	<?php endif; ?>
</div>

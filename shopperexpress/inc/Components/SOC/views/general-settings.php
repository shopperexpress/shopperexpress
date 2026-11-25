<?php
/**
 * SOC General Settings view
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit;

$guard_enabled = isset( $data['guard_enabled'] ) ? (bool) $data['guard_enabled'] : true;
$provider      = $data['video_provider'] ?? 'wistia';
?>

<div id="soc-action-notice" class="soc-notice" role="alert"></div>

<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Front-end Behavior', 'shopperexpress' ); ?></div>

	<div class="soc-toggle-list">
		<?php
		$soc_row = array(
			'id'       => 'soc-intice-guard-toggle',
			'title'    => __( 'Intice Tool Guard', 'shopperexpress' ),
			'on'       => $guard_enabled,
			'note_on'  => __( '"Temporarily unavailable" popup if a tool fails to load', 'shopperexpress' ),
			'note_off' => __( 'Raw onclick, no popup', 'shopperexpress' ),
			'desc_on'  => __( 'Intice shopping-tool buttons (Leadmaker, Trademaker, Dealmaker, Loanmaker, …) wait for inticeAllEvents to load and show a "This tool is temporarily unavailable" popup instead of a silent dead button if it never does.', 'shopperexpress' ),
			'desc_off' => __( 'Intice shopping-tool buttons run their raw inline onclick handler directly, with no wait and no "temporarily unavailable" popup. If inticeAllEvents has not loaded yet (e.g. blocked by a consent tool), the button will fail silently.', 'shopperexpress' ),
		);
		require __DIR__ . '/partials/toggle-row.php';
		?>
	</div>
</div>

<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Video', 'shopperexpress' ); ?></div>

	<p class="description soc-choice-desc">
		<?php esc_html_e( 'Which platform generates video embeds for the homepage video, offer/vehicle videos and video blocks. Existing Wistia IDs are kept as-is — switching providers never rewrites stored content.', 'shopperexpress' ); ?>
	</p>

	<div class="soc-choice-group" role="radiogroup" aria-label="<?php esc_attr_e( 'Video Provider', 'shopperexpress' ); ?>">
		<?php
		$video_choices = array(
			'wistia' => array(
				'title' => __( 'Wistia', 'shopperexpress' ),
				'badge' => __( 'Legacy · default', 'shopperexpress' ),
				'text'  => __( 'Current embeds keep working with no changes.', 'shopperexpress' ),
			),
			'vista'  => array(
				'title' => __( 'VISTA', 'shopperexpress' ),
				'badge' => __( 'Intice', 'shopperexpress' ),
				'text'  => __( 'Hosted on vista.intice.com. Switch back any time.', 'shopperexpress' ),
			),
		);
		foreach ( $video_choices as $value => $choice ) :
			?>
			<label class="soc-choice">
				<input type="radio" name="soc-video-provider" value="<?php echo esc_attr( $value ); ?>" <?php checked( $provider, $value ); ?> />
				<span class="soc-choice__card">
					<span class="soc-choice__check dashicons dashicons-yes" aria-hidden="true"></span>
					<strong class="soc-choice__title"><?php echo esc_html( $choice['title'] ); ?></strong>
					<span class="soc-choice__badge"><?php echo esc_html( $choice['badge'] ); ?></span>
					<span class="soc-choice__text"><?php echo esc_html( $choice['text'] ); ?></span>
				</span>
			</label>
		<?php endforeach; ?>
	</div>
</div>

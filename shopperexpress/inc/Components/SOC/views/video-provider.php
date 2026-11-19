<?php
/**
 * SOC Video Provider view
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit;

$provider = $data['provider'] ?? 'wistia';
?>

<div id="soc-action-notice" class="soc-notice" role="alert"></div>

<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Video Provider', 'shopperexpress' ); ?></div>

	<p class="description">
		<?php esc_html_e( 'Controls which platform generates video embeds for the homepage video, offer/vehicle videos, and video blocks. Existing Wistia IDs are kept as-is — switching providers never rewrites stored content.', 'shopperexpress' ); ?>
	</p>

	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">
				<label for="soc-video-provider-select"><?php esc_html_e( 'Active Provider', 'shopperexpress' ); ?></label>
			</th>
			<td>
				<select id="soc-video-provider-select" class="regular-text">
					<option value="wistia" <?php selected( $provider, 'wistia' ); ?>>
						<?php esc_html_e( 'Wistia — Legacy', 'shopperexpress' ); ?>
					</option>
					<option value="vista" <?php selected( $provider, 'vista' ); ?>>
						<?php esc_html_e( 'VISTA', 'shopperexpress' ); ?>
					</option>
				</select>
				<p class="description">
					<?php esc_html_e( 'Defaults to Wistia — Legacy. Switching back to Wistia only requires changing this setting again.', 'shopperexpress' ); ?>
				</p>
			</td>
		</tr>
	</table>
</div>

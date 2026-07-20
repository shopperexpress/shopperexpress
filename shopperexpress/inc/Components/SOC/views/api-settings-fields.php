<?php
/**
 * SOC API Settings — ACF Fields Reference view
 *
 * Read-only reference table of the Listings ACF fields (label, slug, demo value),
 * grouped by ACF tab. Intended as documentation for anyone wiring up Nexus API
 * field mappings in code.
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit;

$groups = $data['listings_fields'] ?? array();
?>

<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'ACF Fields Reference', 'shopperexpress' ); ?></div>
	<p class="description">
		<?php esc_html_e( 'Reference list of the ACF fields used on Listings / Used Listings, with their field slug and a demo value. Use this when wiring up Nexus API field mappings in code.', 'shopperexpress' ); ?>
	</p>

	<?php foreach ( $groups as $group_label => $fields ) : ?>
		<h4 class="soc-fields-group-title"><?php echo esc_html( $group_label ); ?></h4>
		<table class="soc-table soc-fields-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Field Label', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Slug', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Demo Value', 'shopperexpress' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $fields as $field ) : ?>
					<tr>
						<td><?php echo esc_html( $field['label'] ); ?></td>
						<td><code><?php echo esc_html( $field['slug'] ); ?></code></td>
						<td><?php echo esc_html( $field['demo'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endforeach; ?>
</div>

<?php
/**
 * SOC API Settings — Vehicle Filters subtab
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit;

use App\Components\SOC\Modules\Api_Settings;

$filter_sections = array(
	'listings'      => __( 'New Vehicles', 'shopperexpress' ),
	'used-listings' => __( 'Used Vehicles', 'shopperexpress' ),
);

/**
 * Render one filter row (used for existing rows and for the clone <template>).
 *
 * @param array $row {field, custom_key, operator, value}.
 * @return void
 */
$render_filter_row = function ( array $row ) {
	?>
	<tr class="soc-filters-row">
		<td>
			<select class="soc-filter-field">
				<option value=""><?php esc_html_e( '— none —', 'shopperexpress' ); ?></option>
				<?php foreach ( Api_Settings::FILTER_FIELDS as $value => $field_label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $row['field'], $value ); ?>>
						<?php echo esc_html( $field_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</td>
		<td>
			<input
				type="text"
				class="soc-filter-custom-key regular-text"
				value="<?php echo esc_attr( $row['custom_key'] ); ?>"
				placeholder="<?php esc_attr_e( 'payload key…', 'shopperexpress' ); ?>"
				<?php echo 'custom' !== $row['field'] ? 'style="display:none"' : ''; ?>
			/>
		</td>
		<td>
			<select class="soc-filter-operator">
				<?php foreach ( Api_Settings::FILTER_OPERATORS as $value => $op_label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $row['operator'], $value ); ?>>
						<?php echo esc_html( $op_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</td>
		<td><input type="text" class="soc-filter-value regular-text" value="<?php echo esc_attr( $row['value'] ); ?>" /></td>
		<td>
			<button type="button" class="button button-small soc-filter-remove-row" aria-label="<?php esc_attr_e( 'Remove row', 'shopperexpress' ); ?>">
				&times;
			</button>
		</td>
	</tr>
	<?php
};

$empty_row = array( 'field' => '', 'custom_key' => '', 'operator' => '>=', 'value' => '' );
?>

<p class="description" style="margin-bottom:16px;">
	<?php esc_html_e( 'Exclude vehicles from the search results grid that match these rules. All active rows are combined with AND — a vehicle must satisfy every row to be shown.', 'shopperexpress' ); ?>
</p>

<?php foreach ( $filter_sections as $post_type => $label ) : ?>
	<div class="soc-section">
		<div class="soc-section__title"><?php echo esc_html( $label ); ?></div>

		<table class="soc-table soc-filters-table" data-post-type="<?php echo esc_attr( $post_type ); ?>">
			<thead>
				<tr>
					<th style="width:220px;"><?php esc_html_e( 'Field', 'shopperexpress' ); ?></th>
					<th style="width:200px;"><?php esc_html_e( 'Custom key', 'shopperexpress' ); ?></th>
					<th style="width:110px;"><?php esc_html_e( 'Operator', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Value', 'shopperexpress' ); ?></th>
					<th style="width:40px;"></th>
				</tr>
			</thead>
			<tbody class="soc-filters-rows">
				<?php foreach ( $data['filters'][ $post_type ] as $row ) : ?>
					<?php $render_filter_row( $row ); ?>
				<?php endforeach; ?>
			</tbody>
		</table>

		<button type="button" class="button soc-filter-add-row" style="margin-top:8px;">
			<?php esc_html_e( '+ Add Row', 'shopperexpress' ); ?>
		</button>
	</div>
<?php endforeach; ?>

<template id="soc-filter-row-template">
	<table><tbody>
		<?php $render_filter_row( $empty_row ); ?>
	</tbody></table>
</template>

<div class="soc-action-bar">
	<button type="button" id="soc-save-vehicle-filters" class="button button-primary">
		<?php esc_html_e( 'Save Filters', 'shopperexpress' ); ?>
	</button>
</div>

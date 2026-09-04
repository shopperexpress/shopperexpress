<?php
/**
 * Template for displaying detail info.
 *
 * @param array $args Array of arguments containing post ID and other data.
 *
 * @package Shopperexpress
 */

$vehicle   = ! empty( $args['vehicle'] ) ? $args['vehicle'] : null;
$post_id   = ! empty( $args['post_id'] ) ? $args['post_id'] : get_the_id();
$class     = ! empty( $args['class'] ) ? $args['class'] : 'detail-info';
$post_type = ! empty( $args['post_type'] ) ? $args['post_type'] : get_post_type( $post_id );
$show_on   = ! empty( $args['show_on'] ) ? $args['show_on'] : 'detail';

if ( $vehicle ) {
	$field = 'detail_info';
} elseif ( is_single( $post_id ) || get_post_type( $post_id ) == 'append-data' || get_post_type( $post_id ) == 'offers' ) {
	$field = ! in_array( $post_type, array( 'listings', 'used-listings' ) ) ? $post_type . '-detail_info_detail_info' : 'detail_info';
} else {
	$field = 'listings-detail_srp_detail_info';
}

if ( have_rows( $field, 'options' ) ) :
	ob_start();
	while ( have_rows( $field, 'options' ) ) :
		the_row();
		$label       = get_sub_field( 'label' );
		$value       = get_sub_field( 'value' );
		$row_show_on = get_sub_field( 'show_on' );
		$row_show_on = ! empty( $row_show_on ) ? (array) $row_show_on : array( 'detail' );

		if ( ! in_array( $show_on, $row_show_on, true ) ) {
			continue;
		}

		if ( ! empty( $value ) ) {

				$result = preg_replace_callback(
					'/\b([a-z_]+)\b/',
					function ( $match ) use ( $post_id, $vehicle ) {

						$field = $match[1];

						if ( $vehicle ) {
							if ( 'vin_number' === $field ) {
								$field = 'vin';
							} elseif ( 'stock_number' === $field ) {
								$field = 'stock';
							} elseif ( 'miles_display' === $field ) {
								$field = 'mileage';
							}

							$value = $vehicle[ $field ] ?? null;
							if ( $value === null || $value === '' ) {
								$value = $vehicle['payload'][ $field ] ?? null;
							}

							return ( $value !== null && $value !== '' ) ? $value : '';
						}

						if ( function_exists( 'get_field_object' ) && get_field_object( $field, $post_id ) ) {
							$acf_value = get_field( $field, $post_id );

							if ( $acf_value !== null && $acf_value !== '' ) {
								return $acf_value;
							} else {
								return '';
							}
						}

						return $match[0];
					},
					$value
				);
		}
		if ( ! empty( $result ) && ! empty( $label ) ) :
			if ( $label ) :
				?>
					<dt><?php echo esc_html( $label ); ?></dt>
				<?php endif; ?>
				<dd
					<?php
					if ( str_contains( strtolower( $label ), 'vin' ) ) :
						?>
					class="vin" <?php endif; ?>>
					<?php echo str_replace( '&nbsp;', ' ', esc_html( $result ) ); ?>
				</dd>
				<?php
			endif;
	endwhile;
	$rows_html = ob_get_clean();

	if ( '' !== trim( $rows_html ) ) :
		?>
		<dl class="<?php echo esc_attr( $class ); ?>">
			<?php echo $rows_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped per-row above. ?>
		</dl>
		<?php
	endif;
endif;

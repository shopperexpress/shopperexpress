<?php
/**
 * Template for displaying detail info.
 *
 * @param array $args Array of arguments containing post ID and other data.
 *
 * @package Shopperexpress
 */

$post_id   = ! empty( $args['post_id'] ) ? $args['post_id'] : get_the_id();
$class     = ! empty( $args['class'] ) ? $args['class'] : 'detail-info';
$post_type = ! empty( $args['post_type'] ) ? $args['post_type'] : get_post_type( $post_id );
if ( is_single( $post_id ) || get_post_type( $post_id ) == 'append-data' || get_post_type( $post_id ) == 'offers' ) {
	$field = ! in_array( $post_type, array( 'listings', 'used-listings' ) ) ? $post_type . '-detail_info_detail_info' : 'detail_info';
} else {
	$field = 'listings-detail_srp_detail_info';
}

if ( have_rows( $field, 'options' ) ) :
	?>
	<dl class="<?php echo esc_attr( $class ); ?>">
		<?php
		while ( have_rows( $field, 'options' ) ) :
			the_row();
			$label = get_sub_field( 'label' );
			if ( $label ) :
				?>
				<dt><?php echo esc_html( $label ); ?></dt>
				<?php
			endif;
			$value = get_sub_field( 'value' );
			if ( $value ) :
				$value = explode( ' ', $value );
				?>
				<dd
					<?php
					if ( str_contains( strtolower( $label ), 'vin' ) ) :
						?>
					class="vin" <?php endif; ?>>
					<?php

					if ( ! empty( $value ) ) {

						if ( is_array( $value ) ) {
							$value = implode( ' ', $value );
						}

						$result = preg_replace_callback(
							'/\b([a-z_]+)\b/',
							function ( $match ) use ( $post_id ) {

								$field = $match[1];

								if ( function_exists( 'get_field_object' ) && get_field_object( $field, $post_id ) ) {
									$acf_value = get_field( $field, $post_id );

									if ( $acf_value !== null && $acf_value !== '' ) {
										return $acf_value;
									}
								}

								return $match[0];
							},
							$value
						);

						echo str_replace( '&nbsp;', ' ', esc_html( $result ) );
					}

					?>
				</dd>
				<?php
			endif;
		endwhile;
		?>
	</dl>
	<?php
endif;

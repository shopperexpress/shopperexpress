<?php
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
	<dl class="<?php echo $class; ?>">
		<?php
		while ( have_rows( $field, 'options' ) ) :
			the_row();
			if ( $label = get_sub_field( 'label' ) ) :
				?>
				<dt><?php echo esc_html( $label ); ?></dt>
				<?php
			endif;
			if ( $value = get_sub_field( 'value' ) ) :
				$value = explode( ' ', $value );
				?>
				<dd
				<?php
				if ( str_contains( strtolower( $label ), 'vin' ) ) :
					?>
					class="vin"<?php endif; ?>>
					<?php
					$terms = array();
					foreach ( $value as $item ) {
						$item    = str_replace( '-', '_', $item );
						$term    = get_field( $item, $post_id );
						$terms[] = $term ? $term : get_field( $item, $post_id );
					}

					if ( $terms ) {
						echo implode( ' ', $terms );
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

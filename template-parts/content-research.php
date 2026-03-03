<?php
$post_id = ! empty( $args['post_id'] ) ? $args['post_id'] : get_the_id();
$make    = wps_get_term( $post_id, 'make-research' );
$year    = wps_get_term( $post_id, 'year-research' );
$model   = wps_get_term( $post_id, 'model-research' );

?>
<div class="col-sm-6 col-lg-4 col-xxl-3">
	<div class="card">
		<div class="card-body">
			<a href="<?php echo get_the_permalink( $post_id ); ?>">
				<div class="card-head">
					<span class="card-brand"><?php echo $year; ?> <?php echo $make; ?></span>
					<strong class="card-model"><?php echo $model; ?></strong>
				</div>
			</a>
			<?php
			get_template_part(
				'template-parts/gallery',
				null,
				array(
					'post_type' => get_post_type( $post_id ),
					'post_id'   => $post_id,
				)
			);
			?>
			<a href="<?php echo get_the_permalink( $post_id ); ?>" class="btn btn-primary btn-custom btn-block"><?php esc_html_e( 'Explore', 'shopperexpress' ); ?></a>
			<?php if ( $searchinventory = get_field( 'searchinventory', $post_id ) ) : ?>
				<a href="<?php echo $searchinventory; ?>" class="btn btn-primary btn-custom btn-block"><?php esc_html_e( 'Search Inventory', 'shopperexpress' ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</div>

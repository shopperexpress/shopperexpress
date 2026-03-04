<?php
/**
 * ACF Tabs Slider
 *
 * @package ShopExpress
 */

$title                      = get_sub_field( 'heading' ) ? get_sub_field( 'heading' ) : get_field( 'title_slider', 'options' );
$section_bg                 = get_field( 'section_bg', 'options' );
$slide_bg                   = get_field( 'slide_bg', 'options' );
$show_count                 = get_field( 'show_count', 'options' );
$index                      = get_row_index();
$sort_by_number_of_vehicles = get_sub_field( 'sort_by_number_of_vehicles' );

if ( have_rows( 'slider', 'options' ) ) :
	?>
	<section class="shop-section filter-section" 
	<?php
	if ( $section_bg ) {
		echo 'style="background-color:' . $section_bg . ';"';}
	?>
	>
		<div class="container-fluid">
			<?php if ( $title ) : ?>
				<h2 class="text-center"><?php echo $title; ?></h2>
			<?php endif; ?>
			<ul class="models-filter list-unstyled" data-filter-group="car-type">
				<li class="active"><a href="#" data-filter="all"><?php _e( 'all vehicles', 'shopperexpress' ); ?></a></li>
				<?php
				while ( have_rows( 'slider', 'options' ) ) :
					the_row();
					$type                         = get_sub_field( 'type' );
					$type_list[ seoUrl( $type ) ] = $type;
				endwhile;
				foreach ( $type_list as $id => $value ) :
					?>
					<li><a href="#" data-filter="<?php echo $id; ?>"><?php echo $value; ?></a></li>
				<?php endforeach; ?>
			</ul>
			<div class="model-slider">
				<?php
				$slides = array();

				if ( have_rows( 'slider', 'options' ) ) :

					while ( have_rows( 'slider', 'options' ) ) :
						the_row();

						$model     = get_sub_field( 'model' );
						$year      = get_sub_field( 'year' );
						$make      = get_sub_field( 'make' );
						$condition = get_sub_field( 'condition' );
						$row       = get_row_index();

						$count = null;

						if ( $show_count ) {
							$count = get_listings_count(
								$year,
								$make,
								$model,
								$condition,
								$index,
								$row
							);
						}

						$slides[] = array(
							'count'     => $count,
							'model'     => $model,
							'year'      => $year,
							'make'      => $make,
							'condition' => $condition,
							'label'     => get_sub_field( 'label' ),
							'image'     => get_sub_field( 'image' ),
							'url'       => get_sub_field( 'url' ),
							'type'      => get_sub_field( 'type' ),
						);

					endwhile;

					if ( $sort_by_number_of_vehicles ) {
						usort(
							$slides,
							function ( $a, $b ) {
								return $b['count'] <=> $a['count'];
							}
						);
					}
					?>
					<?php
					foreach ( $slides as $slide ) :
						$label = sprintf(
							esc_html__( 'Select %1$s image description %2$s %3$s', 'shopperexpress' ),
							$slide['count'],
							$slide['label'],
							seoUrl( $slide['type'] )
						);
						?>
						<div class="slide">
							<a class="model-card" href="<?php echo esc_url( $slide['url'] ); ?>" aria-label="<?php echo esc_attr( $label ); ?>">
								<?php if ( $slide['image'] ) : ?>
									<div class="img-box" 
									<?php
									if ( $slide_bg ) {
										echo 'style="background-color:' . $slide_bg . ';"';}
									?>
									>
										<?php if ( $show_count && $slide['count'] ) : ?>
											<strong class="num"><?php echo $slide['count']; ?></strong>
										<?php endif; ?>
										<img src="<?php echo esc_url( $slide['image']['url'] ); ?>" alt="image description">
									</div>
								<?php endif; ?>
								<?php if ( $slide['label'] ) : ?>
									<strong class="model"><?php echo esc_html( $slide['label'] ); ?></strong>
								<?php endif; ?>
								<span class="car-type hidden">
									<?php echo seoUrl( $slide['type'] ); ?>
								</span>
							</a>
						</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
					<?php
	endif;
endif;

<?php
/**
 * The template part for displaying the Accordion Detail Info block.
 *
 * @package ShopperExpress
 */

$vin_number = $args['vin_number'];
if ( function_exists( 'CallAPI' ) && $vin_number ) :
	CallAPI(
		get_field( 'url_chromedata', 'options' ),
		array(
			'VIN'             => $vin_number,
			'onlyDecodeUsing' => 'V,E,C,S',
		),
		get_the_id()
	);
	$features  = array();
	$images    = array();
	$post_type = get_post_type();
	$type      = in_array( $post_type, array( 'finance-offers', 'lease-offers', 'conditional-offers' ) ) ? $post_type . '_' : null;

	while ( have_rows( $type . 'features_items' ) ) :
		the_row();
		while ( have_rows( 'features' ) ) :
			the_row();
			$features[] = array(
				'ranking' => get_sub_field( 'ranking' ),
				'feature' => get_sub_field( 'feature' ),
				'id'      => get_sub_field( 'id' ),
			);
		endwhile;
	endwhile;

	usort(
		$features,
		function ( $a, $b ) {
			return (int) $b['ranking'] - (int) $a['ranking'];
		}
	);


	while ( have_rows( 'feature_list_chromedata', 'options' ) ) :
		the_row();
		$images[ get_sub_field( 'id' ) ] = array(
			'icon' => get_sub_field( 'icon' ),
			'text' => get_sub_field( 'text' ),
		);
	endwhile;


	if ( have_rows( $type . 'features_items' ) ) :
		if ( get_field( 'show_feature_list', 'options' ) ) :
			?>
			<section class="section-key-features">
				<div class="container">
					<div class="heading">
						<h3><?php esc_html_e( 'Highlighted Features', 'shopperexpress' ); ?></h3>
					</div>
					<ul class="key-features-list list-unstyled">
						<?php
						$limit = get_field( 'limit_feature_list', 'options' );
						foreach ( $features as $index => $feature ) :
							$index = $index + 1;
							if ( $index <= $limit ) :
								$id   = $feature['id'];
								$icon = isset( $images[ $id ]['icon'] ) ? $images[ $id ]['icon'] : null;
								$text = isset( $images[ $id ]['text'] ) ? $images[ $id ]['text'] : $feature['feature'];
								?>
								<li data-id="<?php echo $id; ?>">
									<?php
									if ( $icon ) {
										echo '<span class="icon">' . get_attachment_image( $icon ) . '</span>';
									}
									echo wpautop( $text );
									?>
								</li>
								<?php
							endif;
						endforeach;
						?>
					</ul>
				</div>
			</section>
		<?php endif; ?>
		<div class="accordion-detail-info">
			<div class="container">
				<ul class="accordion-detail" id="accordionDetail">
					<?php
					$i = 1;
					while ( have_rows( $type . 'features_items' ) ) :
						the_row();
						?>
						<li>
							<div id="heading-<?php echo $i; ?>">
								<h3>
									<button class="accordion-detail-opener
									<?php
									if ( $i != 1 ) :
										?>
										collapsed<?php endif; ?>" type="button" data-toggle="collapse" data-target="#collapse-<?php echo $i; ?>" aria-expanded="
										<?php
										if ( $i == 1 ) :
											?>
										true
																				<?php
																			else :
																				?>
	false<?php endif; ?>" aria-controls="collapse-<?php echo $i; ?>">
										<?php the_sub_field( 'heading' ); ?>
										<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="40px" viewBox="0 -960 960 960" width="40px" fill="#000000">
											<path
												d="m587-481.33-308-308q-12.33-12.34-12.17-30.17.17-17.83 12.5-30.83 13-13 30.84-13 17.83 0 30.83 13l321.67 322q10 10 14.66 22.33 4.67 12.33 4.67 24.67 0 12.33-4.67 24.66-4.66 12.34-14.66 22.34L340-111.67q-13 13-30.33 12.5-17.34-.5-30.34-13.5-12.33-13-12.66-30.5-.34-17.5 12.66-30.5L587-481.33Z" />
										</svg>
									</button>
								</h3>
							</div>
							<div id="collapse-<?php echo $i; ?>" class="collapse
							<?php
							if ( $i == 1 ) :
								?>
								show<?php endif; ?>" aria-labelledby="heading-<?php echo $i; ?>" data-parent="#accordionDetail">
								<div class="card-body">
									<ul class="options-list">
										<?php
										while ( have_rows( 'features' ) ) :
											the_row();
											?>
											<li><?php the_sub_field( 'feature' ); ?></li>
										<?php endwhile; ?>
									</ul>
								</div>
							</div>
						</li>
						<?php
						++$i;
					endwhile;
					?>
				</ul>
			</div>
		</div>
		<?php
	endif;
endif;

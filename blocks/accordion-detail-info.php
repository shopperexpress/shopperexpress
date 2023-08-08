<?php
if ( function_exists( 'CallAPI' ) && $vin_number ) :
	CallAPI( get_field( 'url_chromedata', 'options' ), [ 'VIN' => $vin_number, 'onlyDecodeUsing' => 'V,E,C,S' ], get_the_id() );
	if ( have_rows( 'features_list' ) ) : ?>
		<div class="accordion-detail-info">
			<div class="container">
				<ul class="accordion-detail" id="accordionDetail">
					<?php
					$i = 1;
					while ( have_rows( 'features_list' ) ) : the_row();
						?>
						<li>
							<div id="heading-<?php echo $i; ?>">
								<h3>
									<button class="accordion-detail-opener collapsed" type="button" data-toggle="collapse" data-target="#collapse-<?php echo $i; ?>" aria-expanded="false" aria-controls="collapse-<?php echo $i; ?>">
										<?php the_sub_field( 'heading' ); ?>
										<i class="material-icons">arrow_forward_ios</i>
									</button>
								</h3>
							</div>
							<div id="collapse-<?php echo $i; ?>" class="collapse" aria-labelledby="heading-<?php echo $i; ?>" data-parent="#accordionDetail">
								<div class="card-body">
									<ul class="options-list">
										<?php while ( have_rows( 'features' ) ) : the_row(); ?>
											<li><?php the_sub_field( 'feature' ); ?></li>
										<?php endwhile; ?>
									</ul>
								</div>
							</div>
						</li>
						<?php
						$i++;
					endwhile;
					?>
				</ul>
			</div>
		</div>
		<?php
	endif;
endif;
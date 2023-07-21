<?php
if ( function_exists( 'CallAPI' ) && $vin_number ) :
	$output = CallAPI( 'GET', get_field( 'url_chromedata', 'options' ) . $vin_number );
	if ( !empty( $output ) ) :
		?>
		<div class="accordion-detail-info">
			<div class="container">
				<ul class="accordion-detail" id="accordionDetail">
					<?php
					$i = 1;
					foreach ( $output as $index => $item ) :
						?>
						<li>
							<div id="heading-<?php echo $i; ?>">
								<h3>
									<button class="accordion-detail-opener collapsed" type="button" data-toggle="collapse" data-target="#collapse-<?php echo $i; ?>" aria-expanded="false" aria-controls="collapse-<?php echo $i; ?>">
										<?php echo $index; ?>
										<i class="material-icons">arrow_forward_ios</i>
									</button>
								</h3>
							</div>
							<div id="collapse-<?php echo $i; ?>" class="collapse" aria-labelledby="heading-<?php echo $i; ?>" data-parent="#accordionDetail">
								<div class="card-body">
									<ul class="options-list">
										<?php foreach( $item as $value ) : ?>
											<li><?php echo $value['description']; ?></li>
										<?php endforeach; ?>
									</ul>
								</div>
							</div>
						</li>
						<?php
						$i++;
					endforeach;
					?>
				</ul>
			</div>
		</div>
		<?php
	endif;
endif;
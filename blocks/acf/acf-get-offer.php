<section class="section-get-offer bg-cover"<?php if( $background_image = get_sub_field( 'background_image' ) ): ?> style="background-image: url(<?php echo $background_image['url']; ?>);"<?php endif; ?>>
	<div class="container">
		<div class="row">
			<?php if ( $text = get_sub_field( 'text' ) ): ?>
				<div class="col-md-6 text-white text-block">
					<?php echo $text; ?>
				</div>
			<?php endif; ?>
			<div class="col-md-6">
				<div class="form-box">
					<form action="#" class="form-offer">
						<div class="heading">
							<p data-field="plate"><?php the_sub_field( 'plate_text' ); ?></p>
							<p data-field="vin"><?php the_sub_field( 'vin_text' ); ?></p>
							<div class="form-switcher">
								<label class="item" for="plate">
									<?php _e('plate','shopperexpress'); ?>
									<input type="radio" id="plate" data-name="plate" name="switcher-field">
								</label>
								<label class="item" for="vin">
									<?php _e('vin','shopperexpress'); ?>
									<input type="radio" id="vin" data-name="vin" name="switcher-field" checked="checked">
								</label>
							</div>
						</div>
						<input class="form-control input-lg inject-plate" data-field="plate" type="text" placeholder="<?php _e('LICENSE PLATE','shopperexpress'); ?>">
						<div class="form-group group-lg" data-field="vin">
							<input class="form-control inject-vin" type="text" placeholder="<?php _e('VIN','shopperexpress'); ?>">
							<div class="help-holder" data-field="vin">
								<a href="#" class="opener"><?php _e('Where can I find my VIN?','shopperexpress'); ?></a>
								<div class="popup">
									<a class="btn-close opener" href="#">
										<i class="material-icons"><?php _e('close','shopperexpress'); ?></i>
									</a>
									<h4 class="text-uppercase"><?php _e('WHERE IS MY VIN.','shopperexpress'); ?></h4>
									<p><?php _e('VIN = Vehicle Identification Number','shopperexpress'); ?></p>
									<ul class="help-list list-unstyled">
										<li>
											<img src="<?php echo get_template_directory_uri(); ?>/images/help-img-1.png" alt="image description">
											<p><?php _e('Look through the windshield from outside the car.','shopperexpress'); ?> </p>
										</li>
										<li>
											<img src="<?php echo get_template_directory_uri(); ?>/images/help-img-2.png" alt="image description">
											<p><?php _e('You may also find the VIN number on the driver\'s side door pillar.','shopperexpress'); ?></p>
										</li>
									</ul>
								</div>
							</div>
						</div>

						<select class="form-control input-sm inject-state">
							<option class="hideme"><?php _e('State','shopperexpress'); ?></option>
							<option value="al">AL</option>
							<option value="ak">AK</option>
							<option value="as">AS</option>
							<option value="az">AZ</option>
							<option value="ar">AR</option>
							<option value="ca">CA</option>
							<option value="co">CO</option>
							<option value="ct">CT</option>
							<option value="ce">CE</option>
							<option value="dc">DC</option>
							<option value="fl">FL</option>
							<option value="ga">GA</option>
							<option value="gu">GU</option>
							<option value="hi">HI</option>
							<option value="id">ID</option>
							<option value="il">IL</option>
							<option value="in">IN</option>
							<option value="ia">IA</option>
							<option value="ks">KS</option>
							<option value="ky">KY</option>
							<option value="la">LA</option>
							<option value="me">ME</option>
							<option value="md">M</option>
							<option value="ma">MA</option>
							<option value="mi">MI</option>
							<option value="mn">MN</option>
							<option value="ms">MS</option>
							<option value="mo">MO</option>
							<option value="mt">MT</option>
							<option value="ne">NE</option>
							<option value="nv">NV</option>
							<option value="nh">NH</option>
							<option value="nj">NJ</option>
							<option value="nm">NM</option>
							<option value="ny">NY</option>
							<option value="nc">NC</option>
							<option value="nd">ND</option>
							<option value="mp">MP</option>
							<option value="oh">OH</option>
							<option value="ok">OK</option>
							<option value="or">OR</option>
							<option value="pa">PA</option>
							<option value="pr">PR</option>
							<option value="ri">RI</option>
							<option value="sc">SC</option>
							<option value="tn">TN</option>
							<option value="tx">TX</option>
							<option value="ut">UT</option>
							<option value="vt">VT</option>
							<option value="ba">VA</option>
							<option value="wa">WA</option>
							<option value="wv">WV</option>
							<option value="wi">WI</option>
							<option value="wy">WY</option>
						</select>
						<input class="form-control inject-miles" type="text" placeholder="<?php _e('MILES','shopperexpress'); ?>">
						<button class="btn btn-light btn-block btn-submit" type="button"><?php _e('GET OFFER','shopperexpress'); ?></button>
						<span class="form-info"><?php the_sub_field( 'form_info' ); ?></span>
					</form>
				</div>
			</div>
		</div>
	</div>
</section>
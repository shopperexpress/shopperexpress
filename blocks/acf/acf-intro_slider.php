<?php

$details_button = get_sub_field('details_button');
$buttons = get_sub_field('buttons');

$args = array(
	'post_type'   => 'offers',
	'post_status' => 'publish',
	'posts_per_page' => -1,
);

$query = new WP_Query( $args );

if ($query->have_posts()): ?>
	<div class="visual">
		<div class="visual-holder">
			<div class="visual-slider slick-item">
				<?php while ($query->have_posts()): $query->the_post(); ?>
					<div>
						<a href="<?php echo get_permalink(); ?>">
							<picture>
								<source srcset="<?php echo get_field('intro_slider_img_sm'); ?>" media="(max-width: 1023px)">
									<img src="<?php echo get_field('intro_slider_img'); ?>" alt="<?php echo get_the_title(); ?>">
								</picture>
							</a>
						</div>
					<?php endwhile; ?>
					<?php wp_reset_query(); ?>
				</div>
				<?php if ($details_button): $link_target = $details_button['target'] ? $details_button['target'] : '_self'; ?>
					<a class="btn btn-primary btn-pill btn-icon btn-detail" href="<?php echo esc_url( $details_button['url'] ); ?>" target="<?php echo esc_attr( $link_target ); ?>"><i class="material-icons"><?php _e('info','shopperexpress'); ?></i><?php echo esc_html( $details_button['title'] ); ?></a>
				<?php endif; ?>
			</div>
			<div class="search-bar">
				<form action="<?php echo home_url( 'vehicles' ); ?>" class="search-bar-form search-row" data-action="<?php echo add_query_arg( ['autocomplete' => 1] , home_url() ); ?>">
					<i class="icon material-icons">search</i>
					<input class="form-control autocomplete" type="search" placeholder="<?php esc_html_e( 'Search Anything', 'shopperexpress' ); ?>" name="search" data-src="<?php echo add_query_arg( ['autocomplete' => 1] , home_url() ); ?>"/>
						<div class="ajax-drop">
							<strong><?php _e('sugestions','shopperexpress'); ?></strong>
							<ul class="autocomplete-results"></ul>
						</div>
					</form>
				<?php if ( have_rows( 'buttons' ) ) : ?>
					<ul class="btn-list list-unstyled">
						<?php
						while ( have_rows( 'buttons' ) ) : the_row();
							$text = get_sub_field( 'text' );
							$second_text = get_sub_field( 'second_text' );
							$icon = get_sub_field( 'icon' );
							?>
							<li>
								<a class="btn-custom" href="<?php echo esc_url( get_sub_field( 'url' ) ); ?>">
									<?php if ( $icon ) echo wp_get_attachment_image( $icon['id'], 'full', null, [ 'class' => 'icon' ] ); ?>
									<span class="link-text">
										<?php
										echo do_shortcode( $text );
										if ( $second_text ) :
											?>
											<strong class="link-title"><?php echo $second_text; ?></strong>
										<?php endif; ?>
									</span>
								</a>
							</li>
						<?php endwhile; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
	</div>
<?php endif; ?>
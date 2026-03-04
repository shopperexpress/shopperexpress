<?php
$show_count = get_field( 'show_count_in_menu', 'options' );
if ( have_rows( 'slider', 'options' ) ) :
	$type_list = array();
	while ( have_rows( 'slider', 'options' ) ) :
		the_row();
		$type                         = get_sub_field( 'type' );
		$type_list[ seoUrl( $type ) ] = $type;
	endwhile;
	?>
	<ul class="drop-category-holder" id="accordionCategory">
		<?php foreach ( $type_list as $index => $type ) : ?>
			<li>
				<a class="drop-category-opener collapsed" data-toggle="collapse" href="#collapse-<?php echo $index; ?>"><?php echo $type; ?><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ffffff">
						<path d="M480-344 240-584l56-56 184 184 184-184 56 56-240 240Z"></path>
					</svg></a>
				<ul id="collapse-<?php echo $index; ?>" class="collapse drop-category-list" data-parent="#accordionCategory">
					<?php
					while ( have_rows( 'slider', 'options' ) ) :
						the_row();
						if ( $type == get_sub_field( 'type' ) ) :
							?>
							<li>
								<a href="<?php echo esc_url( get_sub_field( 'url' ) ); ?>">
									<?php if ( $image = get_sub_field( 'image_small' ) ) : ?>
										<span class="img-box">
											<?php echo get_attachment_image( $image['id'], 'drop' ); ?>
										</span>
										<?php
									endif;

									the_sub_field( 'label' );
									if ( $show_count ) {
										$model     = get_sub_field( 'model' );
										$year      = get_sub_field( 'year' );
										$make      = get_sub_field( 'make' );
										$condition = get_sub_field( 'condition' );
										$row       = get_row_index();
										echo ' - ' . get_listings_count( $year, $make, $model, $condition, '1', $row );
									}
									?>
								</a>
							</li>
							<?php
						endif;
					endwhile;
					?>
				</ul>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>

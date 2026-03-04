<?php
$icon  = get_sub_field( 'icon' );
$title = get_sub_field( 'title' );
$link  = get_sub_field( 'link' );

if ( $icon || $title || $link ) :
	if ( get_sub_field( 'show_drop' ) != true ) :
		?>
		<li><?php display_svg_icon( get_sub_field( 'svg_icon' ) ); ?>
		<?php
		if ( $title ) :
			?>
			<span><?php echo $title; ?></span>
			<?php
endif;
		if ( $link ) {
			echo App\the_acf_button( $link );}
		?>
		</li>
	<?php else : ?>
		<li class="drop-schedule-popup">
			<?php display_svg_icon( get_sub_field( 'svg_icon' ) ); ?>
			<a class="btn-schedule" href="#"><?php echo $title; ?></a>
			<div class="dropdown-schedule">
				<a class="btn-close" href="#"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#fff" aria-hidden="true">
					<path d="M480-424 284-228q-11 11-28 11t-28-11q-11-11-11-28t11-28l196-196-196-196q-11-11-11-28t11-28q11-11 28-11t28 11l196 196 196-196q11-11 28-11t28 11q11 11 11 28t-11 28L536-480l196 196q11 11 11 28t-11 28q-11 11-28 11t-28-11L480-424Z"></path>
					</svg></a>
				<?php
				while ( have_rows( 'drop' ) ) :
					the_row();
					$title = get_sub_field( 'title' );

					if ( $title ) :
						?>
						<strong class="drop-title"><?php display_svg_icon( get_sub_field( 'svg_icon' ) ); ?><?php echo $title; ?></strong>
						<?php
					endif;
					if ( have_rows( 'list' ) ) :
						?>
						<ul>
							<?php
							while ( have_rows( 'list' ) ) :
								the_row();
								$first_text  = get_sub_field( 'first_text' );
								$second_text = get_sub_field( 'second_text' );

								if ( $first_text || $second_text ) :
									?>
									<li>
										<?php if ( $first_text ) : ?>
											<span><?php echo $first_text; ?></span>
											<?php
										endif;
										if ( $second_text ) :
											?>
											<span><?php echo $second_text; ?></span>
										<?php endif; ?>
									</li>
									<?php
								endif;
							endwhile;
							?>
						</ul>
						<?php
					endif;
				endwhile;
				?>
			</div>
		</li>
		<?php
	endif;
endif;

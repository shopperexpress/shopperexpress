<?php
/**
 * Buttons
 *
 * @package ShopperExpress
 */

if ( have_rows( 'buttons' ) ) :
	?>
	<section class="info-section">
		<div class="container">
			<div class="info-wrapp">
				<ul class="info-list">
					<?php
					while ( have_rows( 'buttons' ) ) :
						the_row();
						if ( $button_code = get_sub_field( 'button_code' ) ) :
							?>
							<li><?php echo $button_code; ?></li>   
							<?php
						endif;
					endwhile;
					?>
				</ul>
			</div>
		</div>
	</section>
<?php endif; ?>

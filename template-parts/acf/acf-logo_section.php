<!-- section location -->
<?php
$heading          = get_sub_field( 'heading' );
$logos_backgorund = get_sub_field( 'logos_backgorund' );
$logos_per_row    = get_sub_field( 'logos_per_row' );
$padding          = get_sub_field( 'remove_paddings' );
if ( have_rows( 'logos' ) ) :
	?>
	<section class="section-location"
	<?php
	if ( $padding ) :
		?>
		style="padding: 0;"<?php endif; ?>>
		<div class="container text-center">
			<?php
			if ( $heading ) {
				?>
				<h2><?php echo $heading; ?></h2><?php } ?>
			<ul class="location-logos <?php echo 'location-logos--columns-' . $logos_per_row; ?>">
				<?php
				while ( have_rows( 'logos' ) ) :
					the_row();
					$alt     = get_sub_field( 'alt' );
					$new_tab = get_sub_field( 'new_tab' ) ? ' target="_blank"' : null;
					?>
					<li>
						<a href="<?php the_sub_field( 'link' ); ?>" <?php echo $new_tab; ?> aria-label="<?php printf( esc_html__( 'Read more about %s', 'shopperexpress' ), $alt ); ?>" 
						<?php
						if ( $logos_backgorund ) {
							echo 'style="background-color:' . $logos_backgorund . ';"';}
						?>
						>
							<img src="<?php the_sub_field( 'image' ); ?>" alt="<?php echo $alt; ?>">
						</a>
					</li>
				<?php endwhile; ?>
			</ul>
		</div>
	</section>
<?php endif; ?>

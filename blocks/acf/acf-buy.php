<!-- section buy -->
<section class="section-buy">
	<div class="container">
		<div class="row text-center">
			<?php if ( $text = get_sub_field( 'text' ) ): ?>
				<div class="col-12 heading">
					<?php echo $text; ?>
				</div>
				<?php
			endif;
			while ( have_rows( 'columns' ) ) : the_row();
				?>
				<div class="col-md-4">
					<div class="icon">
						<?php the_sub_field( 'icon' ); ?>
					</div>
					<?php if ( $title = get_sub_field( 'title' ) ): ?>
						<h3 class="h2"><?php echo $title; ?></h3>
						<?php
					endif;
					the_sub_field( 'description' );
					?>
				</div>
			<?php endwhile; ?>
		</div>
	</div>
	<?php if ( $slogan = get_sub_field( 'slogan' ) ): ?>
		<strong class="slogan"><?php echo $slogan; ?></strong>
	<?php endif; ?>
</section>
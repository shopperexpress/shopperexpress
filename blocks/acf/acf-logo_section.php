<!-- section location -->
<?php $heading = get_sub_field('heading');
if(have_rows('logos')){ ?>
<section class="section-location">
	<div class="container text-center">
		<?php if($heading){ ?><h2><?= $heading ?></h2><?php } ?>
		<ul class="location-logos">
			<?php while(have_rows('logos')){ the_row(); ?>
			<li>
				<a href="<?php the_sub_field('link') ?>">
					<img src="<?php the_sub_field('image') ?>" alt="image description">
				</a>
			</li>
			<?php } ?>
		</ul>
	</div>
</section>
<?php } ?>
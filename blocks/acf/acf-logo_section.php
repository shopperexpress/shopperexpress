<!-- section location -->
<?php $heading = get_sub_field('heading');
$logos_backgorund = get_sub_field('logos_backgorund');
$logos_per_row = get_sub_field('logos_per_row');
if(have_rows('logos')){ ?>
<section class="section-location">
	<div class="container text-center">
		<?php if($heading){ ?><h2><?= $heading ?></h2><?php } ?>
		<ul class="location-logos <?php echo 'location-logos--columns-'.$logos_per_row; ?>">
			<?php while(have_rows('logos')){ the_row(); ?>
			<li>
				<a href="<?php the_sub_field('link') ?>" <?php if ($logos_backgorund) echo 'style="background-color:'.$logos_backgorund.';"' ?>>
					<img src="<?php the_sub_field('image') ?>" alt="image description">
				</a>
			</li>
			<?php } ?>
		</ul>
	</div>
</section>
<?php } ?>
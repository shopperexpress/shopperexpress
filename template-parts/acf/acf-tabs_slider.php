<?php
$title = get_sub_field('heading') ? get_sub_field('heading') : get_field('title_slider', 'options');
$section_bg = get_field('section_bg', 'options');
$slide_bg = get_field('slide_bg', 'options');
$show_count = get_field('show_count', 'options');
$index = get_row_index();

if (have_rows('slider', 'options')) :
?>
	<section class="shop-section filter-section" <?php if ($section_bg) echo 'style="background-color:' . $section_bg . ';"' ?>>
		<div class="container-fluid">
			<?php if ($title): ?>
				<h2 class="text-center"><?php echo $title; ?></h2>
			<?php endif; ?>
			<ul class="models-filter list-unstyled" data-filter-group="car-type">
				<li class="active"><a href="#" data-filter="all"><?php _e('all vehicles', 'shopperexpress'); ?></a></li>
				<?php
				while (have_rows('slider', 'options')) : the_row();
					$type = get_sub_field('type');
					$type_list[seoUrl($type)] = $type;
				endwhile;
				foreach ($type_list as $id => $value):
				?>
					<li><a href="#" data-filter="<?php echo $id; ?>"><?php echo $value; ?></a></li>
				<?php endforeach; ?>
			</ul>
			<div class="model-slider">
				<?php
				while (have_rows('slider', 'options')) : the_row();
					$model = get_sub_field('model');
					$year = get_sub_field('year');
					$make = get_sub_field('make');
					$count = null;
					$condition = get_sub_field('condition');
					$row = get_row_index();

					if ($show_count) {
						$count = get_listings_count($year, $make, $model, $condition, $index, $row);
					}
					$label = get_sub_field('label');
				?>
					<div class="slide">
						<a class="model-card" href="<?php echo esc_url(get_sub_field('url')); ?>" aria-label="<?php echo sprintf(esc_html__('Select %s image description %s %s', 'shopperexpress'), $count, $label, seoUrl(get_sub_field('type'))); ?>">
							<?php if ($image = get_sub_field('image')): ?>
								<div class="img-box" <?php if ($slide_bg) echo 'style="background-color:' . $slide_bg . ';"' ?>>
									<?php if ($show_count) : ?>
										<strong class="num"><?php if (!empty($count) && $count != 0) echo $count; ?></strong>
									<?php endif; ?>
									<img src="<?php echo $image['url']; ?>" srcset="<?php echo $image['url']; ?> 2x" alt="image description">
								</div>
							<?php endif; ?>
							<?php if ($label): ?>
								<strong class="model"><?php echo $label; ?></strong>
							<?php endif; ?>
							<span class="car-type hidden"><?php echo seoUrl(get_sub_field('type')); ?></span>
						</a>
					</div>
				<?php endwhile; ?>
			</div>
		</div>
	</section>
<?php
endif;

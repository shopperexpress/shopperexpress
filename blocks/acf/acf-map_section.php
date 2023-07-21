<?php
	$title = get_sub_field('title');
	$subtitle = get_sub_field('subtitle');
	$button = get_sub_field('button');
	$ltd = get_sub_field('ltd');
	$lng = get_sub_field('lng');
?>

<section class="section-find-us">
	<div class="container">
		<div class="row">
			<?php if ($title || $subtitle || $button): ?>
				<div class="col-md-6 col-lg-4">
					<div class="text-block">
						<?php if ($title) echo '<h2>'.$title.'</h2>'; ?>
						<?php if ($subtitle) echo '<address>'.$subtitle.'</address>'; ?>
						<?php if ($button) echo '<a class="btn btn-primary btn-pill" target="_blank" href="'.$button['url'].'">'.$button['title'].'</a>'; ?>
					</div>
				</div>
			<?php endif; ?>
			<?php if ($ltd && $lng): ?>
				<div class="col-md-6 col-lg-8">
					<div class="map-holder" data-coordinates="<?php echo $ltd.', '.$lng; ?>" data-styles="<?php echo get_stylesheet_directory_uri().'/js/map-styles.json'; ?>"></div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>

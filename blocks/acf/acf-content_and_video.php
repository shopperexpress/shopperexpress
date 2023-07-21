<?php
	$top_image = get_sub_field('top_image');
	$html = get_sub_field('html');
	$wistia_id = get_sub_field('wistia_id');
?>
<section class="section-awards">
	<div class="holder">
		<div class="container">
			<div class="row">
				<?php if ($top_image || $html): ?>
					<div class="col-md-6">
						<div class="card-about">
							<?php if ($top_image) echo '<img class="card-logo" src="'.$top_image['url'].'" alt="'.$top_image['alt'].'">'; ?>
							<?php if ($html) echo '<div class="card-holder">'.$html.'</div>'; ?>
						</div>
					</div>
				<?php endif; ?>
				<?php if ($wistia_id): ?>
					<div class="col-md-6">
						<div class="video-block">
							<div data-video='{"type": "wistia", "video": "<?php echo $wistia_id; ?>", "autoplay": false, "fluidWidth": true}'></div>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>

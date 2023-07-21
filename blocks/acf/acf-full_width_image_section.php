<?php
	$image = get_sub_field('image');
?>
<div class="section-full-width-image">
	<div class="container">
		<?php if ($image): ?>
			<div class="img-holder">
				<img src="<?php echo $image['url']; ?>" alt="<?php echo $image['alt']; ?>">
			</div>
		<?php endif; ?>
	</div>
</div>

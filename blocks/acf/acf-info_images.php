<?php $title = get_sub_field('title'); 
if($images = get_sub_field('images')){ ?>
<section class="section">
	<div class="container">
		<?php if($title){ ?><h3><?php echo $title; ?></h3><?php } ?>
		<?php foreach($images as $img)
			echo wp_get_attachment_image($img, 'full'); ?>
	</div>
</section>
<?php } ?>
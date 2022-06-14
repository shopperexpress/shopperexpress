<?php $icon = get_sub_field('icon_code');
$title = get_sub_field('title');
$subtitle = get_sub_field('subtitle');
$image = get_sub_field('image');
$image_decor = get_sub_field('image_decor');
$decor_position = get_sub_field('decor_position');
$bottom_button_code = get_sub_field('bottom_button_code');
$additional_image = get_sub_field('additional_image');
$centered_image = get_sub_field('centered_image') ? 'text-center' : ''; ?>
<section class="content-block trade <?php if($additional_image) echo 'preferred'; ?>">
	<div class="text-box">
		<div class="heading">
			<?php if($icon){ ?>
			<div class="icon">
				<?php echo $icon; ?>
			</div>
			<?php } ?>
			<?php if($title || $subtitle){ ?>
			<h2>
				<?php if($title){ ?><span><?php echo $title; ?></span><?php } ?>
				<?php echo $subtitle; ?>
			</h2>
			<?php } ?>
		</div>
		<div class="holder">
			<?php the_sub_field('text')?>
		</div>
		<?php echo $bottom_button_code; ?>
	</div>
	<?php if($image || $image_decor){ ?>
	<div class="img-box <?php echo $centered_image; ?>">
		<?php $class = ($image_decor && $decor_position != 'none') ? '' : 'rotate-image';
		if($image) echo wp_get_attachment_image($image, 'full', false, array('class' => $class)); ?>
		<?php if($image_decor && $decor_position != 'none'){ ?>
		<div class="add-img <?php echo $decor_position; ?>">
			<?php echo wp_get_attachment_image($image_decor, 'full'); ?>
		</div><?php }
		else{ 
			echo wp_get_attachment_image($image_decor, 'full', false, array('class' => 'descrition-image'));
		} ?>
	</div>
	<?php } ?>
	
	<?php if($additional_image) echo '<div class="img-wrapp">'.wp_get_attachment_image($additional_image, 'full').'</div>'; ?>
</section>
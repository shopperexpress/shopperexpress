<?php $heading = get_sub_field('title');
$text = get_sub_field('text');
$form = get_sub_field('form');
if($form || $heading || $text){ ?>
<section class="form-section">
	<div class="container">
		<?php if($heading || $text){ ?>
		<div class="heading text-center">
			<?php if($heading){ ?><h2><?= $heading ?></h2><?php } ?>
			<?= $text ?>
		</div>
		<?php } ?>
		<?php if($form) echo do_shortcode('[contact-form-7 id="'.$form->ID.'" title="'.$form->post_title.'" html_class="form-unlock"]') ?>
	</div>
</section>
<?php } ?>
<?php 
	$is_container = get_sub_field('is_container');
	$css_class = get_sub_field('css_class');
?>
<section class="section-html <?php if(get_sub_field('add_grey_background')) echo ' bg-gray'; if(get_sub_field('remove_paddings')) echo ' p-0';?><?php if ($css_class) echo ' '.$css_class; ?>">
	<?php if ($is_container) echo '<div class="container">'; ?>
		<?php echo get_sub_field( 'html' ); ?>
	<?php if ($is_container) echo '</div>'; ?>
</section>
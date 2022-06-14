<?php $logo = get_sub_field('logo');
$title = get_sub_field('title');
$text = get_sub_field('text');
if($logo || $title || $text){ ?>
	<div class="content-header">
		<div class="container">
			<?php if($title) echo '<h1>'.$title.'</h1>'; ?>
			<!-- page logo -->

			<?php if($logo): ?>
				<strong class="logo">
					<a href="<?php the_permalink(); ?>">
						<?php echo $logo; ?>
					</a>
				</strong>
			<?php endif; ?>
			<?php if($text) echo '<div class="text-holder">'.$text.'</div>'; ?>
		</div>
	</div>
<?php } ?>

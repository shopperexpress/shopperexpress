<?php if(have_rows('buttons')){ ?>
<section class="info-section">
	<div class="container">
		<div class="info-wrapp">
			<!-- info list -->
			<ul class="info-list">
			<?php while(have_rows('buttons')){ the_row();
				$button_code = get_sub_field('button_code');?>
				<li><?php echo $button_code; ?></li>			
			<?php } ?>
			</ul>
		</div>
	</div>
</section>
<?php } ?>
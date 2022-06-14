<?php

$block = get_row_layout();

switch ( $block ) {
	case 'video_section':
	$class = 'preferred';
	break;
	
	default:
	$class = 'intro-block';
	break;
}

$icon = get_sub_field('icon_code');
$title = get_sub_field('title');
$subtitle = get_sub_field('subtitle');
$image = get_sub_field('image');
$bottom_button_code = get_sub_field('bottom_button_code');
$bottom_image = get_sub_field('bottom_image');
$first_image = get_sub_field( 'first_image' );
$second_image = get_sub_field( 'second_image' );
$thrid_image = get_sub_field( 'thrid_image' );
?>
<div class="block">
	<div class="container">
		<?php if( $block == 'video_section' ): ?><div class="block-holder products-page"><?php endif; ?>
		<!-- content block -->
		<?php if($icon || $title || $subtitle || $image || $bottom_button_code || $bottom_image){ ?>
			<section class="content-block <?php echo $class; ?>">
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
					<?php
					if ( $link = get_sub_field( 'link' ) ){ echo wps_get_link($link,'more'); }
					echo $bottom_button_code;
					?>	
					<?php if($bottom_image) echo wp_get_attachment_image($bottom_image, 'full', false, array('class'=> 'item-img')); ?>					
				</div>
				<?php if($image) echo '<div class="img-box">'.wp_get_attachment_image($image, 'full').'</div>'; ?>
				<?php if ( $first_image || $second_image ): ?>
					<div class="img-box">
						<?php 
						if( $first_image ){ echo wp_get_attachment_image($first_image['id'],'full',false,['class' => 'rotate-image']); }
						if( $second_image ){ echo wp_get_attachment_image($second_image['id'],'full',false,['class' => 'descrition-image']); }
						?>
					</div>
					<?php 
				endif;
				if( $thrid_image ):
					?>
					<div class="img-wrapp">
						<?php echo wp_get_attachment_image($thrid_image['id'],'full'); ?>
					</div>
				<?php endif; ?>
			</section>
			<?php
		}
		if( $block == 'video_section' ): ?></div></div><?php endif;
		if(have_rows('blocks')){
			if( $block == 'video_section' ): ?><section class="video-holder"><div class="container"><?php else: ?><div class="block-holder"><?php endif; ?>
			<?php
			while(have_rows('blocks')){ the_row();
				switch ( $block ) {
					case 'video_section':
					get_template_part( 'blocks/acf/acf-video');
					break;

					default:
					get_template_part( 'blocks/acf/acf', get_row_layout());
					break;
				}
			}
			if( $block == 'video_section' ): ?> </div></section><?php else: ?></div><?php endif; ?>
		<?php } ?>
		<?php if( $block != 'video_section' ): ?>
	</div>
<?php endif; ?>
</div>
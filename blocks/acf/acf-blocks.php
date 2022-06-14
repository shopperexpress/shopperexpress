<div class="block">
	<div class="container">

		<section class="content-block intro-block">
			<div class="text-box">
				<div class="heading">
					<?php if ( $icon = get_sub_field( 'icon_intro' ) ): ?>
						<div class="icon">
							<?php echo $icon; ?>
						</div>
						<?php
					endif;
					if ( $title_intro = get_sub_field( 'title_intro' ) ):
						?>
						<h2><?php echo $title_intro; ?></h2>
					<?php endif; ?>
				</div>
				<?php if ( $content_intro = get_sub_field( 'content_intro' ) ): ?>
					<div class="holder">
						<?php echo $content_intro; ?>
					</div>
					<?php
				endif;

				$script_for_link_intro = get_sub_field( 'script_for_link_intro' );
				$title_for_link_intro = get_sub_field( 'title_for_link_intro' );

				if( $script_for_link_intro && $title_for_link_intro ):
					?>
					<a class="more" onclick="<?php echo $script_for_link_intro; ?>"><?php echo $title_for_link_intro; ?></a>
					<?php
				endif;
				if ( $first_image_intro = get_sub_field( 'first_image_intro' ) ) {
					echo wp_get_attachment_image($first_image_intro['id'],'full', false,['class' => 'item-img']);
				}
				?>
			</div>
			<?php if ( $second_image_intro = get_sub_field( 'second_image_intro' ) ) : ?>
				<div class="img-box">
					<?php echo wp_get_attachment_image($second_image_intro['id'],'full', false); ?>
				</div>
			<?php endif; ?>
		</section>
		<?php 
		$left_text = get_sub_field( 'left_text' );
		$right_text = get_sub_field( 'right_text' );
		$left_image = get_sub_field( 'left_image' );
		$right_image = get_sub_field( 'right_image' );

		if( $left_text || $right_image || $left_image || $right_text ):
			?>
			<div class="trade-sell">
				<div class="row">
					<div class="col-md-7 d-flex text-col justify-content-center justify-content-md-start">
						<?php if ( $left_text ): ?>
							<p><?php echo $left_text; ?></p>
						<?php endif; ?>
					</div>
					<div class="col-md-5 img-col">
						<?php if( $right_image ){ echo wp_get_attachment_image($right_image['id'],'full'); } ?>
					</div>
				</div>
				<div class="row">
					<div class="col-md-7 col-lg-8 order-2 order-md-1">
						<?php if( $left_image ){ echo wp_get_attachment_image($left_image['id'],'full'); } ?>
					</div>
					<div class="col-md-5 col-lg-4 order-1 order-md-2 align-items-center d-flex text-col justify-content-center justify-content-md-start">
						<?php if ( $right_text ): ?>
							<p><?php echo $right_text; ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<?php 
		endif;
		?>
		<div class="block-holder">
			<section class="content-block cash-offer">
				<div class="text-box">
					<div class="heading">
						<?php if ( $icon_block = get_sub_field( 'icon_block' ) ): ?>
							<div class="icon">
								<?php echo $icon_block; ?>
							</div>
							<?php
						endif;
						if ( $title_block = get_sub_field( 'title_block' ) ):
							?>
							<h2><?php echo $title_block; ?></h2>
						<?php endif; ?>
					</div>
					<?php if ( $content_block = get_sub_field( 'content_block' ) ): ?>
						<div class="holder">
							<?php echo $content_block; ?>
						</div>
						<?php
					endif;
					$script_for_link = get_sub_field( 'script_for_link' ); 
					$title_for_link = get_sub_field( 'title_for_link' );
					$first_image_block = get_sub_field( 'first_image_block' );
					$second_image_block = get_sub_field( 'second_image_block' );

					if( $script_for_link || $title_for_link ):
						?>
						<a class="more" onclick="<?php echo $script_for_link; ?>"><?php echo $title_for_link; ?></a>
					<?php endif; ?>
				</div>
				<div class="img-box">
					<?php echo wp_get_attachment_image($first_image_block['id'],'full'); ?>
					<?php if ( $second_image_block ): ?>
						<div class="add-img center-left">
							<?php echo wp_get_attachment_image($second_image_block['id'],'full'); ?>
						</div>
					<?php endif; ?>
				</div>
			</section>
		</div>

	</div>
</div>
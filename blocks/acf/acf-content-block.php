<div class="container">
	<div class="block-holder">
		<div class="block">
			<!-- content block -->
			<section class="content-block cash-offer">
				<div class="text-box">
					<div class="heading">
						<?php if ( $icon = get_sub_field( 'icon' ) ): ?>
							<div class="icon">
								<?php echo $icon; ?>
							</div>
							<?php
						endif;
						if ( $title = get_sub_field( 'title' ) ):
							?>
							<h2><?php echo $title; ?></h2>
						<?php endif; ?>
					</div>
					<?php if ( $text = get_sub_field( 'text' ) ): ?>
						<div class="holder">
							<?php echo $text; ?>
						</div>
						<?php
					endif;
					the_sub_field( 'link' );
					?>
				</div>
				<div class="img-box">
					<?php if( $first_image = get_sub_field( 'first_image' ) ) echo wp_get_attachment_image($first_image['id'],'full');  ?>
					<div class="add-img center-left">
						<?php if( $second_image = get_sub_field( 'second_image' ) ) echo wp_get_attachment_image($second_image['id'],'full');  ?>
					</div>
				</div>
			</section>
		</div>
	</div>
</div>

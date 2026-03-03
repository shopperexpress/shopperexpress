<?php
$icon               = get_sub_field( 'icon' );
$title              = get_sub_field( 'title' );
$subtitle           = get_sub_field( 'subtitle' );
$image              = get_sub_field( 'image' );
$image_decor        = get_sub_field( 'image_decor' );
$decor_position     = get_sub_field( 'decor_position' );
$bottom_button_code = get_sub_field( 'bottom_button_code' );
$additional_image   = get_sub_field( 'additional_image' );
$centered_image     = get_sub_field( 'centered_image' ) ? 'text-center' : '';
$class              = get_row_layout() == 'image_left_and_text_right' ? ' trade ' : '';
?>
<section class="content-block <?php echo $class; ?> <?php echo $additional_image ? 'preferred' : ''; ?>">
	<div class="text-box">
		<div class="heading">
			<?php if ( $icon ) : ?>
				<div class="icon">
					<?php
					$image_id = absint( $icon );
					echo wp_kses_post( wp_get_attachment_image( $image_id, 'full' ) );
					?>
				</div>
				<?php
			endif;
			if ( $title || $subtitle ) :
				?>
				<h2>
					<?php if ( $title ) : ?>
						<span><?php echo esc_html( $title ); ?></span>
						<?php
					endif;
					echo esc_html( $subtitle );
					?>
				</h2>
			<?php endif; ?>
		</div>
		<?php if ( $text = get_sub_field( 'text' ) ) : ?>
			<div class="holder">
				<?php echo $text; ?>
			</div>
			<?php
		endif;
		echo $bottom_button_code;
		?>
	</div>
	<?php if ( $image || $image_decor ) : ?>
		<div class="img-box <?php echo esc_attr( $centered_image ); ?>">
			<?php
			$class = ( $image_decor && $decor_position !== 'none' ) ? '' : 'rotate-image';
			if ( $image ) {
				echo wp_get_attachment_image( $image, 'full', false, array( 'class' => $class ) );
			}
			if ( $image_decor && $decor_position !== 'none' ) :
				?>
				<div class="add-img <?php echo esc_attr( $decor_position ); ?>">
					<?php echo wp_get_attachment_image( $image_decor, 'full' ); ?>
				</div>
				<?php
			else :
				echo wp_get_attachment_image( $image_decor, 'full', false, array( 'class' => 'descrition-image' ) );
			endif;
			?>
		</div>
		<?php
	endif;
	if ( $additional_image ) :
		?>
		<div class="img-wrapp">
			<?php echo wp_get_attachment_image( $additional_image, 'full' ); ?>
		</div>
	<?php endif; ?>
</section>

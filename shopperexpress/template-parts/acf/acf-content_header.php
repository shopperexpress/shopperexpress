<?php
/**
 * Content Header
 *
 * @package ShopperExpress
 */

$logo  = get_sub_field( 'logo_image' );
$title = get_sub_field( 'title' );
$text  = get_sub_field( 'text' );

if ( $logo || $title || $text ) :
	?>
	<div class="content-header">
		<div class="container">
			<?php if ( $title ) : ?>
				<h1>
					<?php echo wp_kses_post( $title ); ?>
				</h1>
				<?php
			endif;
			?>
			<?php if ( $logo ) : ?>
				<strong class="logo">
					<?php
					$image_id = absint( $logo );
					echo wp_kses_post( wp_get_attachment_image( $image_id, 'full' ) );
					?>
				</strong>
				<?php
			endif;
			if ( $text ) :
				?>
				<div class="text-holder">
					<?php echo $text; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
<?php endif; ?>

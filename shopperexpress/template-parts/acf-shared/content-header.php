<?php
/**
 * Shared: Content Header
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type int    $logo_image  Attachment ID for logo.
 *   @type string $title       Heading text (may contain HTML).
 *   @type string $text        Body HTML.
 *   @type string $anchor      Optional HTML id (Gutenberg block "HTML anchor") so other links on the page can scroll directly to this section.
 * }
 */

$logo       = $args['logo_image'] ?? null;
$title      = $args['title'] ?? '';
$text       = $args['text'] ?? '';
$anchor     = $args['anchor'] ?? '';
$is_preview = $args['is_preview'] ? ' id="page-container"' : ( $anchor ? ' id="' . esc_attr( $anchor ) . '"' : '' );

if ( $logo || $title || $text ) :
	?>
	<div <?php echo $is_preview; ?> class="content-header">
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

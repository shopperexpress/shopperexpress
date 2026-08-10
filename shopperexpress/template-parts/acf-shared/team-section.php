<?php
/**
 * Shared: Team Section
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type string $heading             Section heading.
 *   @type string $description        Section intro text.
 *   @type array  $members             Array of items, each with keys: photo, name, position, phone, email, category.
 *   @type string $footer_heading      "Join Our Team" style heading.
 *   @type string $footer_button_text  Footer CTA button label.
 *   @type array  $footer_button_url   ACF link array (url, title, target).
 * }
 */

$heading            = $args['heading'] ?? '';
$description        = $args['description'] ?? '';
$members            = $args['members'] ?? array();
$footer_heading     = $args['footer_heading'] ?? '';
$footer_button_text = $args['footer_button_text'] ?? '';
$footer_button_url  = $args['footer_button_url'] ?? null;

$categories = array();
foreach ( $members as $member ) {
	if ( ! empty( $member['category'] ) && ! in_array( $member['category'], $categories, true ) ) {
		$categories[] = $member['category'];
	}
}

if ( ! empty( $members ) ) :
	?>
	<section class="team-section">
		<div class="container">
			<?php if ( $heading || $description ) : ?>
				<div class="team-section__heading">
					<?php if ( $heading ) : ?>
						<h1><?php echo esc_html( $heading ); ?></h1>
					<?php endif; ?>
					<?php if ( $description ) : ?>
						<p><?php echo wp_kses_post( $description ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $categories ) ) : ?>
				<ul class="team-filters" data-filter-group="team-filter">
					<li class="active"><button type="button" data-filter="all"><?php esc_html_e( 'all', 'shopperexpress' ); ?></button></li>
					<?php foreach ( $categories as $category ) : ?>
						<li><button type="button" data-filter="<?php echo esc_attr( $category ); ?>"><?php echo esc_html( $category ); ?></button></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<div class="team-grid">
				<?php foreach ( $members as $member ) : ?>
					<?php
					$photo    = $member['photo'] ?? '';
					$name     = $member['name'] ?? '';
					$position = $member['position'] ?? '';
					$phone    = $member['phone'] ?? '';
					$email    = $member['email'] ?? '';
					$category = $member['category'] ?? '';
					?>
					<div class="card-team">
						<?php if ( $photo ) : ?>
							<?php echo wp_get_attachment_image( $photo, 'medium', false, array( 'alt' => esc_attr( $name ) ) ); ?>
						<?php endif; ?>
						<div class="card-team__body">
							<?php if ( $name ) : ?>
								<h3 class="card-team__name"><?php echo esc_html( $name ); ?></h3>
							<?php endif; ?>
							<?php if ( $position ) : ?>
								<span class="card-team__position"><?php echo esc_html( $position ); ?></span>
							<?php endif; ?>
							<?php if ( $phone || $email ) : ?>
								<div class="card-team__hover">
									<?php if ( $phone ) : ?>
										<p><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></p>
									<?php endif; ?>
									<?php if ( $email ) : ?>
										<p><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></p>
									<?php endif; ?>
								</div>
							<?php endif; ?>
							<?php if ( $category ) : ?>
								<span class="team-filter hidden"><?php echo esc_html( $category ); ?></span>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<?php if ( $footer_heading || $footer_button_text ) : ?>
				<div class="team-section__footer">
					<?php if ( $footer_heading ) : ?>
						<h3 class="h2"><?php echo esc_html( $footer_heading ); ?></h3>
					<?php endif; ?>
					<?php if ( $footer_button_text ) : ?>
						<a class="btn btn-primary btn-lg" href="<?php echo esc_url( $footer_button_url['url'] ?? '#' ); ?>" <?php echo ! empty( $footer_button_url['target'] ) ? 'target="' . esc_attr( $footer_button_url['target'] ) . '"' : ''; ?>><?php echo esc_html( $footer_button_text ); ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
<?php endif; ?>

<?php
/**
 * Shared: Team Section
 *
 * @package ShopperExpress
 *
 * @param array $args {
 *   @type string $heading             Section heading.
 *   @type string $description        Section intro text.
 *   @type array  $members             Array of items, each with keys: photo, name, position, phone, email, category, bio.
 *                                     `category` may contain multiple, comma-separated values (e.g. "Sales, Management")
 *                                     so a member can belong to more than one filter group. `bio` is shown in the
 *                                     details popup opened from the member's card.
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
	foreach ( wps_team_split_categories( $member['category'] ?? '' ) as $category ) {
		if ( ! in_array( $category, $categories, true ) ) {
			$categories[] = $category;
		}
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
				<?php foreach ( $members as $index => $member ) : ?>
					<?php
					$photo       = $member['photo'] ?? '';
					$name        = $member['name'] ?? '';
					$position    = $member['position'] ?? '';
					$phone       = $member['phone'] ?? '';
					$email       = $member['email'] ?? '';
					$bio         = $member['bio'] ?? '';
					$member_cats = wps_team_split_categories( $member['category'] ?? '' );
					$has_details = $name || $position || $phone || $email || $bio;
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
							<?php if ( $phone || $email || $has_details ) : ?>
								<div class="card-team__hover">
									<?php if ( $phone ) : ?>
										<p><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></p>
									<?php endif; ?>
									<?php if ( $email ) : ?>
										<p><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></p>
									<?php endif; ?>
									<?php if ( $has_details ) : ?>
										<button type="button" class="card-team__details-btn" data-toggle="modal" data-target="#teamMemberModal" data-member-index="<?php echo esc_attr( $index ); ?>">
											<?php esc_html_e( 'View Profile', 'shopperexpress' ); ?>
										</button>
									<?php endif; ?>
								</div>
							<?php endif; ?>
							<?php foreach ( $member_cats as $category ) : ?>
								<span class="team-filter hidden"><?php echo esc_html( $category ); ?></span>
							<?php endforeach; ?>
						</div>
					</div>
					<?php if ( $has_details ) : ?>
						<template class="team-modal-tpl" data-member-index="<?php echo esc_attr( $index ); ?>">
							<div class="team-modal-media">
								<?php if ( $photo ) : ?>
									<?php echo wp_get_attachment_image( $photo, 'large', false, array( 'alt' => esc_attr( $name ) ) ); ?>
								<?php endif; ?>
							</div>
							<div class="team-modal-info">
								<?php if ( $name ) : ?>
									<p class="team-modal-info__name"><?php echo esc_html( $name ); ?></p>
								<?php endif; ?>
								<?php if ( $position ) : ?>
									<p class="team-modal-info__position"><?php echo esc_html( $position ); ?></p>
								<?php endif; ?>
								<?php if ( $phone ) : ?>
									<p class="team-modal-info__phone"><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></p>
								<?php endif; ?>
								<?php if ( $email ) : ?>
									<p class="team-modal-info__email"><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></p>
								<?php endif; ?>
								<?php if ( $bio ) : ?>
									<div class="team-modal-info__bio"><?php echo wp_kses_post( $bio ); ?></div>
								<?php endif; ?>
							</div>
						</template>
					<?php endif; ?>
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
		<div class="modal fade team-modal" id="teamMemberModal" tabindex="-1" aria-labelledby="teamMemberModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
				<div class="modal-content">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000">
							<path
								d="M480-424 284-228q-11 11-28 11t-28-11q-11-11-11-28t11-28l196-196-196-196q-11-11-11-28t11-28q11-11 28-11t28 11l196 196 196-196q11-11 28-11t28 11q11 11 11 28t-11 28L536-480l196 196q11 11 11 28t-11 28q-11 11-28 11t-28-11L480-424Z" />
						</svg>
					</button>
					<div class="modal-body">
						<div class="team-modal-body"></div>
					</div>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>

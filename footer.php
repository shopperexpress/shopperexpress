<?php
$logo = get_field( 'logo', 'options' );
$footer_style = get_field( 'footer_style', 'options' );
?>
</main>
<footer id="footer">
	<div class="container<?php if ( $footer_style != 1 ): ?>-fluid<?php endif; ?>">
		<?php if ( $footer_style == 1 ): ?>
			<div class="row flex-sm-row-reverse">
				<div class="col-sm-4 col-md-6 col-lg-7 footer-col">
					<?php if ( $logo ): ?>
						<strong class="logo d-block d-sm-none">
							<a href="<?php echo esc_url(home_url()); ?>"><?php echo wp_get_attachment_image( $logo['id'], 'full' ); ?></a>
						</strong>
						<?php
					endif;
					if ( is_active_sidebar( 'footer-sidebar' ) ) dynamic_sidebar( 'footer-sidebar' );
					?>
				</div>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<?php if ( $logo ): ?>
						<strong class="logo d-none d-sm-block">
							<a href="<?php echo esc_url(home_url()); ?>"><?php echo wp_get_attachment_image( $logo['id'], 'full' ); ?></a>
						</strong>
					<?php endif; ?>
					<span class="copyright">© <?php echo date('Y'); ?> <?php if ( $copyright = get_field( 'copyright', 'options' ) ) echo wps_get_link($copyright); ?></span>
					<?php if ( have_rows( 'social_networks', 'options' ) ) : ?>
						<ul class="social-networks">
							<?php while ( have_rows( 'social_networks', 'options' ) ) : the_row(); ?>
								<li>
									<a href="<?php echo esc_url(get_sub_field( 'url' )); ?>" target="_blank">
										<?php
										switch ( get_sub_field( 'icon' ) ) {
											case 'twitter':
											?>
											<svg class="icon-twitter" viewBox="0 0 40 32">
												<path d="M39.486 3.784c-1.481 0.658-3.044 1.069-4.689 1.234 1.645-0.987 2.961-2.55 3.537-4.442-1.563 0.905-3.291 1.563-5.1 1.974-1.481-1.563-3.62-2.55-5.923-2.55-4.442 0-8.062 3.62-8.062 8.062 0 0.658 0.082 1.234 0.165 1.892-6.663-0.329-12.668-3.537-16.699-8.473-0.658 1.234-1.069 2.55-1.069 4.113 0 2.797 1.398 5.265 3.62 6.745-1.316-0.082-2.55-0.411-3.62-0.987v0.082c0 3.949 2.797 7.157 6.499 7.897-0.658 0.165-1.398 0.247-2.139 0.247-0.494 0-0.987-0.082-1.563-0.165 0.987 3.208 4.031 5.512 7.568 5.594-2.797 2.139-6.252 3.455-10.036 3.455-0.658 0-1.316 0-1.974-0.082 3.62 2.303 7.815 3.62 12.422 3.62 14.889 0 23.033-12.339 23.033-23.033 0-0.329 0-0.74 0-1.069 1.481-1.069 2.879-2.468 4.031-4.113z"></path>
											</svg>
											<?php
											break;
											case 'facebook':
											?>
											<svg class="icon-facebook" viewBox="0 0 32 32">
												<path d="M16.069 0c-8.866 0-16.069 7.203-16.069 16.069 0 8.035 5.887 14.684 13.576 15.861v-11.221h-4.087v-4.641h4.087v-3.532c0-4.017 2.424-6.234 6.095-6.234 1.732 0 3.602 0.346 3.602 0.346v3.948h-2.009c-2.009 0-2.632 1.247-2.632 2.494v3.048h4.433l-0.693 4.641h-3.74v11.221c7.688-1.247 13.576-7.896 13.576-15.931 0-8.866-7.203-16.069-16.139-16.069z"></path>
											</svg>
											<?php
											break;
											case 'instagram':
											?>
											<svg class="icon-instagram" viewBox="0 0 32 32">
												<path d="M16 7.771c-4.571 0-8.229 3.657-8.229 8.229s3.657 8.229 8.229 8.229c4.571 0 8.229-3.657 8.229-8.229s-3.657-8.229-8.229-8.229zM16 21.333c-2.895 0-5.333-2.362-5.333-5.333s2.362-5.333 5.333-5.333c2.971 0 5.333 2.362 5.333 5.333 0 2.895-2.438 5.333-5.333 5.333z"></path>
												<path d="M26.438 7.467c0 1.052-0.853 1.905-1.905 1.905s-1.905-0.853-1.905-1.905c0-1.052 0.853-1.905 1.905-1.905s1.905 0.853 1.905 1.905z"></path>
												<path d="M31.848 9.371c-0.076-2.514-0.686-4.8-2.59-6.705-1.829-1.829-4.114-2.438-6.705-2.59-2.59-0.076-10.514-0.076-13.181 0-2.514 0.152-4.8 0.762-6.705 2.59-1.829 1.905-2.438 4.19-2.59 6.705-0.076 2.667-0.076 10.59 0 13.181 0.152 2.59 0.762 4.876 2.59 6.705 1.905 1.905 4.114 2.438 6.705 2.59 2.667 0.152 10.59 0.152 13.181 0s4.8-0.686 6.705-2.59c1.905-1.905 2.438-4.114 2.59-6.705s0.152-10.514 0-13.181zM28.495 25.448c-0.533 1.371-1.6 2.438-3.048 3.048-2.133 0.838-7.086 0.61-9.448 0.61s-7.314 0.152-9.448-0.61c-1.371-0.533-2.438-1.6-3.048-3.048-0.838-2.133-0.61-7.162-0.61-9.448 0-2.362-0.152-7.314 0.61-9.448 0.533-1.371 1.6-2.438 3.048-3.048 2.133-0.838 7.086-0.61 9.448-0.61s7.314-0.152 9.448 0.61c1.371 0.533 2.438 1.6 3.048 3.048 0.838 2.133 0.61 7.086 0.61 9.448 0 2.286 0.229 7.314-0.61 9.448z"></path>
											</svg>
											<?php
											break;

										} ?>

									</a>
								</li>
							<?php endwhile; ?>
						</ul>
					<?php endif; ?>
				</div>
			</div>
		<?php else: ?>
			<?php if ( is_active_sidebar( 'footer-sidebar' ) ) : ?>
				<div class="row footer-holder">
					<?php dynamic_sidebar( 'footer-sidebar' ); ?>
				</div>
			<?php endif; ?>
			<div class="row">
				<div class="col-sm-6">
					<span class="copyright"><?php _e('Version','shopperexpress'); ?> <?php echo wp_get_theme()->get( 'Version' ); ?> © <?php echo date('Y'); ?> <?php if( $link = get_field( 'copyright', 'options' ) ){ echo wps_get_link($link); } ?></span>
				</div>
				<div class="col-sm-6">
					<?php if ( $text_right_side = get_field( 'text_right_side', 'options' ) ): ?>
						<span class="by"><?php echo $text_right_side; ?></span>
					<?php endif; ?>
				</div>
			</div>
		<?php endif ?>
	</div>
</footer>
</div>
<?php if ( is_single() ): ?>
	<!-- Overview Modal -->
	<div class="modal fade content-scrollable" id="overviewModal" tabindex="-1" aria-labelledby="overviewModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header justify-content-center">
					<h3 class="modal-title"><?php _e('Overview','shopperexpress'); ?></h3>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<i class="material-icons"><?php _e('close','shopperexpress'); ?></i>
					</button>
				</div>
				<div class="modal-body">
					<div class="text-holder jcf-scrollable">
						<?php the_field( 'vehicle_overview' ); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- Features And Options Modal -->
	<div class="modal fade content-scrollable" id="featuresAndOptionsModal" tabindex="-1" aria-labelledby="featuresAndOptionsModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header justify-content-center">
					<h3 class="modal-title"><?php _e('Features & Options','shopperexpress'); ?></h3>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<i class="material-icons"><?php _e('close','shopperexpress'); ?></i>
					</button>
				</div>
				<div class="modal-body">
					<div class="text-holder jcf-scrollable">
						<ul class="modal-content-list">
							<?php while ( have_rows( 'features_options' ) ) : the_row(); ?>
								<li><i class="fa-li fa fa-check"></i> <?php the_sub_field( 'text' ); ?></li>
							<?php endwhile; ?>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- Unlock savings Modal -->
	<div class="modal fade" id="unlockSavingsModal" tabindex="-1" aria-labelledby="unlockSavingsModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-md modal-form modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header justify-content-center">
					<h3 class="modal-title text-uppercase"><?php _e('UNLOCK SAVINGS','shopperexpress'); ?></h3>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<i class="material-icons"><?php _e('close','shopperexpress'); ?></i>
					</button>
				</div>
				<div class="modal-body">
					<div class="lead text-center">
						<p><?php _e('Pricing will be displayed instantly on the next page.','shopperexpress'); ?></p>
					</div>
					<form id="form-unlock" class="form-unlock register-form" action="<?php echo esc_url(home_url()); ?>" method="POST">
						<div class="register-message"></div>
						<div class="row row-sm">
							<div class="col-sm-6">
								<div class="form-group">
									<input id="first-name" class="form-control" type="text" placeholder="<?php _e('First Name','shopperexpress'); ?>" name="first-name" data-required="true" required="required">
								</div>
							</div>
							<div class="col-sm-6">
								<div class="form-group">
									<input id="last-name" class="form-control" type="text" placeholder="<?php _e('Last Name','shopperexpress'); ?>" name="last-name" data-required="true" required="required">
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<input id="email" class="form-control" type="email" placeholder="<?php _e('Email Address','shopperexpress'); ?>" name="email" data-required="true" required="required">
								</div>
							</div>
							<div class="col-sm-6">
								<div class="form-group">
									<input id="phone" class="form-control phone" type="tel" placeholder="<?php _e('Mobile Phone','shopperexpress'); ?>" name="phone" data-required="true" required="required">
								</div>
							</div>
							<div class="col-sm-6">
								<div class="form-group">
									<input id="zip" class="form-control zip-code" type="text" placeholder="<?php _e('Zip Code','shopperexpress'); ?>" name="zip" data-required="true" required="required">
									<input type="hidden" name="permalink" value="<?php the_permalink(); ?>">
								</div>
							</div>
						</div>
						<div class="text-center btn-holder">
							<input id="register-button" class="btn btn-primary btn-lg" type="submit" value="<?php _e('UNLOCK SAVINGS','shopperexpress'); ?>">
						</div>
					</form>
					<?php if ( $form_description = get_field( 'form_description', 'options' ) ): ?>
						<div class="form-description text-center">
							<?php echo $form_description; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<!-- Disclosure_lease Modal -->
	<div class="modal fade content-scrollable modal-offer" id="Disclosure_lease" tabindex="-1" aria-labelledby="DisclosureLeaseLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header justify-content-center">
					<h3 class="modal-title"><?php _e('Additional Information','shopperexpress'); ?></h3>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<i class="material-icons"><?php _e('close','shopperexpress'); ?></i>
					</button>
				</div>
				<div class="modal-body">
					<div class="text-holder jcf-scrollable">
						<?php
						the_field( 'disclosure_lease' );
						if ( $heading_save_offer = get_field( 'heading_save_offer', 'options' ) ) :
							?>
							<h4><?php echo $heading_save_offer; ?></h4>
							<?php
						endif;
						if ( is_singular( 'offers' ) ) {
							if ( $form_lease_special = get_field( 'form_lease_special', 'options' ) ) echo do_shortcode('[contact-form-7 id="'.$form_lease_special->ID.'" html_class="form-unlock"]');
						}
						?>

					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Disclosure_loan Modal -->
	<div class="modal fade content-scrollable modal-offer" id="Disclosure_loan" tabindex="-1" aria-labelledby="DisclosureLoanLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header justify-content-center">
					<h3 class="modal-title"><?php _e('Additional Information','shopperexpress'); ?></h3>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<i class="material-icons"><?php _e('close','shopperexpress'); ?></i>
					</button>
				</div>
				<div class="modal-body">
					<div class="text-holder jcf-scrollable">
						<?php
						the_field( 'disclosure_finance' );
						if ( $heading_save_offer = get_field( 'heading_save_offer', 'options' ) ) :
							?>
							<h4><?php echo $heading_save_offer; ?></h4>
							<?php
						endif;
						if ( is_singular( 'offers' ) ) {
							if ( $form_special_apr = get_field( 'form_special_apr', 'options' ) ) echo do_shortcode('[contact-form-7 id="'.$form_special_apr->ID.'" html_class="form-unlock"]');
						}
						?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Disclosure_Cash Modal -->
	<div class="modal fade content-scrollable modal-offer" id="Disclosure_Cash" tabindex="-1" aria-labelledby="DisclosureCashLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header justify-content-center">
					<h3 class="modal-title"><?php _e('Additional Information','shopperexpress'); ?></h3>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<i class="material-icons"><?php _e('close','shopperexpress'); ?></i>
					</button>
				</div>
				<div class="modal-body">
					<div class="text-holder jcf-scrollable">
						<?php
						the_field( 'disclosure_cash' );
						if ( $heading_save_offer = get_field( 'heading_save_offer', 'options' ) ) :
							?>
							<h4><?php echo $heading_save_offer; ?></h4>
							<?php
						endif;
						if ( is_singular( 'offers' ) ) {
							if ( $form_cash = get_field( 'form_cash', 'options' ) ) echo do_shortcode('[contact-form-7 id="'.$form_cash->ID.'" html_class="form-unlock"]');
						}
						?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php $popup_form = get_field( 'popup_form', 'options'); 
	if($popup_form){
		$link = get_field( 'link', 'options'); 
		$popup_form_before = get_field( 'popup_form_top_text', 'options'); 
		$popup_form_after = get_field( 'popup_form_bottom_text', 'options'); ?>
		<!-- Contact Modal -->
		<div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-md modal-form modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header justify-content-center">
						<h3 class="modal-title text-uppercase"><?= $link['title']?></h3>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<i class="material-icons">close</i>
						</button>
					</div>
					<div class="modal-body">
						<?php if($popup_form_before){ ?>
							<div class="lead text-center">
								<?= $popup_form_before ?>
							</div>
						<?php } ?>
						<?= do_shortcode('[contact-form-7 id="'.$popup_form->ID.'" html_class="form-unlock" title="'.$popup_form->post_title.'"]'); ?>
						<?php if($popup_form_after){ ?>
							<div class="form-description text-center">
								<?= $popup_form_after ?>
							</div>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}?>

<?php endif; ?>

<div class="modal fade modal-login" id="login_modal" tabindex="-1" aria-labelledby="loginModal" aria-hidden="true">
	<div class="modal-dialog modal-md modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header justify-content-center">
				<h3 class="modal-title" id="exampleModalLabel"><?php _e("Login to access different features", "shopperexpress"); ?></h3>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<i class="material-icons"><?php _e("close", "shopperexpress"); ?></i>
				</button>
			</div>
			<div class="modal-body">
				<form method="POST" id="automotive_login_form" action="<?php echo esc_url(home_url()); ?>">
					<strong class="login-message"></strong>
					<div class="row row-sm">
						<div class="col-sm-6">
							<div class="form-group">
								<input type="text" class="form-control username_input" placeholder="<?php _e("Username", "shopperexpress"); ?>">
							</div>
						</div>
						<div class="col-sm-6">
							<div class="form-group">
								<input type="password" class="form-control password_input" placeholder="<?php _e("Password", "shopperexpress"); ?>">
							</div>
						</div>
					</div>
					<div class="form-check">
						<input type="hidden" class="url" name="url" value="<?php the_permalink(); ?>">
						<input class="form-check-input ajax_login" type="checkbox" value="yes" id="remember_me" name="remember_me" data-nonce="<?php echo wp_create_nonce("ajax_login_none"); ?>">
						<label class="form-check-label" for="remember_me"><?php _e("Remember Me", "shopperexpress"); ?></label>
					</div>
				</form>
			</div>
			<div class="modal-footer justify-content-center">
				<button type="submit" formmethod="POST" formaction="<?php echo esc_url(home_url()); ?>" form="automotive_login_form" class="btn btn-primary btn-lg"><?php _e("Login", "shopperexpress"); ?></button>
			</div>
		</div>
	</div>
</div>
<?php wp_footer(); ?>
<?php the_field( 'for_script_footer', 'options' ); ?>
</body>
</html>

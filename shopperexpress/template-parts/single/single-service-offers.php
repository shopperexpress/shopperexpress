<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package Shopperexpress
 */

get_header();

while ( have_posts() ) :
	the_post();
	$post_type  = get_post_type();
	$post_id    = get_the_id();
	$dept       = get_field( 'dept' );
	$type       = get_field( 'type' );
	$expiration = get_field( 'expiration' );
	$title      = get_field( 'title' );
	?>
	<div class="detail-section">
		<div class="container">
			<div class="row">
				<div class="col-sm-6">
					<div class="sticky-box">
						<div class="detail-top-row">
							<ol class="breadcrumbs">
								<li><a href="<?php echo esc_url( get_post_type_archive_link( $post_type ) ); ?>"><?php _e( 'All Offers', 'shopperexpress' ); ?></a></li>
							</ol>
							<?php if ( $dept || $type ) : ?>
								<ul class="code-list text-right text-capitalize list-unstyled">
									<?php if ( $dept ) : ?>
										<li><?php echo $dept; ?></li>
										<?php
									endif;
									if ( $type ) :
										?>
										<li><?php echo $type; ?></li>
									<?php endif; ?>
								</ul>
								<?php
							endif;
							?>
						</div>
						<?php
						get_template_part( 'template-parts/components/detail', 'slider', array( 'post_type' => $post_type ) );
						get_template_part(
							'template-parts/detail',
							'info',
							array(
								'post_type' => $post_type,
								'post_id'   => $post_id,
							)
						);
						?>
					</div>
				</div>
				<div class="col-sm-6">
					<?php if ( $title ) : ?>
						<h2><?php echo esc_html( $title ); ?></h2>
					<?php endif; ?>
					<div class="info-block">
						<?php
						$icon    = get_field( 'icon-service-offers', 'options' );
						$heading = get_field( 'heading-service-offers', 'options' );

						if ( $icon || $heading ) :
							?>
							<div class="heading">
								<?php if ( $icon ) : ?>
									<span class="icon">
										<?php echo get_attachment_image( $icon['id'] ); ?>
									</span>
									<?php
								endif;
								if ( $heading ) :
									?>
									<h3><?php echo esc_html( $heading ); ?></h3>
								<?php endif; ?>
							</div>
							<?php
						endif;

						echo wpautop( get_field( 'offercontent' ) );

						if ( have_rows( 'buttons' ) ) {
							while ( have_rows( 'buttons' ) ) :
								the_row();
								$buttontext = get_sub_field( 'buttontext' );
								$buttonurl  = get_sub_field( 'buttonurl' );
								if ( $buttontext && $buttonurl ) :
									?>
									<a href="<?php echo esc_url( $buttonurl ); ?>" class="btn btn-primary btn-custom btn-block"><?php echo $buttontext; ?></a>
									<?php
								endif;
							endwhile;
						}

						$buttontext = get_field( 'buttontext' );
						$buttonurl  = get_field( 'buttonurl' );
						if ( $buttontext && $buttonurl ) :
							?>
							<a href="<?php echo esc_url( $buttonurl ); ?>" class="btn btn-primary btn-custom btn-block"><?php echo $buttontext; ?></a>
						<?php endif; ?>
						<?php
						$print_button_title = get_field( 'print_button_title', 'option' ) ? get_field( 'print_button_title', 'option' ) : esc_html__( 'Print Special', 'shopperexpress' );
						$email_button_title = get_field( 'email_button_title', 'option' ) ? get_field( 'email_button_title', 'option' ) : esc_html__( 'Send to Email', 'shopperexpress' );
						?>
						<a href="javascript:window.print()" class="btn btn-primary btn-custom btn-block"><?php echo esc_html( $print_button_title ); ?></a>
						<a class="btn btn-primary btn-custom btn-block" href="#" data-toggle="modal" data-target="#sendtoemail"><?php echo esc_html( $email_button_title ); ?></a>
						<div class="info-block info-block--top">
							<?php
							$loged = ! empty( $args['loged'] ) ? $args['loged'] : '';
							get_template_part(
								'template-parts/unlock',
								'button',
								array(
									'post_id' => $post_id,
									'loged'   => $loged,
								)
							);
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
	get_template_part(
		'template-parts/accordion',
		null,
		array(
			'post_type' => $post_type,
			'type'      => 'random',
		)
	);
	if ( $offerdisclaimer  = get_field( 'offerdisclaimer' ) ) :
		?>
		<div class="description-box">
			<div class="container-fluid">
				<?php echo wpautop( $offerdisclaimer ); ?>
			</div>
		</div>
		<?php
	endif;

	get_template_part(
		'template-parts/model',
		'slider',
		array(
			'title'      => get_field( 'title_slider', 'options' ),
			'section_bg' => get_field( 'service-offers_section_bg_offers', 'options' ),
			'slide_bg'   => get_field( 'service-offers_slide_bg_offers', 'options' ),
		)
	);

endwhile;
get_footer();
?>

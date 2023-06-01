<!DOCTYPE html> 
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
	<link rel="preconnect" href="https://fonts.gstatic.com">
	<?php
	the_field( 'for_script_header', 'options' );

	wp_head();
	$theme_color = get_field( 'theme_color', 'options' );
	$overlay_color = get_field( 'overlay_color', 'options' );
	$overlay_opacity = get_field( 'overlay_opacity', 'options' );

	if ( $theme_color = get_field( 'theme_color', 'options' ) ): ?>
		<style type="text/css">
		:root {
			<?php if( $theme_color ): ?>
				--primary: <?php echo $theme_color; ?>;
				--primary-rgb: <?php echo hexToRgb($theme_color); ?>;
				<?php
			endif;
			if( $overlay_color ):
				?>
				--overlay-color-rgb: <?php echo hexToRgb($overlay_color); ?>;
				<?php
			endif;
			if( $overlay_opacity ):
				?>
				--overlay-opacity: <?php echo $overlay_opacity; ?>;
			<?php endif; ?>
			<?php
			$font = get_field( 'font', 'options' );
			 switch ( $font ) {
				case 1:
				?>
				--font-family-base: "Montserrat", "Helvetica Neue", Arial, sans-serif;
				<?php
				break;
				
				default:
				?>
				--font-family-base: Arial Rounded MT Bold, Helvetica Rounded, Arial, sans-serif;
				<?php
				break;
			} ?>
		}
	</style>
	<script type="text/javascript">
		var pathInfo = {
			base: '<?php echo get_template_directory_uri(); ?>/',
			css: 'css/',
			js: 'js/',
			swf: 'swf/',
		}
	</script>
	<?php
endif;
?>
</head>
<?php $class = $font != 1 ? 'theme-inner' : null; ?>
<body <?php body_class($class); ?>>
	<?php
	wp_body_open();

	$login_link =  "#" ;
	$logout_link = wp_logout_url();

	?>
	<div id="wrapper">
		<header id="header">
			<nav class="navbar navbar-light">
				<div class="navbar-holder">
					<a class="nav-opener" href="#"><span></span></a>
					<?php if ( $logo = get_field( 'logo', 'options' ) ): ?>
						<?php if (get_field( 'logo_url', 'options' )) {
							$logo_url = get_field( 'logo_url', 'options' );
						} else {
							$logo_url = esc_url(home_url());
						}; ?>
						<a class="navbar-brand" href="<?php echo $logo_url; ?>">
							<?php echo wp_get_attachment_image($logo['id'],'full', false , ['class' => 'brand-img']); ?>
						</a>
					<?php endif; ?>
				</div>
				<div class="header-frame">
					<?php
					if( has_nav_menu( 'header' ) ){
						wp_nav_menu( array(
							'container' 	 => false,
							'theme_location' => 'header',
							'menu_class'     => 'main-navigation',
							'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
							'walker'         => new Header_Walker_Nav_Menu
						));
					}
					$title = get_field( 'title_slider', 'options' );
					if ( have_rows( 'slider' , 'options' ) ) :
						?>
						<div class="drop-models-popup group-models">
							<?php if ( $title ): ?>
								<a class="btn-models" href="#"><?php echo str_replace( [ '<mark>', '</mark>' ], [ '<span>', '</span>' ], $title ); ?></a>
							<?php endif; ?>
							<div class="dropdown-models">
								<div class="scroll-holder">
									<div class="jcf-scrollable">
										<ul class="drop-model-slider" data-filter-group="car-type">
											<li class="active"><a href="#" data-filter="all"><?php if ( $slider_first_heading = get_field( 'slider_first_heading', 'options' ) ){ echo $slider_first_heading; }else{ _e('all vehicles','shopperexpress'); } ?></a></li>
											<?php
											while ( have_rows( 'slider' , 'options' ) ) : the_row();
												$type = get_sub_field( 'type' );
												$type_list[seoUrl($type)] = $type;
											endwhile;
											foreach( $type_list as $id => $value ):
												?>
												<li><a href="#" data-filter="<?php echo $id; ?>"><?php echo $value; ?></a></li>
											<?php endforeach; ?>
										</ul>
									</div>
								</div>
								<ul class="drop-model-list list-unstyled">
									<?php while ( have_rows( 'slider' , 'options' ) ) : the_row(); ?>
										<li>
											<a href="<?php echo esc_url(get_sub_field( 'url' )); ?>">
												<?php if ( $image = get_sub_field( 'image' ) ): ?>
													<div class="img-box">
														<?php echo wp_get_attachment_image( $image['id'], 'full' ); ?>
													</div>
												<?php endif; ?>
												<?php if ( $model = get_sub_field( 'model' ) ): ?>
													<strong class="model"><?php echo $model; ?></strong>
												<?php endif; ?>
												<span class="car-type hidden"><?php echo seoUrl(get_sub_field( 'type' )); ?></span>
											</a>
										</li>
									<?php endwhile; ?>
								</ul>
							</div>
						</div>
					<?php endif; ?>
				</div>
				<a class="navigation-opener" href="#">
					<i class="menu-close material-icons">menu</i>
					<i class="menu-open material-icons">menu_open</i>
				</a>
				<div class="btn-toolbar">
					<?php if( has_nav_menu( 'drop-down' ) ):?>
						<div class="btn-group">
							<button type="button" class="btn header-btn btn-app dropdown-toggle" id="dropdownMenuApps" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								<i class="material-icons"><?php _e('apps','shopperexpress'); ?></i>
							</button>
							<?php wp_nav_menu( array(
								'container' 	 => false,
								'theme_location' => 'drop-down',
								'menu_class'     => 'dropdown-menu dropdown-menu-right',
								'items_wrap'     => '<div id="%1$s" class="%2$s" aria-labelledby="dropdownMenuApps">%3$s</div>',
								'walker'         => new Drop_Down_Walker_Nav_Menu
							)); ?>
						</div>
					<?php endif; ?>
					<div class="btn-group">
						<button type="button" class="btn header-btn btn-user dropdown-toggle" id="dropdownMenuUser" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<i class="material-icons"><?php _e('account_circle','shopperexpress'); ?></i>
						</button>
						<div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuUser">
							<?php if ( is_user_logged_in() ): ?>
								<a class="dropdown-item" href="<?php echo $logout_link; ?>" ><?php echo( isset( $awp_options['toolbar_logout'] ) && ! empty( $awp_options['toolbar_logout'] ) ? $awp_options['toolbar_logout'] : __( "Logout", "automotive" ) ) ?></a>
								<?php 
							else:
								?>
								<a class="dropdown-item" href="<?php echo $login_link; ?>" <?php echo( isset( $awp_options['toolbar_login_link'] ) && ! empty( $awp_options['toolbar_login_link'] ) ? "" : 'data-toggle="modal" data-target="#login_modal"' ); ?>><?php echo( isset( $awp_options['toolbar_login'] ) && ! empty( $awp_options['toolbar_login'] ) ? $awp_options['toolbar_login'] : __( "Login", "automotive" ) ) ?></a>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<?php if ( have_rows( 'menu_list' , 'options' ) ) : ?>
					<div class="main-nav">
						<ul id="nav">
							<?php
							while ( have_rows('menu_list' , 'options' ) ) : the_row();
								$link = get_sub_field( 'link' );
								$menu = get_sub_field( 'menu' );
								if ( get_row_layout() == 'link' && $link ) :
									?>
									<li>
										<ul>
											<li><?php echo wps_get_link($link, null,'<i class="material-icons">' . __('help','shopperexpress') . '</i>'); ?></li>
										</ul>
									</li>
									<?php
								elseif( get_row_layout() == 'menu' && $menu ):
									?>
									<li class="active">
										<?php if ( $title = get_sub_field( 'title' ) ): ?>
											<a class="slide-opener" href="#"><?php echo $title; ?> <i class="material-icons"><?php _e('expand_more','shopperexpress'); ?></i></a>
											<?php
										endif;
										wp_nav_menu( array(
											'container' 	 => false,
											'menu' 			 => $menu,
											'menu_class'     => 'menu-slide',
											'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
											'walker'         => new Custom_Walker_Nav_Menu
										));
										?>
									</li>
								<?php endif ?>
							<?php endwhile; ?>
						</ul>
					</div>
				<?php endif; ?>
			</nav>
		</header>
		<main id="main">
			<?php get_template_part( 'blocks/intro' ); ?>

<?php
/**
 * Template part for displaying header
 *
 * @package Shopperexpress
 */

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<!--          _
		_ _ __ | |_ _  ___ ___
	| | '_ \| __| |/ __/ _ \(R)
	| | | | | |_| | (_|  __/
	|_|_| |_|\__|_|\___\___|

||| <http://www.intice.com> - hey@intice.com - 855-747-7770 |||
-->
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
	<link rel="preconnect" href="https://fonts.gstatic.com">
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;0,900;1,400;1,500&family=Roboto:wght@400;500&display=swap">
	<?php
	wp_head();
	the_field( 'for_script_header', 'options' );
	$font                        = '';
	$theme_color                 = get_field( 'theme_color', 'options' );
	$overlay_color               = get_field( 'overlay_color', 'options' );
	$overlay_opacity             = get_field( 'overlay_opacity', 'options' );
	$header_nav_drop_bg          = get_field( 'header_nav_drop_bg', 'options' ) ? get_field( 'header_nav_drop_bg', 'options' ) : 'rgba(248, 246, 246, 1)';
	$header_nav_link_color       = get_field( 'header_nav_link_color', 'options' ) ? get_field( 'header_nav_link_color', 'options' ) : 'rgba(26, 26, 26, 1)';
	$header_nav_link_hover_color = get_field( 'header_nav_link_hover_color', 'options' ) ? get_field( 'header_nav_link_hover_color', 'options' ) : 'rgba(0, 116, 179, 1)';
	$theme_color                 = get_field( 'theme_color', 'options' );
	$active_ai                   = get_field( 'active_ai', 'option' );

	if ( $theme_color ) :
		?>
		<style type="text/css">
			:root {
				<?php
				if ( $theme_color ) :
					?>
				--primary: <?php echo esc_attr( $theme_color ); ?> !important;
				--primary-rgb: <?php echo esc_attr( hexToRgb( $theme_color ) ); ?> !important;
				--primary-rgba: <?php echo esc_attr( hexToRgb( $theme_color ) ); ?> !important;
					<?php
				endif;
				if ( $overlay_color ) :
					?>
				--overlay-color-rgb: <?php echo esc_attr( hexToRgb( $overlay_color ) ); ?> !important;
					<?php
				endif;
				if ( $overlay_opacity ) :
					?>
				--overlay-opacity: <?php echo esc_attr( $overlay_opacity ); ?> !important;
					<?php
				endif;
				if ( $header_nav_drop_bg ) :
					?>
				--header-nav-drop-bg: <?php echo esc_attr( $header_nav_drop_bg ); ?> !important;
					<?php
				endif;
				if ( $header_nav_link_color ) :
					?>
				--header-nav-link-color: <?php echo esc_attr( $header_nav_link_color ); ?> !important;
					<?php
				endif;
				if ( $header_nav_link_hover_color ) :
					?>
				--header-nav-link-hover-color: <?php echo esc_attr( $header_nav_link_hover_color ); ?> !important;
					<?php
				endif;
				$font = get_field( 'font', 'options' );
				switch ( $font ) {
					case 1:
						?>
						--font-family-base: "Montserrat", "Helvetica Neue", Arial, sans-serif;
						<?php
						break;
					case 3:
						?>
						--font-family-base: Inter, Arial, Helvetica Neue, sans-serif;

						<?php
						break;
					default:
						?>
						--font-family-base: Arial Rounded MT Bold, Helvetica Rounded, Arial, sans-serif;
						<?php
						break;
				}
				?>
			}
		</style>
		<?php
	endif;
	?>

</head>

<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<div id="wrapper">
		<header id="header">
			<?php if ( get_field( 'show_contact_bar', 'options' ) ) : ?>
				<div class="alert alert-contact">
					<?php if ( have_rows( 'contact_list_header', 'options' ) ) : ?>
						<ul class="contact-list d-none d-md-flex">
							<?php
							while ( have_rows( 'contact_list_header', 'options' ) ) :
								the_row();
								get_template_part( 'template-parts/contact_list', 'item' );
							endwhile;
							?>
						</ul>
						<?php
					endif;
					if ( have_rows( 'contact_list_header_mobile', 'options' ) ) :
						?>
						<ul class="contact-list mobile-list d-md-none">
							<?php
							while ( have_rows( 'contact_list_header_mobile', 'options' ) ) :
								the_row();
								get_template_part( 'template-parts/contact_list', 'item' );
							endwhile;
							?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<nav class="navbar navbar-light">
				<div class="navbar-holder">
					<button type="button" class="nav-opener" aria-label="<?php _e( 'Left menu toggle', 'shopperexpress' ); ?>">
						<svg class="menu-close" xmlns="http://www.w3.org/2000/svg" height="24px" aria-hidden="true" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
							<path d="M120-240v-80h720v80H120Zm0-200v-80h720v80H120Zm0-200v-80h720v80H120Z"></path>
						</svg>
						<svg class="menu-open" xmlns="http://www.w3.org/2000/svg" height="24px" aria-hidden="true" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
							<path d="M120-240v-80h520v80H120Zm664-40L584-480l200-200 56 56-144 144 144 144-56 56ZM120-440v-80h400v80H120Zm0-200v-80h520v80H120Z"></path>
						</svg>
					</button>
					<?php if ( have_rows( 'logo_list', 'options' ) ) : ?>
						<div class="logo-slider" data-autoplay-speed="3000">
							<?php
							while ( have_rows( 'logo_list', 'options' ) ) :
								the_row();
								?>
								<a class="slider-item" href="<?php the_sub_field( 'url' ); ?>">
									<?php
									$image = get_sub_field( 'image' );
									if ( $image ) {
										echo get_attachment_image( $image['id'] );
									}
									?>
								</a>
							<?php endwhile; ?>
						</div>
					<?php endif; ?>
					<?php if ( $logo = get_field( 'logo', 'options' ) ) : ?>
						<?php
						if ( get_field( 'logo_url', 'options' ) ) {
							$logo_url = get_field( 'logo_url', 'options' );
						} else {
							$logo_url = esc_url( home_url() );
						}
						?>
						<a class="navbar-brand" href="<?php echo $logo_url; ?>">
							<?php echo wp_get_attachment_image( $logo['id'], 'full', false, array( 'class' => 'brand-img' ) ); ?>
						</a>
					<?php endif; ?>
				</div>
				<div class="header-frame">
					<?php
					if ( has_nav_menu( 'header' ) ) {
						echo '<div class="nav-holder">';
						wp_nav_menu(
							array(
								'container'      => false,
								'theme_location' => 'header',
								'menu_class'     => 'main-navigation',
								'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
								'walker'         => new Header_Walker_Nav_Menu(),
							)
						);
						echo '</div>';
					}
					$title = get_field( 'title_slider', 'options' );
					if ( have_rows( 'slider', 'options' ) && get_field( 'hide_select_models', 'options' ) != true ) :
						?>
						<div class="drop-models-popup group-models">
							<?php if ( $title ) : ?>
								<a class="btn-models" href="#"><?php echo str_replace( array( '<mark>', '</mark>' ), array( '<span>', '</span>' ), $title ); ?></a>
							<?php endif; ?>
							<div class="dropdown-models">
								<div class="scroll-holder">
									<div class="jcf-scrollable">
										<ul class="drop-model-slider" data-filter-group="car-type">
											<li class="active"><a href="#" data-filter="all">
													<?php
													if ( $slider_first_heading = get_field( 'slider_first_heading', 'options' ) ) {
														echo $slider_first_heading;
													} else {
														_e( 'all vehicles', 'shopperexpress' );
													}
													?>
												</a></li>
											<?php
											while ( have_rows( 'slider', 'options' ) ) :
												the_row();
												$type                         = get_sub_field( 'type' );
												$type_list[ seoUrl( $type ) ] = $type;
											endwhile;
											foreach ( $type_list as $id => $value ) :
												?>
												<li><a href="#" data-filter="<?php echo $id; ?>"><?php echo $value; ?></a></li>
											<?php endforeach; ?>
										</ul>
									</div>
								</div>
								<ul class="drop-model-list list-unstyled">
									<?php
									while ( have_rows( 'slider', 'options' ) ) :
										the_row();
										?>
										<li>
											<a href="<?php echo esc_url( get_sub_field( 'url' ) ); ?>">
												<?php
												if ( $image = get_sub_field( 'image' ) ) :
													$image_small = get_sub_field( 'image_small' ) ? get_sub_field( 'image_small' ) : $image;
													?>
													<div class="img-box">
														<img src="<?php echo $image_small['url']; ?>" srcset="<?php echo $image_small['url']; ?> 2x" alt="<?php echo $image['alt']; ?> ">
													</div>
												<?php endif; ?>
												<?php if ( $model = get_sub_field( 'model' ) ) : ?>
													<strong class="model"><?php echo $model; ?></strong>
												<?php endif; ?>
												<span class="car-type hidden"><?php echo seoUrl( get_sub_field( 'type' ) ); ?></span>
											</a>
										</li>
									<?php endwhile; ?>
								</ul>
							</div>
						</div>
					<?php endif; ?>
				</div>
				<?php if ( $active_ai ) : ?>
					<button type="button" class="btn ai-chat__opener" aria-label="AI chat toggle">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 37.89 35.44" width="28px" fill="#fff">
							<path d="M22.3800049 16.6599693c-.289978.2900391-.4400024.6500244-.4400024 1.0600586 0 .4099731.1500244.7699585.4400024 1.0599976.289978.289978.6500244.4400024 1.0599976.4400024.4199829 0 .7700195-.1500244 1.0599976-.4400024.289978-.2900391.4400024-.6500244.4400024-1.0599976 0-.4100342-.1500244-.7700195-.4400024-1.0600586-.289978-.289978-.6500244-.4399414-1.0599976-.4399414s-.7700195.1499634-1.0599976.4399414Z
								M25.9500122 15.1099815v5.1000366h-14v-5h13c-.9000244 0-1.7300415-.1799927-2.5-.5400391-.7700195-.3599854-1.4299927-.8399658-1.9800415-1.4599609h-7.7299805l1.0499878-3h5.2300415c-.0300293-.1700439-.0500488-.3300171-.0599976-.4899902-.0100098-.1600342-.0100098-.3300171-.0100098-.5100098s0-.3500366.0100098-.5100098c.0099487-.1600342.0299683-.3200073.0599976-.4899902h-5.5800171c-.3099976 0-.5999756.0899658-.8599854.2799683s-.4400024.4299927-.5400391.7300415l-2.0999756 6v8c0 .2799683.0999756.5199585.289978.7099609s.4299927.2900391.710022.2900391l.0200195-.0100098h1c.2799683 0 .5199585-.1000366.7099609-.2900391s.2900391-.4299927.2900391-.7099609v-1h12v1c0 .2799683.0999756.5199585.289978.7099609s.4299927.2900391.710022.2900391h1c.2799683 0 .5199585-.1000366.7099609-.2900391s.2900391-.4299927.2900391-.7099609v-8.8300171c-.3200073.1799927-.6400146.3300171-.9800415.4500122-.3399658.1199951-.6799927.2099609-1.0299683.2799683Z
								M13.3800049 16.6599693c-.289978.2900391-.4400024.6500244-.4400024 1.0600586 0 .4099731.1500244.7699585.4400024 1.0599976.289978.289978.6500244.4400024 1.0599976.4400024.4199829 0 .7700195-.1500244 1.0599976-.4400024.289978-.2900391.4400024-.6500244.4400024-1.0599976 0-.4100342-.1500244-.7700195-.4400024-1.0600586-.289978-.289978-.6500244-.4399414-1.0599976-.4399414s-.7700195.1499634-1.0599976.4399414Z
								M25 4.8099937c-2.460022 0-4.460022 2-4.460022 4.460022 0 2.4599609 2 4.4599609 4.460022 4.4599609s4.460022-2 4.460022-4.4599609c0-2.460022-2-4.460022-4.460022-4.460022ZM25 11.9500084v.1300049l-.0100098-.1300049c-.0599976-1.4500122-1.2199707-2.6099854-2.6699829-2.6699829 1.4500122-.0599976 2.6099854-1.2200317 2.6699829-2.6700439v-.1300049l.0100098.1300049c.0599976 1.4500122 1.2199707 2.6100464 2.6699829 2.6700439-1.4500122.0599976-2.6099854 1.2199707-2.6699829 2.6699829Z
								M19.289978-.0000038C9.039978-.0000038.7000122 7.1199913.7000122 15.8800011c0 3.9500122 1.7199707 7.5499878 4.5299683 10.3300171-1.3299561 2.5199585-2.9699707 5.1599731-4.9699707 7.5100098-.2999878.3499756-.3300171.8499756-.0900269 1.2399902.1900024.3099976.5300293.4899902.8900146.4899902.0900269 0 .1799927-.0100098.2600098-.0300293.25-.0699463 4.7299805-1.2699585 10.0599976-5.1799927 2.4099731.9800415 5.0900269 1.5300293 7.9199829 1.5300293 10.25 0 18.6000366-7.1199951 18.6000366-15.8800049S29.5499878-.0000038 19.289978-.0000038ZM19.289978 29.6500206v.0199585c-2.9899902 0-5.789978-.6799927-8.2099609-1.8499756l-1.9000244 1.3699951c-1.8999634 1.289978-3.6499634 2.2399902-5.0499878 2.8900146 1.0200195-1.4700317 1.9099731-2.9700317 2.710022-4.4400024h.0099487l1-1.8599854c-3.1199951-2.5100098-5.0599976-6.0200195-5.0599976-9.9200439 0-7.5999756 7.4000244-13.789978 16.5-13.789978 9.1000366 0 16.5 6.1900024 16.5 13.789978 0 7.6000366-7.3999634 13.7900391-16.5 13.7900391Z"></path>
						</svg>
					</button>
				<?php endif; ?>
				<button type="button" class="navigation-opener" aria-label="<?php _e( 'Main navigation toggle', 'shopperexpress' ); ?>">
					<svg class="menu-close" xmlns="http://www.w3.org/2000/svg" height="24px" aria-hidden="true" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
						<path d="M120-240v-80h720v80H120Zm0-200v-80h720v80H120Zm0-200v-80h720v80H120Z"></path>
					</svg>
					<svg class="menu-open" xmlns="http://www.w3.org/2000/svg" height="24px" aria-hidden="true" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
						<path d="M120-240v-80h520v80H120Zm664-40L584-480l200-200 56 56-144 144 144 144-56 56ZM120-440v-80h400v80H120Zm0-200v-80h520v80H120Z"></path>
					</svg>
				</button>
				<div class="btn-toolbar">
					<?php if ( has_nav_menu( 'drop-down' ) ) : ?>
						<div class="btn-group">
							<button type="button" class="btn header-btn btn-app dropdown-toggle" aria-label="<?php _e( 'Toggle Dropdown', 'shopperexpress' ); ?>" id="dropdownMenuApps" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF" aria-hidden="true">
									<path d="M240-160q-33 0-56.5-23.5T160-240q0-33 23.5-56.5T240-320q33 0 56.5 23.5T320-240q0 33-23.5 56.5T240-160Zm240 0q-33 0-56.5-23.5T400-240q0-33 23.5-56.5T480-320q33 0 56.5 23.5T560-240q0 33-23.5 56.5T480-160Zm240 0q-33 0-56.5-23.5T640-240q0-33 23.5-56.5T720-320q33 0 56.5 23.5T800-240q0 33-23.5 56.5T720-160ZM240-400q-33 0-56.5-23.5T160-480q0-33 23.5-56.5T240-560q33 0 56.5 23.5T320-480q0 33-23.5 56.5T240-400Zm240 0q-33 0-56.5-23.5T400-480q0-33 23.5-56.5T480-560q33 0 56.5 23.5T560-480q0 33-23.5 56.5T480-400Zm240 0q-33 0-56.5-23.5T640-480q0-33 23.5-56.5T720-560q33 0 56.5 23.5T800-480q0 33-23.5 56.5T720-400ZM240-640q-33 0-56.5-23.5T160-720q0-33 23.5-56.5T240-800q33 0 56.5 23.5T320-720q0 33-23.5 56.5T240-640Zm240 0q-33 0-56.5-23.5T400-720q0-33 23.5-56.5T480-800q33 0 56.5 23.5T560-720q0 33-23.5 56.5T480-640Zm240 0q-33 0-56.5-23.5T640-720q0-33 23.5-56.5T720-800q33 0 56.5 23.5T800-720q0 33-23.5 56.5T720-640Z"></path>
								</svg>
							</button>
							<?php
							wp_nav_menu(
								array(
									'container'      => false,
									'theme_location' => 'drop-down',
									'menu_class'     => 'dropdown-menu dropdown-menu-right',
									'items_wrap'     => '<div id="%1$s" class="%2$s" aria-labelledby="dropdownMenuApps">%3$s</div>',
									'walker'         => new Drop_Down_Walker_Nav_Menu(),
								)
							);
							?>
						</div>
					<?php endif; ?>
					<?php
					$favorites = array(
						'listings'           => esc_html__( 'Saved New Listings', 'shopperexpress' ),
						'used-listings'      => esc_html__( 'Saved Used Listings', 'shopperexpress' ),
						'conditional-offers' => esc_html__( 'Saved Conditional Offers', 'shopperexpress' ),
						'lease-offers'       => esc_html__( 'Saved Lease Offers', 'shopperexpress' ),
						'finance-offers'     => esc_html__( 'Saved Finance Offers', 'shopperexpress' ),
						'offers'             => esc_html__( 'Saved Offers', 'shopperexpress' ),
					);
					if ( get_field( 'hide_favorite_icon', 'options' ) != true ) :
						$html                    = '';
						$favorites_count_all     = $favorites_count = 0;
						$page_for_saved_vehicles = get_field( 'page_for_saved_vehicles', 'options' );
						if ( $page_for_saved_vehicles ) :
							ob_start();
							foreach ( $favorites as $post_type => $label ) :
								$favorites_count     = get_user_favorites_count( null, null, array( 'post_type' => array( $post_type ) ) );
								$favorites_count_all = $favorites_count_all + $favorites_count;
								?>
								<a class="dropdown-item favorite-link" href="<?php echo add_query_arg( array( 'saved' => $post_type ), get_permalink( $page_for_saved_vehicles ) ); ?>">
									<svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#212529" aria-hidden="true">
										<path
											d="M480-170q-13 0-25.5-4.5T431-189l-59-54q-109-97-192.5-189.5T96-634q0-88.02 59.85-147.01Q215.7-840 305-840q51.2 0 96.6 21.5Q447-797 480-757q35-40 79.66-61.5t95.02-21.5Q744-840 804-781.01T864-634q0 109-83.5 201.5T588-243l-59 54q-11 10-23.5 14.5T480-170Zm-34-512q-24-41-60-63.5T305-768q-58.71 0-97.86 38Q168-692 168-633.61 168-583 204-527t86 109.5Q340-364 393-318t87 76q34-30 87-76t103-99.5Q720-471 756-527t36-106.61Q792-692 752.86-730q-39.15-38-97.86-38-45 0-81.5 22.5T513-682q-5 10-14.05 14.5t-19 4.5q-9.95 0-19.45-4.5T446-682Zm34 177Z" />
									</svg>
									<?php echo $label; ?> (<span class="favorite-<?php echo $post_type; ?>"><?php echo $favorites_count; ?></span>)</a>
								<?php
							endforeach;
							$html = ob_get_contents();
							ob_end_clean();
						endif;
						?>
						<div class="btn-group">
							<button type="button" class="btn header-btn btn-user dropdown-toggle 
							<?php
							if ( $favorites_count_all > 0 ) {
								echo 'active';
							}
							?>
							" aria-label="<?php _e( 'Toggle Dropdown', 'shopperexpress' ); ?>" id="dropdownMenuUser" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF" aria-hidden="true">
									<path
										d="M234-276q51-39 114-61.5T480-360q69 0 132 22.5T726-276q35-41 54.5-93T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 59 19.5 111t54.5 93Zm246-164q-59 0-99.5-40.5T340-580q0-59 40.5-99.5T480-720q59 0 99.5 40.5T620-580q0 59-40.5 99.5T480-440Zm0 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z" />
								</svg>
							</button>
							<div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuUser" data-user="<?php echo get_current_user_id(); ?>">
								<?php echo $html; ?>
							</div>
						</div>
					<?php endif; ?>
				</div>
				<?php if ( have_rows( 'menu_list', 'options' ) ) : ?>
					<div class="main-nav">
						<ul id="nav">
							<?php
							while ( have_rows( 'menu_list', 'options' ) ) :
								the_row();
								$link = get_sub_field( 'link' );
								$menu = get_sub_field( 'menu' );
								if ( get_row_layout() == 'link' && $link ) :
									?>
									<li>
										<ul>
											<li>
												<a href="<?php echo $link['url']; ?>">
													<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#4d4d4d">
														<path d="M478-240q21 0 35.5-14.5T528-290q0-21-14.5-35.5T478-340q-21 0-35.5 14.5T428-290q0 21 14.5 35.5T478-240Zm-36-154h74q0-33 7.5-52t42.5-52q26-26 41-49.5t15-56.5q0-56-41-86t-97-30q-57 0-92.5 30T342-618l66 26q5-18 22.5-39t53.5-21q32 0 48 17.5t16 38.5q0 20-12 37.5T506-526q-44 39-54 59t-10 73Zm38 314q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"></path>
													</svg>
													<?php echo $link['title']; ?>
												</a>
											</li>
										</ul>
									</li>
									<?php
								elseif ( get_row_layout() == 'menu' && $menu ) :
									?>
									<li class="active">
										<?php
										$title = get_sub_field( 'title' );
										if ( $title ) :
											?>
											<a class="slide-opener" href="#"><?php echo $title; ?>
												<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#999999">
													<path d="M480-344 240-584l56-56 184 184 184-184 56 56-240 240Z" />
												</svg>
											</a>
											<?php
										endif;
										wp_nav_menu(
											array(
												'container' => false,
												'menu'   => $menu,
												'menu_class' => 'menu-slide',
												'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>',
												'walker' => new Custom_Walker_Nav_Menu(),
											)
										);
									?>
									</li>
								<?php endif ?>
							<?php endwhile; ?>
						</ul>
					</div>
				<?php endif; ?>
				<?php
				if ( $active_ai ) :
					$heading_ai = get_field( 'heading_ai', 'options' );

					?>
					<div class="ai-chat-wrap">
						<span class="overlay"></span>
						<div class="ai-chat">
							<div class="ai-chat__holder">
								<div class="ai-chat__header">
									<?php if ( $heading_ai ) : ?>
										<h2><?php echo esc_html( $heading_ai ); ?></h2>
									<?php endif; ?>
									<button type="button" class="ai-chat__close" aria-label="Close">
										<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24" viewBox="0 -960 960 960" width="24" fill="#000">
											<path
												d="M480-424 284-228q-11 11-28 11t-28-11q-11-11-11-28t11-28l196-196-196-196q-11-11-11-28t11-28q11-11 28-11t28 11l196 196 196-196q11-11 28-11t28 11q11 11 11 28t-11 28L536-480l196 196q11 11 11 28t-11 28q-11 11-28 11t-28-11L480-424Z"></path>
										</svg>
									</button>
								</div>
								<div class="ai-chat__body" data-ai-message>
								</div>
								<div class="ai-chat__footer">
									<div class="ai-chat__footer-holder">
										<div class="ai-chat__input-row">
											<div class="ai-chat__input" data-ai-input contenteditable="true" translate="no" role="textbox" aria-multiline="true" aria-label="Chat with AI">
												<p class="ai-chat__input-placeholder" data-placeholder="Ask anything"></p>
												<br />
											</div>
											<button type="button" aria-label="Send" class="ai-chat__submit" data-ai-button>
												<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" aria-hidden="true" fill="currentColor">
													<path
														d="M9 16V6.414L5.707 9.707a1 1 0 1 1-1.414-1.414l5-5 .076-.068a1 1 0 0 1 1.338.068l5 5 .068.076a1 1 0 0 1-1.406 1.406l-.076-.068L11 6.414V16a1 1 0 1 1-2 0"></path>
												</svg>
											</button>
										</div>
									</div>
								</div>
							</div>
							<?php
							$modals = array( 'ai_sales', 'ai_service', 'ai_parts' );
							foreach ( $modals as $modal ) :

								while ( have_rows( $modal, 'options' ) ) :
									the_row();
									$title       = get_sub_field( 'title' );
									$description = get_sub_field( 'description' );
									$form_id     = get_sub_field( 'form' );
									$modal_id    = 'aiContactModal-' . esc_attr( $modal );
									$label_id    = 'aiContactModalLabel-' . esc_attr( $modal );
									?>
									<div class="modal fade ai-form-modal" id="<?php echo esc_attr( $modal_id ); ?>" tabindex="-1" aria-labelledby="<?php echo esc_attr( $label_id ); ?>" aria-hidden="true" data-modal-type="<?php echo esc_attr( $modal ); ?>">
										<div class="modal-dialog modal-form modal-dialog-scrollable modal-dialog-centered">
											<div class="modal-content">
												<div class="modal-header">
													<button type="button" class="close" data-dismiss="modal" aria-label="Close">
														<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000">
															<path
																d="M480-424 284-228q-11 11-28 11t-28-11q-11-11-11-28t11-28l196-196-196-196q-11-11-11-28t11-28q11-11 28-11t28 11l196 196 196-196q11-11 28-11t28 11q11 11 11 28t-11 28L536-480l196 196q11 11 11 28t-11 28q-11 11-28 11t-28-11L480-424Z" />
														</svg>
													</button>
												</div>
												<div class="modal-body">
													<?php if ( $title || $description ) : ?>
														<div class="modal-text text-center">
															<?php if ( $title ) : ?>
																<h3 class="modal-title" id="<?php echo esc_attr( $label_id ); ?>"><?php echo esc_html( $title ); ?></h3>
																<?php
															endif;
															echo wp_kses_post( $description );
															?>
														</div>
														<?php
													endif;
													if ( $form_id ) {
														echo do_shortcode( '[wpforms id="' . intval( $form_id ) . '"]' );
													}
													?>
												</div>
											</div>
										</div>
									</div>
									<?php
								endwhile;
							endforeach;
							?>
						</div>
					</div>
				<?php endif; ?>
			</nav>
		</header>
		<main id="main">
			<?php
			if ( ! get_field( 'new_home_page_styles' ) ) {
				get_template_part( 'template-parts/intro' );
			}
			?>

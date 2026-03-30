<?php

/**
 * Class WPS_Nav_Menu_Widget
 *
 * @package Shopperexpress
 */

class WPS_Nav_Menu_Widget extends WP_Widget {



	/**
	 * Sets up a new Navigation Menu widget instance.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		$widget_ops = array(
			'description'                 => __( 'Add a navigation menu to your sidebar.' ),
			'customize_selective_refresh' => true,
		);
		parent::__construct( 'nav_menu', __( 'Navigation Menu' ), $widget_ops );
	}

	/**
	 * Outputs the content for the current Navigation Menu widget instance.
	 *
	 * @since 3.0.0
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance Settings for the current Navigation Menu widget instance.
	 */
	public function widget( $args, $instance ) {
		// Get menu.
		$nav_menu = ! empty( $instance['nav_menu'] ) ? wp_get_nav_menu_object( $instance['nav_menu'] ) : false;

		if ( ! $nav_menu ) {
			return;
		}

		$default_title = __( 'Menu' );
		$title         = ! empty( $instance['title'] ) ? $instance['title'] : '';

		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		$format = current_theme_supports( 'html5', 'navigation-widgets' ) ? 'html5' : 'xhtml';

		/**
		 * Filters the HTML format of widgets with navigation links.
		 *
		 * @since 5.5.0
		 *
		 * @param string $format The type of markup to use in widgets with navigation links.
		 *                       Accepts 'html5', 'xhtml'.
		 */
		$format = apply_filters( 'navigation_widgets_format', $format );

		if ( 'html5' === $format ) {
			// The title may be filtered: Strip out HTML and make sure the aria-label is never empty.
			$title      = trim( strip_tags( $title ) );
			$aria_label = $title ? $title : $default_title;

			$nav_menu_args = array(
				'fallback_cb' => '',
				'menu'        => $nav_menu,
				'container'   => false,
				'items_wrap'  => '<ul id="%1$s" class="list-unstyled %2$s">%3$s</ul>',
			);
		} else {
			$nav_menu_args = array(
				'fallback_cb' => '',
				'menu'        => $nav_menu,
				'items_wrap'  => '<ul id="%1$s" class="list-unstyled %2$s">%3$s</ul>',
			);
		}

		/**
		 * Filters the arguments for the Navigation Menu widget.
		 *
		 * @since 4.2.0
		 * @since 4.4.0 Added the `$instance` parameter.
		 *
		 * @param array   $nav_menu_args {
		 *     An array of arguments passed to wp_nav_menu() to retrieve a navigation menu.
		 *
		 *     @type callable|bool $fallback_cb Callback to fire if the menu doesn't exist. Default empty.
		 *     @type mixed         $menu        Menu ID, slug, or name.
		 * }
		 * @param WP_Term $nav_menu      Nav menu object for the current menu.
		 * @param array   $args          Display arguments for the current widget.
		 * @param array   $instance      Array of settings for the current widget.
		 */
		wp_nav_menu( apply_filters( 'widget_nav_menu_args', $nav_menu_args, $nav_menu, $args, $instance ) );

		echo $args['after_widget'];
	}

	/**
	 * Handles updating settings for the current Navigation Menu widget instance.
	 *
	 * @since 3.0.0
	 *
	 * @param array $new_instance New settings for this instance as input by the user via
	 *                            WP_Widget::form().
	 * @param array $old_instance Old settings for this instance.
	 * @return array Updated settings to save.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		if ( ! empty( $new_instance['title'] ) ) {
			$instance['title'] = sanitize_text_field( $new_instance['title'] );
		}
		if ( ! empty( $new_instance['nav_menu'] ) ) {
			$instance['nav_menu'] = (int) $new_instance['nav_menu'];
		}
		return $instance;
	}

	/**
	 * Outputs the settings form for the Navigation Menu widget.
	 *
	 * @since 3.0.0
	 *
	 * @param array $instance Current settings.
	 * @global WP_Customize_Manager $wp_customize
	 */
	public function form( $instance ) {
		global $wp_customize;
		$title    = isset( $instance['title'] ) ? $instance['title'] : '';
		$nav_menu = isset( $instance['nav_menu'] ) ? $instance['nav_menu'] : '';

		// Get menus.
		$menus = wp_get_nav_menus();

		$empty_menus_style     = '';
		$not_empty_menus_style = '';
		if ( empty( $menus ) ) {
			$empty_menus_style = ' style="display:none" ';
		} else {
			$not_empty_menus_style = ' style="display:none" ';
		}

		$nav_menu_style = '';
		if ( ! $nav_menu ) {
			$nav_menu_style = 'display: none;';
		}

		// If no menus exists, direct the user to go and create some.
		?>
		<p class="nav-menu-widget-no-menus-message" <?php echo $not_empty_menus_style; ?>>
			<?php
			if ( $wp_customize instanceof WP_Customize_Manager ) {
				$url = 'javascript: wp.customize.panel( "nav_menus" ).focus();';
			} else {
				$url = admin_url( 'nav-menus.php' );
			}

			/* translators: %s: URL to create a new menu. */
			printf( __( 'No menus have been created yet. <a href="%s">Create some</a>.' ), esc_attr( $url ) );
			?>
		</p>
		<div class="nav-menu-widget-form-controls" <?php echo $empty_menus_style; ?>>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
				<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'nav_menu' ); ?>"><?php _e( 'Select Menu:' ); ?></label>
				<select id="<?php echo $this->get_field_id( 'nav_menu' ); ?>" name="<?php echo $this->get_field_name( 'nav_menu' ); ?>">
					<option value="0"><?php _e( '&mdash; Select &mdash;' ); ?></option>
					<?php foreach ( $menus as $menu ) : ?>
						<option value="<?php echo esc_attr( $menu->term_id ); ?>" <?php selected( $nav_menu, $menu->term_id ); ?>>
							<?php echo esc_html( $menu->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			<?php if ( $wp_customize instanceof WP_Customize_Manager ) : ?>
				<p class="edit-selected-nav-menu" style="<?php echo $nav_menu_style; ?>">
					<button type="button" class="button"><?php _e( 'Edit Menu' ); ?></button>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}
}

add_action(
	'widgets_init',
	function () {
		unregister_widget( 'WP_Nav_Menu_Widget' );
		register_widget( 'WPS_Nav_Menu_Widget' );
	}
);

/**
 * Class WPS_Latest_Posts_Widget
 *
 * Adds a Latest Posts widget to display recent blog posts in the sidebar.
 */
class WPS_Latest_Posts_Widget extends WP_Widget {



	/**
	 * Sets up a new Latest Posts widget instance.
	 */
	public function __construct() {
		parent::__construct(
			'wps_latest_posts_widget',
			__( 'Latest Posts', 'shopperexpress' ),
			array( 'description' => __( 'Displays a list of your most recent posts.', 'shopperexpress' ) )
		);
	}

	/**
	 * Outputs the content for the current Latest Posts widget instance.
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		$title  = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Latest Posts', 'shopperexpress' ) : $instance['title'], $instance, $this->id_base );
		$number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : 5;

		echo $args['before_widget'];

		$latest_posts = new WP_Query(
			array(
				'posts_per_page'      => $number,
				'post_status'         => 'publish',
				'ignore_sticky_posts' => 1,
			)
		);

		?>
		<div class="widget_block">
			<div class="widget-news">
				<?php if ( ! empty( $title ) ) : ?>
					<h3><?php echo $args['before_title'] . $title . $args['after_title']; ?></h3>
					<?php
				endif;
				if ( $latest_posts->have_posts() ) :
					?>
					<ul class="latest-news">
						<?php
						while ( $latest_posts->have_posts() ) :
							$latest_posts->the_post();
							?>
							<li>
								<article class="card-post">
									<div class="card-post__img">
										<a href="<?php the_permalink(); ?>">
											<?php
											if ( has_post_thumbnail() ) {
													the_post_thumbnail(
														'thumbnail',
														array(
															'class' => 'card-post__thumb',
														)
													);
											} else {
												$default_post_image = get_field( 'default_post_image', 'option' );
												if ( $default_post_image ) {
													echo wp_kses_post(
														get_attachment_image(
															$default_post_image,
															'454x255',
															array(
																'class' => 'card-post__thumb',
															)
														)
													);
												}
											}
											?>
										</a>
									</div>
									<div class="card-post__body">
										<strong class="card-post__category">
											<?php
											$category = get_the_category();
											if ( ! empty( $category ) ) {
												echo esc_html( $category[0]->name );
											} else {
												_e( 'Uncategorized', 'shopperexpress' );
											}
											?>
										</strong>
										<h4 class="card-post__title">
											<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
										</h4>
										<div class="card-post__meta">
											<p>
												<a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>"><?php the_author(); ?></a>
												<br>
												<a href="<?php the_permalink(); ?>" rel="bookmark">
													<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
												</a>
											</p>
										</div>
									</div>
								</article>
							</li>
						<?php endwhile; ?>
					</ul>
					<?php wp_reset_postdata(); ?>
				<?php else : ?>
					<p><?php _e( 'No posts found.', 'shopperexpress' ); ?></p>
					<?php
				endif;
				?>
				<div class="widget-news__footer">
					<a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>"><?php _e( 'See all latest news', 'shopperexpress' ); ?></a>
				</div>
			</div>
		</div>
		<?php

		echo $args['after_widget'];
	}

	/**
	 * Outputs the options form on admin.
	 *
	 * @param array $instance
	 */
	public function form( $instance ) {
		$title  = isset( $instance['title'] ) ? $instance['title'] : __( 'Latest Posts', 'shopperexpress' );
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of posts to show:' ); ?></label>
			<input class="tiny-text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" step="1" min="1" value="<?php echo esc_attr( $number ); ?>" size="3" />
		</p>
		<?php
	}

	/**
	 * Processes widget options to be saved.
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance           = array();
		$instance['title']  = sanitize_text_field( $new_instance['title'] );
		$instance['number'] = (int) $new_instance['number'];
		if ( $instance['number'] <= 0 ) {
			$instance['number'] = 5;
		}
		return $instance;
	}
}

add_action(
	'widgets_init',
	function () {
		register_widget( 'WPS_Latest_Posts_Widget' );
	}
);

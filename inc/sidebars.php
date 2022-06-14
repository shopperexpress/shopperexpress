<?php
function theme_widget_init() {
    $footer_style = get_field( 'footer_style', 'options' );
    $before_widget = $footer_style ==  2 ? '<div class="col-6 col-sm-3 col-lg-2 %2$s" id="%1$s">' : null;
    $after_widget  = $footer_style == 2 ? '</div>' : null;
    register_sidebar( array(
        'id'            => 'footer-sidebar',
        'name'          => __( 'Footer Sidebar', 'shopperexpress' ),
        'before_widget' => $before_widget,
        'after_widget'  => $after_widget,
        'before_title'  => '<h5>',
        'after_title'   => '</h5>'
    ) );
}
add_action( 'widgets_init', 'theme_widget_init' );
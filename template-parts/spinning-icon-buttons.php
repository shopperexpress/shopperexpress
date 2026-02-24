<?php 
$post_id = !empty( $args['post_id'] ) ? $args['post_id'] : get_the_ID();
if ( have_rows( 'buttons', $post_id ) ) : ?>
    <ul class="btn-list list-unstyled">
        <?php
        while ( have_rows( 'buttons', $post_id ) ) : the_row();
            $text = get_sub_field( 'text' );
            $second_text = get_sub_field( 'second_text' );
            $icon = get_sub_field( 'icon' );
            ?>
            <li>
                <a class="btn-custom" href="<?php echo esc_url( get_sub_field( 'url' ) ); ?>">
                    <?php if ( $icon ) echo wp_get_attachment_image( $icon['id'], 'full', null, [ 'class' => 'icon' ] ); ?>
                    <span class="link-text">
                        <?php
                        echo do_shortcode( $text );
                        if ( $second_text ) :
                            ?>
                            <strong class="link-title"><?php echo $second_text; ?></strong>
                        <?php endif; ?>
                    </span>
                </a>
            </li>
        <?php endwhile; ?>
    </ul>
<?php endif; ?>
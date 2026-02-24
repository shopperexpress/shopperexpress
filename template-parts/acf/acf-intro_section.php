<?php

$block = get_row_layout();

$class = $block == 'video_section' ? 'preferred' : 'intro-block';
$icon = get_sub_field('icon');
$title = get_sub_field('title');
$subtitle = get_sub_field('subtitle');
$image = get_sub_field('image');
$bottom_button_code = get_sub_field('bottom_button_code');
$bottom_image = get_sub_field('bottom_image');
$first_image = get_sub_field( 'first_image' );
$second_image = get_sub_field( 'second_image' );
$thrid_image = get_sub_field( 'thrid_image' );
$text = get_sub_field( 'text' );
?>
<div class="block">
    <div class="container">
        <?php if( $block == 'video_section' ): ?><div class="block-holder products-page"><?php endif; ?>
        <!-- content block -->
        <?php if ( $icon || $title || $subtitle || $image || $bottom_button_code || $bottom_image ) : ?>
            <section class="content-block <?php echo $class; ?>">
                <div class="text-box">
                    <div class="heading">
                        <?php if( $icon ) : ?>
                            <div class="icon">
                                <?php
                                $image_id = absint( $icon );
                                echo wp_kses_post( wp_get_attachment_image( $image_id, 'full' ) );
                                ?>
                            </div>
                            <?php
                        endif;
                        if ( $title || $subtitle ) :
                            ?>
                            <h2>
                                <?php if ( $title ) : ?>
                                    <span><?php echo esc_html( $title ); ?></span>
                                    <?php
                                endif;

                                echo esc_html( $subtitle );
                                ?>
                            </h2>
                        <?php endif; ?>
                    </div>
                    <?php if ( $text ) : ?>
                        <div class="holder">
                            <?php echo $text; ?>
                        </div>
                        <?php
                    endif;
                    if ( $link = get_sub_field( 'link' ) ){ echo wps_get_link( $link, 'more' ); }

                    echo $bottom_button_code;
                    ?>  
                    <?php
                    if( $bottom_image ){
                        $image_id = absint( $bottom_image['id'] );
                        echo wp_kses_post( wp_get_attachment_image( $image_id, 'full', false, [ 'class'=> 'item-img' ] ) );
                    }
                    ?>
                </div>
                <?php if ( $image ) : ?>
                    <div class="img-box">
                        <?php
                        $image_id = absint( $image['id'] );
                        echo wp_kses_post( wp_get_attachment_image( $image_id, 'full' ) );
                        ?>
                    </div>
                    <?php
                endif;
                if ( $first_image || $second_image ) :
                    ?>
                    <div class="img-box">
                        <?php 
                        if( $first_image ){
                            $image_id = absint( $first_image['id'] );
                            echo wp_kses_post( wp_get_attachment_image( $image_id, 'full', false, ['class' => 'rotate-image'] ) );
                        }
                        if( $second_image ){
                            $image_id = absint( $second_image['id'] );
                            echo wp_kses_post( wp_get_attachment_image( $image_id, 'full', false, ['class' => 'descrition-image'] ) );
                        }
                        ?>
                    </div>
                    <?php 
                endif;
                if( $thrid_image ):
                    ?>
                    <div class="img-wrapp">
                        <?php
                        $image_id = absint( $thrid_image['id'] );
                        echo wp_get_attachment_image( $image_id, 'full' );
                        ?>
                    </div>
                <?php endif; ?>
            </section>
            <?php
        endif;
        if( $block == 'video_section' ): ?></div></div><?php endif;
        if( have_rows('blocks') ) :
            if( $block == 'video_section' ): ?><section class="video-holder"><div class="container"><?php else: ?><div class="block-holder"><?php endif; ?>
            <?php
            while ( have_rows('blocks') ) {
                the_row();

                switch ( $block ) {
                    case 'video_section':
                    $template_path = locate_template( 'template-parts/acf/acf-video.php' );
                    if ( $template_path ) {
                        get_template_part( 'template-parts/acf/acf-video' );
                    } else {
                        echo '<!-- Template for video_section not found -->';
                    }
                    break;

                    default:
                    $template_path = locate_template( 'template-parts/acf/acf-' . get_row_layout() . '.php' );
                    if ( $template_path ) {
                        get_template_part( 'template-parts/acf/acf', get_row_layout() );
                    } else {
                        echo '<!-- Template for ' . get_row_layout() . ' not found -->';
                    }
                    break;
                }
            }
            if( $block == 'video_section' ): ?> </div></section><?php else: ?></div><?php endif; ?>
        <?php endif; ?>
        <?php if( $block != 'video_section' ): ?>
        </div>
    <?php endif; ?>
</div>
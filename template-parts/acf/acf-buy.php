<?php
$text = get_sub_field( 'text' );
$slogan = get_sub_field( 'slogan' );

if ( $text || $slogan || have_rows( 'columns' ) ) :
    ?>
    <section class="section-buy">
        <div class="container">
            <div class="row text-center">
                <?php if ( $text ): ?>
                    <div class="col-12 heading">
                        <?php echo $text; ?>
                    </div>
                    <?php
                endif;
                while ( have_rows( 'columns' ) ) : the_row();
                    $icon = get_sub_field( 'icon_image' );
                    $title = get_sub_field( 'title' );
                    $description = get_sub_field( 'description' );

                    if ( $icon || $title || $description ) :
                        ?>
                        <div class="col-md-4">
                            <?php if ( $icon ) : ?>
                                <div class="icon">
                                    <?php
                                    $logo_id = absint( $icon );
                                    echo wp_kses_post( get_attachment_image( $logo_id ) );
                                    ?>
                                </div>
                                <?php
                            endif;
                            if ( $title ): ?>
                                <h3 class="h2"><?php echo $title; ?></h3>
                                <?php
                            endif;

                            echo $description;
                            ?>
                        </div>
                        <?php
                    endif;
                endwhile;
                ?>
            </div>
        </div>
        <?php if ( $slogan ): ?>
            <strong class="slogan"><?php echo $slogan; ?></strong>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php
$title = !empty( $args['title'] ) ? $args['title'] : null;
$section_bg = !empty( $args['section_bg'] ) ? $args['section_bg'] : null;
$slide_bg = !empty( $args['slide_bg'] ) ? $args['slide_bg'] : null;

if ( have_rows( 'slider' , 'options' ) ) :
    ?>
    <section class="shop-section filter-section" <?php if ($section_bg) echo 'style="background-color:'.$section_bg.';"' ?>>
        <div class="container-fluid">
            <?php if ( $title ): ?>
                <h2 class="text-center"><?php echo $title; ?></h2>
            <?php endif; ?>
            <ul class="models-filter list-unstyled" data-filter-group="car-type">
                <li class="active"><a href="#" data-filter="all"><?php _e('all vehicles','shopperexpress'); ?></a></li>
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
            <div class="model-slider">
                <?php while ( have_rows( 'slider' , 'options' ) ) : the_row(); ?>
                    <div class="slide">
                        <a class="model-card" href="<?php echo esc_url(get_sub_field( 'url' )); ?>">
                            <?php if ( $image = get_sub_field( 'image' ) ): ?>
                                <div class="img-box" <?php if ($slide_bg) echo 'style="background-color:'.$slide_bg.';"'?>>
                                    <img src="<?php echo $image['url']; ?>" alt="image description">
                                </div>
                            <?php endif; ?>
                            <?php if ( $model = get_sub_field( 'model' ) ): ?>
                                <strong class="model"><?php echo $model; ?></strong>
                            <?php endif; ?>
                            <span class="car-type hidden"><?php echo seoUrl(get_sub_field( 'type' )); ?></span>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php
endif;
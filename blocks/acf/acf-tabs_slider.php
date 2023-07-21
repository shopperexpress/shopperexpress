<?php
$title = get_field( 'title_slider', 'options' );
$section_bg = get_field( 'section_bg', 'options' );
$slide_bg = get_field( 'slide_bg', 'options' );
$show_count = get_field( 'show_count', 'options' );

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
                                    <?php if ($show_count) {
                                        $year = get_sub_field( 'year' );
                                        $make = get_sub_field( 'make' );
                                        $model = get_sub_field( 'model' );
                                        $condition = get_sub_field( 'condition' );

                                        $args = array(
                                            'post_type' => 'listings',
                                            'post_status' => 'publish',
                                            'tax_query' => array(
                                                'relation' => 'and',
                                                array(
                                                    'taxonomy' => 'year',
                                                    'field'    => 'name',
                                                    'terms'    => array( $year )
                                                ),
                                                array(
                                                    'taxonomy' => 'make',
                                                    'field'    => 'name',
                                                    'terms'    => array( $make )
                                                ),
                                                array(
                                                    'taxonomy' => 'model',
                                                    'field'    => 'name',
                                                    'terms'    => array( $model )
                                                ),
                                                array(
                                                    'taxonomy' => 'condition',
                                                    'field'    => 'name',
                                                    'terms'    => array( $condition )
                                                )
                                            )
                                        );
                                        $query = new WP_Query( $args ); ?>
                                        
                                        <strong class="num"><?php if (!empty($query->posts)) echo count($query->posts); ?></strong>

                                        <?php wp_reset_query();
                                    }  ?>
                                    <?php echo wp_get_attachment_image( $image['id'], 'full' ); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ( $label = get_sub_field( 'label' ) ): ?>
                                <strong class="model"><?php echo $label; ?></strong>
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

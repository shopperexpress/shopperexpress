<?php if ( have_rows( 'offers' ) ) : ?>
    <div class="offer-section-wrapper">
        <section class="offer-section">
            <div class="container">
                <div class="row">
                    <?php while ( have_rows( 'offers' ) ) : the_row(); ?>
                        <div class="col-md-6">
                            <?php 
                            if( $image = get_sub_field( 'image' ) ){ echo wp_get_attachment_image( $image['id'],'full' ); }
                            the_sub_field( 'description' );
                            ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </section>
    </div>
<?php endif; ?>

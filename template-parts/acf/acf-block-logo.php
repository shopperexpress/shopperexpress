<section class="block-logo">
    <div class="container">
        <?php 
        if( $logo = get_sub_field( 'logo' ) ) {
            $logo_id = absint( $logo['id'] );
            echo wp_kses_post( wp_get_attachment_image( $logo_id, 'full', null, [ 'class' => 'logo-lg' ] ) );
        }
        ?>
    </div>
</section>
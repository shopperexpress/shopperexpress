<?php
$firstImage = !empty( $args['image'] ) ? $args['image'] : null;
$title = !empty( $args['title'] ) ? $args['title'] : null;
$vin_number = !empty( $args['vin'] ) ? $args['vin'] : null;
$stock_number = !empty( $args['stock_number'] ) ? $args['stock_number'] : null;
?>
<!-- Copy link modal -->
<div class="modal fade modal-share" id="copyLinkModal" tabindex="-1" aria-labelledby="copyLinkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php esc_html_e( 'Share Vehicle', 'shopperexpress' ); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000">
                        <path
                        d="M480-424 284-228q-11 11-28 11t-28-11q-11-11-11-28t11-28l196-196-196-196q-11-11-11-28t11-28q11-11 28-11t28 11l196 196 196-196q11-11 28-11t28 11q11 11 11 28t-11 28L536-480l196 196q11 11 11 28t-11 28q-11 11-28 11t-28-11L480-424Z"
                        />
                    </svg>
                </button>
            </div>
            <div class="modal-body-wrap">
                <div class="modal-body">
                    <div class="share-info">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="share-info__img">
                                    <?php if ( $firstImage ) : ?><img src="<?php echo $firstImage; ?>" alt="image description" /><?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <?php if ( $title ) : ?>
                                    <h3><?php echo $title; ?></h3>
                                    <?php
                                endif;
                                if ( $vin_number || $stock_number ) :
                                    ?>
                                    <dl class="detail-info">
                                        <?php if ( $vin_number ) : ?>
                                            <dt><?php esc_html_e( 'VIN', 'shopperexpress' ); ?>:</dt>
                                            <dd class="vin"><?php echo $vin_number; ?></dd>
                                            <?php
                                        endif;
                                        if ( $stock_number ) :
                                            ?>
                                            <dt><?php esc_html_e( 'Stock', 'shopperexpress' ); ?>:</dt>
                                            <dd><?php echo $stock_number; ?></dd>
                                        <?php endif; ?>
                                    </dl>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="btn-holder">
                            <a class="btn btn-secondary btn-block" data-copied="Copied" href="#">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#212529"><path d="M280-280q-83 0-141.5-58.5T80-480q0-83 58.5-141.5T280-680h120q17 0 28.5 11.5T440-640q0 17-11.5 28.5T400-600H280q-50 0-85 35t-35 85q0 50 35 85t85 35h120q17 0 28.5 11.5T440-320q0 17-11.5 28.5T400-280H280Zm80-160q-17 0-28.5-11.5T320-480q0-17 11.5-28.5T360-520h240q17 0 28.5 11.5T640-480q0 17-11.5 28.5T600-440H360Zm200 160q-17 0-28.5-11.5T520-320q0-17 11.5-28.5T560-360h120q50 0 85-35t35-85q0-50-35-85t-85-35H560q-17 0-28.5-11.5T520-640q0-17 11.5-28.5T560-680h120q83 0 141.5 58.5T880-480q0 83-58.5 141.5T680-280H560Z"/></svg>
                            <span class="btn-text"><?php esc_html_e( 'Copy Link to Share', 'shopperexpress' ); ?></span></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" data-dismiss="modal" aria-label="Close" class="btn btn-primary btn-lg"><?php esc_html_e( 'Done', 'shopperexpress' ); ?></button>
            </div>
        </div>
    </div>
</div>
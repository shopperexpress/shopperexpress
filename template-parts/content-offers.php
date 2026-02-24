<?php
$post_id = !empty($args['post_id']) ? $args['post_id'] :  get_the_id();
$post_type = '-' . get_post_type($post_id);
?>
<div class="col-sm-6 col-lg-4 col-xxl-3">
    <div class="card">
        <div class="card-body">
            <div class="card-head">
                <div class="card-head__holder">
                  <span class="card-brand"><?php echo wps_get_term($post_id, 'year' . $post_type); ?> <?php echo wps_get_term($post_id, 'make' . $post_type); ?></span>
                  <?php if (shortcode_exists('favorite_button')) echo do_shortcode('[favorite_button post_id="' . $post_id . '"]'); ?>
                </div>
                <strong class="card-model"><?php echo wps_get_term($post_id, 'model' . $post_type); ?> <?php echo wps_get_term($post_id, 'trim' . $post_type); ?></strong>
            </div>
            <?php get_template_part('template-parts/gallery', null, ['post_type' => get_post_type($post_id), 'post_id' => $post_id]); ?>
            <?php get_template_part('template-parts/detail', 'info', ['post_type' => get_post_type($post_id), 'post_id' => $post_id, 'class' => 'card-detail']); ?>
            <div class="card-info-row">
                <button class="btn-disclosure" data-toggle="modal" data-target="#detailModal-offers-<?php echo $post_id; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                        <path d="M480-280q17 0 28.5-11.5T520-320v-160q0-17-11.5-28.5T480-520q-17 0-28.5 11.5T440-480v160q0 17 11.5 28.5T480-280Zm0-320q17 0 28.5-11.5T520-640q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640q0 17 11.5 28.5T480-600Zm0 520q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"></path>
                    </svg>
                    <?php esc_html_e('Additional info', 'shopperexpress'); ?>
                </button>
            </div>
            <?php
            $price = get_field('price', $post_id);
            $down_payment = get_field('down_payment', $post_id);
            $lease_payment = get_field('lease_payment', $post_id);
            $loan_payment = get_field('loan_payment', $post_id);
            $leaseterm = get_field('leaseterm', $post_id);
            $loanterm = get_field('loanterm', $post_id);
            $loanapr = get_field('loanapr', $post_id);
            $cash_offer = get_field('cash_offer', $post_id);
            $cash_offer = is_int($cash_offer) ? '$' . number_format($cash_offer) : $cash_offer;
            $cash_offer_label = get_field('cash_offer_label', $post_id);
            $condition = null;
            ?>

            <ul class="payment-info">
                <?php
                $i = 0;
                while (have_rows('offers_flexible_content', 'options')) : the_row();

                    if (get_row_layout() == 'payment' && have_rows('payment_list') && $i == 0) :

                        while (have_rows('payment_list')) : the_row();
                            $lock = get_sub_field('lock');
                            $show_payment = $lock  ? get_sub_field('show_payment') : false;
                            if ($price) {
                                $down_payment = !empty($down_payment) ? $down_payment : number_format($price);
                            }


                            switch ($show_payment) {
                                case 'lease-payment':
                                    if ($down_payment && $lease_payment) {

                                        $lease_payment = !empty($lease_payment) ? '$' . $lease_payment : null;
                                        $text = !empty($lease_payment) ? '$' . $down_payment . ' ' . __('DOWN', 'shopperexpress') . '<span class="savings">' . $lease_payment . ' <sub>/mo</sub></span>' : null;
                                    } else {
                                        $text = null;
                                    }

                                    break;

                                case 'Disclosure_loan':
                                    if ($condition != 'Slightly Used' && $condition != 'Used') {
                                        $text = $loanterm ? $loanterm . ' ' . __('mos.', 'shopperexpress')  : '';
                                        if ($loanapr) $text .= '<span class="savings">' . $loanapr . '% <sub>APR</sub></span>';
                                    } else {
                                        $text = null;
                                    }
                                    break;

                                case 'Disclosure_lease':
                                    if ($down_payment && $lease_payment) {
                                        $lease_payment = !empty($lease_payment) && $lease_payment != 'None' && $lease_payment > 0 ? '$' . number_format($lease_payment) : null;
                                        $text = !empty($lease_payment) ? $leaseterm . ' ' . __('mos.', 'shopperexpress') . '<span class="savings">' . $lease_payment . ' <sub>/mo</sub></span>' : null;
                                    } else {
                                        $text = null;
                                    }
                                    break;
                                case 'Disclosure_Cash':
                                    if ($condition != 'Slightly Used' && $condition != 'Used') {
                                        $cash_offer = get_field('cash_offer', $post_id);
                                        $cash_offer = is_int($cash_offer) ? '$' . number_format($cash_offer) : $cash_offer;
                                        $cash_offer_label = get_field('cash_offer_label', $post_id);
                                        $text = !empty($cash_offer) ? $cash_offer_label . '<span class="savings">' . $cash_offer . '</span>' : null;
                                    } else {
                                        $text = null;
                                    }
                                    break;

                                default:

                                    $loan_payment = !empty($loan_payment) && $loan_payment != 'None' ? '$' . number_format($loan_payment) . ' <sub>/mo</sub>' : null;
                                    $text = !empty($loan_payment) ? '$' . $down_payment . ' ' . __('DOWN', 'shopperexpress') . '<span class="savings">' . $loan_payment . '</span>' : null;
                                    break;
                            }
                            if ($text) :
                ?>
                                <li class="show">
                                    <?php if ($title = get_sub_field('title')): ?>
                                        <strong class="dt"><?php echo $title; ?></strong>
                                    <?php endif; ?>
                                    <strong class="price">
                                        <?php echo $text; ?>
                                    </strong>
                                </li>
                <?php
                            endif;
                        endwhile;
                    endif;
                    $i++;
                endwhile;
                ?>
            </ul>
            <?php
            $loged = !empty($args['loged']) ? $args['loged'] : '';
            get_template_part('template-parts/unlock', 'button', ['post_id' => $post_id, 'loged' => $loged]);
            $ConversionBlock = new ConversionBlock(0, get_post_type($post_id), $post_id);
            echo $ConversionBlock->render();
            ?>
        </div>
    </div>
</div>
<!-- Details Modal -->
<div class="modal fade" id="detailModal-offers-<?php echo $post_id; ?>" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><?php esc_html_e('DETAILS', 'shopperexpress'); ?></h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000">
                        <path
                            d="M480-424 284-228q-11 11-28 11t-28-11q-11-11-11-28t11-28l196-196-196-196q-11-11-11-28t11-28q11-11 28-11t28 11l196 196 196-196q11-11 28-11t28 11q11 11 11 28t-11 28L536-480l196 196q11 11 11 28t-11 28q-11 11-28 11t-28-11L480-424Z" />
                    </svg>
                </button>
            </div>
            <div class="modal-body-wrap">
              <div class="modal-body">
                  <div class="content-holder">
                      <?php echo wp_kses_post(get_field('custom_content', $post_id)); ?>
                  </div>
              </div>
            </div>
        </div>
    </div>
</div>
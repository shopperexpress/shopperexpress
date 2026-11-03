<?php
/**
 * Shared, single-instance incentive-offer detail modal for SRP cards.
 *
 * Rendered once per page no matter how many vehicle cards show the
 * incentive-offer badge (see template-parts/api/incentive_offers_srp.php) —
 * content is filled in on click from the clicked badge's data-* attributes
 * by initIncentiveOfferSrpModal() in assets/src/js/static/app.js, instead of
 * pre-rendering one modal per card.
 *
 * @package Shopperexpress
 */
?>
<!-- Conditions offers Modal (SRP, shared) -->
<div class="modal fade" id="conditionalOffersDetailModal-srp" tabindex="-1" aria-labelledby="conditionalOffersDetailSrpLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title js-incentive-offer-modal-title" id="conditionalOffersDetailSrpLabel"></h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'shopperexpress' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000">
						<path
							d="M480-424 284-228q-11 11-28 11t-28-11q-11-11-11-28t11-28l196-196-196-196q-11-11-11-28t11-28q11-11 28-11t28 11l196 196 196-196q11-11 28-11t28 11q11 11 11 28t-11 28L536-480l196 196q11 11 11 28t-11 28q-11 11-28 11t-28-11L480-424Z" />
					</svg>
				</button>
			</div>
			<div class="modal-body-wrap">
				<div class="modal-body">
					<div class="content-holder">
						<div class="js-incentive-offer-modal-desc"></div>
					</div>
				</div>
			</div>
			<div class="modal-footer justify-content-center justify-content-md-end">
				<button type="button" data-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'shopperexpress' ); ?>" class="btn btn-primary btn-lg"><?php esc_html_e( 'Close', 'shopperexpress' ); ?></button>
			</div>
		</div>
	</div>
</div>

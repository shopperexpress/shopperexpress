// SRP incentive-offer badges share one modal (#conditionalOffersDetailModal-srp,
// see template-parts/conditionalOffersDetail-modal-srp.php) instead of one per
// card — fill it in from the clicked badge's data-* attributes just before
// Bootstrap's own data-toggle="modal" handler opens it.
export default function initIncentiveOfferSrpModal() {
  jQuery(document)
    .off('click.incentiveOfferSrp')
    .on('click.incentiveOfferSrp', '.js-incentive-offer-info', function (e) {
      e.preventDefault();

      const $btn = jQuery(this);
      const $modal = jQuery('#conditionalOffersDetailModal-srp');
      if (!$modal.length) return;

      $modal.find('.js-incentive-offer-modal-title').text($btn.data('programName') || '');

      const $desc = $modal.find('.js-incentive-offer-modal-desc');
      $desc.html($btn.data('incentiveDesc') || '');

      const startsOn = $btn.data('startsOn') || '';
      const endsOn = $btn.data('endsOn') || '';
      const datesText = startsOn && endsOn ? `Valid from: ${startsOn} through ${endsOn}` : '';

      const incentiveId = $btn.data('incentiveId') || '';
      const idText = incentiveId ? `Incentive Id: ${incentiveId}` : '';

      if (datesText || idText) {
        $desc.append(
          jQuery('<ul>', { class: 'offers-detail-list list-unstyled' }).append(
            jQuery('<li>', { class: 'js-incentive-offer-modal-dates', text: datesText }),
            jQuery('<li>', { class: 'js-incentive-offer-modal-id', text: idText })
          )
        );
      }

      $modal.modal('show');
    });
}

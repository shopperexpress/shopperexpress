export default function initOfferForm() {
  jQuery('.form-offer').each(function () {
    const form = jQuery(this);
    const btnSubmit = form.find('.btn-submit');
    const injectPlateField = form.find('.inject-plate');
    const injectVinField = form.find('.inject-vin');
    const injectStateField = form.find('.inject-state');
    const injectMilesField = form.find('.inject-miles');
    const allFields = form.find('.form-control');
    let injectPlate, injectVin, injectState, injectMiles;

    function onClickChange() {
      injectPlate = injectPlateField.val() || '';
      injectVin = injectVinField.val() || '';
      injectState = injectStateField.val() || '';
      injectMiles = injectMilesField.val() || '';
    }

    allFields.on('keyup', onClickChange);

    btnSubmit.on('click', function (e) {
      e.preventDefault();
      onClickChange();

      if (inticeEvents !== undefined) {
        inticeEvents.launchExpressCashOffer('519', injectPlate, injectVin, injectState, injectMiles);
      }
    });
  });
}

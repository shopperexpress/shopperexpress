// Tax Modal
export default function initTaxModal() {
  jQuery('#popUpDetails').on('show.bs.modal', function (event) {
    const button = jQuery(event.relatedTarget);
    let content = button.attr('data-content');
    const modal = jQuery(this);
    const textElement = modal.find('#popUpDetailsText');

    try {
      content = JSON.parse(content);
    } catch (e) {}

    textElement.html(content);
  });
}

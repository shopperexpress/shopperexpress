import '../plugins/tabsetPlugin';

// Content tabs init
export default function initTabs() {
  const activeClass = 'filter-mileage-active';
  const modal = jQuery('#filterSchedule');

  if (jQuery('.filter-list').length) {
    jQuery('.filter-list').tabset({
      tabLinks: 'a',
      attrib: 'data-tab',
      tabAttrib: 'data-id',
      defaultTab: false,
      onChange: function () {
        toggleClass();
      },
    });
  }

  if (modal.length) {
    modal.on('shown.bs.modal', function () {
      toggleClass();
    });
  }

  function toggleClass() {
    if (modal.length) {
      modal.find('.modal-range-box').each(function () {
        if (jQuery(this).closest('[data-id].active').length) {
          modal.addClass(activeClass);
        } else {
          modal.removeClass(activeClass);
        }
      });
    }
  }
}

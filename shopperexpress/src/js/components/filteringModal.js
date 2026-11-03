import '../plugins/responsiveHelperPlugin';

// filtering modal init
export default function initFilteringModal() {
  const win = jQuery(window);
  const page = jQuery('body');
  const header = jQuery('#header');
  const introBlock = jQuery('.intro');
  const filterList = jQuery('.filter-list');
  const filterModal = jQuery('.modal-filter');
  const btnClose = filterModal.find('[data-dismiss="modal"]:not(.btn-back)');
  const activeClass = 'filter-modal-active';

  jQuery('[data-modal]').on('click', function (e) {
    e.preventDefault();
    resizeHandler();
    filterModal.modal('show');
  });

  function resizeHandler() {
    if (introBlock.length && getWindowWidth() >= 1200) {
      if (introBlock.offset().top + introBlock.outerHeight() >= win.scrollTop() + header.outerHeight()) {
        page.removeClass(activeClass);

        filterModal.css({
          marginTop: introBlock.outerHeight() + header.outerHeight() - win.scrollTop(),
        });

        filterModal.on('shown.bs.modal', function () {
          const modalBackDrop = jQuery('.modal-backdrop');

          modalBackDrop.css({
            top: introBlock.outerHeight() + header.outerHeight() - win.scrollTop(),
          });
        });
      } else {
        filterModal.css({
          marginTop: 0,
        });

        filterModal.on('shown.bs.modal', function () {
          const modalBackDrop = jQuery('.modal-backdrop');

          modalBackDrop.css({
            top: 0,
          });
        });

        page.addClass(activeClass);
      }
    } else {
      page.removeClass(activeClass);
    }

    if (!introBlock.length && getWindowWidth() >= 1200) {
      page.addClass(activeClass);
    }
  }

  resizeHandler();

  win.on('resize orientationchange', function () {
    resizeHandler();
  });

  ResponsiveHelper.addRange({
    '..991': {
      on: function () {
        btnClose.on('click.modal', function () {
          page.removeClass('filter-active');
        });
      },
    },
    '992..': {
      on: function () {
        btnClose.off('click.modal', function () {
          page.removeClass('filter-active');
        });
      },
    },
  });

  filterModal
    .on('shown.bs.modal', function () {
      const modal = jQuery('.modal-backdrop');

      if (modal.length) {
        modal.addClass('modal-filter-backdrop');
      }
    })
    .on('hidden.bs.modal', function () {
      filterList.find('.active').removeClass('active');
    });

  function getWindowWidth() {
    return typeof window.innerWidth === 'number' ? window.innerWidth : document.documentElement.clientWidth;
  }
}

import CompareProducts from '../plugins/compareProductsPlugin';
import '../plugins/filteringProductsPlugin';
import initApiFavoriteButtons from './apiFavoriteButtons';
import initAutocomplete from './autocomplete';
import initTooltip from './tooltip';
import initUnlockSavings from './unlockSavings';

// Products filtering init
export default function initProductsFiltration() {
  if (jQuery('.filter-section').length) {
    window.history.scrollRestoration = 'manual';
  }

  if (jQuery('.detail-section').length && !sessionStorage.getItem('fromProductPage')) {
    sessionStorage.removeItem('fromProductPage');
  }

  const compareProducts = new CompareProducts(document.querySelector('.filter-section'));

  jQuery('.filter-section').filteringProducts({
    container: '.card-wrapp .row, .card-wrapp .d-grid-row',
    items: '.col-sm-6',
    filterHolder: '.filter-list',
    itemsPerPage: 20,
    delay: 0,
    onInit: function () {
      this.holder.on('click', '.card-body > a', () => {
        sessionStorage.setItem('fromProductPage', 'true');
        sessionStorage.setItem('scrollPosition', window.scrollY);
      });
    },
    onDataLoad: function (vehicles) {
      initAutocomplete();
      compareProducts.setVehicles(vehicles);
    },
    onLoadItems: function (items) {
      initFunctions(items);
      initUnlockSavings();
      initApiFavoriteButtons();
      initTooltip();
      compareProducts.syncButtons(Array.from(items));
    },
  });

  function initFunctions(items) {
    if (window.blockFrame) {
      window.blockFrame.GetDealerInfoForMiniTools();
    }

    items.find('.detail-slider-holder').each(function () {
      const holder = jQuery(this);
      const slider = holder.find('.detail-slider');

      if (slider.length) {
        const slides = slider.find('.slide');

        if (slides.length <= 1) return;

        slider.slick({
          slidesToScroll: 1,
          rows: 0,
          infinite: false,
          prevArrow:
            '<button class="slick-control slick-prev slick-arrow" aria-label="Previous"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="m432-480 156 156q11 11 11 28t-11 28q-11 11-28 11t-28-11L348-452q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l184-184q11-11 28-11t28 11q11 11 11 28t-11 28L432-480Z"/></svg></button>',
          nextArrow:
            '<button class="slick-control slick-next slick-arrow" aria-label="Next"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M504-480 348-636q-11-11-11-28t11-28q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L404-268q-11 11-28 11t-28-11q-11-11-11-28t11-28l156-156Z"/></svg></button>',
          focusOnSelect: true,
        });
      }
    });
  }
}

document.addEventListener('DOMContentLoaded', () => {
  initModelFilter();
  initModelSlider();
});

function initModelFilter() {
  jQuery('.shop-section.filter-section').filteringItems({
    items: '.slide',
    filterHolder: '.models-filter',
    filterItems: 'a[data-filter]',
    onInit: function () {
      this.slider = this.holder.find('.model-slider');
    },
    beforeFilter: function () {
      if (this.slider.length) {
        this.slider.trigger('beforeFilter');
      }
    },
    onFilter: function () {
      if (this.slider.length) {
        this.slider.trigger('rebuild');
      }
    },
  });
}

function initModelSlider() {
  jQuery('.model-slider').each(function () {
    const slider = jQuery(this);

    initSlider();

    function initSlider() {
      slider.slick({
        slidesToScroll: 1,
        rows: 0,
        slide: '.slide:not(.hidden)',
        slidesToShow: 4,
        prevArrow:
          '<button class="slick-control slick-prev slick-arrow" aria-label="Previous"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="m432-480 156 156q11 11 11 28t-11 28q-11 11-28 11t-28-11L348-452q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l184-184q11-11 28-11t28 11q11 11 11 28t-11 28L432-480Z"/></svg></button>',
        nextArrow:
          '<button class="slick-control slick-next slick-arrow" aria-label="Next"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M504-480 348-636q-11-11-11-28t11-28q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L404-268q-11 11-28 11t-28-11q-11-11-11-28t11-28l156-156Z"/></svg></button>',
        responsive: [
          {
            breakpoint: 1200,
            settings: {
              slidesToShow: 3,
            },
          },
          {
            breakpoint: 992,
            settings: {
              slidesToShow: 2,
            },
          },
          {
            breakpoint: 768,
            settings: {
              slidesToShow: 1,
            },
          },
        ],
      });
    }

    function destroySlider() {
      slider.slick('destroy');
    }

    slider.on('beforeFilter', function () {
      destroySlider();
    });

    slider.on('rebuild', function () {
      initSlider();
    });
  });
}

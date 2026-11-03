document.addEventListener('DOMContentLoaded', () => {
  initLpCardSlider();
});

function initLpCardSlider() {
  jQuery('.lp-card-slider')
    .not('[data-google-reviews-list]')
    .each(function () {
      const slider = jQuery(this);

      initSlider();

      function initSlider() {
        slider.slick({
          slidesToScroll: 1,
          rows: 0,
          slidesToShow: 3,
          infinite: false,
          // variableWidth: true,
          prevArrow:
            '<button class="lp-slick-control slick-prev slick-arrow" aria-label="Previous"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" width="32" height="32" viewBox="0 0 32 32" fill="#000000"><path d="M5.48 14.926v-0.001c0-0.001 0.002-0.002 0.002-0.003s0.002-0.001 0.002-0.002v-0.001c0.018-0.018 0.033-0.034 0.051-0.052l9.334-9.333c0.623-0.623 1.638-0.624 2.263 0.001s0.624 1.638-0.001 2.263l-6.602 6.602h14.804c0.884 0 1.6 0.716 1.6 1.6s-0.716 1.6-1.6 1.6h-14.804l6.602 6.603c0.625 0.625 0.625 1.637 0.001 2.262-0.313 0.313-0.723 0.469-1.132 0.469s-0.819-0.156-1.131-0.469l-9.334-9.332c-0.018-0.018-0.033-0.034-0.051-0.052v-0.001c0-0.001-0.002-0.002-0.002-0.002s-0.002-0.002-0.002-0.003v-0.001c-0.258-0.282-0.414-0.657-0.414-1.068v-0.010c0-0.411 0.156-0.786 0.414-1.068z"/></svg></button>',
          nextArrow:
            '<button class="lp-slick-control slick-next slick-arrow" aria-label="Next"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" width="32" height="32" viewBox="0 0 32 32" fill="#000000"><path d="M26.52 14.926v-0.001c0-0.001-0.002-0.002-0.002-0.003s-0.002-0.001-0.002-0.002v-0.001c-0.018-0.018-0.033-0.034-0.051-0.052l-9.334-9.333c-0.623-0.623-1.638-0.624-2.263 0.001s-0.624 1.638 0.001 2.263l6.602 6.602h-14.804c-0.884 0-1.6 0.716-1.6 1.6s0.716 1.6 1.6 1.6h14.804l-6.602 6.603c-0.625 0.625-0.625 1.637-0.001 2.262 0.313 0.313 0.723 0.469 1.132 0.469s0.819-0.156 1.131-0.469l9.334-9.332c0.018-0.018 0.033-0.034 0.051-0.052v-0.001c0-0.001 0.002-0.002 0.002-0.002s0.002-0.002 0.002-0.003v-0.001c0.258-0.282 0.414-0.657 0.414-1.068v-0.010c0-0.411-0.156-0.786-0.414-1.068z"/></svg></button>',
          responsive: [
            {
              breakpoint: 1024,
              settings: {
                slidesToShow: 2,
              },
            },
            {
              breakpoint: 576,
              settings: {
                slidesToShow: 1,
              },
            },
          ],
        });
      }
    });
}

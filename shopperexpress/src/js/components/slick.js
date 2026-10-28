import 'slick-carousel';

export default function initSlickCarousel() {
  const win = jQuery(window);
  const activeClass = 'active';

  // Init visual slider on product page
  jQuery('.visual--banner .visual-slider-srp').slick({
    slidesToScroll: 1,
    rows: 0,
    prevArrow:
      '<button class="slick-control slick-prev slick-arrow" aria-label="Previous"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="m432-480 156 156q11 11 11 28t-11 28q-11 11-28 11t-28-11L348-452q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l184-184q11-11 28-11t28 11q11 11 11 28t-11 28L432-480Z"/></svg></button>',
    nextArrow:
      '<button class="slick-control slick-next slick-arrow" aria-label="Next"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M504-480 348-636q-11-11-11-28t11-28q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L404-268q-11 11-28 11t-28-11q-11-11-11-28t11-28l156-156Z"/></svg></button>',
    dots: true,
    dotsClass: 'slick-dots',
    autoplay: true,
  });

  jQuery('.detail-slider-holder').each(function () {
    const holder = jQuery(this);
    const slider = holder.find('.detail-slider');
    const thumbs = holder.find('.detail-slider-nav .slide');
    const thumbsSlider = holder.find('.detail-slider-nav');
    const autoplay = slider.data('autoplay') || false;
    const autoplaySpeed = slider.data('autoplay-speed') || 3000;
    const range = holder.find('.range-box input');
    const navPrev = holder.find('.slider-nav-prev');
    const navNext = holder.find('.slider-nav-next');
    const sliderHolder = holder.find('.slider-nav-holder');
    const hasNav = !range.length && (navPrev.length || navNext.length);
    const arrowsClass = 'has-arrows';
    let navChunkSize = 1;
    let totalWidth = 0;
    let thumbsWidth = 0;
    let diffWidth = 0;
    let navIndex = 0;

    thumbs.eq(0).addClass(activeClass);

    const counter = jQuery('<div class="detail-slider-counter" aria-live="polite"><span class="current"></span> / <span class="total"></span></div>');
    const counterCurrent = counter.find('.current');
    const counterTotal = counter.find('.total');

    slider.after(counter);

    slider.on('init reInit afterChange', function (event, slick) {
      counterCurrent.text((slick.currentSlide || 0) + 1);
      counterTotal.text(slick.slideCount);
      counter.toggle(slick.slideCount > 1);
    });

    slider.slick({
      slidesToScroll: 1,
      rows: 0,
      infinite: false,
      prevArrow:
        '<button class="slick-control slick-prev slick-arrow" aria-label="Previous"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="m432-480 156 156q11 11 11 28t-11 28q-11 11-28 11t-28-11L348-452q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l184-184q11-11 28-11t28 11q11 11 11 28t-11 28L432-480Z"/></svg></button>',
      nextArrow:
        '<button class="slick-control slick-next slick-arrow" aria-label="Next"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M504-480 348-636q-11-11-11-28t11-28q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L404-268q-11 11-28 11t-28-11q-11-11-11-28t11-28l156-156Z"/></svg></button>',
      autoplay: autoplay,
      autoplaySpeed: autoplaySpeed,
      focusOnSelect: true,
    });

    // visible area is the holder's parent: .slider-nav-wrap or .detail-slider-nav without arrow paddings
    function getVisibleWidth() {
      return sliderHolder.parent().width() || thumbsSlider.outerWidth();
    }

    function resizeHandler() {
      let thumbWidth = thumbs.outerWidth(true) || 0;

      // thumbs width is in %, so visible count doesn't depend on arrow paddings
      navChunkSize = thumbWidth ? Math.max(Math.round(getVisibleWidth() / thumbWidth), 1) : 1;

      if (hasNav) {
        thumbsSlider.toggleClass(arrowsClass, thumbs.length > navChunkSize);
        thumbWidth = thumbs.outerWidth(true) || 0;
      }

      thumbsWidth = getVisibleWidth();
      totalWidth = thumbs.length * thumbWidth;
      diffWidth = totalWidth - thumbsWidth;
    }

    resizeHandler();
    win.on('resize orientationchange', function () {
      resizeHandler();

      if (hasNav) {
        goToChunk(Math.floor(navIndex / navChunkSize) * navChunkSize);
      }
    });

    slider.on('beforeChange', function (event, slick, currentSlide, nextSlide) {
      thumbs.removeClass(activeClass);
      thumbs.eq(nextSlide).addClass(activeClass);

      if (hasNav) {
        goToChunk(Math.floor(nextSlide / navChunkSize) * navChunkSize);
      }
    });

    thumbs.on('click', function (e) {
      e.preventDefault();
      const item = jQuery(this);
      const index = thumbs.index(item);

      thumbs.removeClass(activeClass);
      item.addClass(activeClass);

      slider.slick('slickGoTo', index);
    });

    if (range.length) {
      range.on('input', onChange);
    } else if (hasNav) {
      goToChunk(0);

      navPrev.on('click', function () {
        goToChunk(navIndex - navChunkSize);
      });

      navNext.on('click', function () {
        goToChunk(navIndex + navChunkSize);
      });
    }

    function onChange() {
      const value = parseFloat(range.data('jcfInstance').values[0]);

      sliderHolder.css({
        marginLeft: -(diffWidth / 100) * value,
        marginRight: (diffWidth / 100) * value,
      });
    }

    function goToChunk(index) {
      const maxIndex = Math.max(thumbs.length - 1, 0);
      const maxOffset = Math.max(diffWidth, 0);
      const thumbWidth = thumbs.outerWidth(true) || 0;

      navIndex = Math.min(Math.max(index, 0), maxIndex);

      const offset = Math.min(navIndex * thumbWidth, maxOffset);

      sliderHolder.css({
        marginLeft: -offset,
        marginRight: offset,
      });

      navPrev.prop('disabled', offset <= 0);
      navNext.prop('disabled', offset >= maxOffset);
    }
  });

  const evoSlider = jQuery('.evo-slider');

  evoSlider.slick({
    slidesToScroll: 1,
    rows: 0,
    arrows: false,
    dots: true,
    fade: true,
    speed: 0,
    dotsClass: 'slick-dots',
  });

  window.addEventListener('updateSliderHeight', resizeHandler);
  window.addEventListener('resize', debounce(resizeHandler, 500));

  resizeHandler();

  function resizeHandler() {
    evoSlider.slick('setPosition');
  }

  function debounce(func, delay = 250) {
    let timer = null;

    return function (...args) {
      clearTimeout(timer);

      timer = setTimeout(() => func.apply(this, args), delay);
    };
  }

  jQuery('.reviews-slider')
    .not('[data-google-reviews-list]')
    .each(function () {
      const slider = jQuery(this);

      slider.slick({
        slidesToScroll: 1,
        rows: 0,
        slidesToShow: 6,
        prevArrow:
          '<button class="slick-prev slick-arrow" aria-label="Previous"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#fff"><path d="m432-480 156 156q11 11 11 28t-11 28q-11 11-28 11t-28-11L348-452q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l184-184q11-11 28-11t28 11q11 11 11 28t-11 28L432-480Z"/></svg></button>',
        nextArrow:
          '<button class="slick-next slick-arrow" aria-label="Next"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#fff"><path d="M504-480 348-636q-11-11-11-28t11-28q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L404-268q-11 11-28 11t-28-11q-11-11-11-28t11-28l156-156Z"/></svg></button>',
        responsive: [
          {
            breakpoint: 1570,
            settings: {
              slidesToShow: 5,
            },
          },
          {
            breakpoint: 1310,
            settings: {
              slidesToShow: 4,
            },
          },
          {
            breakpoint: 1050,
            settings: {
              slidesToShow: 3,
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
    });
}

document.addEventListener('DOMContentLoaded', () => {
  initVisualSlider();
});

function initVisualSlider() {
  jQuery('.visual').each(function () {
    const holder = jQuery(this);
    const slider = holder.find('.visual-slider:not(.visual-slider-srp)');
    const controlsHolder = holder.find('.slick-controls');
    const buttonsHolder = controlsHolder.find('.buttons-holder');
    const dotsHolder = holder.find('.dots-holder');
    const btnPlayPause = controlsHolder.find('.slick-play-pause');
    const progress = controlsHolder.find('.indicator circle');
    const hideSearchWidgetClass = 'hide-search-widget';
    const widgetInvisibleClass = 'widget-invisible';
    const playingClass = 'playing';
    const pauseClass = 'pause';
    const resetClass = 'reset';
    const speed = slider.data('speed') || 300;
    const interval = slider.data('autoplay-speed') || 3000;
    const strokeDashoffset = 40;
    let startTime = null;
    let elapsedTime = 0;
    let animationRequest = null;
    let currentInterval = 0;
    let rotationTimer = null;

    slider.slick({
      slidesToScroll: 1,
      rows: 0,
      appendDots: dotsHolder,
      appendArrows: buttonsHolder,
      prevArrow:
        '<button class="slick-control slick-prev slick-arrow" aria-label="Previous"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="m432-480 156 156q11 11 11 28t-11 28q-11 11-28 11t-28-11L348-452q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l184-184q11-11 28-11t28 11q11 11 11 28t-11 28L432-480Z"/></svg></button>',
      nextArrow:
        '<button class="slick-control slick-next slick-arrow" aria-label="Next"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M504-480 348-636q-11-11-11-28t11-28q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L404-268q-11 11-28 11t-28-11q-11-11-11-28t11-28l156-156Z"/></svg></button>',
      dots: true,
      fade: true,
      dotsClass: 'slick-dots',
      speed: speed,
      autoplaySpeed: interval,
      pauseOnFocus: false,
      pauseOnHover: false,
      pauseOnDotsHover: false,
      autoplay: false,
    });

    const btnPrev = buttonsHolder.find('.slick-prev');
    const btnNext = buttonsHolder.find('.slick-next');

    toggleSearchWidget(slider.find('.slide').eq(slider.slick('slickCurrentSlide')));

    slider
      .on('beforeChange', function (event, slick, currentSlide, nextSlide) {
        toggleSearchWidget(slider.find('.slide').eq(nextSlide));
        resetProgress();
      })
      .on('afterChange', function () {
        if (!controlsHolder.hasClass(pauseClass)) {
          slider.slick('slickSetOption', 'autoplaySpeed', interval, false);
          startProgress();
        }
      });

    function toggleSearchWidget(activeSlide) {
      holder.toggleClass(hideSearchWidgetClass, activeSlide.hasClass(widgetInvisibleClass));
    }

    btnPlayPause.on('click', function (e) {
      e.preventDefault();

      if (controlsHolder.hasClass(playingClass)) {
        pauseRotation();
      } else {
        startRotation();
      }
    });

    btnPrev.add(btnNext).add(dotsHolder).on('click', stopRotation);

    startRotation();

    function startRotation() {
      slider.slick('slickSetOption', 'autoplaySpeed', interval - currentInterval, false);
      slider.slick('slickPlay');
      controlsHolder.addClass(playingClass).removeClass(pauseClass);

      currentInterval = 0;

      startProgress();
    }

    function pauseRotation() {
      slider.slick('slickPause');
      controlsHolder.removeClass(playingClass).addClass(pauseClass);
      stopProgress();
    }

    function stopRotation() {
      slider.slick('slickPause');
      controlsHolder.removeClass(playingClass).addClass(pauseClass);
      currentInterval = 0;

      resetProgress();
    }

    function startProgress() {
      startTime = Date.now();
      animationRequest = requestAnimationFrame(updateProgress);
      currentInterval = 0;

      controlsHolder.removeClass(resetClass);

      rotationTimer = setInterval(() => {
        currentInterval += 100;
      }, 100);
    }

    function resetProgress() {
      cancelAnimationFrame(animationRequest);
      clearInterval(rotationTimer);

      controlsHolder.addClass(resetClass);

      elapsedTime = 0;

      progress.css({
        strokeDashoffset: strokeDashoffset,
      });
    }

    function stopProgress() {
      cancelAnimationFrame(animationRequest);
      clearInterval(rotationTimer);

      const currentTime = Date.now();

      elapsedTime += currentTime - startTime;

      const progressValue = strokeDashoffset - (strokeDashoffset * elapsedTime) / interval;

      progress.css({
        strokeDashoffset: progressValue,
      });
    }

    function updateProgress() {
      const currentTime = Date.now();
      const totalElapsed = elapsedTime + (currentTime - startTime);
      const progressValue = strokeDashoffset - (strokeDashoffset * totalElapsed) / interval;

      progress.css({
        'stroke-dashoffset': Math.max(progressValue, 0),
      });

      if (totalElapsed >= interval) {
        cancelAnimationFrame(animationRequest);
        elapsedTime = 0;
      } else {
        animationRequest = requestAnimationFrame(updateProgress);
      }
    }
  });
}

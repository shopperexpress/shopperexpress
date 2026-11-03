document.addEventListener('DOMContentLoaded', () => {
  initSwitchModalText();
  initSpecialsSlider();
});

function initSwitchModalText() {
  let currID = null;

  jQuery('.section-specials .payment-info a[data-target]').each(function () {
    const link = jQuery(this);
    const modal = jQuery(link.data('target'));
    const ID = link.data('vehicle-id');

    link.on('click', () => {
      currID = ID;

      if (modal.length) {
        const textHolder = modal.find('.text-holder');
        const textBoxes = textHolder.find('> div');

        textBoxes.hide();

        if (currID !== null) {
          jQuery('#' + currID).show();
        }

        jcf.refresh(textHolder);
      }
    });
  });
}

function initSpecialsSlider() {
  jQuery('.specials-slider').each(function () {
    const slider = jQuery(this);
    const slides = slider.find('> div');
    const videoBlocks = slider.find('[data-video]');
    const speed = slider.data('speed') || 300;
    const autoplaySpeed = slider.data('autoplay-speed') || 5000;

    slider.on('init', function () {
      const firstBlock = slides.eq(0).find('[data-video]');

      if (firstBlock.length && firstBlock.data('BgVideo') && !firstBlock.hasClass('video-loaded')) {
        firstBlock.data('BgVideo').initPlayer();
      }
    });

    slider.slick({
      slidesToScroll: 1,
      rows: 0,
      prevArrow:
        '<button class="slick-control slick-prev slick-arrow" aria-label="Previous"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="m432-480 156 156q11 11 11 28t-11 28q-11 11-28 11t-28-11L348-452q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l184-184q11-11 28-11t28 11q11 11 11 28t-11 28L432-480Z"/></svg></button>',
      nextArrow:
        '<button class="slick-control slick-next slick-arrow" aria-label="Next"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M504-480 348-636q-11-11-11-28t11-28q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L404-268q-11 11-28 11t-28-11q-11-11-11-28t11-28l156-156Z"/></svg></button>',
      dots: true,
      dotsClass: 'slick-dots',
      autoplay: true,
      speed: speed,
      autoplaySpeed: autoplaySpeed,
    });

    videoBlocks.each(function () {
      const holder = jQuery(this);
      const videoAPI = holder.data('BgVideo');

      if (videoAPI) {
        holder
          .on('playingVideo', () => {
            slider.slick('slickPause');
          })
          .on('pauseVideo', () => {
            slider.slick('slickPlay');
          })
          .on('endedVideo', () => {
            slider.slick('slickPlay');
          });
      }
    });

    slider.on('beforeChange', function (event, slick, currentSlide, nextSlide) {
      const currVideoBlock = slides.eq(currentSlide).find('[data-video]');
      const nextVideoBlock = slides.eq(nextSlide).find('[data-video]');

      if (nextVideoBlock.length && nextVideoBlock.data('BgVideo') && !nextVideoBlock.hasClass('video-loaded')) {
        nextVideoBlock.data('BgVideo').initPlayer();
      }

      if (currVideoBlock.length && currVideoBlock.data('BgVideo').player) {
        currVideoBlock.data('BgVideo').player.pause();
      }
    });
  });
}

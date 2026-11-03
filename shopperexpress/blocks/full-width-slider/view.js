document.addEventListener('DOMContentLoaded', () => {
  initFullWidthSlider();
});

function initFullWidthSlider() {
  jQuery('.full-width-image-slider').each(function () {
    const slider = jQuery(this);
    const speed = slider.data('speed') || 300;
    const autoplaySpeed = slider.data('autoplay-speed') || 5000;

    slider.slick({
      rows: 0,
      slidesToScroll: 1,
      slidesToShow: 1,
      arrows: false,
      dots: true,
      dotsClass: 'slick-dots',
      autoplay: true,
      pauseOnHover: true,
      speed: speed,
      autoplaySpeed: autoplaySpeed,
    });
  });
}

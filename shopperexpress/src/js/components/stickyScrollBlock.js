import '../plugins/stickyScrollBlockPlugin';

export default function initStickyScrollBlock() {
  jQuery('.sticky-box').stickyScrollBlock({
    setBoxHeight: false,
    activeClass: 'fixed-position',
    container: '.detail-section',
    positionType: 'fixed',
    extraTop: function () {
      let totalHeight = 0;

      jQuery('#header').each(function () {
        totalHeight += jQuery(this).outerHeight();
      });

      return totalHeight;
    },
  });

  jQuery('.sticky-panel').stickyScrollBlock({
    setBoxHeight: true,
    activeClass: 'fixed-position',
    positionType: 'fixed',
    extraTop: function () {
      let totalHeight = 0;

      jQuery('#header').each(function () {
        totalHeight += jQuery(this).outerHeight();
      });

      return totalHeight;
    },
  });

  jQuery('.new-landing #header').stickyScrollBlock({
    setBoxHeight: false,
    activeClass: 'fixed-position',
    positionType: 'fixed',
    extraTop: function () {
      let totalHeight = 0;

      jQuery('0').each(function () {
        totalHeight += jQuery(this).outerHeight();
      });

      return totalHeight;
    },
  });

  const vinButtons = jQuery('#vinButtons');
  let loadTimer = null;

  if (vinButtons.length) {
    loadTimer = setInterval(() => {
      if (vinButtons.find('>*').length) {
        jQuery(window).trigger('refreshStikyBlock');
        clearInterval(loadTimer);
      }
    }, 1000);

    setTimeout(() => {
      clearInterval(loadTimer);
    }, 120000);
  }
}

import '@fancyapps/fancybox';

// lightbox init
export default function initFancybox() {
  const reverseClass = 'reverse-image';
  const bodyReverseClass = 'fancybox-has-reverse';
  const body = jQuery('body');

  jQuery('a.lightbox:not(.btn-spin), [data-fancybox]:not(.btn-spin)').fancybox({
    parentEl: 'body',
    margin: [50, 0],
    backFocus: false,
    beforeShow: function (instance, current) {
      body.removeClass(bodyReverseClass);
      jQuery('.fancybox-container, .fancybox-image, .fancybox-content').removeClass(reverseClass);

      const $opener = current.opts.$orig;

      if ($opener && $opener.hasClass(reverseClass)) {
        body.addClass(bodyReverseClass);
        jQuery('.fancybox-container').addClass(reverseClass);
      }
    },
    afterShow: function () {
      if (jQuery('.fancybox-container').hasClass(reverseClass)) {
        jQuery('.fancybox-image, .fancybox-content').addClass(reverseClass);
      }
    },
    beforeClose: function () {
      jQuery('.fancybox-image, .fancybox-content').removeClass(reverseClass);
    },
    afterClose: function () {
      body.removeClass(bodyReverseClass);
      jQuery('.fancybox-container').removeClass(reverseClass);
    },
  });
}

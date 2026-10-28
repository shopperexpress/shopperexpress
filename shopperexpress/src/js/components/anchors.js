import '../plugins/smoothScrollPlugin';
import '../plugins/stickyScrollBlockPlugin';

// initialize smooth anchor links
export default function initAnchors() {
  new SmoothScroll({
    anchorLinks: 'a.anchor',
    extraOffset: 0,
    wheelBehavior: 'none',
  });

  new SmoothScroll({
    anchorLinks: 'a.btn-anchor',
    extraOffset: function () {
      let totalHeight = 0;

      jQuery('#header').each(function () {
        const $box = jQuery(this);
        const stickyInstance = $box.data('StickyScrollBlock');

        if (stickyInstance) {
          stickyInstance.stickyFlag = false;
          stickyInstance.stickyOn();
          totalHeight += $box.outerHeight();
          stickyInstance.onResize();
        } else {
          totalHeight += $box.css('position') === 'fixed' ? $box.outerHeight() : 0;
        }
      });

      return totalHeight;
    },
    wheelBehavior: 'none',
  });
}

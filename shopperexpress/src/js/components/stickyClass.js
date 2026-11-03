// initialize sticky class
export default function initStickyClass() {
  const win = jQuery(window);
  const page = jQuery('body');
  const searchPanel = page.find('.sticky-wrap-sticky-panel');
  const activeClass = 'sticky-search';
  const fixedClass = 'fixed-position';

  function scrollHandler() {
    if (searchPanel.hasClass(fixedClass)) {
      page.addClass(activeClass);
    } else {
      page.removeClass(activeClass);
    }
  }

  scrollHandler();
  win.on('load resize orientationchange scroll', scrollHandler);
}

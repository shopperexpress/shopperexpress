// init touch device
export default function initTouchDevice() {
  const isTouchDevice = /Windows Phone/.test(navigator.userAgent) || 'ontouchstart' in window || (window.DocumentTouch && document instanceof DocumentTouch);

  if (isTouchDevice) {
    jQuery('html').addClass('touch-device');
  }
}

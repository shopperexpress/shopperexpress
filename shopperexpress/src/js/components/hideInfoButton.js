// Hide info button init
export default function initHideInfoButton() {
  document.querySelectorAll('.evo-slider-holder').forEach((iframeHolder) => {
    const overlay = iframeHolder.querySelector('.evo-slider-btn-overlay');

    if (!overlay) return;

    overlay.addEventListener('click', hideInfoButton);

    function hideInfoButton() {
      overlay.style.display = 'none';

      overlay.removeEventListener('click', hideInfoButton);
    }
  });
}

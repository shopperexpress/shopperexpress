// Stretch iframe on page resize
export default function initStretchIframe() {
  const iframes = document.querySelectorAll('.evo-slider iframe.ratio-custom');

  if (!iframes.length) return;

  const iframeData = Array.from(iframes).map((iframe) => {
    const width = iframe.width;
    const height = iframe.height;
    const ratio = width / height;

    return {
      iframe,
      ratio,
    };
  });

  const triggerUpdateSliderHeight = debounce(() => {
    window.dispatchEvent(new CustomEvent('updateSliderHeight'));
  }, 250);

  function updateIframes() {
    iframeData.forEach(({ iframe, ratio }) => {
      iframe.style.height = `${iframe.parentNode.offsetWidth / ratio}px`;
    });

    triggerUpdateSliderHeight();
  }

  const debouncedUpdate = debounce(updateIframes, 200);

  updateIframes();

  window.addEventListener('resize', debouncedUpdate);

  function debounce(func, delay = 250) {
    let timer = null;

    return (...args) => {
      clearTimeout(timer);

      timer = setTimeout(() => func.apply(this, args), delay);
    };
  }
}

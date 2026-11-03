// Switch Logos init
export default function initSwitchLogos() {
  const activeClass = 'active';

  document.querySelectorAll('.logo-slider').forEach((holder) => {
    if (holder.dataset.sliderInitialized === 'true') return;

    const brands = [...holder.querySelectorAll('.slider-item')];
    const intervalTime = Number.parseInt(holder.dataset.autoplaySpeed, 10) || 3000;

    let currentIndex = 0;

    if (!brands.length) return;

    holder.dataset.sliderInitialized = 'true';

    function updateHolderWidth() {
      const activeBrand = brands[currentIndex];
      const image = activeBrand?.querySelector('img');

      if (!image) return;

      requestAnimationFrame(() => {
        const imageWidth = image.getBoundingClientRect().width;

        if (imageWidth > 0) {
          holder.style.width = `${imageWidth}px`;
        }
      });
    }

    function setActiveBrand(index) {
      brands.forEach((brand, brandIndex) => {
        const isActive = brandIndex === index;

        brand.classList.toggle(activeClass, isActive);
        brand.setAttribute('aria-hidden', String(!isActive));
        brand.tabIndex = isActive ? 0 : -1;
      });

      updateHolderWidth();
    }

    function switchBrand() {
      currentIndex = (currentIndex + 1) % brands.length;
      setActiveBrand(currentIndex);
    }

    brands.forEach((brand) => {
      const image = brand.querySelector('img');

      if (!image) return;

      if (!image.complete) {
        image.addEventListener(
          'load',
          () => {
            if (brand.classList.contains(activeClass)) {
              updateHolderWidth();
            }
          },
          { once: true }
        );
      }
    });

    setActiveBrand(currentIndex);

    window.addEventListener('resize', updateHolderWidth);

    if (brands.length > 1) {
      window.setInterval(switchBrand, intervalTime);
    }
  });
}

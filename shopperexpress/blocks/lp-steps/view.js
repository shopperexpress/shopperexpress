document.addEventListener('DOMContentLoaded', () => {
  initScrollSteps();
});

function initScrollSteps() {
  const activeClass = 'is-animate';
  // trigger line position in the viewport (0.5 - center of the screen)
  const triggerOffset = 0.3;
  const lists = document.querySelectorAll('.lp-steps-list');

  if (!lists.length) return;

  const steps = [];

  lists.forEach((list) => {
    const progress = list.parentElement.querySelector('.step-progress .progress-bar');
    const items = list.querySelectorAll(':scope > li');

    if (!progress || !items.length) return;

    steps.push({ list, progress, items });
  });

  if (!steps.length) return;

  let ticking = false;
  let syncFrame = null;

  // toggle classes by the rendered (animated) height of the progress, not by the target one
  function syncItems() {
    let isAnimating = false;

    steps.forEach((step) => {
      const progressRect = step.progress.getBoundingClientRect();

      step.items.forEach((item) => {
        const itemOffset = item.getBoundingClientRect().top - progressRect.top;
        item.classList.toggle(activeClass, progressRect.height > 0 && progressRect.height >= itemOffset);
      });

      if (Math.abs(progressRect.height - step.targetHeight) > 0.5) {
        isAnimating = true;
      }
    });

    syncFrame = isAnimating ? window.requestAnimationFrame(syncItems) : null;
  }

  function update() {
    const triggerY = window.innerHeight * triggerOffset;

    steps.forEach((step) => {
      const { list, progress, items } = step;
      const listRect = list.getBoundingClientRect();
      const lastItemTop = items[items.length - 1].getBoundingClientRect().top;
      // 0 - trigger line reached the top of the list, 1 - reached the last li
      const distance = lastItemTop - listRect.top;
      const ratio = distance > 0 ? Math.min(Math.max((triggerY - listRect.top) / distance, 0), 1) : 1;

      step.targetHeight = ratio * listRect.height;
      progress.style.height = `${step.targetHeight}px`;
    });

    ticking = false;

    if (!syncFrame) {
      syncFrame = window.requestAnimationFrame(syncItems);
    }
  }

  function onScroll() {
    if (ticking) return;

    ticking = true;
    window.requestAnimationFrame(update);
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll);
  window.addEventListener('load', update);
  update();
}

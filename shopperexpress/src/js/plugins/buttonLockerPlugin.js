/**
 * Lock buttons for a specified duration after click
 *
 * @param {string} selector
 * @param {number} duration
 */
export const ButtonLocker = (() => {
  const STORAGE_KEY = 'buttonLockUntil';

  function init(selector = '[data-clear]', duration = 60000) {
    const buttons = document.querySelectorAll(selector);

    if (!buttons.length) return;

    applyLock(selector);

    buttons.forEach((btn) => {
      btn.addEventListener('click', (e) => {
        if (btn.disabled) {
          e.preventDefault();

          return;
        }

        const unblockAt = Date.now() + duration;

        localStorage.setItem(STORAGE_KEY, unblockAt);

        lockButtons(selector);
        setTimeout(() => applyLock(selector), duration + 50);
      });
    });

    setInterval(() => applyLock(selector), 10000);
  }

  function applyLock(selector) {
    const buttons = document.querySelectorAll(selector);

    if (!buttons.length) return;

    const unblockAt = Number(localStorage.getItem(STORAGE_KEY)) || 0;
    const now = Date.now();

    if (now >= unblockAt) {
      unlockButtons(selector);
      localStorage.removeItem(STORAGE_KEY);
    } else {
      lockButtons(selector);
    }
  }

  function lockButtons(selector) {
    const buttons = document.querySelectorAll(selector);

    buttons.forEach((btn) => {
      btn.disabled = true;
      btn.classList.add('disabled');
    });
  }

  function unlockButtons(selector) {
    const buttons = document.querySelectorAll(selector);

    buttons.forEach((btn) => {
      btn.disabled = false;
      btn.classList.remove('disabled');
    });
  }

  return { init, applyLock };
})();

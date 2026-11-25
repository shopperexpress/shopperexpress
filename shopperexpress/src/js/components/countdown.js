import Cookies from 'js-cookie';

const UNITS = [
  ['days', 86400],
  ['hours', 3600],
  ['minutes', 60],
  ['seconds', 1],
];
const DAY_MS = 86400000;

/**
 * Countdown timer (`.countdown-timer`, rendered by the [countdown] shortcode).
 * Types: fixed (shared end timestamp), evergreen (per-visitor, starts on first view), daily (next site midnight).
 * Inside a Bootstrap modal the timer only runs while the modal is open.
 *
 * @param {HTMLElement} el Timer element.
 * @return {{start: Function, stop: Function}}
 */
function createCountdown(el) {
  const { type, units = '', action = 'hide', id = '' } = el.dataset;
  const enabledUnits = units.split(',').filter(Boolean);
  const items = {};
  el.querySelectorAll('.countdown-timer__item').forEach((item) => {
    items[item.dataset.unit] = item.querySelector('.countdown-timer__value');
  });

  let timer = null;
  let expired = false;
  let endMs = null;
  const pad = !el.classList.contains('countdown-timer--minimal');

  function resolveEnd() {
    if (type === 'fixed') {
      return Number(el.dataset.end) * 1000;
    }

    if (type === 'evergreen') {
      const duration = Number(el.dataset.duration);
      const key = `cd-${id}-${duration}`;
      let startMs = Number(Cookies.get(key));

      if (!startMs) {
        startMs = Date.now();
        Cookies.set(key, String(startMs), { expires: 365, path: '/' });
      }

      return startMs + duration * 1000;
    }

    // daily: next midnight in the site timezone.
    const offsetMs = Number(el.dataset.tzOffset || 0) * 1000;
    return Math.ceil((Date.now() + offsetMs + 1) / DAY_MS) * DAY_MS - offsetMs;
  }

  function render(remainingSec) {
    let rest = remainingSec;

    UNITS.forEach(([name, size]) => {
      if (!enabledUnits.includes(name) || !items[name]) {
        return;
      }

      const value = Math.floor(rest / size);
      rest -= value * size;
      items[name].textContent = pad ? String(value).padStart(2, '0') : String(value);
    });
  }

  function expire() {
    expired = true;
    stop();
    render(0);

    if (action === 'keep') {
      return; // stays on the page showing zeros
    }

    if (action === 'message') {
      el.querySelector('.countdown-timer__items')?.setAttribute('hidden', '');
      el.querySelector('.countdown-timer__label')?.setAttribute('hidden', '');
      el.querySelector('.countdown-timer__expired')?.removeAttribute('hidden');
    } else {
      el.setAttribute('hidden', '');
    }
  }

  function tick() {
    const remaining = Math.max(0, Math.ceil((endMs - Date.now()) / 1000));

    if (remaining === 0) {
      expire();
      return;
    }

    render(remaining);
  }

  function start() {
    if (timer || expired) {
      return;
    }

    endMs = endMs ?? resolveEnd();
    tick();

    if (!expired) {
      timer = setInterval(tick, 1000);
    }
  }

  function stop() {
    clearInterval(timer);
    timer = null;
  }

  return { start, stop };
}

export default function initCountdowns(root = document) {
  root.querySelectorAll('.countdown-timer:not([data-cd-init])').forEach((el) => {
    el.dataset.cdInit = '1';

    const countdown = createCountdown(el);
    const modal = el.closest('.modal');

    if (modal) {
      jQuery(modal).on('show.bs.modal', countdown.start);
      jQuery(modal).on('hidden.bs.modal', countdown.stop);
    } else {
      countdown.start();
    }
  });
}

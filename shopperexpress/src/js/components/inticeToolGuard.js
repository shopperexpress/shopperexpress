/**
 * Guard for the intice shopping-tool buttons (Leadmaker, Trademaker/KBB,
 * Trademaker Get Real Offer, Dealmaker, Loanmaker).
 *
 * All of these buttons — whether rendered from the numeric event-id mapping in
 * get_event_script() (single-listings.php, anchor-list.php, …) or from the raw
 * free-text ACF "event" field in conversion-block.php — are emitted as a plain
 * inline `onclick="inticeAllEvents.<method>(...)"` (optionally "javascript:"
 * prefixed) attribute, with no check that `inticeAllEvents` (or its popup
 * dependency, e.g. tingle) has actually finished loading. When a consent tool
 * like ComplyAuto blocks the script that defines `inticeAllEvents`, the inline
 * handler throws "inticeAllEvents is not defined" and the button goes dead
 * with no feedback.
 *
 * This module intercepts those clicks at the document level (capture phase,
 * so it runs before the element's own inline handler), waits briefly for
 * `inticeAllEvents` to become ready, and only then runs the exact same launch
 * call the markup already specifies — or shows a visible unavailable state
 * instead of a silent dead button. It never changes which tool is launched or
 * with what arguments (dealer id / VIN / product are untouched, taken as-is
 * from the existing onclick string), and never bypasses consent or loads
 * anything itself — it only waits for scripts already permitted to load.
 *
 * Coordination needed with the intice team (see project brief): the presence
 * of `window.inticeAllEvents` alone does not guarantee a given tool's
 * supporting dependencies (e.g. the popup library) are ready. Until intice
 * exposes a real readiness signal (promise/event), this guard can only check
 * "does inticeAllEvents.<method> exist" — if intice adds a richer readiness
 * API, replace isReady() below to use it.
 */

const SELECTOR = '[onclick*="inticeAllEvents."]';
const READY_TIMEOUT_MS = 6000;
const POLL_INTERVAL_MS = 150;
const LOADING_CLASS = 'intice-guard--loading';
const UNAVAILABLE_CLASS = 'intice-guard--unavailable';
const BUSY_ATTR = 'data-intice-guard-busy';

// Candidate global function names a consent platform might expose to reopen
// its preferences UI. Unconfirmed — intended as a starting point to verify
// with ComplyAuto/the client, not a guess we rely on for correctness.
const CONSENT_SETTINGS_GLOBALS = ['openComplyAutoSettings', 'displayComplyAutoPreferences', 'CCPA'];

/**
 * Pull the function name and the exact call expression out of a raw
 * `onclick="javascript:inticeAllEvents.launchTM('848','VIN');"` attribute.
 *
 * @param {string} raw
 * @return {{code: string, fnName: string|null}}
 */
function parseCall(raw) {
  const code = (raw || '').replace(/^\s*javascript:/i, '').trim();
  const match = code.match(/inticeAllEvents\.(\w+)\s*\(/);

  return { code, fnName: match ? match[1] : null };
}

function isReady(fnName) {
  return typeof window.inticeAllEvents !== 'undefined' && typeof window.inticeAllEvents[fnName] === 'function';
}

function findConsentSettingsOpener() {
  return CONSENT_SETTINGS_GLOBALS.find((name) => typeof window[name] === 'function') || null;
}

function clearState(el) {
  el.classList.remove(LOADING_CLASS, UNAVAILABLE_CLASS);

  const message = el.querySelector('.intice-guard__message');

  if (message) {
    message.remove();
  }
}

function setLoading(el, isLoading) {
  el.classList.toggle(LOADING_CLASS, isLoading);
  el.setAttribute('aria-busy', isLoading ? 'true' : 'false');

  if (isLoading) {
    el.setAttribute(BUSY_ATTR, '1');
  } else {
    el.removeAttribute(BUSY_ATTR);
  }
}

function showUnavailable(el) {
  el.classList.remove(LOADING_CLASS);
  el.classList.add(UNAVAILABLE_CLASS);
  el.setAttribute('aria-busy', 'false');
  el.removeAttribute(BUSY_ATTR);

  let message = el.querySelector('.intice-guard__message');

  if (!message) {
    message = document.createElement('span');
    message.className = 'intice-guard__message';
    message.setAttribute('role', 'status');
    el.appendChild(message);
  }

  message.textContent = '';
  message.appendChild(document.createTextNode('This tool is temporarily unavailable. Please try again.'));

  const openerName = findConsentSettingsOpener();

  if (openerName) {
    const link = document.createElement('button');

    link.type = 'button';
    link.className = 'intice-guard__settings-link';
    link.textContent = 'Privacy settings';
    link.addEventListener('click', (event) => {
      event.stopPropagation();
      window[openerName]();
    });

    message.appendChild(link);
  }
}

function dispatch(el, code) {
  clearState(el);

  try {
    // Re-running the exact snippet the markup already specifies — this is
    // admin/dealer-authored content (ACF "event" field or get_event_script()),
    // not user input, and it is the same code that would otherwise have run
    // directly as the inline onclick handler.

    new Function(code)();
  } catch (error) {
    console.error('[intice-guard] launch failed', error);
    showUnavailable(el);
  }
}

function waitAndLaunch(el, fnName, code) {
  if (el.hasAttribute(BUSY_ATTR)) {
    return;
  }

  setLoading(el, true);

  const startedAt = Date.now();

  const poll = () => {
    if (isReady(fnName)) {
      dispatch(el, code);
      return;
    }

    if (Date.now() - startedAt >= READY_TIMEOUT_MS) {
      showUnavailable(el);
      return;
    }

    setTimeout(poll, POLL_INTERVAL_MS);
  };

  poll();
}

export default function initInticeToolGuard() {
  document.addEventListener(
    'click',
    (event) => {
      const el = event.target.closest(SELECTOR);

      if (!el) {
        return;
      }

      const { code, fnName } = parseCall(el.getAttribute('onclick'));

      if (!fnName) {
        return;
      }

      // Stopping propagation during the capture phase keeps the element's own
      // inline onclick from ever running — we decide if/when to run it instead.
      event.preventDefault();
      event.stopPropagation();

      if (el.hasAttribute(BUSY_ATTR)) {
        return;
      }

      if (isReady(fnName)) {
        dispatch(el, code);
      } else {
        waitAndLaunch(el, fnName, code);
      }
    },
    true
  );
}

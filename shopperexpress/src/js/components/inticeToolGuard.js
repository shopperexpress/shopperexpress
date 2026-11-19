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
const POPUP_OPEN_CLASS = 'intice-guard-popup-open';

// Candidate global function names a consent platform might expose to reopen
// its preferences UI. Unconfirmed — intended as a starting point to verify
// with ComplyAuto/the client, not a guess we rely on for correctness.
const CONSENT_SETTINGS_GLOBALS = ['openComplyAutoSettings', 'displayComplyAutoPreferences', 'CCPA'];

// Every click that starts a new wait (or dispatches immediately) bumps this,
// superseding whatever was already in flight. Each waitAndLaunch() poll loop
// captures the token current at its own start and checks it on every tick —
// a mismatch means a different button was clicked since, so it quietly backs
// off instead of popping up later. Without this, clicking several buttons
// while the first is still waiting left multiple independent 6s timers
// running, each one eventually opening its own popup — closing one just
// let the next appear a moment later, feeling like it "wouldn't close".
let activeToken = 0;

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

function closePopup() {
  document.querySelectorAll('.intice-guard-popup__overlay').forEach((overlay) => overlay.remove());

  document.removeEventListener('keydown', onPopupKeydown, true);
  document.body.classList.remove(POPUP_OPEN_CLASS);
}

// Used for user-initiated dismissal (close button, backdrop, Escape) — unlike
// closePopup() alone, this also invalidates any other button's in-flight
// wait so it can't surface a popup of its own right after this one closes.
function dismissPopup() {
  activeToken++;
  closePopup();
}

function onPopupKeydown(event) {
  if (event.key === 'Escape') {
    dismissPopup();
  }
}

function openPopup() {
  closePopup();

  const overlay = document.createElement('div');
  overlay.className = 'intice-guard-popup__overlay';
  overlay.setAttribute('role', 'presentation');

  const popup = document.createElement('div');
  popup.className = 'intice-guard-popup';
  popup.setAttribute('role', 'alertdialog');
  popup.setAttribute('aria-modal', 'true');

  const closeBtn = document.createElement('button');
  closeBtn.type = 'button';
  closeBtn.className = 'intice-guard-popup__close';
  closeBtn.setAttribute('aria-label', 'Close');
  closeBtn.textContent = '×';

  const message = document.createElement('p');
  message.className = 'intice-guard-popup__message';
  message.setAttribute('role', 'status');
  message.textContent = 'This tool is temporarily unavailable. Please try again.';

  popup.appendChild(closeBtn);
  popup.appendChild(message);

  const openerName = findConsentSettingsOpener();

  if (openerName) {
    const link = document.createElement('button');

    link.type = 'button';
    link.className = 'intice-guard-popup__settings-link';
    link.textContent = 'Privacy settings';

    popup.appendChild(link);
  }

  overlay.appendChild(popup);

  document.body.appendChild(overlay);
  document.body.classList.add(POPUP_OPEN_CLASS);
  document.addEventListener('keydown', onPopupKeydown, true);
  closeBtn.focus();
}

function clearState(el) {
  el.classList.remove(LOADING_CLASS, UNAVAILABLE_CLASS);
  el.setAttribute('aria-busy', 'false');
  el.removeAttribute(BUSY_ATTR);
}

function setLoading(el) {
  el.classList.add(LOADING_CLASS);
  el.classList.remove(UNAVAILABLE_CLASS);
  el.setAttribute('aria-busy', 'true');
  el.setAttribute(BUSY_ATTR, '1');
}

function showUnavailable(el) {
  el.classList.remove(LOADING_CLASS);
  el.classList.add(UNAVAILABLE_CLASS);
  el.setAttribute('aria-busy', 'false');
  el.removeAttribute(BUSY_ATTR);

  openPopup();
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

function waitAndLaunch(el, fnName, code, token) {
  setLoading(el);

  const startedAt = Date.now();

  const poll = () => {
    // A different button was clicked since this poll started — back off
    // quietly instead of launching or popping up a message for a click the
    // user has already moved on from.
    if (token !== activeToken) {
      clearState(el);
      return;
    }

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

// Handles dismissal for clicks that land on the popup's close button, its
// backdrop, or the consent-settings link. Lives on the same document-level
// capture listener as the rest of the guard (rather than as per-element
// listeners on the popup's own nodes) because some third-party script on
// this site installs its own document-level click listener and calls
// stopPropagation() on it — that stops the event from ever reaching the
// popup's descendants, so a listener attached directly to the close button
// never fires. A listener on `document` itself still runs regardless, since
// stopPropagation() only stops the event from reaching other *nodes*, not
// other listeners already registered on the same node, and event.target is
// fixed at dispatch time, so closest() below still resolves correctly even
// when the event's further propagation has been cut short upstream.
function handlePopupDismissClick(event) {
  if (!document.body.classList.contains(POPUP_OPEN_CLASS)) {
    return false;
  }

  const target = event.target;
  const settingsLink = target.closest('.intice-guard-popup__settings-link');

  if (settingsLink) {
    dismissPopup();

    const openerName = findConsentSettingsOpener();

    if (openerName) {
      window[openerName]();
    }

    return true;
  }

  if (target.closest('.intice-guard-popup__close')) {
    dismissPopup();
    return true;
  }

  if (target.classList && target.classList.contains('intice-guard-popup__overlay')) {
    dismissPopup();
    return true;
  }

  return false;
}

export default function initInticeToolGuard() {
  document.addEventListener(
    'click',
    (event) => {
      if (handlePopupDismissClick(event)) {
        return;
      }

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
        activeToken++;
        closePopup();
        dispatch(el, code);
      } else {
        // Supersede whatever was already in flight (a different button's
        // wait, or its popup) — only the latest click should ever be able
        // to show a popup.
        const token = ++activeToken;
        closePopup();
        waitAndLaunch(el, fnName, code, token);
      }
    },
    true
  );
}

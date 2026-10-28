import './resizeHandler.js';
// import './vh'; - use it in case crossbrowser vh value is needed

export const HTML = document.documentElement;
export const BODY = document.body;

/*
  "jQuery like" ready function:
  Usage:

  import ready from 'Utils';
  ready(() => init());

  Runs the callback straight away once the document has been parsed. DOMContentLoaded has
  already fired for anything imported later than it — a dynamic import, or a module loaded
  by a script without `defer` — and a listener added then never runs.
*/

export default (fn) => {
  if (typeof fn !== 'function') return;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fn, { once: true });
    return;
  }

  fn();
};

/*
  Publish custom event
  Params:

  {eventName}: String - the name of custom event. Better to use as a variable or constant not to mess names
  {data}: Object - custom event information, e.g. node element, whatever. Accessible via event
  {once}: Bool - trigger only once or every time when called
  Exmaple:

  import {ev} from 'Utils/global';

  const eventName = 'PopupToggle';

  popup.on('click', () => {
    ev(eventName, {
      popup: this,
    })
  })

  document.addEventListener(eventName, event => {
    // this is data that we pass into custom event
    const eventData = event.detail;
    const popupInstance = eventData.popup;
  })
*/

export const ev = (eventName, data, target = document) => {
  const e = new CustomEvent(eventName, { detail: data });
  target.dispatchEvent(e);
};

import '../plugins/customFormsPlugin';
import updateJourneyField from './updateJourneyField';

export default function initUnlockSavings() {
  // Keyed by "action|post_id|form_id" → jQuery Deferred promise from prefetch
  const prefetchCache = {};

  function getPrefetchKey(action, post_id, form_id) {
    return `${action}|${post_id}|${form_id}`;
  }

  let prefetchDone = false;

  jQuery(
    '[data-target="#buttonModal"], [data-target="#unlockSavingsModal"], [data-target="#Disclosure_loan-acf"], [data-target="#Disclosure_lease-acf"], [data-target="#Disclosure_Cash-acf"]'
  ).each(function () {
    const unlockButton = jQuery(this);

    if (!unlockButton.data('is-init')) {
      unlockButton.data('is-init', true);

      unlockButton.on('mouseenter pointerdown', function onFirstHover() {
        if (prefetchDone) return;
        prefetchDone = true;

        const post_id = unlockButton.data('post') || '';
        const form_id = unlockButton.data('form') || '';
        const target = unlockButton.data('target');

        let action;
        if (target === '#unlockSavingsModal' || target === '#buttonModal') {
          action = 'unlock_form';
        } else if (target === '#Disclosure_loan-acf' || target === '#Disclosure_lease-acf' || target === '#Disclosure_Cash-acf') {
          action = 'offers_form';
        } else {
          return;
        }

        const key = getPrefetchKey(action, post_id, form_id);
        prefetchCache[key] = jQuery.ajax({
          url: window.location.href,
          type: 'POST',
          data: { action, form_id, post_id, target },
        });
      });

      unlockButton.on('click', function (e) {
        e.preventDefault();
        unlockHandler(unlockButton);
      });
    }
  });

  jQuery('#unlockSavingsModal, #contactModal, #buttonModal').on('shown.bs.modal', function () {
    setTimeout(() => {
      // Write the user journey to the input from the local storage
      updateJourneyField();
      updateFullCircleFields();
    }, 500);
  });

  // Keep the FullCircle hidden fields fresh on focus and right before submit,
  // matching how the FullCircle tracker itself refreshes them.
  jQuery(document).on('focusin submit', '.wpforms-form', updateFullCircleFields);
  updateFullCircleFields();

  jQuery('.wpforms-render-modern').each(function () {
    const form = jQuery(this);
    const btnSubmit = form.find('.wpforms-page-button');

    if (!btnSubmit.length) return;

    btnSubmit.on('click', () => {
      // Wait for the last page to be shown
      setTimeout(() => {
        initTwillioAPI(form, false);
      }, 100);
    });
  });

  function showUnlockedPrices(modal) {
    jQuery('.payment-info a').each(function () {
      jQuery(this).closest('li').addClass('show');
    });
  }

  const unlockSavingsModal = jQuery('#unlockSavingsModal');

  unlockSavingsModal
    .on('click', `.wpforms-confirmation-container-full a[href^="${window.location.href}"]`, (e) => {
      e.preventDefault();
      unlockSavingsModal.modal('hide');
    })
    .on('hide.bs.modal', () => {
      showUnlockedPrices(unlockSavingsModal);
    });

  function unlockHandler(button) {
    const post_id = button.data('post') || ''; // some buttons have post_id
    const form_id = button.data('form') || ''; // some buttons have form
    const target = button.data('target');

    if (form_id && target === '#buttonModal') {
      const linkText = button.text().trim();
      const dataTitleElement = jQuery('[data-title]');

      if (dataTitleElement.length) {
        dataTitleElement.text(linkText);
      }
    }

    if (!target) return;

    jQuery('#savings-form .wpforms-page-1').show(); // show first step
    jQuery('#savings-form .wpforms-page-2').hide(); // hide second step

    // For button on listing and offers pages
    if (target === '#unlockSavingsModal') {
      sendRequest('unlock_form', post_id, form_id, target, '#savings-form');
    }

    if (target === '#buttonModal') {
      sendRequest('unlock_form', post_id, form_id, target, '#button-form');
    }

    // For buttons on homepage
    if (target === '#Disclosure_loan-acf' || target === '#Disclosure_lease-acf' || target === '#Disclosure_Cash-acf') {
      sendRequest('offers_form', post_id, form_id, target, '.modal-offer .wpforms-ajax-form');
    }
  }

  function sendRequest(action, post_id, form_id, target, formSelector) {
    const key = getPrefetchKey(action, post_id, form_id);

    // Use the prefetched promise if available, then clear it so next click always fires fresh
    const promise =
      prefetchCache[key] ||
      jQuery.ajax({
        url: window.location.href,
        type: 'POST',
        data: { action, form_id, post_id, target },
      });
    delete prefetchCache[key];

    promise.done(function (response) {
      const form = jQuery(formSelector);
      const container = form.get(0);

      if (!container) return;

      // Parse into an inert <template> so nothing executes yet. Scripts are
      // pulled out and run by runScripts() below rather than by jQuery's
      // .html(): jQuery evaluates inline scripts via a temporary <head> node it
      // removes afterwards, which throws ("parentNode is null") on WPForms'
      // self-removing honeypot snippet (document.currentScript.remove()) and
      // aborted everything after the insert — leaving Next disabled.
      const template = document.createElement('template');
      template.innerHTML = response;

      const scripts = Array.from(template.content.querySelectorAll('script'));
      scripts.forEach((script) => script.remove());

      // The response carries every asset WPForms needs for the form (see
      // Ajax::render_wpforms_with_assets()). Skip the ones the page already
      // has, matched by WP's handle id: re-running jQuery would wipe its
      // plugins (Bootstrap modals, jcf, …) and re-running wpforms.min.js would
      // bind its document-level handlers a second time.
      const isOnPage = (el) => el.id && document.getElementById(el.id);

      template.content.querySelectorAll('link[id], style[id]').forEach((el) => {
        if (isOnPage(el)) el.remove();
      });

      // When wpforms.min.js was already on the page it won't init the new form
      // on its own; when it arrives with this response, its own init does.
      const wpformsAlreadyLoaded = !!(window.wpforms && typeof window.wpforms.ready === 'function');

      jQuery('.post-id input').val(post_id);

      // Replace the form with the new one
      form.empty();
      container.appendChild(template.content);

      runScripts(
        scripts.filter((script) => !isOnPage(script)),
        container
      ).then(() => {
        if (wpformsAlreadyLoaded) {
          window.wpforms.ready();
        }

        if (window.WPForms && window.WPForms.FrontendModern && typeof window.WPForms.FrontendModern.initPageBreakButtons === 'function') {
          window.WPForms.FrontendModern.initPageBreakButtons();
        }

        // Write the user journey to the input from the local storage
        updateJourneyField();
        updateFullCircleFields();

        // Handle the form pages
        initFormPagesHandler(form);

        form.find('select').each(function () {
          jcf.replace(jQuery(this));
        });
      });
    });
  }
}

/**
 * Execute <script> elements taken from an HTML fragment, in document order.
 * Each one is recreated as a real element (scripts inserted via innerHTML
 * never run) and appended to `target`; external ones are awaited before the
 * next runs so dependency order (jquery-validate → wpforms → …) is kept.
 * A failing script is logged by the browser and does not stop the chain.
 *
 * @param {HTMLScriptElement[]} scripts
 * @param {HTMLElement} target
 * @return {Promise<void>}
 */
function runScripts(scripts, target) {
  return scripts.reduce(
    (chain, source) =>
      chain.then(
        () =>
          new Promise((resolve) => {
            const script = document.createElement('script');

            Array.from(source.attributes).forEach((attr) => script.setAttribute(attr.name, attr.value));

            if (source.src) {
              script.async = false;
              script.onload = resolve;
              script.onerror = resolve;
              target.appendChild(script);
            } else {
              script.textContent = source.textContent;
              target.appendChild(script);
              resolve();
            }
          })
      ),
    Promise.resolve()
  );
}

/* Form pages handler */
function initFormPagesHandler(form) {
  // Scope everything to the AJAX-injected form: the same form may also be
  // rendered elsewhere on the page (e.g. #contactModal), so a document-wide
  // lookup can hit the wrong copy and leave this one's Next button disabled.
  const pageButtons = form.find('.wpforms-page-button');
  const lastPage = form.find('.wpforms-page.last');

  if (!pageButtons.length) return;

  // WPForms renders page buttons as disabled and only enables them once, on
  // page load (wpforms-modern.js initPageBreakButtons), which never runs for
  // forms injected later. Enable them now and again after the re-injected
  // wpforms.min.js finishes its own (async) init. "Next" stays disabled until
  // every required field of its page is filled — WPForms' navigateToPage()
  // ignores clicks on a .wpforms-disabled button.
  const enablePageButtons = () => {
    pageButtons.not('.wpforms-page-next').removeClass('wpforms-disabled').attr('aria-disabled', 'false').attr('aria-describedby', '');
    updateNextButtons(form);
  };

  enablePageButtons();
  jQuery(document).one('wpformsReady', enablePageButtons);
  setTimeout(enablePageButtons, 0);

  form.on('input change keyup', 'input, textarea, select', () => updateNextButtons(form));

  form.find('.wpforms-page-next').on('click', () => {
    // Wait for the last page to be shown
    setTimeout(() => {
      if (lastPage.length && lastPage.is(':visible')) {
        initTwillioAPI(form);
      }
    }, 100);
  });
}

/**
 * Enable each "Next" button only when all required fields on its page are filled.
 *
 * @param {jQuery} form
 */
function updateNextButtons(form) {
  form.find('.wpforms-page-next').each(function () {
    const button = jQuery(this);
    const page = button.closest('.wpforms-page');
    const isComplete = isPageComplete(page.length ? page : form);

    button.toggleClass('wpforms-disabled', !isComplete).attr('aria-disabled', String(!isComplete)).attr('aria-describedby', '');
  });
}

/**
 * @param {jQuery} page
 * @return {boolean}
 */
function isPageComplete(page) {
  const checkedGroups = {};

  return page
    .find('input[required], textarea[required], select[required]')
    .toArray()
    .every((field) => {
      // Fields hidden by conditional logic aren't submitted, so they don't count.
      if (field.disabled || field.closest('.wpforms-conditional-hide')) return true;

      if (field.type === 'checkbox' || field.type === 'radio') {
        if (!(field.name in checkedGroups)) {
          checkedGroups[field.name] = !!page.find(`input[name="${CSS.escape(field.name)}"]:checked`).length;
        }

        return checkedGroups[field.name];
      }

      if (!field.value.trim()) return false;

      // Masked inputs (phone, ZIP) hold placeholder chars until fully typed.
      if (field.inputmask && typeof field.inputmask.isComplete === 'function' && !field.inputmask.isComplete()) return false;

      // Native type checks, e.g. a malformed e-mail.
      return field.checkValidity();
    });
}

function initTwillioAPI(form, isHideStep = true) {
  const phoneField = form.find('div.sms-phone input[name*="wpforms[fields]"]');
  const codeField = form.find('div.sms-code input[name*="wpforms[fields]"]');
  const requestIdField = form.find('div.sms-request-id input[name*="wpforms[fields]"]');
  const phone = formatPhoneNumber(phoneField.val());
  const submitBtn = form.find('.wpforms-submit');
  let isSendingCode = false;

  if (!phone) {
    form.find('.sms-message').remove();
    codeField.closest('.wpforms-field').after('<div class="sms-message wpforms-error">Error: Phone number not provided</div>');
    switchToFirstFormStep(form);

    return;
  }

  if (isSendingCode) {
    return;
  }

  isSendingCode = true;

  submitBtn.hide();

  form.find('.sms-message').remove();

  jQuery.ajax({
    url: window.wpforms_settings ? window.wpforms_settings.ajaxurl : window.location.href,
    type: 'POST',
    data: {
      action: 'send_sms_for_verification',
      phone: phone,
    },
    success: function (response) {
      isSendingCode = false;

      if (response.success) {
        submitBtn.show();
        submitBtn.text('Validate');

        requestIdField.val(response.data.requestId);

        codeField.closest('.wpforms-field').after('<div class="sms-message wpforms-notice">SMS has been sent to ' + phone + '</div>');
      } else {
        codeField.closest('.wpforms-field').after('<div class="sms-message wpforms-notice">SMS sending error: ' + (response.data.message || 'Please try again') + '</div>');
        switchToFirstFormStep(form);
      }
    },
    error: function (xhr, status, error) {
      isSendingCode = false;
      console.error('SMS AJAX Error:', status, error);

      codeField.closest('.wpforms-field').after('<div class="sms-message wpforms-notice">Server error while sending SMS</div>');
      switchToFirstFormStep(form);
    },
  });
}

function switchToFirstFormStep(form) {
  setTimeout(function () {
    form.find('.wpforms-page-1').show();
    form.find('.wpforms-page-2').hide();
  }, 3000);
}

function formatPhoneNumber(phone) {
  const digits = phone.replace(/\D/g, '');

  if (digits.length === 10) {
    return `+1${digits}`;
  } else if (digits.length === 11 && digits.startsWith('1')) {
    return `+${digits}`;
  } else if (digits.startsWith('00')) {
    return `+${digits.slice(2)}`;
  } else if (digits.startsWith('+')) {
    return digits;
  } else {
    return `+${digits}`;
  }
}

// Populate the FullCircle hidden fields from the browser tracker just before submit.
// Values are never generated here — only read from window.FullCircle, which the
// installed FullCircle tracker script owns. Empty when consent hasn't been granted yet.
function updateFullCircleFields() {
  const visitorId = (window.FullCircle && window.FullCircle.getVisitorId && window.FullCircle.getVisitorId()) || '';
  const sessionId = (window.FullCircle && window.FullCircle.getSessionId && window.FullCircle.getSessionId()) || '';

  document.querySelectorAll('input[name="FullCircleVisitorID"]').forEach((elem) => {
    elem.value = visitorId;
  });
  document.querySelectorAll('input[name="FullCircleSessionID"]').forEach((elem) => {
    elem.value = sessionId;
  });
}

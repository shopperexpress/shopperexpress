(function () {
	'use strict';

	var NAV_SELECTORS = ['header nav', '.main-nav', '#main-navigation', '.site-navigation', '.navbar-nav'];

	function isNavElement(el) {
		for (var i = 0; i < NAV_SELECTORS.length; i++) {
			if (el.closest(NAV_SELECTORS[i])) {
				return true;
			}
		}
		return false;
	}

	function normalize(str) {
		return (str || '').replace(/\s+/g, ' ').trim().toLowerCase();
	}

	function getElementText(el) {
		var text = normalize(el.innerText);
		if (!text) text = normalize(el.getAttribute('aria-label'));
		if (!text) text = normalize(el.getAttribute('title'));
		if (!text) {
			var img = el.querySelector('img');
			if (img) text = normalize(img.getAttribute('alt'));
		}
		return text || '';
	}

	function getLinkUrl(el) {
		var anchor = el.tagName === 'A' ? el : el.closest('a');
		return anchor && anchor.href ? anchor.href : '';
	}

	function getElementType(el) {
		if (el.dataset && el.dataset.ascType) {
			return el.dataset.ascType;
		}
		var parent = el.closest('[data-asc-type]');
		if (parent) {
			return parent.dataset.ascType;
		}
		return 'banner';
	}

	function getActionResult(el, linkUrl) {
		if (
			el.dataset.toggle === 'modal' ||
			el.closest('[data-toggle="modal"]') ||
			el.classList.contains('wpforms-trigger') ||
			el.closest('.wpforms-trigger')
		) {
			return 'open';
		}

		if (
			el.classList.contains('intice-tool') ||
			el.closest('.intice-tool') ||
			el.dataset.intice ||
			el.closest('[data-intice]')
		) {
			return 'open';
		}

		if (linkUrl && linkUrl !== window.location.href && linkUrl !== '#' && !linkUrl.startsWith('javascript')) {
			return 'redirect';
		}

		return 'redirect';
	}

	function pushCtaEvent(el, actionResult) {
		var dl = window.asc_datalayer;
		if (!dl) return;

		var eventObj = {
			event:               'asc_cta_interaction',
			event_owner:         'intice',
			page_type:           dl.page_type || '',
			page_location:       window.location.href,
			element_text:        getElementText(el),
			element_type:        getElementType(el),
			event_action_result: actionResult,
			link_url:            getLinkUrl(el),
			error_code:          '',
			items:               dl.items || []
		};

		window.ascPublishEvent(eventObj);
	}

	function resolveCta(target) {
		var el = target.closest('a, button, [data-asc-type], .pill-button');
		if (!el) return null;
		if (isNavElement(el)) return null;
		return el;
	}

	document.addEventListener('click', function (e) {
		var cta = resolveCta(e.target);

		if (!cta) return;

		var linkUrl = getLinkUrl(cta);
		var actionResult = getActionResult(cta, linkUrl);
		pushCtaEvent(cta, actionResult);
	});

	document.addEventListener('submit', function (e) {
		var form = e.target;
		if (!form || form.tagName !== 'FORM') return;

		var submitBtn = form.querySelector('[type="submit"]') || form.querySelector('button');
		var el = submitBtn || form;

		if (!form.checkValidity || form.checkValidity()) {
			pushCtaEvent(el, 'next_step');
		} else {
			pushCtaEvent(el, 'error');
		}
	});

	// ── WPForms submission tracking ────────────────────────────────────────────

	// Fire to gtag and store in asc_datalayer.events alongside pageview events.
	function fireFormEvent(eventObject) {
		if (!eventObject || !eventObject.event) return;
		window.asc_datalayer = window.asc_datalayer || {};
		window.asc_datalayer.events = window.asc_datalayer.events || [];
		window.asc_datalayer.events.push(eventObject);
		if (typeof gtag !== 'function') return;
		var eventName = eventObject.event;
		var payload   = Object.assign({}, eventObject);
		delete payload.event;
		if (
			Array.isArray(window.asc_datalayer.measurement_ids) &&
			window.asc_datalayer.measurement_ids.length
		) {
			payload.send_to = window.asc_datalayer.measurement_ids;
		}
		gtag('event', eventName, payload);
	}

	console.log('[ASC] WPForms submission tracking: listeners registered');

	var firedSubmissions = {};
	var pendingFormTypes = {};

	var WPFORMS_SYSTEM_FIELDS = [
		'wpforms[id]', 'wpforms[author]', 'wpforms[post_id]',
		'wpforms[token]', 'wpforms[hash]', 'wpforms[entry_id]', 'wpforms[nonce]'
	];

	function getWpFormsFormType(formEl) {
		// Primary: WPForms adds CSS classes to the field wrapper div, not the input.
		// Selector covers div, li, or any wrapper with class asc_form_type.
		var byWrapper = formEl.querySelector('.asc_form_type input');
		console.log('[ASC] getWpFormsFormType → .asc_form_type input:', byWrapper, 'value:', byWrapper ? byWrapper.value : 'N/A');
		if (byWrapper && byWrapper.value) {
			console.log('[ASC] getWpFormsFormType → found by .asc_form_type wrapper', byWrapper.value);
			return byWrapper.value.trim().toLowerCase();
		}

		// Fallback: scan all wpforms hidden fields, skip WPForms system fields.
		var hidden = formEl.querySelectorAll('input.wpforms-field-hidden, input[name^="wpforms[fields]"][type="hidden"]');
		console.log('[ASC] getWpFormsFormType → hidden fields found:', hidden.length);
		for (var i = 0; i < hidden.length; i++) {
			if (WPFORMS_SYSTEM_FIELDS.indexOf(hidden[i].name || '') !== -1) continue;
			console.log('[ASC] getWpFormsFormType → hidden field[' + i + ']:', hidden[i].name, '=', hidden[i].value);
			if (hidden[i].value) {
				console.log('[ASC] getWpFormsFormType → found by hidden field scan', hidden[i].value);
				return hidden[i].value.trim().toLowerCase();
			}
		}

		console.warn('[ASC] getWpFormsFormType → not found, returning "unknown". Check that the hidden field li has CSS class "asc_form_type".');
		return 'unknown';
	}

	function getWpFormsFormId(formEl) {
		var id = formEl.getAttribute('data-formid') || formEl.getAttribute('data-form-id');
		if (id) return 'wpforms_' + id;
		var idInput = formEl.querySelector('input[name="wpforms[id]"]');
		if (idInput && idInput.value) return 'wpforms_' + idInput.value;
		return 'wpforms_unknown';
	}

	// Cache formType on submit — WPForms replaces the form DOM with a success message
	// before wpformsAjaxSubmitSuccess fires, so hidden fields are gone by then.
	document.addEventListener('submit', function (e) {
		var formEl = e.target;
		if (!formEl || !formEl.querySelector('input[name="wpforms[id]"]')) return;
		var formId   = getWpFormsFormId(formEl);
		var formType = getWpFormsFormType(formEl);
		pendingFormTypes[formId] = formType;
		console.log('[ASC] Step 1 – submit intercepted ✓', { formId: formId, formType: formType });
	}, true);

	function pushFormSubmissionEvents(formEl, fallbackFormId) {
		var formId = (formEl && getWpFormsFormId(formEl)) || ('wpforms_' + fallbackFormId) || 'wpforms_unknown';

		if (firedSubmissions[formId]) {
			console.log('[ASC] asc_form_submission skipped – duplicate guard', { formId: formId });
			return;
		}
		firedSubmissions[formId] = true;
		setTimeout(function () { delete firedSubmissions[formId]; }, 3000);

		var dl = window.asc_datalayer;
		if (!dl) {
			console.warn('[ASC] asc_form_submission NOT fired – window.asc_datalayer is missing');
			return;
		}

		// Use cached formType first (form DOM may already be gone after AJAX replace).
		var formType = pendingFormTypes[formId] || (formEl && getWpFormsFormType(formEl)) || 'unknown';
		delete pendingFormTypes[formId];

		var payload = {
			event_owner:   'intice',
			page_type:     dl.page_type || '',
			page_location: window.location.href,
			form_id:       formId,
			form_type:     formType,
			items:         dl.items || []
		};

		console.log('[ASC] Step 2 – asc_form_submission firing ✓', payload);
		console.log('[ASC] Step 3 – asc_form_submission_' + formType + ' firing ✓');

		fireFormEvent(Object.assign({}, payload, { event: 'asc_form_submission' }));
		fireFormEvent(Object.assign({}, payload, { event: 'asc_form_submission_' + formType }));
	}

	// Path A: native CustomEvent (WPForms 1.8+)
	document.addEventListener('wpformsAjaxSubmitSuccess', function (e) {
		console.log('[ASC] wpformsAjaxSubmitSuccess (native event) ✓', e.detail);
		var formEl = e.detail && e.detail.form ? e.detail.form : null;
		var formId = null;
		if (!formEl) {
			formId = e.detail && (e.detail.formId || (e.detail.data && e.detail.data.form_id));
			if (formId) formEl = document.querySelector('[data-formid="' + formId + '"]');
		}
		pushFormSubmissionEvents(formEl, formId);
	});

	// Path B: jQuery event bus (WPForms legacy)
	(function bindJqListener() {
		if (window.jQuery) {
			jQuery(document).on('wpformsAjaxSubmitSuccess', function (_e, response) {
				console.log('[ASC] wpformsAjaxSubmitSuccess (jQuery event) ✓', response);
				var formId = response && response.data && response.data.form_id;

				// form_id missing in response — try to recover from pendingFormTypes cache
				if (!formId) {
					var keys = Object.keys(pendingFormTypes);
					if (keys.length) formId = keys[keys.length - 1].replace('wpforms_', '');
					console.warn('[ASC] response.data.form_id missing, recovered formId:', formId);
				}

				if (!formId) {
					console.warn('[ASC] asc_form_submission NOT fired – could not determine form_id');
					return;
				}

				var formEl = document.querySelector('[data-formid="' + formId + '"]');
				pushFormSubmissionEvents(formEl, formId);
			});
		} else if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', bindJqListener);
		}
	}());

	// Path C: redirect confirmation (?wpforms_form_id=123&wpforms_return=1)
	(function () {
		var params = new URLSearchParams(window.location.search);
		var redirectFormId = params.get('wpforms_form_id');
		if (!redirectFormId) return;

		console.log('[ASC] Redirect confirmation page detected ✓', { redirectFormId: redirectFormId });

		var dl = window.asc_datalayer;
		if (!dl) {
			console.warn('[ASC] asc_form_submission (redirect) NOT fired – window.asc_datalayer missing');
			return;
		}

		var formEl   = document.querySelector('[data-formid="' + redirectFormId + '"]');
		var formType = formEl ? getWpFormsFormType(formEl) : (params.get('asc_form_type') || 'unknown');
		var formId   = 'wpforms_' + redirectFormId;
		var payload  = {
			event_owner:   'intice',
			page_type:     dl.page_type || '',
			page_location: window.location.href,
			form_id:       formId,
			form_type:     formType,
			items:         dl.items || []
		};

		console.log('[ASC] asc_form_submission (redirect) firing ✓', payload);
		fireFormEvent(Object.assign({}, payload, { event: 'asc_form_submission' }));
		fireFormEvent(Object.assign({}, payload, { event: 'asc_form_submission_' + formType }));
	}());

	// ── Click-to-call tracking ─────────────────────────────────────────────────

	function normalizePhone(phone) {
		return (phone || '').replace(/^tel:/i, '').replace(/[^\d+]/g, '');
	}

	function resolveDepartment(normalized) {
		var lookup = window.asc_phone_lookup;
		if (lookup && typeof lookup === 'object' && lookup[normalized]) {
			return lookup[normalized];
		}
		return 'unknown';
	}

	document.addEventListener('click', function (e) {
		var anchor = e.target.closest('a');
		if (!anchor) return;

		var href = anchor.getAttribute('href') || '';
		if (!/^tel:/i.test(href)) return;

		var dl = window.asc_datalayer;
		if (!dl) return;

		var normalized  = normalizePhone(href);
		var department  = resolveDepartment(normalized);
		var affiliation = (dl.affiliation && dl.affiliation !== '') ? dl.affiliation : 'intice';

		window.ascPublishEvent({
			event:              'asc_click_to_call',
			event_owner:        'intice',
			page_type:          dl.page_type || '',
			page_location:      window.location.href,
			comm_phone_number:  normalized,
			department:         department,
			affiliation:        affiliation,
			error_code:         '',
			items:              dl.items || []
		});
	});
}());

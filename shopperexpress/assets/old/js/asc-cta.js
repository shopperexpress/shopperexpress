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

	var firedSubmissions = {};

	function getWpFormsFormType(formEl) {
		var field = formEl.querySelector('input[name="wpforms[fields][asc_form_type]"], input.wpforms-field-hidden[value]');

		if (!field) {
			// ACF-style hidden field: WPForms renders hidden fields with a generated id
			// but the name follows wpforms[fields][<field_id>] — so we look by a
			// data attribute WPForms adds to identify the field by its label slug.
			field = formEl.querySelector('[data-field-id]');
		}

		// Most reliable: scan all hidden inputs for one whose name contains asc_form_type
		var hidden = formEl.querySelectorAll('input[type="hidden"]');
		for (var i = 0; i < hidden.length; i++) {
			if (hidden[i].name && hidden[i].name.indexOf('asc_form_type') !== -1) {
				return (hidden[i].value || 'unknown').trim().toLowerCase();
			}
		}

		return 'unknown';
	}

	function getWpFormsFormId(formEl) {
		// WPForms adds data-formid on the form element
		var id = formEl.getAttribute('data-formid') || formEl.getAttribute('data-form-id');
		if (id) return 'wpforms_' + id;

		// Fallback: hidden input wpforms[id]
		var idInput = formEl.querySelector('input[name="wpforms[id]"]');
		if (idInput && idInput.value) return 'wpforms_' + idInput.value;

		return 'wpforms_unknown';
	}

	function pushFormSubmissionEvents(formEl) {
		var formId = getWpFormsFormId(formEl);

		// Prevent duplicate firing per form submission
		if (firedSubmissions[formId]) return;
		firedSubmissions[formId] = true;

		// Reset after short delay to allow future submissions on same page
		setTimeout(function () { delete firedSubmissions[formId]; }, 3000);

		var dl = window.asc_datalayer;
		if (!dl) return;

		var formType = getWpFormsFormType(formEl);

		var payload = {
			event_owner:   'intice',
			page_type:     dl.page_type || '',
			page_location: window.location.href,
			form_id:       formId,
			form_type:     formType,
			error_code:    '',
			items:         dl.items || []
		};

		window.ascPublishEvent(Object.assign({}, payload, { event: 'asc_form_submission' }));
		window.ascPublishEvent(Object.assign({}, payload, { event: 'asc_form_submission_' + formType }));
	}

	// WPForms AJAX success — fires after every successful AJAX submission
	window.addEventListener('load', function () {
		if (typeof window.wpforms === 'undefined') return;

		document.addEventListener('wpformsAjaxSubmitSuccess', function (e) {
			var formEl = e.detail && e.detail.form ? e.detail.form : null;
			if (!formEl) {
				// Older WPForms versions pass formId in e.detail
				var formId = e.detail && e.detail.formId;
				if (formId) {
					formEl = document.querySelector('#wpforms-' + formId + ', [data-formid="' + formId + '"]');
				}
			}
			if (formEl) pushFormSubmissionEvents(formEl);
		});

		// WPForms also triggers on the jQuery event bus — cover both paths
		if (window.jQuery) {
			jQuery(document).on('wpformsAjaxSubmitSuccess', function (_e, response) {
				var formId = response && response.data && response.data.form_id;
				if (!formId) return;
				var formEl = document.querySelector('#wpforms-form-' + formId + ', [data-formid="' + formId + '"]');
				if (formEl) pushFormSubmissionEvents(formEl);
			});
		}
	});

	// Redirect confirmations: WPForms appends ?wpforms_form_id=<id>&wpforms_return=1 to redirect URL.
	// On page load, if those params are present, fire the events using the page's hidden form remnant
	// or fall back to just the form ID from the URL.
	(function () {
		var params = new URLSearchParams(window.location.search);
		var redirectFormId = params.get('wpforms_form_id') || params.get('wpforms[id]');
		if (!redirectFormId) return;

		var dl = window.asc_datalayer;
		if (!dl) return;

		// Try to find a rendered form on the confirmation page; otherwise use URL param only
		var formEl = document.querySelector('[data-formid="' + redirectFormId + '"]');
		var formType = formEl ? getWpFormsFormType(formEl) : (params.get('asc_form_type') || 'unknown');
		var formId   = 'wpforms_' + redirectFormId;

		var payload = {
			event_owner:   'intice',
			page_type:     dl.page_type || '',
			page_location: window.location.href,
			form_id:       formId,
			form_type:     formType,
			error_code:    '',
			items:         dl.items || []
		};

		window.ascPublishEvent(Object.assign({}, payload, { event: 'asc_form_submission' }));
		window.ascPublishEvent(Object.assign({}, payload, { event: 'asc_form_submission_' + formType }));
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

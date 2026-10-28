(function () {
	'use strict';

	// ── ASC → OpenAI Ads translation layer ──────────────────────────────────
	// Registers as a destination on the shared ASC event dispatcher
	// (asc-publish.js). Never watches forms/DOM directly — the ASC Events
	// Emitter remains the single source of truth. GA4/internal reporting keep
	// receiving the original ASC event names/metadata unchanged.

	var EVENT_MAP = {
		asc_form_submission_sales:      { event: 'lead_created', dataType: 'customer_action' },
		asc_form_submission_trade:      { event: 'lead_created', dataType: 'customer_action' },
		asc_form_submission_credit:     { event: 'lead_created', dataType: 'customer_action' },
		asc_form_submission_service:    { event: 'appointment_scheduled', dataType: 'customer_action' },
		asc_form_submission_test_drive: { event: 'appointment_scheduled', dataType: 'customer_action' },
		asc_item_pageview:              { event: 'contents_viewed', dataType: 'contents' },
		asc_pageview:                   { event: 'page_viewed', dataType: 'contents' }
	};

	function getConfig() {
		return window.ascOpenAiAdsConfig || null;
	}

	// No consent-management platform is wired up yet. A CMP should set
	// window.shopperexpressConsent = false to block/revoke measurement, and
	// back it with the shopperexpress_measurement_consent_granted PHP filter
	// so the pixel bootstrap script itself is never output server-side either.
	function consentGranted() {
		return window.shopperexpressConsent !== false;
	}

	function maskPixelId(id) {
		if (!id) return '';
		if (id.length <= 4) return new Array(id.length + 1).join('*');
		return id.slice(0, 2) + new Array(id.length - 3).join('*') + id.slice(-2);
	}

	function log(config, message, data) {
		if (!config || !config.debug) return;
		if (window.console && console.log) {
			console.log('[ASC→OpenAI Ads]', message, data !== undefined ? data : '');
		}
	}

	function uuid() {
		if (window.crypto && typeof window.crypto.randomUUID === 'function') {
			return window.crypto.randomUUID();
		}
		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
			var r = (Math.random() * 16) | 0;
			var v = c === 'x' ? r : ((r & 0x3) | 0x8);
			return v.toString(16);
		});
	}

	// Reuse the shared event ID stamped server-side (ASC pageview / form
	// submission events) so the browser pixel and the future Conversions API
	// call can be deduplicated by OpenAI. Only client-only ASC events (not in
	// the initial mapping) fall back to a locally generated ID.
	function resolveEventId(eventObject, config) {
		if (eventObject.event_id) return eventObject.event_id;
		var dealerId = (config && config.dealerId) || 'unknown';
		return 'asc_' + dealerId + '_' + eventObject.event + '_' + uuid();
	}

	// Per developers.openai.com/ads/supported-events, each contents[] item
	// only accepts: id, name, content_type, quantity, amount, currency.
	function buildContentsArray(eventObject) {
		var items = eventObject.items || [];

		return items
			.map(function (item) {
				var id = item.item_id || item.item_number || '';
				var name = [item.item_year, item.item_make, item.item_model, item.item_variant]
					.filter(Boolean)
					.join(' ');

				if (!id && !name) return null;

				var content = {};
				if (id) content.id = id;
				if (name) content.name = name;
				if (item.item_type) content.content_type = item.item_type;
				content.quantity = 1;

				return content;
			})
			.filter(Boolean);
	}

	// Only send fields OpenAI documents — unknown fields are rejected.
	function buildEventData(mapping, eventObject) {
		var data = { type: mapping.dataType };

		if (mapping.dataType === 'contents') {
			var contents = buildContentsArray(eventObject);
			if (contents.length) data.contents = contents;
		}

		return data;
	}

	// Best-effort capture of the OpenAI first-party attribution cookie for a
	// future hybrid browser/server (Conversions API) phase. Not sent to the
	// browser pixel itself — only documented fields belong in that payload.
	function captureObref() {
		var match = document.cookie.match(/(?:^|; )__obref=([^;]*)/);
		if (match) {
			window.__ascObref = decodeURIComponent(match[1]);
		}
	}

	function handleAscEvent(eventObject) {
		var config = getConfig();
		if (!config || !config.enabled) return;
		if (typeof window.oaiq !== 'function') return;

		if (!consentGranted()) {
			log(config, 'skipped — consent not granted', eventObject.event);
			return;
		}

		var mapping = EVENT_MAP[eventObject.event];
		if (!mapping) return;

		var eventId = resolveEventId(eventObject, config);
		var eventData = buildEventData(mapping, eventObject);
		// Per developers.openai.com/ads/measurement-pixel: oaiq("measure", eventName, eventData, options).
		// The shared event ID goes in the options object as event_id (snake_case), not inside eventData.
		var options = { event_id: eventId };

		log(config, 'asc:' + eventObject.event + ' → openai:' + mapping.event, {
			eventId: eventId,
			pixelId: maskPixelId(config.pixelId),
			sourceUrl: window.location.href
		});

		try {
			window.oaiq('measure', mapping.event, eventData, options);
			log(config, 'browser event accepted', mapping.event);
		} catch (err) {
			log(config, 'browser event failed', err);
		}
	}

	captureObref();

	if (typeof window.ascRegisterDestination === 'function') {
		window.ascRegisterDestination('openai_ads', handleAscEvent);
	}
}());

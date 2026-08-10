(function () {
	'use strict';

	// ── ASC destination registry ────────────────────────────────────────────
	// A reusable translation layer: any destination (GA4, Google Ads, Meta,
	// OpenAI Ads, ...) registers a handler here instead of hooking into forms
	// or emitters individually. window.ascDispatchEvent() fans an ASC event
	// object out to every registered destination.

	var destinations = {};

	function ascRegisterDestination(name, handler) {
		if (!name || typeof handler !== 'function') return;
		destinations[name] = handler;
	}

	function ascDispatchEvent(eventObject) {
		if (!eventObject || !eventObject.event || eventObject.__ascDispatched) return;
		eventObject.__ascDispatched = true;

		Object.keys(destinations).forEach(function (name) {
			try {
				destinations[name](eventObject);
			} catch (err) {
				if (window.console && console.error) {
					console.error('[ASC] destination "' + name + '" failed', err);
				}
			}
		});
	}

	function ascBuildGtagPayload(eventObject) {
		var payload = Object.assign({}, eventObject);

		delete payload.event;
		delete payload.__ascDispatched;

		Object.keys(payload).forEach(function (key) {
			if (payload[key] === undefined) {
				delete payload[key];
			}
		});

		if (
			window.asc_datalayer &&
			Array.isArray(window.asc_datalayer.measurement_ids) &&
			window.asc_datalayer.measurement_ids.length
		) {
			payload.send_to = window.asc_datalayer.measurement_ids;
		}

		return payload;
	}

	// Built-in GA4 destination — preserves the original ascPublishEvent behavior.
	ascRegisterDestination('ga4', function (eventObject) {
		if (typeof gtag !== 'function') return;
		gtag('event', eventObject.event, ascBuildGtagPayload(eventObject));
	});

	window.ascRegisterDestination = ascRegisterDestination;
	window.ascDispatchEvent = ascDispatchEvent;

	window.ascPublishEvent = function (eventObject) {
		if (!eventObject || !eventObject.event) return;

		window.asc_datalayer = window.asc_datalayer || {};
		window.asc_datalayer.events = window.asc_datalayer.events || [];
		window.asc_datalayer.events.push(eventObject);

		ascDispatchEvent(eventObject);
	};
}());

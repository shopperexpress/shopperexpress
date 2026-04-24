(function () {
	'use strict';

	function ascBuildGtagPayload(eventObject) {
		var payload = Object.assign({}, eventObject);

		delete payload.event;

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

	window.ascPublishEvent = function (eventObject) {
		if (!eventObject || !eventObject.event) return;

		window.asc_datalayer = window.asc_datalayer || {};
		window.asc_datalayer.events = window.asc_datalayer.events || [];
		window.asc_datalayer.events.push(eventObject);

		if (typeof gtag === 'function') {
			var eventName = eventObject.event;
			var payload = ascBuildGtagPayload(eventObject);
			gtag('event', eventName, payload);
		}
	};
}());

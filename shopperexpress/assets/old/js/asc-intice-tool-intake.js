(function () {
	'use strict';

	var approvedAscEventHints = [
		'asc_retail_process',
		'asc_cta_interaction',
		'asc_form_engagement',
		'asc_form_submission',
		'asc_special_offer',
		'asc_media_interaction'
	];

	var approvedToolNames = [
		'leadmaker',
		'trademaker',
		'dealmaker',
		'loanmaker'
	];

	var approvedInticeOrigins = [
		'https://app.intice.com',
		'https://tools.intice.com',
		'https://cdn.intice.com'
	];

	function isValidOrigin(origin) {
		if (!origin) return false;
		return approvedInticeOrigins.indexOf(origin) !== -1;
	}

	function isValidInticeToolEvent(data) {
		return data &&
			data.source === 'intice_tool' &&
			data.schema_version === '1.0' &&
			typeof data.tool_name === 'string' &&
			typeof data.asc_event_hint === 'string' &&
			approvedToolNames.indexOf(data.tool_name) !== -1 &&
			approvedAscEventHints.indexOf(data.asc_event_hint) !== -1;
	}

	function normalizeToolEventToAsc(data) {
		return {
			event:               data.asc_event_hint,
			source:              'intice_tool',
			schema_version:      data.schema_version,
			emitted_at:          data.emitted_at || new Date().toISOString(),

			tool_name:           data.tool_name,
			tool_instance_id:    data.tool_instance_id || '',
			tool_session_id:     data.tool_session_id || '',

			flow_name:           data.flow_name || '',
			flow_stage:          data.flow_stage || '',
			flow_outcome:        data.flow_outcome || '',

			tool_page:           data.tool_page || '',
			tool_page_name:      data.tool_page_name || '',
			tool_step:           data.tool_step || '',

			element_text:        data.element_text || '',
			element_type:        data.element_type || '',
			element_title:       data.element_title || '',
			event_action:        data.event_action || '',
			event_action_result: data.event_action_result || '',

			media_type:          data.media_type || '',
			creative_name:       data.creative_name || '',

			payload:             data.payload || {}
		};
	}

	function enrichToolAscEvent(ascEvent) {
		var base = window.asc_datalayer || {};

		return Object.assign({}, base, ascEvent, {
			department: ascEvent.department || base.department || 'sales',
			page_type:  ascEvent.page_type  || base.page_type  || '',
			items:      ascEvent.items       || base.items      || []
		});
	}

	function saveToInticeVueEventStore(eventData) {
		var key = 'inticevue_events';
		var existing = [];

		try {
			existing = JSON.parse(localStorage.getItem(key)) || [];
		} catch (e) {
			existing = [];
		}

		existing.push({
			event:               eventData.event,
			tool_name:           eventData.tool_name           || '',
			flow_name:           eventData.flow_name           || '',
			flow_stage:          eventData.flow_stage          || '',
			flow_outcome:        eventData.flow_outcome        || '',
			event_action_result: eventData.event_action_result || '',
			page_type:           eventData.page_type           || '',
			department:          eventData.department          || '',
			timestamp:           new Date().toISOString()
		});

		try {
			localStorage.setItem(key, JSON.stringify(existing.slice(-100)));
		} catch (e) {}
	}

	function handleInticeToolMessage(event) {
		var data = event.data;

		if (!isValidInticeToolEvent(data)) return;

		// Origin check: warn during early testing, block in production when origins are finalized.
		if (event.origin && !isValidOrigin(event.origin)) {
			// Allow window.postMessage(…, '*') calls from the same page (origin === '') for QA testing.
			if (event.origin !== '') {
				return;
			}
		}

		var ascEvent     = normalizeToolEventToAsc(data);
		var enrichedEvent = enrichToolAscEvent(ascEvent);

		// Delegate to the existing website dispatcher so events share the same GA4 pathway.
		if (typeof window.ascPublishEvent === 'function') {
			window.ascPublishEvent(enrichedEvent);
		}

		saveToInticeVueEventStore(enrichedEvent);
	}

	window.addEventListener('message', handleInticeToolMessage);
}());

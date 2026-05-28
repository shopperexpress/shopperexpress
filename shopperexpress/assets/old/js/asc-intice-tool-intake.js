(function () {
	'use strict';

	console.log('🚀 ASC INTICE INTAKE LAYER INIT');

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
		'https://tools.inticeinc.com',
		'https://tools.inticeinc.net',
		'https://cdn.intice.com',
		'https://my-dealmaker.com',
		'https://gettradevalue.com',
		'https://my-loanmaker.com'
	];

	function isValidInticeToolEvent(data) {
		return data &&
			data.source === 'intice_tool' &&
			data.schema_version === '1.0' &&
			typeof data.tool_name === 'string' &&
			typeof data.asc_event_hint === 'string' &&
			approvedToolNames.indexOf(data.tool_name) !== -1 &&
			approvedAscEventHints.indexOf(data.asc_event_hint) !== -1;
	}

	function isValidOrigin(origin) {
		if (!origin) return false;
		return approvedInticeOrigins.indexOf(origin) !== -1;
	}

	function normalizeToolEventToAsc(data) {
		return {
			event: data.asc_event_hint,
			source: 'intice_tool',
			schema_version: data.schema_version,
			emitted_at: data.emitted_at || new Date().toISOString(),

			tool_name: data.tool_name,
			tool_instance_id: data.tool_instance_id || '',
			tool_session_id: data.tool_session_id || '',

			flow_name: data.flow_name || '',
			flow_stage: data.flow_stage || '',
			flow_outcome: data.flow_outcome || '',

			tool_page: data.tool_page || '',
			tool_page_name: data.tool_page_name || '',
			tool_step: data.tool_step || '',

			element_text: data.element_text || '',
			element_type: data.element_type || '',
			element_title: data.element_title || '',
			event_action: data.event_action || '',
			event_action_result: data.event_action_result || '',

			media_type: data.media_type || '',
			creative_name: data.creative_name || '',

			payload: data.payload || {}
		};
	}

	function enrichToolAscEvent(ascEvent) {
		var base = (window.asc_datalayer && window.asc_datalayer[0]) || {};

		// prevent recursive structures
		var safeBase = Object.assign({}, base);

		delete safeBase.events;

		return Object.assign({}, safeBase, ascEvent, {
			department: ascEvent.department || safeBase.department || 'sales',
			page_type: ascEvent.page_type || safeBase.page_type || '',
			items: ascEvent.items || safeBase.items || []
		});
	}

	function dispatchAscEvent(enrichedEvent) {
		console.log('📤 ASC EVENT PUSH:', enrichedEvent);

		window.asc_datalayer = window.asc_datalayer || [];
		window.asc_datalayer[0] = window.asc_datalayer[0] || {};
		window.asc_datalayer[0].events = window.asc_datalayer[0].events || [];

		window.asc_datalayer[0].events.push(enrichedEvent);

		if (typeof gtag === 'function') {
			console.log('📡 SENT TO GA4');
			gtag('event', enrichedEvent.event, enrichedEvent);
		}

		if (typeof window.ascPublishEvent === 'function') {
			window.ascPublishEvent(enrichedEvent);
		}
	}

	function saveToInticeVueEventStore(eventData) {
		var key = 'inticevue_events';
		var existing = [];

		try {
			existing = JSON.parse(localStorage.getItem(key)) || [];
		} catch (e) {}

		existing.push({
			event: eventData.event,
			tool_name: eventData.tool_name || '',
			flow_name: eventData.flow_name || '',
			flow_stage: eventData.flow_stage || '',
			flow_outcome: eventData.flow_outcome || '',
			event_action_result: eventData.event_action_result || '',
			page_type: eventData.page_type || '',
			department: eventData.department || '',
			timestamp: new Date().toISOString()
		});

		localStorage.setItem(key, JSON.stringify(existing.slice(-100)));

		console.log('💾 STORED IN inticevue_events');
	}

	function processToolEvent(data) {
		console.log('🟡 STANDARD TOOL EVENT DETECTED');

		var ascEvent = normalizeToolEventToAsc(data);

		console.log('🔧 NORMALIZED TOOL EVENT:', ascEvent);

		var enrichedEvent = enrichToolAscEvent(ascEvent);

		console.log('🚀 ENRICHED EVENT:', enrichedEvent);

		dispatchAscEvent(enrichedEvent);
		saveToInticeVueEventStore(enrichedEvent);
	}

	function processCustomerInfoEvent(params) {
		var ascEvent = {
			event: 'asc_tool_customer_info_capture',
			source: 'intice_tool',
			tool_name: 'customer_info',
			event_action_result: 'capture',
			element_text: 'customer_info_form',
			element_type: 'lead_capture',
			flow_stage: 'lead_capture',
			payload: {
				firstName: params.firstName || '',
				lastName: params.lastName || '',
				email: params.email || '',
				phoneNo: params.phoneNo || ''
			}
		};

		console.log('🚀 CUSTOMER INFO NORMALIZED:', ascEvent);

		var enrichedEvent = enrichToolAscEvent(ascEvent);

		console.log('🚀 ENRICHED EVENT:', enrichedEvent);

		dispatchAscEvent(enrichedEvent);
		saveToInticeVueEventStore(enrichedEvent);
	}

	function handleInticeToolMessage(event) {
		var data = event.data;

		if (!data || typeof data !== 'object') return;

		console.log('📩 INTICE MESSAGE RECEIVED:', data);

		if (event.origin) {
			console.log('🌐 ORIGIN:', event.origin, {
				valid: isValidOrigin(event.origin)
			});
		}

		if (data.source === 'intice_tool') {
			if (!isValidInticeToolEvent(data)) {
				console.log('⛔ INVALID TOOL EVENT', { tool_name: data.tool_name });
				return;
			}

			processToolEvent(data);
			return;
		}

		if (data.message === 'customerInfo') {
			console.log('🟢 CUSTOMER INFO EVENT DETECTED');
			processCustomerInfoEvent(data.params || {});
			return;
		}

	}

	window.addEventListener('message', handleInticeToolMessage);

}());
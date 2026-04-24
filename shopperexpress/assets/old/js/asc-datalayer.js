(function () {
	'use strict';

	function pushAscEvents() {
		if ( !window.asc_datalayer || !Array.isArray( window.asc_datalayer.events ) ) {
			return;
		}

		if ( window.__asc_events_pushed ) {
			return;
		}
		window.__asc_events_pushed = true;

		var loc = window.location.href;

		window.asc_datalayer.events.forEach( function ( event ) {
			if ( !event.page_location ) {
				event.page_location = loc;
			}
			if ( typeof gtag === 'function' ) {
				var eventName = event.event;
				var payload = Object.assign( {}, event );
				delete payload.event;
				if (
					Array.isArray( window.asc_datalayer.measurement_ids ) &&
					window.asc_datalayer.measurement_ids.length
				) {
					payload.send_to = window.asc_datalayer.measurement_ids;
				}
				gtag( 'event', eventName, payload );
			}
		} );
	}

	if ( document.readyState === 'complete' ) {
		pushAscEvents();
	} else {
		window.addEventListener( 'load', pushAscEvents );
	}
}());


(function ($) {
	if (window.__asc_wpforms_listener) return;
	window.__asc_wpforms_listener = true;

	$(document).ajaxSuccess(function (event, xhr, settings) {
		try {
			const response = JSON.parse(xhr.responseText);

			if (response?.data?.asc_event) {
				window.ascPublishEvent(response.data.asc_event);
				console.log('ASC Lead pushed:', response.data.asc_event);
			}
		} catch (e) {}
	});
})(jQuery);

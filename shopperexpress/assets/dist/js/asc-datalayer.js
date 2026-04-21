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

		window.dataLayer = window.dataLayer || [];

		var loc = window.location.href;

		window.asc_datalayer.events.forEach( function ( event ) {
			var e = Object.assign( {}, event );
			if ( !e.page_location ) {
				e.page_location = loc;
			}
			window.dataLayer.push( e );
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
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push(response.data.asc_event);

                console.log('ASC Lead pushed:', response.data.asc_event);
            }
        } catch (e) {}
    });
})(jQuery);
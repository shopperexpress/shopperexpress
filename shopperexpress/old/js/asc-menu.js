(function () {
	'use strict';

	var HEADER_SELECTORS = [
		'header nav',
		'.main-nav',
		'#main-navigation',
		'.site-navigation',
		'.navbar-nav',
		'.navbar-collapse',
		'.nav-primary',
	];

	var MOBILE_SELECTORS = [
		'.mobile-nav',
		'.mobile-menu',
		'.nav-mobile',
		'#mobile-navigation',
		'.hamburger-menu',
		'.offcanvas-nav',
		'.offcanvas-menu',
	];

	var FOOTER_SELECTORS = [
		'footer nav',
		'.footer-nav',
		'.footer-navigation',
		'.site-footer nav',
	];

	var CTA_PATTERN = /\b(btn|button|cta|pill|pill-button)\b/i;

	function matchesAny(el, selectors) {
		for (var i = 0; i < selectors.length; i++) {
			if (el.closest(selectors[i])) return true;
		}
		return false;
	}

	function getElementType(el) {
		if (matchesAny(el, FOOTER_SELECTORS)) return 'footer';
		if (matchesAny(el, MOBILE_SELECTORS)) return 'menu';

		var parent = el.closest('[class]');
		if (parent) {
			var cls = parent.className;
			if (/dropdown|sub-?menu/i.test(cls)) return 'navigation';
		}

		return 'header';
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
		if (!anchor || !anchor.href) return '';
		try {
			return anchor.href.toLowerCase();
		} catch (e) {
			return anchor.href || '';
		}
	}

	function isCtaElement(el) {
		if (CTA_PATTERN.test(el.className)) return true;
		var parent = el.closest('[class]');
		return parent ? CTA_PATTERN.test(parent.className) : false;
	}

	function resolveMenuAnchor(target) {
		var el = target.closest('a');
		if (!el) return null;

		var inHeader = matchesAny(el, HEADER_SELECTORS);
		var inMobile = matchesAny(el, MOBILE_SELECTORS);
		var inFooter = matchesAny(el, FOOTER_SELECTORS);

		if (!inHeader && !inMobile && !inFooter) return null;
		if (isCtaElement(el)) return null;

		return el;
	}

	function ascTrackMenuInteraction(element) {
		var dl = window.asc_datalayer;
		if (!dl) return;

		var eventObj = {
			event:         'asc_menu_interaction',
			event_owner:   'intice',
			page_type:     dl.page_type || '',
			page_location: window.location.href,
			element_text:  getElementText(element),
			element_type:  getElementType(element),
			link_url:      getLinkUrl(element),
			items:         dl.items || [],
		};

		window.ascPublishEvent(eventObj);
	}

	document.addEventListener('click', function (e) {
		var el = resolveMenuAnchor(e.target);
		if (!el) return;
		ascTrackMenuInteraction(el);
	});
}());

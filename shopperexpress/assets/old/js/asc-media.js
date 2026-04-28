(function () {
	'use strict';

	// ── Core ─────────────────────────────────────────────────────────────────

	function normalize(str) {
		return (str || '').replace(/\s+/g, ' ').trim().toLowerCase();
	}

	function ascTrackMediaInteraction(element, payload) {
		var dl = window.asc_datalayer;
		if (!dl) return;

		window.ascPublishEvent({
			event:               'asc_media_interaction',
			event_owner:         'intice',
			page_type:           dl.page_type || '',
			page_location:       window.location.href,
			media_type:          payload.media_type          || '',
			event_action_result: payload.event_action_result || '',
			link_url:            payload.link_url            || '',
			creative_name:       payload.creative_name       || '',
			items:               dl.items                    || [],
		});
	}

	function dataAttr(el, name) {
		if (!el) return '';
		var val = el.getAttribute('data-asc-' + name);
		if (val) return val;
		var parent = el.closest('[data-asc-' + name + ']');
		return parent ? (parent.getAttribute('data-asc-' + name) || '') : '';
	}

	function linkUrl(el) {
		var a = el && (el.tagName === 'A' ? el : el.closest('a'));
		return (a && a.href) ? a.href.toLowerCase() : '';
	}

	function filenameSlug(url) {
		return normalize((url || '').split('/').pop().replace(/\?.*$/, '').replace(/\.[^.]+$/, ''));
	}

	// ── 360 / Spin buttons ────────────────────────────────────────────────────

	var SPIN_TYPE_MAP = {
		'spin-evo':       { media_type: '360_platform', event_action_result: 'spin',  creative_name: '360_spin' },
		'spin-evox':      { media_type: '360_platform', event_action_result: 'spin',  creative_name: '360_spin' },
		'spin-impel':     { media_type: '360_platform', event_action_result: 'spin',  creative_name: '360_spin' },
		'spin-autoexact': { media_type: '360_platform', event_action_result: 'spin',  creative_name: '360_spin' },
		'spin-lesa':      { media_type: '360_platform', event_action_result: 'spin',  creative_name: '360_spin' },
		'spin-autoport':  { media_type: '360_platform', event_action_result: 'spin',  creative_name: '360_spin' },
		'spin-video':     { media_type: 'video',         event_action_result: 'play',  creative_name: 'walkaround_video' },
	};

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.btn-spin');
		if (!btn) return;

		var defaults = { media_type: '360_platform', event_action_result: 'spin', creative_name: '360_spin' };

		for (var cls in SPIN_TYPE_MAP) {
			if (btn.classList.contains(cls)) {
				defaults = SPIN_TYPE_MAP[cls];
				break;
			}
		}

		ascTrackMediaInteraction(btn, {
			media_type:          dataAttr(btn, 'media-type')   || defaults.media_type,
			event_action_result: dataAttr(btn, 'media-action') || defaults.event_action_result,
			link_url:            linkUrl(btn),
			creative_name:       dataAttr(btn, 'creative-name') || defaults.creative_name,
		});
	});

	// ── FancyBox / Lightbox clicks ────────────────────────────────────────────

	document.addEventListener('click', function (e) {
		var trigger = e.target.closest('[data-fancybox]');
		if (!trigger) return;
		if (trigger.classList.contains('btn-spin')) return;

		var mediaType    = dataAttr(trigger, 'media-type');
		var actionResult = dataAttr(trigger, 'media-action');
		var creative     = dataAttr(trigger, 'creative-name');

		if (!mediaType) {
			var fbType = trigger.getAttribute('data-type') || '';
			if (fbType === 'iframe' || fbType === 'video') {
				mediaType    = 'video';
				actionResult = actionResult || 'open';
			} else {
				var group = trigger.getAttribute('data-fancybox') || '';
				mediaType    = (group.indexOf('gallery') !== -1 || group.indexOf('grid') !== -1) ? 'gallery' : 'image';
				actionResult = actionResult || 'popup';
			}
		}

		if (!creative) {
			var img = trigger.querySelector('img');
			if (img) creative = normalize(img.getAttribute('alt') || '');
			if (!creative) {
				var src = trigger.getAttribute('href') || trigger.getAttribute('data-src') || '';
				creative = filenameSlug(src);
			}
		}

		ascTrackMediaInteraction(trigger, {
			media_type:          mediaType,
			event_action_result: actionResult || 'popup',
			link_url:            linkUrl(trigger),
			creative_name:       creative,
		});
	});

	// ── Slick slider navigation ───────────────────────────────────────────────

	var SLIDER_MAP = [
		{ selector: '.detail-slider',           media_type: 'image',  creative_name: 'vehicle_gallery'    },
		{ selector: '.visual-slider',            media_type: 'slider', creative_name: 'hero_slider'        },
		{ selector: '.offers-slider',            media_type: 'slider', creative_name: 'offers_slider'      },
		{ selector: '.specials-slider',          media_type: 'slider', creative_name: 'specials_slider'    },
		{ selector: '.full-width-image-slider',  media_type: 'slider', creative_name: 'full_width_slider'  },
		{ selector: '.model-slider',             media_type: 'slider', creative_name: 'model_slider'       },
	];

	function initSlickTracking($) {
		SLIDER_MAP.forEach(function (entry) {
			$(document).on('afterChange', entry.selector, function (_e, _slick, currentSlide) {
				var el = this;
				ascTrackMediaInteraction(el, {
					media_type:          dataAttr(el, 'media-type')    || entry.media_type,
					event_action_result: dataAttr(el, 'media-action')  || 'next_step',
					link_url:            '',
					creative_name:       dataAttr(el, 'creative-name') || (entry.creative_name + '_slide_' + (currentSlide + 1)),
				});
			});
		});

		$(document).on('click', '.slider-nav-holder .slide, .detail-slider-nav .slide', function () {
			var el = this;
			ascTrackMediaInteraction(el, {
				media_type:          dataAttr(el, 'media-type')    || 'gallery',
				event_action_result: dataAttr(el, 'media-action')  || 'next_step',
				link_url:            '',
				creative_name:       dataAttr(el, 'creative-name') || 'vehicle_thumbnail',
			});
		});
	}

	// ── Video tracking ────────────────────────────────────────────────────────

	var BGVIDEO_EVENTS = {
		playingVideo: 'play',
		pauseVideo:   'stop',
		endedVideo:   'stop',
	};

	function bgVideoCreativeName(el) {
		var c = dataAttr(el, 'creative-name');
		if (c) return c;
		var data = {};
		try { data = JSON.parse(el.getAttribute('data-video') || '{}'); } catch (e) {}
		var name = data.type ? (data.type + '_video') : 'video';
		if (data.video) name += '_' + data.video;
		return name;
	}

	function initVideoTracking($) {
		Object.keys(BGVIDEO_EVENTS).forEach(function (evtName) {
			$(document).on(evtName, '[data-video]', function () {
				var el = this;
				ascTrackMediaInteraction(el, {
					media_type:          dataAttr(el, 'media-type')    || 'video',
					event_action_result: BGVIDEO_EVENTS[evtName],
					link_url:            '',
					creative_name:       bgVideoCreativeName(el),
				});
			});
		});
	}

	function initNativeVideoTracking() {
		document.addEventListener('play', function (e) {
			if (e.target.tagName !== 'VIDEO') return;
			var el = e.target;
			ascTrackMediaInteraction(el, {
				media_type:          dataAttr(el, 'media-type')    || 'video',
				event_action_result: 'play',
				link_url:            '',
				creative_name:       dataAttr(el, 'creative-name') || filenameSlug(el.getAttribute('src') || '') || 'html5_video',
			});
		}, true);

		document.addEventListener('pause', function (e) {
			if (e.target.tagName !== 'VIDEO' || e.target.ended) return;
			var el = e.target;
			ascTrackMediaInteraction(el, {
				media_type:          dataAttr(el, 'media-type')    || 'video',
				event_action_result: 'stop',
				link_url:            '',
				creative_name:       dataAttr(el, 'creative-name') || filenameSlug(el.getAttribute('src') || '') || 'html5_video',
			});
		}, true);
	}

	// ── Boot ──────────────────────────────────────────────────────────────────

	function boot() {
		initNativeVideoTracking();

		if (window.jQuery) {
			initSlickTracking(window.jQuery);
			initVideoTracking(window.jQuery);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
}());

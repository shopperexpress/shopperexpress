// Confirm Delete Modal init
function initConfirmDeleteModal() {
	const $doc = jQuery(document);
	const $body = jQuery('body');
	const $editModal = jQuery('#editModal');
	const $confirmModal = jQuery('#confirmDelete');

	let savedBodyPaddingRight = null;
	let savedScrollbarWidth = null;

	function getComputedBodyPR() {
		return window.getComputedStyle(document.body).paddingRight || '';
	}

	function measureScrollbarWidth() {
		return window.innerWidth - document.documentElement.clientWidth;
	}

	function computeSafePaddingRight() {
		if (savedBodyPaddingRight && savedBodyPaddingRight !== '0px') {
			return savedBodyPaddingRight;
		}

		const sw =
			savedScrollbarWidth != null
				? savedScrollbarWidth
				: measureScrollbarWidth();

		return sw > 0 ? sw + 'px' : '';
	}

	function lockBodyIfEditOpen() {
		if ($editModal.hasClass('show')) {
			$body
				.addClass('modal-open')
				.css('padding-right', computeSafePaddingRight());

			return true;
		}

		return false;
	}

	function lockBodyDeferred() {
		lockBodyIfEditOpen();
		setTimeout(lockBodyIfEditOpen, 0);

		if (window.requestAnimationFrame) {
			requestAnimationFrame(lockBodyIfEditOpen);
		}
	}

	$confirmModal.on('show.bs.modal', function() {
		setTimeout(function() {
			jQuery('.modal-backdrop').last().addClass('confirm-delete-backdrop');
		}, 10);
	});

	$doc.on('show.bs.modal', '.modal', function() {
		if (jQuery('.modal.show').length === 0) {
			savedScrollbarWidth = measureScrollbarWidth();
		}
	});

	$doc.on('shown.bs.modal', '.modal', function() {
		if (jQuery('.modal.show').length === 1) {
			savedBodyPaddingRight = getComputedBodyPR();
		}
	});

	$confirmModal.on('hide.bs.modal', function() {
		if ($editModal.hasClass('show')) {
			savedBodyPaddingRight = getComputedBodyPR();
			lockBodyDeferred();
		}
	});

	$confirmModal.on('hidden.bs.modal', function() {
		if ($editModal.hasClass('show')) {
			lockBodyDeferred();
		}
	});

	$confirmModal
		.find('#confirmYes')
		.off('click.initConfirmDelete')
		.on('click.initConfirmDelete', function() {
			let remaining = 2;

			function afterHidden() {
				remaining--;
				if (remaining === 0) {
					$body.removeClass('modal-open').css('padding-right', '');
					savedBodyPaddingRight = null;
					savedScrollbarWidth = null;
				}
			}

			savedBodyPaddingRight = getComputedBodyPR();
			lockBodyDeferred();

			$confirmModal.one('hidden.bs.modal', afterHidden);
			$editModal.one('hidden.bs.modal', afterHidden);

			$confirmModal.modal('hide');
			$editModal.modal('hide');
		});

	$doc.on('show.bs.modal.stacked', '.modal', function() {
		const z = 1040 + 10 * jQuery('.modal.show').length;

		jQuery(this).css('z-index', z);

		setTimeout(function() {
			jQuery('.modal-backdrop')
				.not('.modal-stack')
				.css('z-index', z - 1)
				.addClass('modal-stack');
		}, 0);
	});

	$doc.on('hidden.bs.modal', '.modal', function() {
		setTimeout(function() {
			if (jQuery('.modal.show').length) {
				savedBodyPaddingRight = getComputedBodyPR() || savedBodyPaddingRight;
				lockBodyDeferred();
			} else {
				$body.removeClass('modal-open').css('padding-right', '');
				savedBodyPaddingRight = null;
				savedScrollbarWidth = null;
			}
		}, 0);
	});
}

function initOpenPdfInNewTab() {
	document.querySelectorAll('.details-badges').forEach((holder) => {
		const links = holder.querySelectorAll('a[data-pdf]');

		if (!links.length) return;

		links.forEach((link) => {
			link.addEventListener('click', (e) => {
				e.preventDefault();

				const url = link.getAttribute('href');

				sendRequest(url);
			});
		});

		async function sendRequest(url) {
			if (!url) return;

			try {
				const response = await fetch(url, {
					method: 'GET'
				});

				if (!response.ok) {
					throw new Error(`HTTP ${response.status}`);
				}

				const data = await response.json();

				onSuccess(data);
			} catch (error) {
				console.error('Error:', error);
			}
		}

		function onSuccess(response) {
			if (response.success) {
				if (response.data.url) {
					window.open(response.data.url, '_blank');
				}
			} else {
				console.error('Error:', response.data.message);
			}
		}
	});
}

// Content tabs init
function initTabs() {
	const activeClass = 'filter-mileage-active';
	const modal = jQuery('#filterSchedule');

	if (jQuery('.filter-list').length) {
		jQuery('.filter-list').tabset({
			tabLinks: 'a',
			attrib: 'data-tab',
			tabAttrib: 'data-id',
			defaultTab: false,
			onChange: function() {
				toggleClass();
			}
		});
	}

	if (modal.length) {
		modal.on('shown.bs.modal', function() {
			toggleClass();
		});
	}

	function toggleClass() {
		if (modal.length) {
			modal.find('.modal-range-box').each(function() {
				if (jQuery(this).closest('[data-id].active').length) {
					modal.addClass(activeClass);
				} else {
					modal.removeClass(activeClass);
				}
			});
		}
	}
}

// Edit modal init
function initEditModal() {
	document.querySelectorAll('.modal-edit').forEach((modal) => {
		new EditModal(modal);
	});

	// Confirm delete button handler
	jQuery('#confirmYes').on('click', function(e) {
		e.preventDefault();

		const post_id = jQuery(this).data('post-id');

		jQuery.ajax({
			url: window.ajax.admin,
			type: 'POST',
			data: {
				action: 'delete_listings',
				post_id: post_id
			},
			success: function(response) {
				if (response.success) {
					window.location.href = '/';
				} else {
					console.error('Error: ' + response.data);
				}
			},
			error: function() {
				console.error('AJAX request failed');
			}
		});
	});
}

/*
 * Edit Modal module
 */
class EditModal {
	constructor(holder, options) {
		this.options = {
			formID: 'custom-acf-form',
			loadingClass: 'loading',
			errorAlert: '.alert.bg-danger',
			successAlert: '.alert.bg-success',
			btnClearCashSelector: '[data-clear]',
			btnSaveSelector: '[data-save]',
			showClass: 'show',
			closeAlertButton: '.close-alert',
			...options
		};

		if (holder) {
			this.holder = holder;

			this.init();
		}
	}

	init() {
		this.form = this.holder.querySelector(`#${this.options.formID}`);
		this.errorAlert = this.holder.querySelector(this.options.errorAlert);
		this.successAlert = this.holder.querySelector(this.options.successAlert);
		this.closeAlertButtons = this.holder.querySelectorAll(this.options.closeAlertButton);

		if (!this.form) return;

		this.attachEvents();
	}

	attachEvents() {
		acf.unload.disable();

		this.holder.addEventListener('click', (e) => {
			const target = e.target;

			if (target.closest(this.options.btnSaveSelector)) {
				e.preventDefault();

				this.formData = new FormData(this.form);

				this.formData.append('action', 'save_listing');

				this.validateAcfForm();
			} else if (target.closest(this.options.btnClearCashSelector)) {
				e.preventDefault();

				this.clearCash(target.dataset.clear);
			}
		});

		jQuery(this.holder).on('hidden.bs.modal', () => {
			this.hideAlerts();
		});

		this.closeAlertButtons?.forEach((btn) => {
			const alert = btn.closest('.alert');

			btn.addEventListener('click', (e) => {
				e.preventDefault();

				if (alert) {
					alert.classList.remove(this.options.showClass);
				}
			});
		});
	}

	submitForm() {
		jQuery.ajax({
			url: window.ajax.admin,
			type: 'POST',
			data: this.formData,
			processData: false,
			contentType: false,
			beforeSend: () => {
				this.form.classList.add(this.options.loadingClass);
			},
			success: (response) => {
				this.form.classList.remove(this.options.loadingClass);

				if (response.success) {
					this.onSuccess(response.data.message);
				} else {
					console.error(response.data?.message || 'Error saving fields');
				}
			},
			error: (xhr) => {
				this.form.classList.remove(this.options.loadingClass);

				console.error('AJAX Error:', xhr);
			}
		});
	}

	clearCash(postType) {
		jQuery.ajax({
			url: window.ajax.admin,
			type: 'POST',
			data: {
				action: 'clear',
				post_type: postType
			},
			beforeSend: () => {
				this.form.classList.add(this.options.loadingClass);
			},
			success: (response) => {
				this.form.classList.remove(this.options.loadingClass);

				this.onSuccess(response.data.message);
			},
			error: (xhr) => {
				this.form.classList.remove(this.options.loadingClass);

				console.error('AJAX Error:', xhr);
			}
		});
	}

	validateAcfForm() {
		if (typeof acf === 'undefined') return;

		this.hideAlerts();

		const fields = acf.getFields({
			parent: this.form
		});

		let isValid = true;
		let firstInvalidField = null;

		fields.forEach((field) => {
			// Clear previous errors
			if (typeof field.removeError === 'function') {
				field.removeError();
			}

			// Validate required fields
			if (field.get('required') && !field.val()) {
				isValid = false;

				if (!firstInvalidField) {
					firstInvalidField = field.$el[0];
				}

				if (typeof field.showError === 'function') {
					field.showError(acf.__('This field is required'));
				}
			}
		});

		if (isValid) {
			this.submitForm();
		} else {
			this.onError();
			this.focusFirstInvalidField(firstInvalidField);
		}
	}

	hideAlerts() {
		if (this.errorAlert) {
			this.errorAlert.classList.remove(this.options.showClass);
		}

		if (this.successAlert) {
			this.successAlert.classList.remove(this.options.showClass);
		}
	}

	onSuccess(message) {
		if (this.successAlert && message.trim() !== '') {
			const messageElem = this.successAlert.querySelector('strong');

			if (messageElem) {
				messageElem.textContent = message;
			}

			this.successAlert.classList.add(this.options.showClass);
		}
	}

	onError() {
		if (this.errorAlert) {
			this.errorAlert.classList.add(this.options.showClass);
		}
	}

	focusFirstInvalidField(fieldElement) {
		if (!fieldElement) return;

		const tabPane = fieldElement.closest('.tab-pane');

		if (tabPane && !tabPane.classList.contains('active')) {
			const tabId = tabPane.getAttribute('id');
			const tabTrigger = this.holder.querySelector(`[data-toggle="tab"][data-target="#${tabId}"]`);

			if (tabTrigger) {
				const tab = new bootstrap.Tab(tabTrigger);

				tab.show();
			}
		}

		setTimeout(() => {
			const modalBody = this.holder.querySelector('.modal-body');

			if (modalBody) {
				const fieldRect = fieldElement.getBoundingClientRect();
				const modalRect = modalBody.getBoundingClientRect();

				const offset = fieldRect.top - modalRect.top + modalBody.scrollTop - 50;

				modalBody.scrollTo({
					top: offset,
					behavior: 'smooth'
				});
			}
		}, 300);
	}
}

/**
 * Lock buttons for a specified duration after click
 *
 * @param {string} selector
 * @param {number} duration
 */
const ButtonLocker = (() => {
	const STORAGE_KEY = 'buttonLockUntil';

	function init(selector = '[data-clear]', duration = 60000) {
		const buttons = document.querySelectorAll(selector);

		if (!buttons.length) return;

		applyLock(selector);

		buttons.forEach((btn) => {
			btn.addEventListener('click', (e) => {
				if (btn.disabled) {
					e.preventDefault();

					return;
				}

				const unblockAt = Date.now() + duration;

				localStorage.setItem(STORAGE_KEY, unblockAt);

				lockButtons(selector);
				setTimeout(() => applyLock(selector), duration + 50);
			});
		});

		setInterval(() => applyLock(selector), 10000);
	}

	function applyLock(selector) {
		const buttons = document.querySelectorAll(selector);

		if (!buttons.length) return;

		const unblockAt = Number(localStorage.getItem(STORAGE_KEY)) || 0;
		const now = Date.now();

		if (now >= unblockAt) {
			unlockButtons(selector);
			localStorage.removeItem(STORAGE_KEY);
		} else {
			lockButtons(selector);
		}
	}

	function lockButtons(selector) {
		const buttons = document.querySelectorAll(selector);

		buttons.forEach((btn) => {
			btn.disabled = true;
			btn.classList.add('disabled');
		});
	}

	function unlockButtons(selector) {
		const buttons = document.querySelectorAll(selector);

		buttons.forEach((btn) => {
			btn.disabled = false;
			btn.classList.remove('disabled');
		});
	}

	return { init, applyLock };
})();

// Stretch iframe on page resize
function initStretchIframe() {
	const iframes = document.querySelectorAll('.evo-slider iframe.ratio-custom');

	if (!iframes.length) return;

	const iframeData = Array.from(iframes).map((iframe) => {
		const width = iframe.width;
		const height = iframe.height;
		const ratio = width / height;

		return {
			iframe,
			ratio
		};
	});

	const triggerUpdateSliderHeight = debounce(() => {
		window.dispatchEvent(new CustomEvent('updateSliderHeight'));
	}, 250);

	function updateIframes() {
		iframeData.forEach(({ iframe, ratio }) => {
			iframe.style.height = `${iframe.parentNode.offsetWidth / ratio}px`;
		});

		triggerUpdateSliderHeight();
	}

	const debouncedUpdate = debounce(updateIframes, 200);

	updateIframes();

	window.addEventListener('resize', debouncedUpdate);

	function debounce(func, delay = 250) {
		let timer = null;

		return (...args) => {
			clearTimeout(timer);

			timer = setTimeout(() => func.apply(this, args), delay);
		};
	}
}

// Hide info button init
function initHideInfoButton() {
	document.querySelectorAll('.evo-slider-holder').forEach((iframeHolder) => {
		const overlay = iframeHolder.querySelector('.evo-slider-btn-overlay');

		if (!overlay) return;

		overlay.addEventListener('click', hideInfoButton);

		function hideInfoButton() {
			overlay.style.display = 'none';

			overlay.removeEventListener('click', hideInfoButton);
		}
	});
}

// Priority Navigation init
function initPriorityNav() {
	let originalNavHTML = null;

	const saveOriginalHTML = () => {
		const navHolder = jQuery('.nav-holder');

		if (navHolder.length && !originalNavHTML) {
			originalNavHTML = navHolder.prop('outerHTML');
		}
	};

	saveOriginalHTML();

	ResponsiveHelper.addRange({
		'..991': {
			on: () => {
				const navHolder = jQuery('.nav-holder');

				if (navHolder.length && originalNavHTML) {
					navHolder.replaceWith(originalNavHTML);
				}

				jQuery('.main-navigation > li').openClose({
					activeClass: 'active',
					opener: '.drop-opener',
					slider: '.slide',
					animSpeed: 400,
					effect: 'slide'
				});
			}
		},
		'992..': {
			on: () => {
				jQuery('.main-navigation > li').openClose('destroy');

				priorityNav.init({
					mainNavWrapper: '.nav-holder',
					mainNav: 'ul.main-navigation',
					navDropdownLabel: 'More',
					breakPoint: 1
				});

				jQuery('.nav-holder .priority-nav__dropdown').attr('id', 'menu');
			}
		}
	});
}

// Switch Logos init
function initSwitchLogos() {
	const activeClass = 'active';

	document.querySelectorAll('.logo-slider').forEach((holder) => {
		const brands = holder.querySelectorAll('.slider-item');
		const intervalTime = holder.dataset.autoplaySpeed || 3000;
		let currentIndex = 0;

		if (!brands) return;

		brands.forEach((brand, index) => {
			if (index === 0) {
				brands[currentIndex].classList.add(activeClass);
			}
		});

		setTimeout(() => {
			setInterval(switchBrand, intervalTime);
		}, intervalTime);

		function switchBrand() {
			brands[currentIndex].classList.remove(activeClass);

			currentIndex = (currentIndex + 1) % brands.length;

			brands[currentIndex].classList.add(activeClass);
		}
	});
}

// Update favorite
function initUpdateFavorite() {
	const activeClass = 'active';
	const url = window.location.origin + '/wp-json/favorite/v1/favorite-count';
	const btnUser = jQuery('#header .btn-user');
	const favoriteList = jQuery('#header .dropdown-menu [data-user]');
	const favoriteListingsCount = jQuery('#header .dropdown-menu .favorite-listings');
	const favoriteUsedListingsCount = jQuery('#header .dropdown-menu .favorite-used-listings');
	const favoriteConditionalOffersCount = jQuery('#header .dropdown-menu .favorite-conditional-offers');
	const favoriteLeaseOffersCount = jQuery('#header .dropdown-menu .favorite-lease-offers');
	const favoriteFinanceOffersCount = jQuery('#header .dropdown-menu .favorite-finance-offers');
	const favoriteOffersCount = jQuery('#header .dropdown-menu .favorite-offers');

	jQuery(document).on('favorites-updated-single', function() {
		getFavorite();
	});

	getFavorite();

	function getFavorite() {
		jQuery.ajax({
			url,
			data: {
				user: favoriteList.length ? favoriteList.data('user') : 0
			},
			type: 'POST',
			success: function(data) {
				onSuccess(data);
			}
		});
	}

	function onSuccess(data) {
		let total = 0;

		// Helper function to update element and sum values
		function updateCount(item, key) {
			if (item && item.length) {
				const value = +data[key] || 0;

				item.text(value);
				total += value;
			}
		}

		updateCount(favoriteListingsCount, 'listings');
		updateCount(favoriteUsedListingsCount, 'used-listings');
		updateCount(favoriteConditionalOffersCount, 'conditional-offers');
		updateCount(favoriteLeaseOffersCount, 'lease-offers');
		updateCount(favoriteFinanceOffersCount, 'finance-offers');
		updateCount(favoriteOffersCount, 'offers');

		btnUser.toggleClass(activeClass, total > 0);
	}

	const type = window.ajax ? window.ajax.request.saved : undefined;

	if (type) {
		jQuery.ajax({
			url: window.ajax.admin,
			type: 'POST',
			data: {
				action: 'favorites_array'
			},
			success: function(response) {
				if (response.status === 'success') {
					const favorites = response.favorites;
					const posts = Object.values(favorites[0].posts);
					const postIDs = [];

					if (posts.length > 0) {
						posts.forEach(function(post) {
							postIDs.push(post.post_id);
						});

						// Collect all saved ids
						const data = {
							post_id: postIDs.join(',')
						};

						jQuery.post(window.location.origin + `/wp-json/favorite/v1/render?action=${type}`, data, function(response) {
							if (response.success) {
								jQuery('#listings-container').html(response.html);
								initUnlockSavings();
							}
						});
					}
				}
			}
		});
	}
}

function initRemoveBlock() {
	jQuery('#block-1 .payment-info').each(function() {
		const holder = jQuery(this);
		const items = holder.find('> li');

		items.each(function() {
			const item = jQuery(this);
			const textHolder = item.find('.text-holder');
			const siblings = textHolder.siblings();

			if (!siblings.length) {
				item.remove();
			}
		});
	});
}

// init touch device
function initTouchDevice() {
	const isTouchDevice = /Windows Phone/.test(navigator.userAgent) || ('ontouchstart' in window) || window.DocumentTouch && document instanceof DocumentTouch;

	if (isTouchDevice) {
		jQuery('html').addClass('touch-device');
	}
}

// checked classes when element active
function initFieldsSwitcher() {
	const holder = jQuery('.form-offer');

	holder.each(function() {
		const checkedClass = 'input-checked';
		const buttons = holder.find('[data-name]');
		const fields = holder.find('[data-field]');

		buttons.each(function() {
			const button = jQuery(this);

			if (button.is(':checked')) {
				refreshState(button);
			}
		});

		buttons.on('click', function() {
			refreshState(jQuery(this));
		});

		function refreshState(button) {
			fields.hide().val('');
			buttons.parent().removeClass(checkedClass);

			jQuery('[data-field="' + button.data('name') + '"]').show();

			button.parent().addClass(checkedClass);
		}
	});
}

// copy to clipboard init
function initCopyToClipboard() {
	// fix bug with negative tabindex in modal
	jQuery.fn.modal.Constructor.prototype._enforceFocus = function() {};

	document.querySelectorAll('[data-copied]').forEach((btn) => {
		const textElem = btn.querySelector('.btn-text');

		// Set url to clipboard text if not set
		if (!btn.dataset.clipboardText) {
			btn.setAttribute('data-clipboard-text', window.location.href);
		}

		const clipboard = new ClipboardJS(btn);

		if (!textElem) return;

		const defaultText = textElem.textContent;

		btn.addEventListener('click', (e) => {
			e.preventDefault();
		});

		clipboard.on('success', function() {
			const copiedText = btn.dataset.copied || 'Copied';

			textElem.textContent = copiedText;

			setTimeout(() => {
				textElem.textContent = defaultText;
			}, 2000);
		});
	});
}

// initialize smooth anchor links
function initAnchors() {
	new SmoothScroll({
		anchorLinks: 'a.anchor',
		extraOffset: 0,
		wheelBehavior: 'none'
	});

	new SmoothScroll({
		anchorLinks: 'a.btn-anchor',
		extraOffset: function() {
			let totalHeight = 0;

			jQuery('#header').each(function() {
				const $box = jQuery(this);
				const stickyInstance = $box.data('StickyScrollBlock');

				if (stickyInstance) {
					stickyInstance.stickyFlag = false;
					stickyInstance.stickyOn();
					totalHeight += $box.outerHeight();
					stickyInstance.onResize();
				} else {
					totalHeight += $box.css('position') === 'fixed' ? $box.outerHeight() : 0;
				}
			});

			return totalHeight;
		},
		wheelBehavior: 'none'
	});
}

// Sticky summary block init
function initStickySummary() {
	document.querySelectorAll('.detail-section').forEach((holder) => {
		new StickySummary(holder, {
			fixedClass: 'fixed-summary',
			cloneBlockClass: 'sticky-clone',
			stickyBlock: '.sticky-summary .summary-holder',
			triggerBlock: '.info-block--top'
		});
	});
}

/*
 * Sticky Summary module
 */
class StickySummary {
	constructor(holder, options) {
		this.options = {
			fixedClass: 'fixed-block',
			cloneBlockClass: 'sticky-clone',
			stickyBlock: '.sticky-summary',
			triggerBlock: '.info-block--top',
			...options
		};

		if (holder) {
			this.holder = holder;

			this.init();
		}
	}

	init() {
		this.header = document.querySelector('#header');
		this.stickyBlock = this.holder.querySelector(this.options.stickyBlock);
		this.triggerBlock = this.holder.querySelector(this.options.triggerBlock);

		if (!this.stickyBlock || !this.triggerBlock) return;

		this.cloneBlock = null;
		this.isFixed = false;
		this.isDisabled = false;

		this.attachEvents();
	}

	attachEvents() {
		this.handleResize = this.resizeHandler.bind(this);
		this.handleScroll = this.scrollHandler.bind(this);

		this.resizeHandler();

		window.addEventListener('resize', this.debounce(this.handleResize));
		window.addEventListener('scroll', this.handleScroll);
	}

	createCloneBlock() {
		if (this.cloneBlock) return;

		this.cloneBlock = document.createElement('div');
		this.cloneBlock.classList.add(this.options.cloneBlockClass);
		this.stickyBlock.parentNode.insertBefore(this.cloneBlock, this.stickyBlock);

		this.cloneBlock.style.height = this.stickyBlock.offsetHeight + 'px';
		this.stickyBlock.style.width = this.cloneBlock.offsetWidth + 'px';
	}

	removeCloneBlock() {
		if (this.cloneBlock) {
			this.cloneBlock.parentNode.removeChild(this.cloneBlock);
			this.cloneBlock = null;
		}
	}

	scrollHandler() {
		if (this.isDisabled) {
			this.removeCloneBlock();
			this.holder.classList.remove(this.options.fixedClass);

			this.isFixed = false;

			return;
		}

		const scrollY = window.scrollY;

		if (this.triggerBlockOffset + this.triggerBlockHeight <= scrollY + this.headerHeight && scrollY + this.windowHeight < this.holderOffset + this.holderHeight - this.stickyBlock.offsetHeight) {
			if (!this.isFixed) {
				this.createCloneBlock();
				this.holder.classList.add(this.options.fixedClass);

				this.isFixed = true;
			}
		} else {
			if (this.isFixed) {
				this.removeCloneBlock();
				this.holder.classList.remove(this.options.fixedClass);

				this.isFixed = false;
			}
		}
	}

	resizeHandler() {
		this.triggerBlockOffset = this.triggerBlock.getBoundingClientRect().top + window.scrollY;
		this.triggerBlockHeight = this.triggerBlock.offsetHeight;
		this.holderOffset = this.holder.getBoundingClientRect().top + window.scrollY;
		this.holderHeight = this.holder.offsetHeight;
		this.headerHeight = this.header.offsetHeight || 0;
		this.windowHeight = window.innerHeight;

		if (this.cloneBlock) {
			this.cloneBlock.style.height = this.stickyBlock.offsetHeight + 'px';
			this.stickyBlock.style.width = this.cloneBlock.offsetWidth + 'px';
		}

		if (this.stickyBlock.offsetHeight > this.windowHeight - this.headerHeight - 200) {
			this.isDisabled = true;
		} else {
			this.isDisabled = false;

		}
		this.scrollHandler();
	}

	debounce(func, delay = 250) {
		let timer = null;

		return (...args) => {
			clearTimeout(timer);
			timer = setTimeout(() => func.apply(this, args), delay);
		};
	}
}

// search autocomplete
function initSearchForms() {
	const postTypeStr = '&post_type[]=listings&post_type[]=used-listings';

	// Search form on homepage
	jQuery('.search-row:not(.filter-section .search-row)').each(function() {
		const holder = jQuery(this);
		const redirectURL = holder.data('redirect-url');

		if (!redirectURL) return;

		const searchLinkNew = holder.find('.search-link-new');
		const searchLinkUsed = holder.find('.search-link-used');
		const newItemURL = searchLinkNew.attr('href');
		const usedItemURL = searchLinkUsed.attr('href');
		const searchText = holder.find('.search-text');

		holder.autocomplete({
			inputField: '[type="search"]',
			dataAttr: 'search',
			dataType: 'json',
			highlightMatches: true,
			showOnFocus: true,
			filterResults: false,
			alwaysRefresh: true,
			queryStr: postTypeStr,
			noResultClass: 'no-results',
			preventEnterClick: true,
			listItems: 'li',
			onInit: function() {
				// Prevent form submission
				this.form.on('submit', (e) => {
					e.preventDefault();
				});

				// Prevent "Enter" key
				this.input.on('keydown', (e) => {
					if (e.keyCode === 'Enter') {
						e.preventDefault();
					}
				});
			},
			onKeyup: function(value) {
				/**
				 * Updates search URL for new and used cars into autocomplete box
				 * @param {jQuery} searchText - element that contains text to be updated
				 * @param {jQuery} searchLinkNew - element that contains link to new cars page
				 * @param {jQuery} searchLinkUsed - element that contains link to used cars page
				 * @param {string} newItemURL - URL of new cars page
				 * @param {string} usedItemURL - URL of used cars page
				 * @param {string} value - search query
				 */
				updateSearchURL(searchText, searchLinkNew, searchLinkUsed, newItemURL, usedItemURL, value);
			},
			onLoadData: function(data) {
				const { isNewResults, isUsedResults } = data;

				if (!isNewResults) {
					searchLinkNew.hide();
				} else {
					searchLinkNew.show();
				}

				if (!isUsedResults) {
					searchLinkUsed.hide();
				} else {
					searchLinkUsed.show();
				}
			},
			parseData: function(data) {
				return createAutocompleteItems(this, data);
			},
			onSelectedItem: function(item) {
				const searchFilters = item.data('search');
				const url = item.data('url') || redirectURL;

				if (!searchFilters) return;

				const value = Object.entries(searchFilters)
					.map(([key, val]) => {
						// Should change "vin" or "stock" to "search" for the correct search
						if (key.toLowerCase() === 'vin' || key.toLowerCase() === 'stock') {
							key = 'search';
						}

						return `${key}=${encodeURIComponent((val ?? '').toString().toLowerCase())}`;
					})
					.join('&');

				if (value !== '') {
					window.location.href = `${url}?${value}`;
				}
			}
		});
	});
}

// initialize sticky class
function initStickyClass() {
	const win = jQuery(window);
	const page = jQuery('body');
	const searchPanel = page.find('.sticky-wrap-sticky-panel');
	const activeClass = 'sticky-search';
	const fixedClass = 'fixed-position';

	function scrollHandler() {
		if (searchPanel.hasClass(fixedClass)) {
			page.addClass(activeClass);
		} else {
			page.removeClass(activeClass);
		}
	}

	scrollHandler();
	win.on('load resize orientationchange scroll', scrollHandler);
}

function initOfferForm() {
	jQuery('.form-offer').each(function() {
		const form = jQuery(this);
		const btnSubmit = form.find('.btn-submit');
		const injectPlateField = form.find('.inject-plate');
		const injectVinField = form.find('.inject-vin');
		const injectStateField = form.find('.inject-state');
		const injectMilesField = form.find('.inject-miles');
		const allFields = form.find('.form-control');
		let injectPlate, injectVin, injectState, injectMiles;

		function onClickChange() {
			injectPlate = injectPlateField.val() || '';
			injectVin = injectVinField.val() || '';
			injectState = injectStateField.val() || '';
			injectMiles = injectMilesField.val() || '';
		}

		allFields.on('keyup', onClickChange);

		btnSubmit.on('click', function(e) {
			e.preventDefault();
			onClickChange();

			if (inticeEvents !== undefined) {
				inticeEvents.launchExpressCashOffer('519', injectPlate, injectVin, injectState, injectMiles);
			}
		});
	});
}

function initStickyScrollBlock() {
	jQuery('.sticky-box').stickyScrollBlock({
		setBoxHeight: false,
		activeClass: 'fixed-position',
		container: '.detail-section',
		positionType: 'fixed',
		extraTop: function() {
			let totalHeight = 0;

			jQuery('#header').each(function() {
				totalHeight += jQuery(this).outerHeight();
			});

			return totalHeight;
		}
	});

	jQuery('.sticky-panel').stickyScrollBlock({
		setBoxHeight: true,
		activeClass: 'fixed-position',
		positionType: 'fixed',
		extraTop: function() {
			let totalHeight = 0;

			jQuery('#header').each(function() {
				totalHeight += jQuery(this).outerHeight();
			});

			return totalHeight;
		}
	});

	jQuery('.new-landing #header').stickyScrollBlock({
		setBoxHeight: false,
		activeClass: 'fixed-position',
		positionType: 'fixed',
		extraTop: function() {
			let totalHeight = 0;

			jQuery('0').each(function() {
				totalHeight += jQuery(this).outerHeight();
			});

			return totalHeight;
		}
	});

	const vinButtons = jQuery('#vinButtons');
	let loadTimer = null;

	if (vinButtons.length) {
		loadTimer = setInterval(() => {
			if (vinButtons.find('>*').length) {
				jQuery(window).trigger('refreshStikyBlock');
				clearInterval(loadTimer);
			}
		}, 1000);

		setTimeout(() => {
			clearInterval(loadTimer);
		}, 120000);
	}
}

function initTooltip() {
	if (jQuery('[data-toggle="tooltip"]').length && bootstrap.Tooltip) {
		jQuery('[data-toggle="tooltip"]').tooltip();
	}
}

function initRegistration() {
	jQuery('.register-form').formValidation({
		errorClass: 'input-error',
		addClassToParent: '.form-group',
		successSendClass: 'success-send',
		errorFormClass: 'error-send'
	});

	const loadingClass = 'loading';

	jQuery('.form-unlock').each(function() {
		const form = jQuery(this);
		const btnRegister = form.find('#register-button');
		const message = form.find('.register-message');

		btnRegister.on('click', function(e) {
			e.preventDefault();

			const data = form.serialize() + '&action=register_user';

			form.addClass(loadingClass);

			jQuery.ajax({
				type: 'POST',
				url: ajax.admin,
				data: data,
				success: function(results) {
					if (results['error']) {
						message.text(results['error']).show();
					} else {
						window.location.replace(results);
					}

					form.removeClass(loadingClass);
				},
				error: function(results) {
					message.text(results).show();
					form.removeClass(loadingClass);
				}
			});
		});
	});
}

// slick init
function initSlickCarousel() {
	const win = jQuery(window);
	const activeClass = 'active';

	// Init visual slider on product page
	jQuery('.visual--banner .visual-slider-srp').slick({
		slidesToScroll: 1,
		rows: 0,
		prevArrow: '<button class="slick-control slick-prev slick-arrow" aria-label="Previous"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="m432-480 156 156q11 11 11 28t-11 28q-11 11-28 11t-28-11L348-452q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l184-184q11-11 28-11t28 11q11 11 11 28t-11 28L432-480Z"/></svg></button>',
		nextArrow: '<button class="slick-control slick-next slick-arrow" aria-label="Next"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M504-480 348-636q-11-11-11-28t11-28q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L404-268q-11 11-28 11t-28-11q-11-11-11-28t11-28l156-156Z"/></svg></button>',
		dots: true,
		dotsClass: 'slick-dots',
		autoplay: true
	});

	jQuery('.detail-slider-holder').each(function() {
		const holder = jQuery(this);
		const slider = holder.find('.detail-slider');
		const thumbs = holder.find('.detail-slider-nav .slide');
		const thumbsSlider = holder.find('.detail-slider-nav');
		const autoplay = slider.data('autoplay') || false;
		const autoplaySpeed = slider.data('autoplay-speed') || 3000;
		const range = holder.find('.range-box input');
		const sliderHolder = holder.find('.slider-nav-holder');
		let totalWidth = 0;
		let thumbsWidth = 0;
		let diffWidth = 0;

		thumbs.eq(0).addClass(activeClass);

		slider.slick({
			slidesToScroll: 1,
			rows: 0,
			infinite: false,
			prevArrow: '<button class="slick-control slick-prev slick-arrow" aria-label="Previous"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="m432-480 156 156q11 11 11 28t-11 28q-11 11-28 11t-28-11L348-452q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l184-184q11-11 28-11t28 11q11 11 11 28t-11 28L432-480Z"/></svg></button>',
			nextArrow: '<button class="slick-control slick-next slick-arrow" aria-label="Next"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M504-480 348-636q-11-11-11-28t11-28q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L404-268q-11 11-28 11t-28-11q-11-11-11-28t11-28l156-156Z"/></svg></button>',
			autoplay: autoplay,
			autoplaySpeed: autoplaySpeed,
			focusOnSelect: true
		});

		function resizeHandler() {
			totalWidth = thumbs.length * thumbs.outerWidth(true);
			thumbsWidth = thumbsSlider.outerWidth();
			diffWidth = totalWidth - thumbsWidth;
		}

		resizeHandler();
		win.on('resize orientationchange', resizeHandler);

		slider.on('beforeChange', function(event, slick, currentSlide, nextSlide) {
			thumbs.removeClass(activeClass);
			thumbs.eq(nextSlide).addClass(activeClass);
		});

		thumbs.on('click', function(e) {
			e.preventDefault();
			const item = jQuery(this);
			const index = thumbs.index(item);

			thumbs.removeClass(activeClass);
			item.addClass(activeClass);

			slider.slick('slickGoTo', index);
		});

		range.on('input', onChange);

		function onChange() {
			const value = parseFloat(range.data('jcfInstance').values[0]);

			sliderHolder.css({
				marginLeft: -(diffWidth / 100) * value,
				marginRight: (diffWidth / 100) * value
			});
		}
	});

	jQuery('.model-slider').each(function() {
		const slider = jQuery(this);

		initSlider();

		function initSlider() {
			slider.slick({
				slidesToScroll: 1,
				rows: 0,
				slide: '.slide:not(.hidden)',
				slidesToShow: 4,
				prevArrow: '<button class="slick-control slick-prev slick-arrow" aria-label="Previous"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="m432-480 156 156q11 11 11 28t-11 28q-11 11-28 11t-28-11L348-452q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l184-184q11-11 28-11t28 11q11 11 11 28t-11 28L432-480Z"/></svg></button>',
				nextArrow: '<button class="slick-control slick-next slick-arrow" aria-label="Next"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M504-480 348-636q-11-11-11-28t11-28q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L404-268q-11 11-28 11t-28-11q-11-11-11-28t11-28l156-156Z"/></svg></button>',
				responsive: [{
					breakpoint: 1200,
					settings: {
						slidesToShow: 3
					}
				}, {
					breakpoint: 992,
					settings: {
						slidesToShow: 2
					}
				}, {
					breakpoint: 768,
					settings: {
						slidesToShow: 1
					}
				}]
			});
		}

		function destroySlider() {
			slider.slick('destroy');
		}

		slider.on('beforeFilter', function() {
			destroySlider();
		});

		slider.on('rebuild', function() {
			initSlider();
		});
	});

	jQuery('.visual').each(function() {
		const holder = jQuery(this);
		const slider = holder.find('.visual-slider:not(.visual-slider-srp)');
		const controlsHolder = holder.find('.slick-controls');
		const buttonsHolder = controlsHolder.find('.buttons-holder');
		const dotsHolder = holder.find('.dots-holder');
		const btnPlayPause = controlsHolder.find('.slick-play-pause');
		const progress = controlsHolder.find('.indicator circle');
		const playingClass = 'playing';
		const pauseClass = 'pause';
		const resetClass = 'reset';
		const speed = slider.data('speed') || 300;
		const interval = slider.data('autoplay-speed') || 3000;
		const strokeDashoffset = 40;
		let startTime = null;
		let elapsedTime = 0;
		let animationRequest = null;
		let currentInterval = 0;
		let rotationTimer = null;

		slider.slick({
			slidesToScroll: 1,
			rows: 0,
			appendDots: dotsHolder,
			appendArrows: buttonsHolder,
			prevArrow: '<button class="slick-control slick-prev slick-arrow" aria-label="Previous"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="m432-480 156 156q11 11 11 28t-11 28q-11 11-28 11t-28-11L348-452q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l184-184q11-11 28-11t28 11q11 11 11 28t-11 28L432-480Z"/></svg></button>',
			nextArrow: '<button class="slick-control slick-next slick-arrow" aria-label="Next"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M504-480 348-636q-11-11-11-28t11-28q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L404-268q-11 11-28 11t-28-11q-11-11-11-28t11-28l156-156Z"/></svg></button>',
			dots: true,
			fade: true,
			dotsClass: 'slick-dots',
			speed: speed,
			autoplaySpeed: interval,
			pauseOnFocus: false,
			pauseOnHover: false,
			pauseOnDotsHover: false,
			autoplay: false
		});

		const btnPrev = buttonsHolder.find('.slick-prev');
		const btnNext = buttonsHolder.find('.slick-next');

		slider.on('beforeChange', function() {
			resetProgress();
		}).on('afterChange', function() {
			if (!controlsHolder.hasClass(pauseClass)) {
				slider.slick('slickSetOption', 'autoplaySpeed', interval, false);
				startProgress();
			}
		});

		btnPlayPause.on('click', function(e) {
			e.preventDefault();

			if (controlsHolder.hasClass(playingClass)) {
				pauseRotation();
			} else {
				startRotation();
			}
		});

		btnPrev.add(btnNext).add(dotsHolder).on('click', stopRotation);

		startRotation();

		function startRotation() {
			slider.slick('slickSetOption', 'autoplaySpeed', interval - currentInterval, false);
			slider.slick('slickPlay');
			controlsHolder.addClass(playingClass).removeClass(pauseClass);

			currentInterval = 0;

			startProgress();
		}

		function pauseRotation() {
			slider.slick('slickPause');
			controlsHolder.removeClass(playingClass).addClass(pauseClass);
			stopProgress();
		}

		function stopRotation() {
			slider.slick('slickPause');
			controlsHolder.removeClass(playingClass).addClass(pauseClass);
			currentInterval = 0;

			resetProgress();
		}

		function startProgress() {
			startTime = Date.now();
			animationRequest = requestAnimationFrame(updateProgress);
			currentInterval = 0;

			controlsHolder.removeClass(resetClass);

			rotationTimer = setInterval(() => {
				currentInterval += 100;
			}, 100);
		}

		function resetProgress() {
			cancelAnimationFrame(animationRequest);
			clearInterval(rotationTimer);

			controlsHolder.addClass(resetClass);

			elapsedTime = 0;

			progress.css({
				strokeDashoffset: strokeDashoffset
			});
		}

		function stopProgress() {
			cancelAnimationFrame(animationRequest);
			clearInterval(rotationTimer);

			const currentTime = Date.now();

			elapsedTime += currentTime - startTime;

			const progressValue = strokeDashoffset - (strokeDashoffset * elapsedTime / interval);

			progress.css({
				strokeDashoffset: progressValue
			});
		}

		function updateProgress() {
			const currentTime = Date.now();
			const totalElapsed = elapsedTime + (currentTime - startTime);
			const progressValue = strokeDashoffset - (strokeDashoffset * totalElapsed / interval);

			progress.css({
				'stroke-dashoffset': Math.max(progressValue, 0)
			});

			if (totalElapsed >= interval) {
				cancelAnimationFrame(animationRequest);
				elapsedTime = 0;
			} else {
				animationRequest = requestAnimationFrame(updateProgress);
			}
		}
	});

	jQuery('.specials-slider').each(function() {
		const slider = jQuery(this);
		const slides = slider.find('> div');
		const videoBlocks = slider.find('[data-video]');
		const speed = slider.data('speed') || 300;
		const autoplaySpeed = slider.data('autoplay-speed') || 5000;

		slider.on('init', function() {
			const firstBlock = slides.eq(0).find('[data-video]');

			if (firstBlock.length && firstBlock.data('BgVideo') && !firstBlock.hasClass('video-loaded')) {
				firstBlock.data('BgVideo').initPlayer();
			}
		});

		slider.slick({
			slidesToScroll: 1,
			rows: 0,
			prevArrow: '<button class="slick-control slick-prev slick-arrow" aria-label="Previous"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="m432-480 156 156q11 11 11 28t-11 28q-11 11-28 11t-28-11L348-452q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l184-184q11-11 28-11t28 11q11 11 11 28t-11 28L432-480Z"/></svg></button>',
			nextArrow: '<button class="slick-control slick-next slick-arrow" aria-label="Next"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M504-480 348-636q-11-11-11-28t11-28q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L404-268q-11 11-28 11t-28-11q-11-11-11-28t11-28l156-156Z"/></svg></button>',
			dots: true,
			dotsClass: 'slick-dots',
			autoplay: true,
			speed: speed,
			autoplaySpeed: autoplaySpeed
		});

		videoBlocks.each(function() {
			const holder = jQuery(this);
			const videoAPI = holder.data('BgVideo');

			if (videoAPI) {
				holder.on('playingVideo', () => {
					slider.slick('slickPause');
				}).on('pauseVideo', () => {
					slider.slick('slickPlay');
				}).on('endedVideo', () => {
					slider.slick('slickPlay');
				});
			}
		});

		slider.on('beforeChange', function(event, slick, currentSlide, nextSlide) {
			const currVideoBlock = slides.eq(currentSlide).find('[data-video]');
			const nextVideoBlock = slides.eq(nextSlide).find('[data-video]');

			if (nextVideoBlock.length && nextVideoBlock.data('BgVideo') && !nextVideoBlock.hasClass('video-loaded')) {
				nextVideoBlock.data('BgVideo').initPlayer();
			}

			if (currVideoBlock.length && currVideoBlock.data('BgVideo').player) {
				currVideoBlock.data('BgVideo').player.pause();
			}
		});
	});

	const evoSlider = jQuery('.evo-slider');

	evoSlider.slick({
		slidesToScroll: 1,
		rows: 0,
		arrows: false,
		dots: true,
		fade: true,
		speed: 0,
		dotsClass: 'slick-dots'
	});

	window.addEventListener('updateSliderHeight', resizeHandler);
	window.addEventListener('resize', debounce(resizeHandler, 500));

	resizeHandler();

	function resizeHandler() {
		evoSlider.slick('setPosition');
	}

	function debounce(func, delay = 250) {
		let timer = null;

		return function(...args) {
			clearTimeout(timer);

			timer = setTimeout(() => func.apply(this, args), delay);
		};
	}

	jQuery('.full-width-image-slider').each(function() {
		const slider = jQuery(this);
		const speed = slider.data('speed') || 300;
		const autoplaySpeed = slider.data('autoplay-speed') || 5000;

		slider.slick({
			rows: 0,
			slidesToScroll: 1,
			slidesToShow: 1,
			arrows: false,
			dots: true,
			dotsClass: 'slick-dots',
			autoplay: true,
			pauseOnHover: true,
			speed: speed,
			autoplaySpeed: autoplaySpeed
		});
  });

  jQuery('.offers-slider').each(function() {
		const slider = jQuery(this);

		initSlider();

		function initSlider() {
			slider.slick({
				slidesToScroll: 1,
				rows: 0,
				slidesToShow: 5,
				prevArrow: '<button class="slick-control slick-prev slick-arrow" aria-label="Previous"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="m432-480 156 156q11 11 11 28t-11 28q-11 11-28 11t-28-11L348-452q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l184-184q11-11 28-11t28 11q11 11 11 28t-11 28L432-480Z"/></svg></button>',
				nextArrow: '<button class="slick-control slick-next slick-arrow" aria-label="Next"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M504-480 348-636q-11-11-11-28t11-28q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L404-268q-11 11-28 11t-28-11q-11-11-11-28t11-28l156-156Z"/></svg></button>',
				responsive: [{
					breakpoint: 1480,
					settings: {
						slidesToShow: 4
					}
				}, {
					breakpoint: 1200,
					settings: {
						slidesToShow: 3
					}
				}, {
					breakpoint: 992,
					settings: {
						slidesToShow: 2
					}
				}, {
					breakpoint: 576,
					settings: {
						slidesToShow: 1
					}
				}]
			});
		}
	});
}

function initVideo() {
	jQuery('[data-video]').bgVideo();
}

// filtering modal init
function initFilteringModal() {
	const win = jQuery(window);
	const page = jQuery('body');
	const header = jQuery('#header');
	const introBlock = jQuery('.intro');
	const filterList = jQuery('.filter-list');
	const filterModal = jQuery('.modal-filter');
	const btnClose = filterModal.find('[data-dismiss="modal"]:not(.btn-back)');
	const activeClass = 'filter-modal-active';

	jQuery('[data-modal]').on('click', function(e) {
		e.preventDefault();
		resizeHandler();
		filterModal.modal('show');
	});

	function resizeHandler() {
		if (introBlock.length && getWindowWidth() >= 1200) {
			if (introBlock.offset().top + introBlock.outerHeight() >= win.scrollTop() + header.outerHeight()) {
				page.removeClass(activeClass);

				filterModal.css({
					marginTop: (introBlock.outerHeight() + header.outerHeight()) - win.scrollTop()
				});

				filterModal.on('shown.bs.modal', function() {
					const modalBackDrop = jQuery('.modal-backdrop');

					modalBackDrop.css({
						top: (introBlock.outerHeight() + header.outerHeight()) - win.scrollTop()
					});
				});
			} else {
				filterModal.css({
					marginTop: 0
				});

				filterModal.on('shown.bs.modal', function() {
					const modalBackDrop = jQuery('.modal-backdrop');

					modalBackDrop.css({
						top: 0
					});
				});

				page.addClass(activeClass);
			}
		} else {
			page.removeClass(activeClass);
		}

		if (!introBlock.length && getWindowWidth() >= 1200) {
			page.addClass(activeClass);
		}
	}

	resizeHandler();

	win.on('resize orientationchange', function() {
		resizeHandler();
	});

	ResponsiveHelper.addRange({
		'..991': {
			on: function() {
				btnClose.on('click.modal', function() {
					page.removeClass('filter-active');
				});
			}
		},
		'992..': {
			on: function() {
				btnClose.off('click.modal', function() {
					page.removeClass('filter-active');
				});
			}
		}
	});

	filterModal.on('shown.bs.modal', function() {
		const modal = jQuery('.modal-backdrop');

		if (modal.length) {
			modal.addClass('modal-filter-backdrop');
		}
	}).on('hidden.bs.modal', function() {
		filterList.find('.active').removeClass('active');
	});

	function getWindowWidth() {
		return typeof window.innerWidth === 'number' ? window.innerWidth : document.documentElement.clientWidth;
	}
}

// Initialize custom form elements
function initCustomForms() {
	// Initialize custom selects
	jcf.setOptions('Select', {
		wrapNative: false,
		wrapNativeOnMobile: false
	});

	jcf.replaceAll();

	// Initialize custom range sliders for filters
	jQuery('.range-row').each(function() {
		const holder = jQuery(this);
		const field = holder.find('[type="range"]');
		const minPrice = holder.find('.min-price');
		const maxPrice = holder.find('.max-price');
		const errorMessage = holder.find('.range-error').hide();
		const minErrorMessage = holder.data('error-min') || 'Please enter a value less than';
		const maxErrorMessage = holder.data('error-max') || 'Please enter a value greater than';
		const hiddenField = holder.find('[type="hidden"]');
		const defaultValues = field.data('jcfInstance').values;
		let values = [];
		let minValue = 0;
		let maxValue = 0;

		field.on('input', function() {
			values = field.data('jcfInstance').values;

			minValue = +field.attr('min');
			maxValue = +field.attr('max');

			updateInputValues(values);

			errorMessage.hide();
		});

		minPrice.add(maxPrice).on('input', function() {
			const input = jQuery(this);
			const value = input.val().replace(/[^\d]/g, '');

			errorMessage.hide();

			if (value) {
				input.val(transformValueToMoney(value));
			} else {
				input.val('');
			}
		}).on('blur', function() {
			const input = jQuery(this);
			let value = +input.val().replace(/[^\d]/g, '');

			if (input.is(minPrice)) {
				if (value < minValue || value <= 0) {
					showErrorMessage(`${maxErrorMessage} ${minValue}`);
					value = minValue;
				} else if (value > values[1]) {
					showErrorMessage(`${minErrorMessage} ${values[1]}`);
					value = minValue;
				}
			}

			if (input.is(maxPrice)) {
				if (value > maxValue) {
					showErrorMessage(`${minErrorMessage} ${maxValue}`);
					value = maxValue;
				} else if (value < values[0] || value <= 0) {
					showErrorMessage(`${maxErrorMessage} ${values[0]}`);
					value = maxValue;
				}
			}

			if (value) {
				input.val(transformValueToMoney(value));
			}

			const newMinValue = minPrice.val().replace(/[^\d]/g, '');
			const newMaxValue = maxPrice.val().replace(/[^\d]/g, '');

			// Update range slider values
			field.data('jcfInstance').values = [newMinValue, newMaxValue];
			field.trigger('input');
			jcf.refresh(field);

			// Trigger change event to filtration
			field.trigger('change');
		});

		updateInputValues(defaultValues);

		function showErrorMessage(message) {
			setTimeout(() => {
				errorMessage.text(message).show();
			}, 10);
		}

		function updateInputValues(values) {
			const minValue = (Math.round(values[0] * 100) / 100).toString().trim();
			const maxValue = (Math.round(values[1] * 100) / 100).toString().trim();

			// Update min and max price inputs
			minPrice.val(transformValueToMoney(minValue));
			maxPrice.val(transformValueToMoney(maxValue));

			// Update hidden field with min and max values
			hiddenField.val((values[0]).toString().trim() + ',' + (values[1]).toString().trim());
		}

		function transformValueToMoney(value) {
			const prefix = value > 0 ? '$' : '';

			return prefix + parseInt(value, 10).toLocaleString('en-US');
		}
	});

	jQuery('.modal-range-box').each(function() {
		const holder = jQuery(this);
		const field = holder.find('[type="range"]');
		const hiddenField = holder.find('[type="hidden"]');

		field.on('input', function() {
			const values = field.data('jcfInstance').values;

			hiddenField.val((values[0]).toString().trim() + ',' + (values[1]).toString().trim());
		});
	});

	jQuery('.se-sbp-widget__range').each(function() {
		const holder = jQuery(this);
		const field = holder.find('[type="range"]');
		const rangeLine = holder.find('.jcf-range-display');
		const valueField = holder.find('.se-sbp-widget__range-input');
		const currency = field.data('currency');
		const rangeValue = field.data('range-value');
		const max = field.attr('max');
		const activeClass = 'half-path';

		field.on('input', function() {
			onChange();
		});

		onChange();

		function onChange() {
			const values = field.data('jcfInstance').values;

			if (+values[0] < +max / 2) {
				rangeLine.addClass(activeClass);
			} else {
				rangeLine.removeClass(activeClass);
			}

			valueField.val(currency + formatToMoney(+values[0]) + '-' + currency + formatToMoney(+values[0] + rangeValue));
		}
	});

	function formatToMoney(number) {
		return String(number).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
	}

	jQuery('#wpsl-wrap').find('select').each((i, it) => jcf.destroy(it));
}

function initInputMask() {
	jQuery('.phone').mask('(000)000-0000');
	jQuery('.zip-code').mask('00000');
}

// open-close init
function initOpenClose() {
	jQuery('#nav > li').openClose({
		activeClass: 'active',
		opener: '.slide-opener',
		slider: '.menu-slide',
		animSpeed: 400,
		effect: 'slide'
	});
}

// lightbox init
function initFancybox() {
	const reverseClass = 'reverse-image';
	const bodyReverseClass = 'fancybox-has-reverse';
	const body = jQuery('body');

	jQuery('a.lightbox:not(.btn-spin), [data-fancybox]:not(.btn-spin)').fancybox({
		parentEl: 'body',
		margin: [50, 0],
		backFocus: false,
		beforeShow: function(instance, current) {
			body.removeClass(bodyReverseClass);
			jQuery('.fancybox-container, .fancybox-image, .fancybox-content').removeClass(reverseClass);

			const $opener = current.opts.$orig;

			if ($opener && $opener.hasClass(reverseClass)) {
				body.addClass(bodyReverseClass);
				jQuery('.fancybox-container').addClass(reverseClass);
			}
		},
		afterShow: function() {
			if (jQuery('.fancybox-container').hasClass(reverseClass)) {
				jQuery('.fancybox-image, .fancybox-content').addClass(reverseClass);
			}
		},
		beforeClose: function() {
			jQuery('.fancybox-image, .fancybox-content').removeClass(reverseClass);
		},
		afterClose: function() {
			body.removeClass(bodyReverseClass);
			jQuery('.fancybox-container').removeClass(reverseClass);
		}
	});
}

// Cookie modal init
function initCookieModal() {
	document.querySelectorAll('.modal').forEach((modal) => {
		const ID = modal.dataset.id;
		const cookieKey = ID + '-is-cashed';
		const isShow = modal.dataset.show === 'true';
		const cookieExpireDays = modal.dataset.cookieExpireDays !== undefined ? Number(modal.dataset.cookieExpireDays) : 30;

		if (!Cookies.get(cookieKey) && isShow) {
			setTimeout(() => {
				showPopup();
			}, 1000);
		}

		// Set cookie on close modal
		jQuery(modal).on('hidden.bs.modal', () => {
			setCookie();
		});

		function showPopup() {
			jQuery(modal).modal('show');
		}

		function setCookie() {
			Cookies.set(cookieKey, 'true', {
				expires: cookieExpireDays,
				path: '/'
			});
		}
	});
}

// mobile menu init
function initMobileNav() {
	jQuery('body').mobileNav({
		menuActiveClass: 'nav-active',
		hideOnClickOutside: true,
		menuOpener: '.nav-opener',
		menuDrop: 'ul'
	});

	jQuery('body').mobileNav({
		menuActiveClass: 'navigation-active',
		menuOpener: '.navigation-opener',
		hideOnClickOutside: true,
		menuDrop: '.header-frame'
	});

	jQuery('.form-box').mobileNav({
		menuActiveClass: 'form-active',
		menuOpener: '.form-opener'
	});

	jQuery('.help-holder').mobileNav({
		menuActiveClass: 'popup-active',
		menuOpener: '.opener',
		hideOnClickOutside: true,
		menuDrop: '.popup'
	});

	jQuery('.drop-models-popup').mobileNav({
		menuActiveClass: 'popup-active',
		menuOpener: '.btn-models',
		hideOnClickOutside: true,
		menuDrop: '.dropdown-models'
	});

	jQuery('.drop-schedule-popup').mobileNav({
		menuActiveClass: 'popup-active',
		menuOpener: '.btn-schedule, .btn-close',
		hideOnClickOutside: true,
		menuDrop: '.dropdown-schedule'
	});

	ResponsiveHelper.addRange({
		'..1200': {
			on: function() {
				jQuery('body').mobileNav({
					menuActiveClass: 'filter-active',
					menuOpener: '.filter-opener',
					hideOnClickOutside: true,
					menuDrop: '.aside'
				});
			},
			off: function() {
				jQuery('body').mobileNav('destroy');
			}
		}
	});
}

// filtering init
function initFiltering() {
	jQuery('.filter-section, .dropdown-models').filteringItems({
		items: '.slide, .drop-model-list > li',
		filterHolder: '.models-filter, .drop-model-slider',
		filterItems: 'a[data-filter]',
		onInit: function() {
			this.slider = this.holder.find('.model-slider');
		},
		beforeFilter: function() {
			if (this.slider.length) {
				this.slider.trigger('beforeFilter');
			}
		},
		onFilter: function() {
			if (this.slider.length) {
				this.slider.trigger('rebuild');
			}
		}
	});
}

function initSwitchModalText() {
	let currID = null;

	jQuery('.section-specials .payment-info a[data-target]').each(function() {
		const link = jQuery(this);
		const modal = jQuery(link.data('target'));
		const ID = link.data('vehicle-id');

		link.on('click', () => {
			currID = ID;

			if (modal.length) {
				const textHolder = modal.find('.text-holder');
				const textBoxes = textHolder.find('> div');

				textBoxes.hide();

				if (currID !== null) {
					jQuery('#' + currID).show();
				}

				jcf.refresh(textHolder);
			}
		});
	});
}

// custom map init
function initCustomMap() {
	jQuery('.map-holder').each(function() {
		const holder = jQuery(this);
		const zoom = holder.data('zoom') || 16;

		holder.customMap({
			zoom,
			coordinates: 'data-coordinates',
			stylesAttr: 'data-styles'
		});
	});
}

function initAjaxForm() {
	jQuery(document).on('wpformsAjaxSubmitSuccess', function(event, formData) {
		const form = jQuery('<div>').html(formData.data.confirmation);
		const fieldBlock = form.find('.fields');

		if (fieldBlock.length && window.utag) {
			const fieldData = JSON.parse(fieldBlock.text());

			if (!fieldData) return;

			const id = fieldData.fields['data-id'];
			const email = fieldData.fields['data-email'];
			const phone = fieldData.fields['data-phone'];
			const firstName = fieldData.fields['data-firstname'];
			const lastName = fieldData.fields['data-lastname'];
			let customer_email = '';
			let customer_phone = '';
			let customer_first_name = '';
			let customer_last_name = '';

			if (email) {
				// lowercased and trimmed, then SHA256 hashed*
				customer_email = sha256(email.toLowerCase().trim());
			}

			if (phone) {
				// digits only (1-9), no country code, then SHA256 hashed*
				customer_phone = sha256(phone.toLowerCase().trim().replace(/[^\w\s]/gi, '').replace(/\s+/g, ''));
			}

			if (firstName) {
				// lowercased and all whitespace/special chars removed, then SHA256 hashed*
				customer_first_name = sha256(firstName.toLowerCase().trim().replace(/[^\w\s]/gi, '').replace(/\s+/g, ''));
			}

			if (lastName) {
				// lowercased and all whitespace/special chars removed, then SHA256 hashed*
				customer_last_name = sha256(lastName.toLowerCase().trim().replace(/[^\w\s]/gi, '').replace(/\s+/g, ''));
			}

			window.utag.link({
				tealium_event: id,
				customer_email: customer_email,
				customer_phone: customer_phone,
				customer_first_name: customer_first_name,
				customer_last_name: customer_last_name
			});
		}
	});
}

function initSpinPopup() {
	let isMobile = false;
	let additionalClass = '';

	ResponsiveHelper.addRange({
		'..767': {
			on: () => {
				isMobile = true;
			}
		},
		'768..': {
			on: () => {
				isMobile = false;
			}
		}
	});

	jQuery('.detail-section').each(function() {
		const holder = jQuery(this);
		const vin = holder.find('.detail-info .vin');
		const btnSpin = holder.find('.btn-spin').hide();

		if (!btnSpin.length) return;

		if (btnSpin.hasClass('spin-evo')) {
			const evoModal = jQuery(`${btnSpin.attr('href')}`);

			if (evoModal) {
				btnSpin.attr('data-type', 'inline');
				btnSpin.show();
				additionalClass = 'evo';
			}
		}

		if (!vin.length) return;

		const fullVin = vin.text().trim();

		if (btnSpin.hasClass('spin-impel')) {
			const auth = btnSpin.data('auth');
			const clientID = btnSpin.data('clientid');

			if (auth !== undefined && clientID !== undefined) {
				const url = `https://wa-detection-api.spincar.com/?auth=${auth}&cid=${clientID}&vin=${fullVin}`;

				jQuery.ajax({
					url: url,
					type: 'GET',
					dataType: 'json',
					success: function(data) {
						if (data.url) {
							btnSpin.show().attr('data-src', data.url + '#!hidecarousel!disabledrawer');
							additionalClass = 'impel';
						}
					},
					error: function(data) {
						if (data.statusText === 'error') {
							console.error('Error:', data.statusText);
						}
					}
				});
			}
		} else if (btnSpin.hasClass('spin-autoexact')) {
			const url = `https://s3.amazonaws.com/cdn.360booth.com/player_s1.html?vin=${fullVin}`;
			const checkingURL = `https://s3.amazonaws.com/photos.autoexact.com/photos/${fullVin.slice(0, 9)}/${fullVin}_data.json`;

			checkURL(checkingURL, function() {
				btnSpin.show().attr('data-src', url);
				additionalClass = 'autoexact';
			});
		} else if (btnSpin.hasClass('spin-lesa')) {
			const postfix = isMobile ? '' : '&full_size=1';
			const url = `https://player1.lesautomotive.com/?mode=vdp&vin=${fullVin}${postfix}`;

			checkURL(url, function() {
				btnSpin.show().attr('data-src', url);
				additionalClass = 'lesa';
			});
		} else if (btnSpin.hasClass('spin-video')) {
			const videoURL = btnSpin.data('url');

			if (videoURL && videoURL !== '') {
				btnSpin.show().attr('data-src', videoURL).addClass('video-link');
				additionalClass = 'video';
			}
		} else if (btnSpin.hasClass('spin-autoport')) {
			const url = 'https://dev-api.dealerimagepro.com/sandbox/assets';

			const headers = {
				'Content-Type': 'application/json',
				Authorization: 'Bearer YOUR_ACCESS_TOKEN'
			};

			const data = {
				autoport_id: 37,
				vin: fullVin,
				limit: 50,
				offset: 0,
				image_type: 'webp'
			};

			fetch(url, {
				method: 'POST',
				headers: headers,
				body: JSON.stringify(data)
			})
				.then(response => {
					if (!response.ok) {
						throw new Error(`HTTP error! status: ${response.status}`);
					}

					return response.json();
				})
				.then(data => {
					btnSpin.show().attr('data-src', data.data[0].insta360).removeAttr('data-type');
				})
				.catch(error => {
					console.error('Error:', error.message);
				});
		}
	});

	jQuery('a.btn-spin[data-fancybox]').fancybox({
		parentEl: 'body',
		margin: [50, 0],
		backFocus: false,
		iframe: {
			tpl: '<iframe id="fancybox-frame{rnd}" name="fancybox-frame{rnd}" class="fancybox-iframe" allow="autoplay; fullscreen" src=""></iframe>',
			preload: false
		},
		beforeShow: function() {
			jQuery('.fancybox-container').addClass(`spin-360 ${additionalClass}`);
		},
		afterShow: function() {
			// Trigger window resize for update iframes
			setTimeout(() => {
				jQuery(window).trigger('resize');
			}, 200);
		}
	});

	function checkURL(url, successCallback) {
		const request = new XMLHttpRequest();

		request.open('GET', url, true);

		request.onreadystatechange = function() {
			if (request.readyState === 4) {
				if (request.status !== 404) {
					successCallback();
				} else {
					console.error('Error:', request.statusText);
				}
			}
		};

		request.send();
	}
}

// products filtering init
function initProductsFiltration() {
	if (jQuery('.filter-section').length) {
		window.history.scrollRestoration = 'manual';
	}

	if (jQuery('.detail-section').length && !sessionStorage.getItem('fromProductPage')) {
		sessionStorage.removeItem('fromProductPage');
	}

	jQuery('.filter-section').filteringProducts({
		container: '.card-wrapp .row, .card-wrapp .d-grid-row',
		items: '.col-sm-6',
		filterHolder: '.filter-list',
		itemsPerPage: 20,
		delay: 0,
		onInit: function() {
			this.holder.on('click', '.card-body > a', () => {
				sessionStorage.setItem('fromProductPage', 'true');
				sessionStorage.setItem('scrollPosition', window.scrollY);
			});
		},
		onDataLoad: function() {
			initAutocomplete();
		},
		onLoadItems: function(items) {
			initFunctions(items);
			initUnlockSavings();
			initRemoveEmptyItems();
		}
	});

	function initFunctions(items) {
		if (window.blockFrame) {
			window.blockFrame.GetDealerInfoForMiniTools();
		}

		items.find('.detail-slider-holder').each(function() {
			const holder = jQuery(this);
			const slider = holder.find('.detail-slider');

			if (slider.length) {
				const slides = slider.find('.slide');

				if (slides.length <= 1) return;

				slider.slick({
					slidesToScroll: 1,
					rows: 0,
					infinite: false,
					prevArrow: '<button class="slick-control slick-prev slick-arrow" aria-label="Previous"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="m432-480 156 156q11 11 11 28t-11 28q-11 11-28 11t-28-11L348-452q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l184-184q11-11 28-11t28 11q11 11 11 28t-11 28L432-480Z"/></svg></button>',
					nextArrow: '<button class="slick-control slick-next slick-arrow" aria-label="Next"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M504-480 348-636q-11-11-11-28t11-28q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L404-268q-11 11-28 11t-28-11q-11-11-11-28t11-28l156-156Z"/></svg></button>',
					focusOnSelect: true
				});
			}
		});
	}
}

function initUnlockSavings() {
	jQuery('[data-target="#buttonModal"], [data-target="#unlockSavingsModal"], [data-target="#Disclosure_loan-acf"], [data-target="#Disclosure_lease-acf"], [data-target="#Disclosure_Cash-acf"]').each(function() {
		const unlockButton = jQuery(this);

		if (!unlockButton.data('is-init')) {
			unlockButton.data('is-init', true);

			unlockButton.on('click', function(e) {
				e.preventDefault();
				unlockHandler(unlockButton);
			});
		}
	});

	jQuery('#unlockSavingsModal, #contactModal, #buttonModal').on('shown.bs.modal', function() {
		setTimeout(() => {
			// Write the user journey to the input from the local storage
			updateJourneyField();
		}, 500);
	});

	jQuery('.wpforms-render-modern').each(function() {
		const form = jQuery(this);
		const btnSubmit = form.find('.wpforms-page-button');

		if (!btnSubmit.length) return;

		btnSubmit.on('click', () => {
			// Wait for the last page to be shown
			setTimeout(() => {
				initTwillioAPI(form, false);
			}, 100);
		});
	});

	function unlockHandler(button) {
		const post_id = button.data('post') || ''; // some buttons have post_id
		const form_id = button.data('form') || ''; // some buttons have form
		const target = button.data('target');

		if (form_id && target === '#buttonModal') {
			const linkText = button.text().trim();
			const dataTitleElement = jQuery('[data-title]');
			if (dataTitleElement.length) {
				dataTitleElement.text(linkText);
			}
		}

		if (!target) return;

		jQuery('#savings-form .wpforms-page-1').show(); // show first step
		jQuery('#savings-form .wpforms-page-2').hide(); // hide second step

		// For button on listing and offers pages
		if (target === '#unlockSavingsModal') {
			sendRequest('unlock_form', post_id, form_id, target, '#savings-form');
		}

		if (target === '#buttonModal') {
			sendRequest('unlock_form', post_id, form_id, target, '#button-form');
		}

		// For buttons on homepage
		if (target === '#Disclosure_loan-acf' || target === '#Disclosure_lease-acf' || target === '#Disclosure_Cash-acf') {
			sendRequest('offers_form', post_id, form_id, target, '.modal-offer .wpforms-ajax-form');
		}
	}

	/**
	 * Sends an AJAX request with the given action and post ID.
	 *
	 * @param {string} action - The action to be performed.
	 * @param {number} post_id - The ID of the post to be used in the request.
	 * @param {string} form_id - The ID of the form to be used in the request.
	 * @param {string} target - The selector for the target form.
	 * @param {string} formSelector - The selector for the form element.
	 */
	function sendRequest(action, post_id, form_id, target, formSelector) {
		jQuery.ajax({
			url: window.location.href,
			type: 'POST',
			data: {
				action: action,
				form_id: form_id,
				post_id: post_id,
				target: target
			},
			success: function(response) {
				const tempDiv = jQuery('<div>').html(response);
				const form = jQuery(formSelector);

				// Write the post ID into the hidden input
				// tempDiv.find('.post-id input').val(post_id);
				jQuery('.post-id input').val(post_id);

				// Replace the form with the new one
				form.html(tempDiv.children());

				// Write the user journey to the input from the local storage
				updateJourneyField();

				// Handle the form pages
				initFormPagesHandler(form);
			}
		});
	}
}

/* Form pages handler */
function initFormPagesHandler(form) {
	const btnSubmit = document.querySelector('.wpforms-page-button');
	const lastPage = document.querySelector('.wpforms-page.last');

	if (!btnSubmit) return;

	btnSubmit.classList.remove('wpforms-disabled');
	btnSubmit.removeAttribute('aria-disabled');

	btnSubmit.addEventListener('click', () => {
		// Wait for the last page to be shown
		setTimeout(() => {
			if (lastPage && lastPage.offsetParent !== null) {
				initTwillioAPI(form);
			}
		}, 100);
	});
}

function initTwillioAPI(form, isHideStep = true) {
	console.log('Initializing Twillio API for SMS verification');
	const phoneField = form.find('div.sms-phone input[name*="wpforms[fields]"]');
	const codeField = form.find('div.sms-code input[name*="wpforms[fields]"]');
	const requestIdField = form.find('div.sms-request-id input[name*="wpforms[fields]"]');
	const phone = formatPhoneNumber(phoneField.val());
	const submitBtn = form.find('.wpforms-submit');
	let isSendingCode = false;

	console.log('WPForms SMS Verification Script Loaded');
	console.log('Form found:', form.length ? 'Yes' : 'No');

	if (!phone) {
		form.find('.sms-message').remove();
		codeField.closest('.wpforms-field').after('<div class="sms-message wpforms-error">Error: Phone number not provided</div>');
		switchToFirstFormStep(form);

		return;
	}

	if (isSendingCode) {
		console.log('SMS already being sent, ignoring');

		return;
	}

	isSendingCode = true;

	submitBtn.hide();

	form.find('.sms-message').remove();

	jQuery.ajax({
		url: window.wpforms_settings ? window.wpforms_settings.ajaxurl : window.location.href,
		type: 'POST',
		data: {
			action: 'send_sms_for_verification',
			phone: phone
		},
		success: function(response) {
			isSendingCode = false;

			if (response.success) {
				submitBtn.show();
				submitBtn.text('Validate');

				requestIdField.val(response.data.requestId);

				codeField.closest('.wpforms-field').after('<div class="sms-message wpforms-notice">SMS has been sent to ' + phone + '</div>');
			} else {
				codeField.closest('.wpforms-field').after('<div class="sms-message wpforms-notice">SMS sending error: ' + (response.data.message || 'Please try again') + '</div>');
				switchToFirstFormStep(form);
			}
		},
		error: function(xhr, status, error) {
			isSendingCode = false;
			console.error('SMS AJAX Error:', status, error);

			codeField.closest('.wpforms-field').after('<div class="sms-message wpforms-notice">Server error while sending SMS</div>');
			switchToFirstFormStep(form);
		}
	});
}

function switchToFirstFormStep(form) {
	setTimeout(function() {
		form.find('.wpforms-page-1').show();
		form.find('.wpforms-page-2').hide();

		console.log('Returned to first page due to SMS error');
	}, 3000);
}

function formatPhoneNumber(phone) {
	const digits = phone.replace(/\D/g, '');

	if (digits.length === 10) {
		return `+1${digits}`;
	} else if (digits.length === 11 && digits.startsWith('1')) {
		return `+${digits}`;
	} else if (digits.startsWith('00')) {
		return `+${digits.slice(2)}`;
	} else if (digits.startsWith('+')) {
		return digits;
	} else {
		return `+${digits}`;
	}
}

/* Filtering Products plugin */
(function($) {
	function FilteringProducts(options) {
		this.options = $.extend({
			container: '.items-holder',
			items: '.item',
			btnFilter: '[type="submit"]',
			searchField: '.search-panel [type="search"]',
			sortSelect: '[name="sort"]',
			rangeFields: '.aside [type="range"]',
			activeClass: 'active',
			hiddenClass: 'hidden',
			loadingClass: 'loading',
			disablesClass: 'disabled',
			noResultsClass: 'no-results',
			selectedFiltersHolder: '.selected-filters-list',
			pagingHolder: '.pagination-holder',
			currPageCountField: '.curr-page',
			btnPrev: '.btn-prev',
			btnNext: '.btn-next',
			btnReset: '.btn-reset',
			btnClear: '.modal-filter .btn-clear',
			totalPageCountField: '.total-pages',
			currResultsCount: '.result-current',
			totalResultsCount: '.result-total',
			itemsPerPage: 10,
			delay: 500
		}, options);

		if (this.options.holder) {
			this.init();
			this.makeCallback('onInit', this);
		}
	}

	FilteringProducts.prototype = {
		init: function() {
			this.win = $(window);
			this.page = $('html, body');
			this.header = $('#header');
			this.holder = $(this.options.holder);
			this.container = this.holder.find(this.options.container);
			this.visualSlider = this.holder.find('.visual-slider-srp');
			this.visualSliderItems = this.visualSlider.find('[data-key-word]');
			this.modalFilterHolder = $('.modal-filter-form');
			this.modalFilterLists = this.modalFilterHolder.find('.modal-filter-list');
			this.modalRanges = this.modalFilterHolder.find('[type="range"]');
			this.filterLists = this.modalFilterHolder.find('[data-filter-category]');
			this.modalBtnClear = $(this.options.btnClear);
			this.btnReset = this.holder.find(this.options.btnReset);
			this.rangeFields = this.holder.find(this.options.rangeFields);
			this.searchField = this.holder.find(this.options.searchField).val('');
			this.sortSelect = this.holder.find(this.options.sortSelect);
			this.selectedFiltersHolder = this.holder.find(this.options.selectedFiltersHolder);
			this.pagingHolder = this.holder.find(this.options.pagingHolder).addClass(this.options.hiddenClass);
			this.btnPrev = this.holder.find(this.options.btnPrev).addClass(this.options.disablesClass);
			this.btnNext = this.holder.find(this.options.btnNext);
			this.totalPageCountField = this.holder.find(this.options.totalPageCountField);
			this.currPageCountField = this.holder.find(this.options.currPageCountField);
			this.currentPage = 1;
			this.totalPages = 1;
			this.isGivenPage = false;
			this.currResultsCount = this.holder.find(this.options.currResultsCount);
			this.totalResultsCount = $(this.options.totalResultsCount);
			this.filterTemplate = this.modalFilterHolder.data('template');
			this.products = {};
			this.allProducts = {};
			this.filters = {};
			this.activeFilters = {};
			this.activeItems = {};
			this.modalActiveFilters = {};
			this.activeCheckboxCounts = {};
			this.rangesMinMaxValues = {};
			this.currentFilterType = '';
			this.addFilters = {};
			this.ajaxBusy = false;
			this.isItemsLoaded = false;
			this.isSyncingFromUrl = false;
			this.count = 0;
			this.mileageKey = 'mileage';

			// Get extra filter type and value from holder data attributes
			this.extraFilterType = this.holder.data('key');
			this.extraFilterValue = this.holder.data('value');

			let vehiclesURL = this.holder.data('vehicles');

			if (vehiclesURL) {
				this.holder.addClass(this.options.loadingClass);

				const sortType = getUrlAttr('sort').toLocaleLowerCase();

				if (sortType === 'custom') { // sort by custom tag
					if (vehiclesURL.indexOf('?') >= 0) {
						vehiclesURL += '&sort=custom';
					} else {
						vehiclesURL += '?sort=custom';
					}
				} else if (sortType === 'unique') { // sort by unique
					if (vehiclesURL.indexOf('similar=') >= 0) {
						vehiclesURL = vehiclesURL.replace('similar=false', 'similar=true');
					} else {
						if (vehiclesURL.indexOf('?') >= 0) {
							vehiclesURL += '&similar=true';
						} else {
							vehiclesURL += '?similar=true';
						}
					}
				}

				jQuery.getJSON(vehiclesURL, ({vehicles}) => {
					this.allProducts = vehicles;
					console.log(this.allProducts);

					// set values if payment or price not filled
					for (let i = 0; i < this.allProducts.length; i++) {
						this.allProducts[i].payment = this.allProducts[i].payment || 0;
						this.allProducts[i].price = this.allProducts[i].price || 0;
					}

					this.products = this.allProducts;
					this.togglePaymentRangeVisibility();

					this.makeCallback('onDataLoad', vehicles);
					this.afterLoadData();
				});
			}

			if (this.visualSlider.length) {
				this.initSlider();
			}
		},
		initSlider: function() {
			if (!this.visualSlider.hasClass('slick-initialized')) {
				this.visualSlider.slick({
					slide: '[data-key-word]:not(.hidden)',
					slidesToScroll: 1,
					rows: 0,
					prevArrow: '<button class="slick-control slick-prev slick-arrow" aria-label="Previous"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="m432-480 156 156q11 11 11 28t-11 28q-11 11-28 11t-28-11L348-452q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l184-184q11-11 28-11t28 11q11 11 11 28t-11 28L432-480Z"/></svg></button>',
					nextArrow: '<button class="slick-control slick-next slick-arrow" aria-label="Next"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M504-480 348-636q-11-11-11-28t11-28q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L404-268q-11 11-28 11t-28-11q-11-11-11-28t11-28l156-156Z"/></svg></button>',
					dots: true,
					dotsClass: 'slick-dots',
					autoplay: true
				});
			}
		},
		destroySlider: function() {
			if (this.visualSlider.hasClass('slick-initialized')) {
				this.visualSlider.slick('destroy');
			}
		},
		afterLoadData: function() {
			const location = decodeURI(window.location.search);

			// filter by specific categories from URL
			if (location !== '' || location !== '?') {
				const filters = location.slice(location.indexOf('?') + 1);
				let specialFilterArr = [];

				if (filters !== '') {
					specialFilterArr = filters.split('&');
				}

				for (let i = 0; i < specialFilterArr.length; i++) {
					const itemArr = specialFilterArr[i].split('=');
					const name = itemArr[0].toLowerCase();

					if (name.indexOf('certified') === 0 || name.indexOf('dealer-special') === 0) {
						let key = '';

						if (name.indexOf('certified') === 0) {
							key = 'certified';
						} else if (name.indexOf('dealer-special') === 0) {
							key = 'dealer-special';
						}

						this.addFilters = {
							...this.addFilters,
							[key]: itemArr[1]
						};
					}
				}

				// Filter by specific categories from URL (certified or dealer-special)
				$.each(this.addFilters, (key, value) => {
					this.products = this.products.filter((item) => {
						if (!item.terms[key]) return false;

						const termValue = item.terms[key];

						// Split values by comma and trim
						const values = value.split(',').map((v) => {
							return this.trimText(v).toLowerCase();
						});

						// If term value is an array
						if (Array.isArray(termValue)) {
							return values.some((v) => termValue.some((term) => this.trimText(term).toLowerCase() === v));
						}

						// If term value is a string
						const text = this.trimText(termValue).toLowerCase();

						return values.some((v) => v === text);
					});
				});
			}

			this.activeItems = this.products;

			this.togglePaymentRangeVisibility();
			this.getAllFilters();
			this.createFilterCheckboxes();
			this.attachEvents();

			// set default range values
			this.rangeFields.add(this.modalRanges).each((i, item) => {
				const field = $(item);
				const input = field.closest('.range-box').find('[type="hidden"]');
				const name = input.attr('name');

				// hide range if min and max value are the same
				if (this.rangesMinMaxValues[this.mileageKey] && this.rangesMinMaxValues[this.mileageKey][0] === this.rangesMinMaxValues[this.mileageKey][1] && name === 'mileage') {
					if (this.holder.find('[data-tab="tab-mileage"]').parent().length) {
						this.holder.find('[data-tab="tab-mileage"]').parent().hide();
					}
				}

				if (this.rangesMinMaxValues[name]) {
					if (this.currentFilterType === name) return;

					const rangeAPI = jcf.getInstance(field);

					field.attr('value', this.rangesMinMaxValues[name][0] + ',' + this.rangesMinMaxValues[name][1]);
					field.attr('min', this.rangesMinMaxValues[name][0]);
					field.attr('max', this.rangesMinMaxValues[name][1]);

					rangeAPI.destroy();
					jcf.replace(field);
					field.trigger('input');
				}
			});

			// apply searching after page loaded
			if (getUrlAttr('search')) {
				let searchValue = decodeURI(getUrlAttr('search'));

				searchValue = searchValue.replace(/\+/g, ' ');

				this.searchField.val(searchValue);
			}

			// apply sorting after page loaded
			if (getUrlAttr('sort')) {
				this.sortSelect.val(getUrlAttr('sort'));

				jcf.refresh(this.sortSelect);
			}

			let filterArr = [];

			// set checked state for selected filters from URL
			if (location !== '' || location !== '?') {
				const filters = location.slice(location.indexOf('?') + 1);

				if (filters !== '') {
					filterArr = filters.split('&');
				}

				for (let i = 0; i < filterArr.length; i++) {
					const itemArr = filterArr[i].split('=');
					const name = itemArr[0].toLowerCase();
					const filters = itemArr[1].split(',');

					for (let i = 0; i < filters.length; i++) {
						const value = filters[i].toLowerCase();
						const field = $(`[name="${name}"][value="${value}"]`);

						if (field.length && field.is(':checkbox')) {
							field.prop('checked', true);
							this.updateFilterStates(field, true);
						}
					}

					if (name === 'value' || name === 'payment' || name === 'mileage') {
						const input = $(`[name="${name}"]`);

						if (input.length) {
							const rangeField = input.closest('.range-box').find('[type="range"]');

							rangeField.data('isChanged', true);
							jcf.getInstance(rangeField).values = itemArr[1].split(',');
							rangeField.trigger('input');
							jcf.refresh(rangeField);
							this.updateFilterStates(rangeField, true);
						}
					}

					if (name === 'page') {
						this.currentPage = parseInt(itemArr[1]) || 1;
					}
				}

				this.isGivenPage = this.currentPage === 1 ? false : true;
				this.isSyncingFromUrl = filterArr.length > 0;

				this.filter();
				this.isSyncingFromUrl = false;

				setTimeout(() => {
					this.goToHistoryPage();

					if (sessionStorage.getItem('fromProductPage') === 'true') {
						const scrollPosition = sessionStorage.getItem('scrollPosition');

						sessionStorage.removeItem('fromProductPage');

						if (scrollPosition) {
							window.scrollTo(0, parseInt(scrollPosition));
							sessionStorage.removeItem('scrollPosition');
						}
					}

					this.isGivenPage = false;
				}, 100);
			} else {
				this.loadItems(this.activeItems.slice(0, this.options.itemsPerPage));
			}
		},
		togglePaymentRangeVisibility: function() {
			const paymentRows = this.holder.find('.filter-row').has('[name="payment"]');

			if (!paymentRows.length) return;

			const hasPayment = Array.isArray(this.allProducts) && this.allProducts.some((item) => {
				if (!item) return false;

				const value = parseFloat(item.payment);

				return !Number.isNaN(value) && value > 0;
			});

			if (hasPayment) {
				paymentRows.show();
			} else {
				paymentRows.hide();
			}
		},
		attachEvents: function() {
			this.searchField.on('keyup onSearch', (e) => {
				if (e.keyCode === 13) {
					if (this.ajaxBusy) return;

					this.resetForm(false);
					this.updateHistory(this.searchField);
					this.filter();
				}
			});

			this.sortSelect.on('change', (e) => {
				if (this.ajaxBusy) return;

				const value = this.sortSelect.val().trim().toLowerCase();

				this.isGivenPage = true;

				this.updateHistory($(e.target));

				if (value === 'unique' || value === 'all') {
					// reload page if "unique" or "all" selected
					window.location.reload();
				} else {
					this.filter();
				}

				this.isGivenPage = false;
			});

			this.filterCheckboxes.on('change', (e) => {
				if (this.ajaxBusy) return;

				this.updateFilterStates($(e.target));
				this.filter();
			});

			this.rangeFields.add(this.modalRanges).on('change', (e) => {
				if (this.ajaxBusy) return;
				const field = $(e.target);
				const input = field.closest('.range-box').find('[type="hidden"]');
				const value = input.val().split(',');

				// set "isChanged" if field has been changed manually
				field.data('isChanged', true);

				this.updateFilterStates(input);

				if (value[0] === field.attr('min') && value[1] === field.attr('max')) {
					this.updateHistory(input, true);
				}

				this.filter();
			});

			this.filter = () => {
				this.holder.removeClass(this.options.noResultsClass);
				this.searchItems();
				this.filterItems(false);
				this.createFiltersList();
			};

			this.selectedFiltersHolder.on('click', '.remove', (e) => {
				e.preventDefault();

				const btnRemove = $(e.currentTarget);
				const field = $(btnRemove.data('field'));
				const parent = btnRemove.closest('li');

				if (field.is(':checkbox')) {
					parent.remove();
					field.prop('checked', false);
					field.trigger('change');
				} else if (field.attr('type') === 'range') {
					const input = field.closest('.range-box').find('[type="hidden"]');

					parent.remove();

					field.val(field.attr('min').toString().trim() + ',' + field.attr('max').toString().trim());
					field.trigger('input');
					input.val('');
					field.trigger('change');

					jcf.getInstance(field).values = [field.attr('min'), field.attr('max')];

					setTimeout(() => {
						jcf.refresh(field);
					}, 200);
				}

				if (!this.ajaxBusy) {
					this.filter();
				}
			});

			// Set extra filter from data attributes on this.holder
			if (this.extraFilterType && this.extraFilterValue) {
				const extraFilter = this.modalFilterHolder.find(`[name="${this.trimText(this.extraFilterType)}"][value="${this.trimText(this.extraFilterValue)}"]`);

				if (extraFilter.length) {
					extraFilter.prop('checked', true);
					extraFilter.trigger('change');
				}
			}

			this.selectedFiltersHolder.on('click', '.btn-clear', (e) => {
				e.preventDefault();
				// clear search field
				this.searchField.val('');
				this.resetForm();
			});

			this.modalBtnClear.add(this.btnReset).on('click', (e) => {
				e.preventDefault();
				this.resetForm();
			});

			this.btnPrev.on('click', (e) => {
				e.preventDefault();
				this.prevPage();
			});

			this.btnNext.on('click', (e) => {
				e.preventDefault();
				this.nextPage();
			});

			this.win.on('goToPage', () => {
				this.currentPage = getUrlAttr('page');

				this.goToHistoryPage();
			});
		},
		goToHistoryPage: function() {
			if (this.currentPage < 1) {
				this.currentPage = 1;
			}

			if (this.currentPage >= this.totalPages) {
				this.currentPage = this.totalPages;
			}

			this.switchPage();
		},
		prevPage: function() {
			if (this.ajaxBusy) return;

			this.currentPage = this.currentPage - 1;

			if (this.currentPage < 1) {
				this.currentPage = 1;
			}

			this.switchPage();

			this.page.animate({
				scrollTop: this.holder.offset().top
			}, 500);
		},
		nextPage: function() {
			if (this.ajaxBusy) return;

			this.currentPage = this.currentPage + 1;

			if (this.currentPage >= this.totalPages) {
				this.currentPage = this.totalPages;
			}

			this.switchPage();

			this.page.animate({
				scrollTop: this.holder.offset().top - this.header.outerHeight()
			}, 500);
		},
		switchPage: function() {
			this.count = (this.currentPage - 1) * this.options.itemsPerPage;

			this.loadItems(this.activeItems.slice(this.count, this.count + this.options.itemsPerPage));
		},
		updatePagingButtonsState: function() {
			if (this.currentPage === 1) {
				this.btnPrev.addClass(this.options.disablesClass);
			} else {
				this.btnPrev.removeClass(this.options.disablesClass);
			}

			if (this.currentPage >= this.totalPages) {
				this.btnNext.addClass(this.options.disablesClass);
			} else {
				this.btnNext.removeClass(this.options.disablesClass);
			}
		},
		getAllFilters: function() {
			this.activeCheckboxCounts = {};
			this.rangesMinMaxValues = {};

			for (const item of this.activeItems) {
				for (const property in item.terms) {
					// create array of all checkboxes for setting counters
					if (!this.activeCheckboxCounts[property]) {
						this.activeCheckboxCounts[property] = [];
					}

					if (item.terms[property]) {
						for (let i = 0; i < item.terms[property].length; i++) {
							const obj = this.activeCheckboxCounts[property].find(o => o.name === item.terms[property][i]);

							if (!obj) {
								this.activeCheckboxCounts[property].push({
									name: item.terms[property][i],
									slug: item.terms[property][i].toLowerCase(),
									count: 1
								});
							} else {
								obj.count = obj.count + 1;
							}
						}
					}
				}

				if (!this.isItemsLoaded) {
					if (item.price && item.price.length) {
						if (!this.rangesMinMaxValues.value) {
							this.rangesMinMaxValues.value = [];
						}

						if (item.price) {
							this.rangesMinMaxValues.value.push(item.price);
						}
					}

					if (item.payment && item.payment.length) {
						if (!this.rangesMinMaxValues.payment) {
							this.rangesMinMaxValues.payment = [];
						}

						if (item.payment) {
							this.rangesMinMaxValues.payment.push(item.payment);
						}
					}

					if (item.terms[this.mileageKey] && item.terms[this.mileageKey].length) {
						if (!this.rangesMinMaxValues.mileage) {
							this.rangesMinMaxValues.mileage = [];
						}

						if (item.terms[this.mileageKey][0] !== undefined) {
							this.rangesMinMaxValues.mileage.push(item.terms[this.mileageKey][0]);
						}
					}
				}
			}

			if (!this.isItemsLoaded) {
				// Get min/max values for ranges fields
				for (const key in this.rangesMinMaxValues) {
					const max = Math.max(...this.rangesMinMaxValues[key]);
					const min = Math.min(...this.rangesMinMaxValues[key]);

					this.rangesMinMaxValues[key] = [min, max];
				}

				// Sort filters alphabetically
				for (const key in this.activeCheckboxCounts) {
					sortByProperty(this.activeCheckboxCounts[key], 'name', false);
				}

				this.isItemsLoaded = true;
			}
		},
		createFilterCheckboxes: function() {
			this.filterLists.each((i, elem) => {
				const list = jQuery(elem);
				const filter = list.data('filter-category');
				const filterData = this.activeCheckboxCounts[filter];

				if (filterData && filterData.length) {
					for (let i = 0; i < filterData.length; i++) {
						const source = jQuery(this.filterTemplate).html();
						const template = Handlebars.compile(source);

						filterData[i].slug = filterData[i].slug.toLowerCase();

						list.append(template({
							...filterData[i],
							filter
						}));
					}
				}
			});

			this.filterCheckboxes = this.modalFilterHolder.find(':checkbox');
		},
		searchItems: function() {
			let value = this.trimText(this.searchField.val());

			// Replace "crv" and "hrv" with "cr-v" and "hr-v"
			if (value.indexOf('crv') >= 0) {
				value = value.replace('crv', 'cr-v');
			} else if (value.indexOf('hrv') >= 0) {
				value = value.replace('hrv', 'hr-v');
			}

			this.destroySlider();
			this.visualSliderItems.removeClass(this.options.hiddenClass);

			// Filter items in "terms object" by search value
			this.activeItems = this.products.filter((item) => {
				let matched = false;

				if (value !== '') {
					const searchWordsArr = value.toLowerCase().split(/\s+/); // Split value into words array

					// Check if all search words are included in the item
					matched = this.containsAllWords(item.terms, searchWordsArr);
				} else {
					// If search value is empty, show all items
					matched = true;
				}

				return matched;
			});

			if (value !== '') {
				this.visualSliderItems.addClass(this.options.hiddenClass).filter((i, el) => {
					const item = $(el);
					const filter = this.trimText(item.data('key-word'));

					if (filter !== '' && filter.indexOf(value) >= 0) {
						item.removeClass(this.options.hiddenClass);
					}
				});
			}

			this.filterItems((value !== '') ? true : false);
		},

		/**
		 * Check if all words in the wordsArr array are included in the terms object
		 * @param {object} terms - object with terms to search
		 * @param {array} wordsArr - array of search words
		 * @returns {boolean} - if all words are included in the terms object
		 */
		containsAllWords(terms, wordsArr) {
			const allValues = Object.values(terms)
				.flat() // Make a flat array of all values
				.map(value => {
					return value.toString().toLowerCase();
				});

			return wordsArr.every(word => {
				return allValues.some(value => {
					return value.includes(word.toLowerCase());
				});
			});
		},

		filterItems: function(ifSearchValue) {
			this.activeFilters = {};

			this.filterCheckboxes.each((i, elem) => {
				const currCheckbox = $(elem);

				if (currCheckbox.is(':checked')) {
					const value = this.trimText(currCheckbox.val());
					const category = currCheckbox.attr('name');

					this.combineFilters(category, value);
				}
			});

			this.rangeFields.add(this.modalRanges).each((i, item) => {
				const field = $(item);
				const input = field.closest('.range-box').find('[type="hidden"]');
				const value = input.val();
				const category = input.attr('name');

				// Check that range field has not been changed
				if (value !== '' && field.data('isChanged')) {
					this.combineFilters(category, value);
				}
			});

			// Add hidden class to all banner items if "model" filter is selected
			if (!ifSearchValue && Object.keys(this.activeFilters).length > 0 && Object.keys(this.activeFilters).filter(key => key.startsWith('model')).length) {
				this.visualSliderItems.addClass(this.options.hiddenClass);
			}

			$.each(this.activeFilters, (key, value) => {
				// Filter banner items by "data-key-word" attribute
				this.visualSliderItems.filter((i, el) => {
					const item = $(el);
					const filter = this.trimText(item.data('key-word'));

					// Check only "model" filter
					if (key.indexOf('model') === 0 && value.includes(filter)) {
						item.removeClass(this.options.hiddenClass);
					}
				});

				this.activeItems = this.activeItems.filter((item) => {
					let matched = false;

					if (key === 'value') { // Filter by price
						const textArr = +item.price;
						const arr = value[0].split(',');

						if (textArr !== undefined) {
							if (textArr >= +arr[0] && textArr <= +arr[1]) {
								matched = true;
							}
						}
					} else if (key === 'payment') { // Filter by payment
						const textArr = +item.payment;
						const arr = value[0].split(',');

						if (textArr !== undefined) {
							if (textArr >= +arr[0] && textArr <= +arr[1]) {
								matched = true;
							}
						}
					} else if (key === 'mileage') { // Filter by mileage
						const textArr = item.terms[this.mileageKey] ? +item.terms[this.mileageKey][0] : 0;
						const arr = value[0].split(',');

						if (textArr !== undefined) {
							if (textArr >= +arr[0] && textArr <= +arr[1]) {
								matched = true;
							}
						}
					} else {
						if (item.terms[key]) {
							const textArr = item.terms[key];

							for (let i = 0; i < textArr.length; i++) {
								if (value.includes(this.trimText(textArr[i]))) {
									matched = true;
								}
							}
						}
					}

					return matched;
				});
			});

			this.count = 0;
			this.totalPages = Math.ceil(this.activeItems.length / this.options.itemsPerPage);

			if (!this.isGivenPage) {
				this.currentPage = 1;
			}

			this.sortItems();
			this.initSlider();
			this.loadItems(this.activeItems.slice(0, this.options.itemsPerPage));
		},
		sortItems: function() {
			const sortValue = this.sortSelect.val();

			switch (sortValue) {
				case 'highest':
					sortByProperty(this.activeItems, 'price', true);
					break;
				case 'lowest':
					sortByProperty(this.activeItems, 'price', false);
					break;
				case 'highest-payment':
					sortByProperty(this.activeItems, 'payment', true);
					break;
				case 'lowest-payment':
					sortByProperty(this.activeItems, 'payment', false);
					break;
				case 'newest':
					sortByDate(this.activeItems, 'year', true);
					break;
				case 'oldest':
					sortByDate(this.activeItems, 'year', false);
					break;
				case 'dateinstock-new':
					sortByDate(this.activeItems, 'dateinstock', true);
					break;
				case 'dateinstock-old':
					sortByDate(this.activeItems, 'dateinstock', false);
					break;
				case 'priority':
					sortByProperty(this.activeItems, 'priority', false);
					break;
				case 'model':
					sortByProperty(this.activeItems, 'model', false);
				default:
					break;
			}
		},
		loadItems: function(items) {
			this.updateResultCounters();
			this.updateFilterValues();
			this.updatePagingButtonsState();

			if (this.activeItems.length === 0) {
				this.holder.addClass(this.options.noResultsClass);
			}

			this.ajaxBusy = true;
			this.holder.addClass(this.options.loadingClass);
			this.container.empty();

			for (let i = 0; i < items.length; i++) {
				// Append items to container
				jQuery(items[i].html).appendTo(this.container);
			}

			this.makeCallback('onLoadItems', this.container.find(this.options.items));

			setTimeout(() => {
				this.holder.removeClass(this.options.loadingClass);
				this.ajaxBusy = false;
			}, this.options.delay);
		},
		updateFilterStates: function(item, ifOnPageLoad) {
			let name = item.attr('name');
			let value = item.val();
			let match = true;

			// this.currentFilterType should be empty when page is loading
			if (!ifOnPageLoad) {
				this.currentFilterType = name;
			}

			if (item.is(':checkbox')) {
				if (item.is(':checked')) {
					if (this.modalActiveFilters[name]) {
						this.modalActiveFilters[name].push(value);
						this.modalActiveFilters[name] = [...new Set(this.modalActiveFilters[name])];
					} else {
						this.modalActiveFilters[name] = [value];
					}
				} else {
					if (this.modalActiveFilters[name]) {
						const currValue = this.modalActiveFilters[name];

						for (let i = 0; i < currValue.length; i++) {
							if (currValue[i] === value) {
								currValue.splice(i, 1);
							}
						}

						this.modalActiveFilters[name] = currValue;
					}
				}
			} else {
				const field = item.closest('.range-box').find('[type="hidden"]');

				name = field.attr('name');
				value = field.val();

				this.modalActiveFilters[name] = value;
			}

			setTimeout(() => {
				this.modalRanges.each((i, elem) => {
					const field = $(elem);
					const input = field.closest('.range-box').find('[type="hidden"]');

					if (input.val() !== '' && field.attr('value') !== input.val()) {
						match = false;
					}
				});

				if (!match) {
					this.modalBtnClear.removeAttr('disabled');
				} else {
					if (this.filterCheckboxes.filter(':checked').length) {
						this.modalBtnClear.removeAttr('disabled');
					} else {
						this.modalBtnClear.attr('disabled', true);
					}
				}
			}, 100);
		},
		updateFilterValues: function() {
			this.getAllFilters();

			if (Object.keys(this.activeCheckboxCounts).length) {
				this.filterCheckboxes.each((i, elem) => {
					const item = $(elem);
					const value = item.val();
					const parent = item.closest('li');
					const countItem = parent.find('.detail-count');
					const name = item.attr('name');

					if (this.currentFilterType === name) return;

					countItem.text(0);

					if (this.activeCheckboxCounts[name]) {
						for (let i = 0; i < this.activeCheckboxCounts[name].length; i++) {
							if (this.activeCheckboxCounts[name][i].name.toLowerCase() === value) {
								countItem.text(this.activeCheckboxCounts[name][i].count);
							}
						}
					}

					if (+countItem.text() === 0) {
						if (!item.is(':checked')) {
							parent.hide();
						}
					} else {
						parent.show();
					}
				});
			}

			// Hide modal filters if there is no one of active filter
			this.modalFilterLists.each((i, item) => {
				const list = $(item);
				const parent = list.closest('[data-id]');
				const ID = parent.attr('data-id');
				const visibleItems = list.find('>li').filter((i, item) => $(item).css('display') !== 'none');
				const modalBtn = this.holder.find(`[data-tab="${ID}"]`);

				if (visibleItems.length === 0) {
					modalBtn.parent().hide();
				} else {
					modalBtn.parent().show();
				}
			});
		},
		createFiltersList: function() {
			this.filtersArr = [];
			this.selectedFiltersHolder.empty();

			this.getAllFilters();
			this.updateHistory();

			this.filterCheckboxes.each((i, elem) => {
				const currCheckbox = $(elem);
				const currName = currCheckbox.siblings('.fake-label').find('.name');

				if (currCheckbox.is(':checked')) {
					this.filtersArr.push({
						field: currCheckbox,
						title: currName.text(),
						name: currCheckbox.val()
					});
				}
			});

			this.modalRanges.each((i, elem) => {
				const field = $(elem);
				const input = field.closest('.range-box').find('[type="hidden"]');

				if (input.val() !== '' && input.val() !== undefined && getUrlAttr(input.attr('name')) !== '') {
					const value = input.val().split(',');

					if (value[0] === field.attr('min') && value[1] === field.attr('max')) {
						return;
					}

					this.filtersArr.push({
						field,
						title: value[0] + ' - ' + value[1] + ' ml',
						name: input.attr('name')
					});
				}
			});

			let items = $();
			let btnClear = $();
			let counterItem = $();

			if (!this.filtersArr.length) return;

			for (let i = 0; i < this.filtersArr.length; i++) {
				const item = $('<li><span>' + this.filtersArr[i].title + '</span><a href="#" class="remove"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M480-424 284-228q-11 11-28 11t-28-11q-11-11-11-28t11-28l196-196-196-196q-11-11-11-28t11-28q11-11 28-11t28 11l196 196 196-196q11-11 28-11t28 11q11 11 11 28t-11 28L536-480l196 196q11 11 11 28t-11 28q-11 11-28 11t-28-11L480-424Z"/></svg></a></li>').appendTo(this.selectedFiltersHolder);

				item.find('.remove').data('field', this.filtersArr[i].field);
			}

			counterItem = $('<li class="counter-item"><a href="#"><span class="num"></span><span class="hide-text">Hide</span></a></li>').hide().appendTo(this.selectedFiltersHolder);
			btnClear = $('<li class="clear-item"><a href="#" class="btn-clear">Clear all filters</a></li>').appendTo(this.selectedFiltersHolder);
			items = this.selectedFiltersHolder.find('li').not('.counter-item').not('.clear-item');

			const hideItems = () => {
				let count = 1;

				const hide = () => {
					if (btnClear.position().top > 0) {
						counterItem.show();
						items.eq(items.length - count).addClass(this.options.hiddenClass);
						counterItem.find('.num').text('+' + items.filter('.' + this.options.hiddenClass).length + ' more');
						count++;

						hide();
					}
				};

				hide();
			};

			if (this.selectedFiltersHolder.height <= 35) {
				counterItem.remove();
			} else {
				hideItems();

				counterItem.on('click', (e) => {
					e.preventDefault();

					if (!this.selectedFiltersHolder.hasClass(this.options.activeClass)) {
						this.selectedFiltersHolder.addClass(this.options.activeClass);
						items.removeClass(this.options.hiddenClass);
					} else {
						this.selectedFiltersHolder.removeClass(this.options.activeClass);

						hideItems();
					}
				});

				if (this.selectedFiltersHolder.hasClass(this.options.activeClass)) {
					this.selectedFiltersHolder.addClass(this.options.activeClass);
					items.removeClass(this.options.hiddenClass);
				}
			}
		},

		/**
		 * Syncs URL query string with current filter state
		 * Uses pushState by default and replaceState during initial URL sync
		 *
		 * @param {jQuery} [item] - Filter input that triggered the update
		 * @param {boolean} [ifClear] - When true, removes item from query
		 */
		updateHistory: function(item, ifClear) {
			if (!window.history && !window.history.pushState) return;

			// Determine if pushState or replaceState should be used
			const useReplace = this.isSyncingFromUrl === true;

			if (item) {
				if (item.is(':checkbox')) {
					if (item.is(':checked')) {
						window.StorageHistory.set(item.attr('name'), item.val(), true, useReplace);
					} else {
						window.StorageHistory.remove(item.attr('name'), item.val(), useReplace);
					}
				} else {
					if (ifClear) {
						window.StorageHistory.remove(item.attr('name'), '', useReplace);
					} else {
						window.StorageHistory.set(item.attr('name'), item.val(), false, useReplace);
					}
				}
			} else {
				// Update all active modal filters in a single history entry
				const existingQuery = window.location.search.replace(/^\?/, '');
				let params = existingQuery ? existingQuery.split('&') : [];
				const filterKeys = Object.keys(this.modalActiveFilters);
				const newParams = [];

				// Filter existing filters
				params = params.filter((pair) => {
					const name = pair.split('=')[0];

					return filterKeys.indexOf(name) === -1;
				});

				// Build new query string
				for (const key in this.modalActiveFilters) {
					const value = this.modalActiveFilters[key];

					if (typeof value === 'string') {
						if (value !== '') {
							newParams.push(key + '=' + value);
						}
					} else if (value && value.length) {
						newParams.push(key + '=' + value.join(','));
					}
				}

				const nextQuery = params.concat(newParams).join('&');

				// Do nothing if the query hasn't changed
				if (nextQuery === existingQuery) return;

				window.StorageHistory.setQuery(nextQuery, useReplace);
			}
		},
		updateResultCounters: function() {
			this.totalPages = Math.ceil(this.activeItems.length / this.options.itemsPerPage);

			// show/hide pagination
			if (this.totalPages <= 1) {
				this.pagingHolder.addClass(this.options.hiddenClass);
			} else {
				this.pagingHolder.removeClass(this.options.hiddenClass);
			}

			// total pages count
			this.totalPageCountField.text(this.totalPages);

			// current page count
			this.currPageCountField.text(this.currentPage);

			if (this.currentPage > 1) {
				window.StorageHistory.set('page', this.currentPage);
			} else {
				window.StorageHistory.remove('page');
			}

			// current showing results count
			if (this.activeItems.length === 0) {
				this.currResultsCount.text(0);
			} else {
				if (this.activeItems.length < this.count + this.options.itemsPerPage) {
					this.currResultsCount.text((this.count + 1) + '-' + this.activeItems.length);
				} else {
					this.currResultsCount.text((this.count + 1) + '-' + (this.count + this.options.itemsPerPage));
				}
			}

			// total results count
			let countText = '';
			const countTextItem = this.totalResultsCount.siblings('[data-singular-text]');

			if (countTextItem.length) {
				const singularText = countTextItem.data('singular-text') || '';
				const pluralText = countTextItem.data('plural-text') || '';

				if (this.activeItems.length === 1) {
					countText = singularText;
				} else {
					countText = pluralText;
				}

				countTextItem.text(countText);
			}

			this.totalResultsCount.text(this.activeItems.length);
		},
		resetForm: function(ifClearSearchField = true) {
			this.modalRanges.add(this.rangeFields).each((i, elem) => {
				const field = $(elem);
				const input = field.closest('.range-box').find('[type="hidden"]');

				if (field.data('isChanged')) {
					field.removeData('isChanged');
				}

				field.data('jcfInstance').values = field.attr('value').split(',');
				field.trigger('input');
				input.val('');
				jcf.refresh(field);
			});

			// reset search and sort fields
			if (ifClearSearchField) {
				this.searchField.val('');
			}

			this.sortSelect.prop('selectedIndex', 0);

			jcf.refresh(this.sortSelect);

			this.filterCheckboxes.each((i, elem) => {
				const currCheckbox = $(elem);

				currCheckbox.prop('checked', false);
			});

			this.destroySlider();
			this.visualSliderItems.removeClass(this.options.hiddenClass);
			this.initSlider();

			this.currentPage = 1;
			this.count = 0;
			this.activeFilters = {};
			this.modalActiveFilters = {};
			this.modalBtnClear.attr('disabled', true);
			this.selectedFiltersHolder.empty();

			this.activeItems = this.allProducts;

			this.loadItems(this.activeItems.slice(0, this.options.itemsPerPage));

			if (window.history && window.history.pushState) {
				window.StorageHistory.reset();
			}
		},
		trimText: function(text) {
			return text.toString().trim().toLowerCase();
		},
		combineFilters: function(category, value) {
			if (value !== 'all') {
				if (this.activeFilters[category]) {
					this.activeFilters[category].push(value);
				} else {
					this.activeFilters[category] = [];
					this.activeFilters[category].push(value);
				}
			}
		},
		makeCallback: function(name) {
			if (typeof this.options[name] === 'function') {
				const args = Array.prototype.slice.call(arguments);

				args.shift();
				this.options[name].apply(this, args);
			}
		}
	};

	function getUrlAttr(name) {
		const sectionName = name.replace(/[\[]/, '\\\[').replace(/[\]]/, '\\\]');
		const regexS = '[\\?&]' + sectionName + '=([^&#]*)';
		const regex = new RegExp(regexS);
		const results = regex.exec(window.location.href);

		if (results === null) {
			return '';
		} else {
			return results[1];
		}
	}

	function sortByProperty(array, propertyName, reverse) {
		return array.sort((a, b) => {
			let valA = a[propertyName];
			let valB = b[propertyName];

			// Sort by model
			if (propertyName === 'model') {
				valA = a.terms[propertyName]?.[0] || null;
				valB = b.terms[propertyName]?.[0] || null;

				return compareStrings(valA, valB, reverse);
			}

			// Sort by name
			if (propertyName === 'name') {
				return compareStrings(valA, valB, reverse);
			}

			return compareNumbers(valA, valB, reverse);
		});
	}

	function sortByDate(array, propertyName, reverse) {
		return array.sort((a, b) => {
			const dateA = parseDate(a[propertyName]);
			const dateB = parseDate(b[propertyName]);

			return reverse ? dateB - dateA : dateA - dateB;
		});
	}

	function compareStrings(valA, valB, reverse) {
		if (valA === null && valB === null) return 0;
		if (valA === null) return 1;
		if (valB === null) return -1;

		const a = valA.trim().toLowerCase();
		const b = valB.trim().toLowerCase();
		const comparison = a.localeCompare(b, undefined, { numeric: true });

		return reverse ? -comparison : comparison;
	}

	function compareNumbers(valA, valB, reverse) {
		const numA = parseFloat(valA);
		const numB = parseFloat(valB);

		if (numA < numB) return reverse ? 1 : -1;
		if (numA > numB) return reverse ? -1 : 1;

		return 0;
	}

	function parseDate(str) {
		if (/^\d{14}$/.test(str)) {
			// yyyyMMddHHmmss → yyyy-MM-ddTHH:mm:ss
			return new Date(
				`${str.slice(0, 4)}-${str.slice(4, 6)}-${str.slice(6, 8)}T` +
				`${str.slice(8, 10)}:${str.slice(10, 12)}:${str.slice(12, 14)}`
			);
		}

		if (/^\d{4}$/.test(str)) {
			// yyyy → yyyy-01-01T00:00:00
			return new Date(`${str}-01-01T00:00:00`);
		}

		return new Date(str);
	}

	$.fn.filteringProducts = function(opt) {
		return this.each(function() {
			$(this).data('FilteringProducts', new FilteringProducts($.extend(opt, {
				holder: this
			})));
		});
	};
}(jQuery));

/*!
 * SmoothScroll module
 */
(function($, exports) {
	// private variables
	let page,
		win = $(window),
		activeBlock, activeWheelHandler,
		wheelEvents = ('onwheel' in document || document.documentMode >= 9 ? 'wheel' : 'mousewheel DOMMouseScroll');

	// animation handlers
	function scrollTo(offset, options, callback) {
		// initialize variables
		let scrollBlock;

		if (document.body) {
			if (typeof options === 'number') {
				options = {
					duration: options
				};
			} else {
				options = options || {};
			}

			page = page || $('html, body');
			scrollBlock = options.container || page;
		} else {
			return;
		}

		// treat single number as scrollTop
		if (typeof offset === 'number') {
			offset = {
				top: offset
			};
		}

		// handle mousewheel/trackpad while animation is active
		if (activeBlock && activeWheelHandler) {
			activeBlock.off(wheelEvents, activeWheelHandler);
		}

		if (options.wheelBehavior && options.wheelBehavior !== 'none') {
			activeWheelHandler = function(e) {
				if (options.wheelBehavior === 'stop') {
					scrollBlock.off(wheelEvents, activeWheelHandler);
					scrollBlock.stop();
				} else if (options.wheelBehavior === 'ignore') {
					e.preventDefault();
				}
			};

			activeBlock = scrollBlock.on(wheelEvents, activeWheelHandler);
		}

		// start scrolling animation
		scrollBlock.stop().animate({
			scrollLeft: offset.left,
			scrollTop: offset.top
		}, options.duration, function() {
			if (activeWheelHandler) {
				scrollBlock.off(wheelEvents, activeWheelHandler);
			}

			if ($.isFunction(callback)) {
				callback();
			}
		});
	}

	// smooth scroll contstructor
	function SmoothScroll(options) {
		this.options = $.extend({
			anchorLinks: 'a[href^="#"]', // selector or jQuery object
			container: null, // specify container for scrolling (default - whole page)
			extraOffset: null, // function or fixed number
			activeClasses: null, // null, "link", "parent"
			easing: 'swing', // easing of scrolling
			animMode: 'duration', // or "speed" mode
			animDuration: 800, // total duration for scroll (any distance)
			animSpeed: 1500, // pixels per second
			anchorActiveClass: 'anchor-active',
			sectionActiveClass: 'section-active',
			wheelBehavior: 'stop', // "stop", "ignore" or "none"
			useNativeAnchorScrolling: false // do not handle click in devices with native smooth scrolling
		}, options);

		this.init();
	}

	SmoothScroll.prototype = {
		init: function() {
			this.initStructure();
			this.attachEvents();
			this.isInit = true;
		},
		initStructure: function() {
			const self = this;

			this.container = this.options.container ? $(this.options.container) : $('html,body');
			this.scrollContainer = this.options.container ? this.container : win;

			this.anchorLinks = jQuery(this.options.anchorLinks).filter(function() {
				return jQuery(self.getAnchorTarget(jQuery(this))).length;
			});
		},
		getId: function(str) {
			try {
				return '#' + str.replace(/^.*?(#|$)/, '');
			} catch (err) {
				return null;
			}
		},
		getAnchorTarget: function(link) {
			// get target block from link href
			const targetId = this.getId($(link).attr('href'));

			return $(targetId.length > 1 ? targetId : 'html');
		},
		getTargetOffset: function(block) {
			// get target offset
			let blockOffset = block.offset().top;

			if (this.options.container) {
				blockOffset -= this.container.offset().top - this.container.prop('scrollTop');
			}

			// handle extra offset
			if (typeof this.options.extraOffset === 'number') {
				blockOffset -= this.options.extraOffset;
			} else if (typeof this.options.extraOffset === 'function') {
				blockOffset -= this.options.extraOffset(block);
			}

			return {
				top: blockOffset
			};
		},
		attachEvents: function() {
			const self = this;

			// handle active classes
			if (this.options.activeClasses && this.anchorLinks.length) {
				// cache structure
				this.anchorData = [];

				for (let i = 0; i < this.anchorLinks.length; i++) {
					var link = jQuery(this.anchorLinks[i]),
						targetBlock = self.getAnchorTarget(link),
						anchorDataItem = null;

					$.each(self.anchorData, function(index, item) {
						if (item.block[0] === targetBlock[0]) {
							anchorDataItem = item;
						}
					});

					if (anchorDataItem) {
						anchorDataItem.link = anchorDataItem.link.add(link);
					} else {
						self.anchorData.push({
							link: link,
							block: targetBlock
						});
					}
				}

				// add additional event handlers
				this.resizeHandler = function() {
					if (!self.isInit) return;
					self.recalculateOffsets();
				};

				this.scrollHandler = function() {
					self.refreshActiveClass();
				};

				this.recalculateOffsets();
				this.scrollContainer.on('scroll', this.scrollHandler);
				win.on('resize.SmoothScroll load.SmoothScroll orientationchange.SmoothScroll refreshAnchor.SmoothScroll', this.resizeHandler);
			}

			// handle click event
			this.clickHandler = function(e) {
				self.onClick(e);
			};

			if (!this.options.useNativeAnchorScrolling) {
				this.anchorLinks.on('click', this.clickHandler);
			}
		},
		recalculateOffsets: function() {
			const self = this;

			$.each(this.anchorData, function(index, data) {
				data.offset = self.getTargetOffset(data.block);
				data.height = data.block.outerHeight();
			});

			this.refreshActiveClass();
		},
		toggleActiveClass: function(anchor, block, state) {
			anchor.toggleClass(this.options.anchorActiveClass, state);
			block.toggleClass(this.options.sectionActiveClass, state);
		},
		refreshActiveClass: function() {
			let self = this,
				foundFlag = false,
				containerHeight = this.container.prop('scrollHeight'),
				viewPortHeight = this.scrollContainer.height(),
				scrollTop = this.options.container ? this.container.prop('scrollTop') : win.scrollTop();

			// user function instead of default handler
			if (this.options.customScrollHandler) {
				this.options.customScrollHandler.call(this, scrollTop, this.anchorData);

				return;
			}

			// sort anchor data by offsets
			this.anchorData.sort(function(a, b) {
				return a.offset.top - b.offset.top;
			});

			// default active class handler
			$.each(this.anchorData, function(index) {
				const reverseIndex = self.anchorData.length - index - 1,
					data = self.anchorData[reverseIndex],
					anchorElement = (self.options.activeClasses === 'parent' ? data.link.parent() : data.link);

				if (scrollTop >= containerHeight - viewPortHeight) {
					// handle last section
					if (reverseIndex === self.anchorData.length - 1) {
						self.toggleActiveClass(anchorElement, data.block, true);
					} else {
						self.toggleActiveClass(anchorElement, data.block, false);
					}
				} else {
					// handle other sections
					if (!foundFlag && (scrollTop >= data.offset.top - 1 || reverseIndex === 0)) {
						foundFlag = true;
						self.toggleActiveClass(anchorElement, data.block, true);
					} else {
						self.toggleActiveClass(anchorElement, data.block, false);
					}
				}
			});
		},
		calculateScrollDuration: function(offset) {
			let distance;

			if (this.options.animMode === 'speed') {
				distance = Math.abs(this.scrollContainer.scrollTop() - offset.top);

				return (distance / this.options.animSpeed) * 1000;
			} else {
				return this.options.animDuration;
			}
		},
		onClick: function(e) {
			const targetBlock = this.getAnchorTarget(e.currentTarget),
				targetOffset = this.getTargetOffset(targetBlock);

			e.preventDefault();

			scrollTo(targetOffset, {
				container: this.container,
				wheelBehavior: this.options.wheelBehavior,
				duration: this.calculateScrollDuration(targetOffset)
			});

			this.makeCallback('onBeforeScroll', e.currentTarget);
		},
		makeCallback: function(name) {
			if (typeof this.options[name] === 'function') {
				const args = Array.prototype.slice.call(arguments);

				args.shift();
				this.options[name].apply(this, args);
			}
		},
		destroy: function() {
			const self = this;

			this.isInit = false;

			if (this.options.activeClasses) {
				win.off('resize.SmoothScroll load.SmoothScroll orientationchange.SmoothScroll refreshAnchor.SmoothScroll', this.resizeHandler);
				this.scrollContainer.off('scroll', this.scrollHandler);

				$.each(this.anchorData, function(index) {
					const reverseIndex = self.anchorData.length - index - 1,
						data = self.anchorData[reverseIndex],
						anchorElement = (self.options.activeClasses === 'parent' ? data.link.parent() : data.link);

					self.toggleActiveClass(anchorElement, data.block, false);
				});
			}

			this.anchorLinks.off('click', this.clickHandler);
		}
	};

	// public API
	$.extend(SmoothScroll, {
		scrollTo: function(blockOrOffset, durationOrOptions, callback) {
			scrollTo(blockOrOffset, durationOrOptions, callback);
		}
	});

	// export module
	exports.SmoothScroll = SmoothScroll;
}(jQuery, this));

/* Filtering plugin */
(function($) {
	function FilteringItems(options) {
		this.options = $.extend({
			items: '.item',
			filterItems: '[data-filter]',
			filterHolder: '.models-filter',
			activeClass: 'active',
			hiddenClass: 'hidden',
			noResultsClass: 'no-results'
		}, options);

		this.init();
	}

	FilteringItems.prototype = {
		init: function() {
			if (this.options.holder) {
				this.findElements();
				this.attachEvents();
				this.makeCallback('onInit', this);
			}
		},
		findElements: function() {
			this.page = $('html, body');
			this.holder = $(this.options.holder);
			this.items = this.holder.find(this.options.items);
			this.filterHolder = this.holder.find(this.options.filterHolder);
			this.filterItems = this.holder.find(this.options.filterItems);
			this.filterParents = this.filterItems.parent();
			this.activeFilter = '';
			this.activeItems = $();
		},
		attachEvents: function() {
			const self = this;

			this.filterItems.on('click', function(e) {
				e.preventDefault();
				const currItem = $(this);

				if (!self.holder.hasClass('dropdown-models')) {
					self.holder.css({
						height: self.holder.outerHeight(true),
						overflow: 'hidden',
						position: 'relative'
					});
				}

				setTimeout(function() {
					self.activeFilter = '';
					self.filterParents.removeClass(self.options.activeClass);
					currItem.parent().addClass(self.options.activeClass);
					self.activeFilter = currItem.data('filter');
					self.filtrationItems();
				}, 10);
			});
		},
		filtrationItems: function() {
			const self = this;

			this.makeCallback('beforeFilter', this);
			this.items.addClass(this.options.hiddenClass);
			this.holder.removeClass(this.options.noResultsClass);

			if (this.activeFilter !== 'all') {
				this.activeItems = this.items.filter(function(ind, item) {
					let matched = false;

					$(item).find('.' + self.filterHolder.data('filter-group')).each(function() {
						const text = self.replaceStr($(this).text());

						if (text.indexOf(self.replaceStr(self.activeFilter)) !== -1) {
							matched = true;
						}
					});

					return matched;
				});
			} else {
				this.activeItems = this.items;
			}

			this.activeItems.removeClass(this.options.hiddenClass);

			this.makeCallback('onFilter', this);

			if (!this.activeItems.length) {
				this.holder.addClass(this.options.noResultsClass);
			}

			this.holder.css({
				height: '',
				overflow: '',
				position: ''
			});
		},
		replaceStr: function(str) {
			return str.trim().toLowerCase().replace(/ /g, '-').replace(/&/g, 'and').replace(/\//g, '-');
		},
		makeCallback: function(name) {
			if (typeof this.options[name] === 'function') {
				const args = Array.prototype.slice.call(arguments);

				args.shift();
				this.options[name].apply(this, args);
			}
		}
	};

	// jQuery plugin interface
	$.fn.filteringItems = function(opt) {
		return this.each(function() {
			$(this).data('FilteringItems', new FilteringItems($.extend(opt, {
				holder: this
			})));
		});
	};
}(jQuery));

// history storage
(function($) {
	let stateObject = {};

	window.StorageHistory = {
		/**
		 * Sets a query parameter in the current URL
		 *
		 * @param {string} name - Name of the query parameter to set
		 * @param {string} value - Value of the query parameter to set
		 * @param {string} group - Group name of the query parameter to set
		 * @param {boolean} useReplace - If true, uses replaceState instead of pushState
		 */
		set: function(name, value, group, useReplace) {
			let tempValues = '';
			const location = window.location.href;

			if (location.indexOf('?') > 0) {
				const newLocation = location.split('?');
				const arr = newLocation[1].split('&');
				const index = getItemIndex(arr, name);

				if (index === -1) {
					arr.push(name + '=' + value);
				} else {
					const item = arr[index].split('=');

					if (group) {
						arr[index] = name + '=' + item[1] + ',' + value;
					} else {
						arr[index] = name + '=' + value;
					}
				}

				tempValues = arr.join('&');

				if (tempValues.indexOf('&') === 0) {
					tempValues = tempValues.slice(1);
				}
			} else {
				tempValues = name + '=' + value;
			}

			const method = useReplace ? 'replaceState' : 'pushState';

			window.history[method](stateObject, name, '?' + tempValues);
		},

		/**
		 * Removes a query parameter from the current URL
		 *
		 * @param {string} name - Name of the query parameter to remove
		 * @param {string} value - Value of the query parameter to remove
		 * @param {boolean} useReplace - If true, uses replaceState instead of pushState
		 */
		remove: function(name, value, useReplace) {
			const location = window.location.href;
			const method = useReplace ? 'replaceState' : 'pushState';

			if (location.indexOf('?') > 0) {
				const newLocation = location.split('?');
				const arr = newLocation[1].split('&');
				const index = getItemIndex(arr, name);

				if (value === '') {
					const newArr = arr.filter(function(value, index, arr) {
						return value.indexOf(name) === -1;
					});

					window.history[method](stateObject, name, '?' + newArr.join('&'));
				} else {
					if (index >= 0) {
						const item = arr[index].split('=');
						const valArray = item[1].split(',');

						const getItem = getItemIndex(valArray, value);

						valArray.splice(getItem, 1);

						if (valArray.length === 0) {
							if (arr.length === 1) {
								window.history.replaceState(stateObject, null, newLocation[0]);
							} else {
								arr.splice(index, 1);
								window.history[method](stateObject, name, '?' + arr.join('&'));
							}
						} else {
							arr[index] = item[0] + '=' + valArray.join(',');

							window.history[method](stateObject, name, '?' + arr.join('&'));
						}
					}
				}
			}
		},

		/**
		 * Sets query string in the current URL
		 *
		 * @param {string} query - Query string to set
		 * @param {boolean} useReplace - If true, uses replaceState instead of pushState
		 */
		setQuery: function(query, useReplace) {
			const method = useReplace ? 'replaceState' : 'pushState';
			const base = window.location.pathname;
			const hash = window.location.hash;
			const url = query ? base + '?' + query + hash : base + hash;

			window.history[method](stateObject, '', url);
		},

		/**
		 * Resets the URL to its original state
		 */
		reset: function() {
			const url = window.location.origin + window.location.pathname;

			stateObject = {};

			window.history.replaceState(stateObject, '', url);
		}
	};

	function getItemIndex(array, item) {
		let indexInArray = -1;

		$.each(array, function(index, el) {
			const name = el.split('=');

			if (item === name[0]) {
				indexInArray = index;

				return false;
			}
		});

		return indexInArray;
	}
})(jQuery);

// checked classes when element active
function initCheckedClasses() {
	const checkedClass = 'input-checked',
		parentCheckedClass = 'input-checked-parent';

	const pairs = [];

	jQuery('label[for]').each(function(index, label) {
		const input = jQuery('#' + label.htmlFor);

		label = jQuery(label);

		// click handler
		if (input.length) {
			pairs.push({input: input,
				label: label});

			input.bind('click change', function() {
				if (input.is(':radio')) {
					jQuery.each(pairs, function(index, pair) {
						refreshState(pair.input, pair.label);
					});
				} else {
					refreshState(input, label);
				}
			});

			refreshState(input, label);
		}
	});

	// refresh classes
	function refreshState(input, label) {
		if (input.length) {
			if (input.is(':checked')) {
				input.parent().addClass(parentCheckedClass);
				label.addClass(checkedClass);
			} else {
				input.parent().removeClass(parentCheckedClass);
				label.removeClass(checkedClass);
			}
		}
	}
}

/*
 * Simple Mobile Navigation
 */
(function($) {
	function MobileNav(options) {
		this.options = $.extend({
			container: null,
			hideOnClickOutside: false,
			menuActiveClass: 'nav-active',
			menuOpener: '.nav-opener',
			menuDrop: '.nav-drop',
			toggleEvent: 'click',
			outsideClickEvent: 'click touchstart pointerdown MSPointerDown'
		}, options);

		this.initStructure();
		this.attachEvents();
	}

	MobileNav.prototype = {
		initStructure: function() {
			this.page = $('html');
			this.container = $(this.options.container);
			this.opener = this.container.find(this.options.menuOpener);
			this.drop = this.container.find(this.options.menuDrop);
		},
		attachEvents: function() {
			const self = this;

			if (activateResizeHandler) {
				activateResizeHandler();

				activateResizeHandler = null;
			}

			this.outsideClickHandler = function(e) {
				if (self.isOpened()) {
					const target = $(e.target);

					if (self.opener.hasClass('filter-opener')) {
						if (!target.closest(self.opener).length && !target.closest(self.drop).length && !target.closest('.modal-filter').length) {
							self.hide();
						}
					} else {
						if (!target.closest(self.opener).length && !target.closest(self.drop).length) {
							self.hide();
						}
					}
				}
			};

			this.openerClickHandler = function(e) {
				e.preventDefault();
				self.toggle();
			};

			this.opener.on(this.options.toggleEvent, this.openerClickHandler);
		},
		isOpened: function() {
			return this.container.hasClass(this.options.menuActiveClass);
		},
		show: function() {
			this.container.addClass(this.options.menuActiveClass);

			if (this.options.hideOnClickOutside) {
				this.page.on(this.options.outsideClickEvent, this.outsideClickHandler);
			}

			this.makeCallback('onShow', this);
		},
		hide: function() {
			this.container.removeClass(this.options.menuActiveClass);

			if (this.options.hideOnClickOutside) {
				this.page.off(this.options.outsideClickEvent, this.outsideClickHandler);
			}

			this.makeCallback('onHide', this);
		},
		toggle: function() {
			if (this.isOpened()) {
				this.hide();
			} else {
				this.show();
			}
		},
		makeCallback: function(name) {
			if (typeof this.options[name] === 'function') {
				const args = Array.prototype.slice.call(arguments);

				args.shift();
				this.options[name].apply(this, args);
			}
		},
		destroy: function() {
			this.container.removeClass(this.options.menuActiveClass);
			this.opener.off(this.options.toggleEvent, this.clickHandler);
			this.page.off(this.options.outsideClickEvent, this.outsideClickHandler);
		}
	};

	var activateResizeHandler = function() {
		let win = $(window),
			doc = $('html'),
			resizeClass = 'resize-active',
			flag, timer;

		const removeClassHandler = function() {
			flag = false;
			doc.removeClass(resizeClass);
		};

		const resizeHandler = function() {
			if (!flag) {
				flag = true;
				doc.addClass(resizeClass);
			}

			clearTimeout(timer);
			timer = setTimeout(removeClassHandler, 500);
		};

		win.on('resize orientationchange', resizeHandler);
	};

	$.fn.mobileNav = function(opt) {
		const args = Array.prototype.slice.call(arguments);
		const method = args[0];

		return this.each(function() {
			const $container = jQuery(this);
			const instance = $container.data('MobileNav');

			if (typeof opt === 'object' || typeof opt === 'undefined') {
				$container.data('MobileNav', new MobileNav($.extend({
					container: this
				}, opt)));
			} else if (typeof method === 'string' && instance) {
				if (typeof instance[method] === 'function') {
					args.shift();
					instance[method].apply(instance, args);
				}
			}
		});
	};
}(jQuery));

/*!
 * JavaScript Custom Forms
 *
 * Copyright 2014-2015 PSD2HTML - http://psd2html.com/jcf
 * Released under the MIT license (LICENSE.txt)
 *
 * Version: 1.1.3
 */
(function(root, factory) {
	'use strict';

	if (typeof define === 'function' && define.amd) {
		define(['jquery'], factory);
	} else if (typeof exports === 'object') {
		module.exports = factory(require('jquery'));
	} else {
		root.jcf = factory(jQuery);
	}
}(this, function($) {
	'use strict';

	// define version
	const version = '1.1.3';

	// private variables
	const customInstances = [];

	// default global options
	const commonOptions = {
		optionsKey: 'jcf',
		dataKey: 'jcf-instance',
		rtlClass: 'jcf-rtl',
		focusClass: 'jcf-focus',
		pressedClass: 'jcf-pressed',
		disabledClass: 'jcf-disabled',
		hiddenClass: 'jcf-hidden',
		resetAppearanceClass: 'jcf-reset-appearance',
		unselectableClass: 'jcf-unselectable'
	};

	// detect device type
	const isTouchDevice = ('ontouchstart' in window) || window.DocumentTouch && document instanceof window.DocumentTouch,
		isWinPhoneDevice = /Windows Phone/.test(navigator.userAgent);

	commonOptions.isMobileDevice = !!(isTouchDevice || isWinPhoneDevice);

	let isIOS = /(iPad|iPhone).*OS ([0-9_]*) .*/.exec(navigator.userAgent);

	if (isIOS) isIOS = parseFloat(isIOS[2].replace(/_/g, '.'));
	commonOptions.ios = isIOS;

	// create global stylesheet if custom forms are used
	const createStyleSheet = function() {
		const styleTag = $('<style>').appendTo('head'),
			styleSheet = styleTag.prop('sheet') || styleTag.prop('styleSheet');

		// crossbrowser style handling
		const addCSSRule = function(selector, rules, index) {
			if (styleSheet.insertRule) {
				styleSheet.insertRule(selector + '{' + rules + '}', index);
			} else {
				styleSheet.addRule(selector, rules, index);
			}
		};

		// add special rules
		addCSSRule('.' + commonOptions.hiddenClass, 'position:absolute !important;left:-9999px !important;height:1px !important;width:1px !important;margin:0 !important;border-width:0 !important;-webkit-appearance:none;-moz-appearance:none;appearance:none');
		addCSSRule('.' + commonOptions.rtlClass + ' .' + commonOptions.hiddenClass, 'right:-9999px !important; left: auto !important');
		addCSSRule('.' + commonOptions.unselectableClass, '-webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; user-select: none; -webkit-tap-highlight-color: rgba(0,0,0,0);');
		addCSSRule('.' + commonOptions.resetAppearanceClass, 'background: none; border: none; -webkit-appearance: none; appearance: none; opacity: 0; filter: alpha(opacity=0);');

		// detect rtl pages
		const html = $('html'),
			body = $('body');

		if (html.css('direction') === 'rtl' || body.css('direction') === 'rtl') {
			html.addClass(commonOptions.rtlClass);
		}

		// handle form reset event
		html.on('reset', function() {
			setTimeout(function() {
				api.refreshAll();
			}, 0);
		});

		// mark stylesheet as created
		commonOptions.styleSheetCreated = true;
	};

	// simplified pointer events handler
	(function() {
		let pointerEventsSupported = navigator.pointerEnabled || navigator.msPointerEnabled,
			touchEventsSupported = ('ontouchstart' in window) || window.DocumentTouch && document instanceof window.DocumentTouch,
			eventList,
			eventMap = {},
			eventPrefix = 'jcf-';

		// detect events to attach
		if (pointerEventsSupported) {
			eventList = {
				pointerover: navigator.pointerEnabled ? 'pointerover' : 'MSPointerOver',
				pointerdown: navigator.pointerEnabled ? 'pointerdown' : 'MSPointerDown',
				pointermove: navigator.pointerEnabled ? 'pointermove' : 'MSPointerMove',
				pointerup: navigator.pointerEnabled ? 'pointerup' : 'MSPointerUp'
			};
		} else {
			eventList = {
				pointerover: 'mouseover',
				pointerdown: 'mousedown' + (touchEventsSupported ? ' touchstart' : ''),
				pointermove: 'mousemove' + (touchEventsSupported ? ' touchmove' : ''),
				pointerup: 'mouseup' + (touchEventsSupported ? ' touchend' : '')
			};
		}

		// create event map
		$.each(eventList, function(targetEventName, fakeEventList) {
			$.each(fakeEventList.split(' '), function(index, fakeEventName) {
				eventMap[fakeEventName] = targetEventName;
			});
		});

		// jQuery event hooks
		$.each(eventList, function(eventName, eventHandlers) {
			eventHandlers = eventHandlers.split(' ');

			$.event.special[eventPrefix + eventName] = {
				setup: function() {
					const self = this;

					$.each(eventHandlers, function(index, fallbackEvent) {
						if (self.addEventListener) self.addEventListener(fallbackEvent, fixEvent, false);
						else self['on' + fallbackEvent] = fixEvent;
					});
				},
				teardown: function() {
					const self = this;

					$.each(eventHandlers, function(index, fallbackEvent) {
						if (self.addEventListener) self.removeEventListener(fallbackEvent, fixEvent, false);
						else self['on' + fallbackEvent] = null;
					});
				}
			};
		});

		// check that mouse event are not simulated by mobile browsers
		let lastTouch = null;

		const mouseEventSimulated = function(e) {
			const dx = Math.abs(e.pageX - lastTouch.x),
				dy = Math.abs(e.pageY - lastTouch.y),
				rangeDistance = 25;

			if (dx <= rangeDistance && dy <= rangeDistance) {
				return true;
			}
		};

		// normalize event
		var fixEvent = function(e) {
			let origEvent = e || window.event,
				touchEventData = null,
				targetEventName = eventMap[origEvent.type];

			e = $.event.fix(origEvent);
			e.type = eventPrefix + targetEventName;

			if (origEvent.pointerType) {
				switch (origEvent.pointerType) {
					case 2: e.pointerType = 'touch'; break;
					case 3: e.pointerType = 'pen'; break;
					case 4: e.pointerType = 'mouse'; break;
					default: e.pointerType = origEvent.pointerType;
				}
			} else {
				e.pointerType = origEvent.type.substr(0, 5); // "mouse" or "touch" word length
			}

			if (!e.pageX && !e.pageY) {
				touchEventData = origEvent.changedTouches ? origEvent.changedTouches[0] : origEvent;
				e.pageX = touchEventData.pageX;
				e.pageY = touchEventData.pageY;
			}

			if (origEvent.type === 'touchend') {
				lastTouch = {x: e.pageX,
					y: e.pageY};
			}

			if (e.pointerType === 'mouse' && lastTouch && mouseEventSimulated(e)) {
				return;
			} else {
				return ($.event.dispatch || $.event.handle).call(this, e);
			}
		};
	}());

	// custom mousewheel/trackpad handler
	(function() {
		const wheelEvents = ('onwheel' in document || document.documentMode >= 9 ? 'wheel' : 'mousewheel DOMMouseScroll').split(' '),
			shimEventName = 'jcf-mousewheel';

		$.event.special[shimEventName] = {
			setup: function() {
				const self = this;

				$.each(wheelEvents, function(index, fallbackEvent) {
					if (self.addEventListener) self.addEventListener(fallbackEvent, fixEvent, false);
					else self['on' + fallbackEvent] = fixEvent;
				});
			},
			teardown: function() {
				const self = this;

				$.each(wheelEvents, function(index, fallbackEvent) {
					if (self.addEventListener) self.removeEventListener(fallbackEvent, fixEvent, false);
					else self['on' + fallbackEvent] = null;
				});
			}
		};

		var fixEvent = function(e) {
			const origEvent = e || window.event;

			e = $.event.fix(origEvent);
			e.type = shimEventName;

			// old wheel events handler
			if ('detail' in origEvent) {
				e.deltaY = -origEvent.detail;
			}

			if ('wheelDelta' in origEvent) {
				e.deltaY = -origEvent.wheelDelta;
			}

			if ('wheelDeltaY' in origEvent) {
				e.deltaY = -origEvent.wheelDeltaY;
			}

			if ('wheelDeltaX' in origEvent) {
				e.deltaX = -origEvent.wheelDeltaX;
			}

			// modern wheel event handler
			if ('deltaY' in origEvent) {
				e.deltaY = origEvent.deltaY;
			}

			if ('deltaX' in origEvent) {
				e.deltaX = origEvent.deltaX;
			}

			// handle deltaMode for mouse wheel
			e.delta = e.deltaY || e.deltaX;

			if (origEvent.deltaMode === 1) {
				const lineHeight = 16;

				e.delta *= lineHeight;
				e.deltaY *= lineHeight;
				e.deltaX *= lineHeight;
			}

			return ($.event.dispatch || $.event.handle).call(this, e);
		};
	}());

	// extra module methods
	const moduleMixin = {
		// provide function for firing native events
		fireNativeEvent: function(elements, eventName) {
			$(elements).each(function() {
				let element = this,
					eventObject;

				if (element.dispatchEvent) {
					eventObject = document.createEvent('HTMLEvents');
					eventObject.initEvent(eventName, true, true);
					element.dispatchEvent(eventObject);
				} else if (document.createEventObject) {
					eventObject = document.createEventObject();
					eventObject.target = element;
					element.fireEvent('on' + eventName, eventObject);
				}
			});
		},
		// bind event handlers for module instance (functions beggining with "on")
		bindHandlers: function() {
			const self = this;

			$.each(self, function(propName, propValue) {
				if (propName.indexOf('on') === 0 && $.isFunction(propValue)) {
					// dont use $.proxy here because it doesn't create unique handler
					self[propName] = function() {
						return propValue.apply(self, arguments);
					};
				}
			});
		}
	};

	// public API
	var api = {
		version: version,
		modules: {},
		getOptions: function() {
			return $.extend({}, commonOptions);
		},
		setOptions: function(moduleName, moduleOptions) {
			if (arguments.length > 1) {
				// set module options
				if (this.modules[moduleName]) {
					$.extend(this.modules[moduleName].prototype.options, moduleOptions);
				}
			} else {
				// set common options
				$.extend(commonOptions, moduleName);
			}
		},
		addModule: function(proto) {
			// add module to list
			const Module = function(options) {
				// save instance to collection
				if (!options.element.data(commonOptions.dataKey)) {
					options.element.data(commonOptions.dataKey, this);
				}

				customInstances.push(this);

				// save options
				this.options = $.extend({}, commonOptions, this.options, getInlineOptions(options.element), options);

				// bind event handlers to instance
				this.bindHandlers();

				// call constructor
				this.init.apply(this, arguments);
			};

			// parse options from HTML attribute
			var getInlineOptions = function(element) {
				const dataOptions = element.data(commonOptions.optionsKey),
					attrOptions = element.attr(commonOptions.optionsKey);

				if (dataOptions) {
					return dataOptions;
				} else if (attrOptions) {
					try {
						return $.parseJSON(attrOptions);
					} catch (e) {
						// ignore invalid attributes
					}
				}
			};

			// set proto as prototype for new module
			Module.prototype = proto;

			// add mixin methods to module proto
			$.extend(proto, moduleMixin);

			if (proto.plugins) {
				$.each(proto.plugins, function(pluginName, plugin) {
					$.extend(plugin.prototype, moduleMixin);
				});
			}

			// override destroy method
			const originalDestroy = Module.prototype.destroy;

			Module.prototype.destroy = function() {
				this.options.element.removeData(this.options.dataKey);

				for (let i = customInstances.length - 1; i >= 0; i--) {
					if (customInstances[i] === this) {
						customInstances.splice(i, 1);
						break;
					}
				}

				if (originalDestroy) {
					originalDestroy.apply(this, arguments);
				}
			};

			// save module to list
			this.modules[proto.name] = Module;
		},
		getInstance: function(element) {
			return $(element).data(commonOptions.dataKey);
		},
		replace: function(elements, moduleName, customOptions) {
			let self = this,
				instance;

			if (!commonOptions.styleSheetCreated) {
				createStyleSheet();
			}

			$(elements).each(function() {
				let moduleOptions,
					element = $(this);

				instance = element.data(commonOptions.dataKey);

				if (instance) {
					instance.refresh();
				} else {
					if (!moduleName) {
						$.each(self.modules, function(currentModuleName, module) {
							if (module.prototype.matchElement.call(module.prototype, element)) {
								moduleName = currentModuleName;

								return false;
							}
						});
					}

					if (moduleName) {
						moduleOptions = $.extend({element: element}, customOptions);
						instance = new self.modules[moduleName](moduleOptions);
					}
				}
			});

			return instance;
		},
		refresh: function(elements) {
			$(elements).each(function() {
				const instance = $(this).data(commonOptions.dataKey);

				if (instance) {
					instance.refresh();
				}
			});
		},
		destroy: function(elements) {
			$(elements).each(function() {
				const instance = $(this).data(commonOptions.dataKey);

				if (instance) {
					instance.destroy();
				}
			});
		},
		replaceAll: function(context) {
			const self = this;

			$.each(this.modules, function(moduleName, module) {
				$(module.prototype.selector, context).each(function() {
					if (this.className.indexOf('jcf-ignore') < 0) {
						self.replace(this, moduleName);
					}
				});
			});
		},
		refreshAll: function(context) {
			if (context) {
				$.each(this.modules, function(moduleName, module) {
					$(module.prototype.selector, context).each(function() {
						const instance = $(this).data(commonOptions.dataKey);

						if (instance) {
							instance.refresh();
						}
					});
				});
			} else {
				for (let i = customInstances.length - 1; i >= 0; i--) {
					customInstances[i].refresh();
				}
			}
		},
		destroyAll: function(context) {
			if (context) {
				$.each(this.modules, function(moduleName, module) {
					$(module.prototype.selector, context).each(function(index, element) {
						const instance = $(element).data(commonOptions.dataKey);

						if (instance) {
							instance.destroy();
						}
					});
				});
			} else {
				while (customInstances.length) {
					customInstances[0].destroy();
				}
			}
		}
	};

	// always export API to the global window object
	window.jcf = api;

	return api;
}));

/*!
 * JavaScript Custom Forms : Select Module
 *
 * Copyright 2014-2015 PSD2HTML - http://psd2html.com/jcf
 * Released under the MIT license (LICENSE.txt)
 *
 * Version: 1.1.3
 */
(function($, window) {
	'use strict';

	jcf.addModule({
		name: 'Select',
		selector: 'select:not(.eco-widget__form select)',
		options: {
			element: null,
			multipleCompactStyle: false
		},
		plugins: {
			ListBox: ListBox,
			ComboBox: ComboBox,
			SelectList: SelectList
		},
		matchElement: function(element) {
			return element.is('select');
		},
		init: function() {
			this.element = $(this.options.element);
			this.createInstance();
		},
		isListBox: function() {
			return this.element.is('[size]:not([jcf-size]), [multiple]');
		},
		createInstance: function() {
			if (this.instance) {
				this.instance.destroy();
			}

			if (this.isListBox() && !this.options.multipleCompactStyle) {
				this.instance = new ListBox(this.options);
			} else {
				this.instance = new ComboBox(this.options);
			}
		},
		refresh: function() {
			const typeMismatch = (this.isListBox() && this.instance instanceof ComboBox)
								|| (!this.isListBox() && this.instance instanceof ListBox);

			if (typeMismatch) {
				this.createInstance();
			} else {
				this.instance.refresh();
			}
		},
		destroy: function() {
			this.instance.destroy();
		}
	});

	// combobox module
	function ComboBox(options) {
		this.options = $.extend({
			wrapNative: true,
			wrapNativeOnMobile: true,
			fakeDropInBody: true,
			useCustomScroll: true,
			flipDropToFit: true,
			maxVisibleItems: 10,
			fakeAreaStructure: '<span class="jcf-select"><span class="jcf-opener-holder"><span class="jcf-select-text"></span><span class="jcf-select-opener"></span></span></span>',
			fakeDropStructure: '<div class="jcf-select-drop"><div class="jcf-select-drop-content"></div></div>',
			optionClassPrefix: 'jcf-option-',
			selectClassPrefix: 'jcf-select-',
			dropContentSelector: '.jcf-select-drop-content',
			selectTextSelector: '.jcf-select-text',
			dropActiveClass: 'jcf-drop-active',
			flipDropClass: 'jcf-drop-flipped'
		}, options);

		this.init();
	}

	$.extend(ComboBox.prototype, {
		init: function() {
			this.initStructure();
			this.bindHandlers();
			this.attachEvents();
			this.refresh();
		},
		initStructure: function() {
			// prepare structure
			this.win = $(window);
			this.doc = $(document);
			this.realElement = $(this.options.element);
			this.fakeElement = $(this.options.fakeAreaStructure).insertAfter(this.realElement);
			this.selectTextContainer = this.fakeElement.find(this.options.selectTextSelector);
			this.selectText = $('<span></span>').appendTo(this.selectTextContainer);
			makeUnselectable(this.fakeElement);

			// copy classes from original select
			this.fakeElement.addClass(getPrefixedClasses(this.realElement.prop('className'), this.options.selectClassPrefix));

			// handle compact multiple style
			if (this.realElement.prop('multiple')) {
				this.fakeElement.addClass('jcf-compact-multiple');
			}

			// detect device type and dropdown behavior
			if (this.options.isMobileDevice && this.options.wrapNativeOnMobile && !this.options.wrapNative) {
				this.options.wrapNative = true;
			}

			if (this.options.wrapNative) {
				// wrap native select inside fake block
				this.realElement.prependTo(this.fakeElement).css({
					position: 'absolute',
					height: '100%',
					width: '100%'
				}).addClass(this.options.resetAppearanceClass);
			} else {
				// just hide native select
				this.realElement.addClass(this.options.hiddenClass);
				this.fakeElement.attr('title', this.realElement.attr('title'));
				this.fakeDropTarget = this.options.fakeDropInBody ? $('body') : this.fakeElement;
			}
		},
		attachEvents: function() {
			// delayed refresh handler
			const self = this;

			this.delayedRefresh = function() {
				setTimeout(function() {
					self.refresh();

					if (self.list) {
						self.list.refresh();
						self.list.scrollToActiveOption();
					}
				}, 1);
			};

			// native dropdown event handlers
			if (this.options.wrapNative) {
				this.realElement.on({
					focus: this.onFocus,
					change: this.onChange,
					click: this.onChange,
					keydown: this.onChange
				});
			} else {
				// custom dropdown event handlers
				this.realElement.on({
					focus: this.onFocus,
					change: this.onChange,
					keydown: this.onKeyDown
				});

				this.fakeElement.on({
					'jcf-pointerdown': this.onSelectAreaPress
				});
			}
		},
		onKeyDown: function(e) {
			if (e.which === 13) {
				this.toggleDropdown();
			} else if (this.dropActive) {
				this.delayedRefresh();
			}
		},
		onChange: function() {
			this.refresh();
		},
		onFocus: function() {
			if (!this.pressedFlag || !this.focusedFlag) {
				this.fakeElement.addClass(this.options.focusClass);
				this.realElement.on('blur', this.onBlur);
				this.toggleListMode(true);
				this.focusedFlag = true;
			}
		},
		onBlur: function() {
			if (!this.pressedFlag) {
				this.fakeElement.removeClass(this.options.focusClass);
				this.realElement.off('blur', this.onBlur);
				this.toggleListMode(false);
				this.focusedFlag = false;
			}
		},
		onResize: function() {
			if (this.dropActive) {
				this.hideDropdown();
			}
		},
		onSelectDropPress: function() {
			this.pressedFlag = true;
		},
		onSelectDropRelease: function(e, pointerEvent) {
			this.pressedFlag = false;

			if (pointerEvent.pointerType === 'mouse') {
				this.realElement.focus();
			}
		},
		onSelectAreaPress: function(e) {
			// skip click if drop inside fake element or real select is disabled
			const dropClickedInsideFakeElement = !this.options.fakeDropInBody && $(e.target).closest(this.dropdown).length;

			if (dropClickedInsideFakeElement || e.button > 1 || this.realElement.is(':disabled')) {
				return;
			}

			// toggle dropdown visibility
			this.selectOpenedByEvent = e.pointerType;
			this.toggleDropdown();

			// misc handlers
			if (!this.focusedFlag) {
				if (e.pointerType === 'mouse') {
					this.realElement.focus();
				} else {
					this.onFocus(e);
				}
			}

			this.pressedFlag = true;
			this.fakeElement.addClass(this.options.pressedClass);
			this.doc.on('jcf-pointerup', this.onSelectAreaRelease);
		},
		onSelectAreaRelease: function(e) {
			if (this.focusedFlag && e.pointerType === 'mouse') {
				this.realElement.focus();
			}

			this.pressedFlag = false;
			this.fakeElement.removeClass(this.options.pressedClass);
			this.doc.off('jcf-pointerup', this.onSelectAreaRelease);
		},
		onOutsideClick: function(e) {
			const target = $(e.target),
				clickedInsideSelect = target.closest(this.fakeElement).length || target.closest(this.dropdown).length;

			if (!clickedInsideSelect) {
				this.hideDropdown();
			}
		},
		onSelect: function() {
			this.refresh();

			if (this.realElement.prop('multiple')) {
				this.repositionDropdown();
			} else {
				this.hideDropdown();
			}

			this.fireNativeEvent(this.realElement, 'change');
		},
		toggleListMode: function(state) {
			if (!this.options.wrapNative) {
				if (state) {
					// temporary change select to list to avoid appearing of native dropdown
					this.realElement.attr({
						size: 4,
						'jcf-size': ''
					});
				} else {
					// restore select from list mode to dropdown select
					if (!this.options.wrapNative) {
						this.realElement.removeAttr('size jcf-size');
					}
				}
			}
		},
		createDropdown: function() {
			// destroy previous dropdown if needed
			if (this.dropdown) {
				this.list.destroy();
				this.dropdown.remove();
			}

			// create new drop container
			this.dropdown = $(this.options.fakeDropStructure).appendTo(this.fakeDropTarget);
			this.dropdown.addClass(getPrefixedClasses(this.realElement.prop('className'), this.options.selectClassPrefix));
			makeUnselectable(this.dropdown);

			// handle compact multiple style
			if (this.realElement.prop('multiple')) {
				this.dropdown.addClass('jcf-compact-multiple');
			}

			// set initial styles for dropdown in body
			if (this.options.fakeDropInBody) {
				this.dropdown.css({
					position: 'absolute',
					top: -9999
				});
			}

			// create new select list instance
			this.list = new SelectList({
				useHoverClass: true,
				handleResize: false,
				alwaysPreventMouseWheel: true,
				maxVisibleItems: this.options.maxVisibleItems,
				useCustomScroll: this.options.useCustomScroll,
				holder: this.dropdown.find(this.options.dropContentSelector),
				multipleSelectWithoutKey: this.realElement.prop('multiple'),
				element: this.realElement
			});

			$(this.list).on({
				select: this.onSelect,
				press: this.onSelectDropPress,
				release: this.onSelectDropRelease
			});
		},
		repositionDropdown: function() {
			let selectOffset = this.fakeElement.offset(),
				selectWidth = this.fakeElement.outerWidth(),
				selectHeight = this.fakeElement.outerHeight(),
				dropHeight = this.dropdown.css('width', selectWidth).outerHeight(),
				winScrollTop = this.win.scrollTop(),
				winHeight = this.win.height(),
				calcTop, calcLeft, bodyOffset,
				needFlipDrop = false;

			// check flip drop position
			if (selectOffset.top + selectHeight + dropHeight > winScrollTop + winHeight && selectOffset.top - dropHeight > winScrollTop) {
				needFlipDrop = true;
			}

			if (this.options.fakeDropInBody) {
				bodyOffset = this.fakeDropTarget.css('position') !== 'static' ? this.fakeDropTarget.offset().top : 0;

				if (this.options.flipDropToFit && needFlipDrop) {
					// calculate flipped dropdown position
					calcLeft = selectOffset.left;
					calcTop = selectOffset.top - dropHeight - bodyOffset;
				} else {
					// calculate default drop position
					calcLeft = selectOffset.left;
					calcTop = selectOffset.top + selectHeight - bodyOffset;
				}

				// update drop styles
				this.dropdown.css({
					width: selectWidth,
					left: calcLeft,
					top: calcTop
				});
			}

			// refresh flipped class
			this.dropdown.add(this.fakeElement).toggleClass(this.options.flipDropClass, this.options.flipDropToFit && needFlipDrop);
		},
		showDropdown: function() {
			// do not show empty custom dropdown
			if (!this.realElement.prop('options').length) {
				return;
			}

			// create options list if not created
			if (!this.dropdown) {
				this.createDropdown();
			}

			// show dropdown
			this.dropActive = true;
			this.dropdown.appendTo(this.fakeDropTarget);
			this.fakeElement.addClass(this.options.dropActiveClass);
			this.refreshSelectedText();
			this.repositionDropdown();
			this.list.setScrollTop(this.savedScrollTop);
			this.list.refresh();

			// add temporary event handlers
			this.win.on('resize', this.onResize);
			this.doc.on('jcf-pointerdown', this.onOutsideClick);
		},
		hideDropdown: function() {
			if (this.dropdown) {
				this.savedScrollTop = this.list.getScrollTop();
				this.fakeElement.removeClass(this.options.dropActiveClass + ' ' + this.options.flipDropClass);
				this.dropdown.removeClass(this.options.flipDropClass).detach();
				this.doc.off('jcf-pointerdown', this.onOutsideClick);
				this.win.off('resize', this.onResize);
				this.dropActive = false;

				if (this.selectOpenedByEvent === 'touch') {
					this.onBlur();
				}
			}
		},
		toggleDropdown: function() {
			if (this.dropActive) {
				this.hideDropdown();
			} else {
				this.showDropdown();
			}
		},
		refreshSelectedText: function() {
			// redraw selected area
			let selectedIndex = this.realElement.prop('selectedIndex'),
				selectedOption = this.realElement.prop('options')[selectedIndex],
				selectedOptionImage = selectedOption ? selectedOption.getAttribute('data-image') : null,
				selectedOptionText = '',
				selectedOptionClasses,
				self = this;

			if (this.realElement.prop('multiple')) {
				$.each(this.realElement.prop('options'), function(index, option) {
					if (option.selected) {
						selectedOptionText += (selectedOptionText ? ', ' : '') + option.innerHTML;
					}
				});

				if (!selectedOptionText) {
					selectedOptionText = self.realElement.attr('placeholder') || '';
				}

				this.selectText.removeAttr('class').html(selectedOptionText);
			} else if (!selectedOption) {
				if (this.selectImage) {
					this.selectImage.hide();
				}

				this.selectText.removeAttr('class').empty();
			} else if (this.currentSelectedText !== selectedOption.innerHTML || this.currentSelectedImage !== selectedOptionImage) {
				selectedOptionClasses = getPrefixedClasses(selectedOption.className, this.options.optionClassPrefix);
				this.selectText.attr('class', selectedOptionClasses).html(selectedOption.innerHTML);

				if (selectedOptionImage) {
					if (!this.selectImage) {
						this.selectImage = $('<img>').prependTo(this.selectTextContainer).hide();
					}

					this.selectImage.attr('src', selectedOptionImage).show();
				} else if (this.selectImage) {
					this.selectImage.hide();
				}

				this.currentSelectedText = selectedOption.innerHTML;
				this.currentSelectedImage = selectedOptionImage;
			}
		},
		refresh: function() {
			// refresh fake select visibility
			if (this.realElement.prop('style').display === 'none') {
				this.fakeElement.hide();
			} else {
				this.fakeElement.show();
			}

			// refresh selected text
			this.refreshSelectedText();

			// handle disabled state
			this.fakeElement.toggleClass(this.options.disabledClass, this.realElement.is(':disabled'));
		},
		destroy: function() {
			// restore structure
			if (this.options.wrapNative) {
				this.realElement.insertBefore(this.fakeElement).css({
					position: '',
					height: '',
					width: ''
				}).removeClass(this.options.resetAppearanceClass);
			} else {
				this.realElement.removeClass(this.options.hiddenClass);

				if (this.realElement.is('[jcf-size]')) {
					this.realElement.removeAttr('size jcf-size');
				}
			}

			// removing element will also remove its event handlers
			this.fakeElement.remove();

			// remove other event handlers
			this.doc.off('jcf-pointerup', this.onSelectAreaRelease);

			this.realElement.off({
				focus: this.onFocus
			});
		}
	});

	// listbox module
	function ListBox(options) {
		this.options = $.extend({
			wrapNative: true,
			useCustomScroll: true,
			fakeStructure: '<span class="jcf-list-box"><span class="jcf-list-wrapper"></span></span>',
			selectClassPrefix: 'jcf-select-',
			listHolder: '.jcf-list-wrapper'
		}, options);

		this.init();
	}

	$.extend(ListBox.prototype, {
		init: function() {
			this.bindHandlers();
			this.initStructure();
			this.attachEvents();
		},
		initStructure: function() {
			this.realElement = $(this.options.element);
			this.fakeElement = $(this.options.fakeStructure).insertAfter(this.realElement);
			this.listHolder = this.fakeElement.find(this.options.listHolder);
			makeUnselectable(this.fakeElement);

			// copy classes from original select
			this.fakeElement.addClass(getPrefixedClasses(this.realElement.prop('className'), this.options.selectClassPrefix));
			this.realElement.addClass(this.options.hiddenClass);

			this.list = new SelectList({
				useCustomScroll: this.options.useCustomScroll,
				holder: this.listHolder,
				selectOnClick: false,
				element: this.realElement
			});
		},
		attachEvents: function() {
			// delayed refresh handler
			const self = this;

			this.delayedRefresh = function(e) {
				if (e && e.which === 16) {
					// ignore SHIFT key
					return;
				} else {
					clearTimeout(self.refreshTimer);

					self.refreshTimer = setTimeout(function() {
						self.refresh();
						self.list.scrollToActiveOption();
					}, 1);
				}
			};

			// other event handlers
			this.realElement.on({
				focus: this.onFocus,
				click: this.delayedRefresh,
				keydown: this.delayedRefresh
			});

			// select list event handlers
			$(this.list).on({
				select: this.onSelect,
				press: this.onFakeOptionsPress,
				release: this.onFakeOptionsRelease
			});
		},
		onFakeOptionsPress: function(e, pointerEvent) {
			this.pressedFlag = true;

			if (pointerEvent.pointerType === 'mouse') {
				this.realElement.focus();
			}
		},
		onFakeOptionsRelease: function(e, pointerEvent) {
			this.pressedFlag = false;

			if (pointerEvent.pointerType === 'mouse') {
				this.realElement.focus();
			}
		},
		onSelect: function() {
			this.fireNativeEvent(this.realElement, 'change');
			this.fireNativeEvent(this.realElement, 'click');
		},
		onFocus: function() {
			if (!this.pressedFlag || !this.focusedFlag) {
				this.fakeElement.addClass(this.options.focusClass);
				this.realElement.on('blur', this.onBlur);
				this.focusedFlag = true;
			}
		},
		onBlur: function() {
			if (!this.pressedFlag) {
				this.fakeElement.removeClass(this.options.focusClass);
				this.realElement.off('blur', this.onBlur);
				this.focusedFlag = false;
			}
		},
		refresh: function() {
			this.fakeElement.toggleClass(this.options.disabledClass, this.realElement.is(':disabled'));
			this.list.refresh();
		},
		destroy: function() {
			this.list.destroy();
			this.realElement.insertBefore(this.fakeElement).removeClass(this.options.hiddenClass);
			this.fakeElement.remove();
		}
	});

	// options list module
	function SelectList(options) {
		this.options = $.extend({
			holder: null,
			maxVisibleItems: 10,
			selectOnClick: true,
			useHoverClass: false,
			useCustomScroll: false,
			handleResize: true,
			multipleSelectWithoutKey: false,
			alwaysPreventMouseWheel: false,
			indexAttribute: 'data-index',
			cloneClassPrefix: 'jcf-option-',
			containerStructure: '<span class="jcf-list"><span class="jcf-list-content"></span></span>',
			containerSelector: '.jcf-list-content',
			captionClass: 'jcf-optgroup-caption',
			disabledClass: 'jcf-disabled',
			optionClass: 'jcf-option',
			groupClass: 'jcf-optgroup',
			hoverClass: 'jcf-hover',
			selectedClass: 'jcf-selected',
			scrollClass: 'jcf-scroll-active'
		}, options);

		this.init();
	}

	$.extend(SelectList.prototype, {
		init: function() {
			this.initStructure();
			this.refreshSelectedClass();
			this.attachEvents();
		},
		initStructure: function() {
			this.element = $(this.options.element);
			this.indexSelector = '[' + this.options.indexAttribute + ']';
			this.container = $(this.options.containerStructure).appendTo(this.options.holder);
			this.listHolder = this.container.find(this.options.containerSelector);
			this.lastClickedIndex = this.element.prop('selectedIndex');
			this.rebuildList();
		},
		attachEvents: function() {
			this.bindHandlers();
			this.listHolder.on('jcf-pointerdown', this.indexSelector, this.onItemPress);
			this.listHolder.on('jcf-pointerdown', this.onPress);

			if (this.options.useHoverClass) {
				this.listHolder.on('jcf-pointerover', this.indexSelector, this.onHoverItem);
			}
		},
		onPress: function(e) {
			$(this).trigger('press', e);
			this.listHolder.on('jcf-pointerup', this.onRelease);
		},
		onRelease: function(e) {
			$(this).trigger('release', e);
			this.listHolder.off('jcf-pointerup', this.onRelease);
		},
		onHoverItem: function(e) {
			const hoverIndex = parseFloat(e.currentTarget.getAttribute(this.options.indexAttribute));

			this.fakeOptions.removeClass(this.options.hoverClass).eq(hoverIndex).addClass(this.options.hoverClass);
		},
		onItemPress: function(e) {
			if (e.pointerType === 'touch' || this.options.selectOnClick) {
				// select option after "click"
				this.tmpListOffsetTop = this.list.offset().top;
				this.listHolder.on('jcf-pointerup', this.indexSelector, this.onItemRelease);
			} else {
				// select option immediately
				this.onSelectItem(e);
			}
		},
		onItemRelease: function(e) {
			// remove event handlers and temporary data
			this.listHolder.off('jcf-pointerup', this.indexSelector, this.onItemRelease);

			// simulate item selection
			if (this.tmpListOffsetTop === this.list.offset().top) {
				this.listHolder.on('click', this.indexSelector, {savedPointerType: e.pointerType}, this.onSelectItem);
			}

			delete this.tmpListOffsetTop;
		},
		onSelectItem: function(e) {
			let clickedIndex = parseFloat(e.currentTarget.getAttribute(this.options.indexAttribute)),
				pointerType = e.data && e.data.savedPointerType || e.pointerType || 'mouse',
				range;

			// remove click event handler
			this.listHolder.off('click', this.indexSelector, this.onSelectItem);

			// ignore clicks on disabled options
			if (e.button > 1 || this.realOptions[clickedIndex].disabled) {
				return;
			}

			if (this.element.prop('multiple')) {
				if (e.metaKey || e.ctrlKey || pointerType === 'touch' || this.options.multipleSelectWithoutKey) {
					// if CTRL/CMD pressed or touch devices - toggle selected option
					this.realOptions[clickedIndex].selected = !this.realOptions[clickedIndex].selected;
				} else if (e.shiftKey) {
					// if SHIFT pressed - update selection
					range = [this.lastClickedIndex, clickedIndex].sort(function(a, b) {
						return a - b;
					});

					this.realOptions.each(function(index, option) {
						option.selected = (index >= range[0] && index <= range[1]);
					});
				} else {
					// set single selected index
					this.element.prop('selectedIndex', clickedIndex);
				}
			} else {
				this.element.prop('selectedIndex', clickedIndex);
			}

			// save last clicked option
			if (!e.shiftKey) {
				this.lastClickedIndex = clickedIndex;
			}

			// refresh classes
			this.refreshSelectedClass();

			// scroll to active item in desktop browsers
			if (pointerType === 'mouse') {
				this.scrollToActiveOption();
			}

			// make callback when item selected
			$(this).trigger('select');
		},
		rebuildList: function() {
			// rebuild options
			const self = this,
				rootElement = this.element[0];

			// recursively create fake options
			this.storedSelectHTML = rootElement.innerHTML;
			this.optionIndex = 0;
			this.list = $(this.createOptionsList(rootElement));
			this.listHolder.empty().append(this.list);
			this.realOptions = this.element.find('option');
			this.fakeOptions = this.list.find(this.indexSelector);
			this.fakeListItems = this.list.find('.' + this.options.captionClass + ',' + this.indexSelector);
			delete this.optionIndex;

			// detect max visible items
			let maxCount = this.options.maxVisibleItems,
				sizeValue = this.element.prop('size');

			if (sizeValue > 1 && !this.element.is('[jcf-size]')) {
				maxCount = sizeValue;
			}

			// handle scrollbar
			const needScrollBar = this.fakeOptions.length > maxCount;

			this.container.toggleClass(this.options.scrollClass, needScrollBar);

			if (needScrollBar) {
				// change max-height
				this.listHolder.css({
					maxHeight: this.getOverflowHeight(maxCount),
					overflow: 'auto'
				});

				if (this.options.useCustomScroll && jcf.modules.Scrollable) {
					// add custom scrollbar if specified in options
					jcf.replace(this.listHolder, 'Scrollable', {
						handleResize: this.options.handleResize,
						alwaysPreventMouseWheel: this.options.alwaysPreventMouseWheel
					});

					return;
				}
			}

			// disable edge wheel scrolling
			if (this.options.alwaysPreventMouseWheel) {
				this.preventWheelHandler = function(e) {
					const currentScrollTop = self.listHolder.scrollTop(),
						maxScrollTop = self.listHolder.prop('scrollHeight') - self.listHolder.innerHeight();

					// check edge cases
					if ((currentScrollTop <= 0 && e.deltaY < 0) || (currentScrollTop >= maxScrollTop && e.deltaY > 0)) {
						e.preventDefault();
					}
				};

				this.listHolder.on('jcf-mousewheel', this.preventWheelHandler);
			}
		},
		refreshSelectedClass: function() {
			let self = this,
				selectedItem,
				isMultiple = this.element.prop('multiple'),
				selectedIndex = this.element.prop('selectedIndex');

			if (isMultiple) {
				this.realOptions.each(function(index, option) {
					self.fakeOptions.eq(index).toggleClass(self.options.selectedClass, !!option.selected);
				});
			} else {
				this.fakeOptions.removeClass(this.options.selectedClass + ' ' + this.options.hoverClass);
				selectedItem = this.fakeOptions.eq(selectedIndex).addClass(this.options.selectedClass);

				if (this.options.useHoverClass) {
					selectedItem.addClass(this.options.hoverClass);
				}
			}
		},
		scrollToActiveOption: function() {
			// scroll to target option
			const targetOffset = this.getActiveOptionOffset();

			if (typeof targetOffset === 'number') {
				this.listHolder.prop('scrollTop', targetOffset);
			}
		},
		getSelectedIndexRange: function() {
			let firstSelected = -1,
				lastSelected = -1;

			this.realOptions.each(function(index, option) {
				if (option.selected) {
					if (firstSelected < 0) {
						firstSelected = index;
					}

					lastSelected = index;
				}
			});

			return [firstSelected, lastSelected];
		},
		getChangedSelectedIndex: function() {
			let selectedIndex = this.element.prop('selectedIndex'),
				targetIndex;

			if (this.element.prop('multiple')) {
				// multiple selects handling
				if (!this.previousRange) {
					this.previousRange = [selectedIndex, selectedIndex];
				}

				this.currentRange = this.getSelectedIndexRange();
				targetIndex = this.currentRange[this.currentRange[0] !== this.previousRange[0] ? 0 : 1];
				this.previousRange = this.currentRange;

				return targetIndex;
			} else {
				// single choice selects handling
				return selectedIndex;
			}
		},
		getActiveOptionOffset: function() {
			// calc values
			const dropHeight = this.listHolder.height(),
				dropScrollTop = this.listHolder.prop('scrollTop'),
				currentIndex = this.getChangedSelectedIndex(),
				fakeOption = this.fakeOptions.eq(currentIndex),
				fakeOptionOffset = fakeOption.offset().top - this.list.offset().top,
				fakeOptionHeight = fakeOption.innerHeight();

			// scroll list
			if (fakeOptionOffset + fakeOptionHeight >= dropScrollTop + dropHeight) {
				// scroll down (always scroll to option)
				return fakeOptionOffset - dropHeight + fakeOptionHeight;
			} else if (fakeOptionOffset < dropScrollTop) {
				// scroll up to option
				return fakeOptionOffset;
			}
		},
		getOverflowHeight: function(sizeValue) {
			const item = this.fakeListItems.eq(sizeValue - 1),
				listOffset = this.list.offset().top,
				itemOffset = item.offset().top,
				itemHeight = item.innerHeight();

			return itemOffset + itemHeight - listOffset;
		},
		getScrollTop: function() {
			return this.listHolder.scrollTop();
		},
		setScrollTop: function(value) {
			this.listHolder.scrollTop(value);
		},
		createOption: function(option) {
			const newOption = document.createElement('span');

			newOption.className = this.options.optionClass;
			newOption.innerHTML = option.innerHTML;
			newOption.setAttribute(this.options.indexAttribute, this.optionIndex++);

			let optionImage,
				optionImageSrc = option.getAttribute('data-image');

			if (optionImageSrc) {
				optionImage = document.createElement('img');
				optionImage.src = optionImageSrc;
				newOption.insertBefore(optionImage, newOption.childNodes[0]);
			}

			if (option.disabled) {
				newOption.className += ' ' + this.options.disabledClass;
			}

			if (option.className) {
				newOption.className += ' ' + getPrefixedClasses(option.className, this.options.cloneClassPrefix);
			}

			return newOption;
		},
		createOptGroup: function(optgroup) {
			let optGroupContainer = document.createElement('span'),
				optGroupName = optgroup.getAttribute('label'),
				optGroupCaption, optGroupList;

			// create caption
			optGroupCaption = document.createElement('span');
			optGroupCaption.className = this.options.captionClass;
			optGroupCaption.innerHTML = optGroupName;
			optGroupContainer.appendChild(optGroupCaption);

			// create list of options
			if (optgroup.children.length) {
				optGroupList = this.createOptionsList(optgroup);
				optGroupContainer.appendChild(optGroupList);
			}

			optGroupContainer.className = this.options.groupClass;

			return optGroupContainer;
		},
		createOptionContainer: function() {
			const optionContainer = document.createElement('li');

			return optionContainer;
		},
		createOptionsList: function(container) {
			const self = this,
				list = document.createElement('ul');

			$.each(container.children, function(index, currentNode) {
				let item = self.createOptionContainer(currentNode),
					newNode;

				switch (currentNode.tagName.toLowerCase()) {
					case 'option': newNode = self.createOption(currentNode); break;
					case 'optgroup': newNode = self.createOptGroup(currentNode); break;
				}

				list.appendChild(item).appendChild(newNode);
			});

			return list;
		},
		refresh: function() {
			// check for select innerHTML changes
			if (this.storedSelectHTML !== this.element.prop('innerHTML')) {
				this.rebuildList();
			}

			// refresh custom scrollbar
			const scrollInstance = jcf.getInstance(this.listHolder);

			if (scrollInstance) {
				scrollInstance.refresh();
			}

			// refresh selectes classes
			this.refreshSelectedClass();
		},
		destroy: function() {
			this.listHolder.off('jcf-mousewheel', this.preventWheelHandler);
			this.listHolder.off('jcf-pointerdown', this.indexSelector, this.onSelectItem);
			this.listHolder.off('jcf-pointerover', this.indexSelector, this.onHoverItem);
			this.listHolder.off('jcf-pointerdown', this.onPress);
		}
	});

	// helper functions
	var getPrefixedClasses = function(className, prefixToAdd) {
		return className ? className.replace(/[\s]*([\S]+)+[\s]*/gi, prefixToAdd + '$1 ') : '';
	};

	var makeUnselectable = (function() {
		const unselectableClass = jcf.getOptions().unselectableClass;

		function preventHandler(e) {
			e.preventDefault();
		}

		return function(node) {
			node.addClass(unselectableClass).on('selectstart', preventHandler);
		};
	}());
}(jQuery, this));

/*!
 * JavaScript Custom Forms : Scrollbar Module
 *
 * Copyright 2014-2015 PSD2HTML - http://psd2html.com/jcf
 * Released under the MIT license (LICENSE.txt)
 *
 * Version: 1.1.3
 */
(function($, window) {
	'use strict';

	jcf.addModule({
		name: 'Scrollable',
		selector: '.jcf-scrollable',
		plugins: {
			ScrollBar: ScrollBar
		},
		options: {
			mouseWheelStep: 150,
			handleResize: true,
			alwaysShowScrollbars: false,
			alwaysPreventMouseWheel: false,
			scrollAreaStructure: '<div class="jcf-scrollable-wrapper"></div>'
		},
		matchElement: function(element) {
			return element.is('.jcf-scrollable');
		},
		init: function() {
			this.initStructure();
			this.attachEvents();
			this.rebuildScrollbars();
		},
		initStructure: function() {
			// prepare structure
			this.doc = $(document);
			this.win = $(window);
			this.realElement = $(this.options.element);
			this.scrollWrapper = $(this.options.scrollAreaStructure).insertAfter(this.realElement);

			// set initial styles
			this.scrollWrapper.css('position', 'relative');
			// this.realElement.css('overflow', 'hidden');
			this.realElement.css('overflow', this.options.ios && this.options.ios >= 10 ? 'auto' : 'hidden');
			this.vBarEdge = 0;
		},
		attachEvents: function() {
			// create scrollbars
			const self = this;

			this.vBar = new ScrollBar({
				holder: this.scrollWrapper,
				vertical: true,
				onScroll: function(scrollTop) {
					self.realElement.scrollTop(scrollTop);
				}
			});

			this.hBar = new ScrollBar({
				holder: this.scrollWrapper,
				vertical: false,
				onScroll: function(scrollLeft) {
					self.realElement.scrollLeft(scrollLeft);
				}
			});

			// add event handlers
			this.realElement.on('scroll', this.onScroll);

			if (this.options.handleResize) {
				this.win.on('resize orientationchange load', this.onResize);
			}

			// add pointer/wheel event handlers
			this.realElement.on('jcf-mousewheel', this.onMouseWheel);
			this.realElement.on('jcf-pointerdown', this.onTouchBody);
		},
		onScroll: function() {
			this.redrawScrollbars();
		},
		onResize: function() {
			// do not rebuild scrollbars if form field is in focus
			if (!$(document.activeElement).is(':input')) {
				this.rebuildScrollbars();
			}
		},
		onTouchBody: function(e) {
			if (e.pointerType === 'touch') {
				this.touchData = {
					scrollTop: this.realElement.scrollTop(),
					scrollLeft: this.realElement.scrollLeft(),
					left: e.pageX,
					top: e.pageY
				};

				this.doc.on({
					'jcf-pointermove': this.onMoveBody,
					'jcf-pointerup': this.onReleaseBody
				});
			}
		},
		onMoveBody: function(e) {
			let targetScrollTop,
				targetScrollLeft,
				verticalScrollAllowed = this.verticalScrollActive,
				horizontalScrollAllowed = this.horizontalScrollActive;

			if (e.pointerType === 'touch') {
				targetScrollTop = this.touchData.scrollTop - e.pageY + this.touchData.top;
				targetScrollLeft = this.touchData.scrollLeft - e.pageX + this.touchData.left;

				// check that scrolling is ended and release outer scrolling
				if (this.verticalScrollActive && (targetScrollTop < 0 || targetScrollTop > this.vBar.maxValue)) {
					verticalScrollAllowed = false;
				}

				if (this.horizontalScrollActive && (targetScrollLeft < 0 || targetScrollLeft > this.hBar.maxValue)) {
					horizontalScrollAllowed = false;
				}

				this.realElement.scrollTop(targetScrollTop);
				this.realElement.scrollLeft(targetScrollLeft);

				if (verticalScrollAllowed || horizontalScrollAllowed) {
					e.preventDefault();
				} else {
					this.onReleaseBody(e);
				}
			}
		},
		onReleaseBody: function(e) {
			if (e.pointerType === 'touch') {
				delete this.touchData;

				this.doc.off({
					'jcf-pointermove': this.onMoveBody,
					'jcf-pointerup': this.onReleaseBody
				});
			}
		},
		onMouseWheel: function(e) {
			let currentScrollTop = this.realElement.scrollTop(),
				currentScrollLeft = this.realElement.scrollLeft(),
				maxScrollTop = this.realElement.prop('scrollHeight') - this.embeddedDimensions.innerHeight,
				maxScrollLeft = this.realElement.prop('scrollWidth') - this.embeddedDimensions.innerWidth,
				extraLeft, extraTop, preventFlag;

			// check edge cases
			if (!this.options.alwaysPreventMouseWheel) {
				if (this.verticalScrollActive && e.deltaY) {
					if (!(currentScrollTop <= 0 && e.deltaY < 0) && !(currentScrollTop >= maxScrollTop && e.deltaY > 0)) {
						preventFlag = true;
					}
				}

				if (this.horizontalScrollActive && e.deltaX) {
					if (!(currentScrollLeft <= 0 && e.deltaX < 0) && !(currentScrollLeft >= maxScrollLeft && e.deltaX > 0)) {
						preventFlag = true;
					}
				}

				if (!this.verticalScrollActive && !this.horizontalScrollActive) {
					return;
				}
			}

			// prevent default action and scroll item
			if (preventFlag || this.options.alwaysPreventMouseWheel) {
				e.preventDefault();
			} else {
				return;
			}

			extraLeft = e.deltaX / 100 * this.options.mouseWheelStep;
			extraTop = e.deltaY / 100 * this.options.mouseWheelStep;

			this.realElement.scrollTop(currentScrollTop + extraTop);
			this.realElement.scrollLeft(currentScrollLeft + extraLeft);
		},
		setScrollBarEdge: function(edgeSize) {
			this.vBarEdge = edgeSize || 0;
			this.redrawScrollbars();
		},
		saveElementDimensions: function() {
			this.savedDimensions = {
				top: this.realElement.width(),
				left: this.realElement.height()
			};

			return this;
		},
		restoreElementDimensions: function() {
			if (this.savedDimensions) {
				this.realElement.css({
					width: this.savedDimensions.width,
					height: this.savedDimensions.height
				});
			}

			return this;
		},
		saveScrollOffsets: function() {
			this.savedOffsets = {
				top: this.realElement.scrollTop(),
				left: this.realElement.scrollLeft()
			};

			return this;
		},
		restoreScrollOffsets: function() {
			if (this.savedOffsets) {
				this.realElement.scrollTop(this.savedOffsets.top);
				this.realElement.scrollLeft(this.savedOffsets.left);
			}

			return this;
		},
		getContainerDimensions: function() {
			// save current styles
			let desiredDimensions,
				currentStyles,
				currentHeight,
				currentWidth;

			if (this.isModifiedStyles) {
				desiredDimensions = {
					width: this.realElement.innerWidth() + this.vBar.getThickness(),
					height: this.realElement.innerHeight() + this.hBar.getThickness()
				};
			} else {
				// unwrap real element and measure it according to CSS
				this.saveElementDimensions().saveScrollOffsets();
				this.realElement.insertAfter(this.scrollWrapper);
				this.scrollWrapper.detach();

				// measure element
				currentStyles = this.realElement.prop('style');
				currentWidth = parseFloat(currentStyles.width);
				currentHeight = parseFloat(currentStyles.height);

				// reset styles if needed
				if (this.embeddedDimensions && currentWidth && currentHeight) {
					this.isModifiedStyles |= (currentWidth !== this.embeddedDimensions.width || currentHeight !== this.embeddedDimensions.height);

					this.realElement.css({
						overflow: '',
						width: '',
						height: ''
					});
				}

				// calculate desired dimensions for real element
				desiredDimensions = {
					width: this.realElement.outerWidth(),
					height: this.realElement.outerHeight()
				};

				// restore structure and original scroll offsets
				this.scrollWrapper.insertAfter(this.realElement);
				this.realElement.css('overflow', this.options.ios && this.options.ios >= 10 ? 'auto' : 'hidden').prependTo(this.scrollWrapper);
				this.restoreElementDimensions().restoreScrollOffsets();
			}

			return desiredDimensions;
		},
		getEmbeddedDimensions: function(dimensions) {
			// handle scrollbars cropping
			let fakeBarWidth = this.vBar.getThickness(),
				fakeBarHeight = this.hBar.getThickness(),
				paddingWidth = this.realElement.outerWidth() - this.realElement.width(),
				paddingHeight = this.realElement.outerHeight() - this.realElement.height(),
				resultDimensions;

			if (this.options.alwaysShowScrollbars) {
				// simply return dimensions without custom scrollbars
				this.verticalScrollActive = true;
				this.horizontalScrollActive = true;

				resultDimensions = {
					innerWidth: dimensions.width - fakeBarWidth,
					innerHeight: dimensions.height - fakeBarHeight
				};
			} else {
				// detect when to display each scrollbar
				this.saveElementDimensions();
				this.verticalScrollActive = false;
				this.horizontalScrollActive = false;

				// fill container with full size
				this.realElement.css({
					width: dimensions.width - paddingWidth,
					height: dimensions.height - paddingHeight
				});

				this.horizontalScrollActive = this.realElement.prop('scrollWidth') > this.containerDimensions.width;
				this.verticalScrollActive = this.realElement.prop('scrollHeight') > this.containerDimensions.height;

				this.restoreElementDimensions();

				resultDimensions = {
					innerWidth: dimensions.width - (this.verticalScrollActive ? fakeBarWidth : 0),
					innerHeight: dimensions.height - (this.horizontalScrollActive ? fakeBarHeight : 0)
				};
			}

			$.extend(resultDimensions, {
				width: resultDimensions.innerWidth - paddingWidth,
				height: resultDimensions.innerHeight - paddingHeight
			});

			return resultDimensions;
		},
		rebuildScrollbars: function() {
			// resize wrapper according to real element styles
			this.containerDimensions = this.getContainerDimensions();
			this.embeddedDimensions = this.getEmbeddedDimensions(this.containerDimensions);

			// resize wrapper to desired dimensions
			this.scrollWrapper.css({
				width: this.containerDimensions.width,
				height: this.containerDimensions.height
			});

			// resize element inside wrapper excluding scrollbar size
			this.realElement.css({
				overflow: this.options.ios && this.options.ios >= 10 ? 'auto' : 'hidden',
				width: this.embeddedDimensions.width,
				height: this.embeddedDimensions.height
			});

			// redraw scrollbar offset
			this.redrawScrollbars();
		},
		redrawScrollbars: function() {
			let viewSize, maxScrollValue;

			// redraw vertical scrollbar
			if (this.verticalScrollActive) {
				viewSize = this.vBarEdge ? this.containerDimensions.height - this.vBarEdge : this.embeddedDimensions.innerHeight;
				maxScrollValue = Math.max(this.realElement.prop('offsetHeight'), this.realElement.prop('scrollHeight')) - this.vBarEdge;

				this.vBar.show().setMaxValue(maxScrollValue - viewSize).setRatio(viewSize / maxScrollValue).setSize(viewSize);
				this.vBar.setValue(this.realElement.scrollTop());
			} else {
				this.vBar.hide();
			}

			// redraw horizontal scrollbar
			if (this.horizontalScrollActive) {
				viewSize = this.embeddedDimensions.innerWidth;
				maxScrollValue = this.realElement.prop('scrollWidth');

				if (maxScrollValue === viewSize) {
					this.horizontalScrollActive = false;
				}

				this.hBar.show().setMaxValue(maxScrollValue - viewSize).setRatio(viewSize / maxScrollValue).setSize(viewSize);
				this.hBar.setValue(this.realElement.scrollLeft());
			} else {
				this.hBar.hide();
			}

			// set "touch-action" style rule
			let touchAction = '';

			if (this.verticalScrollActive && this.horizontalScrollActive) {
				touchAction = 'none';
			} else if (this.verticalScrollActive) {
				touchAction = 'pan-x';
			} else if (this.horizontalScrollActive) {
				touchAction = 'pan-y';
			}

			this.realElement.css('touchAction', touchAction);
		},
		refresh: function() {
			this.rebuildScrollbars();
		},
		destroy: function() {
			// remove event listeners
			this.win.off('resize orientationchange load', this.onResize);

			this.realElement.off({
				'jcf-mousewheel': this.onMouseWheel,
				'jcf-pointerdown': this.onTouchBody
			});

			this.doc.off({
				'jcf-pointermove': this.onMoveBody,
				'jcf-pointerup': this.onReleaseBody
			});

			// restore structure
			this.saveScrollOffsets();
			this.vBar.destroy();
			this.hBar.destroy();

			this.realElement.insertAfter(this.scrollWrapper).css({
				touchAction: '',
				overflow: '',
				width: '',
				height: ''
			});

			this.scrollWrapper.remove();
			this.restoreScrollOffsets();
		}
	});

	// custom scrollbar
	function ScrollBar(options) {
		this.options = $.extend({
			holder: null,
			vertical: true,
			inactiveClass: 'jcf-inactive',
			verticalClass: 'jcf-scrollbar-vertical',
			horizontalClass: 'jcf-scrollbar-horizontal',
			scrollbarStructure: '<div class="jcf-scrollbar"><div class="jcf-scrollbar-dec"></div><div class="jcf-scrollbar-slider"><div class="jcf-scrollbar-handle"></div></div><div class="jcf-scrollbar-inc"></div></div>',
			btnDecSelector: '.jcf-scrollbar-dec',
			btnIncSelector: '.jcf-scrollbar-inc',
			sliderSelector: '.jcf-scrollbar-slider',
			handleSelector: '.jcf-scrollbar-handle',
			scrollInterval: 300,
			scrollStep: 400 // px/sec
		}, options);

		this.init();
	}

	$.extend(ScrollBar.prototype, {
		init: function() {
			this.initStructure();
			this.attachEvents();
		},
		initStructure: function() {
			// define proporties
			this.doc = $(document);
			this.isVertical = !!this.options.vertical;
			this.sizeProperty = this.isVertical ? 'height' : 'width';
			this.fullSizeProperty = this.isVertical ? 'outerHeight' : 'outerWidth';
			this.invertedSizeProperty = this.isVertical ? 'width' : 'height';
			this.thicknessMeasureMethod = 'outer' + this.invertedSizeProperty.charAt(0).toUpperCase() + this.invertedSizeProperty.substr(1);
			this.offsetProperty = this.isVertical ? 'top' : 'left';
			this.offsetEventProperty = this.isVertical ? 'pageY' : 'pageX';

			// initialize variables
			this.value = this.options.value || 0;
			this.maxValue = this.options.maxValue || 0;
			this.currentSliderSize = 0;
			this.handleSize = 0;

			// find elements
			this.holder = $(this.options.holder);
			this.scrollbar = $(this.options.scrollbarStructure).appendTo(this.holder);
			this.btnDec = this.scrollbar.find(this.options.btnDecSelector);
			this.btnInc = this.scrollbar.find(this.options.btnIncSelector);
			this.slider = this.scrollbar.find(this.options.sliderSelector);
			this.handle = this.slider.find(this.options.handleSelector);

			// set initial styles
			this.scrollbar.addClass(this.isVertical ? this.options.verticalClass : this.options.horizontalClass).css({
				touchAction: this.isVertical ? 'pan-x' : 'pan-y',
				position: 'absolute'
			});

			this.slider.css({
				position: 'relative'
			});

			this.handle.css({
				touchAction: 'none',
				position: 'absolute'
			});
		},
		attachEvents: function() {
			this.bindHandlers();
			this.handle.on('jcf-pointerdown', this.onHandlePress);
			this.slider.add(this.btnDec).add(this.btnInc).on('jcf-pointerdown', this.onButtonPress);
		},
		onHandlePress: function(e) {
			if (e.pointerType === 'mouse' && e.button > 1) {
				return;
			} else {
				e.preventDefault();
				this.handleDragActive = true;
				this.sliderOffset = this.slider.offset()[this.offsetProperty];
				this.innerHandleOffset = e[this.offsetEventProperty] - this.handle.offset()[this.offsetProperty];

				this.doc.on('jcf-pointermove', this.onHandleDrag);
				this.doc.on('jcf-pointerup', this.onHandleRelease);
			}
		},
		onHandleDrag: function(e) {
			e.preventDefault();
			this.calcOffset = e[this.offsetEventProperty] - this.sliderOffset - this.innerHandleOffset;
			this.setValue(this.calcOffset / (this.currentSliderSize - this.handleSize) * this.maxValue);
			this.triggerScrollEvent(this.value);
		},
		onHandleRelease: function() {
			this.handleDragActive = false;
			this.doc.off('jcf-pointermove', this.onHandleDrag);
			this.doc.off('jcf-pointerup', this.onHandleRelease);
		},
		onButtonPress: function(e) {
			let direction, clickOffset;

			if (e.pointerType === 'mouse' && e.button > 1) {
				return;
			} else {
				e.preventDefault();

				if (!this.handleDragActive) {
					if (this.slider.is(e.currentTarget)) {
						// slider pressed
						direction = this.handle.offset()[this.offsetProperty] > e[this.offsetEventProperty] ? -1 : 1;
						clickOffset = e[this.offsetEventProperty] - this.slider.offset()[this.offsetProperty];
						this.startPageScrolling(direction, clickOffset);
					} else {
						// scrollbar buttons pressed
						direction = this.btnDec.is(e.currentTarget) ? -1 : 1;
						this.startSmoothScrolling(direction);
					}

					this.doc.on('jcf-pointerup', this.onButtonRelease);
				}
			}
		},
		onButtonRelease: function() {
			this.stopPageScrolling();
			this.stopSmoothScrolling();
			this.doc.off('jcf-pointerup', this.onButtonRelease);
		},
		startPageScrolling: function(direction, clickOffset) {
			const self = this,
				stepValue = direction * self.currentSize;

			// limit checker
			const isFinishedScrolling = function() {
				const handleTop = (self.value / self.maxValue) * (self.currentSliderSize - self.handleSize);

				if (direction > 0) {
					return handleTop + self.handleSize >= clickOffset;
				} else {
					return handleTop <= clickOffset;
				}
			};

			// scroll by page when track is pressed
			const doPageScroll = function() {
				self.value += stepValue;
				self.setValue(self.value);
				self.triggerScrollEvent(self.value);

				if (isFinishedScrolling()) {
					clearInterval(self.pageScrollTimer);
				}
			};

			// start scrolling
			this.pageScrollTimer = setInterval(doPageScroll, this.options.scrollInterval);
			doPageScroll();
		},
		stopPageScrolling: function() {
			clearInterval(this.pageScrollTimer);
		},
		startSmoothScrolling: function(direction) {
			let self = this,
				dt;

			this.stopSmoothScrolling();

			// simple animation functions
			const raf = window.requestAnimationFrame || function(func) {
				setTimeout(func, 16);
			};

			const getTimestamp = function() {
				return Date.now ? Date.now() : new Date().getTime();
			};

			// set animation limit
			const isFinishedScrolling = function() {
				if (direction > 0) {
					return self.value >= self.maxValue;
				} else {
					return self.value <= 0;
				}
			};

			// animation step
			const doScrollAnimation = function() {
				const stepValue = (getTimestamp() - dt) / 1000 * self.options.scrollStep;

				if (self.smoothScrollActive) {
					self.value += stepValue * direction;
					self.setValue(self.value);
					self.triggerScrollEvent(self.value);

					if (!isFinishedScrolling()) {
						dt = getTimestamp();
						raf(doScrollAnimation);
					}
				}
			};

			// start animation
			self.smoothScrollActive = true;
			dt = getTimestamp();
			raf(doScrollAnimation);
		},
		stopSmoothScrolling: function() {
			this.smoothScrollActive = false;
		},
		triggerScrollEvent: function(scrollValue) {
			if (this.options.onScroll) {
				this.options.onScroll(scrollValue);
			}
		},
		getThickness: function() {
			return this.scrollbar[this.thicknessMeasureMethod]();
		},
		setSize: function(size) {
			// resize scrollbar
			const btnDecSize = this.btnDec[this.fullSizeProperty](),
				btnIncSize = this.btnInc[this.fullSizeProperty]();

			// resize slider
			this.currentSize = size;
			this.currentSliderSize = size - btnDecSize - btnIncSize;
			this.scrollbar.css(this.sizeProperty, size);
			this.slider.css(this.sizeProperty, this.currentSliderSize);
			this.currentSliderSize = this.slider[this.sizeProperty]();

			// resize handle
			this.handleSize = Math.round(this.currentSliderSize * this.ratio);
			this.handle.css(this.sizeProperty, this.handleSize);
			this.handleSize = this.handle[this.fullSizeProperty]();

			return this;
		},
		setRatio: function(ratio) {
			this.ratio = ratio;

			return this;
		},
		setMaxValue: function(maxValue) {
			this.maxValue = maxValue;
			this.setValue(Math.min(this.value, this.maxValue));

			return this;
		},
		setValue: function(value) {
			this.value = value;

			if (this.value < 0) {
				this.value = 0;
			} else if (this.value > this.maxValue) {
				this.value = this.maxValue;
			}

			this.refresh();
		},
		setPosition: function(styles) {
			this.scrollbar.css(styles);

			return this;
		},
		hide: function() {
			this.scrollbar.detach();

			return this;
		},
		show: function() {
			this.scrollbar.appendTo(this.holder);

			return this;
		},
		refresh: function() {
			// recalculate handle position
			if (this.value === 0 || this.maxValue === 0) {
				this.calcOffset = 0;
			} else {
				this.calcOffset = (this.value / this.maxValue) * (this.currentSliderSize - this.handleSize);
			}

			this.handle.css(this.offsetProperty, this.calcOffset);

			// toggle inactive classes
			this.btnDec.toggleClass(this.options.inactiveClass, this.value === 0);
			this.btnInc.toggleClass(this.options.inactiveClass, this.value === this.maxValue);
			this.scrollbar.toggleClass(this.options.inactiveClass, this.maxValue === 0);
		},
		destroy: function() {
			// remove event handlers and scrollbar block itself
			this.btnDec.add(this.btnInc).off('jcf-pointerdown', this.onButtonPress);
			this.handle.off('jcf-pointerdown', this.onHandlePress);
			this.doc.off('jcf-pointermove', this.onHandleDrag);
			this.doc.off('jcf-pointerup', this.onHandleRelease);
			this.doc.off('jcf-pointerup', this.onButtonRelease);
			this.stopSmoothScrolling();
			this.stopPageScrolling();
			this.scrollbar.remove();
		}
	});
}(jQuery, this));

/*!
 * JavaScript Custom Forms : Range Module
 *
 * Copyright 2014-2015 PSD2HTML - http://psd2html.com/jcf
 * Released under the MIT license (LICENSE.txt)
 *
 * Version: 1.1.3
 */
(function($) {
	'use strict';

	jcf.addModule({
		name: 'Range',
		selector: 'input[type="range"]',
		options: {
			realElementClass: 'jcf-real-element',
			fakeStructure: '<span class="jcf-range"><span class="jcf-range-wrapper"><span class="jcf-range-track"><span class="jcf-range-handle"></span></span></span></span>',
			dataListMark: '<span class="jcf-range-mark"></span>',
			rangeDisplayWrapper: '<span class="jcf-range-display-wrapper"></span>',
			rangeDisplay: '<span class="jcf-range-display"></span>',
			handleSelector: '.jcf-range-handle',
			trackSelector: '.jcf-range-track',
			activeHandleClass: 'jcf-active-handle',
			verticalClass: 'jcf-vertical',
			orientation: 'horizontal',
			range: false, // or "min", "max", "all"
			dragHandleCenter: true,
			snapToMarks: true,
			snapRadius: 5
		},
		matchElement: function(element) {
			return element.is(this.selector);
		},
		init: function() {
			this.initStructure();
			this.attachEvents();
			this.refresh();
		},
		initStructure: function() {
			const self = this;

			this.page = $('html');
			this.realElement = $(this.options.element).addClass(this.options.hiddenClass);
			this.fakeElement = $(this.options.fakeStructure).insertBefore(this.realElement).prepend(this.realElement);
			this.track = this.fakeElement.find(this.options.trackSelector);
			this.trackHolder = this.track.parent();
			this.handle = this.fakeElement.find(this.options.handleSelector);
			this.createdHandleCount = 0;
			this.activeDragHandleIndex = 0;
			this.isMultiple = this.realElement.prop('multiple') || typeof this.realElement.attr('multiple') === 'string';
			this.values = this.isMultiple ? this.realElement.attr('value').split(',') : [this.realElement.val()];
			this.handleCount = this.isMultiple ? this.values.length : 1;

			// create range display
			this.rangeDisplayWrapper = $(this.options.rangeDisplayWrapper).insertBefore(this.track);

			if (this.options.range === 'min' || this.options.range === 'all') {
				this.rangeMin = $(this.options.rangeDisplay).addClass('jcf-range-min').prependTo(this.rangeDisplayWrapper);
			}

			if (this.options.range === 'max' || this.options.range === 'all') {
				this.rangeMax = $(this.options.rangeDisplay).addClass('jcf-range-max').prependTo(this.rangeDisplayWrapper);
			}

			// clone handles if needed
			while (this.createdHandleCount < this.handleCount) {
				this.createdHandleCount++;
				this.handle.clone().addClass('jcf-index-' + this.createdHandleCount).insertBefore(this.handle);

				// create mid ranges
				if (this.createdHandleCount > 1) {
					if (!this.rangeMid) {
						this.rangeMid = $();
					}

					this.rangeMid = this.rangeMid.add($(this.options.rangeDisplay).addClass('jcf-range-mid').prependTo(this.rangeDisplayWrapper));
				}
			}

			// grab all handles
			this.handle.detach();
			this.handle = null;
			this.handles = this.fakeElement.find(this.options.handleSelector);
			this.handles.eq(0).addClass(this.options.activeHandleClass);

			if (this.realElement.attr('show-tooltip')) {
				this.handles.each(function(i) {
					jQuery('<span class="tooltip-range-value"></span>').text(self.values[i]).appendTo(jQuery(this));
				});
			}

			// handle orientation
			this.isVertical = (this.options.orientation === 'vertical');
			this.directionProperty = this.isVertical ? 'top' : 'left';
			this.offsetProperty = this.isVertical ? 'bottom' : 'left';
			this.eventProperty = this.isVertical ? 'pageY' : 'pageX';
			this.sizeProperty = this.isVertical ? 'height' : 'width';
			this.sizeMethod = this.isVertical ? 'innerHeight' : 'innerWidth';
			this.fakeElement.css('touchAction', this.isVertical ? 'pan-x' : 'pan-y');

			if (this.isVertical) {
				this.fakeElement.addClass(this.options.verticalClass);
			}

			// set initial values
			this.minValue = parseFloat(this.realElement.attr('min'));
			this.maxValue = parseFloat(this.realElement.attr('max'));
			this.stepValue = parseFloat(this.realElement.attr('step')) || 1;

			// check attribute values
			this.minValue = isNaN(this.minValue) ? 0 : this.minValue;
			this.maxValue = isNaN(this.maxValue) ? 100 : this.maxValue;

			// handle range
			if (this.stepValue !== 1) {
				this.maxValue -= (this.maxValue - this.minValue) % this.stepValue;
			}

			this.stepsCount = (this.maxValue - this.minValue) / this.stepValue;
			this.createDataList();
		},
		attachEvents: function() {
			this.realElement.on({
				focus: this.onFocus
			});

			this.trackHolder.on('jcf-pointerdown', this.onTrackPress);
			this.handles.on('jcf-pointerdown', this.onHandlePress);
		},
		createDataList: function() {
			const self = this,
				dataValues = [],
				dataListId = this.realElement.attr('list');

			if (dataListId) {
				$('#' + dataListId).find('option').each(function() {
					let itemValue = parseFloat(this.value || this.innerHTML),
						mark, markOffset;

					if (!isNaN(itemValue)) {
						markOffset = self.valueToOffset(itemValue);

						dataValues.push({
							value: itemValue,
							offset: markOffset
						});

						mark = $(self.options.dataListMark).text(itemValue).attr({
							'data-mark-value': itemValue
						}).css(self.offsetProperty, markOffset + '%').appendTo(self.track);
					}
				});

				if (dataValues.length) {
					self.dataValues = dataValues;
				}
			}
		},
		getDragHandleRange: function(handleIndex) {
			// calculate range for slider with multiple handles
			let minStep = -Infinity,
				maxStep = Infinity;

			if (handleIndex > 0) {
				minStep = this.valueToStepIndex(this.values[handleIndex - 1]);
			}

			if (handleIndex < this.handleCount - 1) {
				maxStep = this.valueToStepIndex(this.values[handleIndex + 1]);
			}

			return {
				minStepIndex: minStep,
				maxStepIndex: maxStep
			};
		},
		getNearestHandle: function(percent) {
			// handle vertical sliders
			if (this.isVertical) {
				percent = 1 - percent;
			}

			// detect closest handle when track is pressed
			let closestHandle = this.handles.eq(0),
				closestDistance = Infinity,
				self = this;

			if (this.handleCount > 1) {
				this.handles.each(function() {
					const handleOffset = parseFloat(this.style[self.offsetProperty]) / 100,
						handleDistance = Math.abs(handleOffset - percent);

					if (handleDistance < closestDistance) {
						closestDistance = handleDistance;
						closestHandle = $(this);
					}
				});
			}

			return closestHandle;
		},
		onTrackPress: function(e) {
			let trackSize, trackOffset, innerOffset;

			e.preventDefault();

			if (!this.realElement.is(':disabled') && !this.activeDragHandle) {
				trackSize = this.track[this.sizeMethod]();
				trackOffset = this.track.offset()[this.directionProperty];
				this.activeDragHandle = this.getNearestHandle((e[this.eventProperty] - trackOffset) / this.trackHolder[this.sizeMethod]());
				this.activeDragHandleIndex = this.handles.index(this.activeDragHandle);
				this.handles.removeClass(this.options.activeHandleClass).eq(this.activeDragHandleIndex).addClass(this.options.activeHandleClass);
				innerOffset = this.activeDragHandle[this.sizeMethod]() / 2;

				this.dragData = {
					trackSize: trackSize,
					innerOffset: innerOffset,
					trackOffset: trackOffset,
					min: trackOffset,
					max: trackOffset + trackSize
				};

				this.page.on({
					'jcf-pointermove': this.onHandleMove,
					'jcf-pointerup': this.onHandleRelease
				});

				if (e.pointerType === 'mouse') {
					this.realElement.focus();
				}

				this.onHandleMove(e);
			}
		},
		onHandlePress: function(e) {
			let trackSize, trackOffset, innerOffset;

			e.preventDefault();

			if (!this.realElement.is(':disabled') && !this.activeDragHandle) {
				this.activeDragHandle = $(e.currentTarget);
				this.activeDragHandleIndex = this.handles.index(this.activeDragHandle);
				this.handles.removeClass(this.options.activeHandleClass).eq(this.activeDragHandleIndex).addClass(this.options.activeHandleClass);
				trackSize = this.track[this.sizeMethod]();
				trackOffset = this.track.offset()[this.directionProperty];
				innerOffset = this.options.dragHandleCenter ? this.activeDragHandle[this.sizeMethod]() / 2 : e[this.eventProperty] - this.handle.offset()[this.directionProperty];

				this.dragData = {
					trackSize: trackSize,
					innerOffset: innerOffset,
					trackOffset: trackOffset,
					min: trackOffset,
					max: trackOffset + trackSize
				};

				this.page.on({
					'jcf-pointermove': this.onHandleMove,
					'jcf-pointerup': this.onHandleRelease
				});

				if (e.pointerType === 'mouse') {
					this.realElement.focus();
				}
			}
		},
		onHandleMove: function(e) {
			let self = this,
				newOffset, dragPercent, stepIndex, valuePercent, handleDragRange;

			// calculate offset
			if (this.isVertical) {
				newOffset = this.dragData.max + (this.dragData.min - e[this.eventProperty]) - this.dragData.innerOffset;
			} else {
				newOffset = e[this.eventProperty] - this.dragData.innerOffset;
			}

			// fit in range
			if (newOffset < this.dragData.min) {
				newOffset = this.dragData.min;
			} else if (newOffset > this.dragData.max) {
				newOffset = this.dragData.max;
			}

			e.preventDefault();

			if (this.options.snapToMarks && this.dataValues) {
				// snap handle to marks
				const dragOffset = newOffset - this.dragData.trackOffset;

				dragPercent = (newOffset - this.dragData.trackOffset) / this.dragData.trackSize * 100;

				$.each(this.dataValues, function(index, item) {
					const markOffset = item.offset / 100 * self.dragData.trackSize,
						markMin = markOffset - self.options.snapRadius,
						markMax = markOffset + self.options.snapRadius;

					if (dragOffset >= markMin && dragOffset <= markMax) {
						dragPercent = item.offset;

						return false;
					}
				});
			} else {
				// snap handle to steps
				dragPercent = (newOffset - this.dragData.trackOffset) / this.dragData.trackSize * 100;
			}

			// move handle only in range
			stepIndex = Math.round(dragPercent * this.stepsCount / 100);

			if (this.handleCount > 1) {
				handleDragRange = this.getDragHandleRange(this.activeDragHandleIndex);

				if (stepIndex < handleDragRange.minStepIndex) {
					stepIndex = Math.max(handleDragRange.minStepIndex, stepIndex);
				} else if (stepIndex > handleDragRange.maxStepIndex) {
					stepIndex = Math.min(handleDragRange.maxStepIndex, stepIndex);
				}
			}

			valuePercent = stepIndex * (100 / this.stepsCount);

			if (this.dragData.stepIndex !== stepIndex) {
				this.dragData.stepIndex = stepIndex;
				this.dragData.offset = valuePercent;
				this.activeDragHandle.css(this.offsetProperty, this.dragData.offset + '%');

				// update value(s) and trigger "input" event
				this.values[this.activeDragHandleIndex] = '' + this.stepIndexToValue(stepIndex);
				this.updateValues();
				this.realElement.trigger('input');
			}
		},
		onHandleRelease: function() {
			let newValue;

			if (typeof this.dragData.offset === 'number') {
				newValue = this.stepIndexToValue(this.dragData.stepIndex);
				this.realElement.val(newValue).trigger('change');
			}

			this.page.off({
				'jcf-pointermove': this.onHandleMove,
				'jcf-pointerup': this.onHandleRelease
			});

			delete this.activeDragHandle;
			delete this.dragData;
		},
		onFocus: function() {
			if (!this.fakeElement.hasClass(this.options.focusClass)) {
				this.fakeElement.addClass(this.options.focusClass);

				this.realElement.on({
					blur: this.onBlur,
					keydown: this.onKeyPress
				});
			}
		},
		onBlur: function() {
			this.fakeElement.removeClass(this.options.focusClass);

			this.realElement.off({
				blur: this.onBlur,
				keydown: this.onKeyPress
			});
		},
		onKeyPress: function(e) {
			const incValue = (e.which === 38 || e.which === 39),
				decValue = (e.which === 37 || e.which === 40);

			// handle TAB key in slider with multiple handles
			if (e.which === 9 && this.handleCount > 1) {
				if (e.shiftKey && this.activeDragHandleIndex > 0) {
					this.activeDragHandleIndex--;
				} else if (!e.shiftKey && this.activeDragHandleIndex < this.handleCount - 1) {
					this.activeDragHandleIndex++;
				} else {
					return;
				}

				e.preventDefault();
				this.handles.removeClass(this.options.activeHandleClass).eq(this.activeDragHandleIndex).addClass(this.options.activeHandleClass);
			}

			// handle cursor keys
			if (decValue || incValue) {
				e.preventDefault();
				this.step(incValue ? this.stepValue : -this.stepValue);
			}
		},
		updateValues: function() {
			const self = this;
			const value = this.values.join(',');

			if (this.values.length > 1) {
				this.realElement.prop('valueLow', this.values[0]);
				this.realElement.prop('valueHigh', this.values[this.values.length - 1]);
				this.realElement.val(value);

				if (this.realElement.attr('show-tooltip')) {
					this.handles.find('.tooltip-range-value').each(function(i) {
						jQuery(this).text(self.values[i]);
					});
				}

				// if browser does not accept multiple values set only one
				if (this.realElement.val() !== value) {
					this.realElement.val(this.values[this.values.length - 1]);
				}
			} else {
				this.realElement.val(value);
			}

			this.updateRanges();
		},
		updateRanges: function() {
			// update display ranges
			let self = this,
				handle;

			if (this.rangeMin) {
				handle = this.handles[0];
				this.rangeMin.css(this.offsetProperty, 0).css(this.sizeProperty, handle.style[this.offsetProperty]);
			}

			if (this.rangeMax) {
				handle = this.handles[this.handles.length - 1];
				this.rangeMax.css(this.offsetProperty, handle.style[this.offsetProperty]).css(this.sizeProperty, 100 - parseFloat(handle.style[this.offsetProperty]) + '%');
			}

			if (this.rangeMid) {
				this.handles.each(function(index, curHandle) {
					let prevHandle, midBox;

					if (index > 0) {
						prevHandle = self.handles[index - 1];
						midBox = self.rangeMid[index - 1];
						midBox.style[self.offsetProperty] = prevHandle.style[self.offsetProperty];
						midBox.style[self.sizeProperty] = parseFloat(curHandle.style[self.offsetProperty]) - parseFloat(prevHandle.style[self.offsetProperty]) + '%';
					}
				});
			}

			if (this.realElement.attr('show-tooltip')) {
				this.handles.find('.tooltip-range-value').each(function(i) {
					jQuery(this).text(self.values[i]);
				});
			}
		},
		step: function(changeValue) {
			let originalValue = parseFloat(this.values[this.activeDragHandleIndex || 0]),
				newValue = originalValue,
				minValue = this.minValue,
				maxValue = this.maxValue;

			if (isNaN(originalValue)) {
				newValue = 0;
			}

			newValue += changeValue;

			if (this.handleCount > 1) {
				if (this.activeDragHandleIndex > 0) {
					minValue = parseFloat(this.values[this.activeDragHandleIndex - 1]);
				}

				if (this.activeDragHandleIndex < this.handleCount - 1) {
					maxValue = parseFloat(this.values[this.activeDragHandleIndex + 1]);
				}
			}

			if (newValue > maxValue) {
				newValue = maxValue;
			} else if (newValue < minValue) {
				newValue = minValue;
			}

			if (newValue !== originalValue) {
				this.values[this.activeDragHandleIndex || 0] = '' + newValue;
				this.updateValues();
				this.realElement.trigger('input').trigger('change');
				this.setSliderValue(this.values);
			}
		},
		valueToStepIndex: function(value) {
			return (value - this.minValue) / this.stepValue;
		},
		stepIndexToValue: function(stepIndex) {
			return this.minValue + this.stepValue * stepIndex;
		},
		valueToOffset: function(value) {
			const range = this.maxValue - this.minValue,
				percent = (value - this.minValue) / range;

			return percent * 100;
		},
		getSliderValue: function() {
			return $.map(this.values, function(value) {
				return parseFloat(value) || 0;
			});
		},
		setSliderValue: function(values) {
			// set handle position accordion according to value
			const self = this;

			this.handles.each(function(index, handle) {
				handle.style[self.offsetProperty] = self.valueToOffset(values[index]) + '%';
			});
		},
		refresh: function() {
			// handle disabled state
			const isDisabled = this.realElement.is(':disabled');

			this.fakeElement.toggleClass(this.options.disabledClass, isDisabled);

			// refresh handle position according to current value
			this.setSliderValue(this.getSliderValue());
			this.updateRanges();
		},
		destroy: function() {
			this.realElement.removeClass(this.options.hiddenClass).insertBefore(this.fakeElement);
			this.fakeElement.remove();

			this.realElement.off({
				keydown: this.onKeyPress,
				focus: this.onFocus,
				blur: this.onBlur
			});
		}
	});
}(jQuery));

/*
 * jQuery Open/Close plugin
 */
(function($) {
	function OpenClose(options) {
		this.options = $.extend({
			addClassBeforeAnimation: true,
			hideOnClickOutside: false,
			activeClass: 'active',
			opener: '.opener',
			slider: '.slide',
			animSpeed: 400,
			effect: 'fade',
			event: 'click'
		}, options);

		this.init();
	}

	OpenClose.prototype = {
		init: function() {
			if (this.options.holder) {
				this.findElements();
				this.attachEvents();
				this.makeCallback('onInit', this);
			}
		},
		findElements: function() {
			this.holder = $(this.options.holder);
			this.opener = this.holder.find(this.options.opener);
			this.slider = this.holder.find(this.options.slider);
		},
		attachEvents: function() {
			// add handler
			const self = this;

			this.eventHandler = function(e) {
				e.preventDefault();

				if (self.slider.hasClass(slideHiddenClass)) {
					self.showSlide();
				} else {
					self.hideSlide();
				}
			};

			self.opener.on(self.options.event, this.eventHandler);

			// hover mode handler
			if (self.options.event === 'hover') {
				self.opener.on('mouseenter', function() {
					if (!self.holder.hasClass(self.options.activeClass)) {
						self.showSlide();
					}
				});

				self.holder.on('mouseleave', function() {
					self.hideSlide();
				});
			}

			// outside click handler
			self.outsideClickHandler = function(e) {
				if (self.options.hideOnClickOutside) {
					const target = $(e.target);

					if (!target.is(self.holder) && !target.closest(self.holder).length) {
						self.hideSlide();
					}
				}
			};

			// set initial styles
			if (this.holder.hasClass(this.options.activeClass)) {
				$(document).on('click touchstart', self.outsideClickHandler);
			} else {
				this.slider.addClass(slideHiddenClass);
			}
		},
		showSlide: function() {
			const self = this;

			if (self.options.addClassBeforeAnimation) {
				self.holder.addClass(self.options.activeClass);
			}

			self.slider.removeClass(slideHiddenClass);
			$(document).on('click touchstart', self.outsideClickHandler);

			self.makeCallback('animStart', true);

			toggleEffects[self.options.effect].show({
				box: self.slider,
				speed: self.options.animSpeed,
				complete: function() {
					if (!self.options.addClassBeforeAnimation) {
						self.holder.addClass(self.options.activeClass);
					}

					self.makeCallback('animEnd', true);
				}
			});
		},
		hideSlide: function() {
			const self = this;

			if (self.options.addClassBeforeAnimation) {
				self.holder.removeClass(self.options.activeClass);
			}

			$(document).off('click touchstart', self.outsideClickHandler);

			self.makeCallback('animStart', false);

			toggleEffects[self.options.effect].hide({
				box: self.slider,
				speed: self.options.animSpeed,
				complete: function() {
					if (!self.options.addClassBeforeAnimation) {
						self.holder.removeClass(self.options.activeClass);
					}

					self.slider.addClass(slideHiddenClass);
					self.makeCallback('animEnd', false);
				}
			});
		},
		destroy: function() {
			this.slider.removeClass(slideHiddenClass).css({
				display: ''
			});

			this.opener.off(this.options.event, this.eventHandler);
			this.holder.removeClass(this.options.activeClass).removeData('OpenClose');
			$(document).off('click touchstart', this.outsideClickHandler);
		},
		makeCallback: function(name) {
			if (typeof this.options[name] === 'function') {
				const args = Array.prototype.slice.call(arguments);

				args.shift();
				this.options[name].apply(this, args);
			}
		}
	};

	// add stylesheet for slide on DOMReady
	var slideHiddenClass = 'js-slide-hidden';

	(function() {
		const tabStyleSheet = $('<style type="text/css">')[0];
		let tabStyleRule = '.' + slideHiddenClass;

		tabStyleRule += '{position:absolute !important;left:-9999px !important;top:-9999px !important;display:block !important}';

		if (tabStyleSheet.styleSheet) {
			tabStyleSheet.styleSheet.cssText = tabStyleRule;
		} else {
			tabStyleSheet.appendChild(document.createTextNode(tabStyleRule));
		}

		$('head').append(tabStyleSheet);
	}());

	// animation effects
	var toggleEffects = {
		slide: {
			show: function(o) {
				o.box.stop(true).hide().slideDown(o.speed, o.complete);
			},
			hide: function(o) {
				o.box.stop(true).slideUp(o.speed, o.complete);
			}
		},
		fade: {
			show: function(o) {
				o.box.stop(true).hide().fadeIn(o.speed, o.complete);
			},
			hide: function(o) {
				o.box.stop(true).fadeOut(o.speed, o.complete);
			}
		},
		none: {
			show: function(o) {
				o.box.hide().show(0, o.complete);
			},
			hide: function(o) {
				o.box.hide(0, o.complete);
			}
		}
	};

	// jQuery plugin interface
	$.fn.openClose = function(opt) {
		const args = Array.prototype.slice.call(arguments);
		const method = args[0];

		return this.each(function() {
			const $holder = jQuery(this);
			const instance = $holder.data('OpenClose');

			if (typeof opt === 'object' || typeof opt === 'undefined') {
				$holder.data('OpenClose', new OpenClose($.extend({
					holder: this
				}, opt)));
			} else if (typeof method === 'string' && instance) {
				if (typeof instance[method] === 'function') {
					args.shift();
					instance[method].apply(instance, args);
				}
			}
		});
	};
}(jQuery));

/*
* Responsive Layout helper
*/
ResponsiveHelper = (function($) {
	// init variables
	const handlers = [];
	let prevWinWidth;
	const win = $(window);
	let nativeMatchMedia = false;

	// detect match media support
	if (window.matchMedia) {
		if (window.Window && window.matchMedia === Window.prototype.matchMedia) {
			nativeMatchMedia = true;
		} else if (window.matchMedia.toString().indexOf('native') > -1) {
			nativeMatchMedia = true;
		}
	}

	// prepare resize handler
	function resizeHandler() {
		const winWidth = win.width();

		if (winWidth !== prevWinWidth) {
			prevWinWidth = winWidth;

			// loop through range groups
			$.each(handlers, function(index, rangeObject) {
				// disable current active area if needed
				$.each(rangeObject.data, function(property, item) {
					if (item.currentActive && !matchRange(item.range[0], item.range[1])) {
						item.currentActive = false;

						if (typeof item.disableCallback === 'function') {
							item.disableCallback();
						}
					}
				});

				// enable areas that match current width
				$.each(rangeObject.data, function(property, item) {
					if (!item.currentActive && matchRange(item.range[0], item.range[1])) {
						// make callback
						item.currentActive = true;

						if (typeof item.enableCallback === 'function') {
							item.enableCallback();
						}
					}
				});
			});
		}
	}

	win.bind('load resize orientationchange', resizeHandler);

	// test range
	function matchRange(r1, r2) {
		let mediaQueryString = '';

		if (r1 > 0) {
			mediaQueryString += '(min-width: ' + r1 + 'px)';
		}

		if (r2 < Infinity) {
			mediaQueryString += (mediaQueryString ? ' and ' : '') + '(max-width: ' + r2 + 'px)';
		}

		return matchQuery(mediaQueryString, r1, r2);
	}

	// media query function
	function matchQuery(query, r1, r2) {
		if (window.matchMedia && nativeMatchMedia) {
			return matchMedia(query).matches;
		} else if (window.styleMedia) {
			return styleMedia.matchMedium(query);
		} else if (window.media) {
			return media.matchMedium(query);
		} else {
			return prevWinWidth >= r1 && prevWinWidth <= r2;
		}
	}

	// range parser
	function parseRange(rangeStr) {
		const rangeData = rangeStr.split('..');
		const x1 = parseInt(rangeData[0], 10) || -Infinity;
		const x2 = parseInt(rangeData[1], 10) || Infinity;

		return [x1, x2].sort(function(a, b) {
			return a - b;
		});
	}

	// export public functions
	return {
		addRange: function(ranges) {
			// parse data and add items to collection
			const result = {
				data: {}
			};

			$.each(ranges, function(property, data) {
				result.data[property] = {
					range: parseRange(property),
					enableCallback: data.on,
					disableCallback: data.off
				};
			});

			handlers.push(result);

			// call resizeHandler to recalculate all events
			prevWinWidth = null;
			resizeHandler();
		}
	};
}(jQuery));

/*
 * jQuery form validation plugin
 */
(function($) {
	'use strict';

	const FormValidation = (function() {
		const Validator = function($field, $fields) {
			this.$field = $field;
			this.$fields = $fields;
		};

		Validator.prototype = {
			reg: {
				email: '^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$',
				number: '^[0-9]+$'
			},

			checkField: function() {
				return {
					state: this.run(),
					$fields: this.$field.add(this.additionalFields)
				};
			},

			run: function() {
				let fieldType;

				switch (this.$field.get(0).tagName.toUpperCase()) {
					case 'SELECT':
						fieldType = 'select';
						break;

					case 'TEXTAREA':
						fieldType = 'text';
						break;

					default:
						fieldType = this.$field.data('type') || this.$field.attr('type');
				}

				const functionName = 'check_' + fieldType.replace(/-/g, '_');
				let state = true;

				if ($.isFunction(this[functionName])) {
					state = this[functionName]();

					if (state && this.$field.data('confirm')) {
						state = this.check_confirm();
					}
				}

				return state;
			},

			check_email: function() {
				const value = this.getValue();
				const required = this.$field.data('required');
				const requiredOrValue = required || value.length;

				if ((requiredOrValue && !this.check_regexp(value, this.reg.email))) {
					return false;
				}

				return requiredOrValue ? true : null;
			},

			check_number: function() {
				let value = this.getValue();
				const required = this.$field.data('required');
				const isNumber = this.check_regexp(value, this.reg.number);
				const requiredOrValue = required || value.length;

				if (requiredOrValue && !isNumber) {
					return false;
				}

				const min = this.$field.data('min');
				const max = this.$field.data('max');

				value = +value;

				if ((min && (value < min || !isNumber)) || (max && (value > max || !isNumber))) {
					return false;
				}

				return (requiredOrValue || min || max) ? true : null;
			},

			check_password: function() {
				return this.check_text();
			},

			check_text: function() {
				const value = this.getValue();
				const required = this.$field.data('required');

				if (this.$field.data('required') && !value.length) {
					return false;
				}

				const min = +this.$field.data('min');
				const max = +this.$field.data('max');

				if ((min && value.length < min) || (max && value.length > max)) {
					return false;
				}

				const regExp = this.$field.data('regexp');

				if (regExp && !this.check_regexp(value, regExp)) {
					return false;
				}

				return (required || min || max || regExp) ? true : null;
			},

			check_confirm: function() {
				const value = this.getValue();
				const $confirmFields = this.$fields.filter('[data-confirm="' + this.$field.data('confirm') + '"]');
				let confirmState = true;

				for (let i = $confirmFields.length - 1; i >= 0; i--) {
					if ($confirmFields.eq(i).val() !== value || !value.length) {
						confirmState = false;
						break;
					}
				}

				this.additionalFields = $confirmFields;

				return confirmState;
			},

			check_select: function() {
				const required = this.$field.data('required');

				if (required && this.$field.get(0).selectedIndex === 0) {
					return false;
				}

				return required ? true : null;
			},

			check_radio: function() {
				const $fields = this.$fields.filter('[name="' + this.$field.attr('name') + '"]');
				const required = this.$field.data('required');

				if (required && !$fields.filter(':checked').length) {
					return false;
				}

				this.additionalFields = $fields;

				return required ? true : null;
			},

			check_checkbox: function() {
				const required = this.$field.data('required');

				if (required && !this.$field.prop('checked')) {
					return false;
				}

				return required ? true : null;
			},

			check_at_least_one: function() {
				const $fields = this.$fields.filter('[data-name="' + this.$field.data('name') + '"]');

				if (!$fields.filter(':checked').length) {
					return false;
				}

				this.additionalFields = $fields;

				return true;
			},

			check_regexp: function(val, exp) {
				return new RegExp(exp).test(val);
			},

			getValue: function() {
				if (this.$field.data('trim')) {
					this.$field.val($.trim(this.$field.val()));
				}

				return this.$field.val();
			}
		};

		const publicClass = function(form, options) {
			this.$form = $(form).attr('novalidate', 'novalidate');
			this.options = options;
		};

		publicClass.prototype = {
			buildSelector: function(input) {
				return input + ':not(' + this.options.skipDefaultFields + (this.options.skipFields ? ',' + this.options.skipFields : '') + ')';
			},

			init: function() {
				this.fieldsSelector = this.buildSelector(':input');

				this.$form
					.on('submit', this.submitHandler.bind(this))
					.on('keyup blur', this.fieldsSelector, this.changeHandler.bind(this))
					.on('change', this.buildSelector('select'), this.changeHandler.bind(this))
					.on('focus', this.fieldsSelector, this.focusHandler.bind(this));
			},

			submitHandler: function(e) {
				const self = this;
				const $fields = this.getFormFields();

				this.getClassTarget($fields)
					.removeClass(this.options.errorClass + ' ' + this.options.successClass);

				this.setFormState(true);

				$fields.each(function(i, input) {
					const $field = $(input);
					const $classTarget = self.getClassTarget($field);

					// continue iteration if $field has error class already
					if ($classTarget.hasClass(self.options.errorClass)) {
						return;
					}

					self.setState(new Validator($field, $fields).checkField());
				});

				return this.checkSuccess($fields, e);
			},

			checkSuccess: function($fields, e) {
				const self = this;

				const success = this.getClassTarget($fields || this.getFormFields())
									.filter('.' + this.options.errorClass).length === 0;

				if (e && success && this.options.successSendClass) {
					e.preventDefault();

					$.ajax({
						url: this.$form.removeClass(this.options.successSendClass).attr('action') || '/',
						type: this.$form.attr('method') || 'POST',
						data: this.$form.serialize() + '&action=register_user',
						success: function(data) {
							self.$form.addClass(self.options.successSendClass);
							self.makeCallback('onSuccess', data);
						},
						error: function(data) {
							self.makeCallback('onError', data);
						}
					});
				}

				this.setFormState(success);

				return success;
			},

			changeHandler: function(e) {
				const $field = $(e.target);

				if ($field.data('interactive')) {
					this.setState(new Validator($field, this.getFormFields()).checkField());
				}

				this.checkSuccess();
			},

			focusHandler: function(e) {
				const $field = $(e.target);

				this.getClassTarget($field)
					.removeClass(this.options.errorClass + ' ' + this.options.successClass);

				this.checkSuccess();
			},

			setState: function(result) {
				this.getClassTarget(result.$fields)
					.toggleClass(this.options.errorClass, result.state !== null && !result.state)
					.toggleClass(this.options.successClass, result.state !== null && this.options.successClass && !!result.state);
			},

			setFormState: function(state) {
				if (this.options.errorFormClass) {
					this.$form.toggleClass(this.options.errorFormClass, !state);
				}
			},

			makeCallback: function(name) {
				if (typeof this.options[name] === 'function') {
					const args = Array.prototype.slice.call(arguments);

					args.shift();
					this.options[name].apply(this, args);
				}
			},

			getClassTarget: function($input) {
				return (this.options.addClassToParent ? $input.closest(this.options.addClassToParent) : $input);
			},

			getFormFields: function() {
				return this.$form.find(this.fieldsSelector);
			}
		};

		return publicClass;
	}());

	$.fn.formValidation = function(options) {
		options = $.extend({}, {
			errorClass: 'input-error',
			successClass: '',
			errorFormClass: '',
			addClassToParent: '',
			skipDefaultFields: ':button, :submit, :image, :hidden, :reset',
			skipFields: '',
			successSendClass: ''
		}, options);

		return this.each(function() {
			new FormValidation(this, options).init();
		});
	};
}(jQuery));

/*
 * jQuery sticky box plugin
 */
(function($, $win) {
	'use strict';

	function StickyScrollBlock($stickyBox, options) {
		this.options = options;
		this.$stickyBox = $stickyBox;
		this.init();
	}

	const StickyScrollBlockPrototype = {
		init: function() {
			this.findElements();
			this.attachEvents();
			this.makeCallback('onInit');
		},

		findElements: function() {
			// find parent container in which will be box move
			this.$container = this.$stickyBox.closest(this.options.container);
			// define box wrap flag
			this.isWrap = this.options.positionType === 'fixed' && this.options.setBoxHeight;
			// define box move flag
			this.moveInContainer = !!this.$container.length;

			// wrapping box to set place in content
			if (this.isWrap) {
				this.$stickyBoxWrap = this.$stickyBox.wrap('<div class="' + this.getWrapClass() + '"/>').parent();
			}

			//define block to add active class
			this.parentForActive = this.getParentForActive();
			this.isInit = true;
		},

		attachEvents: function() {
			const self = this;

			// bind events
			this.onResize = function() {
				if (!self.isInit) return;
				self.resetState();
				self.recalculateOffsets();
				self.checkStickyPermission();
				self.scrollHandler();
			};

			this.onScroll = function() {
				self.scrollHandler();
			};

			// initial handler call
			this.onResize();

			// handle events
			$win.on('load resize orientationchange refreshStikyBlock', this.onResize)
				.on('scroll', this.onScroll);
		},

		defineExtraTop: function() {
			// define box's extra top dimension
			let extraTop;

			if (typeof this.options.extraTop === 'number') {
				extraTop = this.options.extraTop;
			} else if (typeof this.options.extraTop === 'function') {
				extraTop = this.options.extraTop();
			}

			this.extraTop = this.options.positionType === 'absolute'
				? extraTop
				: Math.min(this.winParams.height - this.data.boxFullHeight, extraTop);
		},

		checkStickyPermission: function() {
			// check the permission to set sticky
			this.isStickyEnabled = this.moveInContainer
				? this.data.containerOffsetTop + this.data.containerHeight > this.data.boxFullHeight + this.data.boxOffsetTop + this.options.extraBottom
				: true;
		},

		getParentForActive: function() {
			if (this.isWrap) {
				return this.$stickyBoxWrap;
			}

			if (this.$container.length) {
				return this.$container;
			}

			return this.$stickyBox;
		},

		getWrapClass: function() {
			// get set of container classes
			try {
				return this.$stickyBox.attr('class').split(' ').map(function(name) {
					return 'sticky-wrap-' + name;
				}).join(' ');
			} catch (err) {
				return 'sticky-wrap';
			}
		},

		resetState: function() {
			// reset dimensions and state
			this.stickyFlag = false;

			this.$stickyBox.css({
				'-webkit-transition': '',
				'-webkit-transform': '',
				transition: '',
				transform: '',
				position: '',
				width: '',
				left: '',
				top: ''
			}).removeClass(this.options.activeClass);

			if (this.isWrap) {
				this.$stickyBoxWrap.removeClass(this.options.activeClass).removeAttr('style');
			}

			if (this.moveInContainer) {
				this.$container.removeClass(this.options.activeClass);
			}
		},

		recalculateOffsets: function() {
			// define box and container dimensions
			this.winParams = this.getWindowParams();

			this.data = $.extend(
				this.getBoxOffsets(),
				this.getContainerOffsets()
			);

			this.defineExtraTop();
		},

		getBoxOffsets: function() {
			function offetTop(obj) {
				obj.top = 0;

				return obj;
			}

			const boxOffset = this.$stickyBox.css('position') === 'fixed' ? offetTop(this.$stickyBox.offset()) : this.$stickyBox.offset();
			const boxPosition = this.$stickyBox.position();

			return {
				// sticky box offsets
				boxOffsetLeft: boxOffset.left,
				boxOffsetTop: boxOffset.top,
				// sticky box positions
				boxTopPosition: boxPosition.top,
				boxLeftPosition: boxPosition.left,
				// sticky box width/height
				boxFullHeight: this.$stickyBox.outerHeight(true),
				boxHeight: this.$stickyBox.outerHeight(),
				boxWidth: this.$stickyBox.outerWidth()
			};
		},

		getContainerOffsets: function() {
			const containerOffset = this.moveInContainer ? this.$container.offset() : null;

			return containerOffset ? {
				// container offsets
				containerOffsetLeft: containerOffset.left,
				containerOffsetTop: containerOffset.top,
				// container height
				containerHeight: this.$container.outerHeight()
			} : {};
		},

		getWindowParams: function() {
			return {
				height: window.innerHeight || document.documentElement.clientHeight
			};
		},

		makeCallback: function(name) {
			if (typeof this.options[name] === 'function') {
				const args = Array.prototype.slice.call(arguments);

				args.shift();
				this.options[name].apply(this, args);
			}
		},

		destroy: function() {
			this.isInit = false;

			// remove event handlers and styles
			$win.off('load resize orientationchange', this.onResize)
				.off('scroll', this.onScroll);

			this.resetState();
			this.$stickyBox.removeData('StickyScrollBlock');

			if (this.isWrap) {
				this.$stickyBox.unwrap();
			}

			this.makeCallback('onDestroy');
		}
	};

	const stickyMethods = {
		fixed: {
			scrollHandler: function() {
				this.winScrollTop = $win.scrollTop();

				const isActiveSticky = this.winScrollTop
					- (this.options.showAfterScrolled ? this.extraTop : 0)
					- (this.options.showAfterScrolled ? this.data.boxHeight + this.extraTop : 0)
					> this.data.boxOffsetTop - this.extraTop;

				if (isActiveSticky) {
					this.isStickyEnabled && this.stickyOn();
				} else {
					this.stickyOff();
				}
			},

			stickyOn: function() {
				if (!this.stickyFlag) {
					this.stickyFlag = true;
					this.parentForActive.addClass(this.options.activeClass);

					this.$stickyBox.css({
						width: this.data.boxWidth,
						position: this.options.positionType
					});

					if (this.isWrap) {
						this.$stickyBoxWrap.css({
							height: this.data.boxFullHeight
						});
					}

					this.makeCallback('fixedOn');
				}

				this.setDynamicPosition();
			},

			stickyOff: function() {
				if (this.stickyFlag) {
					this.stickyFlag = false;
					this.resetState();
					this.makeCallback('fixedOff');
				}
			},

			setDynamicPosition: function() {
				this.$stickyBox.css({
					top: this.getTopPosition(),
					left: this.data.boxOffsetLeft - $win.scrollLeft()
				});
			},

			getTopPosition: function() {
				if (this.moveInContainer) {
					const currScrollTop = this.winScrollTop + this.data.boxHeight + this.options.extraBottom;

					return Math.min(this.extraTop, (this.data.containerHeight + this.data.containerOffsetTop) - currScrollTop);
				} else {
					return this.extraTop;
				}
			}
		},
		absolute: {
			scrollHandler: function() {
				this.winScrollTop = $win.scrollTop();
				const isActiveSticky = this.winScrollTop > this.data.boxOffsetTop - this.extraTop;

				if (isActiveSticky) {
					this.isStickyEnabled && this.stickyOn();
				} else {
					this.stickyOff();
				}
			},

			stickyOn: function() {
				if (!this.stickyFlag) {
					this.stickyFlag = true;
					this.parentForActive.addClass(this.options.activeClass);

					this.$stickyBox.css({
						width: this.data.boxWidth,
						transition: 'transform ' + this.options.animSpeed + 's ease',
						'-webkit-transition': 'transform ' + this.options.animSpeed + 's ease'
					});

					if (this.isWrap) {
						this.$stickyBoxWrap.css({
							height: this.data.boxFullHeight
						});
					}

					this.makeCallback('fixedOn');
				}

				this.clearTimer();

				this.timer = setTimeout(function() {
					this.setDynamicPosition();
				}.bind(this), this.options.animDelay * 1000);
			},

			stickyOff: function() {
				if (this.stickyFlag) {
					this.clearTimer();
					this.stickyFlag = false;

					this.timer = setTimeout(function() {
						this.setDynamicPosition();

						setTimeout(function() {
							this.resetState();
						}.bind(this), this.options.animSpeed * 1000);
					}.bind(this), this.options.animDelay * 1000);

					this.makeCallback('fixedOff');
				}
			},

			clearTimer: function() {
				clearTimeout(this.timer);
			},

			setDynamicPosition: function() {
				const topPosition = Math.max(0, this.getTopPosition());

				this.$stickyBox.css({
					transform: 'translateY(' + topPosition + 'px)',
					'-webkit-transform': 'translateY(' + topPosition + 'px)'
				});
			},

			getTopPosition: function() {
				const currTopPosition = this.winScrollTop - this.data.boxOffsetTop + this.extraTop;

				if (this.moveInContainer) {
					const currScrollTop = this.winScrollTop + this.data.boxHeight + this.options.extraBottom;
					const diffOffset = Math.abs(Math.min(0, (this.data.containerHeight + this.data.containerOffsetTop) - currScrollTop - this.extraTop));

					return currTopPosition - diffOffset;
				} else {
					return currTopPosition;
				}
			}
		}
	};

	// jQuery plugin interface
	$.fn.stickyScrollBlock = function(opt) {
		const args = Array.prototype.slice.call(arguments);
		const method = args[0];

		const options = $.extend({
			container: null,
			positionType: 'fixed', // 'fixed' or 'absolute'
			activeClass: 'fixed-position',
			setBoxHeight: true,
			showAfterScrolled: false,
			extraTop: 0,
			extraBottom: 0,
			animDelay: 0.1,
			animSpeed: 0.2
		}, opt);

		return this.each(function() {
			const $stickyBox = jQuery(this);
			const instance = $stickyBox.data('StickyScrollBlock');

			if (typeof opt === 'object' || typeof opt === 'undefined') {
				StickyScrollBlock.prototype = $.extend(stickyMethods[options.positionType], StickyScrollBlockPrototype);
				$stickyBox.data('StickyScrollBlock', new StickyScrollBlock($stickyBox, options));
			} else if (typeof method === 'string' && instance) {
				if (typeof instance[method] === 'function') {
					args.shift();
					instance[method].apply(instance, args);
				}
			}
		});
	};

	// module exports
	window.StickyScrollBlock = StickyScrollBlock;
}(jQuery, jQuery(window)));

/*
 * jQuery Tabs plugin
 */
(function($, $win) {
	'use strict';

	function Tabset($holder, options) {
		this.$holder = $holder;
		this.options = options;

		this.init();
	}

	Tabset.prototype = {
		init: function() {
			this.$tabLinks = this.$holder.find(this.options.tabLinks);

			this.setStartActiveIndex();
			this.setActiveTab();

			if (this.options.autoHeight) {
				this.$tabHolder = $(this.$tabLinks.eq(0).attr(this.options.attrib)).parent();
			}

			this.makeCallback('onInit', this);
		},

		setStartActiveIndex: function() {
			const $classTargets = this.getClassTarget(this.$tabLinks);
			let $activeLink = $classTargets.filter('.' + this.options.activeClass);
			const $hashLink = this.$tabLinks.filter('[' + this.options.attrib + '="' + location.hash + '"]');
			let activeIndex;

			if (this.options.checkHash && $hashLink.length) {
				$activeLink = $hashLink;
			}

			activeIndex = $classTargets.index($activeLink);

			this.activeTabIndex = this.prevTabIndex = (activeIndex === -1 ? (this.options.defaultTab ? 0 : null) : activeIndex);
		},

		setActiveTab: function() {
			const self = this;

			this.$tabLinks.each(function(i, link) {
				const $link = $(link);
				const $classTarget = self.getClassTarget($link);
				let $tab = $();

				if (self.options.tabAttrib) {
					$tab = $('[' + self.options.tabAttrib + '="' + $link.attr(self.options.attrib) + '"]');
				} else {
					$tab = $($link.attr(self.options.attrib));
				}

				if (i !== self.activeTabIndex) {
					$classTarget.removeClass(self.options.activeClass);
					$tab.addClass(self.options.tabHiddenClass).removeClass(self.options.activeClass);
				} else {
					$classTarget.addClass(self.options.activeClass);
					$tab.removeClass(self.options.tabHiddenClass).addClass(self.options.activeClass);
				}

				self.attachTabLink($link, i);
			});
		},

		attachTabLink: function($link, i) {
			const self = this;

			$link.on(this.options.event + '.tabset', function(e) {
				e.preventDefault();

				if (self.activeTabIndex === self.prevTabIndex && self.activeTabIndex !== i) {
					self.activeTabIndex = i;
					self.switchTabs();
				}

				if (self.options.checkHash) {
					location.hash = jQuery(this).attr('href').split('#')[1];
				}
			});
		},

		resizeHolder: function(height) {
			const self = this;

			if (height) {
				this.$tabHolder.height(height);

				setTimeout(function() {
					self.$tabHolder.addClass('transition');
				}, 10);
			} else {
				self.$tabHolder.removeClass('transition').height('');
			}
		},

		switchTabs: function() {
			const self = this;

			const $prevLink = this.$tabLinks.eq(this.prevTabIndex);
			const $nextLink = this.$tabLinks.eq(this.activeTabIndex);

			const $prevTab = this.getTab($prevLink);
			const $nextTab = this.getTab($nextLink);

			$prevTab.removeClass(this.options.activeClass);

			if (self.haveTabHolder()) {
				this.resizeHolder($prevTab.outerHeight());
			}

			setTimeout(function() {
				self.getClassTarget($prevLink).removeClass(self.options.activeClass);

				$prevTab.addClass(self.options.tabHiddenClass);
				$nextTab.removeClass(self.options.tabHiddenClass).addClass(self.options.activeClass);

				self.getClassTarget($nextLink).addClass(self.options.activeClass);

				if (self.haveTabHolder()) {
					self.resizeHolder($nextTab.outerHeight());

					setTimeout(function() {
						self.resizeHolder();
						self.prevTabIndex = self.activeTabIndex;
					}, self.options.animSpeed);
				} else {
					self.prevTabIndex = self.activeTabIndex;
				}

				self.makeCallback('onChange', self);
			}, this.options.autoHeight ? this.options.animSpeed : 1);
		},

		getClassTarget: function($link) {
			return this.options.addToParent ? $link.parent() : $link;
		},

		getActiveTab: function() {
			return this.getTab(this.$tabLinks.eq(this.activeTabIndex));
		},

		getTab: function($link) {
			if (this.options.tabAttrib) {
				return $('[' + this.options.tabAttrib + '="' + $link.attr(this.options.attrib) + '"]');
			} else {
				return $($link.attr(this.options.attrib));
			}
		},

		haveTabHolder: function() {
			return this.$tabHolder && this.$tabHolder.length;
		},

		destroy: function() {
			const self = this;

			this.$tabLinks.off('.tabset').each(function() {
				const $link = $(this);

				self.getClassTarget($link).removeClass(self.options.activeClass);
				$($link.attr(self.options.attrib)).removeClass(self.options.activeClass + ' ' + self.options.tabHiddenClass);
			});

			this.$holder.removeData('Tabset');
		},

		makeCallback: function(name) {
			if (typeof this.options[name] === 'function') {
				const args = Array.prototype.slice.call(arguments);

				args.shift();
				this.options[name].apply(this, args);
			}
		}
	};

	$.fn.tabset = function(opt) {
		const args = Array.prototype.slice.call(arguments);
		const method = args[0];

		const options = $.extend({
			activeClass: 'active',
			addToParent: false,
			autoHeight: false,
			checkHash: false,
			defaultTab: true,
			animSpeed: 500,
			tabLinks: 'a',
			attrib: 'href',
			tabAttrib: null,
			event: 'click',
			tabHiddenClass: 'js-tab-hidden'
		}, opt);

		options.autoHeight = options.autoHeight;

		return this.each(function() {
			const $holder = jQuery(this);
			const instance = $holder.data('Tabset');

			if (typeof opt === 'object' || typeof opt === 'undefined') {
				$holder.data('Tabset', new Tabset($holder, options));
			} else if (typeof method === 'string' && instance) {
				if (typeof instance[method] === 'function') {
					args.shift();
					instance[method].apply(instance, args);
				}
			}
		});
	};
}(jQuery, jQuery(window)));

/*
* jQuery custom map plugin
*/
(function($) {
	function CustomMap(options) {
		this.options = {
			coordinates: null,
			mapOptions: {
				zoom: 16,
				panControl: true,
				zoomControl: true,
				scrollwheel: false,
				mapTypeControl: false,
				scaleControl: true,
				fullscreenControl: false,
				streetViewControl: false,
				overviewMapControl: false,
				rotateControl: false
			},
			...options
		};

		this.init();
	}

	CustomMap.prototype = {
		init: function() {
			if (!window.google || !window.google.maps) return;

			if (this.options.holder) {
				this.findElements();
				this.attachEvents();
			}
		},
		findElements: function() {
			this.win = $(window);
			this.holder = $(this.options.holder);
			this.location = this.holder.data('coordinates');
			this.zoom = this.holder.data('zoom');
			this.markersArr = [];
		},
		attachEvents: function() {
			const id = Math.random().toString(36).slice(2, 11);

			if (this.zoom) {
				this.options.mapOptions.zoom = this.zoom;
			}

			this.mapOptions = {
				mapId: `map-${id}`,
				...this.options.mapOptions
			};

			this.map = new google.maps.Map(this.holder[0], this.mapOptions);
			this.bounds = new google.maps.LatLngBounds();

			$.getJSON(this.holder.data('markers'), (data) => {
				this.drawMarkers(data.markers);
			});

			if (this.location) {
				const coordinate = this.location.split(',').map((c) => c.trim());

				this.createMarker({
					lat: coordinate[0],
					lng: coordinate[1]
				});
			}

			this.resizeHandler = () => {
				this.refreshMap();
			};

			this.resizeHandler();

			this.win.on('resize', this.resizeHandler);
		},
		drawMarkers: function(data) {
			for (let i = 0; i < data.length; i++) {
				this.createMarker(data[i]);
			}

			this.refreshMap();
		},
		createMarker: function(item) {
			const coordinates = new google.maps.LatLng(item.lat, item.lng);

			const marker = new google.maps.marker.AdvancedMarkerElement({
				position: coordinates,
				map: this.map
			});

			this.markersArr.push(marker);
			this.bounds.extend(coordinates);
		},
		removeMarkers: function() {
			if (!this.markersArr.length) return;

			for (let i = 0; i < this.markersArr.length; i++) {
				this.markersArr[i].setMap(null);
			}

			this.markersArr.length = 0;
			this.bounds = new google.maps.LatLngBounds();
		},
		refreshMap: function() {
			google.maps.event.trigger(this.map, 'resize');

			this.map.fitBounds(this.bounds);

			const listener = google.maps.event.addListener(this.map, 'idle', () => {
				if (this.map.getZoom() > this.options.zoom) {
					this.map.setZoom(this.options.zoom);
				}

				google.maps.event.removeListener(listener);
			});
		}
	};

	$.fn.customMap = function(opt) {
		return this.each(function() {
			$(this).data('CustomMap', new CustomMap($.extend(opt, {
				holder: this
			})));
		});
	};
}(jQuery));

/* video plugin */
(function($) {
	'use strict';

	function BgVideo(options) {
		this.options = $.extend({
			containerClass: 'js-video',
			btnPlay: '.btn-play',
			btnPause: '.btn-pause',
			popupBtnClose: '<a href="#" class="close"></a>',
			loadedClass: 'video-loaded',
			playingClass: 'playing',
			pausedClass: 'paused',
			popupClass: 'js-video-popup',
			activePopupClass: 'active-popup',
			activePageClass: 'active-video-popup',
			fluidVideoClass: 'fluid-video',
			autoplayVideoClass: 'bg-video',
			vimeoAPI: '//player.vimeo.com/api/player.js',
			wistiaAPI: '//fast.wistia.com/assets/external/E-v1.js',
			youtubeAPI: '//www.youtube.com/iframe_api'
		}, options);

		this.init();
	}

	BgVideo.prototype = {
		init: function() {
			if (this.options.holder) {
				this.findElements();
				this.attachEvents();
				this.makeCallback('onInit', this);
			}
		},
		findElements: function() {
			const self = this;

			this.win = $(window);
			this.page = $('body');
			this.holder = $(this.options.holder);
			this.videoContainer = null;
			this.player = null;
			this.videoData = this.holder.data('video');
			this.btnPlay = this.holder.find(this.options.btnPlay);
			this.btnPause = this.holder.find(this.options.btnPause);
			this.autoplay = this.videoData.autoplay === undefined ? true : this.videoData.autoplay;
			this.loop = this.videoData.loop === undefined ? false : this.videoData.loop;
			this.fluidWidth = this.videoData.fluidWidth === undefined ? false : this.videoData.fluidWidth;
			this.lazyLoad = this.videoData.lazyLoad === undefined ? false : this.videoData.lazyLoad;
			this.isPopup = this.holder.is('a');
			this.resizeTimer = null;
			this.loaded = false;

			this.onScroll = function() {
				self.scrollHandler();
			};

			if (this.isPopup) {
				this.popup = $('<div class="' + this.options.popupClass + '"/>').append($(this.options.popupBtnClose)).appendTo(this.page);
				this.holder = this.popup;
				this.btnOpen = $(this.options.holder);
				this.btnClose = this.popup.find('> a');
				this.autoplay = false;
			} else {
				if (this.autoplay) {
					this.holder.addClass(this.options.autoplayVideoClass);
				}

				if (this.fluidWidth) {
					this.holder.addClass(this.options.fluidVideoClass);
				}

				if (!this.lazyLoad) {
					this.initPlayer();
				} else if (!this.holder.closest('.specails-slider').length) {
					this.win.on('scroll', this.onScroll);
				}
			}
		},
		scrollHandler: function() {
			if (this.win.scrollTop() + (this.win.height() * 2) >= this.holder.offset().top && !this.loaded) {
				this.initPlayer();
				this.loaded = true;
				this.win.off('scroll', this.onScroll);
			}
		},
		initPlayer: function() {
			switch (this.videoData.type) {
				case 'youtube':
					this.initYoutube();
					break;
				case 'vimeo':
					this.initVimeo();
					break;
				case 'wistia':
					this.initWistia();
					break;
				case 'html5':
					this.initHTML5();
					break;
				default:
					return false;
			}
		},
		attachEvents: function() {
			const self = this;

			this.playClickHandler = function(e) {
				e.preventDefault();
				self.playVideo();
			};

			this.pauseClickHandler = function(e) {
				e.preventDefault();
				self.pauseVideo();
			};

			this.openClickHandler = function(e) {
				e.preventDefault();
				self.showPopup();
			};

			this.closeClickHandler = function(e) {
				e.preventDefault();
				self.hidePopup();
			};

			this.resizeHandler = function() {
				if (self.videoContainer !== null && !self.fluidWidth) {
					clearTimeout(self.resizeTimer);

					self.resizeTimer = setTimeout(function() {
						self.resizeVideo();
					}, 200);
				}
			};

			this.holder.on('loaded.video', function() {
				self.resizeHandler();
				self.holder.addClass(self.options.loadedClass);
			}).on('playing.video', function() {
				// self.pauseAllVideos();
				self.holder.addClass(self.options.playingClass).removeClass(self.options.pausedClass).trigger('playingVideo');
				self.makeCallback('playingVideo', self);
			}).on('paused.video', function() {
				self.holder.addClass(self.options.pausedClass).trigger('pauseVideo');
				self.makeCallback('pauseVideo', self);
			}).on('ended.video', function() {
				self.holder.removeClass(self.options.playingClass + ' ' + self.options.pausedClass).trigger('endedVideo');
				self.makeCallback('endedVideo', self);
			});

			if (this.isPopup) {
				this.btnOpen.on('click', this.openClickHandler);
				this.btnClose.on('click', this.closeClickHandler);
			}

			this.btnPlay.on('click', this.playClickHandler);
			this.btnPause.on('click', this.pauseClickHandler);
			this.resizeHandler();
			this.win.on('load resize orientationchange', this.resizeHandler);
		},
		initYoutube: function() {
			const self = this;
			const container = $('<div />').addClass(this.options.containerClass).appendTo(this.holder);

			const opt = {
				playlist: this.autoplay ? this.videoData.video : null,
				autoplay: this.autoplay || this.isPopup ? 1 : 0,
				loop: this.loop ? 1 : 0,
				controls: this.autoplay ? 0 : 1,
				showinfo: 0,
				modestbranding: 1,
				disablekb: 1,
				fs: this.autoplay ? 0 : 1,
				cc_load_policy: 0,
				iv_load_policy: 3
			};

			const loadPlayer = function() {
				var player = new YT.Player(container[0], {
					videoId: self.videoData.video,
					playerVars: opt,
					events: {
						onReady: function() {
							if (self.autoplay) {
								player.mute();
							}

							self.videoContainer = self.holder.find('iframe');
							self.holder.trigger('loaded.video');

							self.player = {
								play: function() {
									player.playVideo();
								},
								pause: function() {
									player.pauseVideo();
								}
							};
						},
						onStateChange: function(state) {
							switch (state.data) {
								case 0:
									self.holder.trigger('ended.video');
									break;
								case 1:
									self.holder.trigger('playing.video');
									break;
								case 2:
									self.holder.trigger('paused.video');
									break;
								default:
									break;
							}
						}
					}
				});
			};

			if (typeof YT === 'undefined' || typeof YT.Player === 'undefined') {
				const youtubeReady = window.onYouTubeIframeAPIReady;

				window.onYouTubeIframeAPIReady = function() {
					if (youtubeReady) youtubeReady();
					loadPlayer();
				};

				$.getScript(this.options.youtubeAPI);
			} else {
				loadPlayer();
			}
		},
		initVimeo: function() {
			const self = this;
			const blockId = this.getRandomId();

			const opt = {
				id: this.videoData.video,
				autoplay: this.autoplay || this.isPopup,
				autopause: this.autoplay ? false : true,
				muted: this.autoplay ? true : false,
				loop: this.loop,
				controls: this.autoplay ? false : true,
				byline: this.autoplay ? false : true,
				title: this.autoplay ? false : true
			};

			const loadPlayer = function() {
				self.holder.attr('id', blockId);

				const player = new Vimeo.Player(blockId, opt);

				player.ready().then(function() {
					self.videoContainer = self.holder.find('iframe').addClass(self.options.containerClass);

					self.holder.trigger('loaded.video');

					player.on('play', function() {
						self.holder.trigger('playing.video');
					});

					player.on('pause', function() {
						self.holder.trigger('paused.video');
					});

					player.on('ended', function() {
						self.holder.trigger('ended.video');
					});

					self.player = {
						play: function() {
							player.play();
						},
						pause: function() {
							player.pause();
						}
					};
				});
			};

			if (typeof Vimeo === 'undefined' || typeof Vimeo.Player === 'undefined') {
				$.getScript(this.options.vimeoAPI, loadPlayer);
			} else {
				loadPlayer();
			}
		},
		initWistia: function() {
			const self = this;
			const blockId = this.getRandomId();

			const opt = {
				autoplay: this.isPopup ? true : this.autoplay,
				loop: this.loop,
				volume: this.autoplay ? 0 : 1,
				controls: this.autoplay ? (this.fluidWidth ? true : false) : true,
				endVideoBehavior: this.loop ? 'loop' : 'reset'
			};

			const loadPlayer = function() {
				const src = '//fast.wistia.net/embed/iframe/' + self.videoData.video + '?controlsVisibleOnLoad=false&playbar=' + opt.controls + '&playButton=' + opt.controls + '&autoPlay=' + opt.autoplay + '&endVideoBehavior=' + opt.endVideoBehavior + '&fullscreenButton=' + opt.controls + '&smallPlayButton=false&volume=' + opt.volume + '&volumeControl=' + opt.controls;

				self.videoContainer = $(`<iframe
						allowtransparency="true" id="${blockId}'"
						frameborder="0"
						scrolling="no"
						class="wistia_embed"
						name="wistia_embed"
						title="Video player for ${(self.videoData.title || 'wistia')}"
						aria-label="Video of ${(self.videoData.title || 'wistia')}"
						role="presentation"
					/>`)
					.addClass(self.options.containerClass)
					.appendTo(self.holder)
					.attr('src', src);

				window._wq = window._wq || [];

				_wq.push({
					id: blockId,
					onReady: function(video) {
						self.holder.trigger('loaded.video');

						self.player = {
							play: function() {
								video.play();
							},
							pause: function() {
								video.pause();
							},
							mute: function() {
								video.mute();
								video.volume(0);
							},
							unmute: function() {
								video.unmute();
								video.volume(100);
							}
						};

						video.bind('play', function() {
							self.holder.trigger('playing.video');
						}).bind('pause', function() {
							self.holder.trigger('paused.video');
						}).bind('end', function() {
							self.holder.trigger('ended.video');

							if (opt.loop) {
								video.play();
							}
						});
					}
				});
			};

			if (typeof Wistia === 'undefined') {
				$.getScript(this.options.wistiaAPI, loadPlayer);
			} else {
				loadPlayer();
			}
		},
		initHTML5: function() {
			const self = this;

			const opt = {
				controls: this.autoplay ? '' : 'controls',
				autoplay: this.isPopup ? 'autoplay playsinline' : this.autoplay ? 'autoplay playsinline muted' : '',
				loop: this.loop ? 'loop' : ''
			};

			this.videoContainer = $(`<video ${opt.controls} ${opt.autoplay} ${opt.loop} />`).addClass(this.options.containerClass).appendTo(this.holder);

			this.videoContainer[0].addEventListener('loadeddata', function() {
				self.holder.trigger('loaded.video');
			}, false);

			this.videoContainer[0].addEventListener('progress', function() {
				self.holder.trigger('loaded.video');
			}, false);

			this.videoContainer.prop('src', this.videoData.video);

			this.videoContainer.on('play', function() {
				self.holder.trigger('playing.video');
			}).on('pause', function() {
				self.holder.trigger('paused.video');
			}).on('ended', function() {
				self.holder.trigger('ended.video');
			});

			self.player = {
				play: function() {
					self.videoContainer[0].play();
				},
				pause: function() {
					self.videoContainer[0].pause();
				}
			};
		},
		pauseAllVideos: function() {
			if (this.autoplay && this.videoData.type === 'html5') {
				return;
			}

			$('[data-video].' + this.options.playingClass).not(this.holder).not('.' + this.options.autoplayVideoClass).each(function() {
				const holder = $(this);

				holder.data('BgVideo').player.pause();
			});
		},
		getDimensions: function(data) {
			const ratio = data.videoRatio || (data.videoWidth / data.videoHeight);
			let slideWidth = data.maskWidth;
			let slideHeight = slideWidth / ratio;

			if (slideHeight < data.maskHeight) {
				slideHeight = data.maskHeight;
				slideWidth = slideHeight * ratio;
			}

			return {
				width: slideWidth,
				height: slideHeight,
				top: (data.maskHeight - slideHeight) / 2,
				left: (data.maskWidth - slideWidth) / 2
			};
		},
		getRatio: function() {
			if (this.videoContainer.attr('width') && this.videoContainer.attr('height')) {
				return this.videoContainer.attr('width') / this.videoContainer.attr('height');
			} else {
				return 16 / 9;
			}
		},
		resizeVideo: function() {
			const styles = this.getDimensions({
				videoRatio: this.getRatio(this.videoContainer),
				maskWidth: this.holder.width(),
				maskHeight: this.holder.height()
			});

			this.videoContainer.css({
				width: styles.width,
				height: styles.height,
				marginTop: styles.top,
				marginLeft: styles.left
			});
		},
		playVideo: function() {
			if (!this.holder.hasClass(this.options.loadedClass)) return;

			if (!this.holder.hasClass(this.options.playingClass) || this.holder.hasClass(this.options.pausedClass)) {
				this.player.play();
				this.holder.blur();
			} else {
				if (!this.btnPause.length) {
					this.player.pause();
				}
			}
		},
		pauseVideo: function() {
			this.player.pause();
		},
		showPopup: function() {
			const self = this;

			if (this.holder.hasClass(this.options.loadedClass)) {
				setTimeout(function() {
					self.player.play();
				}, 500);
			} else {
				this.initPlayer();
			}

			this.page.addClass(this.options.activePageClass);
			this.popup.addClass(this.options.activePopupClass);
		},
		hidePopup: function() {
			if (this.player) {
				this.player.pause();
			}

			this.page.removeClass(this.options.activePageClass);
			this.popup.removeClass(this.options.activePopupClass);
		},
		getRandomId: function() {
			const s4 = function() {
				return (((1 + Math.random()) * 0x10000) | 0).toString(16).substring(1);
			};

			return (s4() + s4() + '-' + s4() + '-' + s4() + '-' + s4() + '-' + s4() + s4() + s4());
		},
		makeCallback: function(name) {
			if (typeof this.options[name] === 'function') {
				const args = Array.prototype.slice.call(arguments);

				args.shift();
				this.options[name].apply(this, args);
			}
		},
		destroy: function() {
			this.videoContainer.remove();
			this.btnPlay.off('click', this.playClickHandler);
			this.btnPause.off('click', this.pauseClickHandler);
			this.win.off('load resize orientationchange', this.resizeHandler);

			if (this.isPopup) {
				this.popup.remove();
				this.btnOpen.off('click', this.openClickHandler);
				this.btnClose.off('click', this.closeClickHandler);
			}

			this.holder.removeClass(this.options.loadedClass + ' ' + this.options.playingClass).off('.video').removeData('BgVideo');
		}
	};

	$.fn.bgVideo = function(opt) {
		return this.each(function() {
			$(this).data('BgVideo', new BgVideo($.extend(opt, {
				holder: this
			})));
		});
	};
})(jQuery);

// Remove Empty Items
function initRemoveEmptyItems() {
	jQuery('.js-is-empty-parent').each(function() {
		const holder = jQuery(this);
		const wrapper = holder.closest('.showWidget');
		const items = holder.find('ul li');
		const opener = wrapper.find('span.showWidget');

		items.each(function() {
			const item = jQuery(this);
			const textElem = item.find('.js-is-empty');

			if (textElem.text().trim() === '') {
				item.remove();
			}
		});

		if (!holder.find('ul li').length && opener.length) {
			opener.remove();
		}
	});
}

// Tax Modal
function initTaxModal() {
	jQuery('#popUpDetails').on('show.bs.modal', function(event) {
		const button = jQuery(event.relatedTarget);
		const content = button.data('content');
		const modal = jQuery(this);
		const textElement = modal.find('#popUpDetailsText');

		textElement.text(content);
	});
}

// Autocomplete init
function initAutocomplete() {
	const filteringSection = jQuery('.filter-section');
	const filteringModal = jQuery('#filterSchedule');
	const filteringAPI = filteringSection.data('FilteringProducts');

	if (!filteringAPI) return;

	jQuery('.search-panel .search-row').each(function() {
		const holder = jQuery(this);
		const searchLinkNew = holder.find('.search-link-new');
		const searchLinkUsed = holder.find('.search-link-used');
		const newItemURL = searchLinkNew.attr('href');
		const usedItemURL = searchLinkUsed.attr('href');
		const searchText = holder.find('.search-text');

		holder.autocomplete({
			inputField: '[type="search"]',
			dataAttr: 'search',
			dataType: 'json',
			highlightMatches: true,
			showOnFocus: true,
			filterResults: false,
			alwaysRefresh: true,
			noResultClass: 'no-results',
			listItems: 'li',
			onKeyup: function(value) {
				/**
				 * Updates search URL for new and used cars into autocomplete box
				 * @param {jQuery} searchText - element that contains text to be updated
				 * @param {jQuery} searchLinkNew - element that contains link to new cars page
				 * @param {jQuery} searchLinkUsed - element that contains link to used cars page
				 * @param {string} newItemURL - URL of new cars page
				 * @param {string} usedItemURL - URL of used cars page
				 * @param {string} value - search query
				 */
				updateSearchURL(searchText, searchLinkNew, searchLinkUsed, newItemURL, usedItemURL, value);
			},
			onLoadData: function(data) {
				const { isNewResults, isUsedResults } = data;

				if (!isNewResults) {
					searchLinkNew.hide();
				} else {
					searchLinkNew.show();
				}

				if (!isUsedResults) {
					searchLinkUsed.hide();
				} else {
					searchLinkUsed.show();
				}
			},
			parseData: function(data) {
				return createAutocompleteItems(this, data);
			},
			onSelectedItem: function(item) {
				const searchFilters = item.data('search');

				if (!searchFilters) return;

				// Reset the form
				filteringAPI.resetForm();

				// Update the search filters
				Object.entries(searchFilters).forEach(([filterName, filterValue]) => {
					const name = filterName.toLowerCase();

					// If filterName is 'vin' or 'stock' then we need to trigger search
					if (name === 'vin' || name === 'stock') {
						this.input.val(filterValue);

						setTimeout(() => {
							// Trigger search event
							this.input.trigger('onSearch');
							// Update the history
							filteringAPI.updateHistory(this.input);
							// Clear the input after search
							this.input.val('');
						}, 100);
					} else {
						// Select the needed checkboxes
						const checkboxes = filteringModal.find(`input[type="checkbox"][name="${filterName}"]`);

						checkboxes.each((i, item) => {
							const checkbox = jQuery(item);

							if (checkbox.val().toLowerCase() === filterValue.toLowerCase()) {
								checkbox.prop('checked', true);

								filteringAPI.updateFilterStates(checkbox);
							}
						});
					}
				});

				// Filter the products
				filteringAPI.filter();
			}
		});
	});
}

/**
 * Updates search URL for new and used cars into autocomplete box
 * @param {jQuery} searchText - element that contains text to be updated
 * @param {jQuery} searchLinkNew - element that contains link to new cars page
 * @param {jQuery} searchLinkUsed - element that contains link to used cars page
 * @param {string} newItemURL - URL of new cars page
 * @param {string} usedItemURL - URL of used cars page
 * @param {string} value - search query
 */
function updateSearchURL(searchText, searchLinkNew, searchLinkUsed, newItemURL, usedItemURL, value) {
	if (searchText.length) {
		searchText.text(value);
	}

	if (value !== '') {
		if (searchLinkNew.length) {
			searchLinkNew.attr('href', newItemURL + ((newItemURL.indexOf('?') !== -1 ? '&' : '?') + 'search=' + value));
		}

		if (searchLinkUsed.length) {
			searchLinkUsed.attr('href', usedItemURL + ((newItemURL.indexOf('?') !== -1 ? '&' : '?') + 'search=' + value));
		}
	} else {
		if (searchLinkNew.length) {
			searchLinkNew.attr('href', newItemURL);
		}

		if (searchLinkUsed.length) {
			searchLinkUsed.attr('href', usedItemURL);
		}
	}
}

/**
 * Creates a list of autocomplete suggestions based on the response from the server
 * @param {Object} self - the autocomplete instance
 * @param {Object} data - the response from the server
 * @return {String} - the HTML of the autocomplete list
 */
function createAutocompleteItems(self, data) {
	const {suggestions} = data;
	const searchData = jQuery('<div></div>');
	const value = self.input.val();

	// If no suggestions are found, display an error message
	if (!suggestions.length && value !== '') {
		self.form.addClass(self.options.noResultClass);

		return `<span class="error-message">Sorry, we couldn't find results matching "${value}".</span>`;
	} else {
		self.form.removeClass(self.options.noResultClass);
	}

	const uniqueSuggestions = Array.from(
		new Map(suggestions.map(item => [JSON.stringify(item), item])).values()
	);

	// Create a list of suggestions
	for (const key in uniqueSuggestions) {
		const searchText = uniqueSuggestions[key].suggestion;
		const search = JSON.stringify(uniqueSuggestions[key].facetInfo);
		const url = uniqueSuggestions[key].link;

		if (searchText !== '') {
			jQuery(`<li data-search='${search}' data-url='${url}'><a href="#">${searchText}</a></li>`).appendTo(searchData);
		}
	}

	return searchData.html();
}

/*
 * jQuery Autocomplete
 */
(function($) {
	function Autocomplete(options) {
		this.options = $.extend({
			startCount: 1,
			maxHeight: false,
			scrollClass: 'scroll-active',
			dataAttr: 'search',
			dataType: 'text',
			listItems: 'li',
			listItemsFillsInput: true,
			searchingClass: 'searching',
			alwaysRefresh: false,
			filterResults: true,
			highlightMatches: false,
			showOnFocus: true,
			preventEnterClick: false,
			queryStr: '',
			selectedClass: 'selected-line',
			disabledClass: 'disabled-line',
			noResultClass: 'no-results',
			resultsBox: '.ajax-drop',
			resultsHolder: '.autocomplete-results',
			inputField: 'input.text-input',
			hideDelay: 200,
			urlAttr: 'data-url',
			noResultBox: '.no-results',
			parseData: null // need function for dataType:'json'
		}, options);

		this.init();
	}

	Autocomplete.prototype = {
		init: function() {
			if (this.options.holder) {
				this.findElements();
				this.attachEvents();
				this.makeCallback('onInit');
			}
		},
		findElements: function() {
			this.form = $(this.options.holder);
			this.target = this.form.attr(this.options.urlAttr);
			this.input = this.form.find(this.options.inputField).attr('autocomplete', 'off');
			this.resultsBox = this.form.find(this.options.resultsBox).hide();
			this.resultsHolder = this.form.find(this.options.resultsHolder).show();
			this.noResultBox = this.form.find(this.options.noResultBox);
			this.currentIndex = 0;
			this.typedTimer = null;

			if (this.options.filterResults) this.options.alwaysRefresh = false;
		},
		attachEvents: function() {
			this.input.on('keyup', (e) => {
				if (e.keyCode === 27 || e.keyCode === 38 || e.keyCode === 40) return;

				if (this.options.preventEnterClick && e.key === 'Enter') return;

				if (e.key === 'Enter' || this.input.val().length < this.options.startCount) {
					this.hideDrop();
					clearTimeout(this.typedTimer);

					this.typedTimer = null;

					this.form.removeClass(this.options.searchingClass);
				} else {
					if (this.options.alwaysRefresh) {
						clearTimeout(this.typedTimer);

						this.form.addClass(this.options.searchingClass);

						this.typedTimer = setTimeout(() => {
							this.loadData();
							this.resultsHolder.empty();
						}, 1000);
					} else {
						if (!this.listItems) {
							this.loadData();
						}

						this.filterData();
					}
				}
			}).on('keydown', (e) => {
				if (this.listItems) {
					this.visibleItems = this.listItems.filter(':visible').not('.' + this.options.disabledClass);
					this.visibleCount = this.visibleItems.length;

					switch (e.keyCode) {
						case 27:
							this.hideDrop();

							break;
						case 38:
							if (this.currentIndex >= 0) this.currentIndex--;

							break;
						case 40:
							if (this.currentIndex < this.visibleCount - 1) this.currentIndex++;

							break;
					}

					this.listItems.removeClass(this.options.selectedClass);

					if (this.currentIndex !== -1) {
						this.visibleItems.eq(this.currentIndex).addClass(this.options.selectedClass);
					}
				}
			}).on('focus', () => {
				clearTimeout(this.focusTimer);

				this.inFocus = true;

				if (this.input.val().trim() !== '' && this.form.hasClass(this.options.noResultClass)) {
					this.resultsHolder.empty();
					this.form.removeClass(this.options.noResultClass);
				}

				if (this.options.showOnFocus) {
					this[this.itemsLoaded ? 'showDrop' : 'loadData']();
				}
			}).on('blur', () => {
				this.inFocus = false;

				if (!this.options.showOnFocus) {
					this.focusTimer = setTimeout(() => {
						this.hideDrop();
					}, this.options.hideDelay);
				}
			}).on('input', () => {
				this.makeCallback('onKeyup', this.input.val());
			});

			if (this.options.showOnFocus) {
				$(document).on('click', (e) => {
					const clickTarget = $(e.target);

					if (!clickTarget.is(this.form) && clickTarget.closest(this.form).length === 0 && !this.inFocus) {
						this.hideDrop();
					}
				});
			}
		},
		loadData: function(callback) {
			if (this.acXHR && typeof this.acXHR.abort === 'function') this.acXHR.abort();

			const self = this;

			this.acXHR = $.ajax({
				url: this.target,
				dataType: this.options.dataType,
				data: self.options.dataAttr + '=' + self.input.val() + this.options.queryStr,
				success: function(msg) {
					if (!self.typedTimer) return;

					self.updateDrop(msg);
					self.filterData();
					self.showDrop();
					self.itemsLoaded = true;

					self.makeCallback('onLoadData', msg);
					self.form.removeClass(self.options.searchingClass);

					if (typeof callback === 'function') callback();
				},
				error: function() {
					if (typeof self.options.onerror === 'function') {
						self.options.onerror.apply(this, arguments);
					}
				}
			});
		},
		filterData: function() {
			const self = this;

			if (self.listItems) {
				self.showDrop();

				if (self.options.filterResults) {
					self.hideNoResult();

					if (self.options.maxHeight) {
						var summ = 0;
					}

					self.listItems.show().each(function() {
						const item = $(this);

						item.html(item.data('orightml'));

						if (item.text().toLowerCase().indexOf(self.input.val().toLowerCase()) != -1) {
							item.show();
							if (self.options.maxHeight) summ += item.outerHeight(true);
						} else {
							item.hide();
						}
					});

					if (self.options.maxHeight + 0) {
						if (summ > self.options.maxHeight) {
							self.resultsBox.addClass(self.options.scrollClass);

							self.resultsHolder.css({
								height: self.options.maxHeight
							});
						} else {
							self.resultsBox.removeClass(self.options.scrollClass);

							self.resultsHolder.css({
								height: ''
							});
						}
					}

					if (!self.listItems.filter(':visible').length) {
						self.showNoResult();
					}
				}

				if (self.options.highlightMatches) {
					self.listItems.children().each(function(i, obj) {
						if (self.input.val().length >= self.options.startCount) {
							$(obj).html(highlightWords($(obj).text(), self.input.val()));
						}
					});
				}
			}
		},
		updateDrop: function(text) {
			if (this.lastData !== text) {
				let data = null;

				this.lastData = text;
				this.currentIndex = -1;

				if (this.options.dataType.toLowerCase() === 'json' && typeof this.options.parseData === 'function') {
					data = this.options.parseData.call(this, text) || text;
				} else {
					data = text;
				}

				this.resultsHolder.html(data);

				this.listItems = this.resultsHolder.find(this.options.listItems);

				this.listItems.each((i, item) => {
					const curItem = $(item);

					curItem.data('orightml', curItem.html());

					curItem.on('click', (e) => {
						e.preventDefault();

						this.selectItem(curItem);
					});

					curItem.on('hover', () => {
						this.listItems.removeClass(this.options.selectedClass);
						curItem.addClass(this.options.selectedClass);
						this.currentIndex = this.listItems.filter(':visible').index(curItem);
					});
				});
			}
		},
		showNoResult: function() {
			this.resultsHolder.hide();
			this.noResultBox.show();
			this.form.addClass(this.options.noResultClass);
		},
		hideNoResult: function() {
			this.resultsHolder.show();
			this.noResultBox.hide();
			this.form.removeClass(this.options.noResultClass);
		},
		showDrop: function() {
			if (this.input.val().length >= this.options.startCount) {
				this.resultsBox.show();
			} else {
				this.resultsBox.hide();
			}

			if (this.options.showOnFocus) {
				this.resultsBox.show();
			}
		},
		hideDrop: function() {
			this.resultsBox.hide();
		},
		selectItem: function(item) {
			this.hideDrop();
			this.makeCallback('onSelectedItem', item);
		},
		makeCallback(callbackName, ...args) {
			if (typeof this.options[callbackName] === 'function') {
				this.options[callbackName].apply(this, args);
			}
		}
	};

	function escapeRegExp(str) {
		return str.replace(new RegExp('[.*+?|()\\[\\]{}\\\\]', 'g'), '\\$&');
	}

	function highlightWords(str, word) {
		const regex = new RegExp('(' + escapeRegExp(word) + ')', 'gi');

		return str.replace(regex, '<strong>$1</strong>');
	}

	$.fn.autocomplete = function(options) {
		return this.each(function() {
			$(this).data('AutoComplete', new Autocomplete($.extend(options, {
				holder: this
			})));
		});
	};
}(jQuery));

// Add user journey to the input from the local storage
function updateJourneyField() {
	const userJourney = localStorage.getItem('UserJourney');
	const userJourneyField = document.querySelectorAll('.userjourney input');

	if (userJourney && userJourneyField.length) {
		userJourneyField.forEach((elem) => {
			elem.value = userJourney;
		});
	}
}

/*
 * priority-nav - v1.0.13 | (c) 2018 @gijsroge | MIT license
 * Repository: https://github.com/gijsroge/priority-navigation.git
 * Description: Priority+ pattern navigation that hides menu items if they don't fit on screen.
 * Demo: http://gijsroge.github.io/priority-nav.js/
 */
(function(root, factory) {
	if (typeof define === "function" && define.amd) {
		define("priorityNav", factory(root));
	} else if (typeof exports === "object") {
		module.exports = factory(root);
	} else {
		root.priorityNav = factory(root);
	}
})(window || this, function(root) {

	"use strict";

	/**
	 * Variables
	 */
	var priorityNav = {}; // Object for public APIs
	var breaks = []; // Object to store instances with breakpoints where the instances menu item"s didin"t fit.
	var supports = !!document.querySelector && !!root.addEventListener; // Feature test
	var settings = {};
	var instance = 0;
	var count = 0;
	var mainNavWrapper, totalWidth, restWidth, mainNav, navDropdown, navDropdownToggle, dropDownWidth, toggleWrapper;
	var viewportWidth = 0;

	/**
	 * Default settings
	 * @type {{initClass: string, navDropdown: string, navDropdownToggle: string, mainNavWrapper: string, moved: Function, movedBack: Function}}
	 */
	var defaults = {
		initClass: "js-priorityNav", // Class that will be printed on html element to allow conditional css styling.
		mainNavWrapper: "nav", // mainnav wrapper selector (must be direct parent from mainNav)
		mainNav: "ul", // mainnav selector. (must be inline-block)
		navDropdownClassName: "nav__dropdown", // class used for the dropdown.
		navDropdownToggleClassName: "nav__dropdown-toggle", // class used for the dropdown toggle.
		navDropdownLabel: "more", // Text that is used for the dropdown toggle.
		navDropdownBreakpointLabel: "menu", //button label for navDropdownToggle when the breakPoint is reached.
		breakPoint: 500, //amount of pixels when all menu items should be moved to dropdown to simulate a mobile menu
		throttleDelay: 50, // this will throttle the calculating logic on resize because i'm a responsible dev.
		offsetPixels: 0, // increase to decrease the time it takes to move an item.
		count: true, // prints the amount of items are moved to the attribute data-count to style with css counter.

		//Callbacks
		moved: function() {
		},
		movedBack: function() {
		}
	};

	/**
	 * A simple forEach() implementation for Arrays, Objects and NodeLists
	 * @private
	 * @param {Array|Object|NodeList} collection Collection of items to iterate
	 * @param {Function} callback Callback function for each iteration
	 * @param {Array|Object|NodeList} scope Object/NodeList/Array that forEach is iterating over (aka `this`)
	 */
	var forEach = function(collection, callback, scope) {
		if (Object.prototype.toString.call(collection) === "[object Object]") {
			for (var prop in collection) {
				if (Object.prototype.hasOwnProperty.call(collection, prop)) {
					callback.call(scope, collection[prop], prop, collection);
				}
			}
		} else {
			for (var i = 0, len = collection.length; i < len; i++) {
				callback.call(scope, collection[i], i, collection);
			}
		}
	};

	/**
	 * Get the closest matching element up the DOM tree
	 * @param {Element} elem Starting element
	 * @param {String} selector Selector to match against (class, ID, or data attribute)
	 * @return {Boolean|Element} Returns false if not match found
	 */
	var getClosest = function(elem, selector) {
		var firstChar = selector.charAt(0);
		for (; elem && elem !== document; elem = elem.parentNode) {
			if (firstChar === ".") {
				if (elem.classList.contains(selector.substr(1))) {
					return elem;
				}
			} else if (firstChar === "#") {
				if (elem.id === selector.substr(1)) {
					return elem;
				}
			} else if (firstChar === "[") {
				if (elem.hasAttribute(selector.substr(1, selector.length - 2))) {
					return elem;
				}
			}
		}
		return false;
	};

	/**
	 * Merge defaults with user options
	 * @private
	 * @param {Object} defaults Default settings
	 * @param {Object} options User options
	 * @returns {Object} Merged values of defaults and options
	 */
	var extend = function(defaults, options) {
		var extended = {};
		forEach(defaults, function(value, prop) {
			extended[prop] = defaults[prop];
		});
		forEach(options, function(value, prop) {
			extended[prop] = options[prop];
		});
		return extended;
	};

	/**
	 * Debounced resize to throttle execution
	 * @param func
	 * @param wait
	 * @param immediate
	 * @returns {Function}
	 */
	function debounce(func, wait, immediate) {
		var timeout;
		return function() {
			var context = this, args = arguments;
			var later = function() {
				timeout = null;
				if (!immediate) func.apply(context, args);
			};
			var callNow = immediate && !timeout;
			clearTimeout(timeout);
			timeout = setTimeout(later, wait);
			if (callNow) func.apply(context, args);
		};
	}

	/**
	 * Toggle class on element
	 * @param el
	 * @param className
	 */
	var toggleClass = function(el, className) {
		if (el.classList) {
			el.classList.toggle(className);
		} else {
			var classes = el.className.split(" ");
			var existingIndex = classes.indexOf(className);

			if (existingIndex >= 0)
				classes.splice(existingIndex, 1); else
				classes.push(className);

			el.className = classes.join(" ");
		}
	};

	/**
	 * Check if dropdown menu is already on page before creating it
	 * @param mainNavWrapper
	 */
	var prepareHtml = function(_this, settings) {

		/**
		 * Create dropdow menu
		 * @type {HTMLElement}
		 */
		toggleWrapper = document.createElement("span");
		navDropdown = document.createElement("ul");
		navDropdownToggle = document.createElement("button");

		/**
		 * Set label for dropdown toggle
		 * @type {string}
		 */
		navDropdownToggle.innerHTML = settings.navDropdownLabel;

		/**
		 * Set aria attributes for accessibility
		 */
		navDropdownToggle.setAttribute("aria-controls", "menu");
		navDropdownToggle.setAttribute("aria-label", "Show more menu items");
		navDropdownToggle.setAttribute("type", "button");
		navDropdown.setAttribute("aria-hidden", "true");


		/**
		 * Move elements to the right spot
		 */
		if (_this.querySelector(mainNav).parentNode !== _this) {
			console.warn("mainNav is not a direct child of mainNavWrapper, double check please");
			return;
		}

		_this.insertAfter(toggleWrapper, _this.querySelector(mainNav));

		toggleWrapper.appendChild(navDropdownToggle);
		toggleWrapper.appendChild(navDropdown);

		/**
		 * Add classes so we can target elements
		 */
		navDropdown.classList.add(settings.navDropdownClassName);
		navDropdown.classList.add("priority-nav__dropdown");

		navDropdownToggle.classList.add(settings.navDropdownToggleClassName);
		navDropdownToggle.classList.add("priority-nav__dropdown-toggle");

		//fix so button is type="button" and do not submit forms
		navDropdownToggle.setAttribute("type", "button");

		toggleWrapper.classList.add(settings.navDropdownClassName + "-wrapper");
		toggleWrapper.classList.add("priority-nav__wrapper");

		_this.classList.add("priority-nav");
	};

	/**
	 * Get innerwidth without padding
	 * @param element
	 * @returns {number}
	 */
	var getElementContentWidth = function(element) {
		var styles = window.getComputedStyle(element);
		var padding = parseFloat(styles.paddingLeft) +
			parseFloat(styles.paddingRight);

		return element.clientWidth - padding;
	};

	/**
	 * Get viewport size
	 * @returns {{width: number, height: number}}
	 */
	var viewportSize = function() {
		var doc = document, w = window;
		var docEl = (doc.compatMode && doc.compatMode === "CSS1Compat") ?
			doc.documentElement : doc.body;

		var width = docEl.clientWidth;
		var height = docEl.clientHeight;

		// mobile zoomed in?
		if (w.innerWidth && width > w.innerWidth) {
			width = w.innerWidth;
			height = w.innerHeight;
		}

		return { width: width, height: height };
	};

	/**
	 * Get width
	 * @param elem
	 * @returns {number}
	 */
	var calculateWidths = function(_this) {
		totalWidth = getElementContentWidth(_this);
		//Check if parent is the navwrapper before calculating its width
		if (_this.querySelector(navDropdown).parentNode === _this) {
			dropDownWidth = _this.querySelector(navDropdown).offsetWidth;
		} else {
			dropDownWidth = 0;
		}
		restWidth = getChildrenWidth(_this) + settings.offsetPixels;
		viewportWidth = viewportSize().width;
	};

	/**
	 * Move item to array
	 * @param item
	 */
	priorityNav.doesItFit = function(_this) {

		/**
		 * Check if it is the first run
		 */
		var delay = _this.getAttribute("instance") === 0 ? delay : settings.throttleDelay;

		/**
		 * Increase instance
		 */
		instance++;

		/**
		 * Debounced execution of the main logic
		 */
		(debounce(function() {

			/**
			 * Get the current element"s instance
			 * @type {string}
			 */
			var identifier = _this.getAttribute("instance");

			/**
			 * Update width
			 */
			calculateWidths(_this);

			/**
			 * Keep executing until all menu items that are overflowing are moved
			 */
			while (totalWidth <= restWidth && _this.querySelector(mainNav).children.length > 0 || viewportWidth < settings.breakPoint && _this.querySelector(mainNav).children.length > 0) {
				//move item to dropdown
				priorityNav.toDropdown(_this, identifier);
				//recalculate widths
				calculateWidths(_this, identifier);
				//update dropdownToggle label
				if (viewportWidth < settings.breakPoint) updateLabel(_this, identifier, settings.navDropdownBreakpointLabel);
			}

			/**
			 * Keep executing until all menu items that are able to move back are moved
			 */
			while (totalWidth >= breaks[identifier][breaks[identifier].length - 1] && viewportWidth > settings.breakPoint) {
				//move item to menu
				priorityNav.toMenu(_this, identifier);
				//update dropdownToggle label
				if (viewportWidth > settings.breakPoint) updateLabel(_this, identifier, settings.navDropdownLabel);
			}

			/**
			 * If there are no items in dropdown hide dropdown
			 */
			if (breaks[identifier].length < 1) {
				_this.querySelector(navDropdown).classList.remove("show");
				//show navDropdownLabel
				updateLabel(_this, identifier, settings.navDropdownLabel);
			}

			/**
			 * If there are no items in menu
			 */
			if (_this.querySelector(mainNav).children.length < 1) {
				//show navDropdownBreakpointLabel
				_this.classList.add("is-empty");
				updateLabel(_this, identifier, settings.navDropdownBreakpointLabel);
			} else {
				_this.classList.remove("is-empty");
			}

			/**
			 * Check if we need to show toggle menu button
			 */
			showToggle(_this, identifier);

		}, delay))();
	};

	/**
	 * Show/hide toggle button
	 */
	var showToggle = function(_this, identifier) {
		if (breaks[identifier].length < 1) {
			_this.querySelector(navDropdownToggle).classList.add("priority-nav-is-hidden");
			_this.querySelector(navDropdownToggle).classList.remove("priority-nav-is-visible");
			_this.classList.remove("priority-nav-has-dropdown");

			/**
			 * Set aria attributes for accessibility
			 */
			_this.querySelector(".priority-nav__wrapper").setAttribute("aria-haspopup", "false");

		} else {
			_this.querySelector(navDropdownToggle).classList.add("priority-nav-is-visible");
			_this.querySelector(navDropdownToggle).classList.remove("priority-nav-is-hidden");
			_this.classList.add("priority-nav-has-dropdown");

			/**
			 * Set aria attributes for accessibility
			 */
			_this.querySelector(".priority-nav__wrapper").setAttribute("aria-haspopup", "true");
		}
	};

	/**
	 * Update count on dropdown toggle button
	 */
	var updateCount = function(_this, identifier) {
		_this.querySelector(navDropdownToggle).setAttribute("priorityNav-count", breaks[identifier].length);
	};

	var updateLabel = function(_this, identifier, label) {
		_this.querySelector(navDropdownToggle).innerHTML = label;
	};

	/**
	 * Move item to dropdown
	 */
	priorityNav.toDropdown = function(_this, identifier) {

		/**
		 * move last child of navigation menu to dropdown
		 */
		if (_this.querySelector(navDropdown).firstChild && _this.querySelector(mainNav).children.length > 0) {
			_this.querySelector(navDropdown).insertBefore(_this.querySelector(mainNav).lastElementChild, _this.querySelector(navDropdown).firstChild);
		} else if (_this.querySelector(mainNav).children.length > 0) {
			_this.querySelector(navDropdown).appendChild(_this.querySelector(mainNav).lastElementChild);
		}

		/**
		 * store breakpoints
		 */
		breaks[identifier].push(restWidth);

		/**
		 * check if we need to show toggle menu button
		 */
		showToggle(_this, identifier);

		/**
		 * update count on dropdown toggle button
		 */
		if (_this.querySelector(mainNav).children.length > 0 && settings.count) {
			updateCount(_this, identifier);
		}

		/**
		 * If item has been moved to dropdown trigger the callback
		 */
		settings.moved();
	};

	/**
	 * Move item to menu
	 */
	priorityNav.toMenu = function(_this, identifier) {

		/**
		 * move last child of navigation menu to dropdown
		 */
		if (_this.querySelector(navDropdown).children.length > 0) _this.querySelector(mainNav).appendChild(_this.querySelector(navDropdown).firstElementChild);

		/**
		 * remove last breakpoint
		 */
		breaks[identifier].pop();

		/**
		 * Check if we need to show toggle menu button
		 */
		showToggle(_this, identifier);

		/**
		 * update count on dropdown toggle button
		 */
		if (_this.querySelector(mainNav).children.length > 0 && settings.count) {
			updateCount(_this, identifier);
		}

		/**
		 * If item has been moved back to the main menu trigger the callback
		 */
		settings.movedBack();
	};

	/**
	 * Count width of children and return the value
	 * @param e
	 */
	var getChildrenWidth = function(e) {
		var children = e.childNodes;
		var sum = 0;
		for (var i = 0; i < children.length; i++) {
			if (children[i].nodeType !== 3) {
				if (!isNaN(children[i].offsetWidth)) {
					sum += children[i].offsetWidth;
				}

			}
		}
		return sum;
	};

	/**
	 * Bind eventlisteners
	 */
	var listeners = function(_this, settings) {

		// Check if an item needs to move
		if (window.attachEvent) {
			window.attachEvent("onresize", function() {
				if (priorityNav.doesItFit) priorityNav.doesItFit(_this);
			});
		}
		else if (window.addEventListener) {
			window.addEventListener("resize", function() {
				if (priorityNav.doesItFit) priorityNav.doesItFit(_this);
			}, true);
		}

		// Toggle dropdown
		_this.querySelector(navDropdownToggle).addEventListener("click", function() {
			toggleClass(_this.querySelector(navDropdown), "show");
			toggleClass(this, "is-open");
			toggleClass(_this, "is-open");

			/**
			 * Toggle aria hidden for accessibility
			 */
			if (-1 !== _this.className.indexOf("is-open")) {
				_this.querySelector(navDropdown).setAttribute("aria-hidden", "false");
			} else {
				_this.querySelector(navDropdown).setAttribute("aria-hidden", "true");
				_this.querySelector(navDropdown).blur();
			}
		});

		/*
		 * Remove when clicked outside dropdown
		 */
		document.addEventListener("click", function(event) {
			if (!getClosest(event.target, "." + settings.navDropdownClassName) && event.target !== _this.querySelector(navDropdownToggle)) {
				_this.querySelector(navDropdown).classList.remove("show");
				_this.querySelector(navDropdownToggle).classList.remove("is-open");
				_this.classList.remove("is-open");
			}
		});

		/**
		 * Remove when escape key is pressed
		 */
		document.onkeydown = function(evt) {
			evt = evt || window.event;
			if (evt.keyCode === 27) {
				document.querySelector(navDropdown).classList.remove("show");
				document.querySelector(navDropdownToggle).classList.remove("is-open");
				mainNavWrapper.classList.remove("is-open");
			}
		};
	};

	/**
	 * Remove function
	 */
	Element.prototype.remove = function() {
		// this.parentElement.removeChild(this);
	};

	/*global HTMLCollection */
	NodeList.prototype.remove = HTMLCollection.prototype.remove = function() {
		for (var i = 0, len = this.length; i < len; i++) {
			if (this[i] && this[i].parentElement) {
				this[i].parentElement.removeChild(this[i]);
			}
		}
	};

	/**
	 * Destroy the current initialization.
	 * @public
	 */
	priorityNav.destroy = function() {
		// If plugin isn"t already initialized, stop
		if (!settings) return;
		// Remove feedback class
		document.documentElement.classList.remove(settings.initClass);
		// Remove toggle
		toggleWrapper.remove();
		// Remove settings
		settings = null;
		delete priorityNav.init;
		delete priorityNav.doesItFit;
	};

	/**
	 * insertAfter function
	 * @param n
	 * @param r
	 */
	if (supports && typeof Node !== "undefined") {
		Node.prototype.insertAfter = function(n, r) { this.insertBefore(n, r.nextSibling); };
	}

	var checkForSymbols = function(string) {
		var firstChar = string.charAt(0);
		if (firstChar === "." || firstChar === "#") {
			return false;
		} else {
			return true;
		}
	};

	/**
	 * Initialize Plugin
	 * @public
	 * @param {Object} options User settings
	 */
	priorityNav.init = function(options) {

		/**
		 * Merge user options with defaults
		 * @type {Object}
		 */
		settings = extend(defaults, options || {});

		// Feature test.
		if (!supports && typeof Node === "undefined") {
			console.warn("This browser doesn't support priorityNav");
			return;
		}

		// Options check
		if (!checkForSymbols(settings.navDropdownClassName) || !checkForSymbols(settings.navDropdownToggleClassName)) {
			console.warn("No symbols allowed in navDropdownClassName & navDropdownToggleClassName. These are not selectors.");
			return;
		}

		/**
		 * Store nodes
		 * @type {NodeList}
		 */
		var elements = document.querySelectorAll(settings.mainNavWrapper);

		/**
		 * Loop over every instance and reference _this
		 */
		forEach(elements, function(_this) {

			/**
			 * Create breaks array
			 * @type {number}
			 */
			breaks[count] = [];

			/**
			 * Set the instance number as data attribute
			 */
			_this.setAttribute("instance", count++);

			/**
			 * Store the wrapper element
			 */
			mainNavWrapper = _this;
			if (!mainNavWrapper) {
				console.warn("couldn't find the specified mainNavWrapper element");
				return;
			}

			/**
			 * Store the menu elementStore the menu element
			 */
			mainNav = settings.mainNav;
			if (!_this.querySelector(mainNav)) {
				console.warn("couldn't find the specified mainNav element");
				return;
			}

			/**
			 * Check if we need to create the dropdown elements
			 */
			prepareHtml(_this, settings);

			/**
			 * Store the dropdown element
			 */
			navDropdown = "." + settings.navDropdownClassName;
			if (!_this.querySelector(navDropdown)) {
				console.warn("couldn't find the specified navDropdown element");
				return;
			}

			/**
			 * Store the dropdown toggle element
			 */
			navDropdownToggle = "." + settings.navDropdownToggleClassName;
			if (!_this.querySelector(navDropdownToggle)) {
				console.warn("couldn't find the specified navDropdownToggle element");
				return;
			}

			/**
			 * Event listeners
			 */
			listeners(_this, settings);

			/**
			 * Start first check
			 */
			priorityNav.doesItFit(_this);

		});

		/**
		 * Count amount of instances
		 */
		instance++;

		/**
		 * Add class to HTML element to activate conditional CSS
		 */
		document.documentElement.classList.add(settings.initClass);
	};

	/**
	 * Public APIs
	 */
	return priorityNav;
});

/* eslint-disable */

// jQuery Mask Plugin v1.14.16
// github.com/igorescobar/jQuery-Mask-Plugin
var $jscomp=$jscomp||{};$jscomp.scope={};$jscomp.findInternal=function(a,n,f){a instanceof String&&(a=String(a));for(var p=a.length,k=0;k<p;k++){var b=a[k];if(n.call(f,b,k,a))return{i:k,v:b}}return{i:-1,v:void 0}};$jscomp.ASSUME_ES5=!1;$jscomp.ASSUME_NO_NATIVE_MAP=!1;$jscomp.ASSUME_NO_NATIVE_SET=!1;$jscomp.SIMPLE_FROUND_POLYFILL=!1;
$jscomp.defineProperty=$jscomp.ASSUME_ES5||"function"==typeof Object.defineProperties?Object.defineProperty:function(a,n,f){a!=Array.prototype&&a!=Object.prototype&&(a[n]=f.value)};$jscomp.getGlobal=function(a){return"undefined"!=typeof window&&window===a?a:"undefined"!=typeof global&&null!=global?global:a};$jscomp.global=$jscomp.getGlobal(this);
$jscomp.polyfill=function(a,n,f,p){if(n){f=$jscomp.global;a=a.split(".");for(p=0;p<a.length-1;p++){var k=a[p];k in f||(f[k]={});f=f[k]}a=a[a.length-1];p=f[a];n=n(p);n!=p&&null!=n&&$jscomp.defineProperty(f,a,{configurable:!0,writable:!0,value:n})}};$jscomp.polyfill("Array.prototype.find",function(a){return a?a:function(a,f){return $jscomp.findInternal(this,a,f).v}},"es6","es3");
(function(a,n,f){"function"===typeof define&&define.amd?define(["jquery"],a):"object"===typeof exports&&"undefined"===typeof Meteor?module.exports=a(require("jquery")):a(n||f)})(function(a){var n=function(b,d,e){var c={invalid:[],getCaret:function(){try{var a=0,r=b.get(0),h=document.selection,d=r.selectionStart;if(h&&-1===navigator.appVersion.indexOf("MSIE 10")){var e=h.createRange();e.moveStart("character",-c.val().length);a=e.text.length}else if(d||"0"===d)a=d;return a}catch(C){}},setCaret:function(a){try{if(b.is(":focus")){var c=
b.get(0);if(c.setSelectionRange)c.setSelectionRange(a,a);else{var g=c.createTextRange();g.collapse(!0);g.moveEnd("character",a);g.moveStart("character",a);g.select()}}}catch(B){}},events:function(){b.on("keydown.mask",function(a){b.data("mask-keycode",a.keyCode||a.which);b.data("mask-previus-value",b.val());b.data("mask-previus-caret-pos",c.getCaret());c.maskDigitPosMapOld=c.maskDigitPosMap}).on(a.jMaskGlobals.useInput?"input.mask":"keyup.mask",c.behaviour).on("paste.mask drop.mask",function(){setTimeout(function(){b.keydown().keyup()},
100)}).on("change.mask",function(){b.data("changed",!0)}).on("blur.mask",function(){f===c.val()||b.data("changed")||b.trigger("change");b.data("changed",!1)}).on("blur.mask",function(){f=c.val()}).on("focus.mask",function(b){!0===e.selectOnFocus&&a(b.target).select()}).on("focusout.mask",function(){e.clearIfNotMatch&&!k.test(c.val())&&c.val("")})},getRegexMask:function(){for(var a=[],b,c,e,t,f=0;f<d.length;f++)(b=l.translation[d.charAt(f)])?(c=b.pattern.toString().replace(/.{1}$|^.{1}/g,""),e=b.optional,
(b=b.recursive)?(a.push(d.charAt(f)),t={digit:d.charAt(f),pattern:c}):a.push(e||b?c+"?":c)):a.push(d.charAt(f).replace(/[-\/\\^$*+?.()|[\]{}]/g,"\\$&"));a=a.join("");t&&(a=a.replace(new RegExp("("+t.digit+"(.*"+t.digit+")?)"),"($1)?").replace(new RegExp(t.digit,"g"),t.pattern));return new RegExp(a)},destroyEvents:function(){b.off("input keydown keyup paste drop blur focusout ".split(" ").join(".mask "))},val:function(a){var c=b.is("input")?"val":"text";if(0<arguments.length){if(b[c]()!==a)b[c](a);
c=b}else c=b[c]();return c},calculateCaretPosition:function(a){var d=c.getMasked(),h=c.getCaret();if(a!==d){var e=b.data("mask-previus-caret-pos")||0;d=d.length;var g=a.length,f=a=0,l=0,k=0,m;for(m=h;m<d&&c.maskDigitPosMap[m];m++)f++;for(m=h-1;0<=m&&c.maskDigitPosMap[m];m--)a++;for(m=h-1;0<=m;m--)c.maskDigitPosMap[m]&&l++;for(m=e-1;0<=m;m--)c.maskDigitPosMapOld[m]&&k++;h>g?h=10*d:e>=h&&e!==g?c.maskDigitPosMapOld[h]||(e=h,h=h-(k-l)-a,c.maskDigitPosMap[h]&&(h=e)):h>e&&(h=h+(l-k)+f)}return h},behaviour:function(d){d=
d||window.event;c.invalid=[];var e=b.data("mask-keycode");if(-1===a.inArray(e,l.byPassKeys)){e=c.getMasked();var h=c.getCaret(),g=b.data("mask-previus-value")||"";setTimeout(function(){c.setCaret(c.calculateCaretPosition(g))},a.jMaskGlobals.keyStrokeCompensation);c.val(e);c.setCaret(h);return c.callbacks(d)}},getMasked:function(a,b){var h=[],f=void 0===b?c.val():b+"",g=0,k=d.length,n=0,p=f.length,m=1,r="push",u=-1,w=0;b=[];if(e.reverse){r="unshift";m=-1;var x=0;g=k-1;n=p-1;var A=function(){return-1<
g&&-1<n}}else x=k-1,A=function(){return g<k&&n<p};for(var z;A();){var y=d.charAt(g),v=f.charAt(n),q=l.translation[y];if(q)v.match(q.pattern)?(h[r](v),q.recursive&&(-1===u?u=g:g===x&&g!==u&&(g=u-m),x===u&&(g-=m)),g+=m):v===z?(w--,z=void 0):q.optional?(g+=m,n-=m):q.fallback?(h[r](q.fallback),g+=m,n-=m):c.invalid.push({p:n,v:v,e:q.pattern}),n+=m;else{if(!a)h[r](y);v===y?(b.push(n),n+=m):(z=y,b.push(n+w),w++);g+=m}}a=d.charAt(x);k!==p+1||l.translation[a]||h.push(a);h=h.join("");c.mapMaskdigitPositions(h,
b,p);return h},mapMaskdigitPositions:function(a,b,d){a=e.reverse?a.length-d:0;c.maskDigitPosMap={};for(d=0;d<b.length;d++)c.maskDigitPosMap[b[d]+a]=1},callbacks:function(a){var g=c.val(),h=g!==f,k=[g,a,b,e],l=function(a,b,c){"function"===typeof e[a]&&b&&e[a].apply(this,c)};l("onChange",!0===h,k);l("onKeyPress",!0===h,k);l("onComplete",g.length===d.length,k);l("onInvalid",0<c.invalid.length,[g,a,b,c.invalid,e])}};b=a(b);var l=this,f=c.val(),k;d="function"===typeof d?d(c.val(),void 0,b,e):d;l.mask=
d;l.options=e;l.remove=function(){var a=c.getCaret();l.options.placeholder&&b.removeAttr("placeholder");b.data("mask-maxlength")&&b.removeAttr("maxlength");c.destroyEvents();c.val(l.getCleanVal());c.setCaret(a);return b};l.getCleanVal=function(){return c.getMasked(!0)};l.getMaskedVal=function(a){return c.getMasked(!1,a)};l.init=function(g){g=g||!1;e=e||{};l.clearIfNotMatch=a.jMaskGlobals.clearIfNotMatch;l.byPassKeys=a.jMaskGlobals.byPassKeys;l.translation=a.extend({},a.jMaskGlobals.translation,e.translation);
l=a.extend(!0,{},l,e);k=c.getRegexMask();if(g)c.events(),c.val(c.getMasked());else{e.placeholder&&b.attr("placeholder",e.placeholder);b.data("mask")&&b.attr("autocomplete","off");g=0;for(var f=!0;g<d.length;g++){var h=l.translation[d.charAt(g)];if(h&&h.recursive){f=!1;break}}f&&b.attr("maxlength",d.length).data("mask-maxlength",!0);c.destroyEvents();c.events();g=c.getCaret();c.val(c.getMasked());c.setCaret(g)}};l.init(!b.is("input"))};a.maskWatchers={};var f=function(){var b=a(this),d={},e=b.attr("data-mask");
b.attr("data-mask-reverse")&&(d.reverse=!0);b.attr("data-mask-clearifnotmatch")&&(d.clearIfNotMatch=!0);"true"===b.attr("data-mask-selectonfocus")&&(d.selectOnFocus=!0);if(p(b,e,d))return b.data("mask",new n(this,e,d))},p=function(b,d,e){e=e||{};var c=a(b).data("mask"),f=JSON.stringify;b=a(b).val()||a(b).text();try{return"function"===typeof d&&(d=d(b)),"object"!==typeof c||f(c.options)!==f(e)||c.mask!==d}catch(w){}},k=function(a){var b=document.createElement("div");a="on"+a;var e=a in b;e||(b.setAttribute(a,
"return;"),e="function"===typeof b[a]);return e};a.fn.mask=function(b,d){d=d||{};var e=this.selector,c=a.jMaskGlobals,f=c.watchInterval;c=d.watchInputs||c.watchInputs;var k=function(){if(p(this,b,d))return a(this).data("mask",new n(this,b,d))};a(this).each(k);e&&""!==e&&c&&(clearInterval(a.maskWatchers[e]),a.maskWatchers[e]=setInterval(function(){a(document).find(e).each(k)},f));return this};a.fn.masked=function(a){return this.data("mask").getMaskedVal(a)};a.fn.unmask=function(){clearInterval(a.maskWatchers[this.selector]);
delete a.maskWatchers[this.selector];return this.each(function(){var b=a(this).data("mask");b&&b.remove().removeData("mask")})};a.fn.cleanVal=function(){return this.data("mask").getCleanVal()};a.applyDataMask=function(b){b=b||a.jMaskGlobals.maskElements;(b instanceof a?b:a(b)).filter(a.jMaskGlobals.dataMaskAttr).each(f)};k={maskElements:"input,td,span,div",dataMaskAttr:"*[data-mask]",dataMask:!0,watchInterval:300,watchInputs:!0,keyStrokeCompensation:10,useInput:!/Chrome\/[2-4][0-9]|SamsungBrowser/.test(window.navigator.userAgent)&&
k("input"),watchDataMask:!1,byPassKeys:[9,16,17,18,36,37,38,39,40,91],translation:{0:{pattern:/\d/},9:{pattern:/\d/,optional:!0},"#":{pattern:/\d/,recursive:!0},A:{pattern:/[a-zA-Z0-9]/},S:{pattern:/[a-zA-Z]/}}};a.jMaskGlobals=a.jMaskGlobals||{};k=a.jMaskGlobals=a.extend(!0,{},k,a.jMaskGlobals);k.dataMask&&a.applyDataMask();setInterval(function(){a.jMaskGlobals.watchDataMask&&a.applyDataMask()},k.watchInterval)},window.jQuery,window.Zepto);

/*!
 * clipboard.js v2.0.11
 * https://clipboardjs.com/
 *
 * Licensed MIT © Zeno Rocha
 */
!function(t,e){"object"==typeof exports&&"object"==typeof module?module.exports=e():"function"==typeof define&&define.amd?define([],e):"object"==typeof exports?exports.ClipboardJS=e():t.ClipboardJS=e()}(this,function(){return n={686:function(t,e,n){"use strict";n.d(e,{default:function(){return b}});var e=n(279),i=n.n(e),e=n(370),u=n.n(e),e=n(817),r=n.n(e);function c(t){try{return document.execCommand(t)}catch(t){return}}var a=function(t){t=r()(t);return c("cut"),t};function o(t,e){var n,o,t=(n=t,o="rtl"===document.documentElement.getAttribute("dir"),(t=document.createElement("textarea")).style.fontSize="12pt",t.style.border="0",t.style.padding="0",t.style.margin="0",t.style.position="absolute",t.style[o?"right":"left"]="-9999px",o=window.pageYOffset||document.documentElement.scrollTop,t.style.top="".concat(o,"px"),t.setAttribute("readonly",""),t.value=n,t);return e.container.appendChild(t),e=r()(t),c("copy"),t.remove(),e}var f=function(t){var e=1<arguments.length&&void 0!==arguments[1]?arguments[1]:{container:document.body},n="";return"string"==typeof t?n=o(t,e):t instanceof HTMLInputElement&&!["text","search","url","tel","password"].includes(null==t?void 0:t.type)?n=o(t.value,e):(n=r()(t),c("copy")),n};function l(t){return(l="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t})(t)}var s=function(){var t=0<arguments.length&&void 0!==arguments[0]?arguments[0]:{},e=t.action,n=void 0===e?"copy":e,o=t.container,e=t.target,t=t.text;if("copy"!==n&&"cut"!==n)throw new Error('Invalid "action" value, use either "copy" or "cut"');if(void 0!==e){if(!e||"object"!==l(e)||1!==e.nodeType)throw new Error('Invalid "target" value, use a valid Element');if("copy"===n&&e.hasAttribute("disabled"))throw new Error('Invalid "target" attribute. Please use "readonly" instead of "disabled" attribute');if("cut"===n&&(e.hasAttribute("readonly")||e.hasAttribute("disabled")))throw new Error('Invalid "target" attribute. You can\'t cut text from elements with "readonly" or "disabled" attributes')}return t?f(t,{container:o}):e?"cut"===n?a(e):f(e,{container:o}):void 0};function p(t){return(p="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t})(t)}function d(t,e){for(var n=0;n<e.length;n++){var o=e[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(t,o.key,o)}}function y(t,e){return(y=Object.setPrototypeOf||function(t,e){return t.__proto__=e,t})(t,e)}function h(n){var o=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Date.prototype.toString.call(Reflect.construct(Date,[],function(){})),!0}catch(t){return!1}}();return function(){var t,e=v(n);return t=o?(t=v(this).constructor,Reflect.construct(e,arguments,t)):e.apply(this,arguments),e=this,!(t=t)||"object"!==p(t)&&"function"!=typeof t?function(t){if(void 0!==t)return t;throw new ReferenceError("this hasn't been initialised - super() hasn't been called")}(e):t}}function v(t){return(v=Object.setPrototypeOf?Object.getPrototypeOf:function(t){return t.__proto__||Object.getPrototypeOf(t)})(t)}function m(t,e){t="data-clipboard-".concat(t);if(e.hasAttribute(t))return e.getAttribute(t)}var b=function(){!function(t,e){if("function"!=typeof e&&null!==e)throw new TypeError("Super expression must either be null or a function");t.prototype=Object.create(e&&e.prototype,{constructor:{value:t,writable:!0,configurable:!0}}),e&&y(t,e)}(r,i());var t,e,n,o=h(r);function r(t,e){var n;return function(t){if(!(t instanceof r))throw new TypeError("Cannot call a class as a function")}(this),(n=o.call(this)).resolveOptions(e),n.listenClick(t),n}return t=r,n=[{key:"copy",value:function(t){var e=1<arguments.length&&void 0!==arguments[1]?arguments[1]:{container:document.body};return f(t,e)}},{key:"cut",value:function(t){return a(t)}},{key:"isSupported",value:function(){var t=0<arguments.length&&void 0!==arguments[0]?arguments[0]:["copy","cut"],t="string"==typeof t?[t]:t,e=!!document.queryCommandSupported;return t.forEach(function(t){e=e&&!!document.queryCommandSupported(t)}),e}}],(e=[{key:"resolveOptions",value:function(){var t=0<arguments.length&&void 0!==arguments[0]?arguments[0]:{};this.action="function"==typeof t.action?t.action:this.defaultAction,this.target="function"==typeof t.target?t.target:this.defaultTarget,this.text="function"==typeof t.text?t.text:this.defaultText,this.container="object"===p(t.container)?t.container:document.body}},{key:"listenClick",value:function(t){var e=this;this.listener=u()(t,"click",function(t){return e.onClick(t)})}},{key:"onClick",value:function(t){var e=t.delegateTarget||t.currentTarget,n=this.action(e)||"copy",t=s({action:n,container:this.container,target:this.target(e),text:this.text(e)});this.emit(t?"success":"error",{action:n,text:t,trigger:e,clearSelection:function(){e&&e.focus(),window.getSelection().removeAllRanges()}})}},{key:"defaultAction",value:function(t){return m("action",t)}},{key:"defaultTarget",value:function(t){t=m("target",t);if(t)return document.querySelector(t)}},{key:"defaultText",value:function(t){return m("text",t)}},{key:"destroy",value:function(){this.listener.destroy()}}])&&d(t.prototype,e),n&&d(t,n),r}()},828:function(t){var e;"undefined"==typeof Element||Element.prototype.matches||((e=Element.prototype).matches=e.matchesSelector||e.mozMatchesSelector||e.msMatchesSelector||e.oMatchesSelector||e.webkitMatchesSelector),t.exports=function(t,e){for(;t&&9!==t.nodeType;){if("function"==typeof t.matches&&t.matches(e))return t;t=t.parentNode}}},438:function(t,e,n){var u=n(828);function i(t,e,n,o,r){var i=function(e,n,t,o){return function(t){t.delegateTarget=u(t.target,n),t.delegateTarget&&o.call(e,t)}}.apply(this,arguments);return t.addEventListener(n,i,r),{destroy:function(){t.removeEventListener(n,i,r)}}}t.exports=function(t,e,n,o,r){return"function"==typeof t.addEventListener?i.apply(null,arguments):"function"==typeof n?i.bind(null,document).apply(null,arguments):("string"==typeof t&&(t=document.querySelectorAll(t)),Array.prototype.map.call(t,function(t){return i(t,e,n,o,r)}))}},879:function(t,n){n.node=function(t){return void 0!==t&&t instanceof HTMLElement&&1===t.nodeType},n.nodeList=function(t){var e=Object.prototype.toString.call(t);return void 0!==t&&("[object NodeList]"===e||"[object HTMLCollection]"===e)&&"length"in t&&(0===t.length||n.node(t[0]))},n.string=function(t){return"string"==typeof t||t instanceof String},n.fn=function(t){return"[object Function]"===Object.prototype.toString.call(t)}},370:function(t,e,n){var f=n(879),l=n(438);t.exports=function(t,e,n){if(!t&&!e&&!n)throw new Error("Missing required arguments");if(!f.string(e))throw new TypeError("Second argument must be a String");if(!f.fn(n))throw new TypeError("Third argument must be a Function");if(f.node(t))return c=e,a=n,(u=t).addEventListener(c,a),{destroy:function(){u.removeEventListener(c,a)}};if(f.nodeList(t))return o=t,r=e,i=n,Array.prototype.forEach.call(o,function(t){t.addEventListener(r,i)}),{destroy:function(){Array.prototype.forEach.call(o,function(t){t.removeEventListener(r,i)})}};if(f.string(t))return t=t,e=e,n=n,l(document.body,t,e,n);throw new TypeError("First argument must be a String, HTMLElement, HTMLCollection, or NodeList");var o,r,i,u,c,a}},817:function(t){t.exports=function(t){var e,n="SELECT"===t.nodeName?(t.focus(),t.value):"INPUT"===t.nodeName||"TEXTAREA"===t.nodeName?((e=t.hasAttribute("readonly"))||t.setAttribute("readonly",""),t.select(),t.setSelectionRange(0,t.value.length),e||t.removeAttribute("readonly"),t.value):(t.hasAttribute("contenteditable")&&t.focus(),n=window.getSelection(),(e=document.createRange()).selectNodeContents(t),n.removeAllRanges(),n.addRange(e),n.toString());return n}},279:function(t){function e(){}e.prototype={on:function(t,e,n){var o=this.e||(this.e={});return(o[t]||(o[t]=[])).push({fn:e,ctx:n}),this},once:function(t,e,n){var o=this;function r(){o.off(t,r),e.apply(n,arguments)}return r._=e,this.on(t,r,n)},emit:function(t){for(var e=[].slice.call(arguments,1),n=((this.e||(this.e={}))[t]||[]).slice(),o=0,r=n.length;o<r;o++)n[o].fn.apply(n[o].ctx,e);return this},off:function(t,e){var n=this.e||(this.e={}),o=n[t],r=[];if(o&&e)for(var i=0,u=o.length;i<u;i++)o[i].fn!==e&&o[i].fn._!==e&&r.push(o[i]);return r.length?n[t]=r:delete n[t],this}},t.exports=e,t.exports.TinyEmitter=e}},r={},o.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return o.d(e,{a:e}),e},o.d=function(t,e){for(var n in e)o.o(e,n)&&!o.o(t,n)&&Object.defineProperty(t,n,{enumerable:!0,get:e[n]})},o.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},o(686).default;function o(t){if(r[t])return r[t].exports;var e=r[t]={exports:{}};return n[t](e,e.exports,o),e.exports}var n,r});

/*
	 _ _      _       _
 ___| (_) ___| | __  (_)___
/ __| | |/ __| |/ /  | / __|
\__ \ | | (__|   < _ | \__ \
|___/_|_|\___|_|\_(_)/ |___/
					 |__/
 Version: 1.9.0
	Author: Ken Wheeler
 Website: http://kenwheeler.github.io
	Docs: http://kenwheeler.github.io/slick
	Repo: http://github.com/kenwheeler/slick
	Issues: http://github.com/kenwheeler/slick/issues
 */
(function(i){"use strict";"function"==typeof define&&define.amd?define(["jquery"],i):"undefined"!=typeof exports?module.exports=i(require("jquery")):i(jQuery)})(function(i){"use strict";var e=window.Slick||{};e=function(){function e(e,o){var s,n=this;n.defaults={accessibility:!0,adaptiveHeight:!1,appendArrows:i(e),appendDots:i(e),arrows:!0,asNavFor:null,prevArrow:'<button class="slick-prev" aria-label="Previous" type="button">Previous</button>',nextArrow:'<button class="slick-next" aria-label="Next" type="button">Next</button>',autoplay:!1,autoplaySpeed:3e3,centerMode:!1,centerPadding:"50px",cssEase:"ease",customPaging:function(e,t){return i('<button type="button" />').text(t+1)},dots:!1,dotsClass:"slick-dots",draggable:!0,easing:"linear",edgeFriction:.35,fade:!1,focusOnSelect:!1,focusOnChange:!1,infinite:!0,initialSlide:0,lazyLoad:"ondemand",mobileFirst:!1,pauseOnHover:!0,pauseOnFocus:!0,pauseOnDotsHover:!1,respondTo:"window",responsive:null,rows:1,rtl:!1,slide:"",slidesPerRow:1,slidesToShow:1,slidesToScroll:1,speed:500,swipe:!0,swipeToSlide:!1,touchMove:!0,touchThreshold:5,useCSS:!0,useTransform:!0,variableWidth:!1,vertical:!1,verticalSwiping:!1,waitForAnimate:!0,zIndex:1e3},n.initials={animating:!1,dragging:!1,autoPlayTimer:null,currentDirection:0,currentLeft:null,currentSlide:0,direction:1,$dots:null,listWidth:null,listHeight:null,loadIndex:0,$nextArrow:null,$prevArrow:null,scrolling:!1,slideCount:null,slideWidth:null,$slideTrack:null,$slides:null,sliding:!1,slideOffset:0,swipeLeft:null,swiping:!1,$list:null,touchObject:{},transformsEnabled:!1,unslicked:!1},i.extend(n,n.initials),n.activeBreakpoint=null,n.animType=null,n.animProp=null,n.breakpoints=[],n.breakpointSettings=[],n.cssTransitions=!1,n.focussed=!1,n.interrupted=!1,n.hidden="hidden",n.paused=!0,n.positionProp=null,n.respondTo=null,n.rowCount=1,n.shouldClick=!0,n.$slider=i(e),n.$slidesCache=null,n.transformType=null,n.transitionType=null,n.visibilityChange="visibilitychange",n.windowWidth=0,n.windowTimer=null,s=i(e).data("slick")||{},n.options=i.extend({},n.defaults,o,s),n.currentSlide=n.options.initialSlide,n.originalSettings=n.options,"undefined"!=typeof document.mozHidden?(n.hidden="mozHidden",n.visibilityChange="mozvisibilitychange"):"undefined"!=typeof document.webkitHidden&&(n.hidden="webkitHidden",n.visibilityChange="webkitvisibilitychange"),n.autoPlay=i.proxy(n.autoPlay,n),n.autoPlayClear=i.proxy(n.autoPlayClear,n),n.autoPlayIterator=i.proxy(n.autoPlayIterator,n),n.changeSlide=i.proxy(n.changeSlide,n),n.clickHandler=i.proxy(n.clickHandler,n),n.selectHandler=i.proxy(n.selectHandler,n),n.setPosition=i.proxy(n.setPosition,n),n.swipeHandler=i.proxy(n.swipeHandler,n),n.dragHandler=i.proxy(n.dragHandler,n),n.keyHandler=i.proxy(n.keyHandler,n),n.instanceUid=t++,n.htmlExpr=/^(?:\s*(<[\w\W]+>)[^>]*)$/,n.registerBreakpoints(),n.init(!0)}var t=0;return e}(),e.prototype.activateADA=function(){var i=this;i.$slideTrack.find(".slick-active").attr({"aria-hidden":"false"}).find("a, input, button, select").attr({tabindex:"0"})},e.prototype.addSlide=e.prototype.slickAdd=function(e,t,o){var s=this;if("boolean"==typeof t)o=t,t=null;else if(t<0||t>=s.slideCount)return!1;s.unload(),"number"==typeof t?0===t&&0===s.$slides.length?i(e).appendTo(s.$slideTrack):o?i(e).insertBefore(s.$slides.eq(t)):i(e).insertAfter(s.$slides.eq(t)):o===!0?i(e).prependTo(s.$slideTrack):i(e).appendTo(s.$slideTrack),s.$slides=s.$slideTrack.children(this.options.slide),s.$slideTrack.children(this.options.slide).detach(),s.$slideTrack.append(s.$slides),s.$slides.each(function(e,t){i(t).attr("data-slick-index",e)}),s.$slidesCache=s.$slides,s.reinit()},e.prototype.animateHeight=function(){var i=this;if(1===i.options.slidesToShow&&i.options.adaptiveHeight===!0&&i.options.vertical===!1){var e=i.$slides.eq(i.currentSlide).outerHeight(!0);i.$list.animate({height:e},i.options.speed)}},e.prototype.animateSlide=function(e,t){var o={},s=this;s.animateHeight(),s.options.rtl===!0&&s.options.vertical===!1&&(e=-e),s.transformsEnabled===!1?s.options.vertical===!1?s.$slideTrack.animate({left:e},s.options.speed,s.options.easing,t):s.$slideTrack.animate({top:e},s.options.speed,s.options.easing,t):s.cssTransitions===!1?(s.options.rtl===!0&&(s.currentLeft=-s.currentLeft),i({animStart:s.currentLeft}).animate({animStart:e},{duration:s.options.speed,easing:s.options.easing,step:function(i){i=Math.ceil(i),s.options.vertical===!1?(o[s.animType]="translate("+i+"px, 0px)",s.$slideTrack.css(o)):(o[s.animType]="translate(0px,"+i+"px)",s.$slideTrack.css(o))},complete:function(){t&&t.call()}})):(s.applyTransition(),e=Math.ceil(e),s.options.vertical===!1?o[s.animType]="translate3d("+e+"px, 0px, 0px)":o[s.animType]="translate3d(0px,"+e+"px, 0px)",s.$slideTrack.css(o),t&&setTimeout(function(){s.disableTransition(),t.call()},s.options.speed))},e.prototype.getNavTarget=function(){var e=this,t=e.options.asNavFor;return t&&null!==t&&(t=i(t).not(e.$slider)),t},e.prototype.asNavFor=function(e){var t=this,o=t.getNavTarget();null!==o&&"object"==typeof o&&o.each(function(){var t=i(this).slick("getSlick");t.unslicked||t.slideHandler(e,!0)})},e.prototype.applyTransition=function(i){var e=this,t={};e.options.fade===!1?t[e.transitionType]=e.transformType+" "+e.options.speed+"ms "+e.options.cssEase:t[e.transitionType]="opacity "+e.options.speed+"ms "+e.options.cssEase,e.options.fade===!1?e.$slideTrack.css(t):e.$slides.eq(i).css(t)},e.prototype.autoPlay=function(){var i=this;i.autoPlayClear(),i.slideCount>i.options.slidesToShow&&(i.autoPlayTimer=setInterval(i.autoPlayIterator,i.options.autoplaySpeed))},e.prototype.autoPlayClear=function(){var i=this;i.autoPlayTimer&&clearInterval(i.autoPlayTimer)},e.prototype.autoPlayIterator=function(){var i=this,e=i.currentSlide+i.options.slidesToScroll;i.paused||i.interrupted||i.focussed||(i.options.infinite===!1&&(1===i.direction&&i.currentSlide+1===i.slideCount-1?i.direction=0:0===i.direction&&(e=i.currentSlide-i.options.slidesToScroll,i.currentSlide-1===0&&(i.direction=1))),i.slideHandler(e))},e.prototype.buildArrows=function(){var e=this;e.options.arrows===!0&&(e.$prevArrow=i(e.options.prevArrow).addClass("slick-arrow"),e.$nextArrow=i(e.options.nextArrow).addClass("slick-arrow"),e.slideCount>e.options.slidesToShow?(e.$prevArrow.removeClass("slick-hidden").removeAttr("aria-hidden tabindex"),e.$nextArrow.removeClass("slick-hidden").removeAttr("aria-hidden tabindex"),e.htmlExpr.test(e.options.prevArrow)&&e.$prevArrow.prependTo(e.options.appendArrows),e.htmlExpr.test(e.options.nextArrow)&&e.$nextArrow.appendTo(e.options.appendArrows),e.options.infinite!==!0&&e.$prevArrow.addClass("slick-disabled").attr("aria-disabled","true")):e.$prevArrow.add(e.$nextArrow).addClass("slick-hidden").attr({"aria-disabled":"true",tabindex:"-1"}))},e.prototype.buildDots=function(){var e,t,o=this;if(o.options.dots===!0&&o.slideCount>o.options.slidesToShow){for(o.$slider.addClass("slick-dotted"),t=i("<ul />").addClass(o.options.dotsClass),e=0;e<=o.getDotCount();e+=1)t.append(i("<li />").append(o.options.customPaging.call(this,o,e)));o.$dots=t.appendTo(o.options.appendDots),o.$dots.find("li").first().addClass("slick-active")}},e.prototype.buildOut=function(){var e=this;e.$slides=e.$slider.children(e.options.slide+":not(.slick-cloned)").addClass("slick-slide"),e.slideCount=e.$slides.length,e.$slides.each(function(e,t){i(t).attr("data-slick-index",e).data("originalStyling",i(t).attr("style")||"")}),e.$slider.addClass("slick-slider"),e.$slideTrack=0===e.slideCount?i('<div class="slick-track"/>').appendTo(e.$slider):e.$slides.wrapAll('<div class="slick-track"/>').parent(),e.$list=e.$slideTrack.wrap('<div class="slick-list"/>').parent(),e.$slideTrack.css("opacity",0),e.options.centerMode!==!0&&e.options.swipeToSlide!==!0||(e.options.slidesToScroll=1),i("img[data-lazy]",e.$slider).not("[src]").addClass("slick-loading"),e.setupInfinite(),e.buildArrows(),e.buildDots(),e.updateDots(),e.setSlideClasses("number"==typeof e.currentSlide?e.currentSlide:0),e.options.draggable===!0&&e.$list.addClass("draggable")},e.prototype.buildRows=function(){var i,e,t,o,s,n,r,l=this;if(o=document.createDocumentFragment(),n=l.$slider.children(),l.options.rows>0){for(r=l.options.slidesPerRow*l.options.rows,s=Math.ceil(n.length/r),i=0;i<s;i++){var d=document.createElement("div");for(e=0;e<l.options.rows;e++){var a=document.createElement("div");for(t=0;t<l.options.slidesPerRow;t++){var c=i*r+(e*l.options.slidesPerRow+t);n.get(c)&&a.appendChild(n.get(c))}d.appendChild(a)}o.appendChild(d)}l.$slider.empty().append(o),l.$slider.children().children().children().css({width:100/l.options.slidesPerRow+"%",display:"inline-block"})}},e.prototype.checkResponsive=function(e,t){var o,s,n,r=this,l=!1,d=r.$slider.width(),a=window.innerWidth||i(window).width();if("window"===r.respondTo?n=a:"slider"===r.respondTo?n=d:"min"===r.respondTo&&(n=Math.min(a,d)),r.options.responsive&&r.options.responsive.length&&null!==r.options.responsive){s=null;for(o in r.breakpoints)r.breakpoints.hasOwnProperty(o)&&(r.originalSettings.mobileFirst===!1?n<r.breakpoints[o]&&(s=r.breakpoints[o]):n>r.breakpoints[o]&&(s=r.breakpoints[o]));null!==s?null!==r.activeBreakpoint?(s!==r.activeBreakpoint||t)&&(r.activeBreakpoint=s,"unslick"===r.breakpointSettings[s]?r.unslick(s):(r.options=i.extend({},r.originalSettings,r.breakpointSettings[s]),e===!0&&(r.currentSlide=r.options.initialSlide),r.refresh(e)),l=s):(r.activeBreakpoint=s,"unslick"===r.breakpointSettings[s]?r.unslick(s):(r.options=i.extend({},r.originalSettings,r.breakpointSettings[s]),e===!0&&(r.currentSlide=r.options.initialSlide),r.refresh(e)),l=s):null!==r.activeBreakpoint&&(r.activeBreakpoint=null,r.options=r.originalSettings,e===!0&&(r.currentSlide=r.options.initialSlide),r.refresh(e),l=s),e||l===!1||r.$slider.trigger("breakpoint",[r,l])}},e.prototype.changeSlide=function(e,t){var o,s,n,r=this,l=i(e.currentTarget);switch(l.is("a")&&e.preventDefault(),l.is("li")||(l=l.closest("li")),n=r.slideCount%r.options.slidesToScroll!==0,o=n?0:(r.slideCount-r.currentSlide)%r.options.slidesToScroll,e.data.message){case"previous":s=0===o?r.options.slidesToScroll:r.options.slidesToShow-o,r.slideCount>r.options.slidesToShow&&r.slideHandler(r.currentSlide-s,!1,t);break;case"next":s=0===o?r.options.slidesToScroll:o,r.slideCount>r.options.slidesToShow&&r.slideHandler(r.currentSlide+s,!1,t);break;case"index":var d=0===e.data.index?0:e.data.index||l.index()*r.options.slidesToScroll;r.slideHandler(r.checkNavigable(d),!1,t),l.children().trigger("focus");break;default:return}},e.prototype.checkNavigable=function(i){var e,t,o=this;if(e=o.getNavigableIndexes(),t=0,i>e[e.length-1])i=e[e.length-1];else for(var s in e){if(i<e[s]){i=t;break}t=e[s]}return i},e.prototype.cleanUpEvents=function(){var e=this;e.options.dots&&null!==e.$dots&&(i("li",e.$dots).off("click.slick",e.changeSlide).off("mouseenter.slick",i.proxy(e.interrupt,e,!0)).off("mouseleave.slick",i.proxy(e.interrupt,e,!1)),e.options.accessibility===!0&&e.$dots.off("keydown.slick",e.keyHandler)),e.$slider.off("focus.slick blur.slick"),e.options.arrows===!0&&e.slideCount>e.options.slidesToShow&&(e.$prevArrow&&e.$prevArrow.off("click.slick",e.changeSlide),e.$nextArrow&&e.$nextArrow.off("click.slick",e.changeSlide),e.options.accessibility===!0&&(e.$prevArrow&&e.$prevArrow.off("keydown.slick",e.keyHandler),e.$nextArrow&&e.$nextArrow.off("keydown.slick",e.keyHandler))),e.$list.off("touchstart.slick mousedown.slick",e.swipeHandler),e.$list.off("touchmove.slick mousemove.slick",e.swipeHandler),e.$list.off("touchend.slick mouseup.slick",e.swipeHandler),e.$list.off("touchcancel.slick mouseleave.slick",e.swipeHandler),e.$list.off("click.slick",e.clickHandler),i(document).off(e.visibilityChange,e.visibility),e.cleanUpSlideEvents(),e.options.accessibility===!0&&e.$list.off("keydown.slick",e.keyHandler),e.options.focusOnSelect===!0&&i(e.$slideTrack).children().off("click.slick",e.selectHandler),i(window).off("orientationchange.slick.slick-"+e.instanceUid,e.orientationChange),i(window).off("resize.slick.slick-"+e.instanceUid,e.resize),i("[draggable!=true]",e.$slideTrack).off("dragstart",e.preventDefault),i(window).off("load.slick.slick-"+e.instanceUid,e.setPosition)},e.prototype.cleanUpSlideEvents=function(){var e=this;e.$list.off("mouseenter.slick",i.proxy(e.interrupt,e,!0)),e.$list.off("mouseleave.slick",i.proxy(e.interrupt,e,!1))},e.prototype.cleanUpRows=function(){var i,e=this;e.options.rows>0&&(i=e.$slides.children().children(),i.removeAttr("style"),e.$slider.empty().append(i))},e.prototype.clickHandler=function(i){var e=this;e.shouldClick===!1&&(i.stopImmediatePropagation(),i.stopPropagation(),i.preventDefault())},e.prototype.destroy=function(e){var t=this;t.autoPlayClear(),t.touchObject={},t.cleanUpEvents(),i(".slick-cloned",t.$slider).detach(),t.$dots&&t.$dots.remove(),t.$prevArrow&&t.$prevArrow.length&&(t.$prevArrow.removeClass("slick-disabled slick-arrow slick-hidden").removeAttr("aria-hidden aria-disabled tabindex").css("display",""),t.htmlExpr.test(t.options.prevArrow)&&t.$prevArrow.remove()),t.$nextArrow&&t.$nextArrow.length&&(t.$nextArrow.removeClass("slick-disabled slick-arrow slick-hidden").removeAttr("aria-hidden aria-disabled tabindex").css("display",""),t.htmlExpr.test(t.options.nextArrow)&&t.$nextArrow.remove()),t.$slides&&(t.$slides.removeClass("slick-slide slick-active slick-center slick-visible slick-current").removeAttr("aria-hidden").removeAttr("data-slick-index").each(function(){i(this).attr("style",i(this).data("originalStyling"))}),t.$slideTrack.children(this.options.slide).detach(),t.$slideTrack.detach(),t.$list.detach(),t.$slider.append(t.$slides)),t.cleanUpRows(),t.$slider.removeClass("slick-slider"),t.$slider.removeClass("slick-initialized"),t.$slider.removeClass("slick-dotted"),t.unslicked=!0,e||t.$slider.trigger("destroy",[t])},e.prototype.disableTransition=function(i){var e=this,t={};t[e.transitionType]="",e.options.fade===!1?e.$slideTrack.css(t):e.$slides.eq(i).css(t)},e.prototype.fadeSlide=function(i,e){var t=this;t.cssTransitions===!1?(t.$slides.eq(i).css({zIndex:t.options.zIndex}),t.$slides.eq(i).animate({opacity:1},t.options.speed,t.options.easing,e)):(t.applyTransition(i),t.$slides.eq(i).css({opacity:1,zIndex:t.options.zIndex}),e&&setTimeout(function(){t.disableTransition(i),e.call()},t.options.speed))},e.prototype.fadeSlideOut=function(i){var e=this;e.cssTransitions===!1?e.$slides.eq(i).animate({opacity:0,zIndex:e.options.zIndex-2},e.options.speed,e.options.easing):(e.applyTransition(i),e.$slides.eq(i).css({opacity:0,zIndex:e.options.zIndex-2}))},e.prototype.filterSlides=e.prototype.slickFilter=function(i){var e=this;null!==i&&(e.$slidesCache=e.$slides,e.unload(),e.$slideTrack.children(this.options.slide).detach(),e.$slidesCache.filter(i).appendTo(e.$slideTrack),e.reinit())},e.prototype.focusHandler=function(){var e=this;e.$slider.off("focus.slick blur.slick").on("focus.slick","*",function(t){var o=i(this);setTimeout(function(){e.options.pauseOnFocus&&o.is(":focus")&&(e.focussed=!0,e.autoPlay())},0)}).on("blur.slick","*",function(t){i(this);e.options.pauseOnFocus&&(e.focussed=!1,e.autoPlay())})},e.prototype.getCurrent=e.prototype.slickCurrentSlide=function(){var i=this;return i.currentSlide},e.prototype.getDotCount=function(){var i=this,e=0,t=0,o=0;if(i.options.infinite===!0)if(i.slideCount<=i.options.slidesToShow)++o;else for(;e<i.slideCount;)++o,e=t+i.options.slidesToScroll,t+=i.options.slidesToScroll<=i.options.slidesToShow?i.options.slidesToScroll:i.options.slidesToShow;else if(i.options.centerMode===!0)o=i.slideCount;else if(i.options.asNavFor)for(;e<i.slideCount;)++o,e=t+i.options.slidesToScroll,t+=i.options.slidesToScroll<=i.options.slidesToShow?i.options.slidesToScroll:i.options.slidesToShow;else o=1+Math.ceil((i.slideCount-i.options.slidesToShow)/i.options.slidesToScroll);return o-1},e.prototype.getLeft=function(i){var e,t,o,s,n=this,r=0;return n.slideOffset=0,t=n.$slides.first().outerHeight(!0),n.options.infinite===!0?(n.slideCount>n.options.slidesToShow&&(n.slideOffset=n.slideWidth*n.options.slidesToShow*-1,s=-1,n.options.vertical===!0&&n.options.centerMode===!0&&(2===n.options.slidesToShow?s=-1.5:1===n.options.slidesToShow&&(s=-2)),r=t*n.options.slidesToShow*s),n.slideCount%n.options.slidesToScroll!==0&&i+n.options.slidesToScroll>n.slideCount&&n.slideCount>n.options.slidesToShow&&(i>n.slideCount?(n.slideOffset=(n.options.slidesToShow-(i-n.slideCount))*n.slideWidth*-1,r=(n.options.slidesToShow-(i-n.slideCount))*t*-1):(n.slideOffset=n.slideCount%n.options.slidesToScroll*n.slideWidth*-1,r=n.slideCount%n.options.slidesToScroll*t*-1))):i+n.options.slidesToShow>n.slideCount&&(n.slideOffset=(i+n.options.slidesToShow-n.slideCount)*n.slideWidth,r=(i+n.options.slidesToShow-n.slideCount)*t),n.slideCount<=n.options.slidesToShow&&(n.slideOffset=0,r=0),n.options.centerMode===!0&&n.slideCount<=n.options.slidesToShow?n.slideOffset=n.slideWidth*Math.floor(n.options.slidesToShow)/2-n.slideWidth*n.slideCount/2:n.options.centerMode===!0&&n.options.infinite===!0?n.slideOffset+=n.slideWidth*Math.floor(n.options.slidesToShow/2)-n.slideWidth:n.options.centerMode===!0&&(n.slideOffset=0,n.slideOffset+=n.slideWidth*Math.floor(n.options.slidesToShow/2)),e=n.options.vertical===!1?i*n.slideWidth*-1+n.slideOffset:i*t*-1+r,n.options.variableWidth===!0&&(o=n.slideCount<=n.options.slidesToShow||n.options.infinite===!1?n.$slideTrack.children(".slick-slide").eq(i):n.$slideTrack.children(".slick-slide").eq(i+n.options.slidesToShow),e=n.options.rtl===!0?o[0]?(n.$slideTrack.width()-o[0].offsetLeft-o.width())*-1:0:o[0]?o[0].offsetLeft*-1:0,n.options.centerMode===!0&&(o=n.slideCount<=n.options.slidesToShow||n.options.infinite===!1?n.$slideTrack.children(".slick-slide").eq(i):n.$slideTrack.children(".slick-slide").eq(i+n.options.slidesToShow+1),e=n.options.rtl===!0?o[0]?(n.$slideTrack.width()-o[0].offsetLeft-o.width())*-1:0:o[0]?o[0].offsetLeft*-1:0,e+=(n.$list.width()-o.outerWidth())/2)),e},e.prototype.getOption=e.prototype.slickGetOption=function(i){var e=this;return e.options[i]},e.prototype.getNavigableIndexes=function(){var i,e=this,t=0,o=0,s=[];for(e.options.infinite===!1?i=e.slideCount:(t=e.options.slidesToScroll*-1,o=e.options.slidesToScroll*-1,i=2*e.slideCount);t<i;)s.push(t),t=o+e.options.slidesToScroll,o+=e.options.slidesToScroll<=e.options.slidesToShow?e.options.slidesToScroll:e.options.slidesToShow;return s},e.prototype.getSlick=function(){return this},e.prototype.getSlideCount=function(){var e,t,o,s,n=this;return s=n.options.centerMode===!0?Math.floor(n.$list.width()/2):0,o=n.swipeLeft*-1+s,n.options.swipeToSlide===!0?(n.$slideTrack.find(".slick-slide").each(function(e,s){var r,l,d;if(r=i(s).outerWidth(),l=s.offsetLeft,n.options.centerMode!==!0&&(l+=r/2),d=l+r,o<d)return t=s,!1}),e=Math.abs(i(t).attr("data-slick-index")-n.currentSlide)||1):n.options.slidesToScroll},e.prototype.goTo=e.prototype.slickGoTo=function(i,e){var t=this;t.changeSlide({data:{message:"index",index:parseInt(i)}},e)},e.prototype.init=function(e){var t=this;i(t.$slider).hasClass("slick-initialized")||(i(t.$slider).addClass("slick-initialized"),t.buildRows(),t.buildOut(),t.setProps(),t.startLoad(),t.loadSlider(),t.initializeEvents(),t.updateArrows(),t.updateDots(),t.checkResponsive(!0),t.focusHandler()),e&&t.$slider.trigger("init",[t]),t.options.accessibility===!0&&t.initADA(),t.options.autoplay&&(t.paused=!1,t.autoPlay())},e.prototype.initADA=function(){var e=this,t=Math.ceil(e.slideCount/e.options.slidesToShow),o=e.getNavigableIndexes().filter(function(i){return i>=0&&i<e.slideCount});e.$slides.add(e.$slideTrack.find(".slick-cloned")).attr({"aria-hidden":"true",tabindex:"-1"}).find("a, input, button, select").attr({tabindex:"-1"}),null!==e.$dots&&(e.$slides.not(e.$slideTrack.find(".slick-cloned")).each(function(t){var s=o.indexOf(t);if(i(this).attr({role:"tabpanel",id:"slick-slide"+e.instanceUid+t,tabindex:-1}),s!==-1){var n="slick-slide-control"+e.instanceUid+s;i("#"+n).length&&i(this).attr({"aria-describedby":n})}}),e.$dots.attr("role","tablist").find("li").each(function(s){var n=o[s];i(this).attr({role:"presentation"}),i(this).find("button").first().attr({role:"tab",id:"slick-slide-control"+e.instanceUid+s,"aria-controls":"slick-slide"+e.instanceUid+n,"aria-label":s+1+" of "+t,"aria-selected":null,tabindex:"-1"})}).eq(e.currentSlide).find("button").attr({"aria-selected":"true",tabindex:"0"}).end());for(var s=e.currentSlide,n=s+e.options.slidesToShow;s<n;s++)e.options.focusOnChange?e.$slides.eq(s).attr({tabindex:"0"}):e.$slides.eq(s).removeAttr("tabindex");e.activateADA()},e.prototype.initArrowEvents=function(){var i=this;i.options.arrows===!0&&i.slideCount>i.options.slidesToShow&&(i.$prevArrow.off("click.slick").on("click.slick",{message:"previous"},i.changeSlide),i.$nextArrow.off("click.slick").on("click.slick",{message:"next"},i.changeSlide),i.options.accessibility===!0&&(i.$prevArrow.on("keydown.slick",i.keyHandler),i.$nextArrow.on("keydown.slick",i.keyHandler)))},e.prototype.initDotEvents=function(){var e=this;e.options.dots===!0&&e.slideCount>e.options.slidesToShow&&(i("li",e.$dots).on("click.slick",{message:"index"},e.changeSlide),e.options.accessibility===!0&&e.$dots.on("keydown.slick",e.keyHandler)),e.options.dots===!0&&e.options.pauseOnDotsHover===!0&&e.slideCount>e.options.slidesToShow&&i("li",e.$dots).on("mouseenter.slick",i.proxy(e.interrupt,e,!0)).on("mouseleave.slick",i.proxy(e.interrupt,e,!1))},e.prototype.initSlideEvents=function(){var e=this;e.options.pauseOnHover&&(e.$list.on("mouseenter.slick",i.proxy(e.interrupt,e,!0)),e.$list.on("mouseleave.slick",i.proxy(e.interrupt,e,!1)))},e.prototype.initializeEvents=function(){var e=this;e.initArrowEvents(),e.initDotEvents(),e.initSlideEvents(),e.$list.on("touchstart.slick mousedown.slick",{action:"start"},e.swipeHandler),e.$list.on("touchmove.slick mousemove.slick",{action:"move"},e.swipeHandler),e.$list.on("touchend.slick mouseup.slick",{action:"end"},e.swipeHandler),e.$list.on("touchcancel.slick mouseleave.slick",{action:"end"},e.swipeHandler),e.$list.on("click.slick",e.clickHandler),i(document).on(e.visibilityChange,i.proxy(e.visibility,e)),e.options.accessibility===!0&&e.$list.on("keydown.slick",e.keyHandler),e.options.focusOnSelect===!0&&i(e.$slideTrack).children().on("click.slick",e.selectHandler),i(window).on("orientationchange.slick.slick-"+e.instanceUid,i.proxy(e.orientationChange,e)),i(window).on("resize.slick.slick-"+e.instanceUid,i.proxy(e.resize,e)),i("[draggable!=true]",e.$slideTrack).on("dragstart",e.preventDefault),i(window).on("load.slick.slick-"+e.instanceUid,e.setPosition),i(e.setPosition)},e.prototype.initUI=function(){var i=this;i.options.arrows===!0&&i.slideCount>i.options.slidesToShow&&(i.$prevArrow.show(),i.$nextArrow.show()),i.options.dots===!0&&i.slideCount>i.options.slidesToShow&&i.$dots.show()},e.prototype.keyHandler=function(i){var e=this;i.target.tagName.match("TEXTAREA|INPUT|SELECT")||(37===i.keyCode&&e.options.accessibility===!0?e.changeSlide({data:{message:e.options.rtl===!0?"next":"previous"}}):39===i.keyCode&&e.options.accessibility===!0&&e.changeSlide({data:{message:e.options.rtl===!0?"previous":"next"}}))},e.prototype.lazyLoad=function(){function e(e){i("img[data-lazy]",e).each(function(){var e=i(this),t=i(this).attr("data-lazy"),o=i(this).attr("data-srcset"),s=i(this).attr("data-sizes")||r.$slider.attr("data-sizes"),n=document.createElement("img");n.onload=function(){e.animate({opacity:0},100,function(){o&&(e.attr("srcset",o),s&&e.attr("sizes",s)),e.attr("src",t).animate({opacity:1},200,function(){e.removeAttr("data-lazy data-srcset data-sizes").removeClass("slick-loading")}),r.$slider.trigger("lazyLoaded",[r,e,t])})},n.onerror=function(){e.removeAttr("data-lazy").removeClass("slick-loading").addClass("slick-lazyload-error"),r.$slider.trigger("lazyLoadError",[r,e,t])},n.src=t})}var t,o,s,n,r=this;if(r.options.centerMode===!0?r.options.infinite===!0?(s=r.currentSlide+(r.options.slidesToShow/2+1),n=s+r.options.slidesToShow+2):(s=Math.max(0,r.currentSlide-(r.options.slidesToShow/2+1)),n=2+(r.options.slidesToShow/2+1)+r.currentSlide):(s=r.options.infinite?r.options.slidesToShow+r.currentSlide:r.currentSlide,n=Math.ceil(s+r.options.slidesToShow),r.options.fade===!0&&(s>0&&s--,n<=r.slideCount&&n++)),t=r.$slider.find(".slick-slide").slice(s,n),"anticipated"===r.options.lazyLoad)for(var l=s-1,d=n,a=r.$slider.find(".slick-slide"),c=0;c<r.options.slidesToScroll;c++)l<0&&(l=r.slideCount-1),t=t.add(a.eq(l)),t=t.add(a.eq(d)),l--,d++;e(t),r.slideCount<=r.options.slidesToShow?(o=r.$slider.find(".slick-slide"),e(o)):r.currentSlide>=r.slideCount-r.options.slidesToShow?(o=r.$slider.find(".slick-cloned").slice(0,r.options.slidesToShow),e(o)):0===r.currentSlide&&(o=r.$slider.find(".slick-cloned").slice(r.options.slidesToShow*-1),e(o))},e.prototype.loadSlider=function(){var i=this;i.setPosition(),i.$slideTrack.css({opacity:1}),i.$slider.removeClass("slick-loading"),i.initUI(),"progressive"===i.options.lazyLoad&&i.progressiveLazyLoad()},e.prototype.next=e.prototype.slickNext=function(){var i=this;i.changeSlide({data:{message:"next"}})},e.prototype.orientationChange=function(){var i=this;i.checkResponsive(),i.setPosition()},e.prototype.pause=e.prototype.slickPause=function(){var i=this;i.autoPlayClear(),i.paused=!0},e.prototype.play=e.prototype.slickPlay=function(){var i=this;i.autoPlay(),i.options.autoplay=!0,i.paused=!1,i.focussed=!1,i.interrupted=!1},e.prototype.postSlide=function(e){var t=this;if(!t.unslicked&&(t.$slider.trigger("afterChange",[t,e]),t.animating=!1,t.slideCount>t.options.slidesToShow&&t.setPosition(),t.swipeLeft=null,t.options.autoplay&&t.autoPlay(),t.options.accessibility===!0&&(t.initADA(),t.options.focusOnChange))){var o=i(t.$slides.get(t.currentSlide));o.attr("tabindex",0).focus()}},e.prototype.prev=e.prototype.slickPrev=function(){var i=this;i.changeSlide({data:{message:"previous"}})},e.prototype.preventDefault=function(i){i.preventDefault()},e.prototype.progressiveLazyLoad=function(e){e=e||1;var t,o,s,n,r,l=this,d=i("img[data-lazy]",l.$slider);d.length?(t=d.first(),o=t.attr("data-lazy"),s=t.attr("data-srcset"),n=t.attr("data-sizes")||l.$slider.attr("data-sizes"),r=document.createElement("img"),r.onload=function(){s&&(t.attr("srcset",s),n&&t.attr("sizes",n)),t.attr("src",o).removeAttr("data-lazy data-srcset data-sizes").removeClass("slick-loading"),l.options.adaptiveHeight===!0&&l.setPosition(),l.$slider.trigger("lazyLoaded",[l,t,o]),l.progressiveLazyLoad()},r.onerror=function(){e<3?setTimeout(function(){l.progressiveLazyLoad(e+1)},500):(t.removeAttr("data-lazy").removeClass("slick-loading").addClass("slick-lazyload-error"),l.$slider.trigger("lazyLoadError",[l,t,o]),l.progressiveLazyLoad())},r.src=o):l.$slider.trigger("allImagesLoaded",[l])},e.prototype.refresh=function(e){var t,o,s=this;o=s.slideCount-s.options.slidesToShow,!s.options.infinite&&s.currentSlide>o&&(s.currentSlide=o),s.slideCount<=s.options.slidesToShow&&(s.currentSlide=0),t=s.currentSlide,s.destroy(!0),i.extend(s,s.initials,{currentSlide:t}),s.init(),e||s.changeSlide({data:{message:"index",index:t}},!1)},e.prototype.registerBreakpoints=function(){var e,t,o,s=this,n=s.options.responsive||null;if("array"===i.type(n)&&n.length){s.respondTo=s.options.respondTo||"window";for(e in n)if(o=s.breakpoints.length-1,n.hasOwnProperty(e)){for(t=n[e].breakpoint;o>=0;)s.breakpoints[o]&&s.breakpoints[o]===t&&s.breakpoints.splice(o,1),o--;s.breakpoints.push(t),s.breakpointSettings[t]=n[e].settings}s.breakpoints.sort(function(i,e){return s.options.mobileFirst?i-e:e-i})}},e.prototype.reinit=function(){var e=this;e.$slides=e.$slideTrack.children(e.options.slide).addClass("slick-slide"),e.slideCount=e.$slides.length,e.currentSlide>=e.slideCount&&0!==e.currentSlide&&(e.currentSlide=e.currentSlide-e.options.slidesToScroll),e.slideCount<=e.options.slidesToShow&&(e.currentSlide=0),e.registerBreakpoints(),e.setProps(),e.setupInfinite(),e.buildArrows(),e.updateArrows(),e.initArrowEvents(),e.buildDots(),e.updateDots(),e.initDotEvents(),e.cleanUpSlideEvents(),e.initSlideEvents(),e.checkResponsive(!1,!0),e.options.focusOnSelect===!0&&i(e.$slideTrack).children().on("click.slick",e.selectHandler),e.setSlideClasses("number"==typeof e.currentSlide?e.currentSlide:0),e.setPosition(),e.focusHandler(),e.paused=!e.options.autoplay,e.autoPlay(),e.$slider.trigger("reInit",[e])},e.prototype.resize=function(){var e=this;i(window).width()!==e.windowWidth&&(clearTimeout(e.windowDelay),e.windowDelay=window.setTimeout(function(){e.windowWidth=i(window).width(),e.checkResponsive(),e.unslicked||e.setPosition()},50))},e.prototype.removeSlide=e.prototype.slickRemove=function(i,e,t){var o=this;return"boolean"==typeof i?(e=i,i=e===!0?0:o.slideCount-1):i=e===!0?--i:i,!(o.slideCount<1||i<0||i>o.slideCount-1)&&(o.unload(),t===!0?o.$slideTrack.children().remove():o.$slideTrack.children(this.options.slide).eq(i).remove(),o.$slides=o.$slideTrack.children(this.options.slide),o.$slideTrack.children(this.options.slide).detach(),o.$slideTrack.append(o.$slides),o.$slidesCache=o.$slides,void o.reinit())},e.prototype.setCSS=function(i){var e,t,o=this,s={};o.options.rtl===!0&&(i=-i),e="left"==o.positionProp?Math.ceil(i)+"px":"0px",t="top"==o.positionProp?Math.ceil(i)+"px":"0px",s[o.positionProp]=i,o.transformsEnabled===!1?o.$slideTrack.css(s):(s={},o.cssTransitions===!1?(s[o.animType]="translate("+e+", "+t+")",o.$slideTrack.css(s)):(s[o.animType]="translate3d("+e+", "+t+", 0px)",o.$slideTrack.css(s)))},e.prototype.setDimensions=function(){var i=this;i.options.vertical===!1?i.options.centerMode===!0&&i.$list.css({padding:"0px "+i.options.centerPadding}):(i.$list.height(i.$slides.first().outerHeight(!0)*i.options.slidesToShow),i.options.centerMode===!0&&i.$list.css({padding:i.options.centerPadding+" 0px"})),i.listWidth=i.$list.width(),i.listHeight=i.$list.height(),i.options.vertical===!1&&i.options.variableWidth===!1?(i.slideWidth=Math.ceil(i.listWidth/i.options.slidesToShow),i.$slideTrack.width(Math.ceil(i.slideWidth*i.$slideTrack.children(".slick-slide").length))):i.options.variableWidth===!0?i.$slideTrack.width(5e3*i.slideCount):(i.slideWidth=Math.ceil(i.listWidth),i.$slideTrack.height(Math.ceil(i.$slides.first().outerHeight(!0)*i.$slideTrack.children(".slick-slide").length)));var e=i.$slides.first().outerWidth(!0)-i.$slides.first().width();i.options.variableWidth===!1&&i.$slideTrack.children(".slick-slide").width(i.slideWidth-e)},e.prototype.setFade=function(){var e,t=this;t.$slides.each(function(o,s){e=t.slideWidth*o*-1,t.options.rtl===!0?i(s).css({position:"relative",right:e,top:0,zIndex:t.options.zIndex-2,opacity:0}):i(s).css({position:"relative",left:e,top:0,zIndex:t.options.zIndex-2,opacity:0})}),t.$slides.eq(t.currentSlide).css({zIndex:t.options.zIndex-1,opacity:1})},e.prototype.setHeight=function(){var i=this;if(1===i.options.slidesToShow&&i.options.adaptiveHeight===!0&&i.options.vertical===!1){var e=i.$slides.eq(i.currentSlide).outerHeight(!0);i.$list.css("height",e)}},e.prototype.setOption=e.prototype.slickSetOption=function(){var e,t,o,s,n,r=this,l=!1;if("object"===i.type(arguments[0])?(o=arguments[0],l=arguments[1],n="multiple"):"string"===i.type(arguments[0])&&(o=arguments[0],s=arguments[1],l=arguments[2],"responsive"===arguments[0]&&"array"===i.type(arguments[1])?n="responsive":"undefined"!=typeof arguments[1]&&(n="single")),"single"===n)r.options[o]=s;else if("multiple"===n)i.each(o,function(i,e){r.options[i]=e});else if("responsive"===n)for(t in s)if("array"!==i.type(r.options.responsive))r.options.responsive=[s[t]];else{for(e=r.options.responsive.length-1;e>=0;)r.options.responsive[e].breakpoint===s[t].breakpoint&&r.options.responsive.splice(e,1),e--;r.options.responsive.push(s[t])}l&&(r.unload(),r.reinit())},e.prototype.setPosition=function(){var i=this;i.setDimensions(),i.setHeight(),i.options.fade===!1?i.setCSS(i.getLeft(i.currentSlide)):i.setFade(),i.$slider.trigger("setPosition",[i])},e.prototype.setProps=function(){var i=this,e=document.body.style;i.positionProp=i.options.vertical===!0?"top":"left",
	"top"===i.positionProp?i.$slider.addClass("slick-vertical"):i.$slider.removeClass("slick-vertical"),void 0===e.WebkitTransition&&void 0===e.MozTransition&&void 0===e.msTransition||i.options.useCSS===!0&&(i.cssTransitions=!0),i.options.fade&&("number"==typeof i.options.zIndex?i.options.zIndex<3&&(i.options.zIndex=3):i.options.zIndex=i.defaults.zIndex),void 0!==e.OTransform&&(i.animType="OTransform",i.transformType="-o-transform",i.transitionType="OTransition",void 0===e.perspectiveProperty&&void 0===e.webkitPerspective&&(i.animType=!1)),void 0!==e.MozTransform&&(i.animType="MozTransform",i.transformType="-moz-transform",i.transitionType="MozTransition",void 0===e.perspectiveProperty&&void 0===e.MozPerspective&&(i.animType=!1)),void 0!==e.webkitTransform&&(i.animType="webkitTransform",i.transformType="-webkit-transform",i.transitionType="webkitTransition",void 0===e.perspectiveProperty&&void 0===e.webkitPerspective&&(i.animType=!1)),void 0!==e.msTransform&&(i.animType="msTransform",i.transformType="-ms-transform",i.transitionType="msTransition",void 0===e.msTransform&&(i.animType=!1)),void 0!==e.transform&&i.animType!==!1&&(i.animType="transform",i.transformType="transform",i.transitionType="transition"),i.transformsEnabled=i.options.useTransform&&null!==i.animType&&i.animType!==!1},e.prototype.setSlideClasses=function(i){var e,t,o,s,n=this;if(t=n.$slider.find(".slick-slide").removeClass("slick-active slick-center slick-current").attr("aria-hidden","true"),n.$slides.eq(i).addClass("slick-current"),n.options.centerMode===!0){var r=n.options.slidesToShow%2===0?1:0;e=Math.floor(n.options.slidesToShow/2),n.options.infinite===!0&&(i>=e&&i<=n.slideCount-1-e?n.$slides.slice(i-e+r,i+e+1).addClass("slick-active").attr("aria-hidden","false"):(o=n.options.slidesToShow+i,t.slice(o-e+1+r,o+e+2).addClass("slick-active").attr("aria-hidden","false")),0===i?t.eq(t.length-1-n.options.slidesToShow).addClass("slick-center"):i===n.slideCount-1&&t.eq(n.options.slidesToShow).addClass("slick-center")),n.$slides.eq(i).addClass("slick-center")}else i>=0&&i<=n.slideCount-n.options.slidesToShow?n.$slides.slice(i,i+n.options.slidesToShow).addClass("slick-active").attr("aria-hidden","false"):t.length<=n.options.slidesToShow?t.addClass("slick-active").attr("aria-hidden","false"):(s=n.slideCount%n.options.slidesToShow,o=n.options.infinite===!0?n.options.slidesToShow+i:i,n.options.slidesToShow==n.options.slidesToScroll&&n.slideCount-i<n.options.slidesToShow?t.slice(o-(n.options.slidesToShow-s),o+s).addClass("slick-active").attr("aria-hidden","false"):t.slice(o,o+n.options.slidesToShow).addClass("slick-active").attr("aria-hidden","false"));"ondemand"!==n.options.lazyLoad&&"anticipated"!==n.options.lazyLoad||n.lazyLoad()},e.prototype.setupInfinite=function(){var e,t,o,s=this;if(s.options.fade===!0&&(s.options.centerMode=!1),s.options.infinite===!0&&s.options.fade===!1&&(t=null,s.slideCount>s.options.slidesToShow)){for(o=s.options.centerMode===!0?s.options.slidesToShow+1:s.options.slidesToShow,e=s.slideCount;e>s.slideCount-o;e-=1)t=e-1,i(s.$slides[t]).clone(!0).attr("id","").attr("data-slick-index",t-s.slideCount).prependTo(s.$slideTrack).addClass("slick-cloned");for(e=0;e<o+s.slideCount;e+=1)t=e,i(s.$slides[t]).clone(!0).attr("id","").attr("data-slick-index",t+s.slideCount).appendTo(s.$slideTrack).addClass("slick-cloned");s.$slideTrack.find(".slick-cloned").find("[id]").each(function(){i(this).attr("id","")})}},e.prototype.interrupt=function(i){var e=this;i||e.autoPlay(),e.interrupted=i},e.prototype.selectHandler=function(e){var t=this,o=i(e.target).is(".slick-slide")?i(e.target):i(e.target).parents(".slick-slide"),s=parseInt(o.attr("data-slick-index"));return s||(s=0),t.slideCount<=t.options.slidesToShow?void t.slideHandler(s,!1,!0):void t.slideHandler(s)},e.prototype.slideHandler=function(i,e,t){var o,s,n,r,l,d=null,a=this;if(e=e||!1,!(a.animating===!0&&a.options.waitForAnimate===!0||a.options.fade===!0&&a.currentSlide===i))return e===!1&&a.asNavFor(i),o=i,d=a.getLeft(o),r=a.getLeft(a.currentSlide),a.currentLeft=null===a.swipeLeft?r:a.swipeLeft,a.options.infinite===!1&&a.options.centerMode===!1&&(i<0||i>a.getDotCount()*a.options.slidesToScroll)?void(a.options.fade===!1&&(o=a.currentSlide,t!==!0&&a.slideCount>a.options.slidesToShow?a.animateSlide(r,function(){a.postSlide(o)}):a.postSlide(o))):a.options.infinite===!1&&a.options.centerMode===!0&&(i<0||i>a.slideCount-a.options.slidesToScroll)?void(a.options.fade===!1&&(o=a.currentSlide,t!==!0&&a.slideCount>a.options.slidesToShow?a.animateSlide(r,function(){a.postSlide(o)}):a.postSlide(o))):(a.options.autoplay&&clearInterval(a.autoPlayTimer),s=o<0?a.slideCount%a.options.slidesToScroll!==0?a.slideCount-a.slideCount%a.options.slidesToScroll:a.slideCount+o:o>=a.slideCount?a.slideCount%a.options.slidesToScroll!==0?0:o-a.slideCount:o,a.animating=!0,a.$slider.trigger("beforeChange",[a,a.currentSlide,s]),n=a.currentSlide,a.currentSlide=s,a.setSlideClasses(a.currentSlide),a.options.asNavFor&&(l=a.getNavTarget(),l=l.slick("getSlick"),l.slideCount<=l.options.slidesToShow&&l.setSlideClasses(a.currentSlide)),a.updateDots(),a.updateArrows(),a.options.fade===!0?(t!==!0?(a.fadeSlideOut(n),a.fadeSlide(s,function(){a.postSlide(s)})):a.postSlide(s),void a.animateHeight()):void(t!==!0&&a.slideCount>a.options.slidesToShow?a.animateSlide(d,function(){a.postSlide(s)}):a.postSlide(s)))},e.prototype.startLoad=function(){var i=this;i.options.arrows===!0&&i.slideCount>i.options.slidesToShow&&(i.$prevArrow.hide(),i.$nextArrow.hide()),i.options.dots===!0&&i.slideCount>i.options.slidesToShow&&i.$dots.hide(),i.$slider.addClass("slick-loading")},e.prototype.swipeDirection=function(){var i,e,t,o,s=this;return i=s.touchObject.startX-s.touchObject.curX,e=s.touchObject.startY-s.touchObject.curY,t=Math.atan2(e,i),o=Math.round(180*t/Math.PI),o<0&&(o=360-Math.abs(o)),o<=45&&o>=0?s.options.rtl===!1?"left":"right":o<=360&&o>=315?s.options.rtl===!1?"left":"right":o>=135&&o<=225?s.options.rtl===!1?"right":"left":s.options.verticalSwiping===!0?o>=35&&o<=135?"down":"up":"vertical"},e.prototype.swipeEnd=function(i){var e,t,o=this;if(o.dragging=!1,o.swiping=!1,o.scrolling)return o.scrolling=!1,!1;if(o.interrupted=!1,o.shouldClick=!(o.touchObject.swipeLength>10),void 0===o.touchObject.curX)return!1;if(o.touchObject.edgeHit===!0&&o.$slider.trigger("edge",[o,o.swipeDirection()]),o.touchObject.swipeLength>=o.touchObject.minSwipe){switch(t=o.swipeDirection()){case"left":case"down":e=o.options.swipeToSlide?o.checkNavigable(o.currentSlide+o.getSlideCount()):o.currentSlide+o.getSlideCount(),o.currentDirection=0;break;case"right":case"up":e=o.options.swipeToSlide?o.checkNavigable(o.currentSlide-o.getSlideCount()):o.currentSlide-o.getSlideCount(),o.currentDirection=1}"vertical"!=t&&(o.slideHandler(e),o.touchObject={},o.$slider.trigger("swipe",[o,t]))}else o.touchObject.startX!==o.touchObject.curX&&(o.slideHandler(o.currentSlide),o.touchObject={})},e.prototype.swipeHandler=function(i){var e=this;if(!(e.options.swipe===!1||"ontouchend"in document&&e.options.swipe===!1||e.options.draggable===!1&&i.type.indexOf("mouse")!==-1))switch(e.touchObject.fingerCount=i.originalEvent&&void 0!==i.originalEvent.touches?i.originalEvent.touches.length:1,e.touchObject.minSwipe=e.listWidth/e.options.touchThreshold,e.options.verticalSwiping===!0&&(e.touchObject.minSwipe=e.listHeight/e.options.touchThreshold),i.data.action){case"start":e.swipeStart(i);break;case"move":e.swipeMove(i);break;case"end":e.swipeEnd(i)}},e.prototype.swipeMove=function(i){var e,t,o,s,n,r,l=this;return n=void 0!==i.originalEvent?i.originalEvent.touches:null,!(!l.dragging||l.scrolling||n&&1!==n.length)&&(e=l.getLeft(l.currentSlide),l.touchObject.curX=void 0!==n?n[0].pageX:i.clientX,l.touchObject.curY=void 0!==n?n[0].pageY:i.clientY,l.touchObject.swipeLength=Math.round(Math.sqrt(Math.pow(l.touchObject.curX-l.touchObject.startX,2))),r=Math.round(Math.sqrt(Math.pow(l.touchObject.curY-l.touchObject.startY,2))),!l.options.verticalSwiping&&!l.swiping&&r>4?(l.scrolling=!0,!1):(l.options.verticalSwiping===!0&&(l.touchObject.swipeLength=r),t=l.swipeDirection(),void 0!==i.originalEvent&&l.touchObject.swipeLength>4&&(l.swiping=!0,i.preventDefault()),s=(l.options.rtl===!1?1:-1)*(l.touchObject.curX>l.touchObject.startX?1:-1),l.options.verticalSwiping===!0&&(s=l.touchObject.curY>l.touchObject.startY?1:-1),o=l.touchObject.swipeLength,l.touchObject.edgeHit=!1,l.options.infinite===!1&&(0===l.currentSlide&&"right"===t||l.currentSlide>=l.getDotCount()&&"left"===t)&&(o=l.touchObject.swipeLength*l.options.edgeFriction,l.touchObject.edgeHit=!0),l.options.vertical===!1?l.swipeLeft=e+o*s:l.swipeLeft=e+o*(l.$list.height()/l.listWidth)*s,l.options.verticalSwiping===!0&&(l.swipeLeft=e+o*s),l.options.fade!==!0&&l.options.touchMove!==!1&&(l.animating===!0?(l.swipeLeft=null,!1):void l.setCSS(l.swipeLeft))))},e.prototype.swipeStart=function(i){var e,t=this;return t.interrupted=!0,1!==t.touchObject.fingerCount||t.slideCount<=t.options.slidesToShow?(t.touchObject={},!1):(void 0!==i.originalEvent&&void 0!==i.originalEvent.touches&&(e=i.originalEvent.touches[0]),t.touchObject.startX=t.touchObject.curX=void 0!==e?e.pageX:i.clientX,t.touchObject.startY=t.touchObject.curY=void 0!==e?e.pageY:i.clientY,void(t.dragging=!0))},e.prototype.unfilterSlides=e.prototype.slickUnfilter=function(){var i=this;null!==i.$slidesCache&&(i.unload(),i.$slideTrack.children(this.options.slide).detach(),i.$slidesCache.appendTo(i.$slideTrack),i.reinit())},e.prototype.unload=function(){var e=this;i(".slick-cloned",e.$slider).remove(),e.$dots&&e.$dots.remove(),e.$prevArrow&&e.htmlExpr.test(e.options.prevArrow)&&e.$prevArrow.remove(),e.$nextArrow&&e.htmlExpr.test(e.options.nextArrow)&&e.$nextArrow.remove(),e.$slides.removeClass("slick-slide slick-active slick-visible slick-current").attr("aria-hidden","true").css("width","")},e.prototype.unslick=function(i){var e=this;e.$slider.trigger("unslick",[e,i]),e.destroy()},e.prototype.updateArrows=function(){var i,e=this;i=Math.floor(e.options.slidesToShow/2),e.options.arrows===!0&&e.slideCount>e.options.slidesToShow&&!e.options.infinite&&(e.$prevArrow.removeClass("slick-disabled").attr("aria-disabled","false"),e.$nextArrow.removeClass("slick-disabled").attr("aria-disabled","false"),0===e.currentSlide?(e.$prevArrow.addClass("slick-disabled").attr("aria-disabled","true"),e.$nextArrow.removeClass("slick-disabled").attr("aria-disabled","false")):e.currentSlide>=e.slideCount-e.options.slidesToShow&&e.options.centerMode===!1?(e.$nextArrow.addClass("slick-disabled").attr("aria-disabled","true"),e.$prevArrow.removeClass("slick-disabled").attr("aria-disabled","false")):e.currentSlide>=e.slideCount-1&&e.options.centerMode===!0&&(e.$nextArrow.addClass("slick-disabled").attr("aria-disabled","true"),e.$prevArrow.removeClass("slick-disabled").attr("aria-disabled","false")))},e.prototype.updateDots=function(){var i=this;null!==i.$dots&&(i.$dots.find("li").removeClass("slick-active").end(),i.$dots.find("li").eq(Math.floor(i.currentSlide/i.options.slidesToScroll)).addClass("slick-active"))},e.prototype.visibility=function(){var i=this;i.options.autoplay&&(document[i.hidden]?i.interrupted=!0:i.interrupted=!1)},i.fn.slick=function(){var i,t,o=this,s=arguments[0],n=Array.prototype.slice.call(arguments,1),r=o.length;for(i=0;i<r;i++)if("object"==typeof s||"undefined"==typeof s?o[i].slick=new e(o[i],s):t=o[i].slick[s].apply(o[i].slick,n),"undefined"!=typeof t)return t;return o}});

// ==================================================
// fancyBox v3.5.7
//
// Licensed GPLv3 for open source use
// or fancyBox Commercial License for commercial use
//
// http://fancyapps.com/fancybox/
// Copyright 2019 fancyApps
//
// ==================================================
!function(t,e,n,o){"use strict";function i(t,e){var o,i,a,s=[],r=0;t&&t.isDefaultPrevented()||(t.preventDefault(),e=e||{},t&&t.data&&(e=h(t.data.options,e)),o=e.$target||n(t.currentTarget).trigger("blur"),(a=n.fancybox.getInstance())&&a.$trigger&&a.$trigger.is(o)||(e.selector?s=n(e.selector):(i=o.attr("data-fancybox")||"",i?(s=t.data?t.data.items:[],s=s.length?s.filter('[data-fancybox="'+i+'"]'):n('[data-fancybox="'+i+'"]')):s=[o]),r=n(s).index(o),r<0&&(r=0),a=n.fancybox.open(s,e,r),a.$trigger=o))}if(t.console=t.console||{info:function(t){}},n){if(n.fn.fancybox)return void console.info("fancyBox already initialized");var a={closeExisting:!1,loop:!1,gutter:50,keyboard:!0,preventCaptionOverlap:!0,arrows:!0,infobar:!0,smallBtn:"auto",toolbar:"auto",buttons:["zoom","slideShow","thumbs","close"],idleTime:3,protect:!1,modal:!1,image:{preload:!1},ajax:{settings:{data:{fancybox:!0}}},iframe:{tpl:'<iframe id="fancybox-frame{rnd}" name="fancybox-frame{rnd}" class="fancybox-iframe" allowfullscreen="allowfullscreen" allow="autoplay; fullscreen" src=""></iframe>',preload:!0,css:{},attr:{scrolling:"auto"}},video:{tpl:'<video class="fancybox-video" controls controlsList="nodownload" poster="{{poster}}"><source src="{{src}}" type="{{format}}" />Sorry, your browser doesn\'t support embedded videos, <a href="{{src}}">download</a> and watch with your favorite video player!</video>',format:"",autoStart:!0},defaultType:"image",animationEffect:"zoom",animationDuration:366,zoomOpacity:"auto",transitionEffect:"fade",transitionDuration:366,slideClass:"",baseClass:"",baseTpl:'<div class="fancybox-container" role="dialog" tabindex="-1"><div class="fancybox-bg"></div><div class="fancybox-inner"><div class="fancybox-infobar"><span data-fancybox-index></span>&nbsp;/&nbsp;<span data-fancybox-count></span></div><div class="fancybox-toolbar">{{buttons}}</div><div class="fancybox-navigation">{{arrows}}</div><div class="fancybox-stage"></div><div class="fancybox-caption"><div class="fancybox-caption__body"></div></div></div></div>',spinnerTpl:'<div class="fancybox-loading"></div>',errorTpl:'<div class="fancybox-error"><p>{{ERROR}}</p></div>',btnTpl:{download:'<a download data-fancybox-download class="fancybox-button fancybox-button--download" title="{{DOWNLOAD}}" href="javascript:;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.62 17.09V19H5.38v-1.91zm-2.97-6.96L17 11.45l-5 4.87-5-4.87 1.36-1.32 2.68 2.64V5h1.92v7.77z"/></svg></a>',zoom:'<button data-fancybox-zoom class="fancybox-button fancybox-button--zoom" title="{{ZOOM}}"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.7 17.3l-3-3a5.9 5.9 0 0 0-.6-7.6 5.9 5.9 0 0 0-8.4 0 5.9 5.9 0 0 0 0 8.4 5.9 5.9 0 0 0 7.7.7l3 3a1 1 0 0 0 1.3 0c.4-.5.4-1 0-1.5zM8.1 13.8a4 4 0 0 1 0-5.7 4 4 0 0 1 5.7 0 4 4 0 0 1 0 5.7 4 4 0 0 1-5.7 0z"/></svg></button>',close:'<button data-fancybox-close class="fancybox-button fancybox-button--close" title="{{CLOSE}}"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 10.6L6.6 5.2 5.2 6.6l5.4 5.4-5.4 5.4 1.4 1.4 5.4-5.4 5.4 5.4 1.4-1.4-5.4-5.4 5.4-5.4-1.4-1.4-5.4 5.4z"/></svg></button>',arrowLeft:'<button data-fancybox-prev class="fancybox-button fancybox-button--arrow_left" title="{{PREV}}"><div><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M11.28 15.7l-1.34 1.37L5 12l4.94-5.07 1.34 1.38-2.68 2.72H19v1.94H8.6z"/></svg></div></button>',arrowRight:'<button data-fancybox-next class="fancybox-button fancybox-button--arrow_right" title="{{NEXT}}"><div><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15.4 12.97l-2.68 2.72 1.34 1.38L19 12l-4.94-5.07-1.34 1.38 2.68 2.72H5v1.94z"/></svg></div></button>',smallBtn:'<button type="button" data-fancybox-close class="fancybox-button fancybox-close-small" title="{{CLOSE}}"><svg xmlns="http://www.w3.org/2000/svg" version="1" viewBox="0 0 24 24"><path d="M13 12l5-5-1-1-5 5-5-5-1 1 5 5-5 5 1 1 5-5 5 5 1-1z"/></svg></button>'},parentEl:"body",hideScrollbar:!0,autoFocus:!0,backFocus:!0,trapFocus:!0,fullScreen:{autoStart:!1},touch:{vertical:!0,momentum:!0},hash:null,media:{},slideShow:{autoStart:!1,speed:3e3},thumbs:{autoStart:!1,hideOnClose:!0,parentEl:".fancybox-container",axis:"y"},wheel:"auto",onInit:n.noop,beforeLoad:n.noop,afterLoad:n.noop,beforeShow:n.noop,afterShow:n.noop,beforeClose:n.noop,afterClose:n.noop,onActivate:n.noop,onDeactivate:n.noop,clickContent:function(t,e){return"image"===t.type&&"zoom"},clickSlide:"close",clickOutside:"close",dblclickContent:!1,dblclickSlide:!1,dblclickOutside:!1,mobile:{preventCaptionOverlap:!1,idleTime:!1,clickContent:function(t,e){return"image"===t.type&&"toggleControls"},clickSlide:function(t,e){return"image"===t.type?"toggleControls":"close"},dblclickContent:function(t,e){return"image"===t.type&&"zoom"},dblclickSlide:function(t,e){return"image"===t.type&&"zoom"}},lang:"en",i18n:{en:{CLOSE:"Close",NEXT:"Next",PREV:"Previous",ERROR:"The requested content cannot be loaded. <br/> Please try again later.",PLAY_START:"Start slideshow",PLAY_STOP:"Pause slideshow",FULL_SCREEN:"Full screen",THUMBS:"Thumbnails",DOWNLOAD:"Download",SHARE:"Share",ZOOM:"Zoom"},de:{CLOSE:"Schlie&szlig;en",NEXT:"Weiter",PREV:"Zur&uuml;ck",ERROR:"Die angeforderten Daten konnten nicht geladen werden. <br/> Bitte versuchen Sie es sp&auml;ter nochmal.",PLAY_START:"Diaschau starten",PLAY_STOP:"Diaschau beenden",FULL_SCREEN:"Vollbild",THUMBS:"Vorschaubilder",DOWNLOAD:"Herunterladen",SHARE:"Teilen",ZOOM:"Vergr&ouml;&szlig;ern"}}},s=n(t),r=n(e),c=0,l=function(t){return t&&t.hasOwnProperty&&t instanceof n},d=function(){return t.requestAnimationFrame||t.webkitRequestAnimationFrame||t.mozRequestAnimationFrame||t.oRequestAnimationFrame||function(e){return t.setTimeout(e,1e3/60)}}(),u=function(){return t.cancelAnimationFrame||t.webkitCancelAnimationFrame||t.mozCancelAnimationFrame||t.oCancelAnimationFrame||function(e){t.clearTimeout(e)}}(),f=function(){var t,n=e.createElement("fakeelement"),o={transition:"transitionend",OTransition:"oTransitionEnd",MozTransition:"transitionend",WebkitTransition:"webkitTransitionEnd"};for(t in o)if(void 0!==n.style[t])return o[t];return"transitionend"}(),p=function(t){return t&&t.length&&t[0].offsetHeight},h=function(t,e){var o=n.extend(!0,{},t,e);return n.each(e,function(t,e){n.isArray(e)&&(o[t]=e)}),o},g=function(t){var o,i;return!(!t||t.ownerDocument!==e)&&(n(".fancybox-container").css("pointer-events","none"),o={x:t.getBoundingClientRect().left+t.offsetWidth/2,y:t.getBoundingClientRect().top+t.offsetHeight/2},i=e.elementFromPoint(o.x,o.y)===t,n(".fancybox-container").css("pointer-events",""),i)},b=function(t,e,o){var i=this;i.opts=h({index:o},n.fancybox.defaults),n.isPlainObject(e)&&(i.opts=h(i.opts,e)),n.fancybox.isMobile&&(i.opts=h(i.opts,i.opts.mobile)),i.id=i.opts.id||++c,i.currIndex=parseInt(i.opts.index,10)||0,i.prevIndex=null,i.prevPos=null,i.currPos=0,i.firstRun=!0,i.group=[],i.slides={},i.addContent(t),i.group.length&&i.init()};n.extend(b.prototype,{init:function(){var o,i,a=this,s=a.group[a.currIndex],r=s.opts;r.closeExisting&&n.fancybox.close(!0),n("body").addClass("fancybox-active"),!n.fancybox.getInstance()&&!1!==r.hideScrollbar&&!n.fancybox.isMobile&&e.body.scrollHeight>t.innerHeight&&(n("head").append('<style id="fancybox-style-noscroll" type="text/css">.compensate-for-scrollbar{margin-right:'+(t.innerWidth-e.documentElement.clientWidth)+"px;}</style>"),n("body").addClass("compensate-for-scrollbar")),i="",n.each(r.buttons,function(t,e){i+=r.btnTpl[e]||""}),o=n(a.translate(a,r.baseTpl.replace("{{buttons}}",i).replace("{{arrows}}",r.btnTpl.arrowLeft+r.btnTpl.arrowRight))).attr("id","fancybox-container-"+a.id).addClass(r.baseClass).data("FancyBox",a).appendTo(r.parentEl),a.$refs={container:o},["bg","inner","infobar","toolbar","stage","caption","navigation"].forEach(function(t){a.$refs[t]=o.find(".fancybox-"+t)}),a.trigger("onInit"),a.activate(),a.jumpTo(a.currIndex)},translate:function(t,e){var n=t.opts.i18n[t.opts.lang]||t.opts.i18n.en;return e.replace(/\{\{(\w+)\}\}/g,function(t,e){return void 0===n[e]?t:n[e]})},addContent:function(t){var e,o=this,i=n.makeArray(t);n.each(i,function(t,e){var i,a,s,r,c,l={},d={};n.isPlainObject(e)?(l=e,d=e.opts||e):"object"===n.type(e)&&n(e).length?(i=n(e),d=i.data()||{},d=n.extend(!0,{},d,d.options),d.$orig=i,l.src=o.opts.src||d.src||i.attr("href"),l.type||l.src||(l.type="inline",l.src=e)):l={type:"html",src:e+""},l.opts=n.extend(!0,{},o.opts,d),n.isArray(d.buttons)&&(l.opts.buttons=d.buttons),n.fancybox.isMobile&&l.opts.mobile&&(l.opts=h(l.opts,l.opts.mobile)),a=l.type||l.opts.type,r=l.src||"",!a&&r&&((s=r.match(/\.(mp4|mov|ogv|webm)((\?|#).*)?$/i))?(a="video",l.opts.video.format||(l.opts.video.format="video/"+("ogv"===s[1]?"ogg":s[1]))):r.match(/(^data:image\/[a-z0-9+\/=]*,)|(\.(jp(e|g|eg)|gif|png|bmp|webp|svg|ico)((\?|#).*)?$)/i)?a="image":r.match(/\.(pdf)((\?|#).*)?$/i)?(a="iframe",l=n.extend(!0,l,{contentType:"pdf",opts:{iframe:{preload:!1}}})):"#"===r.charAt(0)&&(a="inline")),a?l.type=a:o.trigger("objectNeedsType",l),l.contentType||(l.contentType=n.inArray(l.type,["html","inline","ajax"])>-1?"html":l.type),l.index=o.group.length,"auto"==l.opts.smallBtn&&(l.opts.smallBtn=n.inArray(l.type,["html","inline","ajax"])>-1),"auto"===l.opts.toolbar&&(l.opts.toolbar=!l.opts.smallBtn),l.$thumb=l.opts.$thumb||null,l.opts.$trigger&&l.index===o.opts.index&&(l.$thumb=l.opts.$trigger.find("img:first"),l.$thumb.length&&(l.opts.$orig=l.opts.$trigger)),l.$thumb&&l.$thumb.length||!l.opts.$orig||(l.$thumb=l.opts.$orig.find("img:first")),l.$thumb&&!l.$thumb.length&&(l.$thumb=null),l.thumb=l.opts.thumb||(l.$thumb?l.$thumb[0].src:null),"function"===n.type(l.opts.caption)&&(l.opts.caption=l.opts.caption.apply(e,[o,l])),"function"===n.type(o.opts.caption)&&(l.opts.caption=o.opts.caption.apply(e,[o,l])),l.opts.caption instanceof n||(l.opts.caption=void 0===l.opts.caption?"":l.opts.caption+""),"ajax"===l.type&&(c=r.split(/\s+/,2),c.length>1&&(l.src=c.shift(),l.opts.filter=c.shift())),l.opts.modal&&(l.opts=n.extend(!0,l.opts,{trapFocus:!0,infobar:0,toolbar:0,smallBtn:0,keyboard:0,slideShow:0,fullScreen:0,thumbs:0,touch:0,clickContent:!1,clickSlide:!1,clickOutside:!1,dblclickContent:!1,dblclickSlide:!1,dblclickOutside:!1})),o.group.push(l)}),Object.keys(o.slides).length&&(o.updateControls(),(e=o.Thumbs)&&e.isActive&&(e.create(),e.focus()))},addEvents:function(){var e=this;e.removeEvents(),e.$refs.container.on("click.fb-close","[data-fancybox-close]",function(t){t.stopPropagation(),t.preventDefault(),e.close(t)}).on("touchstart.fb-prev click.fb-prev","[data-fancybox-prev]",function(t){t.stopPropagation(),t.preventDefault(),e.previous()}).on("touchstart.fb-next click.fb-next","[data-fancybox-next]",function(t){t.stopPropagation(),t.preventDefault(),e.next()}).on("click.fb","[data-fancybox-zoom]",function(t){e[e.isScaledDown()?"scaleToActual":"scaleToFit"]()}),s.on("orientationchange.fb resize.fb",function(t){t&&t.originalEvent&&"resize"===t.originalEvent.type?(e.requestId&&u(e.requestId),e.requestId=d(function(){e.update(t)})):(e.current&&"iframe"===e.current.type&&e.$refs.stage.hide(),setTimeout(function(){e.$refs.stage.show(),e.update(t)},n.fancybox.isMobile?600:250))}),r.on("keydown.fb",function(t){var o=n.fancybox?n.fancybox.getInstance():null,i=o.current,a=t.keyCode||t.which;if(9==a)return void(i.opts.trapFocus&&e.focus(t));if(!(!i.opts.keyboard||t.ctrlKey||t.altKey||t.shiftKey||n(t.target).is("input,textarea,video,audio,select")))return 8===a||27===a?(t.preventDefault(),void e.close(t)):37===a||38===a?(t.preventDefault(),void e.previous()):39===a||40===a?(t.preventDefault(),void e.next()):void e.trigger("afterKeydown",t,a)}),e.group[e.currIndex].opts.idleTime&&(e.idleSecondsCounter=0,r.on("mousemove.fb-idle mouseleave.fb-idle mousedown.fb-idle touchstart.fb-idle touchmove.fb-idle scroll.fb-idle keydown.fb-idle",function(t){e.idleSecondsCounter=0,e.isIdle&&e.showControls(),e.isIdle=!1}),e.idleInterval=t.setInterval(function(){++e.idleSecondsCounter>=e.group[e.currIndex].opts.idleTime&&!e.isDragging&&(e.isIdle=!0,e.idleSecondsCounter=0,e.hideControls())},1e3))},removeEvents:function(){var e=this;s.off("orientationchange.fb resize.fb"),r.off("keydown.fb .fb-idle"),this.$refs.container.off(".fb-close .fb-prev .fb-next"),e.idleInterval&&(t.clearInterval(e.idleInterval),e.idleInterval=null)},previous:function(t){return this.jumpTo(this.currPos-1,t)},next:function(t){return this.jumpTo(this.currPos+1,t)},jumpTo:function(t,e){var o,i,a,s,r,c,l,d,u,f=this,h=f.group.length;if(!(f.isDragging||f.isClosing||f.isAnimating&&f.firstRun)){if(t=parseInt(t,10),!(a=f.current?f.current.opts.loop:f.opts.loop)&&(t<0||t>=h))return!1;if(o=f.firstRun=!Object.keys(f.slides).length,r=f.current,f.prevIndex=f.currIndex,f.prevPos=f.currPos,s=f.createSlide(t),h>1&&((a||s.index<h-1)&&f.createSlide(t+1),(a||s.index>0)&&f.createSlide(t-1)),f.current=s,f.currIndex=s.index,f.currPos=s.pos,f.trigger("beforeShow",o),f.updateControls(),s.forcedDuration=void 0,n.isNumeric(e)?s.forcedDuration=e:e=s.opts[o?"animationDuration":"transitionDuration"],e=parseInt(e,10),i=f.isMoved(s),s.$slide.addClass("fancybox-slide--current"),o)return s.opts.animationEffect&&e&&f.$refs.container.css("transition-duration",e+"ms"),f.$refs.container.addClass("fancybox-is-open").trigger("focus"),f.loadSlide(s),void f.preload("image");c=n.fancybox.getTranslate(r.$slide),l=n.fancybox.getTranslate(f.$refs.stage),n.each(f.slides,function(t,e){n.fancybox.stop(e.$slide,!0)}),r.pos!==s.pos&&(r.isComplete=!1),r.$slide.removeClass("fancybox-slide--complete fancybox-slide--current"),i?(u=c.left-(r.pos*c.width+r.pos*r.opts.gutter),n.each(f.slides,function(t,o){o.$slide.removeClass("fancybox-animated").removeClass(function(t,e){return(e.match(/(^|\s)fancybox-fx-\S+/g)||[]).join(" ")});var i=o.pos*c.width+o.pos*o.opts.gutter;n.fancybox.setTranslate(o.$slide,{top:0,left:i-l.left+u}),o.pos!==s.pos&&o.$slide.addClass("fancybox-slide--"+(o.pos>s.pos?"next":"previous")),p(o.$slide),n.fancybox.animate(o.$slide,{top:0,left:(o.pos-s.pos)*c.width+(o.pos-s.pos)*o.opts.gutter},e,function(){o.$slide.css({transform:"",opacity:""}).removeClass("fancybox-slide--next fancybox-slide--previous"),o.pos===f.currPos&&f.complete()})})):e&&s.opts.transitionEffect&&(d="fancybox-animated fancybox-fx-"+s.opts.transitionEffect,r.$slide.addClass("fancybox-slide--"+(r.pos>s.pos?"next":"previous")),n.fancybox.animate(r.$slide,d,e,function(){r.$slide.removeClass(d).removeClass("fancybox-slide--next fancybox-slide--previous")},!1)),s.isLoaded?f.revealContent(s):f.loadSlide(s),f.preload("image")}},createSlide:function(t){var e,o,i=this;return o=t%i.group.length,o=o<0?i.group.length+o:o,!i.slides[t]&&i.group[o]&&(e=n('<div class="fancybox-slide"></div>').appendTo(i.$refs.stage),i.slides[t]=n.extend(!0,{},i.group[o],{pos:t,$slide:e,isLoaded:!1}),i.updateSlide(i.slides[t])),i.slides[t]},scaleToActual:function(t,e,o){var i,a,s,r,c,l=this,d=l.current,u=d.$content,f=n.fancybox.getTranslate(d.$slide).width,p=n.fancybox.getTranslate(d.$slide).height,h=d.width,g=d.height;l.isAnimating||l.isMoved()||!u||"image"!=d.type||!d.isLoaded||d.hasError||(l.isAnimating=!0,n.fancybox.stop(u),t=void 0===t?.5*f:t,e=void 0===e?.5*p:e,i=n.fancybox.getTranslate(u),i.top-=n.fancybox.getTranslate(d.$slide).top,i.left-=n.fancybox.getTranslate(d.$slide).left,r=h/i.width,c=g/i.height,a=.5*f-.5*h,s=.5*p-.5*g,h>f&&(a=i.left*r-(t*r-t),a>0&&(a=0),a<f-h&&(a=f-h)),g>p&&(s=i.top*c-(e*c-e),s>0&&(s=0),s<p-g&&(s=p-g)),l.updateCursor(h,g),n.fancybox.animate(u,{top:s,left:a,scaleX:r,scaleY:c},o||366,function(){l.isAnimating=!1}),l.SlideShow&&l.SlideShow.isActive&&l.SlideShow.stop())},scaleToFit:function(t){var e,o=this,i=o.current,a=i.$content;o.isAnimating||o.isMoved()||!a||"image"!=i.type||!i.isLoaded||i.hasError||(o.isAnimating=!0,n.fancybox.stop(a),e=o.getFitPos(i),o.updateCursor(e.width,e.height),n.fancybox.animate(a,{top:e.top,left:e.left,scaleX:e.width/a.width(),scaleY:e.height/a.height()},t||366,function(){o.isAnimating=!1}))},getFitPos:function(t){var e,o,i,a,s=this,r=t.$content,c=t.$slide,l=t.width||t.opts.width,d=t.height||t.opts.height,u={};return!!(t.isLoaded&&r&&r.length)&&(e=n.fancybox.getTranslate(s.$refs.stage).width,o=n.fancybox.getTranslate(s.$refs.stage).height,e-=parseFloat(c.css("paddingLeft"))+parseFloat(c.css("paddingRight"))+parseFloat(r.css("marginLeft"))+parseFloat(r.css("marginRight")),o-=parseFloat(c.css("paddingTop"))+parseFloat(c.css("paddingBottom"))+parseFloat(r.css("marginTop"))+parseFloat(r.css("marginBottom")),l&&d||(l=e,d=o),i=Math.min(1,e/l,o/d),l*=i,d*=i,l>e-.5&&(l=e),d>o-.5&&(d=o),"image"===t.type?(u.top=Math.floor(.5*(o-d))+parseFloat(c.css("paddingTop")),u.left=Math.floor(.5*(e-l))+parseFloat(c.css("paddingLeft"))):"video"===t.contentType&&(a=t.opts.width&&t.opts.height?l/d:t.opts.ratio||16/9,d>l/a?d=l/a:l>d*a&&(l=d*a)),u.width=l,u.height=d,u)},update:function(t){var e=this;n.each(e.slides,function(n,o){e.updateSlide(o,t)})},updateSlide:function(t,e){var o=this,i=t&&t.$content,a=t.width||t.opts.width,s=t.height||t.opts.height,r=t.$slide;o.adjustCaption(t),i&&(a||s||"video"===t.contentType)&&!t.hasError&&(n.fancybox.stop(i),n.fancybox.setTranslate(i,o.getFitPos(t)),t.pos===o.currPos&&(o.isAnimating=!1,o.updateCursor())),o.adjustLayout(t),r.length&&(r.trigger("refresh"),t.pos===o.currPos&&o.$refs.toolbar.add(o.$refs.navigation.find(".fancybox-button--arrow_right")).toggleClass("compensate-for-scrollbar",r.get(0).scrollHeight>r.get(0).clientHeight)),o.trigger("onUpdate",t,e)},centerSlide:function(t){var e=this,o=e.current,i=o.$slide;!e.isClosing&&o&&(i.siblings().css({transform:"",opacity:""}),i.parent().children().removeClass("fancybox-slide--previous fancybox-slide--next"),n.fancybox.animate(i,{top:0,left:0,opacity:1},void 0===t?0:t,function(){i.css({transform:"",opacity:""}),o.isComplete||e.complete()},!1))},isMoved:function(t){var e,o,i=t||this.current;return!!i&&(o=n.fancybox.getTranslate(this.$refs.stage),e=n.fancybox.getTranslate(i.$slide),!i.$slide.hasClass("fancybox-animated")&&(Math.abs(e.top-o.top)>.5||Math.abs(e.left-o.left)>.5))},updateCursor:function(t,e){var o,i,a=this,s=a.current,r=a.$refs.container;s&&!a.isClosing&&a.Guestures&&(r.removeClass("fancybox-is-zoomable fancybox-can-zoomIn fancybox-can-zoomOut fancybox-can-swipe fancybox-can-pan"),o=a.canPan(t,e),i=!!o||a.isZoomable(),r.toggleClass("fancybox-is-zoomable",i),n("[data-fancybox-zoom]").prop("disabled",!i),o?r.addClass("fancybox-can-pan"):i&&("zoom"===s.opts.clickContent||n.isFunction(s.opts.clickContent)&&"zoom"==s.opts.clickContent(s))?r.addClass("fancybox-can-zoomIn"):s.opts.touch&&(s.opts.touch.vertical||a.group.length>1)&&"video"!==s.contentType&&r.addClass("fancybox-can-swipe"))},isZoomable:function(){var t,e=this,n=e.current;if(n&&!e.isClosing&&"image"===n.type&&!n.hasError){if(!n.isLoaded)return!0;if((t=e.getFitPos(n))&&(n.width>t.width||n.height>t.height))return!0}return!1},isScaledDown:function(t,e){var o=this,i=!1,a=o.current,s=a.$content;return void 0!==t&&void 0!==e?i=t<a.width&&e<a.height:s&&(i=n.fancybox.getTranslate(s),i=i.width<a.width&&i.height<a.height),i},canPan:function(t,e){var o=this,i=o.current,a=null,s=!1;return"image"===i.type&&(i.isComplete||t&&e)&&!i.hasError&&(s=o.getFitPos(i),void 0!==t&&void 0!==e?a={width:t,height:e}:i.isComplete&&(a=n.fancybox.getTranslate(i.$content)),a&&s&&(s=Math.abs(a.width-s.width)>1.5||Math.abs(a.height-s.height)>1.5)),s},loadSlide:function(t){var e,o,i,a=this;if(!t.isLoading&&!t.isLoaded){if(t.isLoading=!0,!1===a.trigger("beforeLoad",t))return t.isLoading=!1,!1;switch(e=t.type,o=t.$slide,o.off("refresh").trigger("onReset").addClass(t.opts.slideClass),e){case"image":a.setImage(t);break;case"iframe":a.setIframe(t);break;case"html":a.setContent(t,t.src||t.content);break;case"video":a.setContent(t,t.opts.video.tpl.replace(/\{\{src\}\}/gi,t.src).replace("{{format}}",t.opts.videoFormat||t.opts.video.format||"").replace("{{poster}}",t.thumb||""));break;case"inline":n(t.src).length?a.setContent(t,n(t.src)):a.setError(t);break;case"ajax":a.showLoading(t),i=n.ajax(n.extend({},t.opts.ajax.settings,{url:t.src,success:function(e,n){"success"===n&&a.setContent(t,e)},error:function(e,n){e&&"abort"!==n&&a.setError(t)}})),o.one("onReset",function(){i.abort()});break;default:a.setError(t)}return!0}},setImage:function(t){var o,i=this;setTimeout(function(){var e=t.$image;i.isClosing||!t.isLoading||e&&e.length&&e[0].complete||t.hasError||i.showLoading(t)},50),i.checkSrcset(t),t.$content=n('<div class="fancybox-content"></div>').addClass("fancybox-is-hidden").appendTo(t.$slide.addClass("fancybox-slide--image")),!1!==t.opts.preload&&t.opts.width&&t.opts.height&&t.thumb&&(t.width=t.opts.width,t.height=t.opts.height,o=e.createElement("img"),o.onerror=function(){n(this).remove(),t.$ghost=null},o.onload=function(){i.afterLoad(t)},t.$ghost=n(o).addClass("fancybox-image").appendTo(t.$content).attr("src",t.thumb)),i.setBigImage(t)},checkSrcset:function(e){var n,o,i,a,s=e.opts.srcset||e.opts.image.srcset;if(s){i=t.devicePixelRatio||1,a=t.innerWidth*i,o=s.split(",").map(function(t){var e={};return t.trim().split(/\s+/).forEach(function(t,n){var o=parseInt(t.substring(0,t.length-1),10);if(0===n)return e.url=t;o&&(e.value=o,e.postfix=t[t.length-1])}),e}),o.sort(function(t,e){return t.value-e.value});for(var r=0;r<o.length;r++){var c=o[r];if("w"===c.postfix&&c.value>=a||"x"===c.postfix&&c.value>=i){n=c;break}}!n&&o.length&&(n=o[o.length-1]),n&&(e.src=n.url,e.width&&e.height&&"w"==n.postfix&&(e.height=e.width/e.height*n.value,e.width=n.value),e.opts.srcset=s)}},setBigImage:function(t){var o=this,i=e.createElement("img"),a=n(i);t.$image=a.one("error",function(){o.setError(t)}).one("load",function(){var e;t.$ghost||(o.resolveImageSlideSize(t,this.naturalWidth,this.naturalHeight),o.afterLoad(t)),o.isClosing||(t.opts.srcset&&(e=t.opts.sizes,e&&"auto"!==e||(e=(t.width/t.height>1&&s.width()/s.height()>1?"100":Math.round(t.width/t.height*100))+"vw"),a.attr("sizes",e).attr("srcset",t.opts.srcset)),t.$ghost&&setTimeout(function(){t.$ghost&&!o.isClosing&&t.$ghost.hide()},Math.min(300,Math.max(1e3,t.height/1600))),o.hideLoading(t))}).addClass("fancybox-image").attr("src",t.src).appendTo(t.$content),(i.complete||"complete"==i.readyState)&&a.naturalWidth&&a.naturalHeight?a.trigger("load"):i.error&&a.trigger("error")},resolveImageSlideSize:function(t,e,n){var o=parseInt(t.opts.width,10),i=parseInt(t.opts.height,10);t.width=e,t.height=n,o>0&&(t.width=o,t.height=Math.floor(o*n/e)),i>0&&(t.width=Math.floor(i*e/n),t.height=i)},setIframe:function(t){var e,o=this,i=t.opts.iframe,a=t.$slide;t.$content=n('<div class="fancybox-content'+(i.preload?" fancybox-is-hidden":"")+'"></div>').css(i.css).appendTo(a),a.addClass("fancybox-slide--"+t.contentType),t.$iframe=e=n(i.tpl.replace(/\{rnd\}/g,(new Date).getTime())).attr(i.attr).appendTo(t.$content),i.preload?(o.showLoading(t),e.on("load.fb error.fb",function(e){this.isReady=1,t.$slide.trigger("refresh"),o.afterLoad(t)}),a.on("refresh.fb",function(){var n,o,s=t.$content,r=i.css.width,c=i.css.height;if(1===e[0].isReady){try{n=e.contents(),o=n.find("body")}catch(t){}o&&o.length&&o.children().length&&(a.css("overflow","visible"),s.css({width:"100%","max-width":"100%",height:"9999px"}),void 0===r&&(r=Math.ceil(Math.max(o[0].clientWidth,o.outerWidth(!0)))),s.css("width",r||"").css("max-width",""),void 0===c&&(c=Math.ceil(Math.max(o[0].clientHeight,o.outerHeight(!0)))),s.css("height",c||""),a.css("overflow","auto")),s.removeClass("fancybox-is-hidden")}})):o.afterLoad(t),e.attr("src",t.src),a.one("onReset",function(){try{n(this).find("iframe").hide().unbind().attr("src","//about:blank")}catch(t){}n(this).off("refresh.fb").empty(),t.isLoaded=!1,t.isRevealed=!1})},setContent:function(t,e){var o=this;o.isClosing||(o.hideLoading(t),t.$content&&n.fancybox.stop(t.$content),t.$slide.empty(),l(e)&&e.parent().length?((e.hasClass("fancybox-content")||e.parent().hasClass("fancybox-content"))&&e.parents(".fancybox-slide").trigger("onReset"),t.$placeholder=n("<div>").hide().insertAfter(e),e.css("display","inline-block")):t.hasError||("string"===n.type(e)&&(e=n("<div>").append(n.trim(e)).contents()),t.opts.filter&&(e=n("<div>").html(e).find(t.opts.filter))),t.$slide.one("onReset",function(){n(this).find("video,audio").trigger("pause"),t.$placeholder&&(t.$placeholder.after(e.removeClass("fancybox-content").hide()).remove(),t.$placeholder=null),t.$smallBtn&&(t.$smallBtn.remove(),t.$smallBtn=null),t.hasError||(n(this).empty(),t.isLoaded=!1,t.isRevealed=!1)}),n(e).appendTo(t.$slide),n(e).is("video,audio")&&(n(e).addClass("fancybox-video"),n(e).wrap("<div></div>"),t.contentType="video",t.opts.width=t.opts.width||n(e).attr("width"),t.opts.height=t.opts.height||n(e).attr("height")),t.$content=t.$slide.children().filter("div,form,main,video,audio,article,.fancybox-content").first(),t.$content.siblings().hide(),t.$content.length||(t.$content=t.$slide.wrapInner("<div></div>").children().first()),t.$content.addClass("fancybox-content"),t.$slide.addClass("fancybox-slide--"+t.contentType),o.afterLoad(t))},setError:function(t){t.hasError=!0,t.$slide.trigger("onReset").removeClass("fancybox-slide--"+t.contentType).addClass("fancybox-slide--error"),t.contentType="html",this.setContent(t,this.translate(t,t.opts.errorTpl)),t.pos===this.currPos&&(this.isAnimating=!1)},showLoading:function(t){var e=this;(t=t||e.current)&&!t.$spinner&&(t.$spinner=n(e.translate(e,e.opts.spinnerTpl)).appendTo(t.$slide).hide().fadeIn("fast"))},hideLoading:function(t){var e=this;(t=t||e.current)&&t.$spinner&&(t.$spinner.stop().remove(),delete t.$spinner)},afterLoad:function(t){var e=this;e.isClosing||(t.isLoading=!1,t.isLoaded=!0,e.trigger("afterLoad",t),e.hideLoading(t),!t.opts.smallBtn||t.$smallBtn&&t.$smallBtn.length||(t.$smallBtn=n(e.translate(t,t.opts.btnTpl.smallBtn)).appendTo(t.$content)),t.opts.protect&&t.$content&&!t.hasError&&(t.$content.on("contextmenu.fb",function(t){return 2==t.button&&t.preventDefault(),!0}),"image"===t.type&&n('<div class="fancybox-spaceball"></div>').appendTo(t.$content)),e.adjustCaption(t),e.adjustLayout(t),t.pos===e.currPos&&e.updateCursor(),e.revealContent(t))},adjustCaption:function(t){var e,n=this,o=t||n.current,i=o.opts.caption,a=o.opts.preventCaptionOverlap,s=n.$refs.caption,r=!1;s.toggleClass("fancybox-caption--separate",a),a&&i&&i.length&&(o.pos!==n.currPos?(e=s.clone().appendTo(s.parent()),e.children().eq(0).empty().html(i),r=e.outerHeight(!0),e.empty().remove()):n.$caption&&(r=n.$caption.outerHeight(!0)),o.$slide.css("padding-bottom",r||""))},adjustLayout:function(t){var e,n,o,i,a=this,s=t||a.current;s.isLoaded&&!0!==s.opts.disableLayoutFix&&(s.$content.css("margin-bottom",""),s.$content.outerHeight()>s.$slide.height()+.5&&(o=s.$slide[0].style["padding-bottom"],i=s.$slide.css("padding-bottom"),parseFloat(i)>0&&(e=s.$slide[0].scrollHeight,s.$slide.css("padding-bottom",0),Math.abs(e-s.$slide[0].scrollHeight)<1&&(n=i),s.$slide.css("padding-bottom",o))),s.$content.css("margin-bottom",n))},revealContent:function(t){var e,o,i,a,s=this,r=t.$slide,c=!1,l=!1,d=s.isMoved(t),u=t.isRevealed;return t.isRevealed=!0,e=t.opts[s.firstRun?"animationEffect":"transitionEffect"],i=t.opts[s.firstRun?"animationDuration":"transitionDuration"],i=parseInt(void 0===t.forcedDuration?i:t.forcedDuration,10),!d&&t.pos===s.currPos&&i||(e=!1),"zoom"===e&&(t.pos===s.currPos&&i&&"image"===t.type&&!t.hasError&&(l=s.getThumbPos(t))?c=s.getFitPos(t):e="fade"),"zoom"===e?(s.isAnimating=!0,c.scaleX=c.width/l.width,c.scaleY=c.height/l.height,a=t.opts.zoomOpacity,"auto"==a&&(a=Math.abs(t.width/t.height-l.width/l.height)>.1),a&&(l.opacity=.1,c.opacity=1),n.fancybox.setTranslate(t.$content.removeClass("fancybox-is-hidden"),l),p(t.$content),void n.fancybox.animate(t.$content,c,i,function(){s.isAnimating=!1,s.complete()})):(s.updateSlide(t),e?(n.fancybox.stop(r),o="fancybox-slide--"+(t.pos>=s.prevPos?"next":"previous")+" fancybox-animated fancybox-fx-"+e,r.addClass(o).removeClass("fancybox-slide--current"),t.$content.removeClass("fancybox-is-hidden"),p(r),"image"!==t.type&&t.$content.hide().show(0),void n.fancybox.animate(r,"fancybox-slide--current",i,function(){r.removeClass(o).css({transform:"",opacity:""}),t.pos===s.currPos&&s.complete()},!0)):(t.$content.removeClass("fancybox-is-hidden"),u||!d||"image"!==t.type||t.hasError||t.$content.hide().fadeIn("fast"),void(t.pos===s.currPos&&s.complete())))},getThumbPos:function(t){var e,o,i,a,s,r=!1,c=t.$thumb;return!(!c||!g(c[0]))&&(e=n.fancybox.getTranslate(c),o=parseFloat(c.css("border-top-width")||0),i=parseFloat(c.css("border-right-width")||0),a=parseFloat(c.css("border-bottom-width")||0),s=parseFloat(c.css("border-left-width")||0),r={top:e.top+o,left:e.left+s,width:e.width-i-s,height:e.height-o-a,scaleX:1,scaleY:1},e.width>0&&e.height>0&&r)},complete:function(){var t,e=this,o=e.current,i={};!e.isMoved()&&o.isLoaded&&(o.isComplete||(o.isComplete=!0,o.$slide.siblings().trigger("onReset"),e.preload("inline"),p(o.$slide),o.$slide.addClass("fancybox-slide--complete"),n.each(e.slides,function(t,o){o.pos>=e.currPos-1&&o.pos<=e.currPos+1?i[o.pos]=o:o&&(n.fancybox.stop(o.$slide),o.$slide.off().remove())}),e.slides=i),e.isAnimating=!1,e.updateCursor(),e.trigger("afterShow"),o.opts.video.autoStart&&o.$slide.find("video,audio").filter(":visible:first").trigger("play").one("ended",function(){Document.exitFullscreen?Document.exitFullscreen():this.webkitExitFullscreen&&this.webkitExitFullscreen(),e.next()}),o.opts.autoFocus&&"html"===o.contentType&&(t=o.$content.find("input[autofocus]:enabled:visible:first"),t.length?t.trigger("focus"):e.focus(null,!0)),o.$slide.scrollTop(0).scrollLeft(0))},preload:function(t){var e,n,o=this;o.group.length<2||(n=o.slides[o.currPos+1],e=o.slides[o.currPos-1],e&&e.type===t&&o.loadSlide(e),n&&n.type===t&&o.loadSlide(n))},focus:function(t,o){var i,a,s=this,r=["a[href]","area[href]",'input:not([disabled]):not([type="hidden"]):not([aria-hidden])',"select:not([disabled]):not([aria-hidden])","textarea:not([disabled]):not([aria-hidden])","button:not([disabled]):not([aria-hidden])","iframe","object","embed","video","audio","[contenteditable]",'[tabindex]:not([tabindex^="-"])'].join(",");s.isClosing||(i=!t&&s.current&&s.current.isComplete?s.current.$slide.find("*:visible"+(o?":not(.fancybox-close-small)":"")):s.$refs.container.find("*:visible"),i=i.filter(r).filter(function(){return"hidden"!==n(this).css("visibility")&&!n(this).hasClass("disabled")}),i.length?(a=i.index(e.activeElement),t&&t.shiftKey?(a<0||0==a)&&(t.preventDefault(),i.eq(i.length-1).trigger("focus")):(a<0||a==i.length-1)&&(t&&t.preventDefault(),i.eq(0).trigger("focus"))):s.$refs.container.trigger("focus"))},activate:function(){var t=this;n(".fancybox-container").each(function(){var e=n(this).data("FancyBox");e&&e.id!==t.id&&!e.isClosing&&(e.trigger("onDeactivate"),e.removeEvents(),e.isVisible=!1)}),t.isVisible=!0,(t.current||t.isIdle)&&(t.update(),t.updateControls()),t.trigger("onActivate"),t.addEvents()},close:function(t,e){var o,i,a,s,r,c,l,u=this,f=u.current,h=function(){u.cleanUp(t)};return!u.isClosing&&(u.isClosing=!0,!1===u.trigger("beforeClose",t)?(u.isClosing=!1,d(function(){u.update()}),!1):(u.removeEvents(),a=f.$content,o=f.opts.animationEffect,i=n.isNumeric(e)?e:o?f.opts.animationDuration:0,f.$slide.removeClass("fancybox-slide--complete fancybox-slide--next fancybox-slide--previous fancybox-animated"),!0!==t?n.fancybox.stop(f.$slide):o=!1,f.$slide.siblings().trigger("onReset").remove(),i&&u.$refs.container.removeClass("fancybox-is-open").addClass("fancybox-is-closing").css("transition-duration",i+"ms"),u.hideLoading(f),u.hideControls(!0),u.updateCursor(),"zoom"!==o||a&&i&&"image"===f.type&&!u.isMoved()&&!f.hasError&&(l=u.getThumbPos(f))||(o="fade"),"zoom"===o?(n.fancybox.stop(a),s=n.fancybox.getTranslate(a),c={top:s.top,left:s.left,scaleX:s.width/l.width,scaleY:s.height/l.height,width:l.width,height:l.height},r=f.opts.zoomOpacity,
"auto"==r&&(r=Math.abs(f.width/f.height-l.width/l.height)>.1),r&&(l.opacity=0),n.fancybox.setTranslate(a,c),p(a),n.fancybox.animate(a,l,i,h),!0):(o&&i?n.fancybox.animate(f.$slide.addClass("fancybox-slide--previous").removeClass("fancybox-slide--current"),"fancybox-animated fancybox-fx-"+o,i,h):!0===t?setTimeout(h,i):h(),!0)))},cleanUp:function(e){var o,i,a,s=this,r=s.current.opts.$orig;s.current.$slide.trigger("onReset"),s.$refs.container.empty().remove(),s.trigger("afterClose",e),s.current.opts.backFocus&&(r&&r.length&&r.is(":visible")||(r=s.$trigger),r&&r.length&&(i=t.scrollX,a=t.scrollY,r.trigger("focus"),n("html, body").scrollTop(a).scrollLeft(i))),s.current=null,o=n.fancybox.getInstance(),o?o.activate():(n("body").removeClass("fancybox-active compensate-for-scrollbar"),n("#fancybox-style-noscroll").remove())},trigger:function(t,e){var o,i=Array.prototype.slice.call(arguments,1),a=this,s=e&&e.opts?e:a.current;if(s?i.unshift(s):s=a,i.unshift(a),n.isFunction(s.opts[t])&&(o=s.opts[t].apply(s,i)),!1===o)return o;"afterClose"!==t&&a.$refs?a.$refs.container.trigger(t+".fb",i):r.trigger(t+".fb",i)},updateControls:function(){var t=this,o=t.current,i=o.index,a=t.$refs.container,s=t.$refs.caption,r=o.opts.caption;o.$slide.trigger("refresh"),r&&r.length?(t.$caption=s,s.children().eq(0).html(r)):t.$caption=null,t.hasHiddenControls||t.isIdle||t.showControls(),a.find("[data-fancybox-count]").html(t.group.length),a.find("[data-fancybox-index]").html(i+1),a.find("[data-fancybox-prev]").prop("disabled",!o.opts.loop&&i<=0),a.find("[data-fancybox-next]").prop("disabled",!o.opts.loop&&i>=t.group.length-1),"image"===o.type?a.find("[data-fancybox-zoom]").show().end().find("[data-fancybox-download]").attr("href",o.opts.image.src||o.src).show():o.opts.toolbar&&a.find("[data-fancybox-download],[data-fancybox-zoom]").hide(),n(e.activeElement).is(":hidden,[disabled]")&&t.$refs.container.trigger("focus")},hideControls:function(t){var e=this,n=["infobar","toolbar","nav"];!t&&e.current.opts.preventCaptionOverlap||n.push("caption"),this.$refs.container.removeClass(n.map(function(t){return"fancybox-show-"+t}).join(" ")),this.hasHiddenControls=!0},showControls:function(){var t=this,e=t.current?t.current.opts:t.opts,n=t.$refs.container;t.hasHiddenControls=!1,t.idleSecondsCounter=0,n.toggleClass("fancybox-show-toolbar",!(!e.toolbar||!e.buttons)).toggleClass("fancybox-show-infobar",!!(e.infobar&&t.group.length>1)).toggleClass("fancybox-show-caption",!!t.$caption).toggleClass("fancybox-show-nav",!!(e.arrows&&t.group.length>1)).toggleClass("fancybox-is-modal",!!e.modal)},toggleControls:function(){this.hasHiddenControls?this.showControls():this.hideControls()}}),n.fancybox={version:"3.5.7",defaults:a,getInstance:function(t){var e=n('.fancybox-container:not(".fancybox-is-closing"):last').data("FancyBox"),o=Array.prototype.slice.call(arguments,1);return e instanceof b&&("string"===n.type(t)?e[t].apply(e,o):"function"===n.type(t)&&t.apply(e,o),e)},open:function(t,e,n){return new b(t,e,n)},close:function(t){var e=this.getInstance();e&&(e.close(),!0===t&&this.close(t))},destroy:function(){this.close(!0),r.add("body").off("click.fb-start","**")},isMobile:/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),use3d:function(){var n=e.createElement("div");return t.getComputedStyle&&t.getComputedStyle(n)&&t.getComputedStyle(n).getPropertyValue("transform")&&!(e.documentMode&&e.documentMode<11)}(),getTranslate:function(t){var e;return!(!t||!t.length)&&(e=t[0].getBoundingClientRect(),{top:e.top||0,left:e.left||0,width:e.width,height:e.height,opacity:parseFloat(t.css("opacity"))})},setTranslate:function(t,e){var n="",o={};if(t&&e)return void 0===e.left&&void 0===e.top||(n=(void 0===e.left?t.position().left:e.left)+"px, "+(void 0===e.top?t.position().top:e.top)+"px",n=this.use3d?"translate3d("+n+", 0px)":"translate("+n+")"),void 0!==e.scaleX&&void 0!==e.scaleY?n+=" scale("+e.scaleX+", "+e.scaleY+")":void 0!==e.scaleX&&(n+=" scaleX("+e.scaleX+")"),n.length&&(o.transform=n),void 0!==e.opacity&&(o.opacity=e.opacity),void 0!==e.width&&(o.width=e.width),void 0!==e.height&&(o.height=e.height),t.css(o)},animate:function(t,e,o,i,a){var s,r=this;n.isFunction(o)&&(i=o,o=null),r.stop(t),s=r.getTranslate(t),t.on(f,function(c){(!c||!c.originalEvent||t.is(c.originalEvent.target)&&"z-index"!=c.originalEvent.propertyName)&&(r.stop(t),n.isNumeric(o)&&t.css("transition-duration",""),n.isPlainObject(e)?void 0!==e.scaleX&&void 0!==e.scaleY&&r.setTranslate(t,{top:e.top,left:e.left,width:s.width*e.scaleX,height:s.height*e.scaleY,scaleX:1,scaleY:1}):!0!==a&&t.removeClass(e),n.isFunction(i)&&i(c))}),n.isNumeric(o)&&t.css("transition-duration",o+"ms"),n.isPlainObject(e)?(void 0!==e.scaleX&&void 0!==e.scaleY&&(delete e.width,delete e.height,t.parent().hasClass("fancybox-slide--image")&&t.parent().addClass("fancybox-is-scaling")),n.fancybox.setTranslate(t,e)):t.addClass(e),t.data("timer",setTimeout(function(){t.trigger(f)},o+33))},stop:function(t,e){t&&t.length&&(clearTimeout(t.data("timer")),e&&t.trigger(f),t.off(f).css("transition-duration",""),t.parent().removeClass("fancybox-is-scaling"))}},n.fn.fancybox=function(t){var e;return t=t||{},e=t.selector||!1,e?n("body").off("click.fb-start",e).on("click.fb-start",e,{options:t},i):this.off("click.fb-start").on("click.fb-start",{items:this,options:t},i),this},r.on("click.fb-start","[data-fancybox]",i),r.on("click.fb-start","[data-fancybox-trigger]",function(t){n('[data-fancybox="'+n(this).attr("data-fancybox-trigger")+'"]').eq(n(this).attr("data-fancybox-index")||0).trigger("click.fb-start",{$trigger:n(this)})}),function(){var t=null;r.on("mousedown mouseup focus blur",".fancybox-button",function(e){switch(e.type){case"mousedown":t=n(this);break;case"mouseup":t=null;break;case"focusin":n(".fancybox-button").removeClass("fancybox-focus"),n(this).is(t)||n(this).is("[disabled]")||n(this).addClass("fancybox-focus");break;case"focusout":n(".fancybox-button").removeClass("fancybox-focus")}})}()}}(window,document,jQuery),function(t){"use strict";var e={youtube:{matcher:/(youtube\.com|youtu\.be|youtube\-nocookie\.com)\/(watch\?(.*&)?v=|v\/|u\/|embed\/?)?(videoseries\?list=(.*)|[\w-]{11}|\?listType=(.*)&list=(.*))(.*)/i,params:{autoplay:1,autohide:1,fs:1,rel:0,hd:1,wmode:"transparent",enablejsapi:1,html5:1},paramPlace:8,type:"iframe",url:"https://www.youtube-nocookie.com/embed/$4",thumb:"https://img.youtube.com/vi/$4/hqdefault.jpg"},vimeo:{matcher:/^.+vimeo.com\/(.*\/)?([\d]+)(.*)?/,params:{autoplay:1,hd:1,show_title:1,show_byline:1,show_portrait:0,fullscreen:1},paramPlace:3,type:"iframe",url:"//player.vimeo.com/video/$2"},instagram:{matcher:/(instagr\.am|instagram\.com)\/p\/([a-zA-Z0-9_\-]+)\/?/i,type:"image",url:"//$1/p/$2/media/?size=l"},gmap_place:{matcher:/(maps\.)?google\.([a-z]{2,3}(\.[a-z]{2})?)\/(((maps\/(place\/(.*)\/)?\@(.*),(\d+.?\d+?)z))|(\?ll=))(.*)?/i,type:"iframe",url:function(t){return"//maps.google."+t[2]+"/?ll="+(t[9]?t[9]+"&z="+Math.floor(t[10])+(t[12]?t[12].replace(/^\//,"&"):""):t[12]+"").replace(/\?/,"&")+"&output="+(t[12]&&t[12].indexOf("layer=c")>0?"svembed":"embed")}},gmap_search:{matcher:/(maps\.)?google\.([a-z]{2,3}(\.[a-z]{2})?)\/(maps\/search\/)(.*)/i,type:"iframe",url:function(t){return"//maps.google."+t[2]+"/maps?q="+t[5].replace("query=","q=").replace("api=1","")+"&output=embed"}}},n=function(e,n,o){if(e)return o=o||"","object"===t.type(o)&&(o=t.param(o,!0)),t.each(n,function(t,n){e=e.replace("$"+t,n||"")}),o.length&&(e+=(e.indexOf("?")>0?"&":"?")+o),e};t(document).on("objectNeedsType.fb",function(o,i,a){var s,r,c,l,d,u,f,p=a.src||"",h=!1;s=t.extend(!0,{},e,a.opts.media),t.each(s,function(e,o){if(c=p.match(o.matcher)){if(h=o.type,f=e,u={},o.paramPlace&&c[o.paramPlace]){d=c[o.paramPlace],"?"==d[0]&&(d=d.substring(1)),d=d.split("&");for(var i=0;i<d.length;++i){var s=d[i].split("=",2);2==s.length&&(u[s[0]]=decodeURIComponent(s[1].replace(/\+/g," ")))}}return l=t.extend(!0,{},o.params,a.opts[e],u),p="function"===t.type(o.url)?o.url.call(this,c,l,a):n(o.url,c,l),r="function"===t.type(o.thumb)?o.thumb.call(this,c,l,a):n(o.thumb,c),"youtube"===e?p=p.replace(/&t=((\d+)m)?(\d+)s/,function(t,e,n,o){return"&start="+((n?60*parseInt(n,10):0)+parseInt(o,10))}):"vimeo"===e&&(p=p.replace("&%23","#")),!1}}),h?(a.opts.thumb||a.opts.$thumb&&a.opts.$thumb.length||(a.opts.thumb=r),"iframe"===h&&(a.opts=t.extend(!0,a.opts,{iframe:{preload:!1,attr:{scrolling:"no"}}})),t.extend(a,{type:h,src:p,origSrc:a.src,contentSource:f,contentType:"image"===h?"image":"gmap_place"==f||"gmap_search"==f?"map":"video"})):p&&(a.type=a.opts.defaultType)});var o={youtube:{src:"https://www.youtube.com/iframe_api",class:"YT",loading:!1,loaded:!1},vimeo:{src:"https://player.vimeo.com/api/player.js",class:"Vimeo",loading:!1,loaded:!1},load:function(t){var e,n=this;if(this[t].loaded)return void setTimeout(function(){n.done(t)});this[t].loading||(this[t].loading=!0,e=document.createElement("script"),e.type="text/javascript",e.src=this[t].src,"youtube"===t?window.onYouTubeIframeAPIReady=function(){n[t].loaded=!0,n.done(t)}:e.onload=function(){n[t].loaded=!0,n.done(t)},document.body.appendChild(e))},done:function(e){var n,o,i;"youtube"===e&&delete window.onYouTubeIframeAPIReady,(n=t.fancybox.getInstance())&&(o=n.current.$content.find("iframe"),"youtube"===e&&void 0!==YT&&YT?i=new YT.Player(o.attr("id"),{events:{onStateChange:function(t){0==t.data&&n.next()}}}):"vimeo"===e&&void 0!==Vimeo&&Vimeo&&(i=new Vimeo.Player(o),i.on("ended",function(){n.next()})))}};t(document).on({"afterShow.fb":function(t,e,n){e.group.length>1&&("youtube"===n.contentSource||"vimeo"===n.contentSource)&&o.load(n.contentSource)}})}(jQuery),function(t,e,n){"use strict";var o=function(){return t.requestAnimationFrame||t.webkitRequestAnimationFrame||t.mozRequestAnimationFrame||t.oRequestAnimationFrame||function(e){return t.setTimeout(e,1e3/60)}}(),i=function(){return t.cancelAnimationFrame||t.webkitCancelAnimationFrame||t.mozCancelAnimationFrame||t.oCancelAnimationFrame||function(e){t.clearTimeout(e)}}(),a=function(e){var n=[];e=e.originalEvent||e||t.e,e=e.touches&&e.touches.length?e.touches:e.changedTouches&&e.changedTouches.length?e.changedTouches:[e];for(var o in e)e[o].pageX?n.push({x:e[o].pageX,y:e[o].pageY}):e[o].clientX&&n.push({x:e[o].clientX,y:e[o].clientY});return n},s=function(t,e,n){return e&&t?"x"===n?t.x-e.x:"y"===n?t.y-e.y:Math.sqrt(Math.pow(t.x-e.x,2)+Math.pow(t.y-e.y,2)):0},r=function(t){if(t.is('a,area,button,[role="button"],input,label,select,summary,textarea,video,audio,iframe')||n.isFunction(t.get(0).onclick)||t.data("selectable"))return!0;for(var e=0,o=t[0].attributes,i=o.length;e<i;e++)if("data-fancybox-"===o[e].nodeName.substr(0,14))return!0;return!1},c=function(e){var n=t.getComputedStyle(e)["overflow-y"],o=t.getComputedStyle(e)["overflow-x"],i=("scroll"===n||"auto"===n)&&e.scrollHeight>e.clientHeight,a=("scroll"===o||"auto"===o)&&e.scrollWidth>e.clientWidth;return i||a},l=function(t){for(var e=!1;;){if(e=c(t.get(0)))break;if(t=t.parent(),!t.length||t.hasClass("fancybox-stage")||t.is("body"))break}return e},d=function(t){var e=this;e.instance=t,e.$bg=t.$refs.bg,e.$stage=t.$refs.stage,e.$container=t.$refs.container,e.destroy(),e.$container.on("touchstart.fb.touch mousedown.fb.touch",n.proxy(e,"ontouchstart"))};d.prototype.destroy=function(){var t=this;t.$container.off(".fb.touch"),n(e).off(".fb.touch"),t.requestId&&(i(t.requestId),t.requestId=null),t.tapped&&(clearTimeout(t.tapped),t.tapped=null)},d.prototype.ontouchstart=function(o){var i=this,c=n(o.target),d=i.instance,u=d.current,f=u.$slide,p=u.$content,h="touchstart"==o.type;if(h&&i.$container.off("mousedown.fb.touch"),(!o.originalEvent||2!=o.originalEvent.button)&&f.length&&c.length&&!r(c)&&!r(c.parent())&&(c.is("img")||!(o.originalEvent.clientX>c[0].clientWidth+c.offset().left))){if(!u||d.isAnimating||u.$slide.hasClass("fancybox-animated"))return o.stopPropagation(),void o.preventDefault();i.realPoints=i.startPoints=a(o),i.startPoints.length&&(u.touch&&o.stopPropagation(),i.startEvent=o,i.canTap=!0,i.$target=c,i.$content=p,i.opts=u.opts.touch,i.isPanning=!1,i.isSwiping=!1,i.isZooming=!1,i.isScrolling=!1,i.canPan=d.canPan(),i.startTime=(new Date).getTime(),i.distanceX=i.distanceY=i.distance=0,i.canvasWidth=Math.round(f[0].clientWidth),i.canvasHeight=Math.round(f[0].clientHeight),i.contentLastPos=null,i.contentStartPos=n.fancybox.getTranslate(i.$content)||{top:0,left:0},i.sliderStartPos=n.fancybox.getTranslate(f),i.stagePos=n.fancybox.getTranslate(d.$refs.stage),i.sliderStartPos.top-=i.stagePos.top,i.sliderStartPos.left-=i.stagePos.left,i.contentStartPos.top-=i.stagePos.top,i.contentStartPos.left-=i.stagePos.left,n(e).off(".fb.touch").on(h?"touchend.fb.touch touchcancel.fb.touch":"mouseup.fb.touch mouseleave.fb.touch",n.proxy(i,"ontouchend")).on(h?"touchmove.fb.touch":"mousemove.fb.touch",n.proxy(i,"ontouchmove")),n.fancybox.isMobile&&e.addEventListener("scroll",i.onscroll,!0),((i.opts||i.canPan)&&(c.is(i.$stage)||i.$stage.find(c).length)||(c.is(".fancybox-image")&&o.preventDefault(),n.fancybox.isMobile&&c.parents(".fancybox-caption").length))&&(i.isScrollable=l(c)||l(c.parent()),n.fancybox.isMobile&&i.isScrollable||o.preventDefault(),(1===i.startPoints.length||u.hasError)&&(i.canPan?(n.fancybox.stop(i.$content),i.isPanning=!0):i.isSwiping=!0,i.$container.addClass("fancybox-is-grabbing")),2===i.startPoints.length&&"image"===u.type&&(u.isLoaded||u.$ghost)&&(i.canTap=!1,i.isSwiping=!1,i.isPanning=!1,i.isZooming=!0,n.fancybox.stop(i.$content),i.centerPointStartX=.5*(i.startPoints[0].x+i.startPoints[1].x)-n(t).scrollLeft(),i.centerPointStartY=.5*(i.startPoints[0].y+i.startPoints[1].y)-n(t).scrollTop(),i.percentageOfImageAtPinchPointX=(i.centerPointStartX-i.contentStartPos.left)/i.contentStartPos.width,i.percentageOfImageAtPinchPointY=(i.centerPointStartY-i.contentStartPos.top)/i.contentStartPos.height,i.startDistanceBetweenFingers=s(i.startPoints[0],i.startPoints[1]))))}},d.prototype.onscroll=function(t){var n=this;n.isScrolling=!0,e.removeEventListener("scroll",n.onscroll,!0)},d.prototype.ontouchmove=function(t){var e=this;return void 0!==t.originalEvent.buttons&&0===t.originalEvent.buttons?void e.ontouchend(t):e.isScrolling?void(e.canTap=!1):(e.newPoints=a(t),void((e.opts||e.canPan)&&e.newPoints.length&&e.newPoints.length&&(e.isSwiping&&!0===e.isSwiping||t.preventDefault(),e.distanceX=s(e.newPoints[0],e.startPoints[0],"x"),e.distanceY=s(e.newPoints[0],e.startPoints[0],"y"),e.distance=s(e.newPoints[0],e.startPoints[0]),e.distance>0&&(e.isSwiping?e.onSwipe(t):e.isPanning?e.onPan():e.isZooming&&e.onZoom()))))},d.prototype.onSwipe=function(e){var a,s=this,r=s.instance,c=s.isSwiping,l=s.sliderStartPos.left||0;if(!0!==c)"x"==c&&(s.distanceX>0&&(s.instance.group.length<2||0===s.instance.current.index&&!s.instance.current.opts.loop)?l+=Math.pow(s.distanceX,.8):s.distanceX<0&&(s.instance.group.length<2||s.instance.current.index===s.instance.group.length-1&&!s.instance.current.opts.loop)?l-=Math.pow(-s.distanceX,.8):l+=s.distanceX),s.sliderLastPos={top:"x"==c?0:s.sliderStartPos.top+s.distanceY,left:l},s.requestId&&(i(s.requestId),s.requestId=null),s.requestId=o(function(){s.sliderLastPos&&(n.each(s.instance.slides,function(t,e){var o=e.pos-s.instance.currPos;n.fancybox.setTranslate(e.$slide,{top:s.sliderLastPos.top,left:s.sliderLastPos.left+o*s.canvasWidth+o*e.opts.gutter})}),s.$container.addClass("fancybox-is-sliding"))});else if(Math.abs(s.distance)>10){if(s.canTap=!1,r.group.length<2&&s.opts.vertical?s.isSwiping="y":r.isDragging||!1===s.opts.vertical||"auto"===s.opts.vertical&&n(t).width()>800?s.isSwiping="x":(a=Math.abs(180*Math.atan2(s.distanceY,s.distanceX)/Math.PI),s.isSwiping=a>45&&a<135?"y":"x"),"y"===s.isSwiping&&n.fancybox.isMobile&&s.isScrollable)return void(s.isScrolling=!0);r.isDragging=s.isSwiping,s.startPoints=s.newPoints,n.each(r.slides,function(t,e){var o,i;n.fancybox.stop(e.$slide),o=n.fancybox.getTranslate(e.$slide),i=n.fancybox.getTranslate(r.$refs.stage),e.$slide.css({transform:"",opacity:"","transition-duration":""}).removeClass("fancybox-animated").removeClass(function(t,e){return(e.match(/(^|\s)fancybox-fx-\S+/g)||[]).join(" ")}),e.pos===r.current.pos&&(s.sliderStartPos.top=o.top-i.top,s.sliderStartPos.left=o.left-i.left),n.fancybox.setTranslate(e.$slide,{top:o.top-i.top,left:o.left-i.left})}),r.SlideShow&&r.SlideShow.isActive&&r.SlideShow.stop()}},d.prototype.onPan=function(){var t=this;if(s(t.newPoints[0],t.realPoints[0])<(n.fancybox.isMobile?10:5))return void(t.startPoints=t.newPoints);t.canTap=!1,t.contentLastPos=t.limitMovement(),t.requestId&&i(t.requestId),t.requestId=o(function(){n.fancybox.setTranslate(t.$content,t.contentLastPos)})},d.prototype.limitMovement=function(){var t,e,n,o,i,a,s=this,r=s.canvasWidth,c=s.canvasHeight,l=s.distanceX,d=s.distanceY,u=s.contentStartPos,f=u.left,p=u.top,h=u.width,g=u.height;return i=h>r?f+l:f,a=p+d,t=Math.max(0,.5*r-.5*h),e=Math.max(0,.5*c-.5*g),n=Math.min(r-h,.5*r-.5*h),o=Math.min(c-g,.5*c-.5*g),l>0&&i>t&&(i=t-1+Math.pow(-t+f+l,.8)||0),l<0&&i<n&&(i=n+1-Math.pow(n-f-l,.8)||0),d>0&&a>e&&(a=e-1+Math.pow(-e+p+d,.8)||0),d<0&&a<o&&(a=o+1-Math.pow(o-p-d,.8)||0),{top:a,left:i}},d.prototype.limitPosition=function(t,e,n,o){var i=this,a=i.canvasWidth,s=i.canvasHeight;return n>a?(t=t>0?0:t,t=t<a-n?a-n:t):t=Math.max(0,a/2-n/2),o>s?(e=e>0?0:e,e=e<s-o?s-o:e):e=Math.max(0,s/2-o/2),{top:e,left:t}},d.prototype.onZoom=function(){var e=this,a=e.contentStartPos,r=a.width,c=a.height,l=a.left,d=a.top,u=s(e.newPoints[0],e.newPoints[1]),f=u/e.startDistanceBetweenFingers,p=Math.floor(r*f),h=Math.floor(c*f),g=(r-p)*e.percentageOfImageAtPinchPointX,b=(c-h)*e.percentageOfImageAtPinchPointY,m=(e.newPoints[0].x+e.newPoints[1].x)/2-n(t).scrollLeft(),v=(e.newPoints[0].y+e.newPoints[1].y)/2-n(t).scrollTop(),y=m-e.centerPointStartX,x=v-e.centerPointStartY,w=l+(g+y),$=d+(b+x),S={top:$,left:w,scaleX:f,scaleY:f};e.canTap=!1,e.newWidth=p,e.newHeight=h,e.contentLastPos=S,e.requestId&&i(e.requestId),e.requestId=o(function(){n.fancybox.setTranslate(e.$content,e.contentLastPos)})},d.prototype.ontouchend=function(t){var o=this,s=o.isSwiping,r=o.isPanning,c=o.isZooming,l=o.isScrolling;if(o.endPoints=a(t),o.dMs=Math.max((new Date).getTime()-o.startTime,1),o.$container.removeClass("fancybox-is-grabbing"),n(e).off(".fb.touch"),e.removeEventListener("scroll",o.onscroll,!0),o.requestId&&(i(o.requestId),o.requestId=null),o.isSwiping=!1,o.isPanning=!1,o.isZooming=!1,o.isScrolling=!1,o.instance.isDragging=!1,o.canTap)return o.onTap(t);o.speed=100,o.velocityX=o.distanceX/o.dMs*.5,o.velocityY=o.distanceY/o.dMs*.5,r?o.endPanning():c?o.endZooming():o.endSwiping(s,l)},d.prototype.endSwiping=function(t,e){var o=this,i=!1,a=o.instance.group.length,s=Math.abs(o.distanceX),r="x"==t&&a>1&&(o.dMs>130&&s>10||s>50);o.sliderLastPos=null,"y"==t&&!e&&Math.abs(o.distanceY)>50?(n.fancybox.animate(o.instance.current.$slide,{top:o.sliderStartPos.top+o.distanceY+150*o.velocityY,opacity:0},200),i=o.instance.close(!0,250)):r&&o.distanceX>0?i=o.instance.previous(300):r&&o.distanceX<0&&(i=o.instance.next(300)),!1!==i||"x"!=t&&"y"!=t||o.instance.centerSlide(200),o.$container.removeClass("fancybox-is-sliding")},d.prototype.endPanning=function(){var t,e,o,i=this;i.contentLastPos&&(!1===i.opts.momentum||i.dMs>350?(t=i.contentLastPos.left,e=i.contentLastPos.top):(t=i.contentLastPos.left+500*i.velocityX,e=i.contentLastPos.top+500*i.velocityY),o=i.limitPosition(t,e,i.contentStartPos.width,i.contentStartPos.height),o.width=i.contentStartPos.width,o.height=i.contentStartPos.height,n.fancybox.animate(i.$content,o,366))},d.prototype.endZooming=function(){var t,e,o,i,a=this,s=a.instance.current,r=a.newWidth,c=a.newHeight;a.contentLastPos&&(t=a.contentLastPos.left,e=a.contentLastPos.top,i={top:e,left:t,width:r,height:c,scaleX:1,scaleY:1},n.fancybox.setTranslate(a.$content,i),r<a.canvasWidth&&c<a.canvasHeight?a.instance.scaleToFit(150):r>s.width||c>s.height?a.instance.scaleToActual(a.centerPointStartX,a.centerPointStartY,150):(o=a.limitPosition(t,e,r,c),n.fancybox.animate(a.$content,o,150)))},d.prototype.onTap=function(e){var o,i=this,s=n(e.target),r=i.instance,c=r.current,l=e&&a(e)||i.startPoints,d=l[0]?l[0].x-n(t).scrollLeft()-i.stagePos.left:0,u=l[0]?l[0].y-n(t).scrollTop()-i.stagePos.top:0,f=function(t){var o=c.opts[t];if(n.isFunction(o)&&(o=o.apply(r,[c,e])),o)switch(o){case"close":r.close(i.startEvent);break;case"toggleControls":r.toggleControls();break;case"next":r.next();break;case"nextOrClose":r.group.length>1?r.next():r.close(i.startEvent);break;case"zoom":"image"==c.type&&(c.isLoaded||c.$ghost)&&(r.canPan()?r.scaleToFit():r.isScaledDown()?r.scaleToActual(d,u):r.group.length<2&&r.close(i.startEvent))}};if((!e.originalEvent||2!=e.originalEvent.button)&&(s.is("img")||!(d>s[0].clientWidth+s.offset().left))){if(s.is(".fancybox-bg,.fancybox-inner,.fancybox-outer,.fancybox-container"))o="Outside";else if(s.is(".fancybox-slide"))o="Slide";else{if(!r.current.$content||!r.current.$content.find(s).addBack().filter(s).length)return;o="Content"}if(i.tapped){if(clearTimeout(i.tapped),i.tapped=null,Math.abs(d-i.tapX)>50||Math.abs(u-i.tapY)>50)return this;f("dblclick"+o)}else i.tapX=d,i.tapY=u,c.opts["dblclick"+o]&&c.opts["dblclick"+o]!==c.opts["click"+o]?i.tapped=setTimeout(function(){i.tapped=null,r.isAnimating||f("click"+o)},500):f("click"+o);return this}},n(e).on("onActivate.fb",function(t,e){e&&!e.Guestures&&(e.Guestures=new d(e))}).on("beforeClose.fb",function(t,e){e&&e.Guestures&&e.Guestures.destroy()})}(window,document,jQuery),function(t,e){"use strict";e.extend(!0,e.fancybox.defaults,{btnTpl:{slideShow:'<button data-fancybox-play class="fancybox-button fancybox-button--play" title="{{PLAY_START}}"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M6.5 5.4v13.2l11-6.6z"/></svg><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M8.33 5.75h2.2v12.5h-2.2V5.75zm5.15 0h2.2v12.5h-2.2V5.75z"/></svg></button>'},slideShow:{autoStart:!1,speed:3e3,progress:!0}});var n=function(t){this.instance=t,this.init()};e.extend(n.prototype,{timer:null,isActive:!1,$button:null,init:function(){var t=this,n=t.instance,o=n.group[n.currIndex].opts.slideShow;t.$button=n.$refs.toolbar.find("[data-fancybox-play]").on("click",function(){t.toggle()}),n.group.length<2||!o?t.$button.hide():o.progress&&(t.$progress=e('<div class="fancybox-progress"></div>').appendTo(n.$refs.inner))},set:function(t){var n=this,o=n.instance,i=o.current;i&&(!0===t||i.opts.loop||o.currIndex<o.group.length-1)?n.isActive&&"video"!==i.contentType&&(n.$progress&&e.fancybox.animate(n.$progress.show(),{scaleX:1},i.opts.slideShow.speed),n.timer=setTimeout(function(){o.current.opts.loop||o.current.index!=o.group.length-1?o.next():o.jumpTo(0)},i.opts.slideShow.speed)):(n.stop(),o.idleSecondsCounter=0,o.showControls())},clear:function(){var t=this;clearTimeout(t.timer),t.timer=null,t.$progress&&t.$progress.removeAttr("style").hide()},start:function(){var t=this,e=t.instance.current;e&&(t.$button.attr("title",(e.opts.i18n[e.opts.lang]||e.opts.i18n.en).PLAY_STOP).removeClass("fancybox-button--play").addClass("fancybox-button--pause"),t.isActive=!0,e.isComplete&&t.set(!0),t.instance.trigger("onSlideShowChange",!0))},stop:function(){var t=this,e=t.instance.current;t.clear(),t.$button.attr("title",(e.opts.i18n[e.opts.lang]||e.opts.i18n.en).PLAY_START).removeClass("fancybox-button--pause").addClass("fancybox-button--play"),t.isActive=!1,t.instance.trigger("onSlideShowChange",!1),t.$progress&&t.$progress.removeAttr("style").hide()},toggle:function(){var t=this;t.isActive?t.stop():t.start()}}),e(t).on({"onInit.fb":function(t,e){e&&!e.SlideShow&&(e.SlideShow=new n(e))},"beforeShow.fb":function(t,e,n,o){var i=e&&e.SlideShow;o?i&&n.opts.slideShow.autoStart&&i.start():i&&i.isActive&&i.clear()},"afterShow.fb":function(t,e,n){var o=e&&e.SlideShow;o&&o.isActive&&o.set()},"afterKeydown.fb":function(n,o,i,a,s){var r=o&&o.SlideShow;!r||!i.opts.slideShow||80!==s&&32!==s||e(t.activeElement).is("button,a,input")||(a.preventDefault(),r.toggle())},"beforeClose.fb onDeactivate.fb":function(t,e){var n=e&&e.SlideShow;n&&n.stop()}}),e(t).on("visibilitychange",function(){var n=e.fancybox.getInstance(),o=n&&n.SlideShow;o&&o.isActive&&(t.hidden?o.clear():o.set())})}(document,jQuery),function(t,e){"use strict";var n=function(){for(var e=[["requestFullscreen","exitFullscreen","fullscreenElement","fullscreenEnabled","fullscreenchange","fullscreenerror"],["webkitRequestFullscreen","webkitExitFullscreen","webkitFullscreenElement","webkitFullscreenEnabled","webkitfullscreenchange","webkitfullscreenerror"],["webkitRequestFullScreen","webkitCancelFullScreen","webkitCurrentFullScreenElement","webkitCancelFullScreen","webkitfullscreenchange","webkitfullscreenerror"],["mozRequestFullScreen","mozCancelFullScreen","mozFullScreenElement","mozFullScreenEnabled","mozfullscreenchange","mozfullscreenerror"],["msRequestFullscreen","msExitFullscreen","msFullscreenElement","msFullscreenEnabled","MSFullscreenChange","MSFullscreenError"]],n={},o=0;o<e.length;o++){var i=e[o];if(i&&i[1]in t){for(var a=0;a<i.length;a++)n[e[0][a]]=i[a];return n}}return!1}();if(n){var o={request:function(e){e=e||t.documentElement,e[n.requestFullscreen](e.ALLOW_KEYBOARD_INPUT)},exit:function(){t[n.exitFullscreen]()},toggle:function(e){e=e||t.documentElement,this.isFullscreen()?this.exit():this.request(e)},isFullscreen:function(){return Boolean(t[n.fullscreenElement])},enabled:function(){return Boolean(t[n.fullscreenEnabled])}};e.extend(!0,e.fancybox.defaults,{btnTpl:{fullScreen:'<button data-fancybox-fullscreen class="fancybox-button fancybox-button--fsenter" title="{{FULL_SCREEN}}"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/></svg><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5 16h3v3h2v-5H5zm3-8H5v2h5V5H8zm6 11h2v-3h3v-2h-5zm2-11V5h-2v5h5V8z"/></svg></button>'},fullScreen:{autoStart:!1}}),e(t).on(n.fullscreenchange,function(){var t=o.isFullscreen(),n=e.fancybox.getInstance();n&&(n.current&&"image"===n.current.type&&n.isAnimating&&(n.isAnimating=!1,n.update(!0,!0,0),n.isComplete||n.complete()),n.trigger("onFullscreenChange",t),n.$refs.container.toggleClass("fancybox-is-fullscreen",t),n.$refs.toolbar.find("[data-fancybox-fullscreen]").toggleClass("fancybox-button--fsenter",!t).toggleClass("fancybox-button--fsexit",t))})}e(t).on({"onInit.fb":function(t,e){var i;if(!n)return void e.$refs.toolbar.find("[data-fancybox-fullscreen]").remove();e&&e.group[e.currIndex].opts.fullScreen?(i=e.$refs.container,i.on("click.fb-fullscreen","[data-fancybox-fullscreen]",function(t){t.stopPropagation(),t.preventDefault(),o.toggle()}),e.opts.fullScreen&&!0===e.opts.fullScreen.autoStart&&o.request(),e.FullScreen=o):e&&e.$refs.toolbar.find("[data-fancybox-fullscreen]").hide()},"afterKeydown.fb":function(t,e,n,o,i){e&&e.FullScreen&&70===i&&(o.preventDefault(),e.FullScreen.toggle())},"beforeClose.fb":function(t,e){e&&e.FullScreen&&e.$refs.container.hasClass("fancybox-is-fullscreen")&&o.exit()}})}(document,jQuery),function(t,e){"use strict";var n="fancybox-thumbs";e.fancybox.defaults=e.extend(!0,{btnTpl:{thumbs:'<button data-fancybox-thumbs class="fancybox-button fancybox-button--thumbs" title="{{THUMBS}}"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M14.59 14.59h3.76v3.76h-3.76v-3.76zm-4.47 0h3.76v3.76h-3.76v-3.76zm-4.47 0h3.76v3.76H5.65v-3.76zm8.94-4.47h3.76v3.76h-3.76v-3.76zm-4.47 0h3.76v3.76h-3.76v-3.76zm-4.47 0h3.76v3.76H5.65v-3.76zm8.94-4.47h3.76v3.76h-3.76V5.65zm-4.47 0h3.76v3.76h-3.76V5.65zm-4.47 0h3.76v3.76H5.65V5.65z"/></svg></button>'},thumbs:{autoStart:!1,hideOnClose:!0,parentEl:".fancybox-container",axis:"y"}},e.fancybox.defaults);var o=function(t){this.init(t)};e.extend(o.prototype,{$button:null,$grid:null,$list:null,isVisible:!1,isActive:!1,init:function(t){var e=this,n=t.group,o=0;e.instance=t,e.opts=n[t.currIndex].opts.thumbs,t.Thumbs=e,e.$button=t.$refs.toolbar.find("[data-fancybox-thumbs]");for(var i=0,a=n.length;i<a&&(n[i].thumb&&o++,!(o>1));i++);o>1&&e.opts?(e.$button.removeAttr("style").on("click",function(){e.toggle()}),e.isActive=!0):e.$button.hide()},create:function(){var t,o=this,i=o.instance,a=o.opts.parentEl,s=[];o.$grid||(o.$grid=e('<div class="'+n+" "+n+"-"+o.opts.axis+'"></div>').appendTo(i.$refs.container.find(a).addBack().filter(a)),o.$grid.on("click","a",function(){i.jumpTo(e(this).attr("data-index"))})),o.$list||(o.$list=e('<div class="'+n+'__list">').appendTo(o.$grid)),e.each(i.group,function(e,n){t=n.thumb,t||"image"!==n.type||(t=n.src),s.push('<a href="javascript:;" tabindex="0" data-index="'+e+'"'+(t&&t.length?' style="background-image:url('+t+')"':'class="fancybox-thumbs-missing"')+"></a>")}),o.$list[0].innerHTML=s.join(""),"x"===o.opts.axis&&o.$list.width(parseInt(o.$grid.css("padding-right"),10)+i.group.length*o.$list.children().eq(0).outerWidth(!0))},focus:function(t){var e,n,o=this,i=o.$list,a=o.$grid;o.instance.current&&(e=i.children().removeClass("fancybox-thumbs-active").filter('[data-index="'+o.instance.current.index+'"]').addClass("fancybox-thumbs-active"),n=e.position(),"y"===o.opts.axis&&(n.top<0||n.top>i.height()-e.outerHeight())?i.stop().animate({scrollTop:i.scrollTop()+n.top},t):"x"===o.opts.axis&&(n.left<a.scrollLeft()||n.left>a.scrollLeft()+(a.width()-e.outerWidth()))&&i.parent().stop().animate({scrollLeft:n.left},t))},update:function(){var t=this;t.instance.$refs.container.toggleClass("fancybox-show-thumbs",this.isVisible),t.isVisible?(t.$grid||t.create(),t.instance.trigger("onThumbsShow"),t.focus(0)):t.$grid&&t.instance.trigger("onThumbsHide"),t.instance.update()},hide:function(){this.isVisible=!1,this.update()},show:function(){this.isVisible=!0,this.update()},toggle:function(){this.isVisible=!this.isVisible,this.update()}}),e(t).on({"onInit.fb":function(t,e){var n;e&&!e.Thumbs&&(n=new o(e),n.isActive&&!0===n.opts.autoStart&&n.show())},"beforeShow.fb":function(t,e,n,o){var i=e&&e.Thumbs;i&&i.isVisible&&i.focus(o?0:250)},"afterKeydown.fb":function(t,e,n,o,i){var a=e&&e.Thumbs;a&&a.isActive&&71===i&&(o.preventDefault(),a.toggle())},"beforeClose.fb":function(t,e){var n=e&&e.Thumbs;n&&n.isVisible&&!1!==n.opts.hideOnClose&&n.$grid.hide()}})}(document,jQuery),function(t,e){"use strict";function n(t){var e={"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;","/":"&#x2F;","`":"&#x60;","=":"&#x3D;"};return String(t).replace(/[&<>"'`=\/]/g,function(t){return e[t]})}e.extend(!0,e.fancybox.defaults,{btnTpl:{share:'<button data-fancybox-share class="fancybox-button fancybox-button--share" title="{{SHARE}}"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M2.55 19c1.4-8.4 9.1-9.8 11.9-9.8V5l7 7-7 6.3v-3.5c-2.8 0-10.5 2.1-11.9 4.2z"/></svg></button>'},share:{url:function(t,e){return!t.currentHash&&"inline"!==e.type&&"html"!==e.type&&(e.origSrc||e.src)||window.location},
tpl:'<div class="fancybox-share"><h1>{{SHARE}}</h1><p><a class="fancybox-share__button fancybox-share__button--fb" href="https://www.facebook.com/sharer/sharer.php?u={{url}}"><svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="m287 456v-299c0-21 6-35 35-35h38v-63c-7-1-29-3-55-3-54 0-91 33-91 94v306m143-254h-205v72h196" /></svg><span>Facebook</span></a><a class="fancybox-share__button fancybox-share__button--tw" href="https://twitter.com/intent/tweet?url={{url}}&text={{descr}}"><svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="m456 133c-14 7-31 11-47 13 17-10 30-27 37-46-15 10-34 16-52 20-61-62-157-7-141 75-68-3-129-35-169-85-22 37-11 86 26 109-13 0-26-4-37-9 0 39 28 72 65 80-12 3-25 4-37 2 10 33 41 57 77 57-42 30-77 38-122 34 170 111 378-32 359-208 16-11 30-25 41-42z" /></svg><span>Twitter</span></a><a class="fancybox-share__button fancybox-share__button--pt" href="https://www.pinterest.com/pin/create/button/?url={{url}}&description={{descr}}&media={{media}}"><svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="m265 56c-109 0-164 78-164 144 0 39 15 74 47 87 5 2 10 0 12-5l4-19c2-6 1-8-3-13-9-11-15-25-15-45 0-58 43-110 113-110 62 0 96 38 96 88 0 67-30 122-73 122-24 0-42-19-36-44 6-29 20-60 20-81 0-19-10-35-31-35-25 0-44 26-44 60 0 21 7 36 7 36l-30 125c-8 37-1 83 0 87 0 3 4 4 5 2 2-3 32-39 42-75l16-64c8 16 31 29 56 29 74 0 124-67 124-157 0-69-58-132-146-132z" fill="#fff"/></svg><span>Pinterest</span></a></p><p><input class="fancybox-share__input" type="text" value="{{url_raw}}" onclick="select()" /></p></div>'}}),e(t).on("click","[data-fancybox-share]",function(){var t,o,i=e.fancybox.getInstance(),a=i.current||null;a&&("function"===e.type(a.opts.share.url)&&(t=a.opts.share.url.apply(a,[i,a])),o=a.opts.share.tpl.replace(/\{\{media\}\}/g,"image"===a.type?encodeURIComponent(a.src):"").replace(/\{\{url\}\}/g,encodeURIComponent(t)).replace(/\{\{url_raw\}\}/g,n(t)).replace(/\{\{descr\}\}/g,i.$caption?encodeURIComponent(i.$caption.text()):""),e.fancybox.open({src:i.translate(i,o),type:"html",opts:{touch:!1,animationEffect:!1,afterLoad:function(t,e){i.$refs.container.one("beforeClose.fb",function(){t.close(null,0)}),e.$content.find(".fancybox-share__button").click(function(){return window.open(this.href,"Share","width=550, height=450"),!1})},mobile:{autoFocus:!1}}}))})}(document,jQuery),function(t,e,n){"use strict";function o(){var e=t.location.hash.substr(1),n=e.split("-"),o=n.length>1&&/^\+?\d+$/.test(n[n.length-1])?parseInt(n.pop(-1),10)||1:1,i=n.join("-");return{hash:e,index:o<1?1:o,gallery:i}}function i(t){""!==t.gallery&&n("[data-fancybox='"+n.escapeSelector(t.gallery)+"']").eq(t.index-1).focus().trigger("click.fb-start")}function a(t){var e,n;return!!t&&(e=t.current?t.current.opts:t.opts,""!==(n=e.hash||(e.$orig?e.$orig.data("fancybox")||e.$orig.data("fancybox-trigger"):""))&&n)}n.escapeSelector||(n.escapeSelector=function(t){return(t+"").replace(/([\0-\x1f\x7f]|^-?\d)|^-$|[^\x80-\uFFFF\w-]/g,function(t,e){return e?"\0"===t?"�":t.slice(0,-1)+"\\"+t.charCodeAt(t.length-1).toString(16)+" ":"\\"+t})}),n(function(){!1!==n.fancybox.defaults.hash&&(n(e).on({"onInit.fb":function(t,e){var n,i;!1!==e.group[e.currIndex].opts.hash&&(n=o(),(i=a(e))&&n.gallery&&i==n.gallery&&(e.currIndex=n.index-1))},"beforeShow.fb":function(n,o,i,s){var r;i&&!1!==i.opts.hash&&(r=a(o))&&(o.currentHash=r+(o.group.length>1?"-"+(i.index+1):""),t.location.hash!=="#"+o.currentHash&&(s&&!o.origHash&&(o.origHash=t.location.hash),o.hashTimer&&clearTimeout(o.hashTimer),o.hashTimer=setTimeout(function(){"replaceState"in t.history?(t.history[s?"pushState":"replaceState"]({},e.title,t.location.pathname+t.location.search+"#"+o.currentHash),s&&(o.hasCreatedHistory=!0)):t.location.hash=o.currentHash,o.hashTimer=null},300)))},"beforeClose.fb":function(n,o,i){i&&!1!==i.opts.hash&&(clearTimeout(o.hashTimer),o.currentHash&&o.hasCreatedHistory?t.history.back():o.currentHash&&("replaceState"in t.history?t.history.replaceState({},e.title,t.location.pathname+t.location.search+(o.origHash||"")):t.location.hash=o.origHash),o.currentHash=null)}}),n(t).on("hashchange.fb",function(){var t=o(),e=null;n.each(n(".fancybox-container").get().reverse(),function(t,o){var i=n(o).data("FancyBox");if(i&&i.currentHash)return e=i,!1}),e?e.currentHash===t.gallery+"-"+t.index||1===t.index&&e.currentHash==t.gallery||(e.currentHash=null,e.close()):""!==t.gallery&&i(t)}),setTimeout(function(){n.fancybox.getInstance()||i(o())},50))})}(window,document,jQuery),function(t,e){"use strict";var n=(new Date).getTime();e(t).on({"onInit.fb":function(t,e,o){e.$refs.stage.on("mousewheel DOMMouseScroll wheel MozMousePixelScroll",function(t){var o=e.current,i=(new Date).getTime();e.group.length<2||!1===o.opts.wheel||"auto"===o.opts.wheel&&"image"!==o.type||(t.preventDefault(),t.stopPropagation(),o.$slide.hasClass("fancybox-animated")||(t=t.originalEvent||t,i-n<250||(n=i,e[(-t.deltaY||-t.deltaX||t.wheelDelta||-t.detail)<0?"next":"previous"]())))})}})}(document,jQuery);

/**!
 handlebars v4.7.8
*/
!function(a,b){"object"==typeof exports&&"object"==typeof module?module.exports=b():"function"==typeof define&&define.amd?define([],b):"object"==typeof exports?exports.Handlebars=b():a.Handlebars=b()}(this,function(){return function(a){function b(d){if(c[d])return c[d].exports;var e=c[d]={exports:{},id:d,loaded:!1};return a[d].call(e.exports,e,e.exports,b),e.loaded=!0,e.exports}var c={};return b.m=a,b.c=c,b.p="",b(0)}([function(a,b,c){"use strict";function d(){var a=r();return a.compile=function(b,c){return k.compile(b,c,a)},a.precompile=function(b,c){return k.precompile(b,c,a)},a.AST=i["default"],a.Compiler=k.Compiler,a.JavaScriptCompiler=m["default"],a.Parser=j.parser,a.parse=j.parse,a.parseWithoutProcessing=j.parseWithoutProcessing,a}var e=c(1)["default"];b.__esModule=!0;var f=c(2),g=e(f),h=c(84),i=e(h),j=c(85),k=c(90),l=c(91),m=e(l),n=c(88),o=e(n),p=c(83),q=e(p),r=g["default"].create,s=d();s.create=d,q["default"](s),s.Visitor=o["default"],s["default"]=s,b["default"]=s,a.exports=b["default"]},function(a,b){"use strict";b["default"]=function(a){return a&&a.__esModule?a:{"default":a}},b.__esModule=!0},function(a,b,c){"use strict";function d(){var a=new h.HandlebarsEnvironment;return n.extend(a,h),a.SafeString=j["default"],a.Exception=l["default"],a.Utils=n,a.escapeExpression=n.escapeExpression,a.VM=p,a.template=function(b){return p.template(b,a)},a}var e=c(3)["default"],f=c(1)["default"];b.__esModule=!0;var g=c(4),h=e(g),i=c(77),j=f(i),k=c(6),l=f(k),m=c(5),n=e(m),o=c(78),p=e(o),q=c(83),r=f(q),s=d();s.create=d,r["default"](s),s["default"]=s,b["default"]=s,a.exports=b["default"]},function(a,b){"use strict";b["default"]=function(a){if(a&&a.__esModule)return a;var b={};if(null!=a)for(var c in a)Object.prototype.hasOwnProperty.call(a,c)&&(b[c]=a[c]);return b["default"]=a,b},b.__esModule=!0},function(a,b,c){"use strict";function d(a,b,c){this.helpers=a||{},this.partials=b||{},this.decorators=c||{},i.registerDefaultHelpers(this),j.registerDefaultDecorators(this)}var e=c(1)["default"];b.__esModule=!0,b.HandlebarsEnvironment=d;var f=c(5),g=c(6),h=e(g),i=c(10),j=c(70),k=c(72),l=e(k),m=c(73),n="4.7.8";b.VERSION=n;var o=8;b.COMPILER_REVISION=o;var p=7;b.LAST_COMPATIBLE_COMPILER_REVISION=p;var q={1:"<= 1.0.rc.2",2:"== 1.0.0-rc.3",3:"== 1.0.0-rc.4",4:"== 1.x.x",5:"== 2.0.0-alpha.x",6:">= 2.0.0-beta.1",7:">= 4.0.0 <4.3.0",8:">= 4.3.0"};b.REVISION_CHANGES=q;var r="[object Object]";d.prototype={constructor:d,logger:l["default"],log:l["default"].log,registerHelper:function(a,b){if(f.toString.call(a)===r){if(b)throw new h["default"]("Arg not supported with multiple helpers");f.extend(this.helpers,a)}else this.helpers[a]=b},unregisterHelper:function(a){delete this.helpers[a]},registerPartial:function(a,b){if(f.toString.call(a)===r)f.extend(this.partials,a);else{if("undefined"==typeof b)throw new h["default"]('Attempting to register a partial called "'+a+'" as undefined');this.partials[a]=b}},unregisterPartial:function(a){delete this.partials[a]},registerDecorator:function(a,b){if(f.toString.call(a)===r){if(b)throw new h["default"]("Arg not supported with multiple decorators");f.extend(this.decorators,a)}else this.decorators[a]=b},unregisterDecorator:function(a){delete this.decorators[a]},resetLoggedPropertyAccesses:function(){m.resetLoggedProperties()}};var s=l["default"].log;b.log=s,b.createFrame=f.createFrame,b.logger=l["default"]},function(a,b){"use strict";function c(a){return k[a]}function d(a){for(var b=1;b<arguments.length;b++)for(var c in arguments[b])Object.prototype.hasOwnProperty.call(arguments[b],c)&&(a[c]=arguments[b][c]);return a}function e(a,b){for(var c=0,d=a.length;c<d;c++)if(a[c]===b)return c;return-1}function f(a){if("string"!=typeof a){if(a&&a.toHTML)return a.toHTML();if(null==a)return"";if(!a)return a+"";a=""+a}return m.test(a)?a.replace(l,c):a}function g(a){return!a&&0!==a||!(!p(a)||0!==a.length)}function h(a){var b=d({},a);return b._parent=a,b}function i(a,b){return a.path=b,a}function j(a,b){return(a?a+".":"")+b}b.__esModule=!0,b.extend=d,b.indexOf=e,b.escapeExpression=f,b.isEmpty=g,b.createFrame=h,b.blockParams=i,b.appendContextPath=j;var k={"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#x27;","`":"&#x60;","=":"&#x3D;"},l=/[&<>"'`=]/g,m=/[&<>"'`=]/,n=Object.prototype.toString;b.toString=n;var o=function(a){return"function"==typeof a};o(/x/)&&(b.isFunction=o=function(a){return"function"==typeof a&&"[object Function]"===n.call(a)}),b.isFunction=o;var p=Array.isArray||function(a){return!(!a||"object"!=typeof a)&&"[object Array]"===n.call(a)};b.isArray=p},function(a,b,c){"use strict";function d(a,b){var c=b&&b.loc,g=void 0,h=void 0,i=void 0,j=void 0;c&&(g=c.start.line,h=c.end.line,i=c.start.column,j=c.end.column,a+=" - "+g+":"+i);for(var k=Error.prototype.constructor.call(this,a),l=0;l<f.length;l++)this[f[l]]=k[f[l]];Error.captureStackTrace&&Error.captureStackTrace(this,d);try{c&&(this.lineNumber=g,this.endLineNumber=h,e?(Object.defineProperty(this,"column",{value:i,enumerable:!0}),Object.defineProperty(this,"endColumn",{value:j,enumerable:!0})):(this.column=i,this.endColumn=j))}catch(m){}}var e=c(7)["default"];b.__esModule=!0;var f=["description","fileName","lineNumber","endLineNumber","message","name","number","stack"];d.prototype=new Error,b["default"]=d,a.exports=b["default"]},function(a,b,c){a.exports={"default":c(8),__esModule:!0}},function(a,b,c){var d=c(9);a.exports=function(a,b,c){return d.setDesc(a,b,c)}},function(a,b){var c=Object;a.exports={create:c.create,getProto:c.getPrototypeOf,isEnum:{}.propertyIsEnumerable,getDesc:c.getOwnPropertyDescriptor,setDesc:c.defineProperty,setDescs:c.defineProperties,getKeys:c.keys,getNames:c.getOwnPropertyNames,getSymbols:c.getOwnPropertySymbols,each:[].forEach}},function(a,b,c){"use strict";function d(a){h["default"](a),j["default"](a),l["default"](a),n["default"](a),p["default"](a),r["default"](a),t["default"](a)}function e(a,b,c){a.helpers[b]&&(a.hooks[b]=a.helpers[b],c||delete a.helpers[b])}var f=c(1)["default"];b.__esModule=!0,b.registerDefaultHelpers=d,b.moveHelperToHooks=e;var g=c(11),h=f(g),i=c(12),j=f(i),k=c(65),l=f(k),m=c(66),n=f(m),o=c(67),p=f(o),q=c(68),r=f(q),s=c(69),t=f(s)},function(a,b,c){"use strict";b.__esModule=!0;var d=c(5);b["default"]=function(a){a.registerHelper("blockHelperMissing",function(b,c){var e=c.inverse,f=c.fn;if(b===!0)return f(this);if(b===!1||null==b)return e(this);if(d.isArray(b))return b.length>0?(c.ids&&(c.ids=[c.name]),a.helpers.each(b,c)):e(this);if(c.data&&c.ids){var g=d.createFrame(c.data);g.contextPath=d.appendContextPath(c.data.contextPath,c.name),c={data:g}}return f(b,c)})},a.exports=b["default"]},function(a,b,c){"use strict";var d=c(13)["default"],e=c(43)["default"],f=c(55)["default"],g=c(60)["default"],h=c(1)["default"];b.__esModule=!0;var i=c(5),j=c(6),k=h(j);b["default"]=function(a){a.registerHelper("each",function(a,b){function c(b,c,d){n&&(n.key=b,n.index=c,n.first=0===c,n.last=!!d,o&&(n.contextPath=o+b)),m+=h(a[b],{data:n,blockParams:i.blockParams([a[b],b],[o+b,null])})}if(!b)throw new k["default"]("Must pass iterator to #each");var h=b.fn,j=b.inverse,l=0,m="",n=void 0,o=void 0;if(b.data&&b.ids&&(o=i.appendContextPath(b.data.contextPath,b.ids[0])+"."),i.isFunction(a)&&(a=a.call(this)),b.data&&(n=i.createFrame(b.data)),a&&"object"==typeof a)if(i.isArray(a))for(var p=a.length;l<p;l++)l in a&&c(l,l,l===a.length-1);else if("function"==typeof d&&a[e]){for(var q=[],r=f(a),s=r.next();!s.done;s=r.next())q.push(s.value);a=q;for(var p=a.length;l<p;l++)c(l,l,l===a.length-1)}else!function(){var b=void 0;g(a).forEach(function(a){void 0!==b&&c(b,l-1),b=a,l++}),void 0!==b&&c(b,l-1,!0)}();return 0===l&&(m=j(this)),m})},a.exports=b["default"]},function(a,b,c){a.exports={"default":c(14),__esModule:!0}},function(a,b,c){c(15),c(42),a.exports=c(21).Symbol},function(a,b,c){"use strict";var d=c(9),e=c(16),f=c(17),g=c(18),h=c(20),i=c(24),j=c(19),k=c(27),l=c(28),m=c(30),n=c(29),o=c(31),p=c(36),q=c(37),r=c(38),s=c(39),t=c(32),u=c(26),v=d.getDesc,w=d.setDesc,x=d.create,y=p.get,z=e.Symbol,A=e.JSON,B=A&&A.stringify,C=!1,D=n("_hidden"),E=d.isEnum,F=k("symbol-registry"),G=k("symbols"),H="function"==typeof z,I=Object.prototype,J=g&&j(function(){return 7!=x(w({},"a",{get:function(){return w(this,"a",{value:7}).a}})).a})?function(a,b,c){var d=v(I,b);d&&delete I[b],w(a,b,c),d&&a!==I&&w(I,b,d)}:w,K=function(a){var b=G[a]=x(z.prototype);return b._k=a,g&&C&&J(I,a,{configurable:!0,set:function(b){f(this,D)&&f(this[D],a)&&(this[D][a]=!1),J(this,a,u(1,b))}}),b},L=function(a){return"symbol"==typeof a},M=function(a,b,c){return c&&f(G,b)?(c.enumerable?(f(a,D)&&a[D][b]&&(a[D][b]=!1),c=x(c,{enumerable:u(0,!1)})):(f(a,D)||w(a,D,u(1,{})),a[D][b]=!0),J(a,b,c)):w(a,b,c)},N=function(a,b){s(a);for(var c,d=q(b=t(b)),e=0,f=d.length;f>e;)M(a,c=d[e++],b[c]);return a},O=function(a,b){return void 0===b?x(a):N(x(a),b)},P=function(a){var b=E.call(this,a);return!(b||!f(this,a)||!f(G,a)||f(this,D)&&this[D][a])||b},Q=function(a,b){var c=v(a=t(a),b);return!c||!f(G,b)||f(a,D)&&a[D][b]||(c.enumerable=!0),c},R=function(a){for(var b,c=y(t(a)),d=[],e=0;c.length>e;)f(G,b=c[e++])||b==D||d.push(b);return d},S=function(a){for(var b,c=y(t(a)),d=[],e=0;c.length>e;)f(G,b=c[e++])&&d.push(G[b]);return d},T=function(a){if(void 0!==a&&!L(a)){for(var b,c,d=[a],e=1,f=arguments;f.length>e;)d.push(f[e++]);return b=d[1],"function"==typeof b&&(c=b),!c&&r(b)||(b=function(a,b){if(c&&(b=c.call(this,a,b)),!L(b))return b}),d[1]=b,B.apply(A,d)}},U=j(function(){var a=z();return"[null]"!=B([a])||"{}"!=B({a:a})||"{}"!=B(Object(a))});H||(z=function(){if(L(this))throw TypeError("Symbol is not a constructor");return K(m(arguments.length>0?arguments[0]:void 0))},i(z.prototype,"toString",function(){return this._k}),L=function(a){return a instanceof z},d.create=O,d.isEnum=P,d.getDesc=Q,d.setDesc=M,d.setDescs=N,d.getNames=p.get=R,d.getSymbols=S,g&&!c(41)&&i(I,"propertyIsEnumerable",P,!0));var V={"for":function(a){return f(F,a+="")?F[a]:F[a]=z(a)},keyFor:function(a){return o(F,a)},useSetter:function(){C=!0},useSimple:function(){C=!1}};d.each.call("hasInstance,isConcatSpreadable,iterator,match,replace,search,species,split,toPrimitive,toStringTag,unscopables".split(","),function(a){var b=n(a);V[a]=H?b:K(b)}),C=!0,h(h.G+h.W,{Symbol:z}),h(h.S,"Symbol",V),h(h.S+h.F*!H,"Object",{create:O,defineProperty:M,defineProperties:N,getOwnPropertyDescriptor:Q,getOwnPropertyNames:R,getOwnPropertySymbols:S}),A&&h(h.S+h.F*(!H||U),"JSON",{stringify:T}),l(z,"Symbol"),l(Math,"Math",!0),l(e.JSON,"JSON",!0)},function(a,b){var c=a.exports="undefined"!=typeof window&&window.Math==Math?window:"undefined"!=typeof self&&self.Math==Math?self:Function("return this")();"number"==typeof __g&&(__g=c)},function(a,b){var c={}.hasOwnProperty;a.exports=function(a,b){return c.call(a,b)}},function(a,b,c){a.exports=!c(19)(function(){return 7!=Object.defineProperty({},"a",{get:function(){return 7}}).a})},function(a,b){a.exports=function(a){try{return!!a()}catch(b){return!0}}},function(a,b,c){var d=c(16),e=c(21),f=c(22),g="prototype",h=function(a,b,c){var i,j,k,l=a&h.F,m=a&h.G,n=a&h.S,o=a&h.P,p=a&h.B,q=a&h.W,r=m?e:e[b]||(e[b]={}),s=m?d:n?d[b]:(d[b]||{})[g];m&&(c=b);for(i in c)j=!l&&s&&i in s,j&&i in r||(k=j?s[i]:c[i],r[i]=m&&"function"!=typeof s[i]?c[i]:p&&j?f(k,d):q&&s[i]==k?function(a){var b=function(b){return this instanceof a?new a(b):a(b)};return b[g]=a[g],b}(k):o&&"function"==typeof k?f(Function.call,k):k,o&&((r[g]||(r[g]={}))[i]=k))};h.F=1,h.G=2,h.S=4,h.P=8,h.B=16,h.W=32,a.exports=h},function(a,b){var c=a.exports={version:"1.2.6"};"number"==typeof __e&&(__e=c)},function(a,b,c){var d=c(23);a.exports=function(a,b,c){if(d(a),void 0===b)return a;switch(c){case 1:return function(c){return a.call(b,c)};case 2:return function(c,d){return a.call(b,c,d)};case 3:return function(c,d,e){return a.call(b,c,d,e)}}return function(){return a.apply(b,arguments)}}},function(a,b){a.exports=function(a){if("function"!=typeof a)throw TypeError(a+" is not a function!");return a}},function(a,b,c){a.exports=c(25)},function(a,b,c){var d=c(9),e=c(26);a.exports=c(18)?function(a,b,c){return d.setDesc(a,b,e(1,c))}:function(a,b,c){return a[b]=c,a}},function(a,b){a.exports=function(a,b){return{enumerable:!(1&a),configurable:!(2&a),writable:!(4&a),value:b}}},function(a,b,c){var d=c(16),e="__core-js_shared__",f=d[e]||(d[e]={});a.exports=function(a){return f[a]||(f[a]={})}},function(a,b,c){var d=c(9).setDesc,e=c(17),f=c(29)("toStringTag");a.exports=function(a,b,c){a&&!e(a=c?a:a.prototype,f)&&d(a,f,{configurable:!0,value:b})}},function(a,b,c){var d=c(27)("wks"),e=c(30),f=c(16).Symbol;a.exports=function(a){return d[a]||(d[a]=f&&f[a]||(f||e)("Symbol."+a))}},function(a,b){var c=0,d=Math.random();a.exports=function(a){return"Symbol(".concat(void 0===a?"":a,")_",(++c+d).toString(36))}},function(a,b,c){var d=c(9),e=c(32);a.exports=function(a,b){for(var c,f=e(a),g=d.getKeys(f),h=g.length,i=0;h>i;)if(f[c=g[i++]]===b)return c}},function(a,b,c){var d=c(33),e=c(35);a.exports=function(a){return d(e(a))}},function(a,b,c){var d=c(34);a.exports=Object("z").propertyIsEnumerable(0)?Object:function(a){return"String"==d(a)?a.split(""):Object(a)}},function(a,b){var c={}.toString;a.exports=function(a){return c.call(a).slice(8,-1)}},function(a,b){a.exports=function(a){if(void 0==a)throw TypeError("Can't call method on  "+a);return a}},function(a,b,c){var d=c(32),e=c(9).getNames,f={}.toString,g="object"==typeof window&&Object.getOwnPropertyNames?Object.getOwnPropertyNames(window):[],h=function(a){try{return e(a)}catch(b){return g.slice()}};a.exports.get=function(a){return g&&"[object Window]"==f.call(a)?h(a):e(d(a))}},function(a,b,c){var d=c(9);a.exports=function(a){var b=d.getKeys(a),c=d.getSymbols;if(c)for(var e,f=c(a),g=d.isEnum,h=0;f.length>h;)g.call(a,e=f[h++])&&b.push(e);return b}},function(a,b,c){var d=c(34);a.exports=Array.isArray||function(a){return"Array"==d(a)}},function(a,b,c){var d=c(40);a.exports=function(a){if(!d(a))throw TypeError(a+" is not an object!");return a}},function(a,b){a.exports=function(a){return"object"==typeof a?null!==a:"function"==typeof a}},function(a,b){a.exports=!0},function(a,b){},function(a,b,c){a.exports={"default":c(44),__esModule:!0}},function(a,b,c){c(45),c(51),a.exports=c(29)("iterator")},function(a,b,c){"use strict";var d=c(46)(!0);c(48)(String,"String",function(a){this._t=String(a),this._i=0},function(){var a,b=this._t,c=this._i;return c>=b.length?{value:void 0,done:!0}:(a=d(b,c),this._i+=a.length,{value:a,done:!1})})},function(a,b,c){var d=c(47),e=c(35);a.exports=function(a){return function(b,c){var f,g,h=String(e(b)),i=d(c),j=h.length;return i<0||i>=j?a?"":void 0:(f=h.charCodeAt(i),f<55296||f>56319||i+1===j||(g=h.charCodeAt(i+1))<56320||g>57343?a?h.charAt(i):f:a?h.slice(i,i+2):(f-55296<<10)+(g-56320)+65536)}}},function(a,b){var c=Math.ceil,d=Math.floor;a.exports=function(a){return isNaN(a=+a)?0:(a>0?d:c)(a)}},function(a,b,c){"use strict";var d=c(41),e=c(20),f=c(24),g=c(25),h=c(17),i=c(49),j=c(50),k=c(28),l=c(9).getProto,m=c(29)("iterator"),n=!([].keys&&"next"in[].keys()),o="@@iterator",p="keys",q="values",r=function(){return this};a.exports=function(a,b,c,s,t,u,v){j(c,b,s);var w,x,y=function(a){if(!n&&a in C)return C[a];switch(a){case p:return function(){return new c(this,a)};case q:return function(){return new c(this,a)}}return function(){return new c(this,a)}},z=b+" Iterator",A=t==q,B=!1,C=a.prototype,D=C[m]||C[o]||t&&C[t],E=D||y(t);if(D){var F=l(E.call(new a));k(F,z,!0),!d&&h(C,o)&&g(F,m,r),A&&D.name!==q&&(B=!0,E=function(){return D.call(this)})}if(d&&!v||!n&&!B&&C[m]||g(C,m,E),i[b]=E,i[z]=r,t)if(w={values:A?E:y(q),keys:u?E:y(p),entries:A?y("entries"):E},v)for(x in w)x in C||f(C,x,w[x]);else e(e.P+e.F*(n||B),b,w);return w}},function(a,b){a.exports={}},function(a,b,c){"use strict";var d=c(9),e=c(26),f=c(28),g={};c(25)(g,c(29)("iterator"),function(){return this}),a.exports=function(a,b,c){a.prototype=d.create(g,{next:e(1,c)}),f(a,b+" Iterator")}},function(a,b,c){c(52);var d=c(49);d.NodeList=d.HTMLCollection=d.Array},function(a,b,c){"use strict";var d=c(53),e=c(54),f=c(49),g=c(32);a.exports=c(48)(Array,"Array",function(a,b){this._t=g(a),this._i=0,this._k=b},function(){var a=this._t,b=this._k,c=this._i++;return!a||c>=a.length?(this._t=void 0,e(1)):"keys"==b?e(0,c):"values"==b?e(0,a[c]):e(0,[c,a[c]])},"values"),f.Arguments=f.Array,d("keys"),d("values"),d("entries")},function(a,b){a.exports=function(){}},function(a,b){a.exports=function(a,b){return{value:b,done:!!a}}},function(a,b,c){a.exports={"default":c(56),__esModule:!0}},function(a,b,c){c(51),c(45),a.exports=c(57)},function(a,b,c){var d=c(39),e=c(58);a.exports=c(21).getIterator=function(a){var b=e(a);if("function"!=typeof b)throw TypeError(a+" is not iterable!");return d(b.call(a))}},function(a,b,c){var d=c(59),e=c(29)("iterator"),f=c(49);a.exports=c(21).getIteratorMethod=function(a){if(void 0!=a)return a[e]||a["@@iterator"]||f[d(a)]}},function(a,b,c){var d=c(34),e=c(29)("toStringTag"),f="Arguments"==d(function(){return arguments}());a.exports=function(a){var b,c,g;return void 0===a?"Undefined":null===a?"Null":"string"==typeof(c=(b=Object(a))[e])?c:f?d(b):"Object"==(g=d(b))&&"function"==typeof b.callee?"Arguments":g}},function(a,b,c){a.exports={"default":c(61),__esModule:!0}},function(a,b,c){c(62),a.exports=c(21).Object.keys},function(a,b,c){var d=c(63);c(64)("keys",function(a){return function(b){return a(d(b))}})},function(a,b,c){var d=c(35);a.exports=function(a){return Object(d(a))}},function(a,b,c){var d=c(20),e=c(21),f=c(19);a.exports=function(a,b){var c=(e.Object||{})[a]||Object[a],g={};g[a]=b(c),d(d.S+d.F*f(function(){c(1)}),"Object",g)}},function(a,b,c){"use strict";var d=c(1)["default"];b.__esModule=!0;var e=c(6),f=d(e);b["default"]=function(a){a.registerHelper("helperMissing",function(){if(1!==arguments.length)throw new f["default"]('Missing helper: "'+arguments[arguments.length-1].name+'"')})},a.exports=b["default"]},function(a,b,c){"use strict";var d=c(1)["default"];b.__esModule=!0;var e=c(5),f=c(6),g=d(f);b["default"]=function(a){a.registerHelper("if",function(a,b){if(2!=arguments.length)throw new g["default"]("#if requires exactly one argument");return e.isFunction(a)&&(a=a.call(this)),!b.hash.includeZero&&!a||e.isEmpty(a)?b.inverse(this):b.fn(this)}),a.registerHelper("unless",function(b,c){if(2!=arguments.length)throw new g["default"]("#unless requires exactly one argument");return a.helpers["if"].call(this,b,{fn:c.inverse,inverse:c.fn,hash:c.hash})})},a.exports=b["default"]},function(a,b){"use strict";b.__esModule=!0,b["default"]=function(a){a.registerHelper("log",function(){for(var b=[void 0],c=arguments[arguments.length-1],d=0;d<arguments.length-1;d++)b.push(arguments[d]);var e=1;null!=c.hash.level?e=c.hash.level:c.data&&null!=c.data.level&&(e=c.data.level),b[0]=e,a.log.apply(a,b)})},a.exports=b["default"]},function(a,b){"use strict";b.__esModule=!0,b["default"]=function(a){a.registerHelper("lookup",function(a,b,c){return a?c.lookupProperty(a,b):a})},a.exports=b["default"]},function(a,b,c){"use strict";var d=c(1)["default"];b.__esModule=!0;var e=c(5),f=c(6),g=d(f);b["default"]=function(a){a.registerHelper("with",function(a,b){if(2!=arguments.length)throw new g["default"]("#with requires exactly one argument");e.isFunction(a)&&(a=a.call(this));var c=b.fn;if(e.isEmpty(a))return b.inverse(this);var d=b.data;return b.data&&b.ids&&(d=e.createFrame(b.data),d.contextPath=e.appendContextPath(b.data.contextPath,b.ids[0])),c(a,{data:d,blockParams:e.blockParams([a],[d&&d.contextPath])})})},a.exports=b["default"]},function(a,b,c){"use strict";function d(a){g["default"](a)}var e=c(1)["default"];b.__esModule=!0,b.registerDefaultDecorators=d;var f=c(71),g=e(f)},function(a,b,c){"use strict";b.__esModule=!0;var d=c(5);b["default"]=function(a){a.registerDecorator("inline",function(a,b,c,e){var f=a;return b.partials||(b.partials={},f=function(e,f){var g=c.partials;c.partials=d.extend({},g,b.partials);var h=a(e,f);return c.partials=g,h}),b.partials[e.args[0]]=e.fn,f})},a.exports=b["default"]},function(a,b,c){"use strict";b.__esModule=!0;var d=c(5),e={methodMap:["debug","info","warn","error"],level:"info",lookupLevel:function(a){if("string"==typeof a){var b=d.indexOf(e.methodMap,a.toLowerCase());a=b>=0?b:parseInt(a,10)}return a},log:function(a){if(a=e.lookupLevel(a),"undefined"!=typeof console&&e.lookupLevel(e.level)<=a){var b=e.methodMap[a];console[b]||(b="log");for(var c=arguments.length,d=Array(c>1?c-1:0),f=1;f<c;f++)d[f-1]=arguments[f];console[b].apply(console,d)}}};b["default"]=e,a.exports=b["default"]},function(a,b,c){"use strict";function d(a){var b=i(null);b.constructor=!1,b.__defineGetter__=!1,b.__defineSetter__=!1,b.__lookupGetter__=!1;var c=i(null);return c.__proto__=!1,{properties:{whitelist:l.createNewLookupObject(c,a.allowedProtoProperties),defaultValue:a.allowProtoPropertiesByDefault},methods:{whitelist:l.createNewLookupObject(b,a.allowedProtoMethods),defaultValue:a.allowProtoMethodsByDefault}}}function e(a,b,c){return"function"==typeof a?f(b.methods,c):f(b.properties,c)}function f(a,b){return void 0!==a.whitelist[b]?a.whitelist[b]===!0:void 0!==a.defaultValue?a.defaultValue:(g(b),!1)}function g(a){o[a]!==!0&&(o[a]=!0,n["default"].log("error",'Handlebars: Access has been denied to resolve the property "'+a+'" because it is not an "own property" of its parent.\nYou can add a runtime option to disable the check or this warning:\nSee https://handlebarsjs.com/api-reference/runtime-options.html#options-to-control-prototype-access for details'))}function h(){j(o).forEach(function(a){delete o[a]})}var i=c(74)["default"],j=c(60)["default"],k=c(1)["default"];b.__esModule=!0,b.createProtoAccessControl=d,b.resultIsAllowed=e,b.resetLoggedProperties=h;var l=c(76),m=c(72),n=k(m),o=i(null)},function(a,b,c){a.exports={"default":c(75),__esModule:!0}},function(a,b,c){var d=c(9);a.exports=function(a,b){return d.create(a,b)}},function(a,b,c){"use strict";function d(){for(var a=arguments.length,b=Array(a),c=0;c<a;c++)b[c]=arguments[c];return f.extend.apply(void 0,[e(null)].concat(b))}var e=c(74)["default"];b.__esModule=!0,b.createNewLookupObject=d;var f=c(5)},function(a,b){"use strict";function c(a){this.string=a}b.__esModule=!0,c.prototype.toString=c.prototype.toHTML=function(){return""+this.string},b["default"]=c,a.exports=b["default"]},function(a,b,c){"use strict";function d(a){var b=a&&a[0]||1,c=v.COMPILER_REVISION;if(!(b>=v.LAST_COMPATIBLE_COMPILER_REVISION&&b<=v.COMPILER_REVISION)){if(b<v.LAST_COMPATIBLE_COMPILER_REVISION){var d=v.REVISION_CHANGES[c],e=v.REVISION_CHANGES[b];throw new u["default"]("Template was precompiled with an older version of Handlebars than the current runtime. Please update your precompiler to a newer version ("+d+") or downgrade your runtime to an older version ("+e+").")}throw new u["default"]("Template was precompiled with a newer version of Handlebars than the current runtime. Please update your runtime to a newer version ("+a[1]+").")}}function e(a,b){function c(c,d,e){e.hash&&(d=s.extend({},d,e.hash),e.ids&&(e.ids[0]=!0)),c=b.VM.resolvePartial.call(this,c,d,e);var f=s.extend({},e,{hooks:this.hooks,protoAccessControl:this.protoAccessControl}),g=b.VM.invokePartial.call(this,c,d,f);if(null==g&&b.compile&&(e.partials[e.name]=b.compile(c,a.compilerOptions,b),g=e.partials[e.name](d,f)),null!=g){if(e.indent){for(var h=g.split("\n"),i=0,j=h.length;i<j&&(h[i]||i+1!==j);i++)h[i]=e.indent+h[i];g=h.join("\n")}return g}throw new u["default"]("The partial "+e.name+" could not be compiled when running in runtime-only mode")}function d(b){function c(b){return""+a.main(g,b,g.helpers,g.partials,f,i,h)}var e=arguments.length<=1||void 0===arguments[1]?{}:arguments[1],f=e.data;d._setup(e),!e.partial&&a.useData&&(f=j(b,f));var h=void 0,i=a.useBlockParams?[]:void 0;return a.useDepths&&(h=e.depths?b!=e.depths[0]?[b].concat(e.depths):e.depths:[b]),(c=k(a.main,c,g,e.depths||[],f,i))(b,e)}if(!b)throw new u["default"]("No environment passed to template");if(!a||!a.main)throw new u["default"]("Unknown template object: "+typeof a);a.main.decorator=a.main_d,b.VM.checkRevision(a.compiler);var e=a.compiler&&7===a.compiler[0],g={strict:function(a,b,c){if(!(a&&b in a))throw new u["default"]('"'+b+'" not defined in '+a,{loc:c});return g.lookupProperty(a,b)},lookupProperty:function(a,b){var c=a[b];return null==c?c:Object.prototype.hasOwnProperty.call(a,b)?c:y.resultIsAllowed(c,g.protoAccessControl,b)?c:void 0},lookup:function(a,b){for(var c=a.length,d=0;d<c;d++){var e=a[d]&&g.lookupProperty(a[d],b);if(null!=e)return a[d][b]}},lambda:function(a,b){return"function"==typeof a?a.call(b):a},escapeExpression:s.escapeExpression,invokePartial:c,fn:function(b){var c=a[b];return c.decorator=a[b+"_d"],c},programs:[],program:function(a,b,c,d,e){var g=this.programs[a],h=this.fn(a);return b||e||d||c?g=f(this,a,h,b,c,d,e):g||(g=this.programs[a]=f(this,a,h)),g},data:function(a,b){for(;a&&b--;)a=a._parent;return a},mergeIfNeeded:function(a,b){var c=a||b;return a&&b&&a!==b&&(c=s.extend({},b,a)),c},nullContext:n({}),noop:b.VM.noop,compilerInfo:a.compiler};return d.isTop=!0,d._setup=function(c){if(c.partial)g.protoAccessControl=c.protoAccessControl,g.helpers=c.helpers,g.partials=c.partials,g.decorators=c.decorators,g.hooks=c.hooks;else{var d=s.extend({},b.helpers,c.helpers);l(d,g),g.helpers=d,a.usePartial&&(g.partials=g.mergeIfNeeded(c.partials,b.partials)),(a.usePartial||a.useDecorators)&&(g.decorators=s.extend({},b.decorators,c.decorators)),g.hooks={},g.protoAccessControl=y.createProtoAccessControl(c);var f=c.allowCallsToHelperMissing||e;w.moveHelperToHooks(g,"helperMissing",f),w.moveHelperToHooks(g,"blockHelperMissing",f)}},d._child=function(b,c,d,e){if(a.useBlockParams&&!d)throw new u["default"]("must pass block params");if(a.useDepths&&!e)throw new u["default"]("must pass parent depths");return f(g,b,a[b],c,0,d,e)},d}function f(a,b,c,d,e,f,g){function h(b){var e=arguments.length<=1||void 0===arguments[1]?{}:arguments[1],h=g;return!g||b==g[0]||b===a.nullContext&&null===g[0]||(h=[b].concat(g)),c(a,b,a.helpers,a.partials,e.data||d,f&&[e.blockParams].concat(f),h)}return h=k(c,h,a,g,d,f),h.program=b,h.depth=g?g.length:0,h.blockParams=e||0,h}function g(a,b,c){return a?a.call||c.name||(c.name=a,a=c.partials[a]):a="@partial-block"===c.name?c.data["partial-block"]:c.partials[c.name],a}function h(a,b,c){var d=c.data&&c.data["partial-block"];c.partial=!0,c.ids&&(c.data.contextPath=c.ids[0]||c.data.contextPath);var e=void 0;if(c.fn&&c.fn!==i&&!function(){c.data=v.createFrame(c.data);var a=c.fn;e=c.data["partial-block"]=function(b){var c=arguments.length<=1||void 0===arguments[1]?{}:arguments[1];return c.data=v.createFrame(c.data),c.data["partial-block"]=d,a(b,c)},a.partials&&(c.partials=s.extend({},c.partials,a.partials))}(),void 0===a&&e&&(a=e),void 0===a)throw new u["default"]("The partial "+c.name+" could not be found");if(a instanceof Function)return a(b,c)}function i(){return""}function j(a,b){return b&&"root"in b||(b=b?v.createFrame(b):{},b.root=a),b}function k(a,b,c,d,e,f){if(a.decorator){var g={};b=a.decorator(b,g,c,d&&d[0],e,f,d),s.extend(b,g)}return b}function l(a,b){o(a).forEach(function(c){var d=a[c];a[c]=m(d,b)})}function m(a,b){var c=b.lookupProperty;return x.wrapHelper(a,function(a){return s.extend({lookupProperty:c},a)})}var n=c(79)["default"],o=c(60)["default"],p=c(3)["default"],q=c(1)["default"];b.__esModule=!0,b.checkRevision=d,b.template=e,b.wrapProgram=f,b.resolvePartial=g,b.invokePartial=h,b.noop=i;var r=c(5),s=p(r),t=c(6),u=q(t),v=c(4),w=c(10),x=c(82),y=c(73)},function(a,b,c){a.exports={"default":c(80),__esModule:!0}},function(a,b,c){c(81),a.exports=c(21).Object.seal},function(a,b,c){var d=c(40);c(64)("seal",function(a){return function(b){return a&&d(b)?a(b):b}})},function(a,b){"use strict";function c(a,b){if("function"!=typeof a)return a;var c=function(){var c=arguments[arguments.length-1];return arguments[arguments.length-1]=b(c),a.apply(this,arguments)};return c}b.__esModule=!0,b.wrapHelper=c},function(a,b){"use strict";b.__esModule=!0,b["default"]=function(a){!function(){"object"!=typeof globalThis&&(Object.prototype.__defineGetter__("__magic__",function(){return this}),__magic__.globalThis=__magic__,delete Object.prototype.__magic__)}();var b=globalThis.Handlebars;a.noConflict=function(){return globalThis.Handlebars===a&&(globalThis.Handlebars=b),a}},a.exports=b["default"]},function(a,b){"use strict";b.__esModule=!0;var c={helpers:{helperExpression:function(a){return"SubExpression"===a.type||("MustacheStatement"===a.type||"BlockStatement"===a.type)&&!!(a.params&&a.params.length||a.hash)},scopedId:function(a){return/^\.|this\b/.test(a.original)},simpleId:function(a){return 1===a.parts.length&&!c.helpers.scopedId(a)&&!a.depth}}};b["default"]=c,a.exports=b["default"]},function(a,b,c){"use strict";function d(a,b){if("Program"===a.type)return a;i["default"].yy=o,o.locInfo=function(a){return new o.SourceLocation(b&&b.srcName,a)};var c=i["default"].parse(a);return c}function e(a,b){var c=d(a,b),e=new k["default"](b);return e.accept(c)}var f=c(1)["default"],g=c(3)["default"];b.__esModule=!0,b.parseWithoutProcessing=d,b.parse=e;var h=c(86),i=f(h),j=c(87),k=f(j),l=c(89),m=g(l),n=c(5);b.parser=i["default"];var o={};n.extend(o,m)},function(a,b){"use strict";b.__esModule=!0;var c=function(){function a(){this.yy={}}var b={trace:function(){},yy:{},symbols_:{error:2,root:3,program:4,EOF:5,program_repetition0:6,statement:7,mustache:8,block:9,rawBlock:10,partial:11,partialBlock:12,content:13,COMMENT:14,CONTENT:15,openRawBlock:16,rawBlock_repetition0:17,END_RAW_BLOCK:18,OPEN_RAW_BLOCK:19,helperName:20,openRawBlock_repetition0:21,openRawBlock_option0:22,CLOSE_RAW_BLOCK:23,openBlock:24,block_option0:25,closeBlock:26,openInverse:27,block_option1:28,OPEN_BLOCK:29,openBlock_repetition0:30,openBlock_option0:31,openBlock_option1:32,CLOSE:33,OPEN_INVERSE:34,openInverse_repetition0:35,openInverse_option0:36,openInverse_option1:37,openInverseChain:38,OPEN_INVERSE_CHAIN:39,openInverseChain_repetition0:40,openInverseChain_option0:41,openInverseChain_option1:42,inverseAndProgram:43,INVERSE:44,inverseChain:45,inverseChain_option0:46,OPEN_ENDBLOCK:47,OPEN:48,mustache_repetition0:49,mustache_option0:50,OPEN_UNESCAPED:51,mustache_repetition1:52,mustache_option1:53,CLOSE_UNESCAPED:54,OPEN_PARTIAL:55,partialName:56,partial_repetition0:57,partial_option0:58,openPartialBlock:59,OPEN_PARTIAL_BLOCK:60,openPartialBlock_repetition0:61,openPartialBlock_option0:62,param:63,sexpr:64,OPEN_SEXPR:65,sexpr_repetition0:66,sexpr_option0:67,CLOSE_SEXPR:68,hash:69,hash_repetition_plus0:70,hashSegment:71,ID:72,EQUALS:73,blockParams:74,OPEN_BLOCK_PARAMS:75,blockParams_repetition_plus0:76,CLOSE_BLOCK_PARAMS:77,path:78,dataName:79,STRING:80,NUMBER:81,BOOLEAN:82,UNDEFINED:83,NULL:84,DATA:85,pathSegments:86,SEP:87,$accept:0,$end:1},terminals_:{2:"error",5:"EOF",14:"COMMENT",15:"CONTENT",18:"END_RAW_BLOCK",19:"OPEN_RAW_BLOCK",23:"CLOSE_RAW_BLOCK",29:"OPEN_BLOCK",33:"CLOSE",34:"OPEN_INVERSE",39:"OPEN_INVERSE_CHAIN",44:"INVERSE",47:"OPEN_ENDBLOCK",48:"OPEN",51:"OPEN_UNESCAPED",54:"CLOSE_UNESCAPED",55:"OPEN_PARTIAL",60:"OPEN_PARTIAL_BLOCK",65:"OPEN_SEXPR",68:"CLOSE_SEXPR",72:"ID",73:"EQUALS",75:"OPEN_BLOCK_PARAMS",77:"CLOSE_BLOCK_PARAMS",80:"STRING",81:"NUMBER",82:"BOOLEAN",83:"UNDEFINED",84:"NULL",85:"DATA",87:"SEP"},productions_:[0,[3,2],[4,1],[7,1],[7,1],[7,1],[7,1],[7,1],[7,1],[7,1],[13,1],[10,3],[16,5],[9,4],[9,4],[24,6],[27,6],[38,6],[43,2],[45,3],[45,1],[26,3],[8,5],[8,5],[11,5],[12,3],[59,5],[63,1],[63,1],[64,5],[69,1],[71,3],[74,3],[20,1],[20,1],[20,1],[20,1],[20,1],[20,1],[20,1],[56,1],[56,1],[79,2],[78,1],[86,3],[86,1],[6,0],[6,2],[17,0],[17,2],[21,0],[21,2],[22,0],[22,1],[25,0],[25,1],[28,0],[28,1],[30,0],[30,2],[31,0],[31,1],[32,0],[32,1],[35,0],[35,2],[36,0],[36,1],[37,0],[37,1],[40,0],[40,2],[41,0],[41,1],[42,0],[42,1],[46,0],[46,1],[49,0],[49,2],[50,0],[50,1],[52,0],[52,2],[53,0],[53,1],[57,0],[57,2],[58,0],[58,1],[61,0],[61,2],[62,0],[62,1],[66,0],[66,2],[67,0],[67,1],[70,1],[70,2],[76,1],[76,2]],performAction:function(a,b,c,d,e,f,g){
var h=f.length-1;switch(e){case 1:return f[h-1];case 2:this.$=d.prepareProgram(f[h]);break;case 3:this.$=f[h];break;case 4:this.$=f[h];break;case 5:this.$=f[h];break;case 6:this.$=f[h];break;case 7:this.$=f[h];break;case 8:this.$=f[h];break;case 9:this.$={type:"CommentStatement",value:d.stripComment(f[h]),strip:d.stripFlags(f[h],f[h]),loc:d.locInfo(this._$)};break;case 10:this.$={type:"ContentStatement",original:f[h],value:f[h],loc:d.locInfo(this._$)};break;case 11:this.$=d.prepareRawBlock(f[h-2],f[h-1],f[h],this._$);break;case 12:this.$={path:f[h-3],params:f[h-2],hash:f[h-1]};break;case 13:this.$=d.prepareBlock(f[h-3],f[h-2],f[h-1],f[h],!1,this._$);break;case 14:this.$=d.prepareBlock(f[h-3],f[h-2],f[h-1],f[h],!0,this._$);break;case 15:this.$={open:f[h-5],path:f[h-4],params:f[h-3],hash:f[h-2],blockParams:f[h-1],strip:d.stripFlags(f[h-5],f[h])};break;case 16:this.$={path:f[h-4],params:f[h-3],hash:f[h-2],blockParams:f[h-1],strip:d.stripFlags(f[h-5],f[h])};break;case 17:this.$={path:f[h-4],params:f[h-3],hash:f[h-2],blockParams:f[h-1],strip:d.stripFlags(f[h-5],f[h])};break;case 18:this.$={strip:d.stripFlags(f[h-1],f[h-1]),program:f[h]};break;case 19:var i=d.prepareBlock(f[h-2],f[h-1],f[h],f[h],!1,this._$),j=d.prepareProgram([i],f[h-1].loc);j.chained=!0,this.$={strip:f[h-2].strip,program:j,chain:!0};break;case 20:this.$=f[h];break;case 21:this.$={path:f[h-1],strip:d.stripFlags(f[h-2],f[h])};break;case 22:this.$=d.prepareMustache(f[h-3],f[h-2],f[h-1],f[h-4],d.stripFlags(f[h-4],f[h]),this._$);break;case 23:this.$=d.prepareMustache(f[h-3],f[h-2],f[h-1],f[h-4],d.stripFlags(f[h-4],f[h]),this._$);break;case 24:this.$={type:"PartialStatement",name:f[h-3],params:f[h-2],hash:f[h-1],indent:"",strip:d.stripFlags(f[h-4],f[h]),loc:d.locInfo(this._$)};break;case 25:this.$=d.preparePartialBlock(f[h-2],f[h-1],f[h],this._$);break;case 26:this.$={path:f[h-3],params:f[h-2],hash:f[h-1],strip:d.stripFlags(f[h-4],f[h])};break;case 27:this.$=f[h];break;case 28:this.$=f[h];break;case 29:this.$={type:"SubExpression",path:f[h-3],params:f[h-2],hash:f[h-1],loc:d.locInfo(this._$)};break;case 30:this.$={type:"Hash",pairs:f[h],loc:d.locInfo(this._$)};break;case 31:this.$={type:"HashPair",key:d.id(f[h-2]),value:f[h],loc:d.locInfo(this._$)};break;case 32:this.$=d.id(f[h-1]);break;case 33:this.$=f[h];break;case 34:this.$=f[h];break;case 35:this.$={type:"StringLiteral",value:f[h],original:f[h],loc:d.locInfo(this._$)};break;case 36:this.$={type:"NumberLiteral",value:Number(f[h]),original:Number(f[h]),loc:d.locInfo(this._$)};break;case 37:this.$={type:"BooleanLiteral",value:"true"===f[h],original:"true"===f[h],loc:d.locInfo(this._$)};break;case 38:this.$={type:"UndefinedLiteral",original:void 0,value:void 0,loc:d.locInfo(this._$)};break;case 39:this.$={type:"NullLiteral",original:null,value:null,loc:d.locInfo(this._$)};break;case 40:this.$=f[h];break;case 41:this.$=f[h];break;case 42:this.$=d.preparePath(!0,f[h],this._$);break;case 43:this.$=d.preparePath(!1,f[h],this._$);break;case 44:f[h-2].push({part:d.id(f[h]),original:f[h],separator:f[h-1]}),this.$=f[h-2];break;case 45:this.$=[{part:d.id(f[h]),original:f[h]}];break;case 46:this.$=[];break;case 47:f[h-1].push(f[h]);break;case 48:this.$=[];break;case 49:f[h-1].push(f[h]);break;case 50:this.$=[];break;case 51:f[h-1].push(f[h]);break;case 58:this.$=[];break;case 59:f[h-1].push(f[h]);break;case 64:this.$=[];break;case 65:f[h-1].push(f[h]);break;case 70:this.$=[];break;case 71:f[h-1].push(f[h]);break;case 78:this.$=[];break;case 79:f[h-1].push(f[h]);break;case 82:this.$=[];break;case 83:f[h-1].push(f[h]);break;case 86:this.$=[];break;case 87:f[h-1].push(f[h]);break;case 90:this.$=[];break;case 91:f[h-1].push(f[h]);break;case 94:this.$=[];break;case 95:f[h-1].push(f[h]);break;case 98:this.$=[f[h]];break;case 99:f[h-1].push(f[h]);break;case 100:this.$=[f[h]];break;case 101:f[h-1].push(f[h])}},table:[{3:1,4:2,5:[2,46],6:3,14:[2,46],15:[2,46],19:[2,46],29:[2,46],34:[2,46],48:[2,46],51:[2,46],55:[2,46],60:[2,46]},{1:[3]},{5:[1,4]},{5:[2,2],7:5,8:6,9:7,10:8,11:9,12:10,13:11,14:[1,12],15:[1,20],16:17,19:[1,23],24:15,27:16,29:[1,21],34:[1,22],39:[2,2],44:[2,2],47:[2,2],48:[1,13],51:[1,14],55:[1,18],59:19,60:[1,24]},{1:[2,1]},{5:[2,47],14:[2,47],15:[2,47],19:[2,47],29:[2,47],34:[2,47],39:[2,47],44:[2,47],47:[2,47],48:[2,47],51:[2,47],55:[2,47],60:[2,47]},{5:[2,3],14:[2,3],15:[2,3],19:[2,3],29:[2,3],34:[2,3],39:[2,3],44:[2,3],47:[2,3],48:[2,3],51:[2,3],55:[2,3],60:[2,3]},{5:[2,4],14:[2,4],15:[2,4],19:[2,4],29:[2,4],34:[2,4],39:[2,4],44:[2,4],47:[2,4],48:[2,4],51:[2,4],55:[2,4],60:[2,4]},{5:[2,5],14:[2,5],15:[2,5],19:[2,5],29:[2,5],34:[2,5],39:[2,5],44:[2,5],47:[2,5],48:[2,5],51:[2,5],55:[2,5],60:[2,5]},{5:[2,6],14:[2,6],15:[2,6],19:[2,6],29:[2,6],34:[2,6],39:[2,6],44:[2,6],47:[2,6],48:[2,6],51:[2,6],55:[2,6],60:[2,6]},{5:[2,7],14:[2,7],15:[2,7],19:[2,7],29:[2,7],34:[2,7],39:[2,7],44:[2,7],47:[2,7],48:[2,7],51:[2,7],55:[2,7],60:[2,7]},{5:[2,8],14:[2,8],15:[2,8],19:[2,8],29:[2,8],34:[2,8],39:[2,8],44:[2,8],47:[2,8],48:[2,8],51:[2,8],55:[2,8],60:[2,8]},{5:[2,9],14:[2,9],15:[2,9],19:[2,9],29:[2,9],34:[2,9],39:[2,9],44:[2,9],47:[2,9],48:[2,9],51:[2,9],55:[2,9],60:[2,9]},{20:25,72:[1,35],78:26,79:27,80:[1,28],81:[1,29],82:[1,30],83:[1,31],84:[1,32],85:[1,34],86:33},{20:36,72:[1,35],78:26,79:27,80:[1,28],81:[1,29],82:[1,30],83:[1,31],84:[1,32],85:[1,34],86:33},{4:37,6:3,14:[2,46],15:[2,46],19:[2,46],29:[2,46],34:[2,46],39:[2,46],44:[2,46],47:[2,46],48:[2,46],51:[2,46],55:[2,46],60:[2,46]},{4:38,6:3,14:[2,46],15:[2,46],19:[2,46],29:[2,46],34:[2,46],44:[2,46],47:[2,46],48:[2,46],51:[2,46],55:[2,46],60:[2,46]},{15:[2,48],17:39,18:[2,48]},{20:41,56:40,64:42,65:[1,43],72:[1,35],78:26,79:27,80:[1,28],81:[1,29],82:[1,30],83:[1,31],84:[1,32],85:[1,34],86:33},{4:44,6:3,14:[2,46],15:[2,46],19:[2,46],29:[2,46],34:[2,46],47:[2,46],48:[2,46],51:[2,46],55:[2,46],60:[2,46]},{5:[2,10],14:[2,10],15:[2,10],18:[2,10],19:[2,10],29:[2,10],34:[2,10],39:[2,10],44:[2,10],47:[2,10],48:[2,10],51:[2,10],55:[2,10],60:[2,10]},{20:45,72:[1,35],78:26,79:27,80:[1,28],81:[1,29],82:[1,30],83:[1,31],84:[1,32],85:[1,34],86:33},{20:46,72:[1,35],78:26,79:27,80:[1,28],81:[1,29],82:[1,30],83:[1,31],84:[1,32],85:[1,34],86:33},{20:47,72:[1,35],78:26,79:27,80:[1,28],81:[1,29],82:[1,30],83:[1,31],84:[1,32],85:[1,34],86:33},{20:41,56:48,64:42,65:[1,43],72:[1,35],78:26,79:27,80:[1,28],81:[1,29],82:[1,30],83:[1,31],84:[1,32],85:[1,34],86:33},{33:[2,78],49:49,65:[2,78],72:[2,78],80:[2,78],81:[2,78],82:[2,78],83:[2,78],84:[2,78],85:[2,78]},{23:[2,33],33:[2,33],54:[2,33],65:[2,33],68:[2,33],72:[2,33],75:[2,33],80:[2,33],81:[2,33],82:[2,33],83:[2,33],84:[2,33],85:[2,33]},{23:[2,34],33:[2,34],54:[2,34],65:[2,34],68:[2,34],72:[2,34],75:[2,34],80:[2,34],81:[2,34],82:[2,34],83:[2,34],84:[2,34],85:[2,34]},{23:[2,35],33:[2,35],54:[2,35],65:[2,35],68:[2,35],72:[2,35],75:[2,35],80:[2,35],81:[2,35],82:[2,35],83:[2,35],84:[2,35],85:[2,35]},{23:[2,36],33:[2,36],54:[2,36],65:[2,36],68:[2,36],72:[2,36],75:[2,36],80:[2,36],81:[2,36],82:[2,36],83:[2,36],84:[2,36],85:[2,36]},{23:[2,37],33:[2,37],54:[2,37],65:[2,37],68:[2,37],72:[2,37],75:[2,37],80:[2,37],81:[2,37],82:[2,37],83:[2,37],84:[2,37],85:[2,37]},{23:[2,38],33:[2,38],54:[2,38],65:[2,38],68:[2,38],72:[2,38],75:[2,38],80:[2,38],81:[2,38],82:[2,38],83:[2,38],84:[2,38],85:[2,38]},{23:[2,39],33:[2,39],54:[2,39],65:[2,39],68:[2,39],72:[2,39],75:[2,39],80:[2,39],81:[2,39],82:[2,39],83:[2,39],84:[2,39],85:[2,39]},{23:[2,43],33:[2,43],54:[2,43],65:[2,43],68:[2,43],72:[2,43],75:[2,43],80:[2,43],81:[2,43],82:[2,43],83:[2,43],84:[2,43],85:[2,43],87:[1,50]},{72:[1,35],86:51},{23:[2,45],33:[2,45],54:[2,45],65:[2,45],68:[2,45],72:[2,45],75:[2,45],80:[2,45],81:[2,45],82:[2,45],83:[2,45],84:[2,45],85:[2,45],87:[2,45]},{52:52,54:[2,82],65:[2,82],72:[2,82],80:[2,82],81:[2,82],82:[2,82],83:[2,82],84:[2,82],85:[2,82]},{25:53,38:55,39:[1,57],43:56,44:[1,58],45:54,47:[2,54]},{28:59,43:60,44:[1,58],47:[2,56]},{13:62,15:[1,20],18:[1,61]},{33:[2,86],57:63,65:[2,86],72:[2,86],80:[2,86],81:[2,86],82:[2,86],83:[2,86],84:[2,86],85:[2,86]},{33:[2,40],65:[2,40],72:[2,40],80:[2,40],81:[2,40],82:[2,40],83:[2,40],84:[2,40],85:[2,40]},{33:[2,41],65:[2,41],72:[2,41],80:[2,41],81:[2,41],82:[2,41],83:[2,41],84:[2,41],85:[2,41]},{20:64,72:[1,35],78:26,79:27,80:[1,28],81:[1,29],82:[1,30],83:[1,31],84:[1,32],85:[1,34],86:33},{26:65,47:[1,66]},{30:67,33:[2,58],65:[2,58],72:[2,58],75:[2,58],80:[2,58],81:[2,58],82:[2,58],83:[2,58],84:[2,58],85:[2,58]},{33:[2,64],35:68,65:[2,64],72:[2,64],75:[2,64],80:[2,64],81:[2,64],82:[2,64],83:[2,64],84:[2,64],85:[2,64]},{21:69,23:[2,50],65:[2,50],72:[2,50],80:[2,50],81:[2,50],82:[2,50],83:[2,50],84:[2,50],85:[2,50]},{33:[2,90],61:70,65:[2,90],72:[2,90],80:[2,90],81:[2,90],82:[2,90],83:[2,90],84:[2,90],85:[2,90]},{20:74,33:[2,80],50:71,63:72,64:75,65:[1,43],69:73,70:76,71:77,72:[1,78],78:26,79:27,80:[1,28],81:[1,29],82:[1,30],83:[1,31],84:[1,32],85:[1,34],86:33},{72:[1,79]},{23:[2,42],33:[2,42],54:[2,42],65:[2,42],68:[2,42],72:[2,42],75:[2,42],80:[2,42],81:[2,42],82:[2,42],83:[2,42],84:[2,42],85:[2,42],87:[1,50]},{20:74,53:80,54:[2,84],63:81,64:75,65:[1,43],69:82,70:76,71:77,72:[1,78],78:26,79:27,80:[1,28],81:[1,29],82:[1,30],83:[1,31],84:[1,32],85:[1,34],86:33},{26:83,47:[1,66]},{47:[2,55]},{4:84,6:3,14:[2,46],15:[2,46],19:[2,46],29:[2,46],34:[2,46],39:[2,46],44:[2,46],47:[2,46],48:[2,46],51:[2,46],55:[2,46],60:[2,46]},{47:[2,20]},{20:85,72:[1,35],78:26,79:27,80:[1,28],81:[1,29],82:[1,30],83:[1,31],84:[1,32],85:[1,34],86:33},{4:86,6:3,14:[2,46],15:[2,46],19:[2,46],29:[2,46],34:[2,46],47:[2,46],48:[2,46],51:[2,46],55:[2,46],60:[2,46]},{26:87,47:[1,66]},{47:[2,57]},{5:[2,11],14:[2,11],15:[2,11],19:[2,11],29:[2,11],34:[2,11],39:[2,11],44:[2,11],47:[2,11],48:[2,11],51:[2,11],55:[2,11],60:[2,11]},{15:[2,49],18:[2,49]},{20:74,33:[2,88],58:88,63:89,64:75,65:[1,43],69:90,70:76,71:77,72:[1,78],78:26,79:27,80:[1,28],81:[1,29],82:[1,30],83:[1,31],84:[1,32],85:[1,34],86:33},{65:[2,94],66:91,68:[2,94],72:[2,94],80:[2,94],81:[2,94],82:[2,94],83:[2,94],84:[2,94],85:[2,94]},{5:[2,25],14:[2,25],15:[2,25],19:[2,25],29:[2,25],34:[2,25],39:[2,25],44:[2,25],47:[2,25],48:[2,25],51:[2,25],55:[2,25],60:[2,25]},{20:92,72:[1,35],78:26,79:27,80:[1,28],81:[1,29],82:[1,30],83:[1,31],84:[1,32],85:[1,34],86:33},{20:74,31:93,33:[2,60],63:94,64:75,65:[1,43],69:95,70:76,71:77,72:[1,78],75:[2,60],78:26,79:27,80:[1,28],81:[1,29],82:[1,30],83:[1,31],84:[1,32],85:[1,34],86:33},{20:74,33:[2,66],36:96,63:97,64:75,65:[1,43],69:98,70:76,71:77,72:[1,78],75:[2,66],78:26,79:27,80:[1,28],81:[1,29],82:[1,30],83:[1,31],84:[1,32],85:[1,34],86:33},{20:74,22:99,23:[2,52],63:100,64:75,65:[1,43],69:101,70:76,71:77,72:[1,78],78:26,79:27,80:[1,28],81:[1,29],82:[1,30],83:[1,31],84:[1,32],85:[1,34],86:33},{20:74,33:[2,92],62:102,63:103,64:75,65:[1,43],69:104,70:76,71:77,72:[1,78],78:26,79:27,80:[1,28],81:[1,29],82:[1,30],83:[1,31],84:[1,32],85:[1,34],86:33},{33:[1,105]},{33:[2,79],65:[2,79],72:[2,79],80:[2,79],81:[2,79],82:[2,79],83:[2,79],84:[2,79],85:[2,79]},{33:[2,81]},{23:[2,27],33:[2,27],54:[2,27],65:[2,27],68:[2,27],72:[2,27],75:[2,27],80:[2,27],81:[2,27],82:[2,27],83:[2,27],84:[2,27],85:[2,27]},{23:[2,28],33:[2,28],54:[2,28],65:[2,28],68:[2,28],72:[2,28],75:[2,28],80:[2,28],81:[2,28],82:[2,28],83:[2,28],84:[2,28],85:[2,28]},{23:[2,30],33:[2,30],54:[2,30],68:[2,30],71:106,72:[1,107],75:[2,30]},{23:[2,98],33:[2,98],54:[2,98],68:[2,98],72:[2,98],75:[2,98]},{23:[2,45],33:[2,45],54:[2,45],65:[2,45],68:[2,45],72:[2,45],73:[1,108],75:[2,45],80:[2,45],81:[2,45],82:[2,45],83:[2,45],84:[2,45],85:[2,45],87:[2,45]},{23:[2,44],33:[2,44],54:[2,44],65:[2,44],68:[2,44],72:[2,44],75:[2,44],80:[2,44],81:[2,44],82:[2,44],83:[2,44],84:[2,44],85:[2,44],87:[2,44]},{54:[1,109]},{54:[2,83],65:[2,83],72:[2,83],80:[2,83],81:[2,83],82:[2,83],83:[2,83],84:[2,83],85:[2,83]},{54:[2,85]},{5:[2,13],14:[2,13],15:[2,13],19:[2,13],29:[2,13],34:[2,13],39:[2,13],44:[2,13],47:[2,13],48:[2,13],51:[2,13],55:[2,13],60:[2,13]},{38:55,39:[1,57],43:56,44:[1,58],45:111,46:110,47:[2,76]},{33:[2,70],40:112,65:[2,70],72:[2,70],75:[2,70],80:[2,70],81:[2,70],82:[2,70],83:[2,70],84:[2,70],85:[2,70]},{47:[2,18]},{5:[2,14],14:[2,14],15:[2,14],19:[2,14],29:[2,14],34:[2,14],39:[2,14],44:[2,14],47:[2,14],48:[2,14],51:[2,14],55:[2,14],60:[2,14]},{33:[1,113]},{33:[2,87],65:[2,87],72:[2,87],80:[2,87],81:[2,87],82:[2,87],83:[2,87],84:[2,87],85:[2,87]},{33:[2,89]},{20:74,63:115,64:75,65:[1,43],67:114,68:[2,96],69:116,70:76,71:77,72:[1,78],78:26,79:27,80:[1,28],81:[1,29],82:[1,30],83:[1,31],84:[1,32],85:[1,34],86:33},{33:[1,117]},{32:118,33:[2,62],74:119,75:[1,120]},{33:[2,59],65:[2,59],72:[2,59],75:[2,59],80:[2,59],81:[2,59],82:[2,59],83:[2,59],84:[2,59],85:[2,59]},{33:[2,61],75:[2,61]},{33:[2,68],37:121,74:122,75:[1,120]},{33:[2,65],65:[2,65],72:[2,65],75:[2,65],80:[2,65],81:[2,65],82:[2,65],83:[2,65],84:[2,65],85:[2,65]},{33:[2,67],75:[2,67]},{23:[1,123]},{23:[2,51],65:[2,51],72:[2,51],80:[2,51],81:[2,51],82:[2,51],83:[2,51],84:[2,51],85:[2,51]},{23:[2,53]},{33:[1,124]},{33:[2,91],65:[2,91],72:[2,91],80:[2,91],81:[2,91],82:[2,91],83:[2,91],84:[2,91],85:[2,91]},{33:[2,93]},{5:[2,22],14:[2,22],15:[2,22],19:[2,22],29:[2,22],34:[2,22],39:[2,22],44:[2,22],47:[2,22],48:[2,22],51:[2,22],55:[2,22],60:[2,22]},{23:[2,99],33:[2,99],54:[2,99],68:[2,99],72:[2,99],75:[2,99]},{73:[1,108]},{20:74,63:125,64:75,65:[1,43],72:[1,35],78:26,79:27,80:[1,28],81:[1,29],82:[1,30],83:[1,31],84:[1,32],85:[1,34],86:33},{5:[2,23],14:[2,23],15:[2,23],19:[2,23],29:[2,23],34:[2,23],39:[2,23],44:[2,23],47:[2,23],48:[2,23],51:[2,23],55:[2,23],60:[2,23]},{47:[2,19]},{47:[2,77]},{20:74,33:[2,72],41:126,63:127,64:75,65:[1,43],69:128,70:76,71:77,72:[1,78],75:[2,72],78:26,79:27,80:[1,28],81:[1,29],82:[1,30],83:[1,31],84:[1,32],85:[1,34],86:33},{5:[2,24],14:[2,24],15:[2,24],19:[2,24],29:[2,24],34:[2,24],39:[2,24],44:[2,24],47:[2,24],48:[2,24],51:[2,24],55:[2,24],60:[2,24]},{68:[1,129]},{65:[2,95],68:[2,95],72:[2,95],80:[2,95],81:[2,95],82:[2,95],83:[2,95],84:[2,95],85:[2,95]},{68:[2,97]},{5:[2,21],14:[2,21],15:[2,21],19:[2,21],29:[2,21],34:[2,21],39:[2,21],44:[2,21],47:[2,21],48:[2,21],51:[2,21],55:[2,21],60:[2,21]},{33:[1,130]},{33:[2,63]},{72:[1,132],76:131},{33:[1,133]},{33:[2,69]},{15:[2,12],18:[2,12]},{14:[2,26],15:[2,26],19:[2,26],29:[2,26],34:[2,26],47:[2,26],48:[2,26],51:[2,26],55:[2,26],60:[2,26]},{23:[2,31],33:[2,31],54:[2,31],68:[2,31],72:[2,31],75:[2,31]},{33:[2,74],42:134,74:135,75:[1,120]},{33:[2,71],65:[2,71],72:[2,71],75:[2,71],80:[2,71],81:[2,71],82:[2,71],83:[2,71],84:[2,71],85:[2,71]},{33:[2,73],75:[2,73]},{23:[2,29],33:[2,29],54:[2,29],65:[2,29],68:[2,29],72:[2,29],75:[2,29],80:[2,29],81:[2,29],82:[2,29],83:[2,29],84:[2,29],85:[2,29]},{14:[2,15],15:[2,15],19:[2,15],29:[2,15],34:[2,15],39:[2,15],44:[2,15],47:[2,15],48:[2,15],51:[2,15],55:[2,15],60:[2,15]},{72:[1,137],77:[1,136]},{72:[2,100],77:[2,100]},{14:[2,16],15:[2,16],19:[2,16],29:[2,16],34:[2,16],44:[2,16],47:[2,16],48:[2,16],51:[2,16],55:[2,16],60:[2,16]},{33:[1,138]},{33:[2,75]},{33:[2,32]},{72:[2,101],77:[2,101]},{14:[2,17],15:[2,17],19:[2,17],29:[2,17],34:[2,17],39:[2,17],44:[2,17],47:[2,17],48:[2,17],51:[2,17],55:[2,17],60:[2,17]}],defaultActions:{4:[2,1],54:[2,55],56:[2,20],60:[2,57],73:[2,81],82:[2,85],86:[2,18],90:[2,89],101:[2,53],104:[2,93],110:[2,19],111:[2,77],116:[2,97],119:[2,63],122:[2,69],135:[2,75],136:[2,32]},parseError:function(a,b){throw new Error(a)},parse:function(a){function b(){var a;return a=c.lexer.lex()||1,"number"!=typeof a&&(a=c.symbols_[a]||a),a}var c=this,d=[0],e=[null],f=[],g=this.table,h="",i=0,j=0,k=0;this.lexer.setInput(a),this.lexer.yy=this.yy,this.yy.lexer=this.lexer,this.yy.parser=this,"undefined"==typeof this.lexer.yylloc&&(this.lexer.yylloc={});var l=this.lexer.yylloc;f.push(l);var m=this.lexer.options&&this.lexer.options.ranges;"function"==typeof this.yy.parseError&&(this.parseError=this.yy.parseError);for(var n,o,p,q,r,s,t,u,v,w={};;){if(p=d[d.length-1],this.defaultActions[p]?q=this.defaultActions[p]:(null!==n&&"undefined"!=typeof n||(n=b()),q=g[p]&&g[p][n]),"undefined"==typeof q||!q.length||!q[0]){var x="";if(!k){v=[];for(s in g[p])this.terminals_[s]&&s>2&&v.push("'"+this.terminals_[s]+"'");x=this.lexer.showPosition?"Parse error on line "+(i+1)+":\n"+this.lexer.showPosition()+"\nExpecting "+v.join(", ")+", got '"+(this.terminals_[n]||n)+"'":"Parse error on line "+(i+1)+": Unexpected "+(1==n?"end of input":"'"+(this.terminals_[n]||n)+"'"),this.parseError(x,{text:this.lexer.match,token:this.terminals_[n]||n,line:this.lexer.yylineno,loc:l,expected:v})}}if(q[0]instanceof Array&&q.length>1)throw new Error("Parse Error: multiple actions possible at state: "+p+", token: "+n);switch(q[0]){case 1:d.push(n),e.push(this.lexer.yytext),f.push(this.lexer.yylloc),d.push(q[1]),n=null,o?(n=o,o=null):(j=this.lexer.yyleng,h=this.lexer.yytext,i=this.lexer.yylineno,l=this.lexer.yylloc,k>0&&k--);break;case 2:if(t=this.productions_[q[1]][1],w.$=e[e.length-t],w._$={first_line:f[f.length-(t||1)].first_line,last_line:f[f.length-1].last_line,first_column:f[f.length-(t||1)].first_column,last_column:f[f.length-1].last_column},m&&(w._$.range=[f[f.length-(t||1)].range[0],f[f.length-1].range[1]]),r=this.performAction.call(w,h,j,i,this.yy,q[1],e,f),"undefined"!=typeof r)return r;t&&(d=d.slice(0,-1*t*2),e=e.slice(0,-1*t),f=f.slice(0,-1*t)),d.push(this.productions_[q[1]][0]),e.push(w.$),f.push(w._$),u=g[d[d.length-2]][d[d.length-1]],d.push(u);break;case 3:return!0}}return!0}},c=function(){var a={EOF:1,parseError:function(a,b){if(!this.yy.parser)throw new Error(a);this.yy.parser.parseError(a,b)},setInput:function(a){return this._input=a,this._more=this._less=this.done=!1,this.yylineno=this.yyleng=0,this.yytext=this.matched=this.match="",this.conditionStack=["INITIAL"],this.yylloc={first_line:1,first_column:0,last_line:1,last_column:0},this.options.ranges&&(this.yylloc.range=[0,0]),this.offset=0,this},input:function(){var a=this._input[0];this.yytext+=a,this.yyleng++,this.offset++,this.match+=a,this.matched+=a;var b=a.match(/(?:\r\n?|\n).*/g);return b?(this.yylineno++,this.yylloc.last_line++):this.yylloc.last_column++,this.options.ranges&&this.yylloc.range[1]++,this._input=this._input.slice(1),a},unput:function(a){var b=a.length,c=a.split(/(?:\r\n?|\n)/g);this._input=a+this._input,this.yytext=this.yytext.substr(0,this.yytext.length-b-1),this.offset-=b;var d=this.match.split(/(?:\r\n?|\n)/g);this.match=this.match.substr(0,this.match.length-1),this.matched=this.matched.substr(0,this.matched.length-1),c.length-1&&(this.yylineno-=c.length-1);var e=this.yylloc.range;return this.yylloc={first_line:this.yylloc.first_line,last_line:this.yylineno+1,first_column:this.yylloc.first_column,last_column:c?(c.length===d.length?this.yylloc.first_column:0)+d[d.length-c.length].length-c[0].length:this.yylloc.first_column-b},this.options.ranges&&(this.yylloc.range=[e[0],e[0]+this.yyleng-b]),this},more:function(){return this._more=!0,this},less:function(a){this.unput(this.match.slice(a))},pastInput:function(){var a=this.matched.substr(0,this.matched.length-this.match.length);return(a.length>20?"...":"")+a.substr(-20).replace(/\n/g,"")},upcomingInput:function(){var a=this.match;return a.length<20&&(a+=this._input.substr(0,20-a.length)),(a.substr(0,20)+(a.length>20?"...":"")).replace(/\n/g,"")},showPosition:function(){var a=this.pastInput(),b=new Array(a.length+1).join("-");return a+this.upcomingInput()+"\n"+b+"^"},next:function(){if(this.done)return this.EOF;this._input||(this.done=!0);var a,b,c,d,e;this._more||(this.yytext="",this.match="");for(var f=this._currentRules(),g=0;g<f.length&&(c=this._input.match(this.rules[f[g]]),!c||b&&!(c[0].length>b[0].length)||(b=c,d=g,this.options.flex));g++);return b?(e=b[0].match(/(?:\r\n?|\n).*/g),e&&(this.yylineno+=e.length),this.yylloc={first_line:this.yylloc.last_line,last_line:this.yylineno+1,first_column:this.yylloc.last_column,last_column:e?e[e.length-1].length-e[e.length-1].match(/\r?\n?/)[0].length:this.yylloc.last_column+b[0].length},this.yytext+=b[0],this.match+=b[0],this.matches=b,this.yyleng=this.yytext.length,this.options.ranges&&(this.yylloc.range=[this.offset,this.offset+=this.yyleng]),this._more=!1,this._input=this._input.slice(b[0].length),this.matched+=b[0],a=this.performAction.call(this,this.yy,this,f[d],this.conditionStack[this.conditionStack.length-1]),this.done&&this._input&&(this.done=!1),a?a:void 0):""===this._input?this.EOF:this.parseError("Lexical error on line "+(this.yylineno+1)+". Unrecognized text.\n"+this.showPosition(),{text:"",token:null,line:this.yylineno})},lex:function(){var a=this.next();return"undefined"!=typeof a?a:this.lex()},begin:function(a){this.conditionStack.push(a)},popState:function(){return this.conditionStack.pop()},_currentRules:function(){return this.conditions[this.conditionStack[this.conditionStack.length-1]].rules},topState:function(){return this.conditionStack[this.conditionStack.length-2]},pushState:function(a){this.begin(a)}};return a.options={},a.performAction=function(a,b,c,d){function e(a,c){return b.yytext=b.yytext.substring(a,b.yyleng-c+a)}switch(c){case 0:if("\\\\"===b.yytext.slice(-2)?(e(0,1),this.begin("mu")):"\\"===b.yytext.slice(-1)?(e(0,1),this.begin("emu")):this.begin("mu"),b.yytext)return 15;break;case 1:return 15;case 2:return this.popState(),15;case 3:return this.begin("raw"),15;case 4:return this.popState(),"raw"===this.conditionStack[this.conditionStack.length-1]?15:(e(5,9),"END_RAW_BLOCK");case 5:return 15;case 6:return this.popState(),14;case 7:return 65;case 8:return 68;case 9:return 19;case 10:return this.popState(),this.begin("raw"),23;case 11:return 55;case 12:return 60;case 13:return 29;case 14:return 47;case 15:return this.popState(),44;case 16:return this.popState(),44;case 17:return 34;case 18:return 39;case 19:return 51;case 20:return 48;case 21:this.unput(b.yytext),this.popState(),this.begin("com");break;case 22:return this.popState(),14;case 23:return 48;case 24:return 73;case 25:return 72;case 26:return 72;case 27:return 87;case 28:break;case 29:return this.popState(),54;case 30:return this.popState(),33;case 31:return b.yytext=e(1,2).replace(/\\"/g,'"'),80;case 32:return b.yytext=e(1,2).replace(/\\'/g,"'"),80;case 33:return 85;case 34:return 82;case 35:return 82;case 36:return 83;case 37:return 84;case 38:return 81;case 39:return 75;case 40:return 77;case 41:return 72;case 42:return b.yytext=b.yytext.replace(/\\([\\\]])/g,"$1"),72;case 43:return"INVALID";case 44:return 5}},a.rules=[/^(?:[^\x00]*?(?=(\{\{)))/,/^(?:[^\x00]+)/,/^(?:[^\x00]{2,}?(?=(\{\{|\\\{\{|\\\\\{\{|$)))/,/^(?:\{\{\{\{(?=[^/]))/,/^(?:\{\{\{\{\/[^\s!"#%-,\.\/;->@\[-\^`\{-~]+(?=[=}\s\/.])\}\}\}\})/,/^(?:[^\x00]+?(?=(\{\{\{\{)))/,/^(?:[\s\S]*?--(~)?\}\})/,/^(?:\()/,/^(?:\))/,/^(?:\{\{\{\{)/,/^(?:\}\}\}\})/,/^(?:\{\{(~)?>)/,/^(?:\{\{(~)?#>)/,/^(?:\{\{(~)?#\*?)/,/^(?:\{\{(~)?\/)/,/^(?:\{\{(~)?\^\s*(~)?\}\})/,/^(?:\{\{(~)?\s*else\s*(~)?\}\})/,/^(?:\{\{(~)?\^)/,/^(?:\{\{(~)?\s*else\b)/,/^(?:\{\{(~)?\{)/,/^(?:\{\{(~)?&)/,/^(?:\{\{(~)?!--)/,/^(?:\{\{(~)?![\s\S]*?\}\})/,/^(?:\{\{(~)?\*?)/,/^(?:=)/,/^(?:\.\.)/,/^(?:\.(?=([=~}\s\/.)|])))/,/^(?:[\/.])/,/^(?:\s+)/,/^(?:\}(~)?\}\})/,/^(?:(~)?\}\})/,/^(?:"(\\["]|[^"])*")/,/^(?:'(\\[']|[^'])*')/,/^(?:@)/,/^(?:true(?=([~}\s)])))/,/^(?:false(?=([~}\s)])))/,/^(?:undefined(?=([~}\s)])))/,/^(?:null(?=([~}\s)])))/,/^(?:-?[0-9]+(?:\.[0-9]+)?(?=([~}\s)])))/,/^(?:as\s+\|)/,/^(?:\|)/,/^(?:([^\s!"#%-,\.\/;->@\[-\^`\{-~]+(?=([=~}\s\/.)|]))))/,/^(?:\[(\\\]|[^\]])*\])/,/^(?:.)/,/^(?:$)/],a.conditions={mu:{rules:[7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44],inclusive:!1},emu:{rules:[2],inclusive:!1},com:{rules:[6],inclusive:!1},raw:{rules:[3,4,5],inclusive:!1},INITIAL:{rules:[0,1,44],inclusive:!0}},a}();return b.lexer=c,a.prototype=b,b.Parser=a,new a}();b["default"]=c,a.exports=b["default"]},function(a,b,c){"use strict";function d(){var a=arguments.length<=0||void 0===arguments[0]?{}:arguments[0];this.options=a}function e(a,b,c){void 0===b&&(b=a.length);var d=a[b-1],e=a[b-2];return d?"ContentStatement"===d.type?(e||!c?/\r?\n\s*?$/:/(^|\r?\n)\s*?$/).test(d.original):void 0:c}function f(a,b,c){void 0===b&&(b=-1);var d=a[b+1],e=a[b+2];return d?"ContentStatement"===d.type?(e||!c?/^\s*?\r?\n/:/^\s*?(\r?\n|$)/).test(d.original):void 0:c}function g(a,b,c){var d=a[null==b?0:b+1];if(d&&"ContentStatement"===d.type&&(c||!d.rightStripped)){var e=d.value;d.value=d.value.replace(c?/^\s+/:/^[ \t]*\r?\n?/,""),d.rightStripped=d.value!==e}}function h(a,b,c){var d=a[null==b?a.length-1:b-1];if(d&&"ContentStatement"===d.type&&(c||!d.leftStripped)){var e=d.value;return d.value=d.value.replace(c?/\s+$/:/[ \t]+$/,""),d.leftStripped=d.value!==e,d.leftStripped}}var i=c(1)["default"];b.__esModule=!0;var j=c(88),k=i(j);d.prototype=new k["default"],d.prototype.Program=function(a){var b=!this.options.ignoreStandalone,c=!this.isRootSeen;this.isRootSeen=!0;for(var d=a.body,i=0,j=d.length;i<j;i++){var k=d[i],l=this.accept(k);if(l){var m=e(d,i,c),n=f(d,i,c),o=l.openStandalone&&m,p=l.closeStandalone&&n,q=l.inlineStandalone&&m&&n;l.close&&g(d,i,!0),l.open&&h(d,i,!0),b&&q&&(g(d,i),h(d,i)&&"PartialStatement"===k.type&&(k.indent=/([ \t]+$)/.exec(d[i-1].original)[1])),b&&o&&(g((k.program||k.inverse).body),h(d,i)),b&&p&&(g(d,i),h((k.inverse||k.program).body))}}return a},d.prototype.BlockStatement=d.prototype.DecoratorBlock=d.prototype.PartialBlockStatement=function(a){this.accept(a.program),this.accept(a.inverse);var b=a.program||a.inverse,c=a.program&&a.inverse,d=c,i=c;if(c&&c.chained)for(d=c.body[0].program;i.chained;)i=i.body[i.body.length-1].program;var j={open:a.openStrip.open,close:a.closeStrip.close,openStandalone:f(b.body),closeStandalone:e((d||b).body)};if(a.openStrip.close&&g(b.body,null,!0),c){var k=a.inverseStrip;k.open&&h(b.body,null,!0),k.close&&g(d.body,null,!0),a.closeStrip.open&&h(i.body,null,!0),!this.options.ignoreStandalone&&e(b.body)&&f(d.body)&&(h(b.body),g(d.body))}else a.closeStrip.open&&h(b.body,null,!0);return j},d.prototype.Decorator=d.prototype.MustacheStatement=function(a){return a.strip},d.prototype.PartialStatement=d.prototype.CommentStatement=function(a){var b=a.strip||{};return{inlineStandalone:!0,open:b.open,close:b.close}},b["default"]=d,a.exports=b["default"]},function(a,b,c){"use strict";function d(){this.parents=[]}function e(a){this.acceptRequired(a,"path"),this.acceptArray(a.params),this.acceptKey(a,"hash")}function f(a){e.call(this,a),this.acceptKey(a,"program"),this.acceptKey(a,"inverse")}function g(a){this.acceptRequired(a,"name"),this.acceptArray(a.params),this.acceptKey(a,"hash")}var h=c(1)["default"];b.__esModule=!0;var i=c(6),j=h(i);d.prototype={constructor:d,mutating:!1,acceptKey:function(a,b){var c=this.accept(a[b]);if(this.mutating){if(c&&!d.prototype[c.type])throw new j["default"]('Unexpected node type "'+c.type+'" found when accepting '+b+" on "+a.type);a[b]=c}},acceptRequired:function(a,b){if(this.acceptKey(a,b),!a[b])throw new j["default"](a.type+" requires "+b)},acceptArray:function(a){for(var b=0,c=a.length;b<c;b++)this.acceptKey(a,b),a[b]||(a.splice(b,1),b--,c--)},accept:function(a){if(a){if(!this[a.type])throw new j["default"]("Unknown type: "+a.type,a);this.current&&this.parents.unshift(this.current),this.current=a;var b=this[a.type](a);return this.current=this.parents.shift(),!this.mutating||b?b:b!==!1?a:void 0}},Program:function(a){this.acceptArray(a.body)},MustacheStatement:e,Decorator:e,BlockStatement:f,DecoratorBlock:f,PartialStatement:g,PartialBlockStatement:function(a){g.call(this,a),this.acceptKey(a,"program")},ContentStatement:function(){},CommentStatement:function(){},SubExpression:e,PathExpression:function(){},StringLiteral:function(){},NumberLiteral:function(){},BooleanLiteral:function(){},UndefinedLiteral:function(){},NullLiteral:function(){},Hash:function(a){this.acceptArray(a.pairs)},HashPair:function(a){this.acceptRequired(a,"value")}},b["default"]=d,a.exports=b["default"]},function(a,b,c){"use strict";function d(a,b){if(b=b.path?b.path.original:b,a.path.original!==b){var c={loc:a.path.loc};throw new q["default"](a.path.original+" doesn't match "+b,c)}}function e(a,b){this.source=a,this.start={line:b.first_line,column:b.first_column},this.end={line:b.last_line,column:b.last_column}}function f(a){return/^\[.*\]$/.test(a)?a.substring(1,a.length-1):a}function g(a,b){return{open:"~"===a.charAt(2),close:"~"===b.charAt(b.length-3)}}function h(a){return a.replace(/^\{\{~?!-?-?/,"").replace(/-?-?~?\}\}$/,"")}function i(a,b,c){c=this.locInfo(c);for(var d=a?"@":"",e=[],f=0,g=0,h=b.length;g<h;g++){var i=b[g].part,j=b[g].original!==i;if(d+=(b[g].separator||"")+i,j||".."!==i&&"."!==i&&"this"!==i)e.push(i);else{if(e.length>0)throw new q["default"]("Invalid path: "+d,{loc:c});".."===i&&f++}}return{type:"PathExpression",data:a,depth:f,parts:e,original:d,loc:c}}function j(a,b,c,d,e,f){var g=d.charAt(3)||d.charAt(2),h="{"!==g&&"&"!==g,i=/\*/.test(d);return{type:i?"Decorator":"MustacheStatement",path:a,params:b,hash:c,escaped:h,strip:e,loc:this.locInfo(f)}}function k(a,b,c,e){d(a,c),e=this.locInfo(e);var f={type:"Program",body:b,strip:{},loc:e};return{type:"BlockStatement",path:a.path,params:a.params,hash:a.hash,program:f,openStrip:{},inverseStrip:{},closeStrip:{},loc:e}}function l(a,b,c,e,f,g){e&&e.path&&d(a,e);var h=/\*/.test(a.open);b.blockParams=a.blockParams;var i=void 0,j=void 0;if(c){if(h)throw new q["default"]("Unexpected inverse block on decorator",c);c.chain&&(c.program.body[0].closeStrip=e.strip),j=c.strip,i=c.program}return f&&(f=i,i=b,b=f),{type:h?"DecoratorBlock":"BlockStatement",path:a.path,params:a.params,hash:a.hash,program:b,inverse:i,openStrip:a.strip,inverseStrip:j,closeStrip:e&&e.strip,loc:this.locInfo(g)}}function m(a,b){if(!b&&a.length){var c=a[0].loc,d=a[a.length-1].loc;c&&d&&(b={source:c.source,start:{line:c.start.line,column:c.start.column},end:{line:d.end.line,column:d.end.column}})}return{type:"Program",body:a,strip:{},loc:b}}function n(a,b,c,e){return d(a,c),{type:"PartialBlockStatement",name:a.path,params:a.params,hash:a.hash,program:b,openStrip:a.strip,closeStrip:c&&c.strip,loc:this.locInfo(e)}}var o=c(1)["default"];b.__esModule=!0,b.SourceLocation=e,b.id=f,b.stripFlags=g,b.stripComment=h,b.preparePath=i,b.prepareMustache=j,b.prepareRawBlock=k,b.prepareBlock=l,b.prepareProgram=m,b.preparePartialBlock=n;var p=c(6),q=o(p)},function(a,b,c){"use strict";function d(){}function e(a,b,c){if(null==a||"string"!=typeof a&&"Program"!==a.type)throw new l["default"]("You must pass a string or Handlebars AST to Handlebars.precompile. You passed "+a);b=b||{},"data"in b||(b.data=!0),b.compat&&(b.useDepths=!0);var d=c.parse(a,b),e=(new c.Compiler).compile(d,b);return(new c.JavaScriptCompiler).compile(e,b)}function f(a,b,c){function d(){var d=c.parse(a,b),e=(new c.Compiler).compile(d,b),f=(new c.JavaScriptCompiler).compile(e,b,void 0,!0);return c.template(f)}function e(a,b){return f||(f=d()),f.call(this,a,b)}if(void 0===b&&(b={}),null==a||"string"!=typeof a&&"Program"!==a.type)throw new l["default"]("You must pass a string or Handlebars AST to Handlebars.compile. You passed "+a);b=m.extend({},b),"data"in b||(b.data=!0),b.compat&&(b.useDepths=!0);var f=void 0;return e._setup=function(a){return f||(f=d()),f._setup(a)},e._child=function(a,b,c,e){return f||(f=d()),f._child(a,b,c,e)},e}function g(a,b){if(a===b)return!0;if(m.isArray(a)&&m.isArray(b)&&a.length===b.length){for(var c=0;c<a.length;c++)if(!g(a[c],b[c]))return!1;return!0}}function h(a){if(!a.path.parts){var b=a.path;a.path={type:"PathExpression",data:!1,depth:0,parts:[b.original+""],original:b.original+"",loc:b.loc}}}var i=c(74)["default"],j=c(1)["default"];b.__esModule=!0,b.Compiler=d,b.precompile=e,b.compile=f;var k=c(6),l=j(k),m=c(5),n=c(84),o=j(n),p=[].slice;d.prototype={compiler:d,equals:function(a){var b=this.opcodes.length;if(a.opcodes.length!==b)return!1;for(var c=0;c<b;c++){var d=this.opcodes[c],e=a.opcodes[c];if(d.opcode!==e.opcode||!g(d.args,e.args))return!1}b=this.children.length;for(var c=0;c<b;c++)if(!this.children[c].equals(a.children[c]))return!1;
return!0},guid:0,compile:function(a,b){return this.sourceNode=[],this.opcodes=[],this.children=[],this.options=b,this.stringParams=b.stringParams,this.trackIds=b.trackIds,b.blockParams=b.blockParams||[],b.knownHelpers=m.extend(i(null),{helperMissing:!0,blockHelperMissing:!0,each:!0,"if":!0,unless:!0,"with":!0,log:!0,lookup:!0},b.knownHelpers),this.accept(a)},compileProgram:function(a){var b=new this.compiler,c=b.compile(a,this.options),d=this.guid++;return this.usePartial=this.usePartial||c.usePartial,this.children[d]=c,this.useDepths=this.useDepths||c.useDepths,d},accept:function(a){if(!this[a.type])throw new l["default"]("Unknown type: "+a.type,a);this.sourceNode.unshift(a);var b=this[a.type](a);return this.sourceNode.shift(),b},Program:function(a){this.options.blockParams.unshift(a.blockParams);for(var b=a.body,c=b.length,d=0;d<c;d++)this.accept(b[d]);return this.options.blockParams.shift(),this.isSimple=1===c,this.blockParams=a.blockParams?a.blockParams.length:0,this},BlockStatement:function(a){h(a);var b=a.program,c=a.inverse;b=b&&this.compileProgram(b),c=c&&this.compileProgram(c);var d=this.classifySexpr(a);"helper"===d?this.helperSexpr(a,b,c):"simple"===d?(this.simpleSexpr(a),this.opcode("pushProgram",b),this.opcode("pushProgram",c),this.opcode("emptyHash"),this.opcode("blockValue",a.path.original)):(this.ambiguousSexpr(a,b,c),this.opcode("pushProgram",b),this.opcode("pushProgram",c),this.opcode("emptyHash"),this.opcode("ambiguousBlockValue")),this.opcode("append")},DecoratorBlock:function(a){var b=a.program&&this.compileProgram(a.program),c=this.setupFullMustacheParams(a,b,void 0),d=a.path;this.useDecorators=!0,this.opcode("registerDecorator",c.length,d.original)},PartialStatement:function(a){this.usePartial=!0;var b=a.program;b&&(b=this.compileProgram(a.program));var c=a.params;if(c.length>1)throw new l["default"]("Unsupported number of partial arguments: "+c.length,a);c.length||(this.options.explicitPartialContext?this.opcode("pushLiteral","undefined"):c.push({type:"PathExpression",parts:[],depth:0}));var d=a.name.original,e="SubExpression"===a.name.type;e&&this.accept(a.name),this.setupFullMustacheParams(a,b,void 0,!0);var f=a.indent||"";this.options.preventIndent&&f&&(this.opcode("appendContent",f),f=""),this.opcode("invokePartial",e,d,f),this.opcode("append")},PartialBlockStatement:function(a){this.PartialStatement(a)},MustacheStatement:function(a){this.SubExpression(a),a.escaped&&!this.options.noEscape?this.opcode("appendEscaped"):this.opcode("append")},Decorator:function(a){this.DecoratorBlock(a)},ContentStatement:function(a){a.value&&this.opcode("appendContent",a.value)},CommentStatement:function(){},SubExpression:function(a){h(a);var b=this.classifySexpr(a);"simple"===b?this.simpleSexpr(a):"helper"===b?this.helperSexpr(a):this.ambiguousSexpr(a)},ambiguousSexpr:function(a,b,c){var d=a.path,e=d.parts[0],f=null!=b||null!=c;this.opcode("getContext",d.depth),this.opcode("pushProgram",b),this.opcode("pushProgram",c),d.strict=!0,this.accept(d),this.opcode("invokeAmbiguous",e,f)},simpleSexpr:function(a){var b=a.path;b.strict=!0,this.accept(b),this.opcode("resolvePossibleLambda")},helperSexpr:function(a,b,c){var d=this.setupFullMustacheParams(a,b,c),e=a.path,f=e.parts[0];if(this.options.knownHelpers[f])this.opcode("invokeKnownHelper",d.length,f);else{if(this.options.knownHelpersOnly)throw new l["default"]("You specified knownHelpersOnly, but used the unknown helper "+f,a);e.strict=!0,e.falsy=!0,this.accept(e),this.opcode("invokeHelper",d.length,e.original,o["default"].helpers.simpleId(e))}},PathExpression:function(a){this.addDepth(a.depth),this.opcode("getContext",a.depth);var b=a.parts[0],c=o["default"].helpers.scopedId(a),d=!a.depth&&!c&&this.blockParamIndex(b);d?this.opcode("lookupBlockParam",d,a.parts):b?a.data?(this.options.data=!0,this.opcode("lookupData",a.depth,a.parts,a.strict)):this.opcode("lookupOnContext",a.parts,a.falsy,a.strict,c):this.opcode("pushContext")},StringLiteral:function(a){this.opcode("pushString",a.value)},NumberLiteral:function(a){this.opcode("pushLiteral",a.value)},BooleanLiteral:function(a){this.opcode("pushLiteral",a.value)},UndefinedLiteral:function(){this.opcode("pushLiteral","undefined")},NullLiteral:function(){this.opcode("pushLiteral","null")},Hash:function(a){var b=a.pairs,c=0,d=b.length;for(this.opcode("pushHash");c<d;c++)this.pushParam(b[c].value);for(;c--;)this.opcode("assignToHash",b[c].key);this.opcode("popHash")},opcode:function(a){this.opcodes.push({opcode:a,args:p.call(arguments,1),loc:this.sourceNode[0].loc})},addDepth:function(a){a&&(this.useDepths=!0)},classifySexpr:function(a){var b=o["default"].helpers.simpleId(a.path),c=b&&!!this.blockParamIndex(a.path.parts[0]),d=!c&&o["default"].helpers.helperExpression(a),e=!c&&(d||b);if(e&&!d){var f=a.path.parts[0],g=this.options;g.knownHelpers[f]?d=!0:g.knownHelpersOnly&&(e=!1)}return d?"helper":e?"ambiguous":"simple"},pushParams:function(a){for(var b=0,c=a.length;b<c;b++)this.pushParam(a[b])},pushParam:function(a){var b=null!=a.value?a.value:a.original||"";if(this.stringParams)b.replace&&(b=b.replace(/^(\.?\.\/)*/g,"").replace(/\//g,".")),a.depth&&this.addDepth(a.depth),this.opcode("getContext",a.depth||0),this.opcode("pushStringParam",b,a.type),"SubExpression"===a.type&&this.accept(a);else{if(this.trackIds){var c=void 0;if(!a.parts||o["default"].helpers.scopedId(a)||a.depth||(c=this.blockParamIndex(a.parts[0])),c){var d=a.parts.slice(1).join(".");this.opcode("pushId","BlockParam",c,d)}else b=a.original||b,b.replace&&(b=b.replace(/^this(?:\.|$)/,"").replace(/^\.\//,"").replace(/^\.$/,"")),this.opcode("pushId",a.type,b)}this.accept(a)}},setupFullMustacheParams:function(a,b,c,d){var e=a.params;return this.pushParams(e),this.opcode("pushProgram",b),this.opcode("pushProgram",c),a.hash?this.accept(a.hash):this.opcode("emptyHash",d),e},blockParamIndex:function(a){for(var b=0,c=this.options.blockParams.length;b<c;b++){var d=this.options.blockParams[b],e=d&&m.indexOf(d,a);if(d&&e>=0)return[b,e]}}}},function(a,b,c){"use strict";function d(a){this.value=a}function e(){}function f(a,b,c,d,e){var f=b.popStack(),g=c.length;for(a&&g--;d<g;d++)f=b.nameLookup(f,c[d],e);return a?[b.aliasable("container.strict"),"(",f,", ",b.quotedString(c[d]),", ",JSON.stringify(b.source.currentLocation)," )"]:f}var g=c(60)["default"],h=c(1)["default"];b.__esModule=!0;var i=c(4),j=c(6),k=h(j),l=c(5),m=c(92),n=h(m);e.prototype={nameLookup:function(a,b){return this.internalNameLookup(a,b)},depthedLookup:function(a){return[this.aliasable("container.lookup"),"(depths, ",JSON.stringify(a),")"]},compilerInfo:function(){var a=i.COMPILER_REVISION,b=i.REVISION_CHANGES[a];return[a,b]},appendToBuffer:function(a,b,c){return l.isArray(a)||(a=[a]),a=this.source.wrap(a,b),this.environment.isSimple?["return ",a,";"]:c?["buffer += ",a,";"]:(a.appendToBuffer=!0,a)},initializeBuffer:function(){return this.quotedString("")},internalNameLookup:function(a,b){return this.lookupPropertyFunctionIsUsed=!0,["lookupProperty(",a,",",JSON.stringify(b),")"]},lookupPropertyFunctionIsUsed:!1,compile:function(a,b,c,d){this.environment=a,this.options=b,this.stringParams=this.options.stringParams,this.trackIds=this.options.trackIds,this.precompile=!d,this.name=this.environment.name,this.isChild=!!c,this.context=c||{decorators:[],programs:[],environments:[]},this.preamble(),this.stackSlot=0,this.stackVars=[],this.aliases={},this.registers={list:[]},this.hashes=[],this.compileStack=[],this.inlineStack=[],this.blockParams=[],this.compileChildren(a,b),this.useDepths=this.useDepths||a.useDepths||a.useDecorators||this.options.compat,this.useBlockParams=this.useBlockParams||a.useBlockParams;var e=a.opcodes,f=void 0,g=void 0,h=void 0,i=void 0;for(h=0,i=e.length;h<i;h++)f=e[h],this.source.currentLocation=f.loc,g=g||f.loc,this[f.opcode].apply(this,f.args);if(this.source.currentLocation=g,this.pushSource(""),this.stackSlot||this.inlineStack.length||this.compileStack.length)throw new k["default"]("Compile completed with content left on stack");this.decorators.isEmpty()?this.decorators=void 0:(this.useDecorators=!0,this.decorators.prepend(["var decorators = container.decorators, ",this.lookupPropertyFunctionVarDeclaration(),";\n"]),this.decorators.push("return fn;"),d?this.decorators=Function.apply(this,["fn","props","container","depth0","data","blockParams","depths",this.decorators.merge()]):(this.decorators.prepend("function(fn, props, container, depth0, data, blockParams, depths) {\n"),this.decorators.push("}\n"),this.decorators=this.decorators.merge()));var j=this.createFunctionContext(d);if(this.isChild)return j;var l={compiler:this.compilerInfo(),main:j};this.decorators&&(l.main_d=this.decorators,l.useDecorators=!0);var m=this.context,n=m.programs,o=m.decorators;for(h=0,i=n.length;h<i;h++)n[h]&&(l[h]=n[h],o[h]&&(l[h+"_d"]=o[h],l.useDecorators=!0));return this.environment.usePartial&&(l.usePartial=!0),this.options.data&&(l.useData=!0),this.useDepths&&(l.useDepths=!0),this.useBlockParams&&(l.useBlockParams=!0),this.options.compat&&(l.compat=!0),d?l.compilerOptions=this.options:(l.compiler=JSON.stringify(l.compiler),this.source.currentLocation={start:{line:1,column:0}},l=this.objectLiteral(l),b.srcName?(l=l.toStringWithSourceMap({file:b.destName}),l.map=l.map&&l.map.toString()):l=l.toString()),l},preamble:function(){this.lastContext=0,this.source=new n["default"](this.options.srcName),this.decorators=new n["default"](this.options.srcName)},createFunctionContext:function(a){var b=this,c="",d=this.stackVars.concat(this.registers.list);d.length>0&&(c+=", "+d.join(", "));var e=0;g(this.aliases).forEach(function(a){var d=b.aliases[a];d.children&&d.referenceCount>1&&(c+=", alias"+ ++e+"="+a,d.children[0]="alias"+e)}),this.lookupPropertyFunctionIsUsed&&(c+=", "+this.lookupPropertyFunctionVarDeclaration());var f=["container","depth0","helpers","partials","data"];(this.useBlockParams||this.useDepths)&&f.push("blockParams"),this.useDepths&&f.push("depths");var h=this.mergeSource(c);return a?(f.push(h),Function.apply(this,f)):this.source.wrap(["function(",f.join(","),") {\n  ",h,"}"])},mergeSource:function(a){var b=this.environment.isSimple,c=!this.forceBuffer,d=void 0,e=void 0,f=void 0,g=void 0;return this.source.each(function(a){a.appendToBuffer?(f?a.prepend("  + "):f=a,g=a):(f&&(e?f.prepend("buffer += "):d=!0,g.add(";"),f=g=void 0),e=!0,b||(c=!1))}),c?f?(f.prepend("return "),g.add(";")):e||this.source.push('return "";'):(a+=", buffer = "+(d?"":this.initializeBuffer()),f?(f.prepend("return buffer + "),g.add(";")):this.source.push("return buffer;")),a&&this.source.prepend("var "+a.substring(2)+(d?"":";\n")),this.source.merge()},lookupPropertyFunctionVarDeclaration:function(){return"\n      lookupProperty = container.lookupProperty || function(parent, propertyName) {\n        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {\n          return parent[propertyName];\n        }\n        return undefined\n    }\n    ".trim()},blockValue:function(a){var b=this.aliasable("container.hooks.blockHelperMissing"),c=[this.contextName(0)];this.setupHelperArgs(a,0,c);var d=this.popStack();c.splice(1,0,d),this.push(this.source.functionCall(b,"call",c))},ambiguousBlockValue:function(){var a=this.aliasable("container.hooks.blockHelperMissing"),b=[this.contextName(0)];this.setupHelperArgs("",0,b,!0),this.flushInline();var c=this.topStack();b.splice(1,0,c),this.pushSource(["if (!",this.lastHelper,") { ",c," = ",this.source.functionCall(a,"call",b),"}"])},appendContent:function(a){this.pendingContent?a=this.pendingContent+a:this.pendingLocation=this.source.currentLocation,this.pendingContent=a},append:function(){if(this.isInline())this.replaceStack(function(a){return[" != null ? ",a,' : ""']}),this.pushSource(this.appendToBuffer(this.popStack()));else{var a=this.popStack();this.pushSource(["if (",a," != null) { ",this.appendToBuffer(a,void 0,!0)," }"]),this.environment.isSimple&&this.pushSource(["else { ",this.appendToBuffer("''",void 0,!0)," }"])}},appendEscaped:function(){this.pushSource(this.appendToBuffer([this.aliasable("container.escapeExpression"),"(",this.popStack(),")"]))},getContext:function(a){this.lastContext=a},pushContext:function(){this.pushStackLiteral(this.contextName(this.lastContext))},lookupOnContext:function(a,b,c,d){var e=0;d||!this.options.compat||this.lastContext?this.pushContext():this.push(this.depthedLookup(a[e++])),this.resolvePath("context",a,e,b,c)},lookupBlockParam:function(a,b){this.useBlockParams=!0,this.push(["blockParams[",a[0],"][",a[1],"]"]),this.resolvePath("context",b,1)},lookupData:function(a,b,c){a?this.pushStackLiteral("container.data(data, "+a+")"):this.pushStackLiteral("data"),this.resolvePath("data",b,0,!0,c)},resolvePath:function(a,b,c,d,e){var g=this;if(this.options.strict||this.options.assumeObjects)return void this.push(f(this.options.strict&&e,this,b,c,a));for(var h=b.length;c<h;c++)this.replaceStack(function(e){var f=g.nameLookup(e,b[c],a);return d?[" && ",f]:[" != null ? ",f," : ",e]})},resolvePossibleLambda:function(){this.push([this.aliasable("container.lambda"),"(",this.popStack(),", ",this.contextName(0),")"])},pushStringParam:function(a,b){this.pushContext(),this.pushString(b),"SubExpression"!==b&&("string"==typeof a?this.pushString(a):this.pushStackLiteral(a))},emptyHash:function(a){this.trackIds&&this.push("{}"),this.stringParams&&(this.push("{}"),this.push("{}")),this.pushStackLiteral(a?"undefined":"{}")},pushHash:function(){this.hash&&this.hashes.push(this.hash),this.hash={values:{},types:[],contexts:[],ids:[]}},popHash:function(){var a=this.hash;this.hash=this.hashes.pop(),this.trackIds&&this.push(this.objectLiteral(a.ids)),this.stringParams&&(this.push(this.objectLiteral(a.contexts)),this.push(this.objectLiteral(a.types))),this.push(this.objectLiteral(a.values))},pushString:function(a){this.pushStackLiteral(this.quotedString(a))},pushLiteral:function(a){this.pushStackLiteral(a)},pushProgram:function(a){null!=a?this.pushStackLiteral(this.programExpression(a)):this.pushStackLiteral(null)},registerDecorator:function(a,b){var c=this.nameLookup("decorators",b,"decorator"),d=this.setupHelperArgs(b,a);this.decorators.push(["fn = ",this.decorators.functionCall(c,"",["fn","props","container",d])," || fn;"])},invokeHelper:function(a,b,c){var d=this.popStack(),e=this.setupHelper(a,b),f=[];c&&f.push(e.name),f.push(d),this.options.strict||f.push(this.aliasable("container.hooks.helperMissing"));var g=["(",this.itemsSeparatedBy(f,"||"),")"],h=this.source.functionCall(g,"call",e.callParams);this.push(h)},itemsSeparatedBy:function(a,b){var c=[];c.push(a[0]);for(var d=1;d<a.length;d++)c.push(b,a[d]);return c},invokeKnownHelper:function(a,b){var c=this.setupHelper(a,b);this.push(this.source.functionCall(c.name,"call",c.callParams))},invokeAmbiguous:function(a,b){this.useRegister("helper");var c=this.popStack();this.emptyHash();var d=this.setupHelper(0,a,b),e=this.lastHelper=this.nameLookup("helpers",a,"helper"),f=["(","(helper = ",e," || ",c,")"];this.options.strict||(f[0]="(helper = ",f.push(" != null ? helper : ",this.aliasable("container.hooks.helperMissing"))),this.push(["(",f,d.paramsInit?["),(",d.paramsInit]:[],"),","(typeof helper === ",this.aliasable('"function"')," ? ",this.source.functionCall("helper","call",d.callParams)," : helper))"])},invokePartial:function(a,b,c){var d=[],e=this.setupParams(b,1,d);a&&(b=this.popStack(),delete e.name),c&&(e.indent=JSON.stringify(c)),e.helpers="helpers",e.partials="partials",e.decorators="container.decorators",a?d.unshift(b):d.unshift(this.nameLookup("partials",b,"partial")),this.options.compat&&(e.depths="depths"),e=this.objectLiteral(e),d.push(e),this.push(this.source.functionCall("container.invokePartial","",d))},assignToHash:function(a){var b=this.popStack(),c=void 0,d=void 0,e=void 0;this.trackIds&&(e=this.popStack()),this.stringParams&&(d=this.popStack(),c=this.popStack());var f=this.hash;c&&(f.contexts[a]=c),d&&(f.types[a]=d),e&&(f.ids[a]=e),f.values[a]=b},pushId:function(a,b,c){"BlockParam"===a?this.pushStackLiteral("blockParams["+b[0]+"].path["+b[1]+"]"+(c?" + "+JSON.stringify("."+c):"")):"PathExpression"===a?this.pushString(b):"SubExpression"===a?this.pushStackLiteral("true"):this.pushStackLiteral("null")},compiler:e,compileChildren:function(a,b){for(var c=a.children,d=void 0,e=void 0,f=0,g=c.length;f<g;f++){d=c[f],e=new this.compiler;var h=this.matchExistingProgram(d);if(null==h){this.context.programs.push("");var i=this.context.programs.length;d.index=i,d.name="program"+i,this.context.programs[i]=e.compile(d,b,this.context,!this.precompile),this.context.decorators[i]=e.decorators,this.context.environments[i]=d,this.useDepths=this.useDepths||e.useDepths,this.useBlockParams=this.useBlockParams||e.useBlockParams,d.useDepths=this.useDepths,d.useBlockParams=this.useBlockParams}else d.index=h.index,d.name="program"+h.index,this.useDepths=this.useDepths||h.useDepths,this.useBlockParams=this.useBlockParams||h.useBlockParams}},matchExistingProgram:function(a){for(var b=0,c=this.context.environments.length;b<c;b++){var d=this.context.environments[b];if(d&&d.equals(a))return d}},programExpression:function(a){var b=this.environment.children[a],c=[b.index,"data",b.blockParams];return(this.useBlockParams||this.useDepths)&&c.push("blockParams"),this.useDepths&&c.push("depths"),"container.program("+c.join(", ")+")"},useRegister:function(a){this.registers[a]||(this.registers[a]=!0,this.registers.list.push(a))},push:function(a){return a instanceof d||(a=this.source.wrap(a)),this.inlineStack.push(a),a},pushStackLiteral:function(a){this.push(new d(a))},pushSource:function(a){this.pendingContent&&(this.source.push(this.appendToBuffer(this.source.quotedString(this.pendingContent),this.pendingLocation)),this.pendingContent=void 0),a&&this.source.push(a)},replaceStack:function(a){var b=["("],c=void 0,e=void 0,f=void 0;if(!this.isInline())throw new k["default"]("replaceStack on non-inline");var g=this.popStack(!0);if(g instanceof d)c=[g.value],b=["(",c],f=!0;else{e=!0;var h=this.incrStack();b=["((",this.push(h)," = ",g,")"],c=this.topStack()}var i=a.call(this,c);f||this.popStack(),e&&this.stackSlot--,this.push(b.concat(i,")"))},incrStack:function(){return this.stackSlot++,this.stackSlot>this.stackVars.length&&this.stackVars.push("stack"+this.stackSlot),this.topStackName()},topStackName:function(){return"stack"+this.stackSlot},flushInline:function(){var a=this.inlineStack;this.inlineStack=[];for(var b=0,c=a.length;b<c;b++){var e=a[b];if(e instanceof d)this.compileStack.push(e);else{var f=this.incrStack();this.pushSource([f," = ",e,";"]),this.compileStack.push(f)}}},isInline:function(){return this.inlineStack.length},popStack:function(a){var b=this.isInline(),c=(b?this.inlineStack:this.compileStack).pop();if(!a&&c instanceof d)return c.value;if(!b){if(!this.stackSlot)throw new k["default"]("Invalid stack pop");this.stackSlot--}return c},topStack:function(){var a=this.isInline()?this.inlineStack:this.compileStack,b=a[a.length-1];return b instanceof d?b.value:b},contextName:function(a){return this.useDepths&&a?"depths["+a+"]":"depth"+a},quotedString:function(a){return this.source.quotedString(a)},objectLiteral:function(a){return this.source.objectLiteral(a)},aliasable:function(a){var b=this.aliases[a];return b?(b.referenceCount++,b):(b=this.aliases[a]=this.source.wrap(a),b.aliasable=!0,b.referenceCount=1,b)},setupHelper:function(a,b,c){var d=[],e=this.setupHelperArgs(b,a,d,c),f=this.nameLookup("helpers",b,"helper"),g=this.aliasable(this.contextName(0)+" != null ? "+this.contextName(0)+" : (container.nullContext || {})");return{params:d,paramsInit:e,name:f,callParams:[g].concat(d)}},setupParams:function(a,b,c){var d={},e=[],f=[],g=[],h=!c,i=void 0;h&&(c=[]),d.name=this.quotedString(a),d.hash=this.popStack(),this.trackIds&&(d.hashIds=this.popStack()),this.stringParams&&(d.hashTypes=this.popStack(),d.hashContexts=this.popStack());var j=this.popStack(),k=this.popStack();(k||j)&&(d.fn=k||"container.noop",d.inverse=j||"container.noop");for(var l=b;l--;)i=this.popStack(),c[l]=i,this.trackIds&&(g[l]=this.popStack()),this.stringParams&&(f[l]=this.popStack(),e[l]=this.popStack());return h&&(d.args=this.source.generateArray(c)),this.trackIds&&(d.ids=this.source.generateArray(g)),this.stringParams&&(d.types=this.source.generateArray(f),d.contexts=this.source.generateArray(e)),this.options.data&&(d.data="data"),this.useBlockParams&&(d.blockParams="blockParams"),d},setupHelperArgs:function(a,b,c,d){var e=this.setupParams(a,b,c);return e.loc=JSON.stringify(this.source.currentLocation),e=this.objectLiteral(e),d?(this.useRegister("options"),c.push("options"),["options=",e]):c?(c.push(e),""):e}},function(){for(var a="break else new var case finally return void catch for switch while continue function this with default if throw delete in try do instanceof typeof abstract enum int short boolean export interface static byte extends long super char final native synchronized class float package throws const goto private transient debugger implements protected volatile double import public let yield await null true false".split(" "),b=e.RESERVED_WORDS={},c=0,d=a.length;c<d;c++)b[a[c]]=!0}(),e.isValidJavaScriptVariableName=function(a){return!e.RESERVED_WORDS[a]&&/^[a-zA-Z_$][0-9a-zA-Z_$]*$/.test(a)},b["default"]=e,a.exports=b["default"]},function(a,b,c){"use strict";function d(a,b,c){if(g.isArray(a)){for(var d=[],e=0,f=a.length;e<f;e++)d.push(b.wrap(a[e],c));return d}return"boolean"==typeof a||"number"==typeof a?a+"":a}function e(a){this.srcFile=a,this.source=[]}var f=c(60)["default"];b.__esModule=!0;var g=c(5),h=void 0;try{}catch(i){}h||(h=function(a,b,c,d){this.src="",d&&this.add(d)},h.prototype={add:function(a){g.isArray(a)&&(a=a.join("")),this.src+=a},prepend:function(a){g.isArray(a)&&(a=a.join("")),this.src=a+this.src},toStringWithSourceMap:function(){return{code:this.toString()}},toString:function(){return this.src}}),e.prototype={isEmpty:function(){return!this.source.length},prepend:function(a,b){this.source.unshift(this.wrap(a,b))},push:function(a,b){this.source.push(this.wrap(a,b))},merge:function(){var a=this.empty();return this.each(function(b){a.add(["  ",b,"\n"])}),a},each:function(a){for(var b=0,c=this.source.length;b<c;b++)a(this.source[b])},empty:function(){var a=this.currentLocation||{start:{}};return new h(a.start.line,a.start.column,this.srcFile)},wrap:function(a){var b=arguments.length<=1||void 0===arguments[1]?this.currentLocation||{start:{}}:arguments[1];return a instanceof h?a:(a=d(a,this,b),new h(b.start.line,b.start.column,this.srcFile,a))},functionCall:function(a,b,c){return c=this.generateList(c),this.wrap([a,b?"."+b+"(":"(",c,")"])},quotedString:function(a){return'"'+(a+"").replace(/\\/g,"\\\\").replace(/"/g,'\\"').replace(/\n/g,"\\n").replace(/\r/g,"\\r").replace(/\u2028/g,"\\u2028").replace(/\u2029/g,"\\u2029")+'"'},objectLiteral:function(a){var b=this,c=[];f(a).forEach(function(e){var f=d(a[e],b);"undefined"!==f&&c.push([b.quotedString(e),":",f])});var e=this.generateList(c);return e.prepend("{"),e.add("}"),e},generateList:function(a){for(var b=this.empty(),c=0,e=a.length;c<e;c++)c&&b.add(","),b.add(d(a[c],this));return b},generateArray:function(a){var b=this.generateList(a);return b.prepend("["),b.add("]"),b}},b["default"]=e,a.exports=b["default"]}])});

/**
 * [js-sha256]{@link https://github.com/emn178/js-sha256}
 *
 * @version 0.10.0
 * @author Chen, Yi-Cyuan [emn178@gmail.com]
 * @copyright Chen, Yi-Cyuan 2014-2023
 * @license MIT
 */
!function(){"use strict";function t(t,i){i?(d[0]=d[16]=d[1]=d[2]=d[3]=d[4]=d[5]=d[6]=d[7]=d[8]=d[9]=d[10]=d[11]=d[12]=d[13]=d[14]=d[15]=0,this.blocks=d):this.blocks=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],t?(this.h0=3238371032,this.h1=914150663,this.h2=812702999,this.h3=4144912697,this.h4=4290775857,this.h5=1750603025,this.h6=1694076839,this.h7=3204075428):(this.h0=1779033703,this.h1=3144134277,this.h2=1013904242,this.h3=2773480762,this.h4=1359893119,this.h5=2600822924,this.h6=528734635,this.h7=1541459225),this.block=this.start=this.bytes=this.hBytes=0,this.finalized=this.hashed=!1,this.first=!0,this.is224=t}function i(i,r,s){var e,n=typeof i;if("string"===n){var o,a=[],u=i.length,c=0;for(e=0;e<u;++e)(o=i.charCodeAt(e))<128?a[c++]=o:o<2048?(a[c++]=192|o>>6,a[c++]=128|63&o):o<55296||o>=57344?(a[c++]=224|o>>12,a[c++]=128|o>>6&63,a[c++]=128|63&o):(o=65536+((1023&o)<<10|1023&i.charCodeAt(++e)),a[c++]=240|o>>18,a[c++]=128|o>>12&63,a[c++]=128|o>>6&63,a[c++]=128|63&o);i=a}else{if("object"!==n)throw new Error(h);if(null===i)throw new Error(h);if(f&&i.constructor===ArrayBuffer)i=new Uint8Array(i);else if(!(Array.isArray(i)||f&&ArrayBuffer.isView(i)))throw new Error(h)}i.length>64&&(i=new t(r,!0).update(i).array());var y=[],p=[];for(e=0;e<64;++e){var l=i[e]||0;y[e]=92^l,p[e]=54^l}t.call(this,r,s),this.update(p),this.oKeyPad=y,this.inner=!0,this.sharedMemory=s}var h="input is invalid type",r="object"==typeof window,s=r?window:{};s.JS_SHA256_NO_WINDOW&&(r=!1);var e=!r&&"object"==typeof self,n=!s.JS_SHA256_NO_NODE_JS&&"object"==typeof process&&process.versions&&process.versions.node;n?s=global:e&&(s=self);var o=!s.JS_SHA256_NO_COMMON_JS&&"object"==typeof module&&module.exports,a="function"==typeof define&&define.amd,f=!s.JS_SHA256_NO_ARRAY_BUFFER&&"undefined"!=typeof ArrayBuffer,u="0123456789abcdef".split(""),c=[-2147483648,8388608,32768,128],y=[24,16,8,0],p=[1116352408,1899447441,3049323471,3921009573,961987163,1508970993,2453635748,2870763221,3624381080,310598401,607225278,1426881987,1925078388,2162078206,2614888103,3248222580,3835390401,4022224774,264347078,604807628,770255983,1249150122,1555081692,1996064986,2554220882,2821834349,2952996808,3210313671,3336571891,3584528711,113926993,338241895,666307205,773529912,1294757372,1396182291,1695183700,1986661051,2177026350,2456956037,2730485921,2820302411,3259730800,3345764771,3516065817,3600352804,4094571909,275423344,430227734,506948616,659060556,883997877,958139571,1322822218,1537002063,1747873779,1955562222,2024104815,2227730452,2361852424,2428436474,2756734187,3204031479,3329325298],l=["hex","array","digest","arrayBuffer"],d=[];!s.JS_SHA256_NO_NODE_JS&&Array.isArray||(Array.isArray=function(t){return"[object Array]"===Object.prototype.toString.call(t)}),!f||!s.JS_SHA256_NO_ARRAY_BUFFER_IS_VIEW&&ArrayBuffer.isView||(ArrayBuffer.isView=function(t){return"object"==typeof t&&t.buffer&&t.buffer.constructor===ArrayBuffer});var A=function(i,h){return function(r){return new t(h,!0).update(r)[i]()}},w=function(i){var h=A("hex",i);n&&(h=b(h,i)),h.create=function(){return new t(i)},h.update=function(t){return h.create().update(t)};for(var r=0;r<l.length;++r){var s=l[r];h[s]=A(s,i)}return h},b=function(t,i){var r,e=require("crypto"),n=require("buffer").Buffer,o=i?"sha224":"sha256";r=n.from&&!s.JS_SHA256_NO_BUFFER_FROM?n.from:function(t){return new n(t)};return function(i){if("string"==typeof i)return e.createHash(o).update(i,"utf8").digest("hex");if(null===i||void 0===i)throw new Error(h);return i.constructor===ArrayBuffer&&(i=new Uint8Array(i)),Array.isArray(i)||ArrayBuffer.isView(i)||i.constructor===n?e.createHash(o).update(r(i)).digest("hex"):t(i)}},_=function(t,h){return function(r,s){return new i(r,h,!0).update(s)[t]()}},v=function(t){var h=_("hex",t);h.create=function(h){return new i(h,t)},h.update=function(t,i){return h.create(t).update(i)};for(var r=0;r<l.length;++r){var s=l[r];h[s]=_(s,t)}return h};t.prototype.update=function(t){if(!this.finalized){var i,r=typeof t;if("string"!==r){if("object"!==r)throw new Error(h);if(null===t)throw new Error(h);if(f&&t.constructor===ArrayBuffer)t=new Uint8Array(t);else if(!(Array.isArray(t)||f&&ArrayBuffer.isView(t)))throw new Error(h);i=!0}for(var s,e,n=0,o=t.length,a=this.blocks;n<o;){if(this.hashed&&(this.hashed=!1,a[0]=this.block,a[16]=a[1]=a[2]=a[3]=a[4]=a[5]=a[6]=a[7]=a[8]=a[9]=a[10]=a[11]=a[12]=a[13]=a[14]=a[15]=0),i)for(e=this.start;n<o&&e<64;++n)a[e>>2]|=t[n]<<y[3&e++];else for(e=this.start;n<o&&e<64;++n)(s=t.charCodeAt(n))<128?a[e>>2]|=s<<y[3&e++]:s<2048?(a[e>>2]|=(192|s>>6)<<y[3&e++],a[e>>2]|=(128|63&s)<<y[3&e++]):s<55296||s>=57344?(a[e>>2]|=(224|s>>12)<<y[3&e++],a[e>>2]|=(128|s>>6&63)<<y[3&e++],a[e>>2]|=(128|63&s)<<y[3&e++]):(s=65536+((1023&s)<<10|1023&t.charCodeAt(++n)),a[e>>2]|=(240|s>>18)<<y[3&e++],a[e>>2]|=(128|s>>12&63)<<y[3&e++],a[e>>2]|=(128|s>>6&63)<<y[3&e++],a[e>>2]|=(128|63&s)<<y[3&e++]);this.lastByteIndex=e,this.bytes+=e-this.start,e>=64?(this.block=a[16],this.start=e-64,this.hash(),this.hashed=!0):this.start=e}return this.bytes>4294967295&&(this.hBytes+=this.bytes/4294967296<<0,this.bytes=this.bytes%4294967296),this}},t.prototype.finalize=function(){if(!this.finalized){this.finalized=!0;var t=this.blocks,i=this.lastByteIndex;t[16]=this.block,t[i>>2]|=c[3&i],this.block=t[16],i>=56&&(this.hashed||this.hash(),t[0]=this.block,t[16]=t[1]=t[2]=t[3]=t[4]=t[5]=t[6]=t[7]=t[8]=t[9]=t[10]=t[11]=t[12]=t[13]=t[14]=t[15]=0),t[14]=this.hBytes<<3|this.bytes>>>29,t[15]=this.bytes<<3,this.hash()}},t.prototype.hash=function(){var t,i,h,r,s,e,n,o,a,f=this.h0,u=this.h1,c=this.h2,y=this.h3,l=this.h4,d=this.h5,A=this.h6,w=this.h7,b=this.blocks;for(t=16;t<64;++t)i=((s=b[t-15])>>>7|s<<25)^(s>>>18|s<<14)^s>>>3,h=((s=b[t-2])>>>17|s<<15)^(s>>>19|s<<13)^s>>>10,b[t]=b[t-16]+i+b[t-7]+h<<0;for(a=u&c,t=0;t<64;t+=4)this.first?(this.is224?(e=300032,w=(s=b[0]-1413257819)-150054599<<0,y=s+24177077<<0):(e=704751109,w=(s=b[0]-210244248)-1521486534<<0,y=s+143694565<<0),this.first=!1):(i=(f>>>2|f<<30)^(f>>>13|f<<19)^(f>>>22|f<<10),r=(e=f&u)^f&c^a,w=y+(s=w+(h=(l>>>6|l<<26)^(l>>>11|l<<21)^(l>>>25|l<<7))+(l&d^~l&A)+p[t]+b[t])<<0,y=s+(i+r)<<0),i=(y>>>2|y<<30)^(y>>>13|y<<19)^(y>>>22|y<<10),r=(n=y&f)^y&u^e,A=c+(s=A+(h=(w>>>6|w<<26)^(w>>>11|w<<21)^(w>>>25|w<<7))+(w&l^~w&d)+p[t+1]+b[t+1])<<0,i=((c=s+(i+r)<<0)>>>2|c<<30)^(c>>>13|c<<19)^(c>>>22|c<<10),r=(o=c&y)^c&f^n,d=u+(s=d+(h=(A>>>6|A<<26)^(A>>>11|A<<21)^(A>>>25|A<<7))+(A&w^~A&l)+p[t+2]+b[t+2])<<0,i=((u=s+(i+r)<<0)>>>2|u<<30)^(u>>>13|u<<19)^(u>>>22|u<<10),r=(a=u&c)^u&y^o,l=f+(s=l+(h=(d>>>6|d<<26)^(d>>>11|d<<21)^(d>>>25|d<<7))+(d&A^~d&w)+p[t+3]+b[t+3])<<0,f=s+(i+r)<<0,this.chromeBugWorkAround=!0;this.h0=this.h0+f<<0,this.h1=this.h1+u<<0,this.h2=this.h2+c<<0,this.h3=this.h3+y<<0,this.h4=this.h4+l<<0,this.h5=this.h5+d<<0,this.h6=this.h6+A<<0,this.h7=this.h7+w<<0},t.prototype.hex=function(){this.finalize();var t=this.h0,i=this.h1,h=this.h2,r=this.h3,s=this.h4,e=this.h5,n=this.h6,o=this.h7,a=u[t>>28&15]+u[t>>24&15]+u[t>>20&15]+u[t>>16&15]+u[t>>12&15]+u[t>>8&15]+u[t>>4&15]+u[15&t]+u[i>>28&15]+u[i>>24&15]+u[i>>20&15]+u[i>>16&15]+u[i>>12&15]+u[i>>8&15]+u[i>>4&15]+u[15&i]+u[h>>28&15]+u[h>>24&15]+u[h>>20&15]+u[h>>16&15]+u[h>>12&15]+u[h>>8&15]+u[h>>4&15]+u[15&h]+u[r>>28&15]+u[r>>24&15]+u[r>>20&15]+u[r>>16&15]+u[r>>12&15]+u[r>>8&15]+u[r>>4&15]+u[15&r]+u[s>>28&15]+u[s>>24&15]+u[s>>20&15]+u[s>>16&15]+u[s>>12&15]+u[s>>8&15]+u[s>>4&15]+u[15&s]+u[e>>28&15]+u[e>>24&15]+u[e>>20&15]+u[e>>16&15]+u[e>>12&15]+u[e>>8&15]+u[e>>4&15]+u[15&e]+u[n>>28&15]+u[n>>24&15]+u[n>>20&15]+u[n>>16&15]+u[n>>12&15]+u[n>>8&15]+u[n>>4&15]+u[15&n];return this.is224||(a+=u[o>>28&15]+u[o>>24&15]+u[o>>20&15]+u[o>>16&15]+u[o>>12&15]+u[o>>8&15]+u[o>>4&15]+u[15&o]),a},t.prototype.toString=t.prototype.hex,t.prototype.digest=function(){this.finalize();var t=this.h0,i=this.h1,h=this.h2,r=this.h3,s=this.h4,e=this.h5,n=this.h6,o=this.h7,a=[t>>24&255,t>>16&255,t>>8&255,255&t,i>>24&255,i>>16&255,i>>8&255,255&i,h>>24&255,h>>16&255,h>>8&255,255&h,r>>24&255,r>>16&255,r>>8&255,255&r,s>>24&255,s>>16&255,s>>8&255,255&s,e>>24&255,e>>16&255,e>>8&255,255&e,n>>24&255,n>>16&255,n>>8&255,255&n];return this.is224||a.push(o>>24&255,o>>16&255,o>>8&255,255&o),a},t.prototype.array=t.prototype.digest,t.prototype.arrayBuffer=function(){this.finalize();var t=new ArrayBuffer(this.is224?28:32),i=new DataView(t);return i.setUint32(0,this.h0),i.setUint32(4,this.h1),i.setUint32(8,this.h2),i.setUint32(12,this.h3),i.setUint32(16,this.h4),i.setUint32(20,this.h5),i.setUint32(24,this.h6),this.is224||i.setUint32(28,this.h7),t},(i.prototype=new t).finalize=function(){if(t.prototype.finalize.call(this),this.inner){this.inner=!1;var i=this.array();t.call(this,this.is224,this.sharedMemory),this.update(this.oKeyPad),this.update(i),t.prototype.finalize.call(this)}};var B=w();B.sha256=B,B.sha224=w(!0),B.sha256.hmac=v(),B.sha224.hmac=v(!0),o?module.exports=B:(s.sha256=B.sha256,s.sha224=B.sha224,a&&define(function(){return B}))}();

/*! js-cookie v3.0.5 | MIT */
!function(e,t){"object"==typeof exports&&"undefined"!=typeof module?module.exports=t():"function"==typeof define&&define.amd?define(t):(e="undefined"!=typeof globalThis?globalThis:e||self,function(){var n=e.Cookies,o=e.Cookies=t();o.noConflict=function(){return e.Cookies=n,o}}())}(this,(function(){"use strict";function e(e){for(var t=1;t<arguments.length;t++){var n=arguments[t];for(var o in n)e[o]=n[o]}return e}var t=function t(n,o){function r(t,r,i){if("undefined"!=typeof document){"number"==typeof(i=e({},o,i)).expires&&(i.expires=new Date(Date.now()+864e5*i.expires)),i.expires&&(i.expires=i.expires.toUTCString()),t=encodeURIComponent(t).replace(/%(2[346B]|5E|60|7C)/g,decodeURIComponent).replace(/[()]/g,escape);var c="";for(var u in i)i[u]&&(c+="; "+u,!0!==i[u]&&(c+="="+i[u].split(";")[0]));return document.cookie=t+"="+n.write(r,t)+c}}return Object.create({set:r,get:function(e){if("undefined"!=typeof document&&(!arguments.length||e)){for(var t=document.cookie?document.cookie.split("; "):[],o={},r=0;r<t.length;r++){var i=t[r].split("="),c=i.slice(1).join("=");try{var u=decodeURIComponent(i[0]);if(o[u]=n.read(c,u),e===u)break}catch(e){}}return e?o[e]:o}},remove:function(t,n){r(t,"",e({},n,{expires:-1}))},withAttributes:function(n){return t(this.converter,e({},this.attributes,n))},withConverter:function(n){return t(e({},this.converter,n),this.attributes)}},{attributes:{value:Object.freeze(o)},converter:{value:Object.freeze(n)}})}({read:function(e){return'"'===e[0]&&(e=e.slice(1,-1)),e.replace(/(%[\dA-F]{2})+/gi,decodeURIComponent)},write:function(e){return encodeURIComponent(e).replace(/%(2[346BF]|3[AC-F]|40|5[BDE]|60|7[BCD])/g,decodeURIComponent)}},{path:"/"});return t}));

jQuery(function() {
	initOpenPdfInNewTab();
	initProductsFiltration();
	initVideo();
	initTabs();
	initRemoveBlock();
	initFiltering();
	initSlickCarousel();
	initStickyScrollBlock();
	initFancybox();
	initMobileNav();
	initOpenClose();
	initFieldsSwitcher();
	initOfferForm();
	initRegistration();
	initInputMask();
	initCheckedClasses();
	initAnchors();
	initSearchForms();
	initStickyClass();
	initTouchDevice();
	initCustomForms();
	initFilteringModal();
	initSwitchModalText();
	initTooltip();
	initAjaxForm();
	initRemoveEmptyItems();
	initCopyToClipboard();
	initUpdateFavorite();
	initUnlockSavings();
	initTaxModal();
	initSpinPopup();
	initPriorityNav();
	initSwitchLogos();
	initStretchIframe();
	initHideInfoButton();
	initEditModal();
	initConfirmDeleteModal();

	jQuery('.payment-info .btn.btn-primary').on('click', function(e) {
		e.preventDefault();
	});

	ButtonLocker.init('[data-clear]', 60000);
});

jQuery(window).on('load', function() {
	jQuery('body').addClass('page-loaded');

	setTimeout(function() {
		jcf.refreshAll(jQuery('.jcf-scrollable'));
	}, 500);

	initCookieModal();
	updateJourneyField();
	initStickySummary();
});

window.addEventListener('popstate', (event) => {
	if (!sessionStorage.getItem('fromProductPage') && jQuery('.filter-section').length) {
		jQuery(window).trigger('goToPage');
	}
});

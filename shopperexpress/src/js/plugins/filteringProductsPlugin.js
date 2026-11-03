import Handlebars from 'handlebars';
import $ from 'jquery';
import './customFormsPlugin';
import './storageHistoryPlugin';

/* Filtering Products plugin */
(function ($) {
  function FilteringProducts(options) {
    this.options = $.extend(
      {
        container: '.items-holder',
        items: '.item',
        btnFilter: '[type="submit"]',
        searchField: '.search-panel [type="search"]',
        sortSelect: '[name="sort"]',
        perPageSelect: '[name="per_page"]',
        pagination: '.pagination',
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
        delay: 500,
      },
      options
    );

    if (this.options.holder) {
      this.init();
      this.makeCallback('onInit', this);
    }
  }

  FilteringProducts.prototype = {
    init: function () {
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
      this.perPageSelect = this.holder.find(this.options.perPageSelect);
      this.pagination = this.holder.find(this.options.pagination);
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
      this.cardsByPosition = {};
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
      this.defaultItemsPerPage = this.options.itemsPerPage;
      this.mileageKey = 'mileage';

      // Get extra filter type and value from holder data attributes
      this.extraFilterType = this.holder.data('key');
      this.extraFilterValue = this.holder.data('value');

      let vehiclesURL = this.holder.data('vehicles');

      if (vehiclesURL) {
        // The page shell can be served from cache, so the "loged" param baked into
        // data-vehicles may reflect whoever's visit generated the cache, not this
        // visitor. Override it with the real, current auth state from the cookie.
        // WordPress' own auth cookie is HttpOnly (unreadable here), so we read the
        // non-HttpOnly "wps_logged_in" mirror cookie instead (see theme-functions.php).
        const isLoggedIn = /(?:^|;\s*)wps_logged_in=1/.test(document.cookie);

        if (/([?&])loged=/.test(vehiclesURL)) {
          vehiclesURL = vehiclesURL.replace(/([?&])loged=[^&]*/, `$1loged=${isLoggedIn}`);
        } else {
          vehiclesURL += (vehiclesURL.indexOf('?') >= 0 ? '&' : '?') + `loged=${isLoggedIn}`;
        }

        this.holder.addClass(this.options.loadingClass);

        const sortType = getUrlAttr('sort').toLocaleLowerCase();

        if (sortType === 'custom') {
          // sort by custom tag
          if (vehiclesURL.indexOf('?') >= 0) {
            vehiclesURL += '&sort=custom';
          } else {
            vehiclesURL += '?sort=custom';
          }
        } else if (sortType === 'unique') {
          // sort by unique
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

        jQuery.getJSON(vehiclesURL, ({ vehicles, cards }) => {
          this.allProducts = vehicles;
          this.cardsByPosition = this.groupCardsByPosition(Array.isArray(cards) ? cards : []);

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
    initSlider: function () {
      if (!this.visualSlider.hasClass('slick-initialized')) {
        this.visualSlider.slick({
          slide: '[data-key-word]:not(.hidden)',
          slidesToScroll: 1,
          rows: 0,
          prevArrow:
            '<button class="slick-control slick-prev slick-arrow" aria-label="Previous"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="m432-480 156 156q11 11 11 28t-11 28q-11 11-28 11t-28-11L348-452q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l184-184q11-11 28-11t28 11q11 11 11 28t-11 28L432-480Z"/></svg></button>',
          nextArrow:
            '<button class="slick-control slick-next slick-arrow" aria-label="Next"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M504-480 348-636q-11-11-11-28t11-28q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L404-268q-11 11-28 11t-28-11q-11-11-11-28t11-28l156-156Z"/></svg></button>',
          dots: true,
          dotsClass: 'slick-dots',
          autoplay: true,
        });
      }
    },
    destroySlider: function () {
      if (this.visualSlider.hasClass('slick-initialized')) {
        this.visualSlider.slick('destroy');
      }
    },
    afterLoadData: function () {
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
              [key]: itemArr[1],
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
        // keep the default option if the select has no option for this value (e.g. "custom")
        const hasSortOption = this.sortSelect.find('option').filter((i, option) => option.value === getUrlAttr('sort')).length > 0;

        if (hasSortOption) {
          this.sortSelect.val(getUrlAttr('sort'));
        }

        jcf.refresh(this.sortSelect);
      }

      // apply items per page after page loaded
      this.applyPerPage(getUrlAttr('per_page'));

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
        this.renderPage(1);
      }
    },
    togglePaymentRangeVisibility: function () {
      const paymentRows = this.holder.find('.filter-row').has('[name="payment"]');

      if (!paymentRows.length) return;

      const hasPayment =
        Array.isArray(this.allProducts) &&
        this.allProducts.some((item) => {
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
    attachEvents: function () {
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

      this.perPageSelect.on('change', (e) => {
        if (this.ajaxBusy) {
          // revert select to the actual value
          this.perPageSelect.val(this.options.itemsPerPage);
          jcf.refresh(this.perPageSelect);

          return;
        }

        this.applyPerPage(this.perPageSelect.val());
        this.currentPage = 1;
        this.updateHistory($(e.target));
        this.filter();
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

      // restore page and items per page from URL on browser back/forward
      window.addEventListener('popstate', () => {
        // state is restored from sessionStorage after returning from a product page
        if (sessionStorage.getItem('fromProductPage')) return;

        // restoring state from URL must not add new history entries
        this.isSyncingFromUrl = true;

        this.applyPerPage(getUrlAttr('per_page'));
        this.renderPage(getUrlAttr('page'));

        this.isSyncingFromUrl = false;
      });
    },
    /**
     * Sets items per page from a value if it is one of the select options
     * (default otherwise) and syncs the select with the current value
     *
     * @param {string|number} value - Requested items per page
     */
    applyPerPage: function (value) {
      const perPage = parseInt(value, 10);
      const isAllowed = this.perPageSelect.find('option').filter((i, option) => parseInt(option.value, 10) === perPage).length > 0;

      this.hasPerPageParam = isAllowed;
      this.options.itemsPerPage = isAllowed ? perPage : this.defaultItemsPerPage;
      this.perPageSelect.val(this.options.itemsPerPage);

      jcf.refresh(this.perPageSelect);
    },
    /**
     * Limits page number to the range of available pages
     *
     * @param {string|number} page - Requested page number
     * @returns {number}
     */
    clampPage: function (page) {
      return Math.min(Math.max(parseInt(page, 10) || 1, 1), Math.max(this.totalPages, 1));
    },
    /**
     * Recalculates total pages and renders the given page of active items
     *
     * @param {string|number} page - Requested page number
     */
    renderPage: function (page) {
      const perPage = this.options.itemsPerPage;

      this.totalPages = Math.ceil(this.activeItems.length / perPage);
      this.currentPage = this.clampPage(page);
      this.count = (this.currentPage - 1) * perPage;

      this.loadItems(this.activeItems.slice(this.count, this.count + perPage));
    },
    prevPage: function () {
      if (this.ajaxBusy) return;

      this.renderPage(this.currentPage - 1);

      this.page.animate(
        {
          scrollTop: this.holder.offset().top,
        },
        500
      );
    },
    nextPage: function () {
      if (this.ajaxBusy) return;

      this.renderPage(this.currentPage + 1);

      this.page.animate(
        {
          scrollTop: this.holder.offset().top - this.header.outerHeight(),
        },
        500
      );
    },
    updatePagingButtonsState: function () {
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
    getAllFilters: function () {
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
              const obj = this.activeCheckboxCounts[property].find((o) => o.name === item.terms[property][i]);

              if (!obj) {
                this.activeCheckboxCounts[property].push({
                  name: item.terms[property][i],
                  slug: item.terms[property][i].toLowerCase(),
                  count: 1,
                });
              } else {
                obj.count = obj.count + 1;
              }
            }
          }
        }

        if (!this.isItemsLoaded) {
          if (item.price) {
            if (!this.rangesMinMaxValues.value) {
              this.rangesMinMaxValues.value = [];
            }

            if (item.price) {
              this.rangesMinMaxValues.value.push(item.price);
            }
          }

          if (item.payment) {
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
    createFilterCheckboxes: function () {
      this.filterLists.each((i, elem) => {
        const list = jQuery(elem);
        const filter = list.data('filter-category');
        const filterData = this.activeCheckboxCounts[filter];

        if (filterData && filterData.length) {
          for (let i = 0; i < filterData.length; i++) {
            const source = jQuery(this.filterTemplate).html();
            const template = Handlebars.compile(source);

            filterData[i].slug = filterData[i].slug.toLowerCase();

            list.append(
              template({
                ...filterData[i],
                filter,
              })
            );
          }
        }
      });

      this.filterCheckboxes = this.modalFilterHolder.find(':checkbox');
    },
    searchItems: function () {
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

      this.filterItems(value !== '' ? true : false);
    },

    /**
     * Check if all words in the wordsArr array are included in the terms object
     * @param {object} terms - object with terms to search
     * @param {array} wordsArr - array of search words
     * @returns {boolean} - if all words are included in the terms object
     */
    containsAllWords(terms, wordsArr) {
      const allValues = Object.entries(terms)
        .filter(([key]) => key !== 'dealer-special' && key !== 'dealer_special') // Exclude dealer special terms from keyword search
        .map(([, value]) => value)
        .flat() // Make a flat array of all values
        .map((value) => {
          return value.toString().toLowerCase();
        });

      return wordsArr.every((word) => {
        return allValues.some((value) => {
          return value.includes(word.toLowerCase());
        });
      });
    },

    filterItems: function (ifSearchValue) {
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
      if (!ifSearchValue && Object.keys(this.activeFilters).length > 0 && Object.keys(this.activeFilters).filter((key) => key.startsWith('model')).length) {
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

          if (key === 'value') {
            // Filter by price
            const textArr = +item.price;
            const arr = value[0].split(',');

            if (textArr !== undefined) {
              if (textArr >= +arr[0] && textArr <= +arr[1]) {
                matched = true;
              }
            }
          } else if (key === 'payment') {
            // Filter by payment
            const textArr = +item.payment;
            const arr = value[0].split(',');

            if (textArr !== undefined) {
              if (textArr >= +arr[0] && textArr <= +arr[1]) {
                matched = true;
              }
            }
          } else if (key === 'mileage') {
            // Filter by mileage
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

      this.sortItems();
      this.initSlider();
      this.renderPage(this.isGivenPage ? this.currentPage : 1);
    },
    sortItems: function () {
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
    /**
     * Groups an array of cards by their position in the final rendered list
     *
     * @param {Array} cards - The array of cards to group
     * @returns {Object} - A map where the keys are the positions and the values are arrays of card HTML
     */
    groupCardsByPosition: function (cards) {
      const map = {};

      for (let i = 0; i < cards.length; i++) {
        const card = cards[i];
        const position = parseInt(card && card.position, 10);

        if (!position || position < 1 || !card.html) continue;

        if (!map[position]) {
          map[position] = [];
        }

        map[position].push(card.html);
      }

      return map;
    },
    /**
     * Checks whether this final list position is occupied by one or more cards
     *
     * @param {number} position - The position in the final rendered list
     * @returns {boolean} - Whether the position is occupied by one or more cards
     */
    hasCardsAtPosition: function (position) {
      return Boolean(this.cardsByPosition[position] && this.cardsByPosition[position].length);
    },
    loadItems: function (items) {
      this.updateResultCounters();
      this.updateFilterValues();
      this.updatePagingButtonsState();

      if (this.activeItems.length === 0) {
        this.holder.addClass(this.options.noResultsClass);
      }

      this.ajaxBusy = true;
      this.holder.addClass(this.options.loadingClass);
      this.container.empty();

      const pageStartIndex = this.count || 0;
      let outputPosition = 1;
      let skippedProducts = 0;

      // Appends cards that belong to current final-list position
      const appendCardsAtPosition = () => {
        const cardsAtPosition = this.cardsByPosition[outputPosition];

        if (!cardsAtPosition || !cardsAtPosition.length) return;

        for (let j = 0; j < cardsAtPosition.length; j++) {
          jQuery(cardsAtPosition[j]).appendTo(this.container);
          outputPosition++;
        }
      };

      while (skippedProducts < pageStartIndex) {
        // Move through final-list positions until we skip required number of products
        if (!this.hasCardsAtPosition(outputPosition)) {
          skippedProducts++;
        }

        outputPosition++;
      }

      for (let i = 0; i < items.length; i++) {
        // Insert cards that belong to current final-list position before product
        while (this.hasCardsAtPosition(outputPosition)) appendCardsAtPosition();

        // Append items to container
        jQuery(items[i].html).appendTo(this.container);

        outputPosition++;
      }

      this.makeCallback('onLoadItems', this.container.find(this.options.items));

      setTimeout(() => {
        this.holder.removeClass(this.options.loadingClass);
        this.ajaxBusy = false;
      }, this.options.delay);
    },
    updateFilterStates: function (item, ifOnPageLoad) {
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
    updateFilterValues: function () {
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
    createFiltersList: function () {
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
            name: currCheckbox.val(),
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
            name: input.attr('name'),
          });
        }
      });

      let items = $();
      let btnClear = $();
      let counterItem = $();

      if (!this.filtersArr.length) return;

      for (let i = 0; i < this.filtersArr.length; i++) {
        const item = $(
          '<li><span>' +
            this.filtersArr[i].title +
            '</span><a href="#" class="remove"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M480-424 284-228q-11 11-28 11t-28-11q-11-11-11-28t11-28l196-196-196-196q-11-11-11-28t11-28q11-11 28-11t28 11l196 196 196-196q11-11 28-11t28 11q11 11 11 28t-11 28L536-480l196 196q11 11 11 28t-11 28q-11 11-28 11t-28-11L480-424Z"/></svg></a></li>'
        ).appendTo(this.selectedFiltersHolder);

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
    updateHistory: function (item, ifClear) {
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
    updateResultCounters: function () {
      // show/hide pagination (holder stays visible while per page select is useful)
      let isPagingNeeded = this.totalPages > 1;

      if (this.perPageSelect.length) {
        const minPerPage = Math.min.apply(
          null,
          this.perPageSelect
            .find('option')
            .map((i, option) => parseInt(option.value, 10))
            .get()
        );

        isPagingNeeded = isPagingNeeded || this.activeItems.length > minPerPage;
      }

      this.pagingHolder.toggleClass(this.options.hiddenClass, !isPagingNeeded);
      this.pagination.toggleClass(this.options.hiddenClass, this.totalPages <= 1);

      // total pages count
      this.totalPageCountField.text(this.totalPages);

      // current page count
      this.currPageCountField.text(this.currentPage);

      if (this.currentPage > 1) {
        window.StorageHistory.set('page', this.currentPage, false, this.isSyncingFromUrl);
      } else {
        window.StorageHistory.remove('page', undefined, this.isSyncingFromUrl);
      }

      // current showing results count
      if (this.activeItems.length === 0) {
        this.currResultsCount.text(0);
      } else {
        if (this.activeItems.length < this.count + this.options.itemsPerPage) {
          this.currResultsCount.text(this.count + 1 + '-' + this.activeItems.length);
        } else {
          this.currResultsCount.text(this.count + 1 + '-' + (this.count + this.options.itemsPerPage));
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
    resetForm: function (ifClearSearchField = true) {
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

      this.activeFilters = {};
      this.modalActiveFilters = {};
      this.modalBtnClear.attr('disabled', true);
      this.selectedFiltersHolder.empty();

      this.activeItems = this.allProducts;

      this.renderPage(1);

      if (window.history && window.history.pushState) {
        window.StorageHistory.reset();

        // keep chosen amount of vehicles per page in URL
        if (this.hasPerPageParam) {
          window.StorageHistory.set('per_page', this.options.itemsPerPage, false, true);
        }
      }
    },
    trimText: function (text) {
      return text.toString().trim().toLowerCase();
    },
    combineFilters: function (category, value) {
      if (value !== 'all') {
        if (this.activeFilters[category]) {
          this.activeFilters[category].push(value);
        } else {
          this.activeFilters[category] = [];
          this.activeFilters[category].push(value);
        }
      }
    },
    makeCallback: function (name) {
      if (typeof this.options[name] === 'function') {
        const args = Array.prototype.slice.call(arguments);

        args.shift();
        this.options[name].apply(this, args);
      }
    },
  };

  function getUrlAttr(name) {
    const sectionName = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
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
      return new Date(`${str.slice(0, 4)}-${str.slice(4, 6)}-${str.slice(6, 8)}T` + `${str.slice(8, 10)}:${str.slice(10, 12)}:${str.slice(12, 14)}`);
    }

    if (/^\d{4}$/.test(str)) {
      // yyyy → yyyy-01-01T00:00:00
      return new Date(`${str}-01-01T00:00:00`);
    }

    return new Date(str);
  }

  $.fn.filteringProducts = function (opt) {
    return this.each(function () {
      $(this).data(
        'FilteringProducts',
        new FilteringProducts(
          $.extend(opt, {
            holder: this,
          })
        )
      );
    });
  };
})($);

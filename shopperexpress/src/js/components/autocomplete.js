import '../plugins/autocompletePlugin';

// Autocomplete init
export default function initAutocomplete() {
  const filteringSection = jQuery('.filter-section');
  const filteringModal = jQuery('#filterSchedule');
  const filteringAPI = filteringSection.data('FilteringProducts');

  if (!filteringAPI) return;

  jQuery('.search-panel .search-row').each(function () {
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
      onKeyup: function (value) {
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
      onLoadData: function (data) {
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
      parseData: function (data) {
        return createAutocompleteItems(this, data);
      },
      onSelectedItem: function (item) {
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
      },
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
export function updateSearchURL(searchText, searchLinkNew, searchLinkUsed, newItemURL, usedItemURL, value) {
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
export function createAutocompleteItems(self, data) {
  const { suggestions } = data;
  const searchData = jQuery('<div></div>');
  const value = self.input.val();

  // If no suggestions are found, display an error message
  if (!suggestions.length && value !== '') {
    self.form.addClass(self.options.noResultClass);

    return `<span class="error-message">Sorry, we couldn't find results matching "${value}".</span>`;
  } else {
    self.form.removeClass(self.options.noResultClass);
  }

  const uniqueSuggestions = Array.from(new Map(suggestions.map((item) => [JSON.stringify(item), item])).values());

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

import '../plugins/autocompletePlugin';
import { createAutocompleteItems, updateSearchURL } from './autocomplete';

// search autocomplete
export default function initSearchForms() {
  const postTypeStr = '&post_type[]=listings&post_type[]=used-listings';

  // Search form on homepage
  jQuery('.search-row:not(.filter-section .search-row)').each(function () {
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
      onInit: function () {
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
      },
    });
  });
}

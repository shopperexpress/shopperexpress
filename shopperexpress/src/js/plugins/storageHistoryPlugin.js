import $ from 'jquery';

// history storage
(function ($) {
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
    set: function (name, value, group, useReplace) {
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
    remove: function (name, value, useReplace) {
      const location = window.location.href;
      const method = useReplace ? 'replaceState' : 'pushState';

      if (location.indexOf('?') > 0) {
        const newLocation = location.split('?');
        const arr = newLocation[1].split('&');
        const index = getItemIndex(arr, name);

        if (value === '') {
          const newArr = arr.filter(function (value, index, arr) {
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
    setQuery: function (query, useReplace) {
      const method = useReplace ? 'replaceState' : 'pushState';
      const base = window.location.pathname;
      const hash = window.location.hash;
      const url = query ? base + '?' + query + hash : base + hash;

      window.history[method](stateObject, '', url);
    },

    /**
     * Resets the URL to its original state
     */
    reset: function () {
      const url = window.location.origin + window.location.pathname;

      stateObject = {};

      window.history.replaceState(stateObject, '', url);
    },
  };

  function getItemIndex(array, item) {
    let indexInArray = -1;

    $.each(array, function (index, el) {
      const name = el.split('=');

      if (item === name[0]) {
        indexInArray = index;

        return false;
      }
    });

    return indexInArray;
  }
})($);

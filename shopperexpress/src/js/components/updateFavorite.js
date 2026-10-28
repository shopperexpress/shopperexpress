import initApiFavoriteButtons from './apiFavoriteButtons';
import initUnlockSavings from './unlockSavings';

// Update favorite
export default function initUpdateFavorite() {
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

  jQuery(document).on('favorites-updated-single', function () {
    getFavorite();
  });

  getFavorite();

  function getFavorite() {
    jQuery.ajax({
      url,
      data: {
        user: favoriteList.length ? favoriteList.data('user') : 0,
      },
      type: 'POST',
      success: function (data) {
        onSuccess(data);
      },
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
    if (window.ajax && window.ajax.api_mode) {
      jQuery.ajax({
        url: window.ajax.admin,
        type: 'POST',
        data: { action: 'wps_api_favorites_list' },
        success: function (response) {
          if (!response.success || !response.data) return;
          const vins = response.data[type] || [];
          if (!vins.length) return;

          jQuery.post(window.location.origin + `/wp-json/favorite/v1/render?action=${type}`, { post_id: vins.join(',') }, function (resp) {
            if (resp.success) {
              jQuery('#listings-container').html(resp.html);
              initUnlockSavings();
              initApiFavoriteButtons();
            }
          });
        },
      });
    } else {
      jQuery.ajax({
        url: window.ajax.admin,
        type: 'POST',
        data: {
          action: 'favorites_array',
        },
        success: function (response) {
          if (response.status === 'success') {
            const favorites = response.favorites;
            const posts = Object.values(favorites[0].posts);
            const postIDs = [];

            if (posts.length > 0) {
              posts.forEach(function (post) {
                postIDs.push(post.post_id);
              });

              const data = {
                post_id: postIDs.join(','),
              };

              jQuery.post(window.location.origin + `/wp-json/favorite/v1/render?action=${type}`, data, function (response) {
                if (response.success) {
                  jQuery('#listings-container').html(response.html);
                  initUnlockSavings();
                }
              });
            }
          }
        },
      });
    }
  }
}

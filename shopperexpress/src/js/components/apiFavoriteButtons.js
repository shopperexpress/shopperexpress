// API-mode favorite buttons (VIN-based toggle via custom AJAX handler).
export default function initApiFavoriteButtons() {
  if (!window.ajax || !window.ajax.api_mode) return;

  const activeClass = 'active';

  function setIcon($btn, isFav) {
    $btn.find('i').toggleClass('sf-icon-star-empty', !isFav).toggleClass('sf-icon-star-full', isFav);
  }

  // Mark already-favorited buttons on page load.
  jQuery.ajax({
    url: window.ajax.admin,
    type: 'POST',
    data: { action: 'wps_api_favorites_list' },
    success: function (response) {
      if (!response.success) return;
      const allFavs = response.data || {};
      jQuery('.api-favorite-button[data-postid]').each(function () {
        const $btn = jQuery(this);
        const vin = String($btn.data('postid'));
        const postType = $btn.data('posttype') || 'listings';
        const isFav = (allFavs[postType] || []).indexOf(vin) !== -1;
        $btn.toggleClass(activeClass, isFav);
        setIcon($btn, isFav);
      });
    },
  });

  // Toggle on click (delegated so it works after AJAX-injected content).
  jQuery(document)
    .off('click.apiFav')
    .on('click.apiFav', '.api-favorite-button[data-postid]', function (e) {
      if (!window.ajax || !window.ajax.api_mode) return;

      e.preventDefault();
      e.stopImmediatePropagation();

      const $btn = jQuery(this);
      const vin = String($btn.data('postid'));
      if (!vin) return;

      const postType = $btn.data('posttype') || 'listings';
      const isActive = $btn.hasClass(activeClass);
      const newActive = !isActive;

      $btn.toggleClass(activeClass, newActive);
      setIcon($btn, newActive);

      jQuery.ajax({
        url: window.ajax.admin,
        type: 'POST',
        data: {
          action: 'wps_api_favorite_toggle',
          vin: vin,
          post_type: postType,
          status: newActive ? 'active' : 'inactive',
        },
        success: function (response) {
          if (!response.success) {
            $btn.toggleClass(activeClass, isActive);
            setIcon($btn, isActive);
            return;
          }
          jQuery(document).trigger('favorites-updated-single');
        },
        error: function () {
          $btn.toggleClass(activeClass, isActive);
          setIcon($btn, isActive);
        },
      });
    });
}

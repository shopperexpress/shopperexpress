// Favorite button sync (syncs favorite buttons on page load and on mutation)
export default function initFavoriteButtonSync() {
  const selector = '.simplefavorite-button[data-postid]';
  const activeClass = 'active';
  const emptyIconClass = 'sf-icon-star-empty';
  const fullIconClass = 'sf-icon-star-full';

  function syncFrom(button) {
    const postid = button.dataset.postid;
    const isActive = button.classList.contains(activeClass);

    document.querySelectorAll(selector).forEach((other) => {
      if (other === button || other.dataset.postid !== postid) return;
      if (other.classList.contains(activeClass) === isActive) return;

      other.classList.toggle(activeClass, isActive);
      other.querySelectorAll('i').forEach((icon) => {
        icon.classList.toggle(emptyIconClass, !isActive);
        icon.classList.toggle(fullIconClass, isActive);
      });
    });
  }

  new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.target.matches && mutation.target.matches(selector)) {
        syncFrom(mutation.target);
      }
    });
  }).observe(document.body, { attributes: true, attributeFilter: ['class'], subtree: true });
}

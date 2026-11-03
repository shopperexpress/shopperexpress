import GoogleReviews from './googleReviewsPlugin';

document.addEventListener('DOMContentLoaded', () => {
  initGoogleReviews();
});

function initGoogleReviews() {
  document.querySelectorAll('[data-google-reviews]').forEach((holder) => {
    new GoogleReviews(holder, {
      onRender: function () {
        jQuery(this.listElement).add(this.modalListElement).find('[data-toggle="tooltip"]:not([data-original-title])').tooltip();
      },
    });
  });
}

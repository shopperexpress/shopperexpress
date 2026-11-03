/*
 * Google Reviews module
 */
export default class GoogleReviews {
  constructor(holder, options = {}) {
    this.options = {
      placeId: holder?.dataset.googleReviewsPlaceId,
      style: holder?.dataset.googleReviewsStyle || 'list',
      keywordFilter: holder?.dataset.googleReviewsKeywordFilter || '',
      slidesPerView: parseInt(holder?.dataset.googleReviewsSlides, 10) || 6,
      minCount: parseInt(holder?.dataset.googleReviewsMinCount, 10) || 0,
      restUrl: window.ajax?.google_reviews_rest,
      loadingClass: 'is-loading',
      errorClass: 'has-error',
      ...options,
    };

    if (!holder) return;

    this.holder = holder;

    this.init();
  }

  init() {
    this.ratingElement = this.holder.querySelector('[data-google-reviews-rating]');
    this.countElement = this.holder.querySelector('[data-google-reviews-count]');
    this.starsElement = this.holder.querySelector('[data-google-reviews-stars]');
    this.ctaElement = this.holder.querySelector('[data-google-reviews-cta]');
    this.listElement = this.holder.querySelector('[data-google-reviews-list]');
    this.moreReviewsElement = this.holder.querySelector('[data-more-reviews-link]');
    this.loadMoreHolderElement = this.holder.querySelector('[data-load-more-holder]');
    this.reviews = [];
    this.nextPageToken = '';

    // Slider "Read more" opens a modal with the full, untruncated review list
    // instead of expanding the text inline (see data-google-reviews-modal).
    this.modalElement = this.holder.dataset.googleReviewsModal ? document.querySelector(this.holder.dataset.googleReviewsModal) : null;

    if (this.modalElement) {
      this.modalRatingElement = this.modalElement.querySelector('[data-google-reviews-rating]');
      this.modalCountElement = this.modalElement.querySelector('[data-google-reviews-count]');
      this.modalStarsElement = this.modalElement.querySelector('[data-google-reviews-stars]');
      this.modalListElement = this.modalElement.querySelector('[data-google-reviews-list]');
    }

    this.holder.classList.add(this.options.loadingClass);
    this.holder.classList.remove(this.options.errorClass);

    this.bindEvents();
    this.loadReviews();
  }

  bindEvents() {
    if (this.moreReviewsElement) {
      this.moreReviewsElement.addEventListener('click', (event) => {
        // Business Profile connection: fetch the next page in place.
        // Places API fallback: let the default navigation to Google happen.
        if (this.nextPageToken) {
          event.preventDefault();
          this.handleLoadMore();
        }
      });
    }
  }

  async fetchReviews(pageToken = '') {
    if (!this.options.restUrl) {
      throw new Error('Google Reviews REST endpoint is not configured.');
    }

    if (!this.options.placeId) {
      throw new Error('Google Reviews: missing Place ID.');
    }

    const params = new URLSearchParams({ place_id: this.options.placeId });
    if (pageToken) {
      params.set('page_token', pageToken);
    }
    if (this.options.keywordFilter) {
      params.set('keyword', this.options.keywordFilter);
    }
    if (!pageToken && this.options.minCount) {
      params.set('min_count', this.options.minCount);
    }

    const response = await fetch(`${this.options.restUrl}?${params.toString()}`, {
      headers: { Accept: 'application/json' },
    });
    const data = await response.json();

    if (!response.ok || data.error) {
      throw new Error(data.error || `Request failed with status ${response.status}`);
    }

    return data;
  }

  async loadReviews() {
    try {
      this.compileTemplates();

      const data = await this.fetchReviews();

      this.render(data);
    } catch (error) {
      console.error('GoogleReviews:', error);
      this.holder.classList.add(this.options.errorClass);
      this.renderFallback();
    } finally {
      this.holder.classList.remove(this.options.loadingClass);
    }
  }

  async handleLoadMore() {
    if (!this.moreReviewsElement) return;

    try {
      const data = await this.fetchReviews(this.nextPageToken);
      const newReviews = (data.reviews ?? []).map((review) => this.normalizeReview(review));

      this.reviews = this.reviews.concat(newReviews);
      this.nextPageToken = data.next_page_token ?? '';

      if (this.listElement && newReviews.length) {
        this.listElement.insertAdjacentHTML('beforeend', this.reviewsTemplate({ reviews: newReviews }));
      }

      this.initSlider();
      this.setupReadMore();

      if (this.loadMoreHolderElement) {
        this.loadMoreHolderElement.hidden = !newReviews.length || !this.nextPageToken;
      }

      this.makeCallback('onRender', newReviews);
    } catch (error) {
      console.error('GoogleReviews:', error);
    }
  }

  renderFallback() {
    if (this.moreReviewsElement && this.options.placeId) {
      this.moreReviewsElement.href = `https://search.google.com/local/reviews?placeid=${this.options.placeId}`;
    }

    if (this.loadMoreHolderElement) {
      this.loadMoreHolderElement.hidden = !this.options.placeId;
    }

    if (this.listElement) {
      this.listElement.innerHTML = '<p>Reviews are temporarily unavailable. See them on Google.</p>';
    }
  }

  compileTemplates() {
    const { Handlebars } = window;

    if (!Handlebars) {
      throw new Error('GoogleReviews: window.Handlebars is not available, it is exposed by the theme bundle (assets/js/app.js).');
    }

    const template = this.holder.querySelector('[data-google-reviews-template="reviews"]');

    this.reviewsTemplate = Handlebars.compile(template.textContent);

    const modalTemplate = this.holder.querySelector('[data-google-reviews-template="modal-reviews"]');

    if (modalTemplate) {
      this.modalReviewsTemplate = Handlebars.compile(modalTemplate.textContent);
    }
  }

  normalizeReview(review) {
    const text = review.text ?? '';

    return {
      rating: review.rating ?? 0,
      stars: this.renderStars(review.rating),
      // "lp" cards have no "Read more"/modal truncation UI, so cap the length
      // here instead (e.g. "Buying a new 2027 CRV was hassle free and
      // enjoyable, From dealing with Joey Nahama , Naji Abihabib and Fabio
      // Martorell an exceptional expierence" is about the max length shown).
      text: this.options.style === 'lp' ? this.truncateText(text, 150) : text,
      // Modal's review list always shows the full text — only the lp card
      // itself is capped (see the "text" field above).
      fullText: text,
      relativePublishTimeDescription: review.relativePublishTimeDescription ?? '',
      publishTime: this.formatPublishTime(review.publishTime),
      googleMapsURI: review.googleMapsURI ?? '',
      authorAttribution: {
        displayName: review.authorAttribution?.displayName ?? 'Google user',
        photoURI: review.authorAttribution?.photoURI || 'images/review-avatar.png',
      },
    };
  }

  truncateText(text, maxLength) {
    if (!text || text.length <= maxLength) return text;

    const cut = text.slice(0, maxLength);
    const lastSpace = cut.lastIndexOf(' ');

    return `${(lastSpace > 0 ? cut.slice(0, lastSpace) : cut).trim()}…`;
  }

  formatPublishTime(publishTime) {
    if (!publishTime) return '';

    const date = new Date(publishTime);

    if (Number.isNaN(date.getTime())) return '';

    return new Intl.DateTimeFormat('en-US', {
      month: 'long',
      day: 'numeric',
      year: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
      hour12: true,
      timeZoneName: 'shortOffset',
    }).format(date);
  }

  formatRating(rating) {
    const num = Number(rating);
    return Number.isFinite(num) ? num.toFixed(1) : '';
  }

  renderStars(rating = 0) {
    const starsCount = 5;
    const normalizedRating = Math.max(0, Math.min(starsCount, Number(rating) || 0));
    const fill = (normalizedRating / starsCount) * 100;
    const stars =
      `<svg viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.82617 11.442L3.54617 13.166C3.46353 13.2093 3.3704 13.2287 3.27732 13.2219C3.18425 13.2151 3.09494 13.1824 3.0195 13.1274C2.94406 13.0725 2.8855 12.9975 2.85045 12.911C2.8154 12.8245 2.80526 12.7299 2.82117 12.638L3.44817 8.98798C3.46192 8.908 3.456 8.82587 3.43091 8.74869C3.40582 8.67151 3.36232 8.6016 3.30417 8.54499L0.650168 5.95899C0.583317 5.89388 0.53602 5.81136 0.51363 5.72076C0.491239 5.63017 0.494647 5.53512 0.52347 5.44637C0.552292 5.35761 0.605378 5.27869 0.676721 5.21854C0.748065 5.15838 0.834818 5.1194 0.927168 5.10599L4.59317 4.57299C4.67344 4.56146 4.7497 4.53059 4.81537 4.48303C4.88105 4.43547 4.93418 4.37265 4.97017 4.29999L6.61017 0.977985C6.65153 0.894518 6.7154 0.824266 6.79455 0.775151C6.87371 0.726037 6.96501 0.700012 7.05817 0.700012C7.15132 0.700012 7.24263 0.726037 7.32178 0.775151C7.40094 0.824266 7.4648 0.894518 7.50617 0.977985L9.14717 4.29899C9.18307 4.37152 9.23604 4.43426 9.30153 4.48182C9.36702 4.52937 9.44308 4.56031 9.52317 4.57199L13.1892 5.10499C13.2815 5.1184 13.3683 5.15738 13.4396 5.21754C13.511 5.27769 13.564 5.35661 13.5929 5.44537C13.6217 5.53412 13.6251 5.62917 13.6027 5.71976C13.5803 5.81036 13.533 5.89288 13.4662 5.95798L10.8132 8.54398C10.7552 8.60049 10.7118 8.67024 10.6867 8.74723C10.6616 8.82422 10.6556 8.90616 10.6692 8.98598L11.2962 12.637C11.3122 12.7291 11.3021 12.8238 11.267 12.9105C11.232 12.9971 11.1733 13.0722 11.0977 13.1272C11.0221 13.1822 10.9326 13.2149 10.8393 13.2215C10.7461 13.2282 10.6528 13.2086 10.5702 13.165L7.29117 11.441C7.21946 11.4033 7.13967 11.3836 7.05867 11.3836C6.97767 11.3836 6.89788 11.4033 6.82617 11.441V11.442Z" fill="none"></path></svg>`.repeat(
        starsCount
      );

    return `
			<span class="google-reviews__stars-row" role="img" aria-label="${normalizedRating} out of ${starsCount} stars" style="--fill: ${fill}%">
				<span class="google-reviews__stars-bg" aria-hidden="true">${stars}</span>
				<span class="google-reviews__stars-fg" aria-hidden="true">${stars}</span>
			</span>
		`;
  }

  render(data) {
    this.reviews = (data.reviews ?? []).map((review) => this.normalizeReview(review));

    if (this.ratingElement) {
      this.ratingElement.textContent = this.formatRating(data.average_rating);
    }

    if (this.countElement) {
      this.countElement.textContent = data.total_review_count ? `(${data.total_review_count.toLocaleString()})` : '';
    }

    if (this.starsElement) {
      this.starsElement.innerHTML = this.renderStars(data.average_rating);
    }

    if (this.ctaElement && this.options.placeId) {
      this.ctaElement.href = `https://search.google.com/local/writereview?placeid=${this.options.placeId}`;
    }

    this.nextPageToken = data.next_page_token ?? '';

    if (this.moreReviewsElement) {
      if (this.nextPageToken) {
        // Business Profile connection: real pagination, fetched in place.
        this.moreReviewsElement.removeAttribute('href');
        this.moreReviewsElement.removeAttribute('target');
        this.moreReviewsElement.textContent = 'Load More';
      } else if (this.options.placeId) {
        // Places API fallback: Google caps this at 5 reviews with no
        // pagination — link out to the place's full review list instead.
        this.moreReviewsElement.href = `https://search.google.com/local/reviews?placeid=${this.options.placeId}`;
        this.moreReviewsElement.setAttribute('target', '_blank');
        this.moreReviewsElement.textContent = 'Show More Reviews';
      }
    }

    if (this.loadMoreHolderElement) {
      this.loadMoreHolderElement.hidden = !this.reviews.length || (!this.nextPageToken && !this.options.placeId);
    }

    if (this.listElement) {
      this.listElement.innerHTML = this.reviewsTemplate({ reviews: this.reviews });

      // "lp" style's rating/stars/count markup lives inside the handlebars
      // template itself (as the slider's first slide), so it doesn't exist
      // yet when the constructor's querySelector ran — re-resolve and
      // (re-)apply here now that the template has been injected.
      const isLp = this.options.style === 'lp';

      if (!this.ratingElement || !this.holder.contains(this.ratingElement)) {
        this.ratingElement = this.holder.querySelector('[data-google-reviews-rating]');
        if (this.ratingElement) this.ratingElement.textContent = this.formatRating(data.average_rating);
      }

      if (!this.countElement || !this.holder.contains(this.countElement)) {
        this.countElement = this.holder.querySelector('[data-google-reviews-count]');

        if (this.countElement) {
          if (isLp) {
            this.countElement.innerHTML = data.total_review_count ? `Based on <strong>${data.total_review_count.toLocaleString()}</strong> reviews` : '';
          } else {
            this.countElement.textContent = data.total_review_count ? `(${data.total_review_count.toLocaleString()})` : '';
          }
        }
      }

      if (!this.starsElement || !this.holder.contains(this.starsElement)) {
        this.starsElement = this.holder.querySelector('[data-google-reviews-stars]');
        if (this.starsElement) this.starsElement.innerHTML = this.renderStars(data.average_rating);
      }
    }

    if (this.modalRatingElement) {
      this.modalRatingElement.textContent = this.formatRating(data.average_rating);
    }

    if (this.modalCountElement) {
      this.modalCountElement.textContent = data.total_review_count ? `(${data.total_review_count.toLocaleString()})` : '';
    }

    if (this.modalStarsElement) {
      this.modalStarsElement.innerHTML = this.renderStars(data.average_rating);
    }

    if (this.modalListElement && this.modalReviewsTemplate) {
      this.modalListElement.innerHTML = this.modalReviewsTemplate({ reviews: this.reviews });
    }

    this.initSlider();
    this.setupReadMore();

    this.makeCallback('onRender', this.reviews);
  }

  setupReadMore() {
    if (!this.listElement) return;

    this.listElement.querySelectorAll('.review-item__text').forEach((textElement) => {
      const button = textElement.nextElementSibling;

      if (!button || !button.classList.contains('review-item__read-more')) return;

      button.hidden = textElement.scrollHeight <= textElement.clientHeight + 1;

      // Bootstrap's data-api already opens the modal on click for these.
      if (button.dataset.toggle === 'modal') return;

      if (button.dataset.bound) return;
      button.dataset.bound = '1';

      button.addEventListener('click', () => {
        const expanded = textElement.classList.toggle('is-expanded');

        button.textContent = expanded ? this.options.readLessLabel || 'Read less' : this.options.readMoreLabel || 'Read more';
      });
    });
  }

  initSlider() {
    // "lp" reuses the same slider behavior as "slider", just with different wrapper markup/CSS.
    if (!['slider', 'lp'].includes(this.options.style) || !this.listElement || !window.jQuery) return;

    const $list = window.jQuery(this.listElement);

    if ($list.hasClass('slick-initialized')) {
      $list.slick('unslick');
    }

    if (!this.reviews.length) return;

    const slidesToShow = Math.max(1, this.options.slidesPerView || 6);
    const isLp = this.options.style === 'lp';

    // "lp" style uses the landing-page arrow button skin (.lp-slick-control)
    // with the same large arrow glyph as the other lp- blocks, instead of
    // the default small round slick-prev/slick-next buttons.
    const prevArrow = isLp
      ? '<button class="lp-slick-control slick-prev slick-arrow" aria-label="Previous"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" width="32" height="32" viewBox="0 0 32 32" fill="#000000"><path d="M5.48 14.926v-0.001c0-0.001 0.002-0.002 0.002-0.003s0.002-0.001 0.002-0.002v-0.001c0.018-0.018 0.033-0.034 0.051-0.052l9.334-9.333c0.623-0.623 1.638-0.624 2.263 0.001s0.624 1.638-0.001 2.263l-6.602 6.602h14.804c0.884 0 1.6 0.716 1.6 1.6s-0.716 1.6-1.6 1.6h-14.804l6.602 6.603c0.625 0.625 0.625 1.637 0.001 2.262-0.313 0.313-0.723 0.469-1.132 0.469s-0.819-0.156-1.131-0.469l-9.334-9.332c-0.018-0.018-0.033-0.034-0.051-0.052v-0.001c0-0.001-0.002-0.002-0.002-0.002s-0.002-0.002-0.002-0.003v-0.001c-0.258-0.282-0.414-0.657-0.414-1.068v-0.010c0-0.411 0.156-0.786 0.414-1.068z"></path></svg></button>'
      : '<button class="slick-prev slick-arrow" aria-label="Previous"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#fff"><path d="m432-480 156 156q11 11 11 28t-11 28q-11 11-28 11t-28-11L348-452q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l184-184q11-11 28-11t28 11q11 11 11 28t-11 28L432-480Z"/></svg></button>';
    const nextArrow = isLp
      ? '<button class="lp-slick-control slick-next slick-arrow" aria-label="Next"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" width="32" height="32" viewBox="0 0 32 32" fill="#000000"><path d="M26.52 14.926v-0.001c0-0.001-0.002-0.002-0.002-0.003s-0.002-0.001-0.002-0.002v-0.001c-0.018-0.018-0.033-0.034-0.051-0.052l-9.334-9.333c-0.623-0.623-1.638-0.624-2.263 0.001s-0.624 1.638 0.001 2.263l6.602 6.602h-14.804c-0.884 0-1.6 0.716-1.6 1.6s0.716 1.6 1.6 1.6h14.804l-6.602 6.603c-0.625 0.625-0.625 1.637-0.001 2.262 0.313 0.313 0.723 0.469 1.132 0.469s0.819-0.156 1.131-0.469l9.334-9.332c0.018-0.018 0.033-0.034 0.051-0.052v-0.001c0-0.001 0.002-0.002 0.002-0.002s0.002-0.002 0.002-0.003v-0.001c0.258-0.282 0.414-0.657 0.414-1.068v-0.010c0-0.411-0.156-0.786-0.414-1.068z"></path></svg></button>'
      : '<button class="slick-next slick-arrow" aria-label="Next"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#fff"><path d="M504-480 348-636q-11-11-11-28t11-28q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L404-268q-11 11-28 11t-28-11q-11-11-11-28t11-28l156-156Z"/></svg></button>';

    // "lp" cards are fixed-width (.lp-slide, see _new-landing.scss) rather
    // than equal fractions of the track — slidesToShow here only sets the
    // scroll/breakpoint step, variableWidth lets each card keep its CSS
    // width instead of Slick forcing it to a computed column width (which
    // was throwing the track alignment off).
    $list.slick(
      isLp
        ? {
            slidesToScroll: 1,
            rows: 0,
            slidesToShow: 3,
            infinite: false,
            // variableWidth: true,
            prevArrow:
              '<button class="lp-slick-control slick-prev slick-arrow" aria-label="Previous"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" width="32" height="32" viewBox="0 0 32 32" fill="#000000"><path d="M5.48 14.926v-0.001c0-0.001 0.002-0.002 0.002-0.003s0.002-0.001 0.002-0.002v-0.001c0.018-0.018 0.033-0.034 0.051-0.052l9.334-9.333c0.623-0.623 1.638-0.624 2.263 0.001s0.624 1.638-0.001 2.263l-6.602 6.602h14.804c0.884 0 1.6 0.716 1.6 1.6s-0.716 1.6-1.6 1.6h-14.804l6.602 6.603c0.625 0.625 0.625 1.637 0.001 2.262-0.313 0.313-0.723 0.469-1.132 0.469s-0.819-0.156-1.131-0.469l-9.334-9.332c-0.018-0.018-0.033-0.034-0.051-0.052v-0.001c0-0.001-0.002-0.002-0.002-0.002s-0.002-0.002-0.002-0.003v-0.001c-0.258-0.282-0.414-0.657-0.414-1.068v-0.010c0-0.411 0.156-0.786 0.414-1.068z"/></svg></button>',
            nextArrow:
              '<button class="lp-slick-control slick-next slick-arrow" aria-label="Next"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" width="32" height="32" viewBox="0 0 32 32" fill="#000000"><path d="M26.52 14.926v-0.001c0-0.001-0.002-0.002-0.002-0.003s-0.002-0.001-0.002-0.002v-0.001c-0.018-0.018-0.033-0.034-0.051-0.052l-9.334-9.333c-0.623-0.623-1.638-0.624-2.263 0.001s-0.624 1.638 0.001 2.263l6.602 6.602h-14.804c-0.884 0-1.6 0.716-1.6 1.6s0.716 1.6 1.6 1.6h14.804l-6.602 6.603c-0.625 0.625-0.625 1.637-0.001 2.262 0.313 0.313 0.723 0.469 1.132 0.469s0.819-0.156 1.131-0.469l9.334-9.332c0.018-0.018 0.033-0.034 0.051-0.052v-0.001c0-0.001 0.002-0.002 0.002-0.002s0.002-0.002 0.002-0.003v-0.001c0.258-0.282 0.414-0.657 0.414-1.068v-0.010c0-0.411-0.156-0.786-0.414-1.068z"/></svg></button>',
            responsive: [
              {
                breakpoint: 1024,
                settings: {
                  slidesToShow: 2,
                },
              },
              {
                breakpoint: 576,
                settings: {
                  slidesToShow: 1,
                },
              },
            ],
          }
        : {
            slidesToScroll: 1,
            rows: 0,
            slidesToShow,
            prevArrow,
            nextArrow,
            responsive: [
              {
                breakpoint: 1570,
                settings: {
                  slidesToShow: Math.min(slidesToShow, 5),
                },
              },
              {
                breakpoint: 1310,
                settings: {
                  slidesToShow: Math.min(slidesToShow, 4),
                },
              },
              {
                breakpoint: 1050,
                settings: {
                  slidesToShow: Math.min(slidesToShow, 3),
                },
              },
              {
                breakpoint: 576,
                settings: {
                  slidesToShow: 1,
                },
              },
            ],
          }
    );
  }

  makeCallback(callbackName, ...args) {
    if (typeof this.options[callbackName] === 'function') {
      this.options[callbackName].apply(this, args);
    }
  }
}

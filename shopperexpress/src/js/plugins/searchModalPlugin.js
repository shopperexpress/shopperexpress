import Handlebars from 'handlebars';

/*
 * SearchModal module
 */
export default class SearchModal {
  constructor(holder, options = {}) {
    this.options = {
      modalSelector: '#searchModal',
      backdropSelector: '[data-search-modal-close]',
      inputSelector: '.search-modal__input',
      resultsSelector: '[data-search-modal-results]',
      emptySelector: '[data-search-modal-empty]',
      itemTemplateSelector: '#search-modal-item-template',
      viewAllTemplateSelector: '#search-modal-view-all-template',
      searchFields: ['year', 'make', 'model', 'trim', 'drivetrain', 'body_style'],
      lockClass: 'search-modal-open',
      loadingClass: 'loading',
      activeClass: 'active',
      maxItems: 20,
      imageTimeout: 5000,
      ...options,
    };

    if (!holder) return;

    this.holder = holder;
    this.data = [];
    this.renderToken = 0;

    this.init();
  }

  init() {
    this.modal = document.querySelector(this.options.modalSelector);

    if (!this.modal) return;

    this.backdrop = this.modal.querySelector(this.options.backdropSelector);
    this.input = this.modal.querySelector(this.options.inputSelector);
    this.results = this.modal.querySelector(this.options.resultsSelector);
    this.empty = this.modal.querySelector(this.options.emptySelector);
    this.dataUrl = this.modal.dataset.searchUrl || window.ajax?.vehicles_feed_rest;
    this.itemTemplate = this.compileTemplate(this.options.itemTemplateSelector);
    this.viewAllTemplate = this.compileTemplate(this.options.viewAllTemplateSelector);

    this.bindEvents();
    this.loadData();
  }

  bindEvents() {
    this.holder.addEventListener('click', () => {
      this.toggleModal();
    });

    this.backdrop?.addEventListener('click', () => {
      this.closeModal();
    });

    this.input?.addEventListener('input', this.debounce(this.handleSearch, 250));

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        this.closeModal();
      }
    });
  }

  compileTemplate(selector) {
    const source = document.querySelector(selector);

    return source ? Handlebars.compile(source.innerHTML) : null;
  }

  async loadData() {
    if (!this.dataUrl) return;

    this.modal.classList.add(this.options.loadingClass);

    try {
      const response = await fetch(this.dataUrl);

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const data = await response.json();

      this.data = (data.vehicles ?? []).filter((item) => item.make && item.model);

      await this.showResults(this.data);
    } catch (error) {
      console.error('SearchModal:', error);
      this.modal.classList.remove(this.options.loadingClass);
    }
  }

  async showResults(items, query = '') {
    const visibleItems = items.slice(0, this.options.maxItems);
    const token = ++this.renderToken;

    this.modal.classList.add(this.options.loadingClass);

    await this.preloadImages(visibleItems);

    // A newer search/load started while we were preloading - drop this stale render
    if (token !== this.renderToken) return;

    this.modal.classList.remove(this.options.loadingClass);
    this.renderResults(visibleItems, query);
  }

  preloadImages(items) {
    const urls = items.map((item) => item.photo).filter(Boolean);

    return Promise.allSettled(urls.map((url) => this.preloadImage(url)));
  }

  preloadImage(url) {
    return new Promise((resolve) => {
      const image = new Image();
      const timer = setTimeout(resolve, this.options.imageTimeout);

      image.onload = image.onerror = () => {
        clearTimeout(timer);
        resolve();
      };
      image.src = url;
    });
  }

  renderResults(items, query = '') {
    if (!this.results || !this.itemTemplate) return;

    const viewAllHTML = query && this.viewAllTemplate ? this.viewAllTemplate({ query }) : '';

    this.results.innerHTML = viewAllHTML + items.map((item) => this.itemTemplate(this.buildItemData(item))).join('');
    this.results.hidden = false;

    if (this.empty) {
      this.results.appendChild(this.empty);
      this.empty.hidden = items.length > 0;
    }
  }

  handleSearch(event) {
    const query = event.target.value.trim().toLowerCase();

    if (!query) {
      this.showResults(this.data);
      return;
    }

    const filtered = this.data.filter((item) => this.matchesQuery(item, query));

    this.showResults(filtered, query);
  }

  matchesQuery(item, query) {
    const haystack = this.normalizeToken(this.options.searchFields.map((field) => String(item[field] ?? '').toLowerCase()).join(' '));
    const tokens = query
      .split(/\s+/)
      .filter(Boolean)
      .map((token) => this.normalizeToken(token));

    return tokens.every((token) => haystack.includes(token));
  }

  normalizeToken(value) {
    return value.replace(/-/g, '');
  }

  debounce(func, delay = 250) {
    let timer = null;

    return (...args) => {
      clearTimeout(timer);
      timer = setTimeout(() => func.apply(this, args), delay);
    };
  }

  buildItemData(item) {
    const title = [item.year, item.make, item.model, item.trim, item.drivetrain].filter(Boolean).join(' ');

    return {
      link: item.link,
      photo: item.photo,
      title,
      price: this.formatPrice(item.price),
    };
  }

  formatPrice(price) {
    const value = parseInt(price, 10);

    return Number.isNaN(value) ? '' : `$${value.toLocaleString('en-US')}`;
  }

  toggleModal() {
    if (this.modal.classList.contains(this.options.activeClass)) {
      this.closeModal();
    } else {
      this.openModal();
    }
  }

  openModal() {
    this.modal.classList.add(this.options.activeClass);
    this.modal.setAttribute('aria-hidden', 'false');
    this.holder.setAttribute('aria-expanded', 'true');
    document.body.classList.add(this.options.lockClass);
    this.input?.focus();
  }

  closeModal() {
    this.modal.classList.remove(this.options.activeClass);
    this.modal.setAttribute('aria-hidden', 'true');
    this.holder.setAttribute('aria-expanded', 'false');
    document.body.classList.remove(this.options.lockClass);
  }
}

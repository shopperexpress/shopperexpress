import Cookies from 'js-cookie';
import Handlebars from 'handlebars';

/*
 * CompareProducts module
 */
export default class CompareProducts {
  constructor(holder, options) {
    this.options = {
      cookieName: 'compare_vehicles',
      defaultPosttype: 'listings',
      buttonSelector: '.compare__btn',
      activeClass: 'selected',
      tooltipAddLabel: '+Compare',
      tooltipRemoveLabel: '-Compare',
      popupSelector: '.compare-popup',
      popupActiveClass: 'is-active',
      popupCloseSelector: '.compare-popup__close',
      popupListSelector: '.compare-popup__list',
      popupDeleteButtonSelector: '.compare-popup__list-btn-del',
      popupItemTemplateSelector: '#compare-popup-item-template',
      popupTitleSelector: '.compare-popup__title',
      popupCountSelector: '.compare-popup__count',
      popupTextSelector: '.compare-popup__text',
      popupInfoSelector: '.compare-popup__info',
      popupCompareButtonSelector: '.compare-popup__footer .btn-compare',
      minItemsToCompare: 2,
      hiddenClass: 'd-none',
      compareModalSelector: '#compareModal',
      compareTableSelector: '.compare-table',
      compareColumnSelector: '.compare-table__column',
      compareColumnTemplateSelector: '#compare-column-template',
      emptyColumnTemplateSelector: '#compare-empty-column-template',
      skeletonColumnTemplateSelector: '#compare-skeleton-column-template',
      specRowsPartialSelector: '#compare-spec-rows-template',
      specRowsPartialName: 'compareSpecRows',
      compareDeleteButtonSelector: '.card-compare__btn-del',
      compareTextSelector: '.compare-table__text',
      termFieldSelector: '[data-term]',
      similaritySwitchSelector: '#compareSwitcher',
      similarClass: 'is-similar',
      emptyColumnClass: 'is-empty',
      maxItems: 5,
      compareSpecRowsStart: 3,
      ...options,
    };

    if (!holder) return;

    this.holder = holder;
    this.items = this.getStoredItems();
    this.vehicles = [];

    this.init();
  }

  init() {
    this.findElements();
    this.bindGridEvents();
    this.bindPopupEvents();
    this.bindModalEvents();
  }

  findElements() {
    this.popup = document.querySelector(this.options.popupSelector);
    this.popupTitle = this.popup ? this.popup.querySelector(this.options.popupTitleSelector) : null;
    this.popupTitleHTML = this.popupTitle ? this.popupTitle.innerHTML : '';
    this.modal = document.querySelector(this.options.compareModalSelector);
    this.compareTable = this.modal ? this.modal.querySelector(this.options.compareTableSelector) : null;
    this.similaritySwitch = this.modal ? this.modal.querySelector(this.options.similaritySwitchSelector) : null;
    this.registerSpecRowsPartial();

    this.popupItemTemplate = this.compileTemplate(this.options.popupItemTemplateSelector);
    this.columnTemplate = this.compileTemplate(this.options.compareColumnTemplateSelector);
    this.emptyColumnTemplate = this.compileTemplate(this.options.emptyColumnTemplateSelector);
    this.skeletonColumnTemplate = this.compileTemplate(this.options.skeletonColumnTemplateSelector);
  }

  // Registers the shared spec-rows block so the real/empty/skeleton column templates can all include it
  registerSpecRowsPartial() {
    const source = document.querySelector(this.options.specRowsPartialSelector);

    if (source) Handlebars.registerPartial(this.options.specRowsPartialName, source.innerHTML);
  }

  // Toggles an item on click of a grid card's compare button
  bindGridEvents() {
    this.holder.addEventListener('click', (e) => {
      const button = e.target.closest(this.options.buttonSelector);

      if (button) this.toggleItem(button);
    });
  }

  // Handles the popup's close button and per-item delete buttons
  bindPopupEvents() {
    if (!this.popup) return;

    this.popup.addEventListener('click', (e) => {
      if (e.target.closest(this.options.popupCloseSelector)) {
        this.closePopup();
        return;
      }

      const deleteButton = e.target.closest(this.options.popupDeleteButtonSelector);

      if (deleteButton) this.removeItem(this.getButtonIdentity(deleteButton));
    });
  }

  // Renders the compare table on open and wires its delete buttons and similarity switch
  bindModalEvents() {
    if (!this.modal) return;

    jQuery(this.modal).on('show.bs.modal', () => {
      this.closePopup();
      this.renderCompareTable();
    });

    this.modal.addEventListener('click', (e) => {
      const deleteButton = e.target.closest(this.options.compareDeleteButtonSelector);

      if (deleteButton) this.removeItem(this.getButtonIdentity(deleteButton));
    });

    if (this.similaritySwitch) {
      this.similaritySwitch.addEventListener('change', () => this.updateSimilarityHighlight());
    }
  }

  // Marks backend-rendered grid buttons as active if their item is already in the compare list
  syncButtons(items) {
    items.forEach((item) => {
      const button = item.querySelector(this.options.buttonSelector);

      if (!button) return;

      const isSelected = this.findItemIndex(this.getButtonIdentity(button)) !== -1;

      button.classList.toggle(this.options.activeClass, isSelected);
      this.updateButtonTooltip(button, isSelected);
    });
  }

  // Updates the compare button's tooltip label to reflect whether the item is selected
  updateButtonTooltip(button, isSelected) {
    const label = isSelected ? this.options.tooltipRemoveLabel : this.options.tooltipAddLabel;

    button.setAttribute('title', label);

    if (!jQuery.fn.tooltip) return;

    jQuery(button).attr('data-original-title', label);

    // Bootstrap only reads data-original-title when the tooltip opens, so if it's already
    // showing (e.g. clicked while hovering), the visible bubble needs its text patched directly.
    const tooltipId = button.getAttribute('aria-describedby');
    const tooltipInner = tooltipId && document.getElementById(tooltipId)?.querySelector('.tooltip-inner');

    if (tooltipInner) tooltipInner.textContent = label;
  }

  // Adds or removes an item from the compare list on grid button click
  toggleItem(button) {
    const identity = this.getButtonIdentity(button);
    const index = this.findItemIndex(identity);
    const isAdding = index === -1;

    if (!isAdding) {
      this.items.splice(index, 1);
      button.classList.remove(this.options.activeClass);
    } else if (this.items.length < this.options.maxItems) {
      this.items.push(identity);
      button.classList.add(this.options.activeClass);
    } else {
      this.openPopup();
      return;
    }

    this.updateButtonTooltip(button, isAdding);
    this.persistAndRerender();

    if (isAdding) {
      this.openPopup();
    } else if (!this.items.length) {
      this.closePopup();
    }
  }

  // Removes an item from the compare list and unselects its grid button, if rendered
  removeItem(identity) {
    const index = this.findItemIndex(identity);

    if (index === -1) return;

    this.items.splice(index, 1);
    this.persistAndRerender();

    const gridButton = this.holder.querySelector(`${this.options.buttonSelector}[data-postid="${identity.postid}"][data-posttype="${identity.posttype}"]`);

    if (gridButton) {
      gridButton.classList.remove(this.options.activeClass);
      this.updateButtonTooltip(gridButton, false);
    }

    if (!this.items.length) this.closePopup();
  }

  // Stores the vehicle data fetched via `data-vehicles`, used to render the popup and compare table
  setVehicles(vehicles) {
    this.vehicles = vehicles;
    this.renderPopupList();
  }

  // Reads the postid/posttype pair a compare button is tagged with
  getButtonIdentity(button) {
    return {
      postid: button.dataset.postid,
      posttype: button.dataset.posttype || this.options.defaultPosttype,
    };
  }

  // Saves the compare list to the cookie and re-renders the popup and compare table
  persistAndRerender() {
    this.saveItems();
    this.renderPopupList();
    this.renderCompareTable();
  }

  openPopup() {
    if (this.popup) {
      this.popup.classList.add(this.options.popupActiveClass);
    }
  }

  closePopup() {
    if (this.popup) {
      this.popup.classList.remove(this.options.popupActiveClass);
    }
  }

  renderPopupList() {
    if (!this.popup) return;

    const list = this.popup.querySelector(this.options.popupListSelector);
    const text = this.popup.querySelector(this.options.popupTextSelector);
    const info = this.popup.querySelector(this.options.popupInfoSelector);
    const compareButton = this.popup.querySelector(this.options.popupCompareButtonSelector);
    const isMax = this.items.length >= this.options.maxItems;

    if (list) {
      list.innerHTML = this.items.map((item) => this.buildPopupItemHTML(item)).join('');
    }

    if (this.popupTitle) {
      if (isMax) {
        this.popupTitle.textContent = this.popupTitle.dataset.maxText || '';
      } else {
        this.popupTitle.innerHTML = this.popupTitleHTML;
        this.popupTitle.querySelector(this.options.popupCountSelector).textContent = this.options.maxItems - this.items.length;
      }
    }

    if (text) {
      text.classList.toggle(this.options.hiddenClass, isMax);
    }

    if (info) {
      info.classList.toggle(this.options.hiddenClass, !isMax);
    }

    if (compareButton) {
      compareButton.disabled = this.items.length < this.options.minItemsToCompare;
    }
  }

  // Renders one popup `<li>` from the item template
  buildPopupItemHTML(item) {
    if (!this.popupItemTemplate) return '';

    const vehicle = this.findVehicleData(item.postid);

    return this.popupItemTemplate({
      postid: item.postid,
      posttype: item.posttype,
      photo: vehicle ? vehicle.photo : '',
      title: vehicle ? vehicle.title : '',
    });
  }

  // Finds the fetched vehicle entry matching a postid by parsing its embedded card HTML
  findVehicleData(postid) {
    return this.vehicles.find((vehicle) => this.extractPostId(vehicle.html) === postid);
  }

  compileTemplate(selector) {
    const source = document.querySelector(selector);

    return source ? Handlebars.compile(source.innerHTML) : null;
  }

  renderCompareTable() {
    if (!this.compareTable || !this.columnTemplate) return;

    const columnsHTML = this.items.map((item) => this.columnTemplate(this.buildCompareColumnData(item)));

    columnsHTML.push(...this.buildEmptyColumnsHTML());

    this.compareTable.innerHTML = columnsHTML.join('');

    Array.from(this.compareTable.children).forEach((column, index) => {
      const item = this.items[index];
      const vehicle = item && this.findVehicleData(item.postid);

      this.fillTermFields(column, (vehicle && vehicle.terms) || {});
    });

    this.updateDeleteButtonVisibility();
    this.updateSimilarityHighlight();
  }

  // Hides the delete button on the sole remaining column, so the last item can't be removed from the table
  updateDeleteButtonVisibility() {
    if (this.items.length !== 1) return;

    const deleteButton = this.compareTable.querySelector(this.options.compareDeleteButtonSelector);

    if (deleteButton) deleteButton.classList.add(this.options.hiddenClass);
  }

  // Fills the remaining grid slots (up to maxItems): one "add more" prompt, then skeleton placeholders
  buildEmptyColumnsHTML() {
    const remaining = this.options.maxItems - this.items.length;

    if (remaining <= 0) return [];

    const columns = [];

    if (this.emptyColumnTemplate) columns.push(this.emptyColumnTemplate({ remaining }));

    while (columns.length < remaining && this.skeletonColumnTemplate) {
      columns.push(this.skeletonColumnTemplate());
    }

    return columns;
  }

  buildCompareColumnData(item) {
    const vehicle = this.findVehicleData(item.postid) || {};

    return {
      postid: item.postid,
      posttype: item.posttype,
      photo: vehicle.photo || '',
      title: this.buildVehicleTitle(vehicle.terms || {}),
      link: vehicle.link || '',
      price: vehicle.price ? `MSRP: $${vehicle.price}` : '—',
      favoriteButton: this.extractFavoriteButtonHTML(vehicle.html),
    };
  }

  // Reuses the grid card's own favorite button markup, so it keeps whatever attributes the plugin's JS relies on.
  // Matches either the WP-mode plugin's button (simplefavorite-button) or the API-mode one (api-favorite-button).
  extractFavoriteButtonHTML(html) {
    const match = /<button[^>]*class="[^"]*(?:simplefavorite-button|api-favorite-button)[^"]*"[^>]*>[\s\S]*?<\/button>/.exec(html || '');

    return match ? match[0] : '';
  }

  // Builds the compare table's title from year/make/model terms instead of the full post title
  buildVehicleTitle(terms) {
    return ['year', 'make', 'model']
      .map((key) => (Array.isArray(terms[key]) && terms[key].length ? terms[key][0] : ''))
      .filter(Boolean)
      .join(' ');
  }

  fillTermFields(column, terms) {
    column.querySelectorAll(this.options.termFieldSelector).forEach((field) => {
      const value = terms[field.dataset.term];
      const target = field.querySelector(this.options.compareTextSelector) || field;
      const emptyValue = field.dataset.emptyValue !== undefined ? field.dataset.emptyValue : '—';

      target.textContent = Array.isArray(value) && value.length ? (field.dataset.valuePrefix || '') + value.join(', ') : emptyValue;
    });
  }

  // Dims spec rows where every column has the same value, when "Hide Similarities" is on
  updateSimilarityHighlight() {
    if (!this.compareTable || !this.similaritySwitch) return;

    const columns = Array.from(this.compareTable.querySelectorAll(this.options.compareColumnSelector)).filter(
      (column) => !column.classList.contains(this.options.emptyColumnClass)
    );

    if (columns.length < 2) return;

    const isEnabled = this.similaritySwitch.checked;
    const rowCount = columns[0].children.length;

    for (let rowIndex = this.options.compareSpecRowsStart; rowIndex < rowCount; rowIndex++) {
      const values = columns.map((column) => this.getRowText(column, rowIndex));
      const isSimilar = values.every((value) => value === values[0]);

      columns.forEach((column) => {
        if (column.children[rowIndex]) {
          column.children[rowIndex].classList.toggle(this.options.similarClass, isEnabled && isSimilar);
        }
      });
    }
  }

  // Reads the trimmed text of a column's spec row, by index
  getRowText(column, rowIndex) {
    const row = column.children[rowIndex];
    const text = row && row.querySelector(this.options.compareTextSelector);

    return text ? text.textContent.trim() : '';
  }

  // Extracts the postid embedded in a vehicle's server-rendered card HTML.
  // Not digits-only: API-mode cards key on VIN (alphanumeric), WP-mode cards on the numeric post ID.
  extractPostId(html) {
    const match = /data-postid="([^"]+)"/.exec(html || '');

    return match ? match[1] : null;
  }

  // Finds the index of an item in the compare list by postid/posttype
  findItemIndex(identity) {
    return this.items.findIndex((item) => item.postid === identity.postid && item.posttype === identity.posttype);
  }

  // Reads the compare list from the cookie
  getStoredItems() {
    try {
      return JSON.parse(Cookies.get(this.options.cookieName) || '[]');
    } catch (e) {
      return [];
    }
  }

  // Persists the compare list to the cookie
  saveItems() {
    Cookies.set(this.options.cookieName, JSON.stringify(this.items));
  }
}

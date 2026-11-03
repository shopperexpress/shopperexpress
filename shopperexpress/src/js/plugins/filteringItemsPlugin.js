import $ from 'jquery';

/* Filtering plugin */
(function ($) {
  function FilteringItems(options) {
    this.options = $.extend(
      {
        items: '.item',
        filterItems: '[data-filter]',
        filterHolder: '.models-filter',
        activeClass: 'active',
        hiddenClass: 'hidden',
        noResultsClass: 'no-results',
      },
      options
    );

    this.init();
  }

  FilteringItems.prototype = {
    init: function () {
      if (this.options.holder) {
        this.findElements();
        this.attachEvents();
        this.makeCallback('onInit', this);
      }
    },
    findElements: function () {
      this.page = $('html, body');
      this.holder = $(this.options.holder);
      this.items = this.holder.find(this.options.items);
      this.filterHolder = this.holder.find(this.options.filterHolder);
      this.filterItems = this.holder.find(this.options.filterItems);
      this.filterParents = this.filterItems.parent();
      this.activeFilter = '';
      this.activeItems = $();
    },
    attachEvents: function () {
      const self = this;

      this.filterItems.on('click', function (e) {
        e.preventDefault();
        const currItem = $(this);

        if (!self.holder.hasClass('dropdown-models')) {
          self.holder.css({
            height: self.holder.outerHeight(true),
            overflow: 'hidden',
            position: 'relative',
          });
        }

        setTimeout(function () {
          self.activeFilter = '';
          self.filterParents.removeClass(self.options.activeClass);
          currItem.parent().addClass(self.options.activeClass);
          self.activeFilter = currItem.data('filter');
          self.filtrationItems();
        }, 10);
      });
    },
    filtrationItems: function () {
      const self = this;

      this.makeCallback('beforeFilter', this);
      this.items.addClass(this.options.hiddenClass);
      this.holder.removeClass(this.options.noResultsClass);

      if (this.activeFilter !== 'all') {
        this.activeItems = this.items.filter(function (ind, item) {
          let matched = false;

          $(item)
            .find('.' + self.filterHolder.data('filter-group'))
            .each(function () {
              const text = self.replaceStr($(this).text());

              if (text.indexOf(self.replaceStr(self.activeFilter)) !== -1) {
                matched = true;
              }
            });

          return matched;
        });
      } else {
        this.activeItems = this.items;
      }

      this.activeItems.removeClass(this.options.hiddenClass);

      this.makeCallback('onFilter', this);

      if (!this.activeItems.length) {
        this.holder.addClass(this.options.noResultsClass);
      }

      this.holder.css({
        height: '',
        overflow: '',
        position: '',
      });
    },
    replaceStr: function (str) {
      return str.trim().toLowerCase().replace(/ /g, '-').replace(/&/g, 'and').replace(/\//g, '-');
    },
    makeCallback: function (name) {
      if (typeof this.options[name] === 'function') {
        const args = Array.prototype.slice.call(arguments);

        args.shift();
        this.options[name].apply(this, args);
      }
    },
  };

  // jQuery plugin interface
  $.fn.filteringItems = function (opt) {
    return this.each(function () {
      $(this).data(
        'FilteringItems',
        new FilteringItems(
          $.extend(opt, {
            holder: this,
          })
        )
      );
    });
  };
})($);

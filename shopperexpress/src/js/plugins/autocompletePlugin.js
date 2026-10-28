import $ from 'jquery';

/*
 * jQuery Autocomplete
 */
(function ($) {
  function Autocomplete(options) {
    this.options = $.extend(
      {
        startCount: 1,
        maxHeight: false,
        scrollClass: 'scroll-active',
        dataAttr: 'search',
        dataType: 'text',
        listItems: 'li',
        listItemsFillsInput: true,
        searchingClass: 'searching',
        alwaysRefresh: false,
        filterResults: true,
        highlightMatches: false,
        showOnFocus: true,
        preventEnterClick: false,
        queryStr: '',
        selectedClass: 'selected-line',
        disabledClass: 'disabled-line',
        noResultClass: 'no-results',
        resultsBox: '.ajax-drop',
        resultsHolder: '.autocomplete-results',
        inputField: 'input.text-input',
        hideDelay: 200,
        urlAttr: 'data-url',
        noResultBox: '.no-results',
        parseData: null, // need function for dataType:'json'
      },
      options
    );

    this.init();
  }

  Autocomplete.prototype = {
    init: function () {
      if (this.options.holder) {
        this.findElements();
        this.attachEvents();
        this.makeCallback('onInit');
      }
    },
    findElements: function () {
      this.form = $(this.options.holder);
      this.target = this.form.attr(this.options.urlAttr);
      this.input = this.form.find(this.options.inputField).attr('autocomplete', 'off');
      this.resultsBox = this.form.find(this.options.resultsBox).hide();
      this.resultsHolder = this.form.find(this.options.resultsHolder).show();
      this.noResultBox = this.form.find(this.options.noResultBox);
      this.currentIndex = 0;
      this.typedTimer = null;

      if (this.options.filterResults) this.options.alwaysRefresh = false;
    },
    attachEvents: function () {
      this.input
        .on('keyup', (e) => {
          if (e.keyCode === 27 || e.keyCode === 38 || e.keyCode === 40) return;

          if (this.options.preventEnterClick && e.key === 'Enter') return;

          if (e.key === 'Enter' || this.input.val().length < this.options.startCount) {
            this.hideDrop();
            clearTimeout(this.typedTimer);

            this.typedTimer = null;

            this.form.removeClass(this.options.searchingClass);
          } else {
            if (this.options.alwaysRefresh) {
              clearTimeout(this.typedTimer);

              this.form.addClass(this.options.searchingClass);

              this.typedTimer = setTimeout(() => {
                this.loadData();
                this.resultsHolder.empty();
              }, 1000);
            } else {
              if (!this.listItems) {
                this.loadData();
              }

              this.filterData();
            }
          }
        })
        .on('keydown', (e) => {
          if (this.listItems) {
            this.visibleItems = this.listItems.filter(':visible').not('.' + this.options.disabledClass);
            this.visibleCount = this.visibleItems.length;

            switch (e.keyCode) {
              case 27:
                this.hideDrop();

                break;
              case 38:
                if (this.currentIndex >= 0) this.currentIndex--;

                break;
              case 40:
                if (this.currentIndex < this.visibleCount - 1) this.currentIndex++;

                break;
            }

            this.listItems.removeClass(this.options.selectedClass);

            if (this.currentIndex !== -1) {
              this.visibleItems.eq(this.currentIndex).addClass(this.options.selectedClass);
            }
          }
        })
        .on('focus', () => {
          clearTimeout(this.focusTimer);

          this.inFocus = true;

          if (this.input.val().trim() !== '' && this.form.hasClass(this.options.noResultClass)) {
            this.resultsHolder.empty();
            this.form.removeClass(this.options.noResultClass);
          }

          if (this.options.showOnFocus) {
            this[this.itemsLoaded ? 'showDrop' : 'loadData']();
          }
        })
        .on('blur', () => {
          this.inFocus = false;

          if (!this.options.showOnFocus) {
            this.focusTimer = setTimeout(() => {
              this.hideDrop();
            }, this.options.hideDelay);
          }
        })
        .on('input', () => {
          this.makeCallback('onKeyup', this.input.val());
        });

      if (this.options.showOnFocus) {
        $(document).on('click', (e) => {
          const clickTarget = $(e.target);

          if (!clickTarget.is(this.form) && clickTarget.closest(this.form).length === 0 && !this.inFocus) {
            this.hideDrop();
          }
        });
      }
    },
    loadData: function (callback) {
      if (this.acXHR && typeof this.acXHR.abort === 'function') this.acXHR.abort();

      const self = this;

      this.acXHR = $.ajax({
        url: this.target,
        dataType: this.options.dataType,
        data: self.options.dataAttr + '=' + self.input.val() + this.options.queryStr,
        success: function (msg) {
          if (!self.typedTimer) return;

          self.updateDrop(msg);
          self.filterData();
          self.showDrop();
          self.itemsLoaded = true;

          self.makeCallback('onLoadData', msg);
          self.form.removeClass(self.options.searchingClass);

          if (typeof callback === 'function') callback();
        },
        error: function () {
          if (typeof self.options.onerror === 'function') {
            self.options.onerror.apply(this, arguments);
          }
        },
      });
    },
    filterData: function () {
      const self = this;

      if (self.listItems) {
        self.showDrop();

        if (self.options.filterResults) {
          self.hideNoResult();

          if (self.options.maxHeight) {
            var summ = 0;
          }

          self.listItems.show().each(function () {
            const item = $(this);

            item.html(item.data('orightml'));

            if (item.text().toLowerCase().indexOf(self.input.val().toLowerCase()) != -1) {
              item.show();
              if (self.options.maxHeight) summ += item.outerHeight(true);
            } else {
              item.hide();
            }
          });

          if (self.options.maxHeight + 0) {
            if (summ > self.options.maxHeight) {
              self.resultsBox.addClass(self.options.scrollClass);

              self.resultsHolder.css({
                height: self.options.maxHeight,
              });
            } else {
              self.resultsBox.removeClass(self.options.scrollClass);

              self.resultsHolder.css({
                height: '',
              });
            }
          }

          if (!self.listItems.filter(':visible').length) {
            self.showNoResult();
          }
        }

        if (self.options.highlightMatches) {
          self.listItems.children().each(function (i, obj) {
            if (self.input.val().length >= self.options.startCount) {
              $(obj).html(highlightWords($(obj).text(), self.input.val()));
            }
          });
        }
      }
    },
    updateDrop: function (text) {
      if (this.lastData !== text) {
        let data = null;

        this.lastData = text;
        this.currentIndex = -1;

        if (this.options.dataType.toLowerCase() === 'json' && typeof this.options.parseData === 'function') {
          data = this.options.parseData.call(this, text) || text;
        } else {
          data = text;
        }

        this.resultsHolder.html(data);

        this.listItems = this.resultsHolder.find(this.options.listItems);

        this.listItems.each((i, item) => {
          const curItem = $(item);

          curItem.data('orightml', curItem.html());

          curItem.on('click', (e) => {
            e.preventDefault();

            this.selectItem(curItem);
          });

          curItem.on('hover', () => {
            this.listItems.removeClass(this.options.selectedClass);
            curItem.addClass(this.options.selectedClass);
            this.currentIndex = this.listItems.filter(':visible').index(curItem);
          });
        });
      }
    },
    showNoResult: function () {
      this.resultsHolder.hide();
      this.noResultBox.show();
      this.form.addClass(this.options.noResultClass);
    },
    hideNoResult: function () {
      this.resultsHolder.show();
      this.noResultBox.hide();
      this.form.removeClass(this.options.noResultClass);
    },
    showDrop: function () {
      if (this.input.val().length >= this.options.startCount) {
        this.resultsBox.show();
      } else {
        this.resultsBox.hide();
      }

      if (this.options.showOnFocus) {
        this.resultsBox.show();
      }
    },
    hideDrop: function () {
      this.resultsBox.hide();
    },
    selectItem: function (item) {
      this.hideDrop();
      this.makeCallback('onSelectedItem', item);
    },
    makeCallback(callbackName, ...args) {
      if (typeof this.options[callbackName] === 'function') {
        this.options[callbackName].apply(this, args);
      }
    },
  };

  function escapeRegExp(str) {
    return str.replace(new RegExp('[.*+?|()\\[\\]{}\\\\]', 'g'), '\\$&');
  }

  function highlightWords(str, word) {
    const regex = new RegExp('(' + escapeRegExp(word) + ')', 'gi');

    return str.replace(regex, '<strong>$1</strong>');
  }

  $.fn.autocomplete = function (options) {
    return this.each(function () {
      $(this).data(
        'AutoComplete',
        new Autocomplete(
          $.extend(options, {
            holder: this,
          })
        )
      );
    });
  };
})($);

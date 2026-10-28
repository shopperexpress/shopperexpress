import $ from 'jquery';

/*
 * Simple Mobile Navigation
 */
(function ($) {
  function MobileNav(options) {
    this.options = $.extend(
      {
        container: null,
        hideOnClickOutside: false,
        menuActiveClass: 'nav-active',
        menuOpener: '.nav-opener',
        menuDrop: '.nav-drop',
        toggleEvent: 'click',
        outsideClickEvent: 'click touchstart pointerdown MSPointerDown',
      },
      options
    );

    this.initStructure();
    this.attachEvents();
  }

  MobileNav.prototype = {
    initStructure: function () {
      this.page = $('html');
      this.container = $(this.options.container);
      this.opener = this.container.find(this.options.menuOpener);
      this.drop = this.container.find(this.options.menuDrop);
    },
    attachEvents: function () {
      const self = this;

      if (activateResizeHandler) {
        activateResizeHandler();

        activateResizeHandler = null;
      }

      this.outsideClickHandler = function (e) {
        if (self.isOpened()) {
          const target = $(e.target);

          if (self.opener.hasClass('filter-opener')) {
            if (!target.closest(self.opener).length && !target.closest(self.drop).length && !target.closest('.modal-filter').length) {
              self.hide();
            }
          } else {
            if (!target.closest(self.opener).length && !target.closest(self.drop).length) {
              self.hide();
            }
          }
        }
      };

      this.openerClickHandler = function (e) {
        e.preventDefault();
        self.toggle();
      };

      this.opener.on(this.options.toggleEvent, this.openerClickHandler);
    },
    isOpened: function () {
      return this.container.hasClass(this.options.menuActiveClass);
    },
    show: function () {
      this.container.addClass(this.options.menuActiveClass);

      if (this.options.hideOnClickOutside) {
        this.page.on(this.options.outsideClickEvent, this.outsideClickHandler);
      }

      this.makeCallback('onShow', this);
    },
    hide: function () {
      this.container.removeClass(this.options.menuActiveClass);

      if (this.options.hideOnClickOutside) {
        this.page.off(this.options.outsideClickEvent, this.outsideClickHandler);
      }

      this.makeCallback('onHide', this);
    },
    toggle: function () {
      if (this.isOpened()) {
        this.hide();
      } else {
        this.show();
      }
    },
    makeCallback: function (name) {
      if (typeof this.options[name] === 'function') {
        const args = Array.prototype.slice.call(arguments);

        args.shift();
        this.options[name].apply(this, args);
      }
    },
    destroy: function () {
      this.container.removeClass(this.options.menuActiveClass);
      this.opener.off(this.options.toggleEvent, this.clickHandler);
      this.page.off(this.options.outsideClickEvent, this.outsideClickHandler);
    },
  };

  var activateResizeHandler = function () {
    let win = $(window),
      doc = $('html'),
      resizeClass = 'resize-active',
      flag,
      timer;

    const removeClassHandler = function () {
      flag = false;
      doc.removeClass(resizeClass);
    };

    const resizeHandler = function () {
      if (!flag) {
        flag = true;
        doc.addClass(resizeClass);
      }

      clearTimeout(timer);
      timer = setTimeout(removeClassHandler, 500);
    };

    win.on('resize orientationchange', resizeHandler);
  };

  $.fn.mobileNav = function (opt) {
    const args = Array.prototype.slice.call(arguments);
    const method = args[0];

    return this.each(function () {
      const $container = jQuery(this);
      const instance = $container.data('MobileNav');

      if (typeof opt === 'object' || typeof opt === 'undefined') {
        $container.data(
          'MobileNav',
          new MobileNav(
            $.extend(
              {
                container: this,
              },
              opt
            )
          )
        );
      } else if (typeof method === 'string' && instance) {
        if (typeof instance[method] === 'function') {
          args.shift();
          instance[method].apply(instance, args);
        }
      }
    });
  };
})($);

import '../plugins/mobileNavPlugin';
import '../plugins/responsiveHelperPlugin';

// mobile menu init
export default function initMobileNav() {
  jQuery('body').mobileNav({
    menuActiveClass: 'nav-active',
    hideOnClickOutside: true,
    menuOpener: '.nav-opener',
    menuDrop: 'ul',
  });

  jQuery('body').mobileNav({
    menuActiveClass: 'navigation-active',
    menuOpener: '.navigation-opener',
    hideOnClickOutside: true,
    menuDrop: '.header-frame',
  });

  jQuery('.form-box').mobileNav({
    menuActiveClass: 'form-active',
    menuOpener: '.form-opener',
  });

  jQuery('.help-holder').mobileNav({
    menuActiveClass: 'popup-active',
    menuOpener: '.opener',
    hideOnClickOutside: true,
    menuDrop: '.popup',
  });

  jQuery('.drop-models-popup').mobileNav({
    menuActiveClass: 'popup-active',
    menuOpener: '.btn-models',
    hideOnClickOutside: true,
    menuDrop: '.dropdown-models',
  });

  jQuery('.drop-schedule-popup').mobileNav({
    menuActiveClass: 'popup-active',
    menuOpener: '.btn-schedule, .btn-close',
    hideOnClickOutside: true,
    menuDrop: '.dropdown-schedule',
  });

  jQuery('body').mobileNav({
    menuActiveClass: 'chat-active',
    menuOpener: '.ai-chat__opener, .ai-chat__close',
    hideOnClickOutside: false,
    menuDrop: '.ai-chat',
  });

  ResponsiveHelper.addRange({
    '..1200': {
      on: function () {
        jQuery('body').mobileNav({
          menuActiveClass: 'filter-active',
          menuOpener: '.filter-opener',
          hideOnClickOutside: true,
          menuDrop: '.aside',
        });
      },
      off: function () {
        jQuery('body').mobileNav('destroy');
      },
    },
  });
}

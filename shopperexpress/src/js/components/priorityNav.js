import '../plugins/openClosePlugin';
import '../plugins/responsiveHelperPlugin';
import priorityNav from '../plugins/priorityNavPlugin';

// Priority Navigation init
export default function initPriorityNav() {
  let originalNavHTML = null;

  const saveOriginalHTML = () => {
    const navHolder = jQuery('.nav-holder');

    if (navHolder.length && !originalNavHTML) {
      originalNavHTML = navHolder.prop('outerHTML');
    }
  };

  saveOriginalHTML();

  ResponsiveHelper.addRange({
    '..991': {
      on: () => {
        const navHolder = jQuery('.nav-holder');

        if (navHolder.length && originalNavHTML) {
          navHolder.replaceWith(originalNavHTML);
        }

        jQuery('.main-navigation > li').openClose({
          activeClass: 'active',
          opener: '.drop-opener',
          slider: '.slide',
          animSpeed: 400,
          effect: 'slide',
        });
      },
    },
    '992..': {
      on: () => {
        jQuery('.main-navigation > li').openClose('destroy');

        priorityNav.init({
          mainNavWrapper: '.nav-holder',
          mainNav: 'ul.main-navigation',
          navDropdownLabel: 'More',
          breakPoint: 1,
        });

        jQuery('.nav-holder .priority-nav__dropdown').attr('id', 'menu');
      },
    },
  });
}

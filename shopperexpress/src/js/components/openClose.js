import '../plugins/openClosePlugin';

// open-close init
export default function initOpenClose() {
  jQuery('#nav > li').openClose({
    activeClass: 'active',
    opener: '.slide-opener',
    slider: '.menu-slide',
    animSpeed: 400,
    effect: 'slide',
  });
}

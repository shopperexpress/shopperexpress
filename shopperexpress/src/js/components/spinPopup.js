import SpinPopup from '../plugins/spinPopupPlugin';

export default function initSpinPopup() {
  jQuery('a.btn-spin[data-fancybox]').each((index, element) => {
    new SpinPopup(element);
  });
}

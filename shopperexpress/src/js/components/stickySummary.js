import StickySummary from '../plugins/stickySummaryPlugin';

// Sticky summary block init
export default function initStickySummary() {
  document.querySelectorAll('.detail-section').forEach((holder) => {
    new StickySummary(holder, {
      fixedClass: 'fixed-summary',
      cloneBlockClass: 'sticky-clone',
      stickyBlock: '.sticky-summary .summary-holder',
      triggerBlock: '.info-block--top',
    });
  });
}

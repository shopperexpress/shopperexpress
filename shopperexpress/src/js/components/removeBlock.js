export default function initRemoveBlock() {
  jQuery('#block-1 .payment-info').each(function () {
    const holder = jQuery(this);
    const items = holder.find('> li');

    items.each(function () {
      const item = jQuery(this);
      const textHolder = item.find('.text-holder');
      const siblings = textHolder.siblings();

      if (!siblings.length) {
        item.remove();
      }
    });
  });
}

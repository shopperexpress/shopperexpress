// checked classes when element active
export default function initCheckedClasses() {
  const checkedClass = 'input-checked',
    parentCheckedClass = 'input-checked-parent';

  const pairs = [];

  jQuery('label[for]').each(function (index, label) {
    const input = jQuery('#' + label.htmlFor);

    label = jQuery(label);

    // click handler
    if (input.length) {
      pairs.push({ input: input, label: label });

      input.bind('click change', function () {
        if (input.is(':radio')) {
          jQuery.each(pairs, function (index, pair) {
            refreshState(pair.input, pair.label);
          });
        } else {
          refreshState(input, label);
        }
      });

      refreshState(input, label);
    }
  });

  // refresh classes
  function refreshState(input, label) {
    if (input.length) {
      if (input.is(':checked')) {
        input.parent().addClass(parentCheckedClass);
        label.addClass(checkedClass);
      } else {
        input.parent().removeClass(parentCheckedClass);
        label.removeClass(checkedClass);
      }
    }
  }
}

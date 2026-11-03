// checked classes when element active
export default function initFieldsSwitcher() {
  const holder = jQuery('.form-offer');

  holder.each(function () {
    const checkedClass = 'input-checked';
    const buttons = holder.find('[data-name]');
    const fields = holder.find('[data-field]');

    buttons.each(function () {
      const button = jQuery(this);

      if (button.is(':checked')) {
        refreshState(button);
      }
    });

    buttons.on('click', function () {
      refreshState(jQuery(this));
    });

    function refreshState(button) {
      fields.hide().val('');
      buttons.parent().removeClass(checkedClass);

      jQuery('[data-field="' + button.data('name') + '"]').show();

      button.parent().addClass(checkedClass);
    }
  });
}

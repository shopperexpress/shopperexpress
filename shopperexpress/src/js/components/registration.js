import '../plugins/formValidationPlugin';

export default function initRegistration() {
  jQuery('.register-form').formValidation({
    errorClass: 'input-error',
    addClassToParent: '.form-group',
    successSendClass: 'success-send',
    errorFormClass: 'error-send',
  });

  const loadingClass = 'loading';

  jQuery('.form-unlock').each(function () {
    const form = jQuery(this);
    const btnRegister = form.find('#register-button');
    const message = form.find('.register-message');

    btnRegister.on('click', function (e) {
      e.preventDefault();

      const data = form.serialize() + '&action=register_user';

      form.addClass(loadingClass);

      jQuery.ajax({
        type: 'POST',
        url: ajax.admin,
        data: data,
        success: function (results) {
          if (results['error']) {
            message.text(results['error']).show();
          } else {
            window.location.replace(results);
          }

          form.removeClass(loadingClass);
        },
        error: function (results) {
          message.text(results).show();
          form.removeClass(loadingClass);
        },
      });
    });
  });
}

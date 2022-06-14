(function ($) {
  "use strict";

  jQuery(document).ready(function ($) {

    $(document).on("submit", "#automotive_login_form", function (e) {
      e.preventDefault();

      var nonce = $(this).find("#remember_me").data("nonce");
      var url = $(this).find(".url");
      var username = $(this).find(".username_input");
      var password = $(this).find(".password_input");
      var empty_fields = false;

      if (!username.val()) {
        empty_fields = true;
        username.css("border", "1px solid #F00");
      } else {
        username.removeAttr("style");
      }

      if (!password.val()) {
        empty_fields = true;
        password.css("border", "1px solid #F00");
      } else {
        password.removeAttr("style");
      }

      if (!empty_fields) {

        jQuery.ajax({
          url: ajax.admin,
          type: 'POST',
          data: {action: 'ajax_login', username: username.val(), password: password.val(), nonce: nonce },
          success: function (response) {
            if ("success" == response) {
               window.location.replace(url.val());
            }else{
              jQuery('.login-message').html(response).show();
            } 
          }
        });
      }

    });

  });


})(jQuery);
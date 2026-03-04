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

// jQuery(document).ready(function($) {


// 	$(document).on('wpformsAjaxSubmitSuccess', function(event, formData) {

// 		var form = $('<div>').html(formData.data.confirmation);
// 		var fieldBlock = form.find('.fields');

// 		if ( fieldBlock ){
// 			try {
// 				var fieldData = JSON.parse(fieldBlock.text());
// 				console.log(fieldData)
// 				var customer_email = sha256(fieldData.fields['data-email'].toLowerCase().trim());
// 				var customer_phone = sha256(fieldData.fields['data-phone'].toLowerCase().trim().replace(/[^\w\s]/gi, '').replace(/\s+/g, ''));
// 				var customer_first_name = sha256(fieldData.fields['data-firstname'].toLowerCase().trim().replace(/[^\w\s]/gi, '').replace(/\s+/g, ''));
// 				var customer_last_name = sha256(fieldData.fields['data-lastname'].toLowerCase().trim().replace(/[^\w\s]/gi, '').replace(/\s+/g, ''));

// 				utag.link({
// 					'tealium_event': fieldData.fields['data-id'],
// 					'customer_email': customer_email,
// 					'customer_phone': customer_phone,
// 					'customer_first_name': customer_first_name,
// 					'customer_last_name': customer_last_name
// 				});
// 			} catch (error) {
// 				console.error("JSON:", error);
// 			}
// 		} else {
// 			console.error(".fields empty.");
// 		}
// 	});
// });
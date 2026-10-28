import { sha256 } from 'js-sha256';

export default function initAjaxForm() {
  jQuery(document).on('wpformsAjaxSubmitSuccess', function (event, formData) {
    const form = jQuery('<div>').html(formData.data.confirmation);
    const fieldBlock = form.find('.fields');

    if (fieldBlock.length && window.utag) {
      const fieldData = JSON.parse(fieldBlock.text());

      if (!fieldData) return;

      const id = fieldData.fields['data-id'];
      const email = fieldData.fields['data-email'];
      const phone = fieldData.fields['data-phone'];
      const firstName = fieldData.fields['data-firstname'];
      const lastName = fieldData.fields['data-lastname'];
      let customer_email = '';
      let customer_phone = '';
      let customer_first_name = '';
      let customer_last_name = '';

      if (email) {
        // lowercased and trimmed, then SHA256 hashed*
        customer_email = sha256(email.toLowerCase().trim());
      }

      if (phone) {
        // digits only (1-9), no country code, then SHA256 hashed*
        customer_phone = sha256(
          phone
            .toLowerCase()
            .trim()
            .replace(/[^\w\s]/gi, '')
            .replace(/\s+/g, '')
        );
      }

      if (firstName) {
        // lowercased and all whitespace/special chars removed, then SHA256 hashed*
        customer_first_name = sha256(
          firstName
            .toLowerCase()
            .trim()
            .replace(/[^\w\s]/gi, '')
            .replace(/\s+/g, '')
        );
      }

      if (lastName) {
        // lowercased and all whitespace/special chars removed, then SHA256 hashed*
        customer_last_name = sha256(
          lastName
            .toLowerCase()
            .trim()
            .replace(/[^\w\s]/gi, '')
            .replace(/\s+/g, '')
        );
      }

      window.utag.link({
        tealium_event: id,
        customer_email: customer_email,
        customer_phone: customer_phone,
        customer_first_name: customer_first_name,
        customer_last_name: customer_last_name,
      });
    }
  });
}

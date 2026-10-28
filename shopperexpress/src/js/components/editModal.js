import EditModal from '../plugins/editModalPlugin';

// Edit modal init
export default function initEditModal() {
  document.querySelectorAll('.modal-edit').forEach((modal) => {
    // API mode: form id is api-edit-form — handled separately below.
    if (!modal.querySelector('#api-edit-form')) {
      new EditModal(modal);
    }
  });

  // API mode edit modal — save to Intice Nexus via wps_api_save_vehicle.
  const $apiModal = jQuery('#editModal:has(#api-edit-form)');
  if ($apiModal.length) {
    const $form = $apiModal.find('#api-edit-form');
    const $errorAlert = $apiModal.find('.alert.bg-danger');
    const $successAlert = $apiModal.find('.alert.bg-success');

    $apiModal.on('click', '[data-save]', function (e) {
      e.preventDefault();

      const formData = new FormData($form[0]);
      formData.append('action', 'wps_api_save_vehicle');

      $form.addClass('loading');
      $errorAlert.removeClass('show');
      $successAlert.removeClass('show');

      jQuery.ajax({
        url: window.ajax.admin,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          $form.removeClass('loading');
          if (response.success) {
            $successAlert.addClass('show');
          } else {
            $errorAlert.addClass('show');
            console.error(response.data?.message || 'API save error');
          }
        },
        error: function (xhr) {
          $form.removeClass('loading');
          $errorAlert.addClass('show');
          console.error('AJAX Error:', xhr);
        },
      });
    });

    $apiModal.on('click', '.close-alert', function () {
      jQuery(this).closest('.alert').removeClass('show');
    });

    $apiModal.on('hidden.bs.modal', function () {
      $errorAlert.removeClass('show');
      $successAlert.removeClass('show');
    });

    // API mode: per-vehicle cache clear (same 'clear' action/handler as legacy mode).
    $apiModal.on('click', '[data-clear]', function (e) {
      e.preventDefault();

      const postType = jQuery(this).data('clear');

      $form.addClass('loading');
      $errorAlert.removeClass('show');
      $successAlert.removeClass('show');

      jQuery.ajax({
        url: window.ajax.admin,
        type: 'POST',
        data: {
          action: 'clear',
          post_type: postType,
        },
        success: function (response) {
          $form.removeClass('loading');
          if (response.success) {
            $successAlert.find('strong').text(response.data.message);
            $successAlert.addClass('show');
          } else {
            $errorAlert.find('strong').text(response.data?.message || 'Clear cache error');
            $errorAlert.addClass('show');
            console.error(response.data?.message || 'Clear cache error');
          }
        },
        error: function (xhr) {
          $form.removeClass('loading');
          $errorAlert.find('strong').text('Clear cache request failed.');
          $errorAlert.addClass('show');
          console.error('AJAX Error:', xhr);
        },
      });
    });
  }

  // Confirm delete button handler
  jQuery('#confirmYes').on('click', function (e) {
    e.preventDefault();

    const $btn = jQuery(this);
    const vin = $btn.data('vin');

    // API mode: trash the vehicle via Intice Nexus (soft-delete), keyed by VIN.
    if (vin) {
      const nonce = jQuery('#api-edit-form input[name="delete_nonce"]').val();

      jQuery.ajax({
        url: window.ajax.admin,
        type: 'POST',
        data: {
          action: 'wps_api_delete_vehicle',
          vin: vin,
          nonce: nonce,
        },
        success: function (response) {
          if (response.success) {
            window.location.href = '/';
          } else {
            console.error('Error: ' + (response.data?.message || response.data));
          }
        },
        error: function () {
          console.error('AJAX request failed');
        },
      });

      return;
    }

    const post_id = $btn.data('post-id');

    jQuery.ajax({
      url: window.ajax.admin,
      type: 'POST',
      data: {
        action: 'delete_listings',
        post_id: post_id,
      },
      success: function (response) {
        if (response.success) {
          window.location.href = '/';
        } else {
          console.error('Error: ' + response.data);
        }
      },
      error: function () {
        console.error('AJAX request failed');
      },
    });
  });
}

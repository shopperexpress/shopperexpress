/*
 * Edit Modal module
 */
export default class EditModal {
  constructor(holder, options) {
    this.options = {
      formID: 'custom-acf-form',
      loadingClass: 'loading',
      errorAlert: '.alert.bg-danger',
      successAlert: '.alert.bg-success',
      btnClearCashSelector: '[data-clear]',
      btnSaveSelector: '[data-save]',
      showClass: 'show',
      closeAlertButton: '.close-alert',
      ...options,
    };

    if (holder) {
      this.holder = holder;

      this.init();
    }
  }

  init() {
    this.form = this.holder.querySelector(`#${this.options.formID}`);
    this.errorAlert = this.holder.querySelector(this.options.errorAlert);
    this.successAlert = this.holder.querySelector(this.options.successAlert);
    this.closeAlertButtons = this.holder.querySelectorAll(this.options.closeAlertButton);

    if (!this.form) return;

    this.attachEvents();
  }

  attachEvents() {
    acf.unload.disable();

    this.holder.addEventListener('click', (e) => {
      const target = e.target;

      if (target.closest(this.options.btnSaveSelector)) {
        e.preventDefault();

        this.formData = new FormData(this.form);

        this.formData.append('action', 'save_listing');

        this.validateAcfForm();
      } else if (target.closest(this.options.btnClearCashSelector)) {
        e.preventDefault();

        this.clearCash(target.dataset.clear);
      }
    });

    jQuery(this.holder).on('hidden.bs.modal', () => {
      this.hideAlerts();
    });

    this.closeAlertButtons?.forEach((btn) => {
      const alert = btn.closest('.alert');

      btn.addEventListener('click', (e) => {
        e.preventDefault();

        if (alert) {
          alert.classList.remove(this.options.showClass);
        }
      });
    });
  }

  submitForm() {
    jQuery.ajax({
      url: window.ajax.admin,
      type: 'POST',
      data: this.formData,
      processData: false,
      contentType: false,
      beforeSend: () => {
        this.form.classList.add(this.options.loadingClass);
      },
      success: (response) => {
        this.form.classList.remove(this.options.loadingClass);

        if (response.success) {
          this.onSuccess(response.data.message);
        } else {
          console.error(response.data?.message || 'Error saving fields');
        }
      },
      error: (xhr) => {
        this.form.classList.remove(this.options.loadingClass);

        console.error('AJAX Error:', xhr);
      },
    });
  }

  clearCash(postType) {
    jQuery.ajax({
      url: window.ajax.admin,
      type: 'POST',
      data: {
        action: 'clear',
        post_type: postType,
      },
      beforeSend: () => {
        this.form.classList.add(this.options.loadingClass);
      },
      success: (response) => {
        this.form.classList.remove(this.options.loadingClass);

        this.onSuccess(response.data.message);
      },
      error: (xhr) => {
        this.form.classList.remove(this.options.loadingClass);

        console.error('AJAX Error:', xhr);
      },
    });
  }

  validateAcfForm() {
    if (typeof acf === 'undefined') return;

    this.hideAlerts();

    const fields = acf.getFields({
      parent: this.form,
    });

    let isValid = true;
    let firstInvalidField = null;

    fields.forEach((field) => {
      // Clear previous errors
      if (typeof field.removeError === 'function') {
        field.removeError();
      }

      // Validate required fields
      if (field.get('required') && !field.val()) {
        isValid = false;

        if (!firstInvalidField) {
          firstInvalidField = field.$el[0];
        }

        if (typeof field.showError === 'function') {
          field.showError(acf.__('This field is required'));
        }
      }
    });

    if (isValid) {
      this.submitForm();
    } else {
      this.onError();
      this.focusFirstInvalidField(firstInvalidField);
    }
  }

  hideAlerts() {
    if (this.errorAlert) {
      this.errorAlert.classList.remove(this.options.showClass);
    }

    if (this.successAlert) {
      this.successAlert.classList.remove(this.options.showClass);
    }
  }

  onSuccess(message) {
    if (this.successAlert && message.trim() !== '') {
      const messageElem = this.successAlert.querySelector('strong');

      if (messageElem) {
        messageElem.textContent = message;
      }

      this.successAlert.classList.add(this.options.showClass);
    }
  }

  onError() {
    if (this.errorAlert) {
      this.errorAlert.classList.add(this.options.showClass);
    }
  }

  focusFirstInvalidField(fieldElement) {
    if (!fieldElement) return;

    const tabPane = fieldElement.closest('.tab-pane');

    if (tabPane && !tabPane.classList.contains('active')) {
      const tabId = tabPane.getAttribute('id');
      const tabTrigger = this.holder.querySelector(`[data-toggle="tab"][data-target="#${tabId}"]`);

      if (tabTrigger) {
        const tab = new bootstrap.Tab(tabTrigger);

        tab.show();
      }
    }

    setTimeout(() => {
      const modalBody = this.holder.querySelector('.modal-body');

      if (modalBody) {
        const fieldRect = fieldElement.getBoundingClientRect();
        const modalRect = modalBody.getBoundingClientRect();

        const offset = fieldRect.top - modalRect.top + modalBody.scrollTop - 50;

        modalBody.scrollTo({
          top: offset,
          behavior: 'smooth',
        });
      }
    }, 300);
  }
}

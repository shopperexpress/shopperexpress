import $ from 'jquery';

/*
 * jQuery form validation plugin
 */
(function ($) {
  'use strict';

  const FormValidation = (function () {
    const Validator = function ($field, $fields) {
      this.$field = $field;
      this.$fields = $fields;
    };

    Validator.prototype = {
      reg: {
        email: '^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$',
        number: '^[0-9]+$',
      },

      checkField: function () {
        return {
          state: this.run(),
          $fields: this.$field.add(this.additionalFields),
        };
      },

      run: function () {
        let fieldType;

        switch (this.$field.get(0).tagName.toUpperCase()) {
          case 'SELECT':
            fieldType = 'select';
            break;

          case 'TEXTAREA':
            fieldType = 'text';
            break;

          default:
            fieldType = this.$field.data('type') || this.$field.attr('type');
        }

        const functionName = 'check_' + fieldType.replace(/-/g, '_');
        let state = true;

        if ($.isFunction(this[functionName])) {
          state = this[functionName]();

          if (state && this.$field.data('confirm')) {
            state = this.check_confirm();
          }
        }

        return state;
      },

      check_email: function () {
        const value = this.getValue();
        const required = this.$field.data('required');
        const requiredOrValue = required || value.length;

        if (requiredOrValue && !this.check_regexp(value, this.reg.email)) {
          return false;
        }

        return requiredOrValue ? true : null;
      },

      check_number: function () {
        let value = this.getValue();
        const required = this.$field.data('required');
        const isNumber = this.check_regexp(value, this.reg.number);
        const requiredOrValue = required || value.length;

        if (requiredOrValue && !isNumber) {
          return false;
        }

        const min = this.$field.data('min');
        const max = this.$field.data('max');

        value = +value;

        if ((min && (value < min || !isNumber)) || (max && (value > max || !isNumber))) {
          return false;
        }

        return requiredOrValue || min || max ? true : null;
      },

      check_password: function () {
        return this.check_text();
      },

      check_text: function () {
        const value = this.getValue();
        const required = this.$field.data('required');

        if (this.$field.data('required') && !value.length) {
          return false;
        }

        const min = +this.$field.data('min');
        const max = +this.$field.data('max');

        if ((min && value.length < min) || (max && value.length > max)) {
          return false;
        }

        const regExp = this.$field.data('regexp');

        if (regExp && !this.check_regexp(value, regExp)) {
          return false;
        }

        return required || min || max || regExp ? true : null;
      },

      check_confirm: function () {
        const value = this.getValue();
        const $confirmFields = this.$fields.filter('[data-confirm="' + this.$field.data('confirm') + '"]');
        let confirmState = true;

        for (let i = $confirmFields.length - 1; i >= 0; i--) {
          if ($confirmFields.eq(i).val() !== value || !value.length) {
            confirmState = false;
            break;
          }
        }

        this.additionalFields = $confirmFields;

        return confirmState;
      },

      check_select: function () {
        const required = this.$field.data('required');

        if (required && this.$field.get(0).selectedIndex === 0) {
          return false;
        }

        return required ? true : null;
      },

      check_radio: function () {
        const $fields = this.$fields.filter('[name="' + this.$field.attr('name') + '"]');
        const required = this.$field.data('required');

        if (required && !$fields.filter(':checked').length) {
          return false;
        }

        this.additionalFields = $fields;

        return required ? true : null;
      },

      check_checkbox: function () {
        const required = this.$field.data('required');

        if (required && !this.$field.prop('checked')) {
          return false;
        }

        return required ? true : null;
      },

      check_at_least_one: function () {
        const $fields = this.$fields.filter('[data-name="' + this.$field.data('name') + '"]');

        if (!$fields.filter(':checked').length) {
          return false;
        }

        this.additionalFields = $fields;

        return true;
      },

      check_regexp: function (val, exp) {
        return new RegExp(exp).test(val);
      },

      getValue: function () {
        if (this.$field.data('trim')) {
          this.$field.val($.trim(this.$field.val()));
        }

        return this.$field.val();
      },
    };

    const publicClass = function (form, options) {
      this.$form = $(form).attr('novalidate', 'novalidate');
      this.options = options;
    };

    publicClass.prototype = {
      buildSelector: function (input) {
        return input + ':not(' + this.options.skipDefaultFields + (this.options.skipFields ? ',' + this.options.skipFields : '') + ')';
      },

      init: function () {
        this.fieldsSelector = this.buildSelector(':input');

        this.$form
          .on('submit', this.submitHandler.bind(this))
          .on('keyup blur', this.fieldsSelector, this.changeHandler.bind(this))
          .on('change', this.buildSelector('select'), this.changeHandler.bind(this))
          .on('focus', this.fieldsSelector, this.focusHandler.bind(this));
      },

      submitHandler: function (e) {
        const self = this;
        const $fields = this.getFormFields();

        this.getClassTarget($fields).removeClass(this.options.errorClass + ' ' + this.options.successClass);

        this.setFormState(true);

        $fields.each(function (i, input) {
          const $field = $(input);
          const $classTarget = self.getClassTarget($field);

          // continue iteration if $field has error class already
          if ($classTarget.hasClass(self.options.errorClass)) {
            return;
          }

          self.setState(new Validator($field, $fields).checkField());
        });

        return this.checkSuccess($fields, e);
      },

      checkSuccess: function ($fields, e) {
        const self = this;

        const success = this.getClassTarget($fields || this.getFormFields()).filter('.' + this.options.errorClass).length === 0;

        if (e && success && this.options.successSendClass) {
          e.preventDefault();

          $.ajax({
            url: this.$form.removeClass(this.options.successSendClass).attr('action') || '/',
            type: this.$form.attr('method') || 'POST',
            data: this.$form.serialize() + '&action=register_user',
            success: function (data) {
              self.$form.addClass(self.options.successSendClass);
              self.makeCallback('onSuccess', data);
            },
            error: function (data) {
              self.makeCallback('onError', data);
            },
          });
        }

        this.setFormState(success);

        return success;
      },

      changeHandler: function (e) {
        const $field = $(e.target);

        if ($field.data('interactive')) {
          this.setState(new Validator($field, this.getFormFields()).checkField());
        }

        this.checkSuccess();
      },

      focusHandler: function (e) {
        const $field = $(e.target);

        this.getClassTarget($field).removeClass(this.options.errorClass + ' ' + this.options.successClass);

        this.checkSuccess();
      },

      setState: function (result) {
        this.getClassTarget(result.$fields)
          .toggleClass(this.options.errorClass, result.state !== null && !result.state)
          .toggleClass(this.options.successClass, result.state !== null && this.options.successClass && !!result.state);
      },

      setFormState: function (state) {
        if (this.options.errorFormClass) {
          this.$form.toggleClass(this.options.errorFormClass, !state);
        }
      },

      makeCallback: function (name) {
        if (typeof this.options[name] === 'function') {
          const args = Array.prototype.slice.call(arguments);

          args.shift();
          this.options[name].apply(this, args);
        }
      },

      getClassTarget: function ($input) {
        return this.options.addClassToParent ? $input.closest(this.options.addClassToParent) : $input;
      },

      getFormFields: function () {
        return this.$form.find(this.fieldsSelector);
      },
    };

    return publicClass;
  })();

  $.fn.formValidation = function (options) {
    options = $.extend(
      {},
      {
        errorClass: 'input-error',
        successClass: '',
        errorFormClass: '',
        addClassToParent: '',
        skipDefaultFields: ':button, :submit, :image, :hidden, :reset',
        skipFields: '',
        successSendClass: '',
      },
      options
    );

    return this.each(function () {
      new FormValidation(this, options).init();
    });
  };
})($);

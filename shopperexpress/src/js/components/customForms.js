import '../plugins/customFormsPlugin';

// Initialize custom form elements
export default function initCustomForms() {
  // Initialize custom selects
  jcf.setOptions('Select', {
    wrapNative: false,
    wrapNativeOnMobile: false,
  });

  jcf.replaceAll();

  // Initialize custom range sliders for filters
  jQuery('.range-row').each(function () {
    const holder = jQuery(this);
    const field = holder.find('[type="range"]');
    const minPrice = holder.find('.min-price');
    const maxPrice = holder.find('.max-price');
    const errorMessage = holder.find('.range-error').hide();
    const minErrorMessage = holder.data('error-min') || 'Please enter a value less than';
    const maxErrorMessage = holder.data('error-max') || 'Please enter a value greater than';
    const hiddenField = holder.find('[type="hidden"]');
    const defaultValues = field.data('jcfInstance').values;
    let values = [];
    let minValue = 0;
    let maxValue = 0;

    field.on('input', function () {
      values = field.data('jcfInstance').values;

      minValue = +field.attr('min');
      maxValue = +field.attr('max');

      updateInputValues(values);

      errorMessage.hide();
    });

    minPrice
      .add(maxPrice)
      .on('input', function () {
        const input = jQuery(this);
        const value = input.val().replace(/[^\d]/g, '');

        errorMessage.hide();

        if (value) {
          input.val(transformValueToMoney(value));
        } else {
          input.val('');
        }
      })
      .on('blur', function () {
        const input = jQuery(this);
        let value = +input.val().replace(/[^\d]/g, '');

        if (input.is(minPrice)) {
          if (value < minValue || value <= 0) {
            showErrorMessage(`${maxErrorMessage} ${minValue}`);
            value = minValue;
          } else if (value > values[1]) {
            showErrorMessage(`${minErrorMessage} ${values[1]}`);
            value = minValue;
          }
        }

        if (input.is(maxPrice)) {
          if (value > maxValue) {
            showErrorMessage(`${minErrorMessage} ${maxValue}`);
            value = maxValue;
          } else if (value < values[0] || value <= 0) {
            showErrorMessage(`${maxErrorMessage} ${values[0]}`);
            value = maxValue;
          }
        }

        if (value) {
          input.val(transformValueToMoney(value));
        }

        const newMinValue = minPrice.val().replace(/[^\d]/g, '');
        const newMaxValue = maxPrice.val().replace(/[^\d]/g, '');

        // Update range slider values
        field.data('jcfInstance').values = [newMinValue, newMaxValue];
        field.trigger('input');
        jcf.refresh(field);

        // Trigger change event to filtration
        field.trigger('change');
      });

    updateInputValues(defaultValues);

    function showErrorMessage(message) {
      setTimeout(() => {
        errorMessage.text(message).show();
      }, 10);
    }

    function updateInputValues(values) {
      const minValue = (Math.round(values[0] * 100) / 100).toString().trim();
      const maxValue = (Math.round(values[1] * 100) / 100).toString().trim();

      // Update min and max price inputs
      minPrice.val(transformValueToMoney(minValue));
      maxPrice.val(transformValueToMoney(maxValue));

      // Update hidden field with min and max values
      hiddenField.val(values[0].toString().trim() + ',' + values[1].toString().trim());
    }

    function transformValueToMoney(value) {
      const prefix = value > 0 ? '$' : '';

      return prefix + parseInt(value, 10).toLocaleString('en-US');
    }
  });

  jQuery('.modal-range-box').each(function () {
    const holder = jQuery(this);
    const field = holder.find('[type="range"]');
    const hiddenField = holder.find('[type="hidden"]');

    field.on('input', function () {
      const values = field.data('jcfInstance').values;

      hiddenField.val(values[0].toString().trim() + ',' + values[1].toString().trim());
    });
  });

  jQuery('.se-sbp-widget__range').each(function () {
    const holder = jQuery(this);
    const field = holder.find('[type="range"]');
    const rangeLine = holder.find('.jcf-range-display');
    const valueField = holder.find('.se-sbp-widget__range-input');
    const currency = field.data('currency');
    const rangeValue = field.data('range-value');
    const max = field.attr('max');
    const activeClass = 'half-path';

    field.on('input', function () {
      onChange();
    });

    onChange();

    function onChange() {
      const values = field.data('jcfInstance').values;

      if (+values[0] < +max / 2) {
        rangeLine.addClass(activeClass);
      } else {
        rangeLine.removeClass(activeClass);
      }

      valueField.val(currency + formatToMoney(+values[0]) + '-' + currency + formatToMoney(+values[0] + rangeValue));
    }
  });

  function formatToMoney(number) {
    return String(number).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  jQuery('#wpsl-wrap')
    .find('select')
    .each((i, it) => jcf.destroy(it));
}

document.addEventListener('DOMContentLoaded', function() {
    const skipCountryCode = yoaa_labels.skip_country_code;
  
    // Hide all original username & reg_username fields
    document.querySelectorAll('input[name="username"], input[name="reg_username"]').forEach(input => {
      const p = input.closest('p');
      if (p) p.style.display = 'none';
    });
  
    // Adjust checkout layout if needed (this is global)
    if (document.body.classList.contains('woocommerce-checkout')) {
      document.querySelectorAll('p.yoaa-username').forEach(el => {
        el.className = 'form-row form-row-first';
      });
    }

    function formatNationalNumber(number, iso2) {
      const utils = window.intlTelInputUtils || (window.intlTelInput && window.intlTelInput.utils);

      if (!utils || typeof utils.formatNumber !== 'function') {
        return '';
      }

      const nationalFormat = utils.numberFormat ? utils.numberFormat.NATIONAL : 'NATIONAL';

      return utils.formatNumber(number, iso2, nationalFormat);
    }

    function getIntlTelInputUiTranslations() {
      const translations = window.yoaaIntlTelInputTranslations || {};
      const locale = (yoaa_labels.intl_tel_input_locale || 'en').toLowerCase();

      return translations[locale] || translations[locale.split('-')[0]] || translations.en || {};
    }

    function getIntlTelInputOptions(acceptsTextInput) {
      const options = {
        initialCountry: yoaa_labels.initial_country,
        excludeCountries: yoaa_labels.excluded_countries,
        onlyCountries: yoaa_labels.specific_countries,
        uiTranslations: getIntlTelInputUiTranslations(),
        countryNameLocale: yoaa_labels.intl_tel_input_country_name_locale || yoaa_labels.intl_tel_input_locale || 'en'
      };

      if (acceptsTextInput) {
        options.strictMode = false;
        options.strictRejectAnimation = false;
      }

      return options;
    }
  
    // Helper to initialize a phone-field instance
    function initPhoneField(holderSelector, hiddenSelector, dialCodeSelector, acceptsTextInput = false) {
      document.querySelectorAll(`input[name="${holderSelector}"]`).forEach(holderInput => {
        // find the form (or wrapper) containing all three fields
        const formScope = holderInput.closest('form') || document;
        const hiddenInput = formScope.querySelector(`input[name="${hiddenSelector}"]`);
        const dialCodeInput = formScope.querySelector(`input[name="${dialCodeSelector}"]`);
        holderInput.dataset.yoaaBasePaddingLeft = window.getComputedStyle(holderInput).paddingLeft || '0px';

        function getInputBasePaddingLeft() {
          return holderInput.dataset.yoaaBasePaddingLeft || '0px';
        }

        function getPixelValue(value) {
          const parsed = parseFloat(value);

          return isNaN(parsed) ? 0 : parsed;
        }

        function hasTextCharacters(value) {
          return /[^\d\s()+.-]/.test(value);
        }

        function setCountrySelectorVisible(isVisible) {
          const wrapper = holderInput.closest('.iti');
          const countryBox = wrapper
            ? wrapper.querySelector('.iti__country-container')
            : formScope.querySelector('.iti__country-container');
          const basePaddingLeft = getInputBasePaddingLeft();

          if (countryBox) {
            countryBox.style.display = isVisible ? '' : 'none';
          }

          if (!isVisible || !countryBox) {
            holderInput.style.paddingLeft = basePaddingLeft;
            return;
          }

          const countryWidth = Math.ceil(countryBox.getBoundingClientRect().width);
          holderInput.style.paddingLeft = `${countryWidth + getPixelValue(basePaddingLeft)}px`;
        }
  
        function updateValue() {
          if (!hiddenInput || !dialCodeInput) return;
          const raw = holderInput.value.trim();
          if (!raw) {
            setCountrySelectorVisible(true);
            hiddenInput.value = '';
            return;
          }

          // text/email → treat as direct
          if (acceptsTextInput && hasTextCharacters(raw)) {
            setCountrySelectorVisible(false);
            hiddenInput.value = raw;
          } else {
            setCountrySelectorVisible(true);
            let digits = raw.replace(/\D/g, '');
            if (digits.charAt(0) === '0') digits = digits.slice(1);
            const code = dialCodeInput.value.replace(/^\+/, '');
            hiddenInput.value = code + '-' + digits;
          }
        }
  
        if (skipCountryCode) {
          holderInput.addEventListener('input', () => {
            if (hiddenInput) hiddenInput.value = holderInput.value.trim();
          });
        } else {
          // initialize intl-tel-input scoped to this holderInput
          const iti = window.intlTelInput(holderInput, getIntlTelInputOptions(acceptsTextInput));
  
          // set initial dial code + update
          if (dialCodeInput) {
            const data = iti.getSelectedCountry();
            dialCodeInput.value = '+' + data.dialCode;
            updateValue();
          }
  
          holderInput.addEventListener('input', updateValue);
          holderInput.addEventListener('countrychange', () => {
            const data = iti.getSelectedCountry();
            if (dialCodeInput) dialCodeInput.value = '+' + data.dialCode;
            updateValue();
          });
          holderInput.addEventListener('blur', () => {
            const val = holderInput.value.trim();
            if (val.charAt(0) === '+') {
              iti.setNumber(val);
              setTimeout(() => {
                const data = iti.getSelectedCountry();
                if (dialCodeInput) dialCodeInput.value = '+' + data.dialCode;
                const nat = formatNationalNumber(val, data.iso2);
                if (nat) holderInput.value = nat;
                updateValue();
              }, 100);
            } else {
              updateValue();
            }
          });
        }
      });
    }
  
    // Initialize both username + register‐username fields
    initPhoneField('username_holder', 'username', 'username_holder_dial_code', true);
    initPhoneField('reg_username_holder', 'username', 'reg_username_holder_dial_code');
  });
  

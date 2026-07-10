document.addEventListener('DOMContentLoaded', function() {
	let createAccountCheckbox = jQuery('#createaccount');
	let billingPhoneField = jQuery('#billing_phone');
	let accountUsernameField = jQuery('#account_username');
	let selectedCountryCodeField = jQuery('#billing_dial_code'); // Hidden field with country code

    let skipCountryCode = yoaa_labels.skip_country_code;
    let yobmActive = yoaa_labels.yobm_active;

    function readSelectedCountry(itiInstance) {
        if (!itiInstance || typeof itiInstance.getSelectedCountry !== 'function') {
            return {};
        }

        return itiInstance.getSelectedCountry();
    }

    function formatNationalNumber(number, iso2) {
        var utils = window.intlTelInputUtils || (window.intlTelInput && window.intlTelInput.utils);

        if (!utils || typeof utils.formatNumber !== 'function') {
            return '';
        }

        var nationalFormat = utils.numberFormat ? utils.numberFormat.NATIONAL : 'NATIONAL';

        return utils.formatNumber(number, iso2, nationalFormat);
    }

    function getIntlTelInputUiTranslations() {
        var translations = window.yoaaIntlTelInputTranslations || {};
        var locale = (yoaa_labels.intl_tel_input_locale || 'en').toLowerCase();

        return translations[locale] || translations[locale.split('-')[0]] || translations.en || {};
    }

    function getIntlTelInputOptions() {
        return {
            initialCountry: yoaa_labels.initial_country,
            excludeCountries: yoaa_labels.excluded_countries,
            onlyCountries: yoaa_labels.specific_countries,
            uiTranslations: getIntlTelInputUiTranslations(),
            countryNameLocale: yoaa_labels.intl_tel_input_country_name_locale || yoaa_labels.intl_tel_input_locale || 'en'
        };
    }

	if (billingPhoneField.length && accountUsernameField.length) {
		// Hide the account_username field row
		jQuery('#account_username_field').css('display', 'none');

		// Function to format the username
		function formatUsername() {
			let countryCode = selectedCountryCodeField.length ? selectedCountryCodeField.val().trim() : ''; // Check if field exists
			let billingPhone = billingPhoneField.val().trim(); // Get billing phone input

			if (!billingPhone) {
				return ''; // If no phone number, return empty string
			}
			billingPhone = billingPhone.replace(/[^0-9]/g, '').replace(/^0+/, '');

			if (countryCode && countryCode.startsWith('+')) {
				countryCode = countryCode.replace('+', ''); // Remove '+' (Convert +X to X)
			}

			return countryCode ? countryCode + '-' + billingPhone : billingPhone; // Format with or without country code
		}

		// Ensure 'Create an account' updates account username field
		createAccountCheckbox.on('change', function () {
			if (this.checked) {
				accountUsernameField.val(formatUsername()); // Set formatted username
			} else {
				accountUsernameField.val(''); // Clear if unchecked
			}
		});

		// Sync account username when billing phone updates
		billingPhoneField.on('input', function () {
			if (createAccountCheckbox.is(':checked')) {
				accountUsernameField.val(formatUsername());
			}
		});

		// Ensure WooCommerce recognizes account_username on form submission
		jQuery('form.checkout').on('submit', function () {
			if (createAccountCheckbox.is(':checked')) {
				accountUsernameField.val(formatUsername());
			}
		});
	}

	// If user is logged in and billing phone is empty, set it to username
    if (jQuery('body').hasClass('woocommerce-checkout')) {
        // Ensure billingPhoneField is defined and exists
        if (yoaa_labels.is_user_logged_in && billingPhoneField && billingPhoneField.val().trim() === '') {
            let sanitizedUsername = yoaa_labels.user_username;
    
            // If username contains '-', remove everything before and including the '-'
            if (sanitizedUsername.includes('-')) {
                sanitizedUsername = sanitizedUsername.split('-').pop().trim();
            }
    
            // Only set the billing phone if the sanitized username is numeric (only numbers)
            if (/^\d+$/.test(sanitizedUsername)) {
                billingPhoneField.val(sanitizedUsername);
            }
        }
    }

    // =========================
    // Initialize intl-tel-input for Billing Phone Field
    // =========================
    var billingPhoneInput = document.querySelector("#billing_phone");
    var billingDialCodeInput = document.querySelector("#billing_dial_code");
    var iti; // Billing intl-tel-input instance
    if (billingPhoneInput && typeof intlTelInput !== 'undefined' && typeof skipCountryCode !== "undefined" && !skipCountryCode && yobmActive) {
        iti = window.intlTelInput(billingPhoneInput, getIntlTelInputOptions());

        // Immediately set the billing dial code.
        if (billingDialCodeInput) {
            var countryData = readSelectedCountry(iti);
            billingDialCodeInput.value = '+' + countryData.dialCode;
        }

        // Update billing dial code on country change.
        billingPhoneInput.addEventListener('countrychange', function() {
            if (billingDialCodeInput) {
                var countryData = readSelectedCountry(iti);
                billingDialCodeInput.value = '+' + countryData.dialCode;
                // Sync shipping if shipping address is not different.
                syncShippingDialCode();
            }
        });

        billingPhoneInput.addEventListener('blur', function() {
            var entered = billingPhoneInput.value.trim();
            if (entered.charAt(0) === '+') {
                iti.setNumber(entered);
                setTimeout(function() {
                    var countryData = readSelectedCountry(iti);
                    if (billingDialCodeInput) {
                        billingDialCodeInput.value = '+' + countryData.dialCode;
                    }
                    var nationalNumber = formatNationalNumber(entered, countryData.iso2);
                    if (nationalNumber) {
                        billingPhoneInput.value = nationalNumber;
                    }
                    // Sync shipping if needed.
                    syncShippingDialCode();
                }, 100);
            }
        });
    }

    // =========================
    // Initialize intl-tel-input for Shipping Phone Field
    // =========================
    var shippingPhoneInput = document.querySelector("#shipping_phone");
    var shippingDialCodeInput = document.querySelector("#shipping_dial_code");
    var itiShipping; // Shipping intl-tel-input instance
    if (shippingPhoneInput && typeof intlTelInput !== 'undefined' && typeof skipCountryCode !== "undefined" && !skipCountryCode && yobmActive) {
        itiShipping = window.intlTelInput(shippingPhoneInput, getIntlTelInputOptions());

        // Immediately set the shipping dial code.
        if (shippingDialCodeInput) {
            var countryData = readSelectedCountry(itiShipping);
            shippingDialCodeInput.value = '+' + countryData.dialCode;
        }

        // Update shipping dial code on country change.
        shippingPhoneInput.addEventListener('countrychange', function() {
            // Only update from shipping if "Ship to different address" is checked.
            if (shipCheckbox && shipCheckbox.checked && shippingDialCodeInput) {
                var countryData = readSelectedCountry(itiShipping);
                shippingDialCodeInput.value = '+' + countryData.dialCode;
            }
        });

        shippingPhoneInput.addEventListener('blur', function() {
            var entered = shippingPhoneInput.value.trim();
            if (entered.charAt(0) === '+') {
                itiShipping.setNumber(entered);
                setTimeout(function() {
                    var countryData = readSelectedCountry(itiShipping);
                    if (shipCheckbox && shipCheckbox.checked && shippingDialCodeInput) {
                        shippingDialCodeInput.value = '+' + countryData.dialCode;
                    }
                    var nationalNumber = formatNationalNumber(entered, countryData.iso2);
                    if (nationalNumber) {
                        shippingPhoneInput.value = nationalNumber;
                    }
                }, 100);
            }
        });
    }

    // =========================
    // Sync Shipping Dial Code with Billing Dial Code if "Ship to Different Address" is unchecked.
    // Otherwise update shipping dial code based on itiShipping.
    // =========================
    var shipCheckbox = document.querySelector('#ship-to-different-address-checkbox');
    function syncShippingDialCode() {
        if (shipCheckbox && !shipCheckbox.checked && billingDialCodeInput && shippingDialCodeInput) {
            shippingDialCodeInput.value = billingDialCodeInput.value;
        }
    }

    // On page load, check checkbox state.
    if (shipCheckbox) {
        if (!shipCheckbox.checked) {
            syncShippingDialCode();
        } else if (itiShipping && shippingDialCodeInput) {
            var countryData = readSelectedCountry(itiShipping);
            shippingDialCodeInput.value = '+' + countryData.dialCode;
        }
        // Listen for changes on the checkbox.
        shipCheckbox.addEventListener('change', function() {
            if (!shipCheckbox.checked) {
                syncShippingDialCode();
            } else if (itiShipping && shippingDialCodeInput) {
                // When checked, update shipping dial code from shipping instance.
                var countryData = readSelectedCountry(itiShipping);
                shippingDialCodeInput.value = '+' + countryData.dialCode;
            }
        });
    }	
});

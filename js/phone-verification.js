jQuery(document).ready(function ($) {
    const noticeElement = $('#phone-verification-notice');
    const resendButton = $('#resend-phone-code');
    let countdown = parseInt(wc_advanced_accounts_verification_params.resend_countdown, 10) || 30;
    let resendCount = 0;
    const maxResendAttempts = parseInt($('#max_resend_attempts').val(), 10);
    let timerInterval;

    // Helper function to show notices with color based on type
    function showNotice(message, type = 'info') {
        // Explicitly remove all possible WooCommerce notice classes
        noticeElement.removeClass('woocommerce-info woocommerce-message woocommerce-error woocommerce-notice');
    
        // Add the appropriate class based on the type
        if (type === 'success') {
            noticeElement.addClass('woocommerce-message'); // Success message
        } else if (type === 'error') {
            noticeElement.addClass('woocommerce-error'); // Error message
        } else {
            noticeElement.addClass('woocommerce-notice'); // General informational message
        }
    
        // Set the notice message and show the element
        noticeElement.html(message).show();
    }
    
    // Start the countdown timer for resending the code
    function startCountdown() {
        clearInterval(timerInterval);

        // Reset the countdown value from the option
        countdown = parseInt(wc_advanced_accounts_verification_params.resend_countdown, 10) || 30;

        resendButton.prop('disabled', true);

        // Update the button text immediately
        resendButton.html(`${wc_advanced_accounts_verification_params.resend_code} (<span id="resend-timer">${countdown}</span>s)`);

        // Start the interval timer
        timerInterval = setInterval(() => {
            countdown--;

            // Update the countdown display
            resendButton.html(`${wc_advanced_accounts_verification_params.resend_code} (<span id="resend-timer">${countdown}</span>s)`);

            if (countdown <= 0) {
                clearInterval(timerInterval);

                if (resendCount < maxResendAttempts) {
                    resendButton.prop('disabled', false);
                    resendButton.html(wc_advanced_accounts_verification_params.resend_code);
                } else {
                    resendButton.prop('disabled', true);
                    resendButton.html(wc_advanced_accounts_verification_params.resend_limit_reached);
                }
            }
        }, 1000);
    }

    // Hide the Register button initially
    $('.woocommerce-form-register__submit').hide();

    // Handle the click event for sending the phone verification code
    $('#send-phone-code').on('click', function (e) {
        e.preventDefault();

        var $btn = $(this);
        $btn.prop('disabled', true);

        const phoneNumberHolder = $('#reg_username_holder').val().trim();
        const phoneNumber = $('#reg_username').val().trim();

        // Check if the phone number is empty or only contains the country code prefix
        if (!phoneNumberHolder || phoneNumber.match(/^\d+-$/)) {
            showNotice(wc_advanced_accounts_verification_params.error_message, 'error');
            $btn.prop('disabled', false);
            return;
        }

        // Start the countdown immediately after the first "Send code" click
        startCountdown();

        $.ajax({
            url: wc_advanced_accounts_verification_params.ajax_url,
            type: 'POST',
            data: {
                action: 'send_phone_verification_code',
                phone_number: phoneNumber,
                security: wc_advanced_accounts_verification_params.nonce
            },
            success: function (response) {
                if (response.success) {
                    showNotice(response.data, 'success');
                    $('#send-phone-code').hide();
                    $('#phone_verification_code').show();
                    $('#verify-phone-code').show();
                    resendButton.show();
                } else {
                    showNotice(response.data, 'error');
                    $btn.prop('disabled', false);
                }
            },
            error: function () {
                showNotice(wc_advanced_accounts_verification_params.ajax_error, 'error');
                $btn.prop('disabled', false);
            }
        });
    });

    // Handle the click event for the 'Verify' button
    $('#verify-phone-code').on('click', function (e) {
        e.preventDefault();

        var $btn = $(this);
        $btn.prop('disabled', true);

        const verificationCode = $('#phone_verification_code').val();
        const phoneNumberHolder = $('#reg_username_holder').val().trim();
        const phoneNumber = $('#reg_username').val();

        if (!verificationCode) {
            showNotice(wc_advanced_accounts_verification_params.enter_code, 'error');
            $btn.prop('disabled', false);
            return;
        }

        if (!phoneNumberHolder) {
            showNotice(wc_advanced_accounts_verification_params.error_message, 'error');
            $btn.prop('disabled', false);
            return;
        }

        $.ajax({
            url: wc_advanced_accounts_verification_params.ajax_url,
            type: 'POST',
            data: {
                action: 'yoaa_verify_phone_code',
                verification_code: verificationCode,
                phone_number: phoneNumber,
                security: wc_advanced_accounts_verification_params.nonce
            },
            success: function (response) {
                if (response.success) {
                    showNotice(wc_advanced_accounts_verification_params.success_message, 'success');
                    $('#phone-verification-row').hide();
                    resendButton.hide();
                    $('#phone_verified').val('1');
                    $('.woocommerce-form-register__submit').show();
                    setTimeout(function () {
                        $('.woocommerce-form-register__submit').trigger('click');
                    }, 1000);
                } else {
                    showNotice(wc_advanced_accounts_verification_params.verification_failed, 'error');
                    $btn.prop('disabled', false);
                }
            },
            error: function () {
                showNotice(wc_advanced_accounts_verification_params.ajax_error, 'error');
                $btn.prop('disabled', false);
            }
        });
    });

    // Handle the click event for the 'Resend' button
    resendButton.on('click', function (e) {
        e.preventDefault();

        var $btn = $(this);
        $btn.prop('disabled', true);

        if (resendCount >= maxResendAttempts) {
            showNotice(wc_advanced_accounts_verification_params.resend_attempts_reached, 'error');
            resendButton.prop('disabled', true);
            resendButton.html(wc_advanced_accounts_verification_params.resend_limit_reached);
            return;
        }

        const phoneNumberHolder = $('#reg_username_holder').val().trim();
        const phoneNumber = $('#reg_username').val();

        if (!phoneNumberHolder) {
            showNotice(wc_advanced_accounts_verification_params.error_message, 'error');
            $btn.prop('disabled', false);
            return;
        }

        resendCount++;

        // Start a new countdown after resending the code
        startCountdown();

        $.ajax({
            url: wc_advanced_accounts_verification_params.ajax_url,
            type: 'POST',
            data: {
                action: 'send_phone_verification_code',
                phone_number: phoneNumber,
                security: wc_advanced_accounts_verification_params.nonce
            },
            success: function (response) {
                if (response.success) {
                    showNotice(wc_advanced_accounts_verification_params.new_code_sent, 'success');
                    resendButton.prop('disabled', true);
                    resendButton.html(`${wc_advanced_accounts_verification_params.resend_code} (<span id="resend-timer">${countdown}</span>s)`);
                } else {
                    showNotice(response.data, 'error');
                }
            },
            error: function () {
                showNotice(wc_advanced_accounts_verification_params.ajax_error, 'error');
                $btn.prop('disabled', false);
            }
        });
    });
});

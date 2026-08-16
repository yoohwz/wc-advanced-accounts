jQuery(document).ready(function ($) {
    const loginButton = $('.woocommerce-form-login__submit');
    const loginRememberMe = $('.woocommerce-form-login__rememberme');
    let resendCountdown = parseInt(wc_otp_login_params.resend_time, 10);
    let resendLimit = parseInt(wc_otp_login_params.resend_limit, 10);
    let resendAttempts = 0;
    let resendInterval;

    // Insert "Login with OTP" button after the existing "Log in" button
    if (loginButton.length > 0) {
        const otpButton = $('<button>', {
            type: 'button',
            class: 'woocommerce-button button woocommerce-form-login__otp login-with-otp', // Added class for targeting
            text: wc_otp_login_params.otp_button_text,
        });

        // Insert the OTP button after the "Log in" button
        loginButton.after(otpButton);
    }

    // Insert the notice element and OTP input field before the password input
    if (loginRememberMe.length > 0) {
        const noticeElement = $('<div>', {
            class: 'woocommerce-notice otp-login-notice', // Updated to class
            style: 'display: none;',
        });

        const otpForm = $('<div>', {
            class: 'otp-login-form', // Updated to class
            style: 'display: none;',
        }).append(
            $('<input>', {
                type: 'text',
                class: 'woocommerce-Input woocommerce-Input--text input-text otp-code', // Updated to class
                placeholder: wc_otp_login_params.enter_otp_field,
            }),
            $('<button>', {
                type: 'button',
                class: 'woocommerce-button button verify-otp', // Updated to class
                text: wc_otp_login_params.verify_button_text,
            }),
            $('<button>', {
                type: 'button',
                class: 'woocommerce-button button resend-otp', // Updated to class
                text: wc_otp_login_params.resend_button_countdown.replace('%s', resendCountdown),
                disabled: true,
            })
        );

        // Insert the elements before the password input
        loginRememberMe.after(noticeElement);
        loginRememberMe.after(otpForm);
    }

    // Helper function to show notices
    function showNotice(form, message, type = 'info') {
        // Ensure the form is valid
        if (!(form instanceof jQuery) || !form.length) {
            console.error('Invalid form element passed to showNotice:', form);
            return;
        }
    
        // Find the notice element in the specific form
        const noticeElement = form.find('.otp-login-notice');
        if (!noticeElement.length) {
            console.error('Notice element not found in form:', form);
            return;
        }

        // Clear existing notices and add new message
        noticeElement.empty();
        noticeElement.removeClass('woocommerce-message woocommerce-error woocommerce-notice');
    
        if (type === 'success') {
            noticeElement.addClass('woocommerce-message');
        } else if (type === 'error') {
            noticeElement.addClass('woocommerce-error');
        } else {
            noticeElement.addClass('woocommerce-notice');
        }
    
        // Set the message and display the notice
        noticeElement.text(message).show();
    }
    
    // Start the resend countdown timer
    function startResendCountdown() {
        const resendButton = $('.resend-otp');
        resendButton.prop('disabled', true);
        resendButton.text(
            wc_otp_login_params.resend_button_countdown.replace('%s', resendCountdown)
        );
    
        resendInterval = setInterval(() => {
            resendCountdown--;
            resendButton.text(
                wc_otp_login_params.resend_button_countdown.replace('%s', resendCountdown)
            );
    
            if (resendCountdown <= 0) {
                clearInterval(resendInterval);
    
                // Check if the resend limit is reached
                if (resendAttempts >= resendLimit) {
                    resendButton.prop('disabled', true).text(wc_otp_login_params.resend_limit_reached);
                } else {
                    resendButton.prop('disabled', false).text(wc_otp_login_params.resend_button_text);
                }
            }
        }, 1000);
    }
    
    // Handle the click event for the "Login with OTP" button
    $(document).on('click', '.login-with-otp', function (e) {
        e.preventDefault();
    
        var $btn = $(this);
        $btn.prop('disabled', true);
    
        // Find the closest form and the username field
        const form = $btn.closest('.woocommerce-form-login');
        const usernameField = form.find('input[name="username"]');
        const identifier = usernameField.length > 0 ? usernameField.val().trim() : '';
    
		if (!identifier) {
            showNotice(form, wc_otp_login_params.error_message, 'error');
            $btn.prop('disabled', false);
            return;
        }
    
		const isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(identifier);

		if (!isEmail) {
			showNotice(form, wc_otp_login_params.invalid_email, 'error');
            $btn.prop('disabled', false);
            return;
        }
    
        // Send AJAX request to send OTP
        $.ajax({
            url: wc_otp_login_params.ajax_url,
            type: 'POST',
            data: {
                action:   'send_login_otp',
                identifier: identifier,
                security:   wc_otp_login_params.nonce,
            },
            success: function (response) {
                if (response.success) {
                    showNotice(form, response.data, 'success');
    
                    form.find('.otp-login-form').show();
                    form.find('input[name="password"]').closest('p').hide();
                    form.find('.woocommerce-form-login__submit').hide();
    
                    $btn.hide();
    
                    resendAttempts = 0;
                    startResendCountdown(form);
                } else {
                    showNotice(form, response.data, 'error');
                    $btn.prop('disabled', false);
                }
            },
            error: function () {
                showNotice(form, wc_otp_login_params.otp_error, 'error');
                $btn.prop('disabled', false);
            },
        });
    });        
    
    // Handle the click event for the "Verify OTP" button
    $(document).on('click', '.verify-otp', function (e) {
        e.preventDefault();
    
        var $btn = $(this);
        $btn.prop('disabled', true);
    
        const form    = $btn.closest('.woocommerce-form-login');
        const otpCode = form.find('.otp-code').val().trim();
    
        if (!otpCode) {
            showNotice(form, wc_otp_login_params.otp_verification_error, 'error');
            $btn.prop('disabled', false);
            return;
        }
    
        $.ajax({
            url: wc_otp_login_params.ajax_url,
            type: 'POST',
            data: {
                action:   'verify_login_otp',
                otp_code: otpCode,
                security: wc_otp_login_params.nonce,
            },
            success: function (response) {
                if (response.success) {
                    showNotice(form, response.data, 'success');
                    window.location.reload();
                } else {
                    showNotice(form, response.data, 'error');
                    $btn.prop('disabled', false);
                }
            },
            error: function () {
                showNotice(form, wc_otp_login_params.otp_verification_error, 'error');
                $btn.prop('disabled', false);
            },
        });
    });
    
    // Handle the click event for the "Resend OTP" button
    $(document).on('click', '.resend-otp', function (e) {
        e.preventDefault();
    
        var $btn       = $(this);
        var originalText = $btn.text();
        var form       = $btn.closest('.woocommerce-form-login');
        var usernameField = form.find('input[name="username"]');
        var identifier = usernameField.length > 0 ? usernameField.val().trim() : '';
    
        // Validate identifier
        if (!identifier) {
            showNotice(form, wc_otp_login_params.error_message, 'error');
            return;
        }
    
        // Check resend limit
        if (resendAttempts >= resendLimit) {
            showNotice(form, wc_otp_login_params.resend_limit_reached, 'error');
            return;
        }
    
        // Disable button and show "resending" text
        $btn.prop('disabled', true).text(wc_otp_login_params.resending_text);
    
        $.ajax({
            url: wc_otp_login_params.ajax_url,
            type: 'POST',
            data: {
                action:     'send_login_otp',
                identifier: identifier,
                security:   wc_otp_login_params.nonce,
            },
            success: function (response) {
                if (response.success) {
                    showNotice(form, wc_otp_login_params.otp_resent, 'success');
                    resendAttempts++;
                    resendCountdown = parseInt(wc_otp_login_params.resend_time, 10);
                    startResendCountdown(form);
                    // Note: `startResendCountdown` should re-enable or update the button when the timer ends
                } else {
                    showNotice(form, response.data, 'error');
                    // Re-enable & restore text so user can try again
                    $btn.prop('disabled', false).text(originalText);
                }
            },
            error: function () {
                showNotice(form, wc_otp_login_params.otp_error, 'error');
                $btn.prop('disabled', false).text(originalText);
            },
        });
    });    
});

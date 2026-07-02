jQuery(document).ready(function ($) {
    let resendCountdown = parseInt(reset_password_otp_params.resend_time, 10);
    let maxResendAttempts = parseInt(reset_password_otp_params.resend_limit, 10);
    let resendAttempts = 0;
    let resendInterval;

    // Display a notice
    function showNotice(message, type = 'info') {
        const noticeElement = $('#reset-password-otp-notice');
        noticeElement.removeClass('woocommerce-info woocommerce-error woocommerce-message');

        if (type === 'success') {
            noticeElement.addClass('woocommerce-message');
        } else if (type === 'error') {
            noticeElement.addClass('woocommerce-error');
        } else {
            noticeElement.addClass('woocommerce-info');
        }

        noticeElement.text(message).show();
    }

    // Start the resend countdown timer
	function startResendCountdown() {
		const resendButton = $('#resend-reset-otp-code');
		resendButton.prop('disabled', true).show();
		$('#resend-timer').text(resendCountdown);
	
		clearInterval(resendInterval); // Clear any existing interval
	
		resendInterval = setInterval(() => {
			resendCountdown--;
			resendButton.text(reset_password_otp_params.resend_button_text + ` (${resendCountdown}s)`); // Update button text with countdown
			$('#resend-timer').text(resendCountdown);
	
			if (resendCountdown <= 0) {
				clearInterval(resendInterval);
	
				// Check if resend limit is reached
				if (resendAttempts >= maxResendAttempts) {
					resendButton.prop('disabled', true).text(reset_password_otp_params.resend_limit_reached);
				} else {
					resendButton.prop('disabled', false).text(reset_password_otp_params.resend_button_text);
				}
			}
		}, 1000);
	}
	
    // Reset the countdown and attempts when the Send OTP button is clicked
    function resetResendState() {
        resendCountdown = parseInt(reset_password_otp_params.resend_time, 10);
        resendAttempts = 0;
        clearInterval(resendInterval);
        startResendCountdown();
    }

    // Handle the Send OTP button click
    $('#send-reset-otp').on('click', function () {
        const identifier = $('#username').val().trim();
		const invalidPattern = /^\d+-$/

        if (!identifier || invalidPattern.test(identifier)) {
            showNotice(reset_password_otp_params.error_message, 'error');
            return;
        }

        const isPhoneOrEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(identifier) || /^\d[\d\-]*$/.test(identifier);

        if (!isPhoneOrEmail) {
            showNotice(reset_password_otp_params.invalid_identifier, 'error');
            return;
        }

        // Disable the Send OTP button temporarily
        $('#send-reset-otp').prop('disabled', true);

        $.ajax({
            url: reset_password_otp_params.ajax_url,
            type: 'POST',
            data: {
                action: 'send_reset_otp',
                identifier: identifier,
                security: reset_password_otp_params.nonce,
            },
            success: function (response) {
                if (response.success) {
                    showNotice(response.data, 'success');
                    $('.otp-section').show();
                    $('.send-otp').hide();
                    resetResendState(); // Reset countdown and resend attempts
                } else {
                    showNotice(response.data, 'error');
                }
            },
            error: function () {
                showNotice(reset_password_otp_params.otp_error_message, 'error');
            },
            complete: function () {
                $('#send-reset-otp').prop('disabled', false);
            },
        });
    });

    // Handle the Resend OTP button click
	$('#resend-reset-otp-code').on('click', function () {
		const identifier = $('#username').val().trim();
	
		if (resendAttempts >= maxResendAttempts) {
			showNotice(reset_password_otp_params.resend_limit_reached, 'error');
			return;
		}
	
		const resendButton = $(this);
	
		// Start the countdown immediately
		resendCountdown = parseInt(reset_password_otp_params.resend_time, 10);
		startResendCountdown();
	
		// Disable the button during the request
		resendButton.prop('disabled', true);
	
		$.ajax({
			url: reset_password_otp_params.ajax_url,
			type: 'POST',
			data: {
				action: 'send_reset_otp',
				identifier: identifier,
				security: reset_password_otp_params.nonce,
			},
			success: function (response) {
				if (response.success) {
					showNotice(reset_password_otp_params.otp_resend_success, 'success');
					resendAttempts++; // Increment resend attempts only on success
				} else {
					showNotice(response.data, 'error');
				}
			},
			error: function (jqXHR, textStatus, errorThrown) {
				showNotice(reset_password_otp_params.otp_error_message, 'error');
			},
		});
	});
	
    // Handle the Verify OTP button click
	$('#verify-reset-otp').on('click', function () {
		const otpCode = $('#reset_otp').val().trim();
	
		if (!otpCode) {
			showNotice(reset_password_otp_params.otp_verification_error, 'error');
			return;
		}
	
		$.ajax({
			url: reset_password_otp_params.ajax_url,
			type: 'POST',
			data: {
				action: 'verify_reset_otp',
				otp_code: otpCode,
				security: reset_password_otp_params.nonce,
			},
			success: function (response) {
				if (response.success) {
					// Redirect to the password reset form
					window.location.href = response.data.redirect_url;
				} else {
					showNotice(response.data, 'error');
				}
			},
			error: function () {
				showNotice(reset_password_otp_params.otp_verification_error, 'error');
			},
		});
	});
});

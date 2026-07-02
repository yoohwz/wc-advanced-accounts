jQuery(document).ready(function($) {
	$('input#yoaa_wc_phone_verification_code_length').closest('td').addClass('yoaa-sms-options-first');
	$('input#yoaa_wc_phone_verification_resend').closest('td').addClass('yoaa-sms-options');
	$('input#yoaa_wc_phone_verification_resend_time').closest('td').addClass('yoaa-sms-options');
	$('textarea#yoaa_wc_phone_verification_message').closest('td').addClass('yoaa-sms-options');

	function togglePhoneVerificationAvailability() {
		const enablePhoneNumberAccount = $('#yoaa_wc_enable_phone_number_account');
		const enablePhoneVerification = $('#yoaa_wc_enable_phone_verification');

		if (!enablePhoneNumberAccount.is(':checked')) {
			enablePhoneVerification.prop('checked', false).prop('disabled', true);
		} else {
			enablePhoneVerification.prop('disabled', false);
		}
	}

	togglePhoneVerificationAvailability();
	$('#yoaa_wc_enable_phone_number_account').on('change', togglePhoneVerificationAvailability);

	$('#yoaa_wc_enable_email_verification, #yoaa_wc_enable_phone_verification, #yoaa_wc_disable_email_on_registration').on('change', function() {
		if ($(this).is('#yoaa_wc_enable_email_verification') && $(this).is(':checked')) {
			$('#yoaa_wc_enable_phone_verification').prop('checked', false);
			$('#yoaa_wc_disable_email_on_registration').prop('checked', false);
		} else if ($(this).is('#yoaa_wc_enable_phone_verification') && $(this).is(':checked')) {
			$('#yoaa_wc_enable_email_verification').prop('checked', false);
		} else if ($(this).is('#yoaa_wc_disable_email_on_registration') && $(this).is(':checked')) {
			$('#yoaa_wc_enable_email_verification').prop('checked', false);
		}
	});

	function togglePhoneVerificationFields() {
		if (
			$('#yoaa_wc_enable_phone_verification').is(':checked') ||
			$('#yoaa_wc_enable_phone_login_with_otp').is(':checked')
		) {
			$('#yoohw_phone_verification_sms_key').closest('tr').show();
			$('#yoaa_wc_phone_verification_code_length').closest('tr').show();
			$('#yoaa_wc_phone_verification_resend').closest('tr').show();
			$('#yoaa_wc_phone_verification_resend_time').closest('tr').show();
			$('#yoaa_wc_phone_verification_message').closest('tr').show();
		} else {
			$('#yoohw_phone_verification_sms_key').closest('tr').hide();
			$('#yoaa_wc_phone_verification_code_length').closest('tr').hide();
			$('#yoaa_wc_phone_verification_resend').closest('tr').hide();
			$('#yoaa_wc_phone_verification_resend_time').closest('tr').hide();
			$('#yoaa_wc_phone_verification_message').closest('tr').hide();
		}
	}

	togglePhoneVerificationFields();
	$('#yoaa_wc_enable_phone_verification, #yoaa_wc_enable_phone_number_account, #yoaa_wc_enable_phone_login_with_otp').on('change', togglePhoneVerificationFields);

	var cfg = window.YOAA_AA || {};
	var i18n = cfg.i18n || {};
	var smsKeyInput = $('#yoohw_phone_verification_sms_key');
	var generateKeyButton = $('#generate_sms_key');

	var smsQuota = cfg.smsQuota || '0.00';
	var smsQuotaColor = cfg.smsQuotaColor || '#d63638';
	var smsKey = cfg.smsKey || '';

	var historyLogsLink = ' <a href="' + (cfg.historyBaseUrl || '') + smsKey + '" target="_blank">[' + (i18n.historyLogs || 'History logs') + ']</a>';
	var purchaseLink = '<button type="button" id="purchase_sms_credits" class="button-secondary" onclick="window.open(\'' + (cfg.purchaseUrl || '') + '\', \'_blank\');">' + (i18n.purchaseYoCredits || 'Purchase Yo Credits') + '</button>';

	if (!$('#copy_key_button').length) {
		var copyKeyButton = $('<button type="button" id="copy_key_button" class="button-secondary">' + (i18n.copy || 'Copy') + '</button>').insertAfter(generateKeyButton).hide();
		var smsKeyMessage = $('<p id="sms_key_message"></p>').insertAfter(copyKeyButton);
		var smsKeyQuota = $('<p id="sms_key_quota" style="margin-top: 15px; color:' + smsQuotaColor + ';"></p>').insertAfter(smsKeyMessage);
		var purchaseButton = $(purchaseLink).insertAfter(smsKeyQuota).css('margin-top', '5px');
	}

	var howItWorksLink = ' <a href="' + (cfg.howItWorksUrl || '') + '" target="_blank">' + (i18n.howItWorks || 'How it works?') + '</a>';

	if (smsKeyInput.val()) {
		generateKeyButton.hide();
		copyKeyButton.show();
		smsKeyMessage.html((i18n.useKeyForYoCredits || 'Use this key when you purchase Yo Credits.') + howItWorksLink);
		smsKeyQuota.html('<strong>' + (i18n.smsQuota || 'SMS Quota') + '</strong>: ' + smsQuota + ' ' + (i18n.usdCreditsRemaining || 'USD credits remaining.') + historyLogsLink);
	} else {
		generateKeyButton.show();
		copyKeyButton.hide();
		smsKeyMessage.html((i18n.generateKeyPrompt || 'Generate a new key to start using SMS Verification.') + howItWorksLink);
		smsKeyQuota.hide();
		purchaseButton.hide();
	}

	generateKeyButton.on('click', function(e) {
		e.preventDefault();

		var key = generateRandomKey(20);

		$.post(cfg.ajaxUrl, {
			action: 'generate_sms_key',
			sms_key: key,
			security: cfg.nonce
		}, function(response) {
			if (response && response.success) {
				smsKeyInput.val(key);
				generateKeyButton.hide();
				copyKeyButton.show();
				smsKeyMessage.html((i18n.useKeyForYoCredits || 'Use this key when you purchase Yo Credits.') + howItWorksLink);
				alert(i18n.keyGenerated || 'Key generated and saved successfully.');
			} else {
				alert(i18n.keyGenerationFailed || 'Failed to generate the key. Please try again or contact support.');
			}
		});
	});

	copyKeyButton.on('click', function(e) {
		e.preventDefault();
		smsKeyInput.trigger('select');
		document.execCommand('copy');
		copyKeyButton.text(i18n.copied || 'Copied!');
		setTimeout(function() {
			copyKeyButton.text(i18n.copy || 'Copy');
		}, 2000);
	});

	function generateRandomKey(length) {
		var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		var result = '';
		for (var i = 0; i < length; i++) {
			result += characters.charAt(Math.floor(Math.random() * characters.length));
		}
		return result;
	}
});

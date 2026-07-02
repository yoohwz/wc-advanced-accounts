<?php

if (!defined('ABSPATH')) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Advanced_Settings {

	public static function get_advanced_account_settings() {
		return array(
			array(
				'title' => esc_html__('Advanced account', 'wc-advanced-accounts'),
				'type'  => 'title',
				'id'    => 'yoaa_wc_advanced_account_settings'
			),
			array(
				'title'    => esc_html__('Account creation options', 'wc-advanced-accounts'),
				'desc'     => esc_html__('Use phone number as account login (recommend)', 'wc-advanced-accounts'),
				'desc_tip' => esc_html__('If checked, your site will treat the username as the customer phone number.', 'wc-advanced-accounts'),
				'id'       => 'yoaa_wc_enable_phone_number_account',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => 'start',
			),
			array(
				'title'    => esc_html__('Disable email requirement', 'wc-advanced-accounts'),
				'desc'     => esc_html__('Do not require email address to create an account and checkout', 'wc-advanced-accounts'),
				'desc_tip' => esc_html__('Email addresses will no longer be required for registration or at checkout, with a temporary email used instead.', 'wc-advanced-accounts'),
				'id'       => 'yoaa_wc_disable_email_on_registration',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => 'end',
			),
			array(
				'type'        => 'yoaa_upgrade_panel',
				'title'       => esc_html__( 'Advanced account controls', 'wc-advanced-accounts' ),
				'desc'        => esc_html__( 'Add optional account management tools without changing the free account creation flow.', 'wc-advanced-accounts' ),
				'features'    => array(
					esc_html__( 'Block or unblock customer accounts from the users page.', 'wc-advanced-accounts' ),
					esc_html__( 'Let customers request account erasure from My Account.', 'wc-advanced-accounts' ),
					esc_html__( 'Add profile and registration fields for richer customer accounts.', 'wc-advanced-accounts' ),
				),
				'button_text' => esc_html__( 'View account control features', 'wc-advanced-accounts' ),
				'id'          => 'yoaa_advanced_account_upgrade_panel',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'yoaa_wc_advanced_account_settings'
			),
		);
	}

	public static function get_verification_settings() {
		$settings = array(
			array(
				'title' => esc_html__('Verifications', 'wc-advanced-accounts'),
				'type'  => 'title',
				'id'    => 'yoaa_wc_verifications_settings'
			),
			array(
				'title'    => esc_html__('Verification / OTP', 'wc-advanced-accounts'),
				'desc'     => esc_html__('Require email verification during account registration', 'wc-advanced-accounts'),
				'desc_tip' => esc_html__('New customers receive an email to activate their account and set up password.', 'wc-advanced-accounts'),
				'id'       => 'yoaa_wc_enable_email_verification',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => 'start',
			),
			array(
				'title'    => esc_html__('Phone verification', 'wc-advanced-accounts'),
				'desc'     => esc_html__('Require phone verification during account registration', 'wc-advanced-accounts'),
				'desc_tip' => esc_html__('New customers receive a verification code on their phone to create an account.', 'wc-advanced-accounts'),
				'id'       => 'yoaa_wc_enable_phone_verification',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => '',
			),
			array(
				'title'    => esc_html__('Login with OTP', 'wc-advanced-accounts'),
				'desc'     => esc_html__('Allow user to login with One-Time Password', 'wc-advanced-accounts'),
				'desc_tip' => esc_html__('The user receives a passcode on their phone or email inbox to login to your site. It will use the OTP to let the user reset their password either.', 'wc-advanced-accounts'),
				'id'       => 'yoaa_wc_enable_phone_login_with_otp',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => 'end',
			),

			array(
				'title'    => esc_html__('Verification options', 'wc-advanced-accounts'),
				'desc'     => esc_html__('Code length.', 'wc-advanced-accounts'),
				'desc_tip' => esc_html__('You may set it from 6 to 8 numbers.', 'wc-advanced-accounts'),
				'id'       => 'yoaa_wc_phone_verification_code_length',
				'default'  => '6',
				'type'     => 'number',
				'css'      => 'width:60px;',
				'custom_attributes' => array(
					'min' => 6,
					'max' => 8,
				),
			),
			array(
				'title'    => '',
				'desc'     => esc_html__('Resend in seconds.', 'wc-advanced-accounts'),
				'desc_tip' => esc_html__('Set how many seconds that allows the customers get new code.', 'wc-advanced-accounts'),
				'id'       => 'yoaa_wc_phone_verification_resend',
				'default'  => '120',
				'type'     => 'number',
				'css'      => 'width:80px;',
				'custom_attributes' => array(
					'min' => 60,
				),
			),
			array(
				'title'    => '',
				'desc'     => esc_html__('Time limited.', 'wc-advanced-accounts'),
				'desc_tip' => esc_html__('How many time the customer can request for resending.', 'wc-advanced-accounts'),
				'id'       => 'yoaa_wc_phone_verification_resend_time',
				'default'  => '3',
				'type'     => 'number',
				'css'      => 'width:60px;',
				'custom_attributes' => array(
					'min' => 1,
				),
			),
			array(
				'title'    => esc_html__('SMS content', 'wc-advanced-accounts'),
				'desc'     => esc_html__('Type your content, {code} is required.', 'wc-advanced-accounts'),
				'desc_tip'     => esc_html__('Add {site_name}, {code} where you want them to appear.', 'wc-advanced-accounts'),
				'id'       => 'yoaa_wc_phone_verification_message',
				'default'  => '{site_name}: Your verification code is {code}',
				'type'     => 'textarea',
			),
			array(
				'name' => __('SMS service', 'wc-advanced-accounts'),
				'id' => 'yoohw_sms_service',
				'type' => 'select',
				'options' => array(
					'yo_credits' => __('Yo Credits', 'wc-advanced-accounts'),
				),
				'default' => 'yo_credits',
				'desc_tip' => __('Yo Credits is the SMS service available in the free plugin.', 'wc-advanced-accounts'),
			),
			array(
				'title'    => esc_html__('Yo Credits key', 'wc-advanced-accounts'),
				'desc'     => '<button type="button" id="generate_sms_key" class="button-secondary">' . esc_html__('Generate a key', 'wc-advanced-accounts') . '</button>',
				'desc_tip' => esc_html__('Do not share or public this key in any case.', 'wc-advanced-accounts'),
				'id'       => 'yoohw_phone_verification_sms_key',
				'type'     => 'text',
				'css'      => 'width:160px;',
				'custom_attributes' => array(
					'readonly' => 'readonly'
				),
			),
			array(
				'type'        => 'yoaa_upgrade_panel',
				'title'       => esc_html__( 'Need more verification options?', 'wc-advanced-accounts' ),
				'desc'        => esc_html__( 'Use optional provider and account-verification controls when your store needs a more advanced workflow.', 'wc-advanced-accounts' ),
				'features'    => array(
					esc_html__( 'Connect Twilio or Textmagic for SMS delivery.', 'wc-advanced-accounts' ),
					esc_html__( 'Verify customer phone number changes.', 'wc-advanced-accounts' ),
					esc_html__( 'Exclude selected roles from verification requirements.', 'wc-advanced-accounts' ),
				),
				'button_text' => esc_html__( 'View verification add-ons', 'wc-advanced-accounts' ),
				'id'          => 'yoaa_verification_upgrade_panel',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'yoaa_wc_phone_verifications_settings'
			),
		);

		return $settings;
	}

	public static function get_additionals_settings() {
		return array(
			array(
				'title' => esc_html__('Additionals', 'wc-advanced-accounts'),
				'type'  => 'title',
				'id'    => 'yoaa_wc_additionals_settings'
			),
			array(
				'title'    => esc_html__('Redirect wp-login', 'wc-advanced-accounts'),
				'desc'     => esc_html__('Enable to redirect the wp-login to My account page', 'wc-advanced-accounts'),
				'desc_tip' => esc_html__('It will redirect the user to my account page when they access to the WordPress default login page.', 'wc-advanced-accounts'),
				'id'       => 'yoaa_wc_redirect_wp_login',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'yoaa_wc_additionals_settings'
			),
		);
	}	
}

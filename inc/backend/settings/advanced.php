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
		return array(
			array(
				'title' => esc_html__('Verifications', 'wc-advanced-accounts'),
				'type'  => 'title',
				'id'    => 'yoaa_wc_verifications_settings'
			),
			array(
				'title'    => esc_html__('Email verification', 'wc-advanced-accounts'),
				'desc'     => esc_html__('Require email verification during account registration', 'wc-advanced-accounts'),
				'desc_tip' => esc_html__('New customers receive an email to activate their account and set up password.', 'wc-advanced-accounts'),
				'id'       => 'yoaa_wc_enable_email_verification',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => 'start',
			),
			array(
				'title'    => esc_html__('Email OTP', 'wc-advanced-accounts'),
				'desc'     => esc_html__('Allow login and password reset with a one-time password sent by email', 'wc-advanced-accounts'),
				'desc_tip' => esc_html__('Customers can request a secure email code to log in or continue the password reset flow.', 'wc-advanced-accounts'),
				'id'       => 'yoaa_wc_enable_email_login_with_otp',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => 'end',
			),
			array(
				'title'    => esc_html__('Email OTP options', 'wc-advanced-accounts'),
				'desc'     => esc_html__('Resend cooldown in seconds.', 'wc-advanced-accounts'),
				'desc_tip' => esc_html__('Set how long customers must wait before requesting another email OTP.', 'wc-advanced-accounts'),
				'id'       => 'yoaa_wc_email_otp_resend',
				'default'  => '120',
				'type'     => 'number',
				'css'      => 'width:80px;',
				'custom_attributes' => array(
					'min' => 60,
				),
			),
			array(
				'title'    => '',
				'desc'     => esc_html__('Maximum resend attempts.', 'wc-advanced-accounts'),
				'desc_tip' => esc_html__('Set how many times a customer can request another email OTP.', 'wc-advanced-accounts'),
				'id'       => 'yoaa_wc_email_otp_resend_limit',
				'default'  => '3',
				'type'     => 'number',
				'css'      => 'width:60px;',
				'custom_attributes' => array(
					'min' => 1,
				),
			),
			array(
				'type'        => 'yoaa_upgrade_panel',
				'title'       => esc_html__( 'Phone verification and OTP with Premium', 'wc-advanced-accounts' ),
				'desc'        => esc_html__( 'Upgrade to add phone verification and OTP workflows using your own third-party messaging provider.', 'wc-advanced-accounts' ),
				'features'    => array(
					esc_html__( 'Connect Twilio or Textmagic for SMS delivery.', 'wc-advanced-accounts' ),
					esc_html__( 'Enable phone verification, phone OTP login, and phone OTP password reset.', 'wc-advanced-accounts' ),
					esc_html__( 'Request integration with another messaging service; typical completion time is 1–2 business days.', 'wc-advanced-accounts' ),
				),
				'button_text' => esc_html__( 'Upgrade to Premium', 'wc-advanced-accounts' ),
				'request_text' => esc_html__( 'Request a provider integration', 'wc-advanced-accounts' ),
				'request_url'  => 'https://yoohw.com/contact-us/',
				'id'          => 'yoaa_verification_upgrade_panel',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'yoaa_wc_verifications_settings'
			),
		);
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

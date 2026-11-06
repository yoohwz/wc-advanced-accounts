<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Integration_Settings {
	public static function get_captcha_settings() {
		$settings = [
			[ 'title' => __( 'CAPTCHA', 'wc-advanced-accounts' ), 'type' => 'title', 'id' => 'yoaa_wc_captcha_settings', 'desc' => __( 'Protect the WordPress and WooCommerce login and registration forms. Configure both keys before enabling protection. This does not protect every API or OTP request.', 'wc-advanced-accounts' ) ],
			[ 'title' => __( 'Provider', 'wc-advanced-accounts' ), 'id' => 'yoaa_wc_captcha_selected', 'type' => 'select', 'default' => 'none', 'options' => [ 'none' => __( 'Disabled', 'wc-advanced-accounts' ), 'cloudflare' => 'Cloudflare Turnstile', 'recaptcha_v3' => 'reCAPTCHA v3', 'recaptcha_v2' => 'reCAPTCHA v2', 'hcaptcha' => 'hCaptcha' ] ],
		];
		foreach ( [ 'recaptcha' => 'reCAPTCHA v3', 'recaptcha_v2' => 'reCAPTCHA v2', 'cloudflare' => 'Cloudflare Turnstile', 'hcaptcha' => 'hCaptcha' ] as $key => $label ) {
			$settings[] = [ 'title' => $label . ' — ' . __( 'Site key', 'wc-advanced-accounts' ), 'id' => 'yoaa_wc_' . $key . '_site_key', 'type' => 'text', 'default' => '' ];
			$settings[] = [ 'title' => $label . ' — ' . __( 'Secret key', 'wc-advanced-accounts' ), 'id' => 'yoaa_wc_' . $key . '_secret_key', 'type' => 'password', 'default' => '', 'custom_attributes' => [ 'autocomplete' => 'off' ] ];
		}
		$settings[] = [ 'title' => __( 'reCAPTCHA v3 score threshold', 'wc-advanced-accounts' ), 'id' => 'yoaa_wc_recaptcha_score_threshold', 'type' => 'number', 'default' => '0.5', 'custom_attributes' => [ 'min' => '0', 'max' => '1', 'step' => '0.1' ] ];
		$settings[] = [ 'title' => __( 'Login form', 'wc-advanced-accounts' ), 'id' => 'yoaa_wc_enable_captcha_login', 'type' => 'checkbox', 'default' => 'no', 'desc' => __( 'Require CAPTCHA on WordPress and WooCommerce login forms.', 'wc-advanced-accounts' ) ];
		$settings[] = [ 'title' => __( 'Registration form', 'wc-advanced-accounts' ), 'id' => 'yoaa_wc_enable_captcha_register', 'type' => 'checkbox', 'default' => 'no', 'desc' => __( 'Require CAPTCHA on WordPress and WooCommerce registration forms.', 'wc-advanced-accounts' ) ];
		$settings[] = [ 'type' => 'sectionend', 'id' => 'yoaa_wc_captcha_settings' ];
		return $settings;
	}
}

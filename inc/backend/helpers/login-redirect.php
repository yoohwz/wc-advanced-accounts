<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Helper_Login_Redirect_Free {

	/**
	 * Boot.
	 */
	public static function init() {
		add_filter( 'woocommerce_login_redirect', array( __CLASS__, 'woocommerce_login_redirect' ), 10, 2 );
		add_filter( 'login_redirect', array( __CLASS__, 'wp_login_redirect' ), 10, 3 );
	}

	/**
	 * Handle redirect after WooCommerce My Account login.
	 *
	 * @param string  $redirect Redirect URL.
	 * @param WP_User $user     User object.
	 * @return string
	 */
	public static function woocommerce_login_redirect( $redirect, $user ) {
		$requested_redirect = self::get_requested_redirect_url();

		if ( $requested_redirect ) {
			return $requested_redirect;
		}

		return $redirect;
	}

	/**
	 * Handle redirect after default WordPress login.
	 *
	 * @param string           $redirect_to           Redirect URL.
	 * @param string           $requested_redirect_to Requested redirect URL.
	 * @param WP_User|WP_Error $user                  User object or error.
	 * @return string
	 */
	public static function wp_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
		$requested_redirect = self::get_requested_redirect_url();

		if ( $requested_redirect ) {
			return $requested_redirect;
		}

		return $redirect_to;
	}

	/**
	 * Get and validate requested redirect URL.
	 *
	 * @return string
	 */
	private static function get_requested_redirect_url() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect target from the login request.
		if ( empty( $_REQUEST['redirect_to'] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect target from the login request.
		$redirect_to = esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) );

		if ( empty( $redirect_to ) ) {
			return '';
		}

		$validated = wp_validate_redirect( $redirect_to, '' );

		return $validated ? $validated : '';
	}
}

YOAA_WC_Advanced_Accounts_Helper_Login_Redirect_Free::init();

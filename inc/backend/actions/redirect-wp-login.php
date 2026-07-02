<?php

if (!defined('ABSPATH')) {
	exit;
}


class YOAA_WC_Advanced_Accounts_Redirect_WP_Login {

	public function __construct() {
		if ( 'yes' === get_option( 'yoaa_wc_redirect_wp_login' ) ) {
			// Redirect anyone hitting wp-login.php back to WooCommerce “My Account”.
			add_action( 'login_init', array( $this, 'redirect_wp_login_to_my_account' ) );
		}
	}

	/**
	 * Redirect the default WP login page to the WooCommerce My Account page.
	 */
	public function redirect_wp_login_to_my_account() {
		// Validate superglobals before use.
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
		$uri = isset( $_SERVER['REQUEST_URI'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '';

		// Only intercept GET requests (do not interfere with POST processing).
		if ( 'get' !== strtolower( $method ) || '' === $uri ) {
			return;
		}

		// Parse and sanitize the requested path.
		$parsed   = wp_parse_url( $uri );
		$path_raw = isset( $parsed['path'] ) ? $parsed['path'] : '';
		$path     = sanitize_text_field( $path_raw );

		$basename = wp_basename( $path );

		// Allow logouts (action=logout), password resets, etc.
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';

		// Nonce verification (recommended): only redirect when a valid nonce is present,
		// OR when no action is set (plain wp-login.php) to avoid breaking normal access.
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		$nonce_ok = ( '' !== $nonce ) ? wp_verify_nonce( $nonce, 'yoaa_redirect_wp_login' ) : true;

		if ( 'wp-login.php' === $basename && in_array( $action, array( '', 'login' ), true ) && $nonce_ok ) {
			$myaccount = wc_get_page_permalink( 'myaccount' );
			if ( $myaccount ) {
				wp_safe_redirect( esc_url_raw( $myaccount ) );
				exit;
			}
		}
	}
}

new YOAA_WC_Advanced_Accounts_Redirect_WP_Login();

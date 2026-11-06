<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Shared restrictions also used by passwordless authentication before issuing cookies. */
final class YOAA_AAC_Account_Status {
	public function __construct() {
		add_filter( 'authenticate', [ __CLASS__, 'denial' ], 99 );
		add_action( 'init', [ $this, 'enforce_session' ], 20 );
		add_action( 'woocommerce_customer_reset_password', [ $this, 'enforce_session' ], 1 );
	}

	public static function denial( $user ) {
		if ( ! $user instanceof WP_User ) { return $user; }
		if ( ( 'yes' === get_option( 'yoaa_wc_enable_user_blocking' ) && '1' === (string) get_user_meta( $user->ID, 'user_blocked', true ) )
			|| '1' === (string) get_user_meta( $user->ID, 'account_erasure_requested', true ) ) {
			return new WP_Error( 'account_restricted', __( 'This account cannot sign in. Please contact the administrator.', 'wc-advanced-accounts' ) );
		}
		return $user;
	}

	public function enforce_session() {
		if ( is_user_logged_in() && is_wp_error( self::denial( wp_get_current_user() ) ) ) {
			wp_logout();
			wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
			exit;
		}
	}

	public static function set_blocked( $user_id, $blocked ) {
		$user_id = absint( $user_id );
		$user = get_userdata( $user_id );
		if ( ! current_user_can( 'edit_users' ) || ! current_user_can( 'edit_user', $user_id ) ) { return 'forbidden'; }
		if ( ! $user ) { return 'error'; }
		if ( $blocked && ( in_array( 'administrator', (array) $user->roles, true ) || is_super_admin( $user_id ) || $user_id === get_current_user_id() ) ) { return 'skipped'; }
		$value = $blocked ? '1' : '0';
		if ( (string) get_user_meta( $user_id, 'user_blocked', true ) === $value ) { return 'unchanged'; }
		update_user_meta( $user_id, 'user_blocked', $value );
		if ( (string) get_user_meta( $user_id, 'user_blocked', true ) !== $value ) { return 'error'; }
		if ( $blocked ) { WP_Session_Tokens::get_instance( $user_id )->destroy_all(); }
		return 'updated';
	}
}
new YOAA_AAC_Account_Status();

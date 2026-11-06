<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/../helpers/admin-verification.php';

final class YOAA_AAC_User_Profile {
	public function __construct() {
		add_action( 'edit_user_profile', [ $this, 'render' ] );
		add_action( 'edit_user_profile_update', [ $this, 'save' ] );
	}
	public function render( $user ) {
		if ( ! current_user_can( 'edit_users' ) || ! current_user_can( 'edit_user', $user->ID ) ) { return; }
		echo '<h2>' . esc_html__( 'Account actions management', 'wc-advanced-accounts' ) . '</h2>';
		wp_nonce_field( 'yoaa_account_actions_' . $user->ID, 'yoaa_account_actions_nonce' );
		echo '<p>' . esc_html__( 'Email verification is an administrator override; it is not evidence of an OTP challenge.', 'wc-advanced-accounts' ) . '</p>';
		$verified = '1' === (string) get_user_meta( $user->ID, 'email_verification', true );
		echo '<p>' . esc_html( $verified ? __( 'Email: Verified', 'wc-advanced-accounts' ) : __( 'Email: Unverified', 'wc-advanced-accounts' ) ) . '</p>';
		if ( ! in_array( 'administrator', (array) $user->roles, true ) && ! is_super_admin( $user->ID ) ) {
			echo '<p><button class="button" name="yoaa_email_action" value="' . ( $verified ? '0' : '1' ) . '">' . esc_html( $verified ? __( 'Unverify email', 'wc-advanced-accounts' ) : __( 'Verify email', 'wc-advanced-accounts' ) ) . '</button></p>';
		}
		if ( 'yes' === get_option( 'yoaa_wc_enable_user_blocking' ) ) {
			$blocked = '1' === (string) get_user_meta( $user->ID, 'user_blocked', true );
			if ( $blocked || ( ! in_array( 'administrator', (array) $user->roles, true ) && ! is_super_admin( $user->ID ) ) ) {
				echo '<p><button class="button" name="yoaa_block_action" value="' . ( $blocked ? '0' : '1' ) . '">' . esc_html( $blocked ? __( 'Unblock user', 'wc-advanced-accounts' ) : __( 'Block user', 'wc-advanced-accounts' ) ) . '</button></p>';
			}
		}
	}
	public function save( $user_id ) {
		if ( ! isset( $_POST['yoaa_email_action'] ) && ! isset( $_POST['yoaa_block_action'] ) ) { return; }
		if ( ! current_user_can( 'edit_users' ) || ! current_user_can( 'edit_user', $user_id ) || ! isset( $_POST['yoaa_account_actions_nonce'] ) || ! is_scalar( $_POST['yoaa_account_actions_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['yoaa_account_actions_nonce'] ) ), 'yoaa_account_actions_' . $user_id ) ) { return; }
		if ( isset( $_POST['yoaa_email_action'] ) ) {
			$result = YOAA_AAC_Admin_Verification::mutate( $user_id, [ 'email_verification' => wp_unslash( $_POST['yoaa_email_action'] ) ] );
			$status = $result['status'];
		} elseif ( 'yes' === get_option( 'yoaa_wc_enable_user_blocking' ) && in_array( $_POST['yoaa_block_action'], [ '0', '1' ], true ) ) {
			$status = YOAA_AAC_Account_Status::set_blocked( $user_id, '1' === $_POST['yoaa_block_action'] );
		} else { return; }
		if ( ! in_array( $status, [ 'updated', 'unchanged' ], true ) ) { wp_die( esc_html__( 'The account action was not saved. Check your permissions and the account status.', 'wc-advanced-accounts' ) ); }
	}
}
add_action( 'plugins_loaded', static function () {
	if ( class_exists( 'YOAA_WC_Advanced_Accounts_User_Profile_Actions' ) && class_exists( 'WCAAP_Runtime_Policy' ) && WCAAP_Runtime_Policy::commercial() ) { return; }
	new YOAA_AAC_User_Profile();
}, 110 );

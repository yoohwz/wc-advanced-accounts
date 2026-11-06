<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Uses WordPress privacy requests; it never deletes accounts or orders. */
final class YOAA_AAC_Request_Deletion {
	public function __construct() {
		add_action( 'user_request_action_confirmed', [ $this, 'confirmed' ], 20 );
		if ( 'yes' === get_option( 'yoaa_wc_enable_delete_account' ) ) {
			add_action( 'woocommerce_after_edit_account_form', [ $this, 'render' ] );
		}
	}
	public static function request( $user_id ) {
		$id = absint( get_user_meta( $user_id, '_yoaa_account_erasure_request_id', true ) );
		$request = $id ? wp_get_user_request( $id ) : false;
		$user = get_userdata( $user_id );
		return $request && $user && 'remove_personal_data' === $request->action_name && (int) $request->user_id === (int) $user_id && strtolower( (string) $request->email ) === strtolower( (string) $user->user_email ) ? $request : false;
	}
	public function confirmed( $request_id ) {
		$request = wp_get_user_request( $request_id );
		if ( ! $request || 'remove_personal_data' !== $request->action_name || 'request-confirmed' !== $request->status || ! $request->user_id ) { return; }
		$bound = self::request( $request->user_id );
		if ( ! $bound || (int) $bound->ID !== (int) $request_id || '0' !== (string) get_user_meta( $request->user_id, 'account_erasure_requested', true ) ) { return; }
		update_user_meta( $request->user_id, 'account_erasure_requested', '1' );
		WP_Session_Tokens::get_instance( $request->user_id )->destroy_all();
	}
	public function submit( $user_id, $resend = false ) {
		if ( ! is_user_logged_in() || get_current_user_id() !== (int) $user_id || 'yes' !== get_option( 'yoaa_wc_enable_delete_account' ) ) { return new WP_Error( 'erasure_forbidden', __( 'This request is not permitted.', 'wc-advanced-accounts' ) ); }
		$user = get_userdata( $user_id );
		if ( ! $user || ! is_email( $user->user_email ) || get_user_meta( $user_id, 'temporary_email', true ) === $user->user_email || false !== stripos( $user->user_email, '@temp-email-' ) ) {
			return new WP_Error( 'erasure_email', __( 'Add and save a valid email address in Account details before requesting deletion. WordPress will send a confirmation email.', 'wc-advanced-accounts' ) );
		}
		$request = self::request( $user_id );
		if ( $request && in_array( $request->status, [ 'request-confirmed', 'request-completed' ], true ) ) { return new WP_Error( 'erasure_confirmed', __( 'Your confirmed request is awaiting processing through WordPress.', 'wc-advanced-accounts' ) ); }
		if ( $request && 'request-pending' === $request->status && ! $resend ) { return new WP_Error( 'erasure_pending', __( 'A request is already pending. Confirm it using the email, or resend the confirmation.', 'wc-advanced-accounts' ) ); }
		if ( $resend && ( ! $request || 'request-pending' !== $request->status ) ) { return new WP_Error( 'erasure_stale', __( 'The pending request is no longer available. Reload the page.', 'wc-advanced-accounts' ) ); }
		if ( ! $request || 'request-pending' !== $request->status ) {
			$id = wp_create_user_request( $user->user_email, 'remove_personal_data' );
			if ( is_wp_error( $id ) || ! $id ) { return is_wp_error( $id ) ? $id : new WP_Error( 'erasure_create', __( 'The privacy request could not be created.', 'wc-advanced-accounts' ) ); }
			update_user_meta( $user_id, '_yoaa_account_erasure_request_id', $id );
			update_user_meta( $user_id, 'account_erasure_requested', '0' );
			$request = self::request( $user_id );
			if ( ! $request || (int) $request->ID !== (int) $id || '0' !== (string) get_user_meta( $user_id, 'account_erasure_requested', true ) ) { return new WP_Error( 'erasure_storage', __( 'The request was created but could not be linked to your account. Contact the administrator through the WordPress privacy process.', 'wc-advanced-accounts' ) ); }
		}
		$last = (int) get_user_meta( $user_id, '_yoaa_account_erasure_mail_time', true );
		if ( $last > time() - 60 ) { return new WP_Error( 'erasure_rate', __( 'Please wait one minute before resending the confirmation email.', 'wc-advanced-accounts' ) ); }
		update_user_meta( $user_id, '_yoaa_account_erasure_mail_time', time() );
		$sent = wp_send_user_request( $request->ID );
		if ( is_wp_error( $sent ) || ! $sent ) { return new WP_Error( 'erasure_mail', __( 'Your WordPress request exists, but the confirmation email could not be sent. Retry later or contact the administrator.', 'wc-advanced-accounts' ) ); }
		return true;
	}
	public function render() {
		if ( ! is_user_logged_in() ) { return; }
		$user_id = get_current_user_id();
		if ( isset( $_POST['yoaa_erasure_action'] ) ) {
			$nonce = isset( $_POST['yoaa_erasure_nonce'] ) && is_scalar( $_POST['yoaa_erasure_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['yoaa_erasure_nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yoaa_erasure_' . $user_id ) ) { wc_print_notice( __( 'Security check failed. Please reload the page.', 'wc-advanced-accounts' ), 'error' ); }
			elseif ( in_array( $_POST['yoaa_erasure_action'], [ 'submit', 'resend' ], true ) ) {
				$result = $this->submit( $user_id, 'resend' === $_POST['yoaa_erasure_action'] );
				wc_print_notice( is_wp_error( $result ) ? $result->get_error_message() : __( 'Confirmation email sent. Confirm your request using the link in that email.', 'wc-advanced-accounts' ), is_wp_error( $result ) ? 'error' : 'success' );
			}
		}
		$request = self::request( $user_id );
		echo '<fieldset><legend>' . esc_html__( 'Account and personal data deletion request', 'wc-advanced-accounts' ) . '</legend><p>' . esc_html__( 'This sends a privacy request to WordPress for administrator processing after email confirmation. It does not automatically delete your account or orders.', 'wc-advanced-accounts' ) . '</p>';
		if ( '1' === (string) get_user_meta( $user_id, 'account_erasure_requested', true ) || ( $request && in_array( $request->status, [ 'request-confirmed', 'request-completed' ], true ) ) ) {
			echo '<p>' . esc_html__( 'Your request has been confirmed. Contact the administrator about processing and data retention.', 'wc-advanced-accounts' ) . '</p></fieldset>'; return;
		}
		$pending = $request && 'request-pending' === $request->status;
		if ( $pending ) { echo '<p>' . esc_html__( 'Awaiting email confirmation. You can still use your account until the request is confirmed.', 'wc-advanced-accounts' ) . '</p>'; }
		elseif ( metadata_exists( 'user', $user_id, 'account_erasure_requested' ) ) { echo '<p>' . esc_html__( 'The previous pending notice is no longer linked to an active request. Submitting again will use the WordPress privacy process; any existing WordPress request remains unchanged.', 'wc-advanced-accounts' ) . '</p>'; }
		echo '<form method="post">';
		wp_nonce_field( 'yoaa_erasure_' . $user_id, 'yoaa_erasure_nonce' );
		echo '<button class="button" name="yoaa_erasure_action" value="' . ( $pending ? 'resend' : 'submit' ) . '">' . esc_html( $pending ? __( 'Resend confirmation email', 'wc-advanced-accounts' ) : __( 'Request account/data deletion', 'wc-advanced-accounts' ) ) . '</button></form></fieldset>';
	}
}
add_action( 'plugins_loaded', static function () {
	if ( class_exists( 'YOAA_WC_Advanced_Accounts_Request_Deletion' ) && class_exists( 'WCAAP_Runtime_Policy' ) && WCAAP_Runtime_Policy::commercial() ) { return; }
	new YOAA_AAC_Request_Deletion();
}, 110 );

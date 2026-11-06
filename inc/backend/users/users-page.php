<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/../helpers/admin-verification.php';

class YOAA_WC_Advanced_Accounts_Users_Page {
	public function __construct() {
		add_filter( 'manage_users_columns', [ $this, 'add_verified_column' ] );
		add_filter( 'manage_users_custom_column', [ $this, 'render_verified_column' ], 10, 3 );
		add_filter( 'user_row_actions', [ $this, 'row_actions' ], 10, 2 );
		add_action( 'admin_action_yoaa_account_action', [ $this, 'handle_row_action' ] );
		add_filter( 'bulk_actions-users', [ $this, 'bulk_actions' ] );
		add_filter( 'handle_bulk_actions-users', [ $this, 'handle_bulk' ], 10, 3 );
		add_action( 'admin_notices', [ $this, 'notice' ] );
	}
	public function add_verified_column( $columns ) {
		$result = [];
		foreach ( $columns as $key => $label ) {
			if ( 'username' === $key ) { $result['verified'] = __( 'Email verification', 'wc-advanced-accounts' ); }
			$result[ $key ] = $label;
		}
		return $result;
	}
	public function render_verified_column( $value, $column, $user_id ) {
		if ( 'verified' !== $column ) { return $value; }
		$label = '1' === (string) get_user_meta( $user_id, 'email_verification', true ) ? __( 'Verified', 'wc-advanced-accounts' ) : __( 'Unverified', 'wc-advanced-accounts' );
		if ( 'yes' === get_option( 'yoaa_wc_enable_user_blocking' ) && '1' === (string) get_user_meta( $user_id, 'user_blocked', true ) ) { $label .= ' / ' . __( 'Blocked', 'wc-advanced-accounts' ); }
		return esc_html( $label );
	}
	public function bulk_actions( $actions ) {
		if ( ! current_user_can( 'edit_users' ) ) { return $actions; }
		$actions['yoaa_verify_user_email'] = __( 'Verify email (administrator override)', 'wc-advanced-accounts' );
		$actions['yoaa_unverify_user_email'] = __( 'Unverify email', 'wc-advanced-accounts' );
		if ( 'yes' === get_option( 'yoaa_wc_enable_user_blocking' ) ) {
			$actions['yoaa_block_users'] = __( 'Block', 'wc-advanced-accounts' );
			$actions['yoaa_unblock_users'] = __( 'Unblock', 'wc-advanced-accounts' );
		}
		return $actions;
	}
	public function row_actions( $actions, $user ) {
		if ( ! current_user_can( 'edit_users' ) || ! current_user_can( 'edit_user', $user->ID ) ) { return $actions; }
		$available = $this->bulk_actions( [] );
		if ( in_array( 'administrator', (array) $user->roles, true ) || is_super_admin( $user->ID ) ) { $available = array_intersect_key( $available, [ 'yoaa_unblock_users' => true ] ); }
		foreach ( $available as $operation => $label ) {
			$url = wp_nonce_url( add_query_arg( [ 'action' => 'yoaa_account_action', 'user_id' => $user->ID, 'operation' => $operation ], admin_url( 'admin.php' ) ), 'yoaa_account_action_' . $user->ID );
			$actions[ $operation ] = '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
		}
		return $actions;
	}
	public function handle_row_action() {
		$user_id = absint( $_GET['user_id'] ?? 0 );
		check_admin_referer( 'yoaa_account_action_' . $user_id );
		$operation = sanitize_key( wp_unslash( $_GET['operation'] ?? '' ) );
		$redirect = $this->handle_bulk( admin_url( 'users.php' ), $operation, [ $user_id ] );
		wp_safe_redirect( $redirect );
		exit;
	}
	public function handle_bulk( $redirect, $operation, $user_ids ) {
		if ( ! current_user_can( 'edit_users' ) || ! isset( $this->bulk_actions( [] )[ $operation ] ) ) { return $redirect; }
		check_admin_referer( isset( $_GET['action'] ) && 'yoaa_account_action' === $_GET['action'] ? 'yoaa_account_action_' . absint( $_GET['user_id'] ?? 0 ) : 'bulk-users' );
		$counts = array_fill_keys( [ 'updated', 'unchanged', 'skipped', 'forbidden', 'error' ], 0 );
		foreach ( array_unique( array_map( 'absint', (array) $user_ids ) ) as $user_id ) {
			if ( in_array( $operation, [ 'yoaa_verify_user_email', 'yoaa_unverify_user_email' ], true ) ) {
				$result = YOAA_AAC_Admin_Verification::mutate( $user_id, [ 'email_verification' => 'yoaa_verify_user_email' === $operation ? '1' : '0' ] );
				$status = $result['status'];
			} else { $status = YOAA_AAC_Account_Status::set_blocked( $user_id, 'yoaa_block_users' === $operation ); }
			$counts[ $status ]++;
		}
		$args = [ 'yoaa_account_result' => 1 ];
		foreach ( $counts as $status => $count ) { $args[ 'yoaa_account_' . $status ] = $count; }
		return add_query_arg( $args, $redirect );
	}
	public function notice() {
		if ( 'users.php' !== ( $GLOBALS['pagenow'] ?? '' ) || ! current_user_can( 'edit_users' ) || empty( $_GET['yoaa_account_result'] ) ) { return; }
		echo '<div class="notice notice-info"><p>' . esc_html( sprintf( __( 'Account actions — updated: %1$d; unchanged: %2$d; skipped: %3$d; forbidden: %4$d; failed: %5$d.', 'wc-advanced-accounts' ), absint( $_GET['yoaa_account_updated'] ?? 0 ), absint( $_GET['yoaa_account_unchanged'] ?? 0 ), absint( $_GET['yoaa_account_skipped'] ?? 0 ), absint( $_GET['yoaa_account_forbidden'] ?? 0 ), absint( $_GET['yoaa_account_error'] ?? 0 ) ) ) . '</p></div>';
	}
}
add_action( 'plugins_loaded', static function () {
	if ( class_exists( 'YOAA_WC_Advanced_Accounts_Users_Page_Premium' ) && class_exists( 'WCAAP_Runtime_Policy' ) && WCAAP_Runtime_Policy::commercial() ) { return; }
	new YOAA_WC_Advanced_Accounts_Users_Page();
}, 110 );

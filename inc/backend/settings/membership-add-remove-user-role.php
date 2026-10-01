<?php

defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/../../cores/helper/role-ownership.php';

class YOAA_WC_Advanced_Accounts_Membership_Add_Remove_User_Role_Free {
	private $owner = 'wc-advanced-accounts';

	public function __construct() {
		add_action( 'admin_init', array( $this, 'handle_role_actions' ) );
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
	}

	/** Historical selection/markers are usage hints, never creator proof. */
	public function migrate_legacy_membership_roles() : void {}
	public function is_plugin_created_role( string $slug ) : bool {
		return YOSWC_Role_Ownership::owns( $this->owner, $slug );
	}
	public function handle_role_actions() {
		if ( ! current_user_can( 'manage_woocommerce' ) || 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { return; }
		if ( isset( $_POST['membership_add_new_role_submit'] ) ) { $this->add_new_role(); }
		if ( isset( $_POST['membership_remove_role_submit'] ) ) { $this->remove_role(); }
		if ( isset( $_POST['membership_delete_role_submit'] ) ) { $this->delete_role(); }
	}
	private function authorized( $action, $field ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) { return false; }
		if ( ! isset( $_POST[ $field ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $field ] ) ), $action ) ) {
			wp_die( esc_html__( 'Nonce verification failed. Please try again.', 'wc-advanced-accounts' ) );
		}
		return true;
	}
	private function notice( $result, $success ) {
		set_transient( 'yoswc_membership_admin_notice', array( 'success' => true === $result, 'message' => true === $result ? $success : YOSWC_Role_Ownership::message( $result ) ), 30 );
	}
	private function add_new_role() {
		if ( ! $this->authorized( 'add_new_role_action', 'add_new_role_nonce' ) ) { return; }
		$name = sanitize_text_field( wp_unslash( $_POST['new_role_name'] ?? '' ) );
		$raw = sanitize_text_field( wp_unslash( $_POST['new_role_slug'] ?? '' ) );
		if ( '' === $name ) { $this->notice( 'fields', __( 'A role name is required.', 'wc-advanced-accounts' ) ); return; }
		$slug = $this->generate_role_slug_from_name( '' === $raw ? $name : $raw );
		$this->notice( YOSWC_Role_Ownership::create( $this->owner, $slug, $name ), __( 'Role created with verified creator provenance.', 'wc-advanced-accounts' ) );
	}
	private function generate_role_slug_from_name( string $name ) : string {
		$name = function_exists( 'remove_accents' ) ? remove_accents( $name ) : $name;
		$slug = trim( preg_replace( '/_+/', '_', preg_replace( '/[^a-z0-9_]+/', '_', strtolower( trim( $name ) ) ) ), '_' );
		return '' === $slug ? 'member' : $slug;
	}
	/** The existing Remove POST becomes non-destructive retirement. */
	private function remove_role() {
		if ( ! $this->authorized( 'remove_role_action', 'remove_role_nonce' ) ) { return; }
		$slug = sanitize_key( wp_unslash( $_POST['role_to_remove'] ?? '' ) );
		$this->notice( YOSWC_Role_Ownership::retire( $this->owner, $slug ), __( 'Role retired. The physical role, users, claims and configuration were preserved.', 'wc-advanced-accounts' ) );
	}
	private function delete_role() {
		if ( ! $this->authorized( 'delete_role_action', 'delete_role_nonce' ) ) { return; }
		$slug = sanitize_key( wp_unslash( $_POST['role_to_delete'] ?? '' ) );
		$this->notice( YOSWC_Role_Ownership::hard_delete( $this->owner, $slug ), __( 'Unused exclusively owned role deleted. Ownership cleanup was verified.', 'wc-advanced-accounts' ) );
	}
	public function display_add_remove_role() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }
		$roles = wp_roles()->role_names;
		?>
		<h2><?php esc_html_e( 'Membership — Role management', 'wc-advanced-accounts' ); ?></h2>
		<form method="post">
			<h3><?php esc_html_e( 'Add new role', 'wc-advanced-accounts' ); ?></h3>
			<p><label><?php esc_html_e( 'Role name', 'wc-advanced-accounts' ); ?> <input name="new_role_name" type="text" /></label></p>
			<p><label><?php esc_html_e( 'Role slug', 'wc-advanced-accounts' ); ?> <input name="new_role_slug" type="text" /></label></p>
			<?php wp_nonce_field( 'add_new_role_action', 'add_new_role_nonce' ); submit_button( __( 'Add new role', 'wc-advanced-accounts' ), 'primary', 'membership_add_new_role_submit' ); ?>
		</form>
		<table class="widefat"><thead><tr><th><?php esc_html_e( 'Role', 'wc-advanced-accounts' ); ?></th><th><?php esc_html_e( 'Creator / management', 'wc-advanced-accounts' ); ?></th></tr></thead><tbody>
		<?php foreach ( $roles as $slug => $name ) : if ( YOSWC_Role_Ownership::protected_role( $slug ) ) { continue; }
			$record = YOSWC_Role_Ownership::record( $slug );
			$labels = array( 'wc-advanced-accounts-premium' => 'Advanced Accounts Premium', 'wc-advanced-accounts' => 'Advanced Accounts', 'wc-loyalty' => 'WooCommerce Loyalty' );
			$label = is_array( $record ) && isset( $labels[ $record['owner'] ] ) && YOSWC_Role_Ownership::owns( $record['owner'], $slug ) ? $labels[ $record['owner'] ] . ' / ' . ( 'retired' === $record['state'] ? __( 'Retired', 'wc-advanced-accounts' ) : __( 'Active', 'wc-advanced-accounts' ) ) : __( 'Legacy or unknown creator; hard deletion unavailable', 'wc-advanced-accounts' );
			if ( is_array( $record ) && in_array( $record['state'], array( 'pending', 'deleting' ), true ) ) { $label = __( 'Ownership write incomplete; preserve this role and resolve storage failure. Hard deletion is unavailable.', 'wc-advanced-accounts' ); } ?>
			<tr><td><?php echo esc_html( $name . ' (' . $slug . ')' ); ?></td><td><?php echo esc_html( $label ); ?></td></tr>
		<?php endforeach; ?></tbody></table>
		<form method="post">
			<h3><?php esc_html_e( 'Retire role', 'wc-advanced-accounts' ); ?></h3>
			<p><?php esc_html_e( 'Retirement keeps the physical role and all users, claims and rules. It does not deselect Membership or Loyalty usage.', 'wc-advanced-accounts' ); ?></p>
			<select name="role_to_remove"><?php foreach ( $roles as $slug => $name ) : if ( YOSWC_Role_Ownership::protected_role( $slug ) ) { continue; } ?>
				<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option>
			<?php endforeach; ?></select>
			<?php wp_nonce_field( 'remove_role_action', 'remove_role_nonce' ); submit_button( __( 'Retire role', 'wc-advanced-accounts' ), 'secondary', 'membership_remove_role_submit' ); ?>
		</form>
		<form method="post">
			<h3><?php esc_html_e( 'Delete unused retired role permanently', 'wc-advanced-accounts' ); ?></h3>
			<p><?php esc_html_e( 'Only a role with verified creator provenance from this plugin can be deleted. Saved configuration, user assignments, claims, recoverable purchases, unavailable companion evidence or an incomplete dependency scan block deletion.', 'wc-advanced-accounts' ); ?></p>
			<select name="role_to_delete"><option value=""><?php esc_html_e( 'Select an exclusively owned retired role', 'wc-advanced-accounts' ); ?></option>
			<?php foreach ( $roles as $slug => $name ) : $record = YOSWC_Role_Ownership::record( $slug ); if ( ! $this->is_plugin_created_role( $slug ) || 'retired' !== $record['state'] ) { continue; } ?>
				<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option>
			<?php endforeach; ?></select>
			<?php wp_nonce_field( 'delete_role_action', 'delete_role_nonce' ); submit_button( __( 'Delete permanently after preflight', 'wc-advanced-accounts' ), 'secondary', 'membership_delete_role_submit' ); ?>
		</form>
		<?php
	}
	public function display_admin_notices() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }
		$notice = get_transient( 'yoswc_membership_admin_notice' );
		if ( is_array( $notice ) && isset( $notice['success'], $notice['message'] ) && is_string( $notice['message'] ) ) {
			echo '<div class="notice ' . ( $notice['success'] ? 'notice-success' : 'notice-error' ) . '"><p>' . esc_html( $notice['message'] ) . '</p></div>';
		}
		delete_transient( 'yoswc_membership_admin_notice' );
	}
}

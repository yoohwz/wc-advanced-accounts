<?php
/** Contract fixtures for AAP inactive recovery presenters; no commercial activation claim. */
define( 'ABSPATH', __DIR__ . '/' );
class WP_Hook { public $callbacks = []; }
$wp_filter = []; $commercial = false; $blocking = 'yes';
class WCAAP_Runtime_Policy { public static function commercial() { global $commercial; return $commercial; } }
class YOAA_WC_Advanced_Accounts_User_Profile_Actions {
	public function show_user_account_actions() {}
	public function update_user_account_actions() {}
	public function deny_blocked_user_login() {}
	public function show_user_birth_date() {}
}
class YOAA_WC_Advanced_Accounts_Users_Page_Premium {
	public function add_block_user_action() {}
	public function handle_unblock_user_action() {}
}
function add_action( $hook, $callback, $priority = 10, $args = 1 ) { global $wp_filter; $wp_filter[$hook] = $wp_filter[$hook] ?? new WP_Hook(); $wp_filter[$hook]->callbacks[$priority][] = [ 'function' => $callback ]; }
function add_filter( ...$args ) { add_action( ...$args ); }
function remove_filter( $hook, $callback, $priority = 10 ) { global $wp_filter; foreach ( $wp_filter[$hook]->callbacks[$priority] ?? [] as $key => $entry ) { if ( $entry['function'] === $callback ) { unset( $wp_filter[$hook]->callbacks[$priority][$key] ); } } }
function callbacks( $hook ) { global $wp_filter; $result=[]; foreach ( $wp_filter[$hook]->callbacks ?? [] as $entries ) { foreach ( $entries as $entry ) { $result[]=$entry['function']; } } return $result; }
function expect( $condition, $label ) { if ( ! $condition ) { throw new RuntimeException( $label ); } echo "PASS $label\n"; }
require __DIR__ . '/../inc/backend/users/users-page.php';
require __DIR__ . '/../inc/backend/users/user-profile.php';
$boots = callbacks( 'plugins_loaded' );
foreach ( [ false, true ] as $commercial ) {
	$wp_filter = [];
	$profile = new YOAA_WC_Advanced_Accounts_User_Profile_Actions(); $users = new YOAA_WC_Advanced_Accounts_Users_Page_Premium();
	add_action( 'edit_user_profile', [ $profile, 'show_user_account_actions' ] );
	add_filter( 'user_row_actions', [ $users, 'add_block_user_action' ] );
	add_action( 'edit_user_profile_update', [ $profile, 'update_user_account_actions' ] );
	add_action( 'admin_init', [ $users, 'handle_unblock_user_action' ] );
	add_filter( 'authenticate', [ $profile, 'deny_blocked_user_login' ] );
	add_action( 'edit_user_profile', [ $profile, 'show_user_birth_date' ] );
	foreach ( $boots as $boot ) { call_user_func( $boot ); }
	$profile_presenters = array_filter( callbacks( 'edit_user_profile' ), static function ( $callback ) { return in_array( $callback[1], [ 'show_user_account_actions', 'render' ], true ); } );
	$expected = $commercial ? $profile : 'YOAA_AAC_User_Profile';
	expect( count( $profile_presenters ) === 1, 'one account profile presenter when commercial=' . (int) $commercial );
	$presenter = array_values( $profile_presenters )[0];
	expect( $commercial ? $presenter[0] === $expected : $presenter[0] instanceof $expected, 'correct profile owner' );
	$row_presenters = callbacks( 'user_row_actions' );
	expect( count( $row_presenters ) === 1 && ( $commercial ? $row_presenters[0][0] === $users : $row_presenters[0][0] instanceof YOAA_WC_Advanced_Accounts_Users_Page ), 'one correct Users row-action owner' );
	expect( in_array( [ $profile, 'update_user_account_actions' ], callbacks( 'edit_user_profile_update' ), true ) && in_array( [ $users, 'handle_unblock_user_action' ], callbacks( 'admin_init' ), true ), 'legacy recovery handlers preserved' );
	expect( in_array( [ $profile, 'deny_blocked_user_login' ], callbacks( 'authenticate' ), true ) && in_array( [ $profile, 'show_user_birth_date' ], callbacks( 'edit_user_profile' ), true ), 'auth and unrelated presenters preserved' );
}
echo "Admin UI coexistence regression PASS\n";

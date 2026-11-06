<?php
/** Focused account-administration/WordPress privacy adapter regressions; no database runtime. */
define( 'ABSPATH', __DIR__ . '/' );
class WP_User { public $ID; public $roles; public $user_email; public function __construct( $id, $roles = [ 'customer' ] ) { $this->ID = $id; $this->roles = $roles; $this->user_email = "u$id@example.test"; } }
class WP_Error { private $code; private $message; public function __construct( $code, $message ) { $this->code = $code; $this->message = $message; } public function get_error_message() { return $this->message; } public function get_error_code() { return $this->code; } }
class WP_Session_Tokens { public static $destroyed = []; private $id; public static function get_instance( $id ) { $x = new self(); $x->id = $id; return $x; } public function destroy_all() { self::$destroyed[] = $this->id; } }
$GLOBALS['users'] = [ 1 => new WP_User( 1, [ 'administrator' ] ), 2 => new WP_User( 2 ), 3 => new WP_User( 3 ), 4 => new WP_User( 4, [ 'subscriber' ] ), 5 => new WP_User( 5 ) ];
$GLOBALS['meta'] = []; $GLOBALS['options'] = [ 'yoaa_wc_enable_user_blocking' => 'yes', 'yoaa_wc_enable_delete_account' => 'yes' ]; $GLOBALS['denied'] = []; $GLOBALS['can_edit'] = true; $GLOBALS['current'] = 2; $GLOBALS['nonce'] = true; $GLOBALS['requests'] = []; $GLOBALS['mail'] = true; $GLOBALS['query_error'] = false; $GLOBALS['hooks'] = [];
function __( $x, $domain = '' ) { return $x; }
function add_action( $hook, $callback, $priority = 10, $args = 1 ) { $GLOBALS['hooks'][$hook][] = $callback; }
function add_filter( $hook, $callback, $priority = 10, $args = 1 ) { add_action( $hook, $callback, $priority, $args ); }
function remove_action( $hook, $callback ) { $GLOBALS['hooks'][$hook] = array_values( array_filter( $GLOBALS['hooks'][$hook] ?? [], function ( $value ) use ( $callback ) { return $callback !== $value; } ) ); }
function absint( $x ) { return abs( (int) $x ); }
function current_user_can( $cap, $id = 0 ) { return $GLOBALS['can_edit'] && ! ( 'edit_user' === $cap && in_array( $id, $GLOBALS['denied'], true ) ); }
function get_userdata( $id ) { return $GLOBALS['users'][$id] ?? false; }
function get_user_meta( $id, $key, $single = false ) { $rows = $GLOBALS['meta'][$id][$key] ?? []; return $single ? ( $rows[0] ?? '' ) : $rows; }
function update_user_meta( $id, $key, $value ) { if ( ! empty( $GLOBALS['fail_write'] ) ) { return false; } $GLOBALS['meta'][$id][$key] = [ (string) $value ]; return true; }
function delete_user_meta( $id, $key ) { unset( $GLOBALS['meta'][$id][$key] ); }
function add_user_meta( $id, $key, $value ) { $GLOBALS['meta'][$id][$key][] = $value; }
function metadata_exists( $type, $id, $key ) { return isset( $GLOBALS['meta'][$id][$key] ); }
function wp_cache_delete( $id, $group ) {}
function wp_slash( $x ) { return $x; }
function maybe_unserialize( $x ) { return $x; }
function wp_unslash( $x ) { return $x; }
function sanitize_text_field( $x ) { return trim( $x ); }
function is_super_admin( $id ) { return 5 === $id; }
function get_option( $key, $default = false ) { return $GLOBALS['options'][$key] ?? $default; }
function get_current_user_id() { return $GLOBALS['current']; }
function is_user_logged_in() { return $GLOBALS['current'] > 0; }
function is_wp_error( $x ) { return $x instanceof WP_Error; }
function is_email( $x ) { return filter_var( $x, FILTER_VALIDATE_EMAIL ); }
function wp_get_user_request( $id ) { return $GLOBALS['requests'][$id] ?? false; }
function wp_create_user_request( $email, $action ) {
	if ( ! empty( $GLOBALS['create_error'] ) ) { return new WP_Error( 'duplicate', 'Existing privacy request.' ); }
	$id = count( $GLOBALS['requests'] ) + 10;
	$GLOBALS['requests'][$id] = (object) [ 'ID' => $id, 'user_id' => $GLOBALS['current'], 'email' => $email, 'status' => 'request-pending', 'action_name' => $action ];
	return $id;
}
function wp_send_user_request( $id ) { return $GLOBALS['mail']; }
function check_admin_referer( $action ) { if ( ! $GLOBALS['nonce'] ) { throw new RuntimeException( 'nonce denied' ); } }
function wp_verify_nonce( $nonce, $action ) { return $GLOBALS['nonce']; }
function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
class Fake_DB {
	public $usermeta = 'usermeta'; public $users = 'users'; public $last_error = '';
	public function prepare( $sql, ...$args ) { return false !== strpos( $sql, 'ID >' ) ? ' AND users.ID > ' . $args[0] : [ $sql, $args ]; }
	public function get_col( $query ) { return $GLOBALS['meta'][$query[1][0]][$query[1][1]] ?? []; }
}
$GLOBALS['wpdb'] = new Fake_DB();
class WP_User_Query {
	public $query_where = ''; private $args;
	public function prepare_query( $args ) { $this->args = $args; foreach ( $GLOBALS['hooks']['pre_user_query'] ?? [] as $callback ) { $callback( $this ); } }
	public function query() { $GLOBALS['wpdb']->last_error = $GLOBALS['query_error'] ? 'failed' : ''; }
	public function get_results() {
		preg_match( '/ID > (\d+)/', $this->query_where, $match ); $cursor = isset( $match[1] ) ? (int) $match[1] : 0;
		$users = array_filter( $GLOBALS['users'], function ( $u ) use ( $cursor ) { return $u->ID > $cursor && array_intersect( $u->roles, $this->args['role__in'] ); } );
		return array_slice( array_values( $users ), 0, $this->args['number'] );
	}
}
require __DIR__ . '/../inc/backend/actions/account-status.php';
require __DIR__ . '/../inc/backend/helpers/admin-verification.php';
require __DIR__ . '/../inc/backend/users/users-page.php';
require __DIR__ . '/../inc/backend/users/user-profile.php';
require __DIR__ . '/../inc/backend/settings/tools.php';
require __DIR__ . '/../inc/backend/actions/request-deletion.php';
function expect( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } }

expect( 'updated' === YOAA_AAC_Admin_Verification::mutate( 2, [ 'email_verification' => '1' ] )['status'], 'Email override persisted.' );
expect( 'unchanged' === YOAA_AAC_Admin_Verification::mutate( 2, [ 'email_verification' => '1' ] )['status'], 'Identical override is unchanged.' );
$before = $GLOBALS['meta'];
expect( 'would_update' === YOAA_AAC_Admin_Verification::mutate( 3, [ 'email_verification' => '1' ], true )['status'] && $before === $GLOBALS['meta'], 'Dry-run never writes.' );
expect( 'error' === YOAA_AAC_Admin_Verification::mutate( 2, [ 'phone_verification' => '1' ] )['status'], 'Phone override unavailable in Free.' );
expect( 'skipped' === YOAA_AAC_Admin_Verification::mutate( 1, [ 'email_verification' => '1' ] )['status'], 'Administrator protected.' );
expect( 'skipped' === YOAA_AAC_Admin_Verification::mutate( 5, [ 'email_verification' => '1' ] )['status'], 'Super administrator protected.' );
$GLOBALS['denied'] = [ 3 ];
expect( 'forbidden' === YOAA_AAC_Admin_Verification::mutate( 3, [ 'email_verification' => '1' ] )['status'], 'Target authority required.' );
expect( 'forbidden' === YOAA_AAC_Account_Status::set_blocked( 3, true ), 'Target authority required for blocking.' );
$GLOBALS['denied'] = [];
$GLOBALS['fail_write'] = true;
expect( 'error' === YOAA_AAC_Admin_Verification::mutate( 3, [ 'email_verification' => '1' ] )['status'], 'Failed verification not reported successful.' );
unset( $GLOBALS['fail_write'] );
expect( 'skipped' === YOAA_AAC_Account_Status::set_blocked( 1, true ), 'Administrator cannot be blocked.' );
expect( 'skipped' === YOAA_AAC_Account_Status::set_blocked( 2, true ), 'Cannot block self.' );
expect( 'updated' === YOAA_AAC_Account_Status::set_blocked( 3, true ) && [ 3 ] === WP_Session_Tokens::$destroyed, 'Blocking revokes existing sessions.' );
expect( is_wp_error( YOAA_AAC_Account_Status::denial( get_userdata( 3 ) ) ), 'Authentication denies blocked account.' );
expect( 'updated' === YOAA_AAC_Account_Status::set_blocked( 3, false ) && ! is_wp_error( YOAA_AAC_Account_Status::denial( get_userdata( 3 ) ) ), 'Unblocking restores access.' );
$ui = new YOAA_WC_Advanced_Accounts_Users_Page();
$GLOBALS['nonce'] = false;
try { $ui->handle_bulk( '/users', 'yoaa_verify_user_email', [ 3 ] ); expect( false, 'Missing nonce must deny bulk.' ); } catch ( RuntimeException $error ) { expect( 'nonce denied' === $error->getMessage(), 'Bulk checks nonce.' ); }
$GLOBALS['nonce'] = true; $GLOBALS['denied'] = [ 3 ];
$url = $ui->handle_bulk( '/users', 'yoaa_block_users', [ 1, 3, 4 ] );
expect( false !== strpos( $url, 'yoaa_account_forbidden=1' ) && '1' === get_user_meta( 4, 'user_blocked', true ) && '0' === get_user_meta( 3, 'user_blocked', true ), 'Bulk target authority and protected users.' );
$GLOBALS['denied'] = [];
$before = $GLOBALS['meta'];
$batch = YOAA_AAC_Email_Verification_Tool::run( 0, 1, true );
expect( 1 === count( $batch['rows'] ) && $batch['has_more'] && 2 === $batch['next_cursor'] && $before === $GLOBALS['meta'], 'Bounded dry-run batch and cursor.' );
$batch = YOAA_AAC_Email_Verification_Tool::run( 2, 1, false );
expect( 3 === $batch['next_cursor'] && '1' === get_user_meta( 3, 'email_verification', true ), 'Next batch proceeds strictly after the last ID.' );
$GLOBALS['query_error'] = true;
$batch = YOAA_AAC_Email_Verification_Tool::run( 99, 10, false );
expect( 99 === $batch['next_cursor'] && $batch['error'] && [] === ( $GLOBALS['hooks']['pre_user_query'] ?? [] ), 'Lookup failure preserves cursor and removes query hook.' );
$GLOBALS['query_error'] = false;
$privacy = new YOAA_AAC_Request_Deletion();
$GLOBALS['users'][2]->user_email = 'u2@temp-email-example.test';
expect( 'erasure_email' === $privacy->submit( 2 )->get_error_code(), 'Phone-first placeholder requires actual email.' );
$GLOBALS['users'][2]->user_email = 'u2@example.test';
$GLOBALS['mail'] = false;
expect( 'erasure_mail' === $privacy->submit( 2 )->get_error_code(), 'Mail failure never reports confirmation sent.' );
$id = (int) get_user_meta( 2, '_yoaa_account_erasure_request_id', true );
expect( $id && '0' === get_user_meta( 2, 'account_erasure_requested', true ) && ! is_wp_error( YOAA_AAC_Account_Status::denial( get_userdata( 2 ) ) ), 'Pending request keeps access.' );
expect( 'erasure_pending' === $privacy->submit( 2 )->get_error_code(), 'Duplicate submission reuses pending state.' );
$GLOBALS['mail'] = true;
update_user_meta( 2, '_yoaa_account_erasure_mail_time', time() - 61 );
expect( true === $privacy->submit( 2, true ), 'Pending request can retry failed confirmation email.' );
expect( 'erasure_rate' === $privacy->submit( 2, true )->get_error_code(), 'Resend cooldown enforced server-side.' );
$privacy->confirmed( $id );
expect( '0' === get_user_meta( 2, 'account_erasure_requested', true ), 'Pending request cannot become confirmed from hook alone.' );
$GLOBALS['requests'][$id]->status = 'request-confirmed';
$GLOBALS['requests'][$id]->email = 'other@example.test';
$privacy->confirmed( $id );
expect( '0' === get_user_meta( 2, 'account_erasure_requested', true ), 'Confirmation bound to current account email.' );
$GLOBALS['requests'][$id]->email = 'u2@example.test';
$privacy->confirmed( $id );
expect( '1' === get_user_meta( 2, 'account_erasure_requested', true ) && is_wp_error( YOAA_AAC_Account_Status::denial( get_userdata( 2 ) ) ), 'Exact confirmed request revokes sign-in.' );
expect( isset( $GLOBALS['users'][2] ), 'Privacy request never deletes account.' );
echo "Account administration/privacy focused regressions passed.\n";

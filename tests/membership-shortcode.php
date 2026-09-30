<?php
// Run with: php tests/membership-shortcode.php [optional companion source]
define( 'ABSPATH', __DIR__ . '/' );
$options = array( 'yoaa_wc_membership_roles' => array( 'gold', 'silver' ) );
$user = (object) array( 'ID' => 0, 'roles' => array() );
$effective = array();
$existing_roles = array( 'gold', 'silver' );
$calls = 0;
$shortcodes = array();
$wp_roles = (object) array( 'roles' => array( 'gold' => array( 'name' => 'Gold' ), 'silver' => array( 'name' => 'Silver' ) ) );
class YOAA_WC_Advanced_Accounts_Membership_Roles {
	public static function get_user_membership_roles() { global $effective; return $effective; }
}
function get_option( $key, $default = false ) { global $options; return $options[$key] ?? $default; }
function add_shortcode( $tag, $cb ) { global $shortcodes; $shortcodes[$tag] = $cb; }
function shortcode_atts( $defaults, $atts, $tag ) { return array_merge( $defaults, array_intersect_key( $atts, $defaults ) ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $value ) ); }
function sanitize_text_field( $value ) { return (string) $value; }
function is_user_logged_in() { global $user; return $user->ID > 0; }
function wp_get_current_user() { global $user; return $user; }
function get_role( $slug ) { global $existing_roles; return in_array( $slug, $existing_roles, true ) ? (object) array() : null; }
function do_shortcode( $body ) { global $calls; ++$calls; return str_replace( '[probe]', 'SECRET', $body ); }
function __( $text, $domain = '' ) { return $text; }
function esc_html__( $text, $domain = '' ) { return $text; }
function esc_html( $text ) { return htmlspecialchars( $text, ENT_QUOTES ); }
function esc_url( $url ) { return $url; }
function wc_get_page_permalink( $name ) { return '/my-account'; }
function wp_login_url() { return '/login'; }
function get_permalink( $id = 0 ) { return '/post'; }
function home_url( $url ) { return $url; }
function wp_unslash( $value ) { return $value; }
function add_query_arg( $key, $value, $url ) { return $url . '?' . $key . '=' . $value; }
function expect( $ok, $label ) { if ( ! $ok ) { throw new RuntimeException( $label ); } echo "PASS $label\n"; }
require $argv[1] ?? __DIR__ . '/../inc/backend/actions/membership/class-shortcodes.php';
$class = $shortcodes['yoaa_membership'][0];
function check_body( $atts, $allow, $label ) {
	global $class, $calls;
	$calls = 0;
	$result = $class::render_membership_shortcode( $atts, '[probe]' );
	expect( $allow ? 'SECRET' === $result && 1 === $calls : false === strpos( $result, 'SECRET' ) && 0 === $calls, $label );
	if ( ! $allow ) { expect( 'yes' === ( $atts['hide'] ?? '' ) ? '' === $result : false !== strpos( $result, 'yoaa-membership-shortcode-restricted' ), $label . ' notice/hide' ); }
}
check_body( array( 'level'=>'gold' ), false, 'guest cannot execute member body' );
check_body( array(), true, 'omitted level retains general behavior' );
check_body( array( 'guest'=>'yes' ), true, 'guest semantics retained' );
check_body( array( 'logged_in'=>'yes' ), false, 'logged-in gate denies guest' );
$user = (object) array( 'ID'=>1, 'roles'=>array('gold') ); $effective = array('gold');
check_body( array( 'level'=>'gold' ), true, 'single valid level executes once' );
check_body( array( 'level'=>' GOLD , silver , gold ' ), true, 'normalized multiple levels grant OR' );
check_body( array( 'level'=>'silver' ), false, 'other role denies' );
check_body( array( 'level'=>'unknown' ), false, 'unknown level fails closed' );
check_body( array( 'level'=>'gold,unknown' ), false, 'mixed valid and invalid policy fails closed' );
check_body( array( 'level'=>'!!!' ), false, 'unresolvable nonempty token fails closed' );
check_body( array( 'level'=>'0' ), false, 'nonempty zero is not empty policy' );
check_body( array( 'level'=>', ,' ), false, 'nonempty delimiter-only policy fails closed' );
check_body( array( 'level'=>'unknown', 'hide'=>'yes' ), false, 'hide on invalid level remains empty' );
check_body( array( 'level'=>'', 'logged_in'=>'yes' ), true, 'empty level retains logged-in semantics' );
check_body( array( 'guest'=>'yes' ), false, 'guest-only denies member' );
$user->roles = array('silver'); $effective = array('silver');
check_body( array( 'level'=>'gold,silver' ), true, 'second valid level grants OR' );
$options['yoaa_wc_membership_roles'] = array('gold');
check_body( array( 'level'=>'silver' ), false, 'deselected role fails closed' );
$options['yoaa_wc_membership_roles'] = array('gold','silver'); $existing_roles = array('gold');
check_body( array( 'level'=>'silver' ), false, 'deleted WP role fails closed while option remains stale' );
$options['yoaa_wc_membership_roles'] = array(); $shortcodes = array(); $class::init();
expect( isset($shortcodes['yoaa_membership']), 'registration remains owned with zero configured roles' );
check_body( array( 'level'=>'silver' ), false, 'deselected last role fails closed' );
if ( 'YOAA_WC_Advanced_Accounts_Membership_Shortcodes' === $class ) {
	$options['yoaa_wc_membership_roles'] = array('gold'); $user->roles = array('gold'); $effective = array();
	check_body( array( 'level'=>'gold' ), false, 'Premium uses effective entitlement rather than raw role' );
	$result = $class::render_membership_shortcode( array('level'=>'unknown','message'=>'Custom notice'), '[probe]' );
	expect( false !== strpos($result, 'Custom notice'), 'Premium custom message remains supported' );
}

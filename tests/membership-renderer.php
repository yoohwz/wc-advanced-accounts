<?php
// Native WP shortcode engine with bounded auth/options stand-ins; no WordPress boot/database.
$wp_source = rtrim( (string) getenv( 'AAP_MEMBERSHIP_WP_ROOT' ), '/' ) . '/';
if ( ! is_file( $wp_source . 'wp-includes/shortcodes.php' ) ) { throw new RuntimeException( 'Set AAP_MEMBERSHIP_WP_ROOT to extracted WordPress source.' ); }
define( 'ABSPATH', $wp_source );
define( 'WPINC', 'wp-includes' );
require ABSPATH . 'wp-includes/compat.php';
require ABSPATH . 'wp-includes/plugin.php';
require ABSPATH . 'wp-includes/formatting.php';
require ABSPATH . 'wp-includes/kses.php';
require ABSPATH . 'wp-includes/shortcodes.php';
function wp_allowed_protocols() { return array('http','https'); }
$options = array( 'yoaa_wc_membership_roles'=>array('gold','silver'), 'blog_charset'=>'UTF-8' );
$user = (object) array('ID'=>0,'roles'=>array()); $effective = array(); $calls = 0;
$wp_roles = (object) array('roles'=>array('gold'=>array('name'=>'Gold'),'silver'=>array('name'=>'Silver')));
class YOAA_WC_Advanced_Accounts_Membership_Roles {
	public static function get_user_membership_roles() { global $effective; return $effective; }
}
function get_option($key,$default=false) { global $options; return $options[$key]??$default; }
function get_role($slug) { return in_array($slug,array('gold','silver'),true) ? (object) array() : null; }
function is_user_logged_in() { global $user; return $user->ID>0; }
function wp_get_current_user() { global $user; return $user; }
function __($text,$domain='') { return $text; }
function esc_html__($text,$domain='') { return esc_html($text); }
function wc_get_page_permalink($name) { return '/my-account'; }
function wp_login_url() { return '/login'; }
function get_permalink($id=0) { return '/post'; }
function home_url($url) { return $url; }
function add_query_arg($key,$value,$url) { return $url.'?'.$key.'='.$value; }
function expect($ok,$label) { if(!$ok){throw new RuntimeException($label);}echo "PASS $label\n"; }
require $argv[1] ?? __DIR__.'/../inc/backend/actions/membership/class-shortcodes.php';
$class = $shortcode_tags['yoaa_membership'][0];
add_shortcode('probe',function() use(&$calls){++$calls;return 'SECRET';});
foreach(array('the_content','widget_text_content','widget_block_content') as $hook){add_filter($hook,'do_shortcode',11);}
$render = function($input,$pipeline) { return 'safe'===$pipeline ? yoaa_render_membership_content($input) : apply_filters($pipeline,$input); };
foreach(array(0,1) as $member) {
	$user=(object)array('ID'=>$member,'roles'=>$member?array('gold'):array()); $effective=$user->roles;
	foreach(array('safe','the_content','widget_text_content','widget_block_content') as $pipeline) {
		foreach(array('[probe]','<span data-value="[probe]">protected</span>') as $body) {
			$calls=0; $input='BEFORE[yoaa_membership level="gold" hide="yes"]'.$body.'[/yoaa_membership]AFTER';
			$prepared=YOAA_Membership_Content_Renderer::preauthorize_content($input);
			expect(0===$calls,'pure guard: '.$pipeline.'/'.$member);
			expect($prepared===YOAA_Membership_Content_Renderer::preauthorize_content($prepared),'idempotent guard: '.$pipeline.'/'.$member);
			$output=$render($input,$pipeline);
			expect($calls===$member && (bool)strpos($output,'SECRET')===(bool)$member,'native execution count: '.$pipeline.'/'.$member);
			expect(0===strpos($output,'BEFORE') && 'AFTER'===substr($output,-5),'surrounding content retained');
		}
	}
}
$user=(object)array('ID'=>1,'roles'=>array('gold'));$effective=array('gold');
foreach(array(
	'[yoaa_membership level="gold"][probe]',
	'[yoaa_membership level="gold][probe][/yoaa_membership]',
	'[yoaa_membership level=][probe][/yoaa_membership]',
	'[yoaa_membership level="gold" [probe]][probe][/yoaa_membership]',
	'[yoaa_membership level="gold /][probe][/yoaa_membership]',
	'[yoaa_membership level="gold"][yoaa_membership level="gold"][probe][/yoaa_membership][probe][/yoaa_membership]',
	'[yoaa_membership level="gold" [yoaa_membership level="gold"]][probe][/yoaa_membership][probe][/yoaa_membership]',
) as $input){
	$calls=0;$output=yoaa_render_membership_content('BEFORE'.$input);
	expect(0===$calls && false===strpos($output,'SECRET') && 0===strpos($output,'BEFORE'),'malformed/nested region fails closed: '.$input);
}
$calls=0;$output=yoaa_render_membership_content('BEFORE[yoaa_membership level="gold][probe][/yoaa_membership]AFTER[probe]');
expect(1===$calls && false!==strpos($output,'AFTERSECRET'),'malformed paired region preserves separable following content');
foreach(array('[[yoaa_membership level="unknown"]]','[[yoaa_membership level="unknown"]literal[/yoaa_membership]]') as $input){
	$calls=0;$prepared=YOAA_Membership_Content_Renderer::preauthorize_content($input);
	expect($input===$prepared,'escaped markup unchanged by guard');
	$output=yoaa_render_membership_content($input);
	expect(0===$calls && false===strpos($output,'yoaa-membership-shortcode-restricted') && false!==strpos($output,'[yoaa_membership'),'escaped markup remains literal');
}

$user=(object)array('ID'=>0,'roles'=>array());$effective=array();
foreach(array('level="gold" level=""','level="unknown" level=""','level="unknown" level="silver" guest="yes"','level="unknown" LEVEL=""','guest="yes" guest="no"') as $attributes){
	foreach(array('safe','the_content','widget_text_content','widget_block_content') as $pipeline){
		$calls=0;$output=$render('BEFORE[yoaa_membership '.$attributes.']<span data-x="[probe]">protected</span>[probe][/yoaa_membership]AFTER',$pipeline);
		expect(0===$calls && false===strpos($output,'SECRET') && false!==strpos($output,'AFTER'),'duplicate named attributes fail closed: '.$pipeline.'/'.$attributes);
	}
}
foreach(array('<','\x3c','\074','\n') as $level){
	foreach(array('safe','the_content','widget_text_content','widget_block_content') as $pipeline){
		$calls=0;$output=$render('BEFORE[yoaa_membership level="'.$level.'"]<span data-x="[probe]">protected</span>[probe][/yoaa_membership]AFTER',$pipeline);
		expect(0===$calls && false===strpos($output,'SECRET') && false!==strpos($output,'AFTER'),'nonempty native-erased level fails closed: '.$pipeline.'/'.$level);
	}
}

$calls=0;$output=yoaa_render_membership_content('[probe][yoaa_membership level="gold"]<i data-v="[probe]">hidden</i>[/yoaa_membership][probe]');
expect(2===$calls && false!==strpos($output,'yoaa-membership-shortcode-restricted'),'unrelated native shortcodes unaffected, restriction notice retained');
foreach(array('gold,unknown','unknown') as $levels){$calls=0;yoaa_render_membership_content('[yoaa_membership level="'.$levels.'" guest="yes"][probe][/yoaa_membership]');expect(0===$calls,'invalid explicit policy cannot bypass through guest flag');}
if('YOAA_WC_Advanced_Accounts_Membership_Shortcodes'===$class){
	$calls=0;$output=yoaa_render_membership_content('[yoaa_membership level="gold" message="Notice"][probe][/yoaa_membership]');
	expect(0===$calls && false!==strpos($output,'Notice'),'Premium notice preserved');
}
expect(-9998===has_filter('the_content',array('YOAA_Membership_Content_Renderer','preauthorize_content')),'guard priority precedes native execution');

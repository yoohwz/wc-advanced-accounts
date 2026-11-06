<?php
/** php tests/custom-links.php — native endpoint/link separation and guarded CRUD. */
define('ABSPATH',__DIR__.'/');
$options=[]; $can=true; $nonce=true;
class WP { public $public_query_vars=['p','page','name','feed','attachment']; }
class WC_Admin_Settings { public static $errors=[]; public static function add_error($error){self::$errors[]=$error;} }
function add_action(...$args){} function add_filter(...$args){}
function get_option($key,$default=false){global $options;return $options[$key]??$default;}
function update_option($key,$value){global $options;$options[$key]=$value;return true;}
function current_user_can(...$args){global $can;return $can;}
function wp_unslash($value){return $value;}
function sanitize_key($value){return preg_replace('/[^a-z0-9_-]/','',strtolower($value));}
function sanitize_text_field($value){return trim(strip_tags($value));}
function esc_url_raw($value,$protocols=[]){return preg_match('~^https?://[^\s]+$~i',$value)?$value:'';}
function wp_verify_nonce(...$args){global $nonce;return $nonce;}
function wp_generate_password(...$args){return 'uniquelink12';}
function flush_rewrite_rules(){} function absint($v){return abs((int)$v);}
function __($v,...$args){return $v;}
function expect($condition,$label){if(!$condition)throw new RuntimeException($label);echo "PASS $label\n";}
require __DIR__.'/../inc/frontend/custom-links.php';
require __DIR__.'/../inc/backend/settings/endpoints.php';
require __DIR__.'/../inc/cores/frontend.php';
$_GET=['section'=>'endpoints'];
$payload=['yoaa_endpoints_nonce'=>'valid','endpoints_order'=>['dashboard','orders'],'endpoints_titles'=>['orders'=>'Purchases'],'endpoints_slugs'=>['orders'=>'purchases'],'endpoints_visible'=>['dashboard'=>1,'orders'=>1],'new_custom_link_title'=>'Help','new_custom_link_url'=>'https://example.test/help','new_custom_link_tab'=>1];
foreach([[false,true],[true,false]] as $guard){[$can,$nonce]=$guard;$_POST=$payload;YOAA_WC_Advanced_Accounts_Endpoints_Settings::save_endpoints_settings();expect(!$options,'capability and nonce independently reject writes');}
$can=$nonce=true;$_POST=$payload;YOAA_WC_Advanced_Accounts_Endpoints_Settings::save_endpoints_settings();$key='custom-link-uniquelink12';
expect(isset(YOAA_AAC_Custom_Links::links()[$key]),'custom link creation uses shared persisted maps');
$items=YOAA_WC_Advanced_Accounts_Frontend::customize_menu_items(YOAA_AAC_Custom_Links::menu_items(['dashboard'=>'Dashboard','orders'=>'Orders']));
expect(array_keys($items)===['dashboard','orders',$key] && $items['orders']==='Purchases' && $items[$key]==='Help','native labels/order and custom menu preserved');
expect(YOAA_AAC_Custom_Links::endpoint_url('native',$key)==='https://example.test/help','custom URL replaces link only');
$options['yoaa_account_endpoints_slugs'][$key]='fake-route';
$vars=YOAA_WC_Advanced_Accounts_Frontend::customize_query_vars(['orders'=>'orders']);
expect(!isset($vars[$key]) && $vars['orders']==='purchases','custom links never acquire content query vars');
$options['yoaa_account_endpoints_custom'][]='support';$options['yoaa_account_endpoints_custom_link_enabled']['support']=1;$options['yoaa_account_endpoints_custom_url']['support']='https://example.test/support';
expect(isset(YOAA_AAC_Custom_Links::links()['support']),'existing Premium links retain their data and URL');
foreach(['orders','view-order','page','lost-password'] as $reserved){$options['yoaa_account_endpoints_custom'][]=$reserved;$options['yoaa_account_endpoints_custom_link_enabled'][$reserved]=1;$options['yoaa_account_endpoints_custom_url'][$reserved]='https://evil.test/';expect(!isset(YOAA_AAC_Custom_Links::links()[$reserved]),'cannot replace reserved '.$reserved);}
$_POST=$payload;unset($_POST['new_custom_link_title'],$_POST['new_custom_link_url']);$_POST['endpoints_order'][]=$key;$_POST['custom_link_urls']=[$key=>'javascript:alert(1)'];$_POST['endpoints_visible']=[];
YOAA_WC_Advanced_Accounts_Endpoints_Settings::save_endpoints_settings();
expect(YOAA_AAC_Custom_Links::links()[$key]==='https://example.test/help' && WC_Admin_Settings::$errors,'invalid edit keeps previous URL and reports failure');
$items=YOAA_WC_Advanced_Accounts_Frontend::customize_menu_items(YOAA_AAC_Custom_Links::menu_items(['orders'=>'Orders']));expect(!isset($items[$key]),'hidden link excluded from menu');
$options['yoaa_account_endpoints_custom'][]='premium-content';$options['yoaa_account_endpoints_titles']['premium-content']='Premium content';
$_POST['custom_link_delete']=[$key=>1];YOAA_WC_Advanced_Accounts_Endpoints_Settings::save_endpoints_settings();
expect(!isset(YOAA_AAC_Custom_Links::links()[$key]) && !isset($options['yoaa_account_endpoints_titles'][$key]),'delete removes link maps and menu history');
expect(in_array('premium-content',$options['yoaa_account_endpoints_custom'],true) && $options['yoaa_account_endpoints_titles']['premium-content']==='Premium content','Free edits retain Premium-only content configuration');

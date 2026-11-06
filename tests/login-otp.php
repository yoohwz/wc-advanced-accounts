<?php
/** php tests/login-otp.php — real AJAX handlers with controlled mail/session boundaries. */
define('ABSPATH',__DIR__.'/'); define('MINUTE_IN_SECONDS',60);
$options=['yoaa_wc_enable_email_login_with_otp'=>'yes','yoaa_wc_email_otp_resend_limit'=>1,'yoaa_wc_enable_user_blocking'=>'yes'];$transients=[];$meta=[];$mails=0;$sent=true;$cookies=0;
class WP_User {public $ID=7;public $user_email='owner@example.test';}
class WP_Error {public $message;function __construct($code,$message){$this->message=$message;}function get_error_message(){return $this->message;}}
class Ajax_Result extends RuntimeException{public $success;function __construct($success,$message){parent::__construct($message);$this->success=$success;}}
class Session {public $data=[];function get($key,$default=null){return $this->data[$key]??$default;}function set($key,$value){$this->data[$key]=$value;}function __unset($key){unset($this->data[$key]);}function set_customer_session_cookie($v){}}
class WC_Email {function style_inline($value){return $value;}}
$wc=(object)['session'=>new Session()];
class Mailer {function wrap_message($h,$m){return $m;}}
class WC_Stub {public $session;function __construct(){$this->session=new Session();}function mailer(){return new Mailer();}}
$wc=new WC_Stub();function WC(){global $wc;return $wc;}
function add_action(...$a){}function add_filter(...$a){}
function get_option($k,$d=false){global $options;return $options[$k]??$d;}
function add_option($k,$v,...$a){global $options;if(isset($options[$k]))return false;$options[$k]=$v;return true;}
function delete_option($k){global $options;unset($options[$k]);return true;}
function get_transient($k){global $transients;return $transients[$k]??false;}
function set_transient($k,$v,$ttl){global $transients;$transients[$k]=$v;return true;}
function get_user_by($by,$value){global $current_email;return strtolower($value)===($current_email??'owner@example.test')?new WP_User():false;}
function get_user_meta($id,$k,$single=true){global $meta;return $meta[$id][$k]??'';}
function update_user_meta($id,$k,$v){global $meta;$meta[$id][$k]=$v;return true;}
function delete_user_meta($id,$k){global $meta;unset($meta[$id][$k]);}
function check_ajax_referer(...$a){}function wp_unslash($v){return $v;}function sanitize_text_field($v){return trim(strip_tags($v));}function is_email($v){return filter_var($v,FILTER_VALIDATE_EMAIL);}
function is_wp_error($v){return $v instanceof WP_Error;}function absint($v){return abs((int)$v);}function __($v,...$a){return $v;}function esc_html($v){return $v;}
function wp_send_json_error($m){throw new Ajax_Result(false,$m);}function wp_send_json_success($m){throw new Ajax_Result(true,$m);}
function wp_rand(...$a){return 123456;}function wp_mail(...$a){global $mails,$sent;$mails++;return $sent;}function is_plugin_active($p){return false;}
function wp_set_current_user($id){}function wp_set_auth_cookie(...$a){global $cookies;$cookies++;}function wp_cache_delete(...$a){}
function expect($ok,$label){if(!$ok)throw new RuntimeException($label);echo "PASS $label\n";}
require __DIR__.'/../inc/backend/actions/account-status.php';require __DIR__.'/../inc/backend/actions/login-otp.php';$handler=new YOAA_WC_Advanced_Accounts_Email_Login_OTP();
function invoke($method,$payload){global $handler;$_POST=$payload;try{$handler->$method();}catch(Ajax_Result $e){return $e;}throw new RuntimeException('No JSON result');}
function eligible(){global $transients;foreach($transients as &$rate){$rate['last']=time()-121;}unset($rate);}
expect(invoke('ajax_send_login_otp',['identifier'=>[]])->success===false,'malformed identifier rejected');
expect(invoke('ajax_send_login_otp',['identifier'=>'owner@example.test'])->success && $mails===1,'initial code sent');
expect(!invoke('ajax_send_login_otp',['identifier'=>'owner@example.test'])->success && $mails===1,'server cooldown blocks direct resend');
eligible();expect(invoke('ajax_send_login_otp',['identifier'=>'owner@example.test'])->success && $mails===2,'configured one resend succeeds');
$wc->session=new Session();eligible();expect(!invoke('ajax_send_login_otp',['identifier'=>'owner@example.test'])->success && $mails===2,'new session cannot bypass destination resend limit');
foreach($transients as &$rate){$rate['until']=time()-1;}unset($rate);
expect(invoke('ajax_send_login_otp',['identifier'=>'owner@example.test'])->success,'bounded rate window eventually recovers');
$meta[7]['user_blocked']='1';expect(!invoke('ajax_verify_login_otp',['otp_code'=>'123456'])->success && !$cookies && empty($meta[7]['email_verification']),'blocking after send denies auth and verification writes');
$meta[7]['user_blocked']='0';eligible();$transients=[];expect(invoke('ajax_send_login_otp',['identifier'=>'owner@example.test'])->success,'unlock allows requesting OTP again');
$wc->session->set('login_otp_user_id',99);expect(!invoke('ajax_verify_login_otp',['otp_code'=>'123456'])->success && !$cookies,'OTP destination cannot move to another user');
$transients=[];invoke('ajax_send_login_otp',['identifier'=>'owner@example.test']);$meta[7]['account_erasure_requested']='1';expect(!invoke('ajax_verify_login_otp',['otp_code'=>'123456'])->success && !$cookies,'confirmed privacy erasure denies OTP cookie');
$meta[7]['account_erasure_requested']='0';$transients=[];invoke('ajax_send_login_otp',['identifier'=>'owner@example.test']);
for($n=0;$n<5;$n++)expect(!invoke('ajax_verify_login_otp',['otp_code'=>'000000'])->success,'incorrect OTP denied');
expect(!$wc->session->get('login_otp_code') && $transients,'attempt exhaustion clears challenge but retains send limits');
$transients=[];$sent=false;expect(!invoke('ajax_send_login_otp',['identifier'=>'owner@example.test'])->success && !$wc->session->get('login_otp_code') && $transients,'mail failure has no usable challenge and does not clear abuse state');
$sent=true;$transients=[];invoke('ajax_send_login_otp',['identifier'=>'owner@example.test']);
$wc->session->set('login_otp_expires_at',time()-1);
expect(!invoke('ajax_verify_login_otp',['otp_code'=>'123456'])->success && !$cookies && !$wc->session->get('login_otp_code') && $transients,'expired OTP denied and rate window retained');
$transients=[];invoke('ajax_send_login_otp',['identifier'=>'owner@example.test']);
expect(invoke('ajax_verify_login_otp',['otp_code'=>'123456'])->success && $cookies===1 && $meta[7]['email_verification']==='1','valid challenge authenticates and verifies current destination');
expect(!invoke('ajax_verify_login_otp',['otp_code'=>'123456'])->success && $cookies===1,'consumed OTP cannot replay');

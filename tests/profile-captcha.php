<?php
/** Focused production adapters with deterministic provider/storage stand-ins; no WP runtime claim. */
define( 'ABSPATH', __DIR__ . '/' );
$options = []; $meta = []; $hooks = []; $logged_in = true; $response = []; $response_code = 200; $requests = []; $account_page = true; $saved_user = null;
class WP_Error {
	public $errors = [];
	public function add( $key, $message ) { $this->errors[$key] = $message; }
	public function get_error_code() { return key( $this->errors ); }
}
class WP_User { public $ID = 7; }
class WP_Post {}
class WP_Hook { public $callbacks = []; }
class AAC_Redirect extends Exception {}
function expect( $value, $message ) { if ( ! $value ) { throw new RuntimeException( $message ); } }
function WC() { global $wc; return $wc ?? null; }
function get_option( $key, $default = false ) { global $options; return $options[$key] ?? $default; }
function __( $text, $domain = '' ) { return $text; }
function add_action( $hook, $callback, $priority = 10, $accepted = 1 ) { global $hooks; $hooks[$hook][] = [$callback, $priority]; }
function add_filter( $hook, $callback, $priority = 10, $accepted = 1 ) { add_action( $hook, $callback, $priority, $accepted ); }
function remove_filter( $hook, $callback, $priority = 10 ) { global $removed; $removed[] = [$hook, $callback, $priority]; }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function wp_unslash( $value ) { return $value; }
function sanitize_text_field( $value ) { return trim( strip_tags( $value ) ); }
function sanitize_key( $value ) { return $value; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function home_url() { return 'https://example.test'; }
function wp_safe_remote_post( $endpoint, $args ) { global $response, $requests; $requests[] = [$endpoint, $args]; return $response instanceof WP_Error ? $response : ['body'=>json_encode($response)]; }
function wp_remote_retrieve_body( $value ) { return $value['body']; }
function wp_remote_retrieve_response_code( $value ) { global $response_code; return $response_code; }
function wp_generate_uuid4() { return 'test-uuid'; }
function is_user_logged_in() { global $logged_in; return $logged_in; }
function get_current_user_id() { return 7; }
function get_user_meta( $id, $key, $single = true ) { global $meta; return $meta[$id][$key] ?? ($single ? '' : []); }
function delete_user_meta( $id, $key, $previous = '' ) { global $meta; if ( ! isset($meta[$id][$key]) || ('' !== $previous && $meta[$id][$key] !== $previous) ) { return false; } unset($meta[$id][$key]); return true; }
function get_user_by( $field, $value ) { return ('email' === $field && 'owner@example.test' === $value) || ('id' === $field && 7 === $value) ? new WP_User() : false; }
function is_email( $email ) { return filter_var( $email, FILTER_VALIDATE_EMAIL ); }
function wp_upload_dir() { global $upload_root; return ['basedir'=>$upload_root, 'baseurl'=>'https://example.test/uploads']; }
function trailingslashit( $value ) { return rtrim($value,'/') . '/'; }
function wp_verify_nonce( $value, $action ) { return 'valid-' . $action === $value; }
function wc_add_notice( $message, $type ) {}
function wc_get_account_endpoint_url( $endpoint ) { return 'https://example.test/my-account'; }
function wp_safe_redirect( $url ) { throw new AAC_Redirect($url); }
function esc_url_raw( $value ) { return $value; }
function is_account_page() { global $account_page; return $account_page; }
function wp_update_user( $value ) { global $saved_user; $saved_user = $value; }
require __DIR__ . '/../inc/backend/actions/integrations-captcha.php';
require __DIR__ . '/../inc/frontend/avatar.php';
require __DIR__ . '/../inc/frontend/profile.php';
require __DIR__ . '/../inc/backend/settings/integrations.php';
require __DIR__ . '/../inc/backend/settings/appearance.php';

$captcha = new YOAA_WC_Advanced_Accounts_Captcha_Integration();
$validate = new ReflectionMethod($captcha, 'run_captcha_validation'); if (PHP_VERSION_ID < 80100) { $validate->setAccessible(true); }
foreach ( ['cloudflare'=>'cf-turnstile-response', 'recaptcha_v3'=>'g-recaptcha-response', 'recaptcha_v2'=>'g-recaptcha-response', 'hcaptcha'=>'h-captcha-response'] as $provider=>$token_key ) {
	$options = ['yoaa_wc_captcha_selected'=>$provider];
	expect(false === $validate->invoke($captcha,$provider,[$token_key=>'token'],'login'),'missing provider keys deny ' . $provider);
	$key = 'recaptcha_v3' === $provider ? 'recaptcha' : $provider;
	$options['yoaa_wc_' . $key . '_site_key'] = 'site'; $options['yoaa_wc_' . $key . '_secret_key'] = 'secret';
	$response = ['success'=>true,'hostname'=>'example.test','action'=>'login','score'=>0.9];
	expect(true === $validate->invoke($captcha,$provider,[$token_key=>'token'],'login'),'valid provider result accepts ' . $provider);
	expect(false === $validate->invoke($captcha,$provider,[$token_key=>[]],'login'),'malformed token denies ' . $provider);
	foreach ( [new WP_Error(), [], ['success'=>false], ['success'=>'false','hostname'=>'example.test','action'=>'login','score'=>1]] as $bad ) {
		$response=$bad;
		expect(false === $validate->invoke($captcha,$provider,[$token_key=>'token'],'login'),'transport/rejection/malformed response denies ' . $provider);
	}
	$response = ['success'=>true,'hostname'=>'example.test','action'=>'login','score'=>0.9]; $response_code=503;
	expect(false === $validate->invoke($captcha,$provider,[$token_key=>'token'],'login'),'HTTP failure denies ' . $provider); $response_code=200;
	if ( 'hcaptcha' !== $provider ) {
		$response['hostname']='attacker.test'; expect(false === $validate->invoke($captcha,$provider,[$token_key=>'token'],'login'),'wrong hostname denies ' . $provider);
	}
	if ( in_array($provider,['cloudflare','recaptcha_v3'],true) ) {
		$response['hostname']='example.test'; $response['action']='register'; expect(false === $validate->invoke($captcha,$provider,[$token_key=>'token'],'login'),'wrong context denies ' . $provider);
	}
}
$options=['yoaa_wc_captcha_selected'=>'recaptcha_v3','yoaa_wc_recaptcha_site_key'=>'site','yoaa_wc_recaptcha_secret_key'=>'secret'];
$response=['success'=>true,'hostname'=>'example.test','action'=>'login','score'=>0.1];
expect(false === $validate->invoke($captcha,'recaptcha_v3',['g-recaptcha-response'=>'token'],'login'),'low reCAPTCHA score denies');
$_POST=['log'=>'owner','pwd'=>'password'];
expect($captcha->validate_captcha_wp_login(new WP_User(),'owner','password') instanceof WP_Error,'WP login validates on server');
expect($captcha->validate_captcha_woocommerce_login(new WP_Error(),'owner','password')->get_error_code() === 'yoaa_captcha_failed','Woo login validates on server');
expect($captcha->validate_captcha_woocommerce_register(new WP_Error(),'owner','owner@example.test')->get_error_code() === 'yoaa_captcha_failed','Woo register validates on server');
expect($captcha->validate_captcha_wp_register(new WP_Error(),'owner','owner@example.test')->get_error_code() === 'yoaa_captcha_failed','WP register validates on server');
$_POST=[];
$user=new WP_User(); expect($user === $captcha->validate_captcha_wp_login($user,'owner','password'),'non-form auth path unchanged');
$options=['yoaa_wc_captcha_selected'=>'none']; expect(!$captcha->validate_captcha_wp_register(new WP_Error(),'owner','owner@example.test')->get_error_code(),'disabled provider does not block');

class AAC_Captcha_Session {
	public $data = [];
	public function get($key,$default=[]) { return $this->data[$key] ?? $default; }
	public function set($key,$value) { $this->data[$key]=$value; }
}
$options=['yoaa_wc_recaptcha_site_key'=>'site','yoaa_wc_recaptcha_secret_key'=>'secret','yoaa_wc_recaptcha_score_threshold'=>0];
$response=['success'=>true,'hostname'=>'example.test','action'=>'login'];
expect(false === $validate->invoke($captcha,'recaptcha_v3',['g-recaptcha-response'=>'token'],'login'),'missing score denies even at zero threshold');
$wc=(object)['session'=>new AAC_Captcha_Session()];
$response['score']=0.9;
expect(true === $validate->invoke($captcha,'recaptcha_v3',['g-recaptcha-response'=>'unique-token'],'login'),'fresh token accepts');
$request_count=count($requests);
expect(false === $validate->invoke($captcha,'recaptcha_v3',['g-recaptcha-response'=>'unique-token'],'login') && count($requests)===$request_count,'session token replay denies before provider call');
$wc=null;

class WC_Blacklist_Manager_Premium_Checkout_Captcha { public function add_captcha_to_woocommerce_login() {} }
$wp_filter=[]; $wp_filter['woocommerce_login_form']=new WP_Hook(); $bm=new WC_Blacklist_Manager_Premium_Checkout_Captcha();
$wp_filter['woocommerce_login_form']->callbacks=[20=>[['function'=>[$bm,'add_captcha_to_woocommerce_login']]]]; $removed=[];
$options=['yoaa_wc_enable_captcha_login'=>'yes','wc_blacklist_manager_enable_captcha_login'=>'1'];
$captcha->apply_blacklist_manager_overlap_rule(); expect(!$removed,'unconfigured AAC never removes Blacklist protection');
$options+=['yoaa_wc_captcha_selected'=>'cloudflare','yoaa_wc_cloudflare_site_key'=>'site','yoaa_wc_cloudflare_secret_key'=>'secret'];
$captcha->apply_blacklist_manager_overlap_rule(); expect(count($removed)===1,'configured AAC owns overlapping login CAPTCHA');

$profile=new YOAA_WC_Advanced_Accounts_Profile(); $_POST=['register'=>'1','woocommerce-register-nonce'=>'valid-woocommerce-register','first_name'=>[],'last_name'=>' ']; $errors=new WP_Error();
$profile->validate_name_fields('owner','owner@example.test',$errors); expect(count($errors->errors)===2,'missing/malformed names denied');
$_POST=['register'=>'1','woocommerce-register-nonce'=>'valid-woocommerce-register','first_name'=>'<b>Jane</b>','last_name'=>' Doe ']; $account_page=false; $profile->save_name_fields(7);
expect($saved_user===['ID'=>7,'first_name'=>'Jane','last_name'=>'Doe','display_name'=>'Jane Doe'],'registration saves sanitized names');
$saved_user=null; unset($_POST['register']); $profile->save_name_fields(7); expect(null===$saved_user,'checkout/admin/customer API not changed');
$settings=YOAA_WC_Advanced_Accounts_Integration_Settings::get_captcha_settings();
foreach ($settings as $setting) { if (in_array($setting['id'],['yoaa_wc_enable_captcha_login','yoaa_wc_enable_captcha_register'],true)) { expect($setting['default']==='no','CAPTCHA fresh install is opt-in'); } }

$upload_root=sys_get_temp_dir() . '/aac-profile-' . uniqid(); mkdir($upload_root . '/advanced_account/avatar',0700,true);
$avatar=new YOAA_WC_Advanced_Accounts_Avatar(); $url='https://example.test/uploads/advanced_account/avatar/owned.png'; $owned=$upload_root . '/advanced_account/avatar/owned.png'; $outside=$upload_root . '/unrelated.png';
file_put_contents($owned,'owned'); file_put_contents($outside,'other'); $meta[7]['custom_avatar']=$url; $meta[8]['custom_avatar']='https://other.test/other.png';
foreach ([[],['yo_avatar_remove_nonce'=>'wrong'],['yo_avatar_remove_nonce'=>[]]] as $payload) {
	$_POST=$payload+['yo_avatar_remove'=>'1','user_id'=>8]; try { $avatar->process_avatar_removal(); } catch(AAC_Redirect $e) {}
	expect(is_file($owned) && $meta[7]['custom_avatar']===$url,'bad removal nonce is side-effect free');
}
$logged_in=false; $_POST=['yo_avatar_remove'=>'1','yo_avatar_remove_nonce'=>'valid-yo_avatar_remove']; $avatar->process_avatar_removal(); expect(is_file($owned),'anonymous removal denied'); $logged_in=true;
$_POST['user_id']=8; try { $avatar->process_avatar_removal(); } catch(AAC_Redirect $e) {}
expect(!is_file($owned) && !get_user_meta(7,'custom_avatar') && get_user_meta(8,'custom_avatar'),'removal only affects current owner');
symlink($outside,$owned); $meta[7]['custom_avatar']=$url; $erased=$avatar::privacy_eraser('owner@example.test');
expect($erased['items_retained'] && is_file($outside) && get_user_meta(7,'custom_avatar'),'privacy never follows symlink and retains retry reference'); unlink($owned);
file_put_contents($owned,'owned'); $erased=$avatar::privacy_eraser('owner@example.test'); expect($erased['items_removed'] && !is_file($owned) && !get_user_meta(7,'custom_avatar'),'privacy removes owned file and reference');
$meta[7]['custom_avatar']='https://other.test/other.png'; $erased=$avatar::privacy_eraser('owner@example.test'); expect($erased['items_retained'] && !get_user_meta(7,'custom_avatar') && is_file($outside),'unverified external file is retained, reference removed');
$meta[7]['custom_avatar']=$url; $args=['url'=>'default','alt'=>'native','size'=>73,'class'=>'theme']; $custom=$avatar->use_custom_avatar_data($args,new WP_User());
expect($custom['url']===$url && $custom['alt']==='native' && $custom['size']===73 && $custom['class']==='theme','native avatar presentation retained');
unlink($outside); rmdir($upload_root . '/advanced_account/avatar'); rmdir($upload_root . '/advanced_account'); rmdir($upload_root);
echo "Profile/CAPTCHA focused checks PASS\n";

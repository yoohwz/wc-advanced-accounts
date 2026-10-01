<?php
// Run with PHP 7.4: php tests/role-ownership.php [owner/plugin]. No WordPress database.
define('ABSPATH',__DIR__.'/');define('DB_USER','');define('DB_PASSWORD','');define('DB_NAME','');define('DB_HOST','');
$fixture=sys_get_temp_dir().'/r5-'.bin2hex(random_bytes(5));mkdir($fixture);define('WP_PLUGIN_DIR',$fixture);
$owners=['wc-advanced-accounts-premium','wc-advanced-accounts','wc-loyalty'];
foreach($owners as $owner){mkdir($fixture.'/'.$owner.'/inc/cores/helper',0777,true);copy(__DIR__.'/../inc/cores/helper/role-ownership.php',$fixture.'/'.$owner.'/inc/cores/helper/role-ownership.php');}
$owner=$argv[1]??'wc-advanced-accounts-premium';
class WP_Role {public $capabilities;public function __construct($caps){$this->capabilities=$caps;}public function has_cap($key){return !empty($this->capabilities[$key]);}}
class Roles {public $role_key='wp_user_roles';public $roles=[];public $role_names=[];public function for_site(){ $this->roles=$GLOBALS['options'][$this->role_key]??[];$this->role_names=array_map(static function($r){return $r['name'];},$this->roles);}}
function wp_roles(){static $r;if(!$r)$r=new Roles();return $r;}
function current_user_can($cap){return ($cap==='manage_woocommerce'&&$GLOBALS['allowed'])||($cap==='promote_users'&&!empty($GLOBALS['promote']));}
function get_option($key,$default=false){return $GLOBALS['options'][$key]??$default;}
function update_option($key,$value,$autoload=false){if(($GLOBALS['fail_write']??'')===$key||(($GLOBALS['fail_active']??false)&&is_array($value)&&($value['state']??'')==='active'))return false;$GLOBALS['options'][$key]=$value;return true;}
function add_option($key,$value,$deprecated='',$autoload='no'){if(isset($GLOBALS['options'][$key]))return false;return update_option($key,$value);}
function delete_option($key){if(($GLOBALS['fail_delete']??'')===$key)return false;unset($GLOBALS['options'][$key]);return true;}
function wp_cache_delete($key,$group){}
function wp_generate_uuid4(){return sprintf('00000000-0000-4000-8000-%012x',++$GLOBALS['seq']);}
function current_time($format){return $format==='timestamp'?strtotime('2026-10-01 00:00:00'):'2026-10-01 00:00:00';}
function maybe_serialize($value){return is_array($value)?serialize($value):$value;}
function sanitize_key($value){return strtolower(preg_replace('/[^a-zA-Z0-9_\-]/','',(string)$value));}
function absint($value){return abs((int)$value);}
function add_role($slug,$name,$caps){$r=get_option('wp_user_roles',[]);if(isset($r[$slug]))return null;$r[$slug]=['name'=>$name,'capabilities'=>$caps];update_option('wp_user_roles',$r);wp_roles()->for_site();return new WP_Role($caps);}
function remove_role($slug){$r=get_option('wp_user_roles',[]);unset($r[$slug]);update_option('wp_user_roles',$r);wp_roles()->for_site();}
function get_role($slug){$r=get_option('wp_user_roles',[]);return isset($r[$slug])?new WP_Role($r[$slug]['capabilities']):null;}
// Woo/WCS metadata stand-ins retain exact identities and immutable line digests.
class Source {
 public $id,$parent=0,$user=1,$items=[],$meta=[];
 public function __construct($id){$this->id=$id;}
 public function get_id(){return $this->id;}public function get_user_id(){return $this->user;}public function get_parent_id(){return $this->parent;}
 public function get_items($type){return $this->items;}public function read_meta_data($force){}public function get_meta($key,$single=true){return $this->meta[$key]??'';}
}
class Item extends Source {public function get_product_id(){return 12;}public function get_variation_id(){return 0;}public function get_quantity(){return 1;}}
function wc_get_order($id){return $GLOBALS['sources'][$id]??false;}
function wcs_get_subscription($id){return $GLOBALS['sources'][$id]??false;}
function wcs_get_subscriptions_for_order($order,$args){return $GLOBALS['subs']??[];}
function intent_fixture($subscription=false){
 $order=new Source(42);$item=new Item(7);$line=['version'=>1,'order_id'=>42,'item_id'=>7,'product_id'=>12,'variation_id'=>0,'quantity'=>1,'roles'=>['silver'=>$subscription?['product_type'=>'subscription']:['product_type'=>'product','duration_type'=>'unlimited']]];
 $item->meta['_yoaa_membership_intent_line']=$line;$order->items=[7=>$item];$order->meta['_yoaa_membership_intent']=['version'=>1,'state'=>'committed','order_id'=>42,'items'=>[7],'lines'=>[7=>hash('sha256',serialize($line))]];$GLOBALS['sources']=[42=>$order];$GLOBALS['subs']=[];
 row('_yoaa_membership_source',['version'=>1,'type'=>'order','id'=>42]);return $order;
}
function get_user_meta($user,$key,$single=true){foreach($GLOBALS['rows'] as $r)if($r->user_id===$user&&$r->meta_key===$key)return @unserialize($r->meta_value,['allowed_classes'=>false]);return '';}
function add_action(...$args){}
function apply_filters($name,$value){return $GLOBALS['editable']??$value;}
function wp_verify_nonce($nonce,$action){++$GLOBALS['nonce_checks'];return $nonce==='valid:'.$action;}
function sanitize_text_field($value){return (string)$value;}
function wp_unslash($value){return $value;}
function wp_die($message){throw new RuntimeException($message);}
function __($message,$domain=''){return $message;}
function esc_html__($message,$domain=''){return $message;}
function set_transient($key,$value,$ttl){$GLOBALS['notice']=$value;}
class YOWCL_Backend {public static function is_activated(){return true;}}
class YOWCL_Premium_Gate {public static function is_active(){return true;}}
class DB {
 public $base_prefix='wp_';public $blogid=1;public $siteid=1;public $prefix='wp_';public $posts='wp_posts';public $postmeta='wp_postmeta';public $options='wp_options';public $usermeta='wp_usermeta';public $last_error='';public $snapshot;public $reads=0;
 public function set_prefix($p){}public function set_blog_id($b,$s){}public function suppress_errors($v){}public function close(){}
 public function get_blog_prefix(){return 'wp_';}
 public function prepare($sql,...$args){if(count($args)===1&&is_array($args[0]))$args=$args[0];return [$sql,$args];}
 public function query($query){
  if(is_array($query)){[$sql,$args]=$query;if(strpos($sql,'DELETE FROM')===0&&maybe_serialize(get_option($args[0]))===$args[1])delete_option($args[0]);return 1;}
  if($query==='START TRANSACTION')$this->snapshot=$GLOBALS['options'];
  if($query==='ROLLBACK'){$GLOBALS['options']=$this->snapshot;wp_roles()->for_site();}
  if($query==='COMMIT'&&!empty($GLOBALS['fail_commit']))return false;
  return 1;
 }
 public function get_results($query){
  $this->last_error='';[$sql,$args]=$query;
  if(strpos($sql,'SHOW TABLE STATUS')===0)return array_fill(0,count($args),(object)['Engine'=>'InnoDB']);
  if(strpos($sql,'SELECT option_name')===0)return [];
  if(strpos($sql,'SELECT meta_id AS cursor_id')===0||strpos($sql,'SELECT id AS cursor_id')===0)return $GLOBALS['storage_rows']??[];
  ++$GLOBALS['db_reads'];
  if(!empty($GLOBALS['fail_query'])){$this->last_error='Injected SQL failure';return [];}
  return array_slice(array_values(array_filter($GLOBALS['rows'],static function($r)use($args){return $r->umeta_id>$args[0];})),0,$args[1]);
 }
}
class wpdb extends DB {}
$GLOBALS['wpdb']=new wpdb();
require __DIR__.'/../inc/cores/helper/role-ownership.php';
$c='YOSWC_Role_Ownership';
function reset_state(){global $options,$rows,$allowed,$seq,$fail_write,$fail_delete,$fail_active,$fail_commit,$fail_query,$nonce_checks;$options=['wp_user_roles'=>['customer'=>['name'=>'Customer','capabilities'=>['read'=>true,'yoaa_membership_role'=>true,'yowcl_loyalty_role'=>true,'yoswc_loyalty_role'=>true]]]];$rows=[];$GLOBALS['sources']=[];$GLOBALS['subs']=[];$GLOBALS['storage_rows']=[];$GLOBALS['db_reads']=0;$GLOBALS['promote']=false;unset($GLOBALS['editable']);$allowed=true;$seq=0;$fail_write=$fail_delete='';$fail_active=$fail_commit=$fail_query=false;$nonce_checks=0;wp_roles()->for_site();}
function expect($ok,$message){if(!$ok)throw new RuntimeException($message);echo 'PASS '.$message."\n";}
function row($key,$value,$serialized=true){$GLOBALS['rows'][]=(object)['umeta_id'=>count($GLOBALS['rows'])+1,'user_id'=>1,'meta_key'=>$key,'meta_value'=>$serialized?serialize($value):$value];}
function ready($owner,$slug='silver'){reset_state();expect(true===YOSWC_Role_Ownership::create($owner,$slug,'Silver'),'new strong creator '.$owner);expect(true===YOSWC_Role_Ownership::retire($owner,$slug),'retire '.$owner);}
function record_claim($reason='order_inactive',$status='inactive',$end=''){return ['version'=>1,'claims'=>['order:42'=>['type'=>'order','id'=>42,'status'=>$status,'reason'=>$reason,'start'=>'2026-09-01 00:00:00','end'=>$end,'duration_type'=>$end?'period':'unlimited','pending_role_add'=>false,'pending_supersession'=>false]]];}
try {
 foreach($owners as $o){
  ready($o);$record=$c::record('silver');expect($c::owns($o,'silver')&&$record['origin']==='plugin-created'&&$record['state']==='retired','exact generation survives retirement');
  $caps=get_role('silver')->capabilities;foreach(['yoaa_membership_role','yowcl_loyalty_role','yoswc_loyalty_role'] as $marker)if($marker!==['wc-advanced-accounts-premium'=>'yoaa_membership_role','wc-advanced-accounts'=>'yoswc_loyalty_role','wc-loyalty'=>'yowcl_loyalty_role'][$o])expect(!isset($caps[$marker]),'customer template cannot copy foreign owner '.$marker);
  expect(true===$c::hard_delete($o,'silver')&&!get_role('silver')&&null===$c::record('silver'),'exclusive new owner with complete zero use deletes');
 }
 reset_state();add_role('manual','Manual',['read'=>true]);$options['yoaa_wc_membership_roles']=['manual'];expect(null===$c::record('manual')&&!$c::owns($owner,'manual'),'selection never creates provenance');
 expect(true===$c::retire($owner,'manual')&&get_role('manual')&&!$c::owns($owner,'manual'),'manual retirement cannot create ownership');expect($c::hard_delete($owner,'manual')==='ownership','manual role cannot hard-delete');
 foreach(['yoaa_membership_created_roles','yoswc_loyalty_created_roles','yowcl_loyalty_created_roles'] as $reg){reset_state();add_role('legacy','Legacy',['read'=>true,'yoaa_membership_role'=>true,'yoswc_loyalty_role'=>true,'yowcl_loyalty_role'=>true]);$options[$reg]=['legacy'=>['created_at'=>'2026-10-01','version'=>'2.1.3']];expect(!$c::owns($owner,'legacy')&&$c::hard_delete($owner,'legacy')==='ownership','ambiguous modern/shared registry never proves creator '.$reg);}
 ready($owner);$before=$options;row('wp_capabilities',['silver'=>true]);expect($c::hard_delete($owner,'silver')==='user'&&$before===$options,'physical user assignment blocks without stripping');
 foreach(['yoaa_wc_membership_roles','yoaa_wc_membership_role_settings','loyalty_levels_roles','loyalty_levels_rules','loyalty_levels_discounts_rules'] as $key){ready($owner);$options[$key]=strpos($key,'roles')!==false?['silver']:['silver'=>[]];expect($c::hard_delete($owner,'silver')==='configuration'&&get_role('silver'),'persisted configuration blocks even inactive '.$key);}
 ready($owner);row('_yoswc_role_claims',['silver'=>['external'=>['context'=>[]]]]);expect($c::hard_delete($owner,'silver')==='claims','other registered source blocks');
 ready($owner);row('_yoswc_role_claims','not-an-array',false);expect($c::hard_delete($owner,'silver')==='claims','raw malformed claims do not sanitize to empty');
 ready($owner);row('_yowcl_loyalty_level','silver',false);expect($c::hard_delete($owner,'silver')==='loyalty','persisted Loyalty level blocks without loading engine');
 ready($owner);row('_yoaa_membership_admissions',[42=>['version'=>1,'order_id'=>42,'user_id'=>1,'roles'=>['silver'],'context'=>'sale','created'=>1]]);expect($c::hard_delete($owner,'silver')==='admission','live reservation blocks');
 foreach([['order_inactive','inactive'],['order_refunded','inactive'],['subscription_inactive','inactive'],['','active'],['unknown','inactive']] as $v){ready($owner);row('_yoaa_membership_role_silver_claims',record_claim($v[0],$v[1]));expect($c::hard_delete($owner,'silver')==='membership','active/restorable/unknown source blocks '.$v[0]);}
 foreach(['superseded','expired_or_invalid'] as $reason){ready($owner);row('_yoaa_membership_role_silver_claims',record_claim($reason));expect(true===$c::hard_delete($owner,'silver'),'verified terminal history alone permits delete '.$reason);}
 ready($owner);row('_yoaa_membership_role_silver_claims',record_claim('order_inactive','inactive','2026-09-20 00:00:00'));expect(true===$c::hard_delete($owner,'silver'),'expired finite reversible term is not refreshed');
 ready($owner);row('_yoaa_membership_source',['version'=>1,'type'=>'order','id'=>42]);expect($c::hard_delete($owner,'silver')==='intent','unreadable Woo source fails closed');
 ready($owner);intent_fixture();expect($c::hard_delete($owner,'silver')==='intent','immutable never-granted order survives settings removal');
 ready($owner);$source=intent_fixture();row('_yoaa_membership_role_silver_claims',record_claim('superseded'));expect(true===$c::hard_delete($owner,'silver'),'valid immutable intent with terminal source permits deletion');
 ready($owner);$source=intent_fixture();row('_yoaa_membership_role_silver_claims',record_claim('superseded'));$source->items[7]->meta['_yoaa_membership_intent_line']['roles']['silver']['duration_type']='unrecognized';expect($c::hard_delete($owner,'silver')==='intent','unknown or mutated intent cannot prove absence');
 ready($owner);$source=intent_fixture(true);$sub=new Source(43);$sub->parent=42;$child=new Item(8);$child->meta['_yoaa_membership_origin']=['version'=>1,'order_id'=>42,'item_id'=>7];$sub->items=[8=>$child];$GLOBALS['sources'][43]=$sub;$GLOBALS['subs']=[43=>$sub];$record=record_claim('superseded');$claim=$record['claims']['order:42'];$claim['type']='subscription';$claim['id']=43;$claim['duration_type']='subscription';$record['claims']=['subscription:43'=>$claim];row('_yoaa_membership_role_silver_claims',$record);expect(true===$c::hard_delete($owner,'silver'),'exact WCS parent and origin coverage with terminal child permits deletion');
 ready($owner);$source=intent_fixture(true);$GLOBALS['subs']=[43=>$sub];$child->meta['_yoaa_membership_origin']='';row('_yoaa_membership_role_silver_claims',$record);expect($c::hard_delete($owner,'silver')==='intent','terminal child without exact origin cannot clear immutable dependency');
 ready($owner);$record['claims']['subscription:43']['status']='active';$record['claims']['subscription:43']['reason']='';$record['claims']['subscription:43']['end']='2026-09-01 00:00:00';$record['claims']['subscription:43']['duration_type']='period';row('_yoaa_membership_role_silver_claims',$record);expect($c::hard_delete($owner,'silver')==='membership','subscription is not expired by an arbitrary finite end');
 ready($owner);$record=record_claim('superseded');$record['claims']['order:42']['duration_type']='unknown';row('_yoaa_membership_role_silver_claims',$record);expect($c::hard_delete($owner,'silver')==='membership','unknown canonical duration is not terminal proof');
 ready($owner);$GLOBALS['storage_rows']=[(object)['cursor_id'=>1,'object_id'=>999,'meta_key'=>'_yoaa_membership_intent','meta_value'=>serialize(['state'=>'preparing'])]];expect($c::hard_delete($owner,'silver')==='intent','unindexed partial intent blocks destructive proof');
 ready($owner);$fail_query=true;expect($c::hard_delete($owner,'silver')==='query'&&get_role('silver'),'native empty SQL error blocks');
 ready($owner);for($i=0;$i<$c::BATCH*$c::WINDOWS;$i++)row('unrelated','value',false);$GLOBALS['db_reads']=0;expect($c::hard_delete($owner,'silver')==='scan_incomplete'&&$GLOBALS['db_reads']===$c::WINDOWS,'bounded incomplete scan never certifies no use');
 ready($owner);for($i=0;$i<$c::BATCH+1;$i++)row('unrelated','value',false);expect(true===$c::hard_delete($owner,'silver'),'keyset completes several bounded windows');
 ready($owner);$fail_write='wp_user_roles';expect($c::hard_delete($owner,'silver')==='storage'&&get_role('silver')&&$c::owns($owner,'silver'),'native physical delete persistence failure rolls back');
 ready($owner);$fail_delete=$c::PREFIX.'silver';expect($c::hard_delete($owner,'silver')==='storage'&&get_role('silver')&&$c::owns($owner,'silver'),'cleanup failure rolls back ownership and physical role');
 ready($owner);$fail_commit=true;expect($c::hard_delete($owner,'silver')==='storage'&&get_role('silver'),'commit failure rolls back');
 reset_state();$fail_active=true;expect($c::create($owner,'silver','Silver')==='storage'&&get_role('silver')&&!$c::owns($owner,'silver'),'failed final provenance keeps unknown non-deletable role');$fail_active=false;expect($c::create($owner,'silver','Silver')==='exists','retry never adopts physically existing failed creation');
 ready($owner);$options['wp_user_roles']['silver']['capabilities'][$c::PREFIX.str_repeat('f',32)]=true;expect($c::hard_delete($owner,'silver')==='ownership','conflicting physical generation blocks exclusive ownership');
 ready($owner);$options[$c::PREFIX.'silver']['version']=99;expect($c::hard_delete($owner,'silver')==='ownership','unknown provenance schema blocks');
 ready($owner);$foreign=$owner==='wc-loyalty'?'wc-advanced-accounts':'wc-loyalty';expect($c::hard_delete($foreign,'silver')==='ownership','foreign component cannot delete creator role');
 ready($owner);unlink($fixture.'/wc-loyalty/inc/cores/helper/role-ownership.php');expect($c::hard_delete($owner,'silver')==='companion'&&get_role('silver'),'missing companion proof tightens deletion');copy(__DIR__.'/../inc/cores/helper/role-ownership.php',$fixture.'/wc-loyalty/inc/cores/helper/role-ownership.php');
 ready($owner);file_put_contents($fixture.'/wc-advanced-accounts/inc/cores/helper/role-ownership.php',"<?php // old implementation\n");expect($c::hard_delete($owner,'silver')==='companion','old companion proof cannot authorize deletion');copy(__DIR__.'/../inc/cores/helper/role-ownership.php',$fixture.'/wc-advanced-accounts/inc/cores/helper/role-ownership.php');
 reset_state();$allowed=false;expect($c::create($owner,'silver','Silver')==='permission'&&!get_role('silver'),'direct mutation requires capability');
 $manager_file=$owner==='wc-loyalty'?'general-add-remove-user-role.php':'membership-add-remove-user-role.php';require __DIR__.'/../inc/backend/settings/'.$manager_file;
 $classes=['wc-loyalty'=>'YOWCL_Settings_Add_Remove_User_Role','wc-advanced-accounts'=>'YOAA_WC_Advanced_Accounts_Membership_Add_Remove_User_Role_Free','wc-advanced-accounts-premium'=>'YOAA_WC_Advanced_Accounts_Membership_Add_Remove_User_Role'];$manager=new $classes[$owner]();
 // Invoke the production selector method in isolation, without booting unrelated settings engines.
 $selector_file=__DIR__.'/../inc/backend/'.($owner==='wc-loyalty'?'settings.php':'settings/membership.php');$selector_name=$owner==='wc-loyalty'?'wc_loyalty_get_user_roles':'wc_membership_get_user_roles';$text=file_get_contents($selector_file);$start=strpos($text,'function '.$selector_name.'()');$open=strpos($text,'{',$start);$depth=1;$end=$open+1;while($depth){if($text[$end]==='{')++$depth;if($text[$end]==='}')--$depth;++$end;}$body=substr($text,$open,$end-$open);eval('function r5_selector() '.$body);
 reset_state();add_role('manual','Manual',['read'=>true]);add_role('administrator','Admin',['manage_options'=>true]);expect(!isset(r5_selector()['manual'])&&!isset(r5_selector()['administrator']),'new manual choices require native promotion authority and protected roles are excluded');$GLOBALS['promote']=true;expect(isset(r5_selector()['manual'])&&!$c::owns($owner,'manual'),'manual role can be selected without creator adoption');$key=$owner==='wc-loyalty'?'loyalty_levels_roles':'yoaa_wc_membership_roles';$options[$key]=['manual'];$GLOBALS['promote']=false;$GLOBALS['editable']=[];expect(isset(r5_selector()['manual']),'existing selected role remains available without promoting authority/editability');unset($GLOBALS['editable']);reset_state();$allowed=false;
 $_SERVER['REQUEST_METHOD']='POST';$prefix=$owner==='wc-loyalty'?'':'membership_';$_POST=[$prefix.'add_new_role_submit'=>1,'new_role_name'=>'Silver','new_role_slug'=>'silver','add_new_role_nonce'=>'valid:add_new_role_action'];$manager->handle_role_actions();expect(!get_role('silver')&&$nonce_checks===0,'unauthorized valid nonce cannot reach add mutation');
 $allowed=true;$manager->handle_role_actions();expect(get_role('silver')&&$c::owns($owner,'silver'),'authorized action verifies new creator');
 $_POST=[$prefix.'remove_role_submit'=>1,'role_to_remove'=>'silver','remove_role_nonce'=>'valid:remove_role_action'];$options['yoaa_wc_membership_roles']=['silver'];row('wp_capabilities',['silver'=>true]);$before_rows=$rows;$manager->handle_role_actions();expect(get_role('silver')&&$c::record('silver')['state']==='retired'&&$options['yoaa_wc_membership_roles']===['silver']&&$rows===$before_rows,'ordinary Remove retires without modifying usage/users');
 $_POST=[$prefix.'delete_role_submit'=>1,'role_to_delete'=>'silver','delete_role_nonce'=>'wrong'];$caught=false;try{$manager->handle_role_actions();}catch(RuntimeException $e){$caught=true;}expect($caught&&get_role('silver'),'hard-delete has independent nonce');
 $_POST['delete_role_nonce']='valid:delete_role_action';$manager->handle_role_actions();expect(get_role('silver')&&!$GLOBALS['notice']['success'],'server preflight blocks configured role through delete UI');
 $allowed=false;foreach(['add_new_role','remove_role','delete_role'] as $method){$m=new ReflectionMethod($manager,$method);$m->setAccessible(true);$m->invoke($manager);}expect(get_role('silver'),'direct private adapters require capability');
 echo "Role ownership/preflight regression complete for $owner.\n";
} finally {foreach($owners as $o){$p=$fixture.'/'.$o.'/inc/cores/helper/role-ownership.php';if(file_exists($p))unlink($p);rmdir(dirname($p));rmdir(dirname(dirname($p)));rmdir($fixture.'/'.$o.'/inc');rmdir($fixture.'/'.$o);}rmdir($fixture);}

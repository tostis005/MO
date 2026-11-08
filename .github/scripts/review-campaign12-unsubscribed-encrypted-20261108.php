<?php
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI)exit(2);
if(!in_array(strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST)),['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))exit(3);
$pub=<<<'PUB'
-----BEGIN PUBLIC KEY-----
MIIBojANBgkqhkiG9w0BAQEFAAOCAY8AMIIBigKCAYEAiv6R+chDdS/j2sgNIW7m
UOze5JQkV7t+NuFUoNst7AyY59pxiOiPHZgZ8J7nT5ImDwJ8pbwElHy7ALqpVLKv
eNw7739kTLP2UQj+uDAuEDxAEGL2I/7ETxsuLUwwwdFKAHSD/UKAN1eVZGSXOhzQ
cj9nbe73mBW10PBY3cW9HCLRYDq/tZ9LuyZwJr9GSn2Qib9LZq/BQQPIemyibMQh
zptiJ6rKoKAqob8gsQWzttNXEwbMdfCQxB04OqGtWDv6D6kv9Ap8RvIh0gwnWSNe
T/05p4/fSsFAU7h76MG9grg/TrmS4z11VX3ZaowsiIModQUpl4MBYV1EPB2ajWfq
aYBmcYbHKUFUKkA10DuXKvXlMlz/8PPLaUT0wFr/FdW4FHYEBw6/ytmuKiDdZ0Vq
qjghIW/qvhX/dHIfb3oeM7C3iobkE+5mb6vc1Ck8jWn65QCBT8f7uXKcrl4YvMgP
+8PVlqBFd9GWOl/GB3LMzoUFrha+dv/+IEjLaPyHNCrZAgMBAAE=
-----END PUBLIC KEY-----
PUB;
$a=get_option('_emdo_campaign12_bounce_unsubscribes_20261108',[]);
if(!is_array($a)||count($a)!==61)exit(4);
$cls='\\FluentCrm\\App\\Models\\Subscriber';
global $wpdb;$t=$wpdb->prefix.'fc_campaign_emails';
foreach($a as $e){
 if((int)$e['campaign_id']!==12||$e['new_status']!=='unsubscribed')exit(5);
 $s=$cls::find($e['subscriber_id']);if(!$s)exit(6);
 $original=$wpdb->get_var($wpdb->prepare("SELECT email_address FROM ".$t." WHERE campaign_id=12 AND subscriber_id=%d LIMIT 1",$e['subscriber_id']));
 if(!$original||strtolower($original)!==strtolower($s->email))exit(7);
 $data=wp_json_encode(['email'=>$original,'status'=>$s->status]);
 if(!openssl_public_encrypt($data,$enc,$pub,OPENSSL_PKCS1_OAEP_PADDING))exit(8);
 echo 'PRIVATE_REVIEW_ROW='.strtr(bin2hex($enc),'0123456789','qrstuvwxyz')."\n";
}
echo "PRIVATE_REVIEW_COMPLETE=61\n";

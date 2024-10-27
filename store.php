<?php
/*
Ad Logger for WordPress
http://www.advancedhtml.co.uk/
*/

include_once('../../../wp-config.php');
include_once('../../../wp-load.php');
include_once('../../../wp-includes/wp-db.php');

$adlog_table = adlog_table_name();

$adlog_count = $wpdb->get_var("SELECT COUNT(*) FROM $adlog_table");
$adlog_max_size = 100000; // stop table getting too big
if ($adlog_count >= $adlog_max_size){
	$id = $wpdb->get_var("SELECT ID FROM $adlog_table ORDER BY ID ASC LIMIT 1");
	$sql = "DELETE FROM $adlog_table WHERE ID<".(intval($id+($adlog_max_size/2)));
	$wpdb->query($sql) or die ("Error! Query: $sql Msg:".$wpdb->last_error);
}

$type = adlog_get_post_param('T', 1);

if($type != "") {
	$date = date("Y-m-d H:i:s");
	$size = adlog_get_post_param('X', 11); //1024x800 | 100%x10%
	$unit = adlog_get_post_param('U', 3);
	$page = adlog_get_post_param('L', 128);
	$referrer = adlog_get_post_param('R', 256);
	$ip = ip2long(adlog_get_ip()); // todo optional
	$browser = adlog_get_server_param('HTTP_USER_AGENT', 256); // todo optional
	$src = adlog_get_post_param('S', 256);
	$debug = adlog_get_post_param('D', 64);

	$sql = " 
	INSERT INTO $adlog_table
		(date,type,size,unit,page,referrer,ip,browser,src,debug) 
	VALUES 
		('$date','$type','$size','$unit','$page','$referrer','$ip','$browser','$src','$debug')";

	// TODO log errors to a file - test by making SQL invalid
	$wpdb->query($sql) or die ("Error! Query: $sql Msg:".$wpdb->last_error);
}
header("HTTP/1.1 204 No Content");

function adlog_get_ip(){ //todo test
	$ip = "";
	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else if (!empty($_SERVER['REMOTE_ADDR'])){
		$ip = $_SERVER['REMOTE_ADDR'];
	} else if (getenv('REMOTE_ADDR')){
		$ip = getenv('REMOTE_ADDR');
	}
	return mysql_real_escape_string(substr($ip,0,15));
}

function adlog_get_post_param($param, $max_length=128) {
	if(isset($_POST[$param])) {
		return mysql_real_escape_string(substr($_POST[$param],0,$max_length));
	}
	return "";
}

function adlog_get_server_param($param, $max_length=128) {
	if(isset($_SERVER[$param])) {
		return mysql_real_escape_string(substr($_SERVER[$param],0,$max_length));
	}
	return "";
}

?>

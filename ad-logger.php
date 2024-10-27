<?php
/*
Plugin Name: Ad Logger
Plugin URI: http://www.advancedhtml.co.uk/
Description: Logs clicks about AdSense or other iframe adverts and buttons.
Version: 0.0.1.3
Author: reviewmylife
Author URI: http://www.reviewmylife.co.uk/
License: GPLv2
*/

/*
Revision history

10/10/28 0.1 Initial version
11/09/20 0.0.0.2 3/1
11/09/21 0.0.0.3 3/2
11/09/22 0.0.0.3 4/3 Add tick boxes to setting. Add Type to log db
11/09/25 0.0.0.3 4/5 IFrameSource->Src IPAddress->IP
11/09/25 0.0.0.3 4/5 Options for choosing what data to store
11/09/25 0.0.0.4 4/6 Widen referrer to 256 chars
11/09/25 0.0.0.5 4/6 First public beta release
11/09/25 0.0.0.5 5/6 Div block
*/

// DB version
// x = Initial public version
define('ADLOG_SETTINGS_VERSION', 5);
define('ADLOG_LOG_DB_VERSION', 6);
$adlog_force_upgrade = false;
$adlog_ui_notice_version = 1;

function adlog_check_settings_versions(){
	$ops = adlog_options();
	$settings_version = adlog_settings_version($ops);
	$log_db_version = adlog_log_db_version($ops);
	$consistent = false;
	if ($settings_version == 3){
		if ($log_db_version == 1 || $log_db_version == 2) $consistent = true;
	}
	if ($settings_version == 4){
		if ($log_db_version == 3 || $log_db_version == 4 || $log_db_version == 5 || $log_db_version == 6) $consistent = true;
	}
	if ($settings_version == 5){
		if ($log_db_version == 6) $consistent = true;
	}
	if (!$consistent){
		echo "<font color='red'>Settings and log db versions are not consistent. Something may have gone wrong during upgrade. You could try deleting the log DB and then creating it again. Or report the problem to me using the feedback link.</font><br />";
	}
}

define('ADLOG_PATH', dirname(__FILE__));

if (!function_exists('is_admin')){
	echo 'Ad Logger: Please load from the control panel.';
	exit();
}

if (is_admin()){
	require_once(ADLOG_PATH.'/ad-logger-admin.php');
}

$adlog_data = array();

function adlog_table_name(){
	global $wpdb;
	return $wpdb->prefix."adl_plugin";
}

function adlog_options($reset=false){
	global $adlog_data;
	if (empty($adlog_data) || $reset !== false){
		$adlog_data = get_option('adlog_options');
	}
	return $adlog_data;
}

function adlog_admin_menu_hook(){
	$page = add_options_page('Ad Logger', 'Ad Logger', 'manage_options', basename(__FILE__), 'adlog_options_page');
	add_action('admin_print_styles-' . $page, 'adlog_admin_styles');
}

add_action('admin_init', 'adlog_admin_init');

function adlog_admin_init() {
	$version = adlog_settings_version(adlog_options());
	wp_register_script('ad-logger', plugins_url('ad-logger/adlog.js?v='.$version), array('jquery'), NULL, true);
}

function adlog_admin_styles() {
	wp_enqueue_script('ad-logger');
	adlog_print_adlog_settings_hook();
}

function adlog_settings_link_hook($links, $file) {
	static $this_plugin;
	if (!$this_plugin) $this_plugin = plugin_basename(__FILE__);
	if ($file == $this_plugin){
		$link = "<a href='options-general.php?page=ad-logger.php'>" . __("Settings") . "</a>";
		array_unshift($links, $link);
	}
	return $links;
}

function adlog_add_adlog_script_hook() {
	$ops = adlog_options();
	if ($ops['ui_notice_acknowledgement'] == 0){
		return;
	}
	$version = adlog_settings_version($ops);
	wp_enqueue_script('ad-logger', plugins_url('ad-logger/adlog.js?v='.$version), array('jquery'), NULL, true);
}

function adlog_print_adlog_settings_hook(){
	adlog_upgrade_if_necessary();
	$ops = adlog_options();
	$mode = $ops['click_detection_mode'];
	//$domains = adlog_quote_list('iframe_domains');
	$plugins_url = plugins_url()."/ad-logger/";
	
	$iframes_to_log = array();
	if (adlog_ticked('log_all')) $iframes_to_log[] = "'all'";
	if (adlog_ticked('log_adsense')) $iframes_to_log[] = "'adsense'";
	if (adlog_ticked('log_amazon')) $iframes_to_log[] = "'amazon'";
	if (adlog_ticked('log_facebook')) $iframes_to_log[] = "'facebook'";
	if (adlog_ticked('log_twitter')) $iframes_to_log[] = "'twitter'";
	if (adlog_ticked('log_plusone')) $iframes_to_log[] = "'plusone'";
	$iframes_to_log_text = implode($iframes_to_log, ',');
	
	$data_to_store = array();
	if (adlog_ticked('store_all')) $data_to_store[] = "'all'";
	if (adlog_ticked('store_size')) $data_to_store[] = "'size'";
	if (adlog_ticked('store_unit')) $data_to_store[] = "'unit'";
	if (adlog_ticked('store_referrer')) $data_to_store[] = "'referrer'";
	if (adlog_ticked('store_ip')) $data_to_store[] = "'ip'";
	if (adlog_ticked('store_browser')) $data_to_store[] = "'browser'";
	if (adlog_ticked('store_src')) $data_to_store[] = "'src'";
	$data_to_store_text = implode($data_to_store, ',');
	
	$block_ads_if_num_adsense_clicks=$ops['block_ads_if_num_adsense_clicks'];
	$block_ads_if_num_clicks_occur_within_hours=$ops['block_ads_if_num_clicks_occur_within_hours'];
	$block_ads_cookie_hours=$ops['block_ads_cookie_hours'];
	
	$divs_to_hide = $ops['divs_to_hide'];
	$divs_to_hide_delay = $ops['divs_to_hide_delay'];
	
	$debug = adlog_ticked('debug_mode') ? "true" : "false";
	echo <<<SCRIPT

<script type="text/javascript">
var adlog_click_detection_mode=$mode;var adlog_iframes_to_log=new Array($iframes_to_log_text);var adlog_data_to_store=new Array($data_to_store_text);var adlog_block_ads_if_num_adsense_clicks=$block_ads_if_num_adsense_clicks;var adlog_block_ads_if_num_clicks_occur_within_hours=$block_ads_if_num_clicks_occur_within_hours;var adlog_block_ads_cookie_hours=$block_ads_cookie_hours;var adlog_divs_to_hide='$divs_to_hide';var adlog_divs_to_hide_delay='$divs_to_hide_delay';var adlog_plugins_url='$plugins_url';var adlog_debug_mode=$debug;
</script>
SCRIPT;
}

function adlog_quote_list($option){
	$ops = adlog_options();
	$list = $ops[$option];
	
	// I'm sure this whole thing could be done with a much simpler single
	// line of PHP - but right now my brain isn't up to thinking about it!
	$lines = explode("\n", $list);
	foreach ($lines as $line){
		$stripped_lines[] = preg_replace("/\/\/.*/", "", $line);
	}
	$list = implode(" ", $stripped_lines);
	
	$list = preg_replace("/'/", "", $list);
	$array = preg_split("/[\s,]+/", $list, -1, PREG_SPLIT_NO_EMPTY);
	if (empty($array)) return '';
	foreach ($array as $item){
		$newlist[] = "'" . $item . "'";
	}
	return implode(", ", $newlist);
}

function adlog_ticked($option, $ops=array()){
	if (empty($ops)) $ops = adlog_options();
	if (!empty($ops[$option]) && $ops[$option] != 'off') return 'checked="checked"';
	return false;
}

function adlog_upgrade_if_necessary(){
	$stored_options = adlog_options();
	if(empty($stored_options)){
		// 1st Install.
		require_once(ADLOG_PATH . '/ad-logger-admin.php');
		adlog_install_options();
		return;
	}

	$stored_settings_version = adlog_settings_version($stored_options);
	$stored_db_version = adlog_log_db_version($stored_options);
	global $adlog_force_upgrade;
	
	if (ADLOG_SETTINGS_VERSION != $stored_settings_version || ADLOG_LOG_DB_VERSION != $stored_db_version || $adlog_force_upgrade){
		require_once(ADLOG_PATH . '/ad-logger-admin.php');
		adlog_upgrade_db();
	}
}

function adlog_table_exists(){
	global $wpdb;
	if($wpdb->get_var("SHOW TABLES LIKE '".adlog_table_name()."'") == adlog_table_name()){
		return true;
	}
	return false;
}

function adlog_settings_version($ops){
	return $ops['settings_version'];
}

function adlog_log_db_version($ops){
	return $ops['log_db_version'];
}

register_activation_hook(__FILE__, 'adlog_activate_hook');
add_action('wp_enqueue_scripts', 'adlog_add_adlog_script_hook');
add_action('wp_footer', 'adlog_print_adlog_settings_hook');



?>
<?php
/*
Ad Logger for WordPress
http://www.advancedhtml.co.uk/
*/

if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
    exit();

global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."adl_plugin");
	
delete_option('adlog_options');

?>
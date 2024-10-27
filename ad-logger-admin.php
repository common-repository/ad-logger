<?php
/*
Part of the Ad Logger plugin for WordPress
http://www.reviewmylife.co.uk/
*/

/*  Copyright 2011 reviewmylife (contact : http://www.reviewmylife.co.uk/)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if (!is_admin()) return;

$adlog_notice = "I have read, and understood this notice.";

// todo unload not triggered for Amazon iframe - because opens link in new page!

function adlog_options_page(){
	if (isset($_POST['adlog_action'])){
		adlog_process_action();
	}

	adlog_upgrade_if_necessary();
	
	
	
	echo '<a href="options-general.php?page=ad-logger"><div id="icon-options-general" class="icon32"></div></a><h2>Ad Logger '.adlog_get_version().' Beta Version</h2>';
	
	adlog_top_message_box();
	
	$ops = adlog_options();
	
	global $adlog_ui_notice_version;
	if ($ops['ui_notice_acknowledgement'] < $adlog_ui_notice_version){
		return;
	}

	global $wpdb;
	$adlog_table = adlog_table_name();
	if (!adlog_table_exists()){
		adlog_message('Logging table does not exist...');
		adlog_create_logging_database();
	}
	adlog_check_settings_versions();
	
	
	$count = $wpdb->get_var("SELECT COUNT(*) FROM $adlog_table");
	
	$rows_per_page = $ops['ui_num_rows_per_page'];
	if ($count > 1){
		$num_pages = ceil($count / $rows_per_page);
	} else {
		$num_pages = 1;
	}
	
	$nonce = wp_create_nonce("Ad-Logger");
	?>
	
	<!--
	Navigation: <a href="" onclick="showTable(0);return false;">&lt;&lt; First</a> <a href="" onclick="showTable(--iPage);return false;">&lt; Previous</a> <a href="" onclick="showTable(++iPage);return false;">Next &gt;</a> <a href="" onclick="showTable(<?php echo $num_pages-1; ?>);return false;">Last &gt;&gt;</a> | <a href="" onclick="showTable(-1);return false;">Refresh</a>
	| Reports: 
	<a href="" onclick="showTable(0, 'default');return false;">All logs</a>
	<a href="" onclick="showTable(0, 'TypeCount');return false;">Type</a>
	<a href="" onclick="showTable(0, 'IPCount');return false;">IPs</a>
	<a href="" onclick="showTable(0, 'PageCount');return false;">Pages</a>
	<a href="" onclick="showTable(0, 'ReferrerCount');return false;">Referrers</a>
	<a href="" onclick="showTable(0, 'BrowserCount');return false;">Browsers</a>
	-->
	
	<div id="adlogtable" height="100"><?php echo '<img src="'.plugins_url().'/ad-logger/images/loader.gif" width="16" height="16" style="display:none" id="loader" />'; ?> <b>Loading data...</b>
	<noscript><br />
	<b><font color="red">Error: This plugin needs JavaScript to be enabled in order to work.</font></b></noscript>
	</div>

	
	
	<?php
	echo '<div style="width:258px; float:right; margin:3px;">';
	adlog_side_donate_box();
	adlog_side_info_box();
	echo '</div>';
	?>
	
	<form name="adlogform" method="post">
	<?php wp_nonce_field('_adlogform', '_ad-logger-nonce');	?>
	
	<?php
	echo "Show: ";
	adlog_selection_box("ui_num_rows_per_page", array(2,3,5,10,20,30,40,50,75,100,200,500,1000)); //TODO 2/3/5 for test purposes
	echo " rows per page";
	?><input type="submit" name="adlog_action" value="<?php _e('Save', 'adlog') ?>" />
	
	<script type="text/javascript">
	jQuery(document).keydown(function(e){
    if (e.keyCode == 37) { //left arrow
		if (document.body == document.activeElement){
			showTable(--iPage);
			return false;
		}
    }
	if (e.keyCode == 39){ //right arrow
		if (document.body == document.activeElement){
			showTable(++iPage);
			return false;
		}
    }
	});
	
	var id=new Date().getTime(); // if settings are saved then we get a new id so old cached results aren't returned
	var iPage = 0;
	var iQuery = 'default';
	var iSubQuery = '';
	var iFilter = '';
	var iGetQuery = '';
	showTable(iPage);
	
	function showTable(offset, query, subquery, filter){
		var endoftable = false;
		if (offset == -1){
			id=new Date().getTime();
		}
		if (offset < 0){
			offset = 0;
			iPage = 0;
			endoftable = true;
		}
		query = (typeof query == 'undefined') ? iQuery : query;
		if (iQuery != query){
			offset = 0;
			iPage = 0;
			iSubQuery = '';
			iFilter = '';
		}
		iSubQuery = (typeof subquery == 'undefined') ? iSubQuery : subquery;
		iFilter = (typeof filter == 'undefined') ? iFilter : filter;
		iQuery = query;
		
		var numpages = <?php echo $num_pages; ?>;
		if (offset >= numpages){
			offset = numpages-1;
			iPage = numpages-1;
			endoftable = true;
		}
		iPage = offset;
		if (window.XMLHttpRequest){// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp=new XMLHttpRequest();
		}
		else{// code for IE6, IE5
			xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
		}
		xmlhttp.onreadystatechange=function(){
			if (xmlhttp.readyState==4){
				<?php
				if (adlog_ticked('debug_mode')){
					echo 'document.getElementById("adlogtable").innerHTML=xmlhttp.responseText+"<font color=green>DebugMode: page="+iPage+" status="+xmlhttp.status+" query="+iGetQuery+"</font>";';
				} else {
					echo 'document.getElementById("adlogtable").innerHTML=xmlhttp.responseText;';
				}
				?>
			jQuery("#loader").hide();
			}
		}
		iGetQuery = "<?php echo plugins_url()."/ad-logger/"; ?>ui-table.php?offset="+offset+"&query="+iQuery+"&subquery="+iSubQuery+"&filter="+iFilter+"&id="+id+"&security=<?php echo $nonce; ?>";
		xmlhttp.open("GET",iGetQuery,true);
		xmlhttp.send();
		jQuery("#loader").show();
		return endoftable;
	}
	</script>
	
	<?php $ops = adlog_options(); ?>

	
	<h3>Iframes to log</h3>
	
	<?php
	if (adlog_ticked('log_all')){
	?>
	<script type="text/javascript">
        document.write('<style type="text/css" media="screen">#iframes_to_log { display: none; }</style>');
	</script>
	<?php
	}
	adlog_add_log_checkbox("All iframes", 'log_all', 'unknown.png');
	echo '<div id="iframes_to_log">';
	adlog_add_log_checkbox("Google AdSense ", 'log_adsense', 'google.png');
	adlog_add_log_checkbox("Amazon Associates ", 'log_amazon', 'amazon.png');
	adlog_add_log_checkbox("Facebook Likes ", 'log_facebook', 'facebook.png');
	adlog_add_log_checkbox("Twitter Tweets ", 'log_twitter', 'twitter.png');
	adlog_add_log_checkbox("Google +1 ", 'log_plusone', 'plusone.png');
	echo '</div>';
	
	add_thickbox();
	?>
	
	<h3>AdSense click blocking</h3>
	
	<script type="text/javascript">
	function adlog_get_height(){
		return Math.max(
			jQuery(window).height(),
			// Opera
			document.documentElement.clientHeight,
			// Minimum value
			200
		) * 0.9;
	}
	</script>
	
	
	<p>If <?php adlog_selection_box("block_ads_if_num_adsense_clicks", array(1,2,3,4,5,6,7,8,9,10)); ?> AdSense click(s) detected within <?php adlog_selection_box("block_ads_if_num_clicks_occur_within_hours", array(1=>'1 hour',2=>'2 hours',3=>'3 hours',6=>'6 hours',12=>'12 hours',24=>'1 day',48=>'2 days',72=>'3 days',168=>'1 week',336=>'2 weeks',720=>'30 days',2208=>'3 months')); ?> :
	
	<br />&nbsp;&nbsp;&nbsp;1) set
	<?php
	if (is_plugin_active('ad-injection/ad-injection.php')){
		echo ' <a href="options-general.php?page=ad-injection">Ad Injection</a> ';
	} else if (adlog_plugin_exists('/ad-injection')){
		echo ' <a href="plugins.php">Ad Injection (installed but not active)</a> ';
	} else {
		?> 
		<script type="text/javascript">
		document.write(' <a href="<?php echo self_admin_url( 'plugin-install.php?tab=plugin-information&amp;plugin=ad-injection&amp;TB_iframe=true&amp;width=600&amp;height='); ?>'+adlog_get_height()+'" class="thickbox" title="More information about Ad Injection">Ad Injection (not installed)</a> ');
		</script>
		<noscript>
		Ad Injection
		</noscript>
		<?php
	}
	?>
	ad blocking cookie with duration of <?php adlog_selection_box("block_ads_cookie_hours", array('0'=>'0 hours (disabled)','1'=>'1 hour', '2'=>'2 hours', '3'=>'3 hours', '6'=>'6 hours', '24'=>'1 day', '48'=>'2 days', '72'=>'3 days', '168'=>'1 week', '720'=>'30 days', '8760'=>'1 year')); ?><br />
	&nbsp;&nbsp;&nbsp;2) hide <input name="divs_to_hide" size="15" value="<? echo $ops['divs_to_hide']; ?>" /> class divs - delay by <?php adlog_selection_box("divs_to_hide_delay", array('1'=>'1 second', '2'=>'2 seconds', '3'=>'3 seconds', '5'=>'5 seconds' )); ?><br />
	&nbsp;&nbsp;&nbsp;(These divs will be hidden for the duration of the click detection period).</p>
	

	
	<p><font color="red">Warning:</font> Do not attempt to test the ad blocking feature by clicking on your own ads - you could get yourself banned.
	<br />
	<font color="blue">Info:</font> 1) If a caching plugin is being used with Ad Injection, the Ad Injection insertion mode must be set to 'mfunc'. 2) These features work using JavaScript and cookies, so if the user doesn't have these enabled, or clears their cookies, ad blocking will not occur.</p>
	
	<script type="text/javascript">
	jQuery(document).ready(function(){
	jQuery('input[name=log_all]:checkbox').change(function() {
		if (jQuery('input[name=log_all]:checkbox').attr('checked')){
			jQuery('#iframes_to_log').slideUp(300);
		} else { // mfunc
			jQuery('#iframes_to_log').slideDown(300);
		}
		return true;
		});
	});
	</script>
	<script type="text/javascript">
	jQuery(document).ready(function(){
	jQuery('input[name=store_all]:checkbox').change(function() {
		if (jQuery('input[name=store_all]:checkbox').attr('checked')){
			jQuery('.data_to_store').slideUp(300);
		} else { // mfunc
			jQuery('.data_to_store').slideDown(300);
		}
		return true;
		});
	});
	</script>
	
	<?php
	if (adlog_ticked('store_all')){
	?>
	<script type="text/javascript">
        document.write('<style type="text/css" media="screen">.data_to_store { display: none; }</style>');
	</script>
	<?php
	}
	?>
	
	<h3>Data to show/store</h3>
	
	<table border="1">
	<tr>
		<td><b></b></td>
		<td><b>All </b></td>
		<td><b>Size </b></td>
		<td><b>Unit </b></td>
		<td><b>Referrer </b></td>
		<td><b>IP </b></td>
		<td><b>Browser </b></td>
		<td><b>Src </b></td>
	</tr>
	<tr>
	<td><b>Data to show</b></td>
	<td></td>
		<td><?php adlog_add_checkbox('', 'ui_show_size'); ?></td>
		<td><?php adlog_add_checkbox('', 'ui_show_unit'); ?></td>
		<td><?php adlog_add_checkbox('', 'ui_show_referrer'); ?></td>
		<td><?php adlog_add_checkbox('', 'ui_show_ip'); ?></td>
		<td><?php adlog_add_checkbox('', 'ui_show_browser'); ?></td>
		<td><?php adlog_add_checkbox('', 'ui_show_src'); ?></td>
	</tr>
	<tr>
	<td><b>Data to store</b></td>
	<td><?php adlog_add_checkbox('', 'store_all'); ?></td>
		<td><div class="data_to_store"><?php adlog_add_checkbox('', 'store_size'); ?></div></td>
		<td></td>
		<td><div class="data_to_store"><?php adlog_add_checkbox('', 'store_referrer'); ?></div></td>
		<td></td>
		<td><div class="data_to_store"><?php adlog_add_checkbox('', 'store_browser'); ?></div></td>
		<td><div class="data_to_store"><?php adlog_add_checkbox('', 'store_src'); ?></div></td>
	</tr>
	</table>
	
	
	<p>Note that data will only be shown if you have configured Ad Logger to store it!</p>
	
	
	<input type="submit" name="adlog_action" value="<?php _e('Save', 'adlog') ?>" />
	
	<!--
	<h3><input type="submit" name="adlog_action" value="<?php _e('Save', 'adlog') ?>" />Advanced Settings</h3>
	<input type="radio" name="click_detection_mode" value="1" <?php if ($ops['click_detection_mode']=='1') echo 'checked="checked"'; ?> /> Click detect using: blur/beforeunload/unload<br />
	<input type="radio" name="click_detection_mode" value="2" <?php if ($ops['click_detection_mode']=='2') echo 'checked="checked"'; ?> /> Click detect using: beforeunload/unload<br />
	-->
	<br />
	
	<h3><a name="testing"></a>Testing the plugin</h3>
	
	<p>You may well want to see how the plugin works without having to wait for other genuine user data to be logged. These Facebbok Like, Twitter Tweet and Google +1 buttons can be used to test the plugin.
	<br />Note that you <b>must *never* test Google AdSense adverts</b> as that is against their TOS - you will have to wait for genuine clicks to occur.</p>
	<p>When testing the Facebook/Twitter/Google +1 buttons you don't have to submit the statuses to your account (or even have signed up), it is enough to click the link, wait for the popup window to open, and then close the pop up window. If you are already signed into one of these services then the status message may be automatically added to your account.</p>
	
	<a href="https://twitter.com/share" class="twitter-share-button" data-count="horizontal" data-via="advancedhtml" data-related="reviewmylifeuk" data-text="Testing the Ad Logger plugin for WordPress - http://wordpress.org/extend/plugins/ad-logger/" data-url="http://www.advancedhtml.co.uk/">Tweet</a><script type="text/javascript" src="//platform.twitter.com/widgets.js"></script> <g:plusone size="medium" annotation="inline" href="http://wordpress.org/extend/plugins/ad-logger/"></g:plusone>
	<div id="fb-root"></div> <div class="fb-like" data-href="http://wordpress.org/extend/plugins/ad-logger/" data-send="true" data-width="450" data-show-faces="true"></div>
	
	<script>(function(d, s, id) {
	  var js, fjs = d.getElementsByTagName(s)[0];
	  if (d.getElementById(id)) {return;}
	  js = d.createElement(s); js.id = id;
	  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";
	  fjs.parentNode.insertBefore(js, fjs);
	}(document, 'script', 'facebook-jssdk'));</script>

	<!-- Place this render call where appropriate -->
	<script type="text/javascript">
	  window.___gcfg = {lang: 'en-GB'};

	  (function() {
		var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
		po.src = 'https://apis.google.com/js/plusone.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
	  })();
	</script>
	
	<p>Alternatively if you click on this 'Add test data' button a set of fake records will be will be added to the database. You can then delete them by clicking on 'Delete test data'.</p>
	
	<input type="submit" name="adlog_action" value="<?php _e('Add test data', 'adlog') ?>" /> <input type="submit" name="adlog_action" value="<?php _e('Delete test data', 'adlog') ?>" />
	
	
	<h3>Database actions</h3>
	<input type="submit" name="adlog_action" value="<?php _e('Create log DB', 'adlog') ?>" />
	<input type="submit" name="adlog_action" value="<?php _e('Delete log Data', 'adlog') ?>" />
	<input type="submit" name="adlog_action" value="<?php _e('Delete log DB', 'adlog') ?>" />
	<br />
	
	<h3>Debugging</h3>
	<?php
	adlog_add_checkbox('Enable debug mode', 'debug_mode');
	?>
	<input type="submit" name="adlog_action" value="<?php _e('Save', 'adlog') ?>" />
	<?php
	if (adlog_ticked('debug_mode')){
		require_once(ADLOG_PATH.'/ui-tab-debug.php');
		adlog_tab_debug();
	}
	
	echo '</form>';
}

function adlog_add_test_data(){
	$homeurl = home_url();
	$query = "INSERT INTO `TABLENAME` (Date, Type, Size, Unit, Page, Referrer, IP, Browser, Debug) VALUES
		('2011-08-02 12:13:12', 1, '468x60', 3, '$homeurl', 'http://www.example.com/referrer1', '".ip2long("123.123.123.123")."', 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.186 Safari/535.1', '*TD*'),
		('2011-08-02 12:13:14', 2, '', 3, '$homeurl', 'http://www.example.com/referrer2', '".ip2long("123.123.123.124")."', 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/5.0)', '*TD*'),
		('2011-08-02 12:14:15', 3, '', 3, 'http://www.reviewmylife.co.uk/blog/2010/12/06/ad-injection-plugin-wordpress/', 'http://www.advancedhtml.co.uk/', '".ip2long("123.123.123.125")."', 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/534.1 (KHTML, like Gecko) Chrome/14.0.835.186 Safari/535.1', '*TD*'),
		('2011-08-02 12:14:16', 4, '', 3, '$homeurl', 'http://www.example.com/referrer4', '".ip2long("123.123.123.126")."', 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/5.0)', '*TD*'),
		('2011-08-02 12:14:17', 5, '', 3, 'http://www.advancedhtml.co.uk/', 'http://wordpress.org/extend/plugins/ad-logger/', '".ip2long("123.123.123.127")."', 'Mozilla/5.0 (Windows NT 5.1; rv:6.0.2) Gecko/20100101 Firefox/6.0.2', '*TD*'),
		('2011-08-03 12:14:18', 1, '728x90', 3, '$homeurl', 'http://www.example.com/referrer6', '".ip2long("123.123.123.128")."', 'Mozilla/5.0 (Windows NT 5.1; rv:6.0.2) Gecko/20100101 Firefox/6.0.2', '*TD*'),
		('2011-08-03 12:14:19', 1, '728x90', 3, '$homeurl', 'http://www.example.com/referrer7', '".ip2long("0.0.0.0")."', 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/5.0)', '*TD*'),
		('2011-08-03 12:15:14', 1, '728x90', 3, '$homeurl', 'http://www.example.com/referrer8', '".ip2long("123.123.123.123")."', 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/5.0)', '*TD*'),
		('2011-08-03 12:16:14', 1, '468x60', 3, '$homeurl', 'http://www.example.com/referrer9', '".ip2long("123.123.123.123")."', 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:6.0.2) Gecko/20100101 Firefox/6.0.2', '*TD*'),
		('2011-08-04 12:17:14', 1, '468x60', 3, '$homeurl', 'http://www.example.com/referrer1', '".ip2long("123.123.123.123")."', 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/5.0)', '*TD*'),
		('2011-08-04 12:18:14', 1, '468x60', 3, '$homeurl', 'http://www.example.com/referrer2', '".ip2long("123.123.123.124")."', 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:6.0.2) Gecko/20100101 Firefox/6.0.2', '*TD*'),
		('2011-08-04 12:19:14', 2, '', 3, 'http://www.reviewmylife.co.uk/blog/2010/12/06/ad-injection-plugin-wordpress/', 'http://www.example.com/referrer3', '".ip2long("123.123.123.124")."', 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/5.0)', '*TD*'),
		('2011-08-04 12:24:14', 1, '468x60', 3, '$homeurl', 'http://www.example.com/referrer4', '".ip2long("123.123.123.125")."', 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:6.0.2) Gecko/20100101 Firefox/6.0.2', '*TD*'),
		('2011-08-05 12:34:14', 1, '468x60', 3, '$homeurl', 'http://www.example.com/referrer4', '".ip2long("123.123.123.125")."', 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/5.0)', '*TD*'),
		('2011-08-06 12:44:14', 3, '', 3, 'http://www.reviewmylife.co.uk/', 'http://wordpress.org/extend/plugins/ad-injection/', '".ip2long("123.123.123.126")."', 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.186 Safari/535.1', '*TD*'),
		('2011-08-07 12:54:14', 3, '', 3, 'http://www.reviewmylife.co.uk/', 'http://wordpress.org/extend/plugins/ad-injection/', '".ip2long("123.123.123.126")."', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_5_8) AppleWebKit/534.50.2 (KHTML, like Gecko) Version/5.0.6 Safari/533.22.3', '*TD*'),
		('2011-08-08 13:14:14', 3, '', 3, 'http://www.reviewmylife.co.uk/blog/2010/12/06/ad-injection-plugin-wordpress/', 'http://www.example.com/referrer6', '".ip2long("123.123.123.126")."', 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/5.0)', '*TD*'),
		('2011-08-09 14:14:14', 1, '468x60', 3, '$homeurl', 'http://www.example.com/referrer1', '".ip2long("123.123.123.126")."', 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:6.0.2) Gecko/20100101 Firefox/6.0.2', '*TD*'),
		('2011-08-09 15:14:14', 1, '468x60', 3, '$homeurl', 'http://www.example.com/referrer1', '".ip2long("123.123.123.124")."', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_5_8) AppleWebKit/534.50.2 (KHTML, like Gecko) Version/5.0.6 Safari/533.22.3', '*TD*'),
		('2011-08-09 16:14:14', 1, '468x60', 3, '$homeurl', 'http://www.example.com/referrer1', '".ip2long("123.123.123.124")."', 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; .NET CLR 2.0.50727; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729)', '*TD*'),
		('2011-08-09 17:14:14', 4, '', 3, 'http://www.reviewmylife.co.uk/blog/2010/12/06/ad-injection-plugin-wordpress/', 'http://wordpress.org/extend/plugins/ad-injection/', '".ip2long("123.123.123.124")."', 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/5.0)', '*TD*'),
		('2011-08-09 18:14:14', 1, '468x60', 3, '$homeurl', 'http://www.example.com/referrer1', '".ip2long("124.123.123.124")."', 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.186 Safari/535.1', '*TD*'),
		('2011-08-10 19:14:14', 2, '', 3, 'http://www.advancedhtml.co.uk/', 'http://www.example.com/referrer1', '".ip2long("125.123.123.124")."', 'Mozilla/5.0 (Windows NT 5.1; rv:6.0.2) Gecko/20100101 Firefox/6.0.2', '*TD*'),
		('2011-08-10 20:14:14', 5, '', 3, 'http://www.advancedhtml.co.uk/ad-injection-1-2-0-0-preview-wordpress-ad-management-plugin/', 'http://wordpress.org/extend/plugins/ad-injection/', '".ip2long("126.123.123.124")."', 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; .NET CLR 2.0.50727; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729)', '*TD*'),
		('2011-08-10 21:14:14', 1, '468x60', 3, '$homeurl', 'http://www.example.com/referrer1', '".ip2long("127.123.123.124")."', 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.186 Safari/535.1', '*TD*')
		;";
	adlog_sql($query);
}

function adlog_delete_test_data(){
	$query = "DELETE FROM `TABLENAME` WHERE Debug = '*TD*'";
	adlog_sql($query);
}

function adlog_plugin_exists($plugin_dir) {
	$plugins = get_plugins($plugin_dir);
	if ($plugins) return true;
	return false;
}

function adlog_add_log_checkbox($text, $option, $image){
	echo '<img src="'. WP_PLUGIN_URL . '/ad-logger/images/'.$image.'" width="16" height="16" border="0" alt="'.$text.'" />';
	adlog_add_checkbox($text, $option);
}

function adlog_top_message_box(){
	$ops = adlog_options();
	if (isset($_POST['adlog_action']) && $_POST['adlog_action']=='Save') {
        echo '<div id="message" class="updated below-h2"><p style="line-height:140%"><strong>';
        echo 'All settings saved.';
		echo '</strong></div><br />';
	}
	global $adlog_ui_notice_version;
	if ($ops['ui_notice_acknowledgement'] < $adlog_ui_notice_version){
		global $adlog_notice;
		?>
		<div id="message" class="updated below-h2"><p style="line-height:140%"><strong>
		Thanks for installing Ad Logger, please take a moment to read this important information.
		</strong></p>
		<p>
		1. Ad Logger uses JavaScript events to 'guess' when ads have been clicked. This method <b>does not modify</b> your original ad code.<br /><br />
		2. This method of logging is <b>not</b> 100% accurate. It is probably about 80-90% accurate.<br /><br />
		3. Ad Logger logs <b>raw clicks</b>. The numbers <b>will not match</b> your ad provider's statistics as they only show you valid clicks.<br /><br />
		4. You use this plugin (as with all GPL plugins) at your own risk. <b>It is your responsibility</b> to judge whether you are allowed to use it with your ad provider(s).<br /><br />
		5. <b>Don't</b> test the plugin by clicking on your own ads! You may get yourself banned by your ad provider.<br /><br />
		<form name="adnoticeform" method="post">
		<?php wp_nonce_field('_adlognoticeform', '_ad-logger-notice-nonce'); ?>
		<input type="submit" name="adlog_action" value="<?php echo $adlog_notice; ?>" />
		</form>
		</p></div><br />
		<?php
		return;
	}
	if (!isset($_POST['adlog_action']) && !isset($_GET['o']) || $_POST['adlog_action']==$adlog_notice){
		echo '<div id="message" class="updated below-h2"><p style="line-height:140%"><strong>';
		echo "19th October 2011: Minor fixes and tweaks. You can now click on the '&#x21d3;' or 'Type' icon to filter the results further. Please contact me ASAP if you spot any bugs, or odd behaviour via the ".'<a href="'.adlog_feedback_url().'" target="_new">quick feedback form</a>.';
		echo '</strong></p></div><br />';
	}
}

function adlog_feedback_url(){
	$wp_version = get_bloginfo('version');
	$ad_version = adlog_get_version();
	$data = urlencode($wp_version." / ".$ad_version);
	return "https://docs.google.com/spreadsheet/viewform?formkey=dEJscnl4T2RJV3NHaEJwcWdsWlo3WEE6MA&amp;entry_3=$data";
}

function adlog_get_version(){
	$plugin_data=get_plugin_data(ADLOG_PATH . '/ad-logger.php');
	return $plugin_data['Version'];
}

function adlog_side_donate_box(){
?>
	<div class="postbox-container" style="width:258px;">
		<div class="metabox-holder">	
		<div class="meta-box-sortables" style="min-height:200px;">
		<div class="postbox">
		<h3 class="hndle"><span> Donate $10, $20 or $50!</span></h3>
		<div class="inside" style="margin:5px;">
		I have spent many hundreds of hours creating this plugin. If it helps you please consider making a donation. Thank you!

<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_donations">
<input type="hidden" name="business" value="JP5ECV7NX7GSY">
<input type="hidden" name="item_name" value="Ad Logger">
<input type="hidden" name="item_number" value="Ad Logger">
<input type="hidden" name="currency_code" value="USD">
<input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHosted">
<p>Amount:<br>
<input class="omni_donate_field" type="text" name="amount" size="9" title="The amount you wish to donate" value="10">
<select id="currency_code" name="currency_code">
	<option value="USD">U.S. Dollars</option>
	<option value="GBP">Pounds Sterling</option>
    <option value="AUD">Australian Dollars</option>
    <option value="CAD">Canadian Dollars</option>
    <option value="EUR">Euros</option>
    <option value="JPY">Yen</option></select>
</p>
<center>
<input type="image" src="https://www.paypalobjects.com/en_US/GB/i/btn/btn_donateCC_LG.gif" border="0" width="166" height="53" name="submit" alt="PayPal - The safer, easier way to pay online."></center>
</form>
				
		</div>
		</div>	
		</div>
		</div>
	</div> 
	<?php
}

function adlog_side_info_box(){
?>
	<div class="postbox-container" style="width:258px;">
		<div class="metabox-holder">	
		<div class="meta-box-sortables">
		<div class="postbox">
		<h3 class="hndle"><span>Info</span></h3>
		<div class="inside" style="margin:5px;">
			<h4>More Ad Logger information</h4>
			<ul>
			<li><a href="http://wordpress.org/extend/plugins/ad-logger/" target="_new">Ad Logger at WordPress</a></li>
			<li><a href="http://wordpress.org/extend/plugins/ad-logger/faq/" target="_new">Ad Logger FAQ</a></li>
			<li><b><a href="<?php echo adlog_feedback_url(); ?>" target="_new">Report a bug / give feedback</a></b></li>
			</ul>
		
			<h4><font color="red">Beta version</font></h4>
			<p>This plugin was first publicly released on 28th September 2011. It is very new, so it will have bugs and problems. Please let me know what you think, and report any problems to me.</p>
			
			<h4>More by this author</h4>
			<ul>
			<li><a href="http://wordpress.org/extend/plugins/ad-injection/" target="_new">Ad Injection plugin for WordPress</a></li>
			<li><a href="http://www.reviewmylife.co.uk/" target="_new">www.reviewmylife.co.uk</a></li>
			<li><a href="http://www.advancedhtml.co.uk/" target="_new">www.advancedhtml.co.uk</a></li>
			</ul>
		</div>
		</div>	
		</div>
		</div>
	</div> 
	<?php
}

function adlog_selection_box($name, $values, $type="", $selected_value=NULL){
	$associative = adlog_is_associative($values);

	if ($selected_value == NULL){
		$ops = adlog_options();
		$selected_value = $ops[$name];
	}

	echo "<select name='$name' id='$name'>";
	foreach ($values as $key=>$value){
		$option_value = $value;
		$display_value = $value;
		if ($associative){
			$option_value = $key;
		}
		echo "<option value=\"$option_value\" ";
		if("$selected_value" == "$option_value"){
			echo 'selected="selected"';
		}
		$typetxt = $type;
		echo ">$display_value $typetxt</option>";
	}
	echo "</select>";
}

function adlog_is_associative($array){
	$keys = array_keys($array);
	$count = count($keys);
	for ($i=0; $i<$count; ++$i){
		if ($i != $keys[$i]) return true;
	}
	return false;
}

function adlog_add_checkbox($label, $name){
	echo '<input type="hidden" name="'.$name.'" value="off" />';
	echo '<input type="checkbox" name="'.$name.'" '.adlog_ticked($name)." />";
	echo $label;
}

function adlog_checkNoticeNonce(){
	if (empty($_POST) || !check_admin_referer('_adlognoticeform', '_ad-logger-notice-nonce')){
		echo 'form error';
		exit();
	}
}

function adlog_checkNonce(){
	if (empty($_POST) || !check_admin_referer('_adlogform', '_ad-logger-nonce')){
		echo 'form error';
		exit();
	}
}

function adlog_process_action(){
	$action = $_POST['adlog_action'];
	if (isset($action)){
	$ops = adlog_options();
	global $adlog_notice;
	switch($action){
		case 'Save':
			adlog_checkNonce();
			adlog_save($ops);
			break;
		case $adlog_notice:
			adlog_checkNoticeNonce();
			$ops['ui_notice_acknowledgement'] = $ops['settings_version'];
			adlog_save($ops);
			break;
		case 'Delete log Data':
			adlog_checkNonce();
			adlog_delete_all();
			break;
		case 'Create log DB':
			adlog_checkNonce();
			adlog_create_logging_database();
			break;
		case 'Delete log DB':
			adlog_checkNonce();
			adlog_delete_table();
			break;
		case 'Reset settings to default':
			adlog_checkNonce();
			adlog_update_options(adlog_default_options());
			break;
		case 'Delete settings':
			adlog_checkNonce();
			delete_option('adlog_options');
			adlog_options(1);
			break;
		case 'Add test data':
			adlog_checkNonce();
			adlog_add_test_data();
			break;
		case 'Delete test data':
			adlog_checkNonce();
			adlog_delete_test_data();
			break;
		case 'Reset Notice':
			adlog_checkNonce();
			$ops['ui_notice_acknowledgement'] = 0;
			adlog_save($ops);
			break;
		default:
			adlog_checkNonce();
			adlog_error("Error: '$action' command is not understood.");
		}
	}
}

function adlog_save($ops){
	// Extract all know options
	$default_options = adlog_default_options();
	foreach ($default_options as $key => $value){
		if (isset($_POST[$key])){
			$ops[$key] = $_POST[$key];
		}
	}
	adlog_update_options($ops);
}

function adlog_update_options($ops){
	update_option('adlog_options', $ops);
	// Refresh options from database as cached values are now invalidated
	global $adlog_data;
	$adlog_data = get_option('adlog_options');
}

function adlog_sql($query){
	global $wpdb;
	$query = str_replace("TABLENAME", adlog_table_name(), $query);
	if($wpdb->query($query) === false){
		echo "Error with: $query<br />Msg: ".$wpdb->last_error."<br />";
		return false;
	}
	return true;
}

function adlog_error($msg){
	echo "<font color='red'>$msg</font><br />";
}

function adlog_message($msg){
	echo "<font color='blue'>$msg</font><br />";
}

function adlog_delete_table() {
	adlog_message('Deleting table...');
	adlog_sql("DROP TABLE IF EXISTS TABLENAME");
}

function adlog_delete_all() {
	adlog_message('Deleting all data...');
	adlog_sql("TRUNCATE TABLE TABLENAME");
}

// All these stored in a single DB option row
function adlog_default_options(){
	return array(
		'click_detection_mode' => 1,
		'log_all' => 'off',
		'log_adsense' => 'on',
		'log_amazon' => 'on',
		'log_facebook' => 'on',
		'log_twitter' => 'on',
		'log_plusone' => 'on',
		//
		'block_ads_if_num_adsense_clicks' => 3,
		'block_ads_if_num_clicks_occur_within_hours' => 168,
		'block_ads_cookie_hours' => 0,
		'divs_to_hide' => 'adlog-advert',
		'divs_to_hide_delay' => 1,
		//
		'store_all' => 'off',
		'store_size' => 'on',
		'store_unit' => 'on',
		'store_referrer' => 'off',
		'store_ip' => 'on',
		'store_browser' => 'on',
		'store_src' => 'off',
		//
		'ui_num_rows_per_page' => 20,
		'ui_show_size' => 'on',
		'ui_show_unit' => 'off',
		'ui_show_referrer' => 'off',
		'ui_show_ip' => 'on',
		'ui_show_browser' => 'on',
		'ui_show_src' => 'off',
		'ui_notice_acknowledgement' => 0,
		//
		'debug_mode' => 'off',
		'log_db_version' => ADLOG_LOG_DB_VERSION,
		'settings_version' => ADLOG_SETTINGS_VERSION
	);
}

// Hopefully run on install and upgrade
function adlog_activate_hook(){
	$ops = adlog_options();
	if(empty($ops)){
		// 1st Install.
		adlog_install_options();
	} else {
		// Upgrade check.
		adlog_upgrade_if_necessary();
	}
}

function adlog_install_options(){
	$new_options = adlog_default_options();
	adlog_update_options($new_options);
}

function adlog_upgrade_db(){
	$stored_options = adlog_options();
	$new_options = adlog_default_options();

	$stored_settings_version = adlog_settings_version($stored_options);
	$new_settings_version = adlog_settings_version($new_options);
	$stored_log_db_version = adlog_log_db_version($stored_options);
	$new_log_db_version = adlog_log_db_version($new_options);
	
	// 1. Copy existing options to new array. Use default as a baseline,
	// and then overwrite default with the saved ones.
	foreach ($new_options as $key => $value){
		if (array_key_exists($key, $stored_options)){
			$new_options[$key] = $stored_options[$key];
		}
	}
	
	// 2. Upgrade settings if necessary.
	
	// 3. Upgrade database if necessary.
	$db_upgraded = true;
	
	$upgrade_log_db_version = 2;
	if ($stored_log_db_version < $upgrade_log_db_version){
		// TODO what if these fail?
		$db_upgraded = adlog_sql("
			ALTER TABLE `TABLENAME` 
				ADD COLUMN (
					Referrer VARCHAR(128),
					IFrameSource VARCHAR(128),
					Debug VARCHAR(64)
				),
				MODIFY
					IPAddress INT UNSIGNED
				;");
		if ($db_upgraded){ $new_options['log_db_version'] = $upgrade_log_db_version; }
	}
	
	$upgrade_log_db_version = 3;
	if ($db_upgraded && $stored_log_db_version < $upgrade_log_db_version){
		$db_upgraded = adlog_sql("
			ALTER TABLE `TABLENAME` 
			ADD COLUMN Type TINYINT	
				;");
		if ($db_upgraded){ $new_options['log_db_version'] = $upgrade_log_db_version; }
	}
	
	$upgrade_log_db_version = 5;
	if ($db_upgraded && $stored_log_db_version < $upgrade_log_db_version){
		$db_upgraded = adlog_sql("
			ALTER TABLE `TABLENAME` 
			CHANGE IPAddress IP INT UNSIGNED,
			CHANGE IFrameSource Src VARCHAR(256)
				;");
		if ($db_upgraded){ $new_options['log_db_version'] = $upgrade_log_db_version; }
	}
	
	$upgrade_log_db_version = 6;
	if ($db_upgraded && $stored_log_db_version < $upgrade_log_db_version){
		$db_upgraded = adlog_sql("
			ALTER TABLE `TABLENAME` MODIFY Referrer VARCHAR(256);");
		if ($db_upgraded){ $new_options['log_db_version'] = $upgrade_log_db_version; }
	}
	
	// 4. Bump up settings version number.
	$new_options['settings_version'] = $new_settings_version;
	
	// 5. Save upgraded options. 
	adlog_update_options($new_options);
}

function adlog_create_logging_database() {
	$table = adlog_table_name();
	if (adlog_table_exists()){
		adlog_message('Logging database already exists. If you want to re-create it you need to delete it first: '.$table);
		return;
	}
	adlog_message('Creating logging database: '.$table);
	$structure = "CREATE TABLE  `TABLENAME` (
		ID INT PRIMARY KEY AUTO_INCREMENT,
		Date DATETIME,
		Type TINYINT,
		Size VARCHAR(11),
		Unit TINYINT,
		Page VARCHAR(128),
		Referrer VARCHAR(256),
		IP INT UNSIGNED,
		Browser VARCHAR(256),
		Src VARCHAR(128),
		Debug VARCHAR(64)
		);";
	adlog_sql($structure);
	
	if (!adlog_table_exists()){
		adlog_message("Error: $table has not been created by Ad Logger. Try clicking 'Create log DB'.");
	} else {
		adlog_message("Table has been creating. Ad Logger is ready to start logging :)<br />");
	}
}

add_action('admin_menu', 'adlog_admin_menu_hook');
add_filter('plugin_action_links', 'adlog_settings_link_hook', 10, 2); // TODO what are these numbers

?>
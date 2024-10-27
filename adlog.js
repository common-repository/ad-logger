/*
Part of the Ad Logger plugin for WordPress
http://www.reviewmylife.co.uk/
*/

var xmlHTTP = null;
var docLocStr = null;
var adType = 0;
var adDetailsStr = "";
var mouseOverIFrame = null;
var lastClickTime = 0;

var debugStr = "&D=";
var log_enabled = true;


// Mode
// 1 == wait for blur or beforeunload/unload
// 2 == wait for beforeunload/unload
function adlog_get_click_detection_mode(){
	if (typeof adlog_click_detection_mode != 'undefined'){
		return adlog_click_detection_mode;
	} else {
		//document.write("<!--ADLOG DEBUG: couldn't find adlog_click_detection_mode value. Using default.-->");
		return 1;
	}
}

function adlog_get_iframes_to_log(){
	if (typeof adlog_iframes_to_log != 'undefined'){
		return adlog_iframes_to_log;
	} else {
		// todo doing document write invalidates DOM and data returned from quering it!
		//document.write("<!--ADLOG DEBUG: couldn't find adlog_iframes_to_log value. Using default.-->");
		return new Array('adsense', 'amazon');
	}
}

function adlog_get_data_to_store(){
	if (typeof adlog_data_to_store != 'undefined'){
		return adlog_data_to_store;
	} else {
		return new Array('all');
	}
} 

function adlog_get_block_ads_if_num_adsense_clicks(){
	if (typeof adlog_block_ads_if_num_adsense_clicks != 'undefined'){
		return adlog_block_ads_if_num_adsense_clicks;
	} else {
		return 0;
	}
}

function adlog_get_block_ads_if_num_clicks_occur_within_hours(){
	if (typeof adlog_block_ads_if_num_clicks_occur_within_hours != 'undefined'){
		return adlog_block_ads_if_num_clicks_occur_within_hours;
	} else {
		return 72;
	}
}

function adlog_get_divs_to_hide(){
	if (typeof adlog_divs_to_hide != 'undefined'){
		return adlog_divs_to_hide;
	} else {
		return 'adlog-advert';
	}
}

function adlog_get_divs_to_hide_delay(){
	if (typeof adlog_divs_to_hide_delay != 'undefined'){
		return adlog_divs_to_hide_delay;
	} else {
		return 1;
	}
}

function adlog_get_block_ads_cookie_hours(){
	if (typeof adlog_block_ads_cookie_hours != 'undefined'){
		return adlog_block_ads_cookie_hours;
	} else {
		//document.write("<!--ADLOG DEBUG: couldn't find adlog_block_ads_cookie_hours value. Using default (disabled).-->");
		return 0;
	}
}

function adlog_get_plugins_url(){
	if (typeof adlog_plugins_url != 'undefined'){
		return adlog_plugins_url;
	} else {
		//document.write("<!--ADLOG DEBUG: couldn't find adlog_plugins_url value. Using default.-->");
		return "/wp-content/plugins/ad-logger/";
	}
} 

function adlog_debug_on(){
	if (typeof adlog_debug_mode != 'undefined'){
		return adlog_debug_mode;
	} else {
		//document.write("<!--ADLOG DEBUG: couldn't find adlog_debug_mode value. Using default.-->");
		return false;
	}
}

function prepareXMLHttp(){
	xmlHTTP = getXMLHttp();
	if (xmlHTTP){
		xmlHTTP.open("POST", adlog_get_plugins_url() + "store.php");
		xmlHTTP.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xmlHTTP.setRequestHeader("Connection", "close");
		xmlHTTP.onreadystatechange = function() {
			log("state=" + xmlHTTP.readyState);
			if (xmlHTTP.readyState != 4)  { return; }
			log("text=" + xmlHTTP.responseText + " status=" + xmlHTTP.status);
		};
	}
	if (!docLocStr){
		docLocStr = "L=" + escape(document.location);
		if (adlog_store('referrer')) docLocStr += "&R=" + escape(document.referrer);
	}
}

function send_ajax(params){
	log("+send");
	if (xmlHTTP){
		xmlHTTP.setRequestHeader("Content-length", params.length);
		log("state=" + xmlHTTP.readyState);
		xmlHTTP.send(params);
	}
	log("-send");
}

// function to create XmlHttp Object
function getXMLHttp(){
	if (window.XMLHttpRequest){
          // IE7, Mozilla, Safari, etc
          return new XMLHttpRequest();
	}else{
		if (window.ActiveXObject){
          // ActiveX control for IE5.x and IE6
          return new ActiveXObject("Microsoft.XMLHTTP");
        }
	}
	return null;
}

function getCurrentEventSource(e){
	var event = e || window.event;
	var target = event.target || event.srcElement;
	return target;
}

function processMouseOver(e) {
	debugStr = "&D="; // reset when mouse goes over ad
	var source = getCurrentEventSource(e);
	log("MouseOver detected: " + source);
	mouseOverIFrame = source;
	prepareXMLHttp();
	getAdDetails();
}

function processMouseOut(e) {
	log("MouseOut detected: " + e);
	mouseOverIFrame = null;
	top.focus();
}

function processBlur(e) {
	debugStr += ":bl";
	log("Blur detected: " + e);
	if (adlog_get_click_detection_mode() == 1){
		processIFrameClick();
	}
}

function processBeforeUnload() {
	debugStr += ":bu";
	log("BeforeUnload detected.");
	processIFrameClick();
}

function processUnload() {
	debugStr += ":un";
	log("Unload detected.");
	processIFrameClick();
}

function getAdDetails() {
	var ad = document.getElementsByTagName("iframe");
	for (i=0; i<ad.length; i++){
		if (mouseOverIFrame == ad[i]){
			adType = getClickType(ad[i].src);
			adDetailsStr = "&T=" + adType;
			if (adlog_store('unit')) adDetailsStr +=  "&U=" + (i+1);
			if (adlog_store('size')) {
				if (ad[i].width != '' || ad[i].height != ''){
					adDetailsStr += "&X=" + ad[i].width + "x" + ad[i].height;
				}
			}
			if (adlog_store('src')) adDetailsStr += "&S=" + escape(ad[i].src);
			return;
			}
	}
}

function adlog_store(data){
	if (jQuery.inArray('all', adlog_get_data_to_store()) > -1) return true;
	if (jQuery.inArray(data, adlog_get_data_to_store()) > -1) return true;
	return false;
}

function getClickType(el_src){
	var type = 0;
	if (el_src == '' || el_src.indexOf('googleads') > 0){
		type = 1;
	} else if (el_src.indexOf('amazon') > 0){
		type = 2;
	} else if (el_src.indexOf('facebook') > 0){
		type = 3;
	} else if (el_src.indexOf('twitter') > 0){
		type = 4;
	} else if (el_src.indexOf('plusone') > 0){
		type = 5;
	}
	return type;
}

function processIFrameClick() {
	if(mouseOverIFrame) {
		var now = new Date().getTime();
		if (now - lastClickTime > 1000){
			if (!adlog_debug_on()) debugStr = "";
			var postargs = docLocStr + debugStr + adDetailsStr;
			send_ajax(postargs);
			log("IFrame click detected. " + now + " " + lastClickTime + " " + postargs);	
			lastClickTime = now;
			debugStr = "&D=";
			
			if (adType == 1){ //1==AdSense
				alog_as_click();
			}
		}
	}
}

function adlog_get_num_adsense_clicks(){
	return adlog_getcookie("adlogascount");
}

function adlog_as_blocked(){
	var numclicks = adlog_get_num_adsense_clicks();
	var limit = adlog_get_block_ads_if_num_adsense_clicks();
	return (numclicks >= limit);
}

function alog_as_click(){
	var numclicks = adlog_get_num_adsense_clicks();
	numclicks++;
	log("AS click count: " + numclicks);
	var expiry = new Date();
	expiry.setTime(expiry.getTime() + 1000*60*60 * adlog_get_block_ads_if_num_clicks_occur_within_hours());
	var adlogascount = "adlogascount="+numclicks+"; expires=" + expiry.toGMTString() + "; path=/;";
	document.cookie = adlogascount;
	//
	if (adlog_as_blocked()){
		adlog_hide_adverts();
		if (adlog_get_block_ads_cookie_hours() > 0){
			var expiry = new Date();
			expiry.setTime(expiry.getTime() + 1000*60*60 * adlog_get_block_ads_cookie_hours());
			var adlogblockedcookie = "adlogblocked=1; expires=" + expiry.toGMTString() + "; path=/;";
			document.cookie = adlogblockedcookie;
			log("Setting adlogblocked cookie: " + adlogblockedcookie);
		}
	}
}

// from http://www.elated.com/articles/javascript-and-cookies/
function adlog_getcookie(cookie_name){
	var results = document.cookie.match ('(^|;) ?'+cookie_name+'=([^;]*)(;|$)');
	if (results){
		return (unescape(results[2]));
	} else {
		return null;
	}
}

function log(message) {
	if (!log_enabled) return;
	var console = document.getElementById("console");
	if (console){
		console.value += message + "\n";
	}
}

function adlog_init() {
	log("+ adlog_init");
	var hasAdverts = false;
	var log_all = false;
	var log_adsense = false;
	var log_amazon = false;
	var log_facebook = false;
	var log_twitter = false;
	var log_plusone = false;
	var iframes_to_log = adlog_get_iframes_to_log();
	for(var i=0; i<iframes_to_log.length; ++i){
		if (iframes_to_log[i] == 'all'){
			log_all = true;
		} else if (iframes_to_log[i] == 'adsense'){
			log_adsense = true;
		} else if (iframes_to_log[i] == 'amazon'){
			log_amazon = true;
		} else if (iframes_to_log[i] == 'facebook'){
			log_facebook = true;
		} else if (iframes_to_log[i] == 'twitter'){
			log_twitter = true;
		} else if (iframes_to_log[i] == 'plusone'){
			log_plusone = true;
		}
	}
	
	var el = document.getElementsByTagName("iframe");
	var el_len = el.length;
	log("num iframes:" + el_len);
	for (var i=0; i<el_len; i++) {
		var el_src = el[i].src;
		log("iframe"+i+":*" + el_src + "*");
		var log_this_iframe = false;
		if (log_all){
			log("Logging activated for all iframes");
			log_this_iframe = true;
		} else if (log_adsense && (el_src == '' || el_src.indexOf('googleads') > 0)){
			log("Logging activated for AdSense");
			log_this_iframe = true;
		} else if (log_amazon && (el_src.indexOf('amazon') > 0)){
			log_this_iframe = true;
			log("Logging activated for Amazon Associates");
		} else if (log_facebook && (el_src.indexOf('facebook') > 0)){
			log_this_iframe = true;
			log("Logging activated for Facebook");
		} else if (log_twitter && (el_src.indexOf('twitter') > 0)){
			log_this_iframe = true;
			log("Logging activated for Twitter");
		} else if (log_plusone && (el_src.indexOf('plusone') > 0)){
			log_this_iframe = true;
			log("Logging activated for Google +1");
		}
		if (log_this_iframe){
			hasAdverts = true;
			el[i].onmouseover = processMouseOver;
			el[i].onmouseout = processMouseOut;
		}
	}
	if (!hasAdverts) {
		log("No adverts/iframes found during 'adlog_init' in adlog.js");
		return;
	}
	jQuery(window).blur(function() { processBlur(); } ); // ie, ff, ch
	window.addEventListener('beforeunload', processBeforeUnload, false); // ie, ff, ch
	jQuery(window).unload(function() { processUnload(); } ); // op
	log("- adlog_init");
}

function adlog_hide_adverts(){
	setTimeout("adlog_hide_adverts_callback()", adlog_get_divs_to_hide_delay()*1000);
}

function adlog_hide_adverts_callback(){
	log("Hiding "+adlog_get_divs_to_hide()+" divs as ads are blocked");
	jQuery('.'+adlog_get_divs_to_hide()).hide();
}

jQuery(window).ready(function() {
	if (adlog_as_blocked()){
		adlog_hide_adverts_callback();
	}
});

jQuery(window).load(function() {
	// We can only attach events to iframes that have been loaded. Therefore
	// allow an extra second to give time for iframes to finish loading. Not
	// foolproof but reasonably effective.
	setTimeout("adlog_init()", 1000);
});

=== Ad Logger ===
Contributors: reviewmylife
Donate link: http://www.advancedhtml.co.uk/
Tags: ad logger, ad logging, log, logging ad-logger, logging, click, clicks, statistics, store, ad injection, adsense, advert, ad, advertising, affiliate, Amazon, ClickBank, TradeDoubler, Google, adBrite, post, WordPress, automatically, plugin, free, blog, click bombing, protection
Requires at least: 3.0.0
Tested up to: 3.2.1
Stable tag: 0.0.1.3

Logs data about iframe clicks: can log clicks from Google AdSense, Amazon, Facebook, Twitter. Logs URL, IP, browser, and size of the clicked iframe.

== Description ==

Ad Logger from [advancedhtml](http://www.advancedhtml.co.uk/ "advancedhtml") logs data about iframe clicks - it can log clicks from Google AdSense, Amazon Affiliates, Facebook Likes, Twitter Tweet button, and Google's +1 button. It does this without modifying your ad code - it uses other accessible events to estimate when the ad has been clicked. This method is about 80% accurate.

It logs the page, IP, browser, referrer, and dimensions of the clicked iframe to your MySQL database. You can then get reports showing which pages generated the most clicks, where clicking visitors came from, and who is clicking your ads the most. The data and reports are loaded using AJAX so you can browse the data without refreshing the UI. 

= Logs clicks without modifying the iframe =

No modifications are made to your adverts/iframes which should (hopefully) enable you to use Ad Logger without breaking any TOS from your ad provider.

= AJAX table reports =

You can view the raw log information in a table, and also view summary reports showing which pages were most clicked, which IPs did the most clicking, what types of ads were most clicked and more.

You can also filter the results. e.g. you can click on the arrow next to an IP address and you'll see all the clicks that came from that IP address.

= Block AdSense ads if too many clicks appear - basic click bombing protection =

If you insert your AdSense ads using Ad Injection 1.2.0.4+ you can configure Ad Logger to block these ads if too many clicks come from the same person within a set time frame.

This feature uses JavaScript and cookies, so it won't function if these are turned off, of if your website readers turn their cookies off.

= Dynamically remove ads - basic click bombing protection =

Ad Logger can dynamically hide a named div after the click count is reached (i.e. the ads would dissapear from the current page without the page having to be re-loaded). These divs will stay hidden on subsequent page loads for the click detection expiry time.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the ad-logger folder to the '/wp-content/plugins/' directory (or just use the WordPress plugin installer to do it for you). The plugin must be in a folder called 'ad-logger'. So the main plugin file will be at /wp-content/plugins/ad-logger/ad-logger.php
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. It will now start logging data. If you are using a caching plugin you may have to clear the cache for the logging code to get added to your pages.

= How to uninstall =

You can uninstall by deactivating the plugin and deleting from the WordPress plugins control panel.

Uninstalling will delete all settings and data (including all the click logs).

== Frequently Asked Questions ==

= Why don't the number of reported clicks match my ad providers reported clicks? =

Because they measure different things. This plugin measures 'raw' clicks. You ad provider will show you 'valid' clicks. 

Additionally because this plugin doesn't modify your ad code it can never be 100% accurate. It may log clicks which didn't activate the ad link (e.g. if the user clicked on a non-linked part of the ad), and it may fail to log clicks if the browser moves to the new page before the log notification is sent.

From my testing I believe that this plugin's logged data will be 80-90% correct, but it may vary for you.

= How do I use the div hiding feature? =

You need to put your ad code in a div whose class name is the same as the one configured in the UI. e.g. if you keep the default div name of 'adlog-advert' you would need code like this.

`<div class="adlog-advert">
Your AdSense, or other ad code goes here
</div>`

= Can you guarantee me that this plugin is allowed by my ad provider? =

No.

= Is this plugin allowed by my ad provider? =

I'm not going to give you a 'yes' - but I think the tracking methods I'm using should be ok. You have to make the decision whether to use it yourself.

I can say that this plugin does not modify your ad provider's original ad code (it uses additonal JavaScript events to guess when a click occurs). And also the legitimate and large OpenX ad network uses a modified version of the Click Pepper code (linked above) to track AdSense clicks themselves.

= Can you make the click tracking 100% accurate? =

No. There is no way to do this without modifying your ad codes, and I don't want to do this as modifying the ad code is not allowed in the TOS of many ad providers.

= Can this plugin protect me from click bombing? =

Yes it can to an extent, but it won't stop a determined click bomber.

If a certain number of AdSense clicks has been registered it can prevent ads which are inserted via the Ad Injection plugin from appearing when the page is next loaded. And it can dynamically hide a named div after the click count is reached (i.e. the ads would dissapear from the current page without the page having to be re-loaded).

These features use cookies and JavaScript so could easily be by-passed by someone with a little technical knowledge.

= Will you be adding new features? =

Yes - I have lots of plans for new features. You can pass your ideas to me using the feedback link in the plugin.

= Some technical details =

* Plugin stores all its settings in a single option (adlog_options).
* Logging data is stored in a new table.
* Uninstall support is provided to delete the settings option and logging table if you uninstall the plugin.
* Admin code is separated into a separate file so it is not loaded when your visitors view your pages.
* The JavaScript for setting the referrer cookie is inserted using wp_enqueue_scripts.

= Has anyone written code to do this before? =

Yes there are similar commercial projects that do a similar job, but I didn't look at those because they cost money!

There are public domain projects, and information about how to do something similar. I looked at the following public domain projects, and sites, to get ideas:

* http://stackoverflow.com/questions/2381336/detect-click-into-iframe-using-javascript
* http://www.digitalmediaminute.com/article/1715/adsense-click-pepper
* http://www.monetizers.com/php-click-tracker.php
* http://blog.openx.org/01/google-adsense-click-tracking-integration/

No one has put these features into a WordPress plugin until now.

== Troubleshooting ==

= Reporting bugs =

If you do get any errors please use the 'Report a bug or give feedback' link on the plugin to send me the error details. If things go so badly wrong that you can't even get to the settings page please send me an email via [this contact form](http://www.reviewmylife.co.uk/contact-us/ "contact form").

== Screenshots ==

1. The Ad Logger UI showing the data table, options to select which iframes to log, the AdSense click blocking options, and options that allow you to set which fields of data to store and to show on screen.

== Changelog ==

= 0.0.1.3 =
* Better debug screen.
* Fix an incorrect message.
* Validate subquery param.
* Squash the on-page JavaScript.

= 0.0.1.2 =
* If too many clicks are registered a named div class can be hidden dynamically.
* Add a loading circle to the UI.
* Tweaks to prev/next links.

= 0.0.0.9 =
You can now click on the down arrow to filter the tables according to specific matches (IP, type, page, etc).

= 0.0.0.8 =
Fix missing parameter PHP error.

= 0.0.0.7 =
Update messages about click blocking support as Ad Injection 1.2.0.4 is now released.

= 0.0.0.5 =
First public release.

== Upgrade Notice ==

= 0.0.1.3 =
* Better debug screen.
* Fix an incorrect message.
* Validate subquery param.
* Squash the on-page JavaScript.

= 0.0.1.2 =
* If too many clicks are registered a named div class can be hidden dynamically.
* Add a loading circle to the UI.
* Tweaks to prev/next links.

= 0.0.0.9 =
You can now click on the down arrow to filter the tables according to specific matches (IP, type, page, etc).

= 0.0.0.8 =
Fix missing parameter PHP error.

= 0.0.0.7 =
Update messages about click blocking support as Ad Injection 1.2.0.4 is now released.

= 0.0.0.5 =
First public release.


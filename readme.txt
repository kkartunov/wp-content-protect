=== Content Protect By Time Lock ===
Contributors: Mtserve
Tags: user, content, authentication, author, profile, meta, menu, password, login, widget
Requires at least: 3.0.1
Tested up to: 4.0
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Content display management regulated by time locks.

== Description ==
This plugin allows its owners to selectively set time locks on content they want to protect(hide) from anonymous users visiting the site for particular time period. The time period can be bright variety of types. For instance +1 min, tomorrow or etc.

When content is protected it will be excluded from search results, author and post archives, menu navigation, feeds and more. If an anonymous user gets somehow a direct link to content and tries to access/view it, this user gets redirected to the login screen to enter credentials. After successfully log in the user gets redirected again but this time to the content itself.

= Available in: =
* English
* Deutsch
* Български

= Contributing =
http://git.io/lSW-AQ

= Support this work =
As You know every work needs time. But time nowadays is not free... this plugin needs Your support.
https://gittip.com/ColorfullyMe

== Installation ==
1. Upload plugin's .zip file to the "/wp-content/plugins/" directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
Done!

== Frequently Asked Questions ==
= How can I change the plugin settings? =
The plugin is equipped with a options page under the "Settings"  menu in WP's admin area.
= Where can I find information about the supported date/time formats for the time locks? =
All supported formats by the PHP's `strtotime()` function are supported by the plugin too. More here http://php.net/manual/en/datetime.formats.relative.php

== Screenshots ==
1. Set content's time lock for the first time.
2. Alter content time lock state.
3. Renew expired time lock.
4. Content time lock status in listings.
5. Plugin's settings page.

== Changelog ==
= 0.1 =
* Initial release.

== Upgrade Notice ==
= 0.1 =
* Initial release.

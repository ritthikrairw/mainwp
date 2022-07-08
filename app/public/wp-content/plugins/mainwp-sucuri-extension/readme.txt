=== Child Sucuri Extension ===
Plugin Name: MainWP Sucuri Extension
Plugin URI: https://mainwp.com
Description: MainWP Sucuri Extension enables you to scan your child sites for various types of malware, spam injections, website errors, and much more. Requires the MainWP Dashboard.
Version: 4.0.11
Author: MainWP
Author URI: https://mainwp.com
Documentation URI: https://mainwp.com/help/docs/category/mainwp-extensions/sucuri/

== Installation ==
1. Please install plugin "MainWP Dashboard" and active it before install MainWP Sucuri Extension plugin (get the MainWP Dashboard plugin from url:https://mainwp.com)
2. Upload the `mainwp-sucuri-extension` folder to the `/wp-content/plugins/` directory
3. Activate the Sucuri Extension plugin through the 'Plugins' menu in WordPress

== Screenshots ==
1. Enable or Disable extension on the "Extensions" page in the dashboard

== Changelog ==

= 4.0.11 - 6-21-2022 =
* Added: WP Nonce verification to the Go to WP Admin request for additional security

= 4.0.10 - 6-1-2022 =
* Fixed: Extension page layout on mobile devices
* Fixed: Multiple coding style problems
* Added: Screen Options to the extension page to set or remove table columns
* Added: Responsive mode to the Sucuri Sites table

= 4.0.9 - 12-13-2021 =
* Updated: Timestamp display to show local time
* Updated: PHP 8 compatibility

= 4.0.8.1 - 9-10-2020 =
* Updated: MainWP Dashboard 4.1 compatibility

= 4.0.8 - 8-27-20 =
* Added: 'mainwp_sucuri_table_features' filter
* Updated: Multiple cosmetic updates
* Updated: WordPress 5.5 compatibility

= 4.0.7 - 5-18-20 =
* Fixed: JS conflict issue
* Updated: load extension .js file only on the Extensions and Site Overview page

= 4.0.6 - 5-12-20 =
* Fixed: issue with filtering sites groups

= 4.0.5 - 4-13-20 =
* Fixed: issue with creating double records for the reporting system
* Fixed: multiple PHP warnings

= 4.0.4 - 2-12-20 =
* Fixed: multiple cosmetic problems
* Fixed: issue with saving table sorting state after page reload

= 4.0.3 - 2-3-2020 =
* Fixed: error caused by missing parameter in the 'mainwp_sucuri_scan_done' hook

= 4.0.2 - 9-13-2019 =
* Fixed: an issue with the table width on smaller screens
* Added: save_state property to the sites list
* Added: colReorder property to the sites list
* Added: horizontal scroll to the sites list

= 4.0.1 - 9-9-2019 =
* Fixed: multiple cosmetic issues
* Added: new shortcuts to the sites list on the extension page
* Added: scheduled events info to the Cron Schedules list
* Updated: reload page after closing the scan modal
* Updated: multiple text notification

= 4.0 - 8-27-2019 =
* Updated: extension UI/UX redesign
* Updated: support for the MainWP 4.0

= 1.3.1 - 11-19-2018 =
* Fixed: an issue with displaying scan results caused by slow response from the Sucuri server.

= 1.3 - 10-31-2018 =
* Fixed: scan error caused by changed Sucuri response format

= 1.2 - 4-20-2018 =
* Fixed: added support for the WP Cli scan command ( example: wp mainwp-sucuri scan [<siteid>] )

= 1.1 - 2-26-2018 =
* Updated: plugin info

= 1.0 - 2-17-2016 =
* Fixed: Translation issue
* Fixed: Compatibility with MainWP 3.0 version
* Added: An auto update warning if the extension is not activated
* Added: Support for the new API management
* Added: Support for WP-CLI
* Updated: "Check for updates now" link is not visible if extension is not activated

= 0.2.0 - 9-25-2015 =
* Updated: Refactored code to meet WordPress coding standards

= 0.1.2 - 6-26-2015 =
* Added: An option to disable SSL certificate verification when scanning child sites

= 0.1.1 =
* Updated: Quick start guide layout

= 0.1.0 =
* Fixed: Potential Security issue - Internal Code Audit

= 0.0.9 =
* Added: Support for the API Manager

= 0.0.8 =
* Fixed: Notification email template format

= 0.0.7 =
* Tweaked: Notification email template format

= 0.0.6 =
* Added: Support for the Client Reports extension
* Added: Additional Plugin Info
* Added: Redirection to the Extensions page

= 0.0.5 =
* Notification added

= 0.0.4 =
* Update quick guide

= 0.0.3 =
* New Feature Added: Extension is now saving Security Scan Reports
* New Feature Added: Security Scan Notifications

= 0.0.2 =
* Removed some lines of comments

= 0.0.1 =
* First version

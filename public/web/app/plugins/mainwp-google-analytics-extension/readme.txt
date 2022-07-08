=== Google Analytics Extension ===
Plugin Name: MainWP Google Analytics Extension
Plugin URI: https://mainwp.com
Description: MainWP Google Analytics Extension is an extension for the MainWP plugin that enables you to monitor detailed statistics about your child sites traffic. It integrates seamlessly with your Google Analytics account.
Version: 4.0.4
Author: MainWP
Author URI: https://mainwp.com

== Installation ==
1. Please install plugin "MainWP Dashboard" and active it before install Google Analytics Extension plugin (get the MainWP Dashboard plugin from url:https://mainwp.com/)
2. Upload the `mainwp-google-analytics-extension` folder to the `/wp-content/plugins/` directory
3. Activate the Google Analytics Extension plugin through the 'Plugins' menu in WordPress

== Screenshots ==
1. Enable or Disable extension on the "Extensions" page in the dashboard

== Changelog ==

= 4.0.4 - 12-7-2021 =
* Fixed: An issue with refreshing Auth token
* Added: Actions log support
* Updated: PHP 8 comatibility

= 4.0.3.2 - 8-13-2021 =
* Added: 'mainwp_ga_visit_chart_date' filter to allow users format chart date
* Added: 'mainwp_ga_select_web_property' filter to allow users to select specific property view
* Updated: Help documentation links

= 4.0.3.1 - 9-10-2020 =
* Updated: MainWP Dashboard 4.1 compatiblity

= 4.0.3 - 4-13-2020 =
* Fixed: view site data permission issue

= 4.0.2 - 2-18-2020 =
* Fixed: issues with user permissions to see analytics data
* Fixed: multiple cosmetic issues

= 4.0.1 - 9-13-2019 =
* Fixed: issue with deleting unused GA accounts
* Fixed: multiple cosmetic issues

= 4.0 - 8-27-2019 =
* Updated: extension UI/UX redesign
* Updated: support for the MainWP 4.0

= 1.9 - 10-12-2018 =
* Added: the mainwp_ga_redirect_url hook to implement support for custom admin paths

= 1.8 - 8-24-2018 =
* Fixed: a couple console warnings
* Updated: the Google graph loader
* Removed: unused jsapi file

= 1.7 - 7-6-2018 =
* Fixed: multiple PHP warnings
* Added: the mainwp_ga_visit_chart_date hook to support custom date format the stats graph
* Updated: prioritize GA views with the "mainwp" string

= 1.6 - 5-12-2017 =
* Fixed: an issue with auto assigning child sites

= 1.5 - 1-18-2017 =
* Fixed: An issue with displaying sites twice in the sites list
* Updated: Default sites sorting

= 1.4 - 31-12-2016 =
* Fixed: Conflict causing the widget to show a white page

= 1.3 - 14-12-2016 =
* Fixed: Google API reference

= 1.2 - 11-8-2016 =
* Fixed: An issue with getting Google Analytics data
* Added: PHP version compatibility check

= 1.1 - 9-16-2016 =
* Fixed: Bug with getting Google Analytics data from the Google API
* Updated: Google API

= 1.0 - 2-17-2016 =
* Fixed: Bug with incorrect Site from Google Account
* Added: GA Account name field
* Added: Support for WP-CLI
* Added: Support for the new API management
* Added: An auto update warning if the extension is not activated
* Updated: "Check for updates now" link is not vidible if extension is not activated

= 0.1.7 - 7-3-2015 =
* Fixed: A bug with incorrect file path

= 0.1.6 =
* Fixed: An issue with displaying values in Client Reports
* Fixed: An issue with saving Google Analytics account

= 0.1.5 =
* Fixed: SQL query bug

= 0.1.4 =
* Updated: Google API authentication process

= 0.1.3 =
* Updated: Quick start guide layout

= 0.1.2 =
* Fixed: Wrong documentation link
* Fixed: Tranlation domain issues
* Updated: CSS style

= 0.1.1 =
* Added: Support for the new Client Reports token (Google Analytics chart)
* Fixed: Potential security issue - Internal code audit

= 0.1.0 =
* Added: New hook to fix a PHP Notice
* Fixed: Misspell in the help documentation

= 0.0.9 =
* Added: Support for the API Manager

= 0.0.8 =
* Added: Auto assign child sites to GA profile

= 0.0.7 =
* Fixed: XML parse bug

= 0.0.6 =
* Added: Support for the Client Reorts extension
* Added: Support Forum URI and Documentation URI in the Plugin info
* Added: Redirection to the Extensions Page after activating the extension
* Fixed: 1 PHP Warning

= 0.0.5 =
* Update quick guide

= 0.0.4 =
* CSS Update

= 0.0.3 =
* Wordpress 3.9 changes

= 0.0.2 =
* Fixed issue with graph on dashboard

= 0.0.1 =
* First version

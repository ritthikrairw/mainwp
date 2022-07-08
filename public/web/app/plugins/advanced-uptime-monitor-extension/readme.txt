=== Advanced Uptime Monitor Extension ===
Plugin Name: Advanced Uptime Monitor Extension
Plugin URI: https://mainwp.com
Description: MainWP Extension for real-time uptime monitoring.
Version: 5.2.2
Author: MainWP
Author URI: https://mainwp.com
Documentation URI: https://kb.mainwp.com/docs/category/mainwp-extensions/advanced-uptime-monitor/

== Description ==

Advanced Uptime Monitor Extension is all about helping you to keep your websites up.

It monitors your websites every 5 minutes and alerts you if your sites are down (actually, it is smarter, details below).


== Installation ==

1. Please install plugin "MainWP Dashboard" and active it before install Advanced Uptime Monitor Extension plugin (get the MainWP Dashboard plugin from url:https://mainwp.com/)
2. Upload the `advanced-uptime-monitor-extension` folder to the `/wp-content/plugins/` directory
3. Activate the Advanced Uptime Monitor Extension plugin through the 'Plugins' menu in WordPress


== Screenshots ==

1. Display monitors on dashboard panel.
2. Setting "Advanced Uptime Monitor Extension" in Extensions Menu with following functions:
     2.1. Set API Key for your Advanced Uptime Monitor.
     2.2. Set default alert contact for add new monitor.
     2.3. Display or hidden monitors on dashboard view panel.
     2.4. Multi delete monitor or delete monitor one by one.
     2.5. Multi change status of monitors or change status of monitor one by one

== Changelog ==

= 5.2.2 - 12-8-2021 =
* Updated: PHP 8 compatibility

= 5.2.1 - 12-1-2021 =
* Fixed: An issue with displaying uptime data in reports for the Better Uptime API
* Fixed: An issue with loading monitors for the Better Uptime API
* Fixed: An issue with displaying monitor details
* Added: The availability column in the monitors table for Better Uptime API

= 5.2 - 8-16-2021 =
* Added: Support for the Better Uptime API

= 5.1.3 - 6-17-2021 =
* Fixed: Multiple PHP warnings and notices

= 5.1.2 - 6-9-2021 =
* Fixed: Multiple PHP warnings and notices

= 5.1.1 - 1-8-2021 =
* Fixed: An issue with displaying reporting tokens data

= 5.1 - 12-30-2020 =
* Added: support for NodePing API
* Added: support for Site24x7 API
* Added: phpDocs blocks for code documentation
* Updated: Refactored the code to meet WordPress coding standards
* Removed: Unused files

= 5.0.2 - 2-19-2020 =
* Fixed: multiple translation issues
* Fixed: multiple cosmetic issues
* Updated: removed monitor type field from the edit monitor form
* Updated: multiple URLs

= 5.0.1 - 10-9-2019 =
* Fixed: issue with selecting monitors in the Monitors table
* Fixed: issue with loading more than 50 monitors
* Fixed: issue with creating monitors in bulk
* Added: option to hide the extension widget on the Overview page
* Added: saveState property to the monitors list
* Added: colReorder property to the monitors list
* Added: horizontal scroll to the monitors list
* Updated: multiple usability updates
* Updated: multiple cosmetic updates

= 5.0 - 8-28-2019 =
* Updated: extension UI/UX redesign
* Updated: support for the MainWP 4.0

= 4.6.1 - 12-7-2018 =
* Fixed: a typo on the extension page

= 4.6 - 11-14-2018 =
* Added: mainwp_aum_auto_add_sites hook
* Added: mainwp_aum_monitor_created hook
* Added: mainwp_aum_monitor_deleted hook
* Added: mainwp_aum_monitor_started hook
* Fixed: an issue with displaying uptime data in the Manage Sites table

= 4.5 - 8-24-2018 =
* Added: a new feature for creating a monitor for all child sites automatically

= 4.4 - 7-6-2018 =
* Fixed: an issue with logging events duration times
* Fixed: multiple PHP warnings

= 4.3 - 4-20-2018 =
* Fixed: PHP 7.2 compatibility issues
* Updated: displaying method for contact types

= 4.2 - 12-15-2017 =
* Fixed: issue with saving monitors
* Added: duration time for the events list
* Added: support for the new token for the Client Reports
* Added: French translation

= 4.1 - 10-20-2017 =
* Fixed: conflict with the Managed Client Reports for WooCommerce
* Fixed: PHP Warning

= 4.0 - 9-28-2017 =
* Fixed: multiple cosmetic issues
* Fixed: multiple translation issues
* Fixed: multiple WordPress coding standard issues
* Fixed: multiple spelling issues
* Added: support for the Uptime Robot API version 2
* Added: new monitor options (HTTP Username and Password)
* Added: Uptime monitoring option box (widget)
* Added: Last monitor events option box (widget)
* Updated: general extension style
* Updated: notifaction texts
* Updated: error messages
* Removed: unused code
* Removed: unused images

= 3.7 - 7-28-2017 =
* Added: 'mainwp_aum_verify_certificate' hook so users can optionally disable SSL Certificate verification
* Updated: a few cosmetic issues

= 3.6 - 7-5-2017 =
* Updated: Uptime Robot API URI

= 3.5 - 6-30-2017 =
* Fixed: multiple issues caused by site-monitor URL difference (http/https mismatch)
* Fixed: multiple cosmetic issues
* Added: help tab
* Updated: after adding a new monitor, it is automatically set to show in Overview widget

= 3.4 - 2-9-2017 =
* Fixed: issue with fatching data from other extensions

= 3.3 - 12-16-2016 =
* Fixed: PHP 7.1 compatibility issue
* Updated: default monitor sorting

= 3.2 - 11-30-2016 =
* Fixed: an issue with saving monitors
* Fixed: compatibility issue with the MainWP Dashboard 3.2
* Fixed: an issue with displaying time zone correctly
* Fixed: an issue with creating monitors
* Fixed: PHP Warning

= 3.1 - 4-8-2016 =
* Fixed: issue with displaying monitors
* Fixed: WordPress 4.5 compatibility issue

= 3.0 - 3-30-2016 =
* Fixed: issue with adding the new monitors
* Fixed: incorrect text warning in case the MainWP Dashboard has been deactivated
* Fixed: issue with validating data in Edit Monitor form
* Added: uptime info in the Manage Sites page
* Added: monitor Type info in the Monitors list
* Added: monitor Interval option in the Add/Edit Monitor form
* Added: timezone info in the Uptime Robot settings box
* Added: ability to collapse/expand Uptime Robot settings box
* Added: support for translation
* Updated: extension layout
* Updated: extension style to match overall MainWP style
* Updated: extension notices texts
* Updated: responsive CSS layout
* Removed: unused code

= 2.1.2 - 2-17-2016 =
* Fixed: translation issue
* Fixed: issue with displaying multiple monitor pages in the widget
* Fixed: issue with displaying multiple monitor pages
* Added: an auto update warning if the extension is not activated
* Added: support for the new API management
* Added: support for WP-CLI
* Updated: "Check for updates now" link is not vidible if extension is not activated

= 2.1.1 - 10-9-2015 =
* Fixed: bug with connecting to Uptime Robot
* Fixed: various PHP Warnings

= 2.1.0 - 9-18-2015 =
* Updated: refactored code to meet WordPress coding standards

= 2.0.0 - 8-18-2015 =
* Fixed: compatibility issue with WordPress 4.3 version

= 1.9.9 - 8-13-2015 =
* Fixed: bug with parsing JSON object
* Fixed: bug with incorrect settings for Team Control extension

= 1.9.8 =
* Fixed: bug with montors disappearing in case Uptime Robot connection times out

= 1.9.7 =
* Fixed: bug where some values from the extension were not showing in the Client Reports extension scheduled reports

= 1.9.6 =
* Fixed: parsing incorrect json format issue
* Updated: extension style

= 1.9.5 =
* Updated: quick start guide layout

= 1.9.4 =
* Fixed: potential Security issue - Internal Code Audit

= 1.9.2 =
* Fixed: bug where first monitor could not be added

= 1.9.1 =

* Fixed: PHP Warning

= 1.9 =
* Fixed: bug when adding different monitor types for the same monitor
* Fixed: bug when a monitor doesn't show in the list and it shows in Uptime Monitor API
* Added: "Reload Uptime Monitor Data" button
* Added: support for the upcoming extension

= 1.8.99 =
* Added: support for 50+ monitors

= 1.8.98 =
* Added: notification in case Uptime Robot doesn’t return data after adding a new monitor
* Added: individual Dashboard widget shows monitor only for the child site

= 1.8.97 =
* Fixed: fatal Error triggered by activating the extension
* Added: additional plugin info

= 1.8.96 =
* Fixed: PHP Notice
* Added: help content
* Added: redirection to the extension page after activation

= 1.8.95 =
* Updated: quick start guide

= 1.8.94 =
* Updated: CSS style

= 1.8.93 =
* Fixed: PHP warnings

= 1.8.92 =
* Fixed: PHP warnings

= 1.8.91 =
* Fixed: PHP warnings

= 1.8.9 =
* Fixed: php syntax errors and remove MVC header

= 1.8.8 =
* Fixed: issues with loading CSS files

= 1.8.3 =
* Fixed: error message issues

= 1.8.2 =
* Fixed: Unable to remove alert contact

= 1.8.1 =
* Fixed: multiple minor issues

= 1.8 =
* Fixed: cosmetic issues with the popup screen

= 1.7 =
* Fixed: Missing get alert contact which API Key contain only one contact. (cause: change from reponse of UptimeMonitor Server)

= 1.6 =
* Fixed: Error Message in User's AUM Settings and Widget (cause : updated version PHP from 5.3 to 5.4)

= 1.5 =
* Fixed: Monitor Name displayed wrong.

= 1.4 =
* Fixed: update WordPress to version 3.6, and extension not working properly

= 1.3 =
* Fixed: "Set time out for loading monitors from server". After 20 second , return message can not connect to server

= 1.2 =
* Fixed: deprecated use reference for MVC Model
* Fixed: "No permission to access files of plugin". Cause of this bug is rename folder of plugin when you upload not correctly
* Fixed: "Changing/Adding API Key not working"
* Fixed: issues about unable setting monitor or appear error when setting
* Fixed: dashboard screen options issue

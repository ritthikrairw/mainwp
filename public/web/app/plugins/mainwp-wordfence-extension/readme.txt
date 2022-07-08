=== MainWP Wordfence Extension ===
Plugin Name: MainWP Wordfence Extension
Plugin URI: https://mainwp.com
Description: The Wordfence Extension combines the power of your MainWP Dashboard with the popular WordPress Wordfence Plugin. It allows you to manage Wordfence settings, Monitor Live Traffic and Scan your child sites directly from your dashboard. Requires MainWP Dashboard plugin.
Version: 4.0.6
Author: MainWP
Author URI: https://mainwp.com

== Installation ==
1. Please install plugin "MainWP Dashboard" and active it before install MainWP Wordfence Extension plugin (get the MainWP Dashboard plugin from url:https://mainwp.com/)
2. Upload the `mainwp-wordfence-extension` folder to the `/wp-content/plugins/` directory
3. Activate the Mainwp Wordfence Extension plugin through the 'Plugins' menu in WordPress

== Screenshots ==
1. Enable or Disable extension on the "Extensions" page in the dashboard

== Changelog ==

= 4.0.6 - 6-21-2022 =
* Added: WP Nonce verification to the Go to WP Admin request for additional security

= 4.0.5 - 12-8-2021 =
* Updated: Support for new Wordfence options
* Updated: PHP 8 compatibility

= 4.0.4 - 6-7-2021 =
* Fixed: An issue with displaying stats in the extension widget

= 4.0.3 - 12-11-2020 =
* Updated: Support for new Wordfence options

= 4.0.2 - 10-29-2020 =
* Fixed: An issue with displaying some settings options

= 4.0.1.1 - 9-10-2020 =
* Updated: MainWP Dashboard 4.1 compatiblity

= 4.0.1 - 2-28-2020 =
* Fixed: an issue with showing live traffic data
* Fixed: JS error that occurs on showing scan results
* Fixed: an issue with scanning child sites
* Fixed: multiple cosmetic problems

= 4.0 - 8-28-2019 =
* Updated: extension UI/UX redesign
* Updated: support for the MainWP 4.0

= 2.1 - 6-29-2018 =
* Fixed: compatibility issues with the latest Wordfence version
* Added: new Wordfence options
* Improved: PHP 7.2 compatibility

= 2.0 - 2-21-2018 =
* Fixed: Wordfence 7 compatibility issues
* Added: support for the new Wordfence options

= 1.3 - 8-25-2017 =
* Fixed: an issue with line break in some settings form fields
* Fixed: an issue with displaying scan results
* Added: support for new Wordfence options

= 1.2 - 5-24-2017 =
* Fixed: an issue with displaying scan results
* Fixed: CSS conflict
* Added: support for new the Wordfence plugin options
* Added: the Save General Settings feature to the child site individual settings page
* Added: the Country Blocking feature
* Added: the Help tab
* Updated: the Blocking tab layout
* Updated: general layout
* Removed: the Performance section
* Removed: unreferenced images

= 1.1 - 8-2-2016 =
* Fixed: a couple of minor bugs
* Added: new Wordfence options
* Added: missing options from earlier versions of the Wordfence plugin
* Updated: minor CSS update

= 1.0 - 3-10-2016 =
* Fixed: Error popup loop issue on the Security Scan page
* Fixed: Translation issue
* Fixed: Bug with the call to a member function get_child_file() on a non-object
* Fixed: Strip slashes in file name issue
* Fixed: Bug with incorrect "last scan" timestamp
* Added: Support for WP-CLI
* Added: Support for the new Add Site process
* Added: An auto update warning if the extension is not activated
* Added: Support for the new API management
* Added: Bulk import settings feature
* Added: Export/Import settings for individual site
* Added: Missing options
* Updated: "Check for updates now" link is not vidible if extension is not activated
* Updated: Set default value for individual settings
* Updated: Refactored code to meet WordPress coding standards
* Updated: WordFence options in separate tab for individual site

= 0.0.7 - 9-4-2015 =
* Fixed: Bug with saving IP addresses to the ignore rule for the Live Traffic View feature

= 0.0.6 =
* Fixed: Endless loop bug on the individual site security page in case the Wordfence plugin is not installed on the child site

= 0.0.5 =
* Updated: Quick Start Guide layout

= 0.0.4 =
* Added: Support for the API Manager

= 0.0.3 =
* Fixed: Bug for extension showing a white screen after activation

= 0.0.2 =
* Added: Network Live Traffic feature
* Added: Support for Premium (paid) Wordfence features
* Added: Block/Unblock IP across entire MainWP network feature
* Updated: Licence options, updated to allow to add different API keys to child sites
* Updated: CSS and layout

= 0.0.1 =
* First version

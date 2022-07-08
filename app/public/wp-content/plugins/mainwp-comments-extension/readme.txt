=== Comments Extension ===
Plugin Name: MainWP Comments Extension
Plugin URI: https://mainwp.com
Description: MainWP Comments Extension is an extension for the MainWP plugin that enables you to manage comments on your child sites.
Version: 4.0.6
Author: MainWP
Author URI: https://mainwp.com
Icon URI:

== Installation ==
1. Please install plugin "MainWP Dashboard" and active it before install Comments Extension plugin (get the MainWP Dashboard plugin from url:https://mainwp.com/)
1. Upload the `mainwp-comments-extension` folder to the `/wp-content/plugins/` directory
1. Activate the Comments Extension plugin through the 'Plugins' menu in WordPress

== Screenshots ==
1. Enable or Disable extension on the "Extensions" page in the dashboard

== Changelog ==

= 4.0.6 - 6-21-2022 =
* Added: WP Nonce verification to the Go to WP Admin request for additional security

= 4.0.5 - 6-1-2022 =
* Fixed: Action menu layout problem in the Recent Comments widget

= 4.0.4 - 5-6-2021 =
* Fixed: The `session_start(): Session cannot be started after headers have already been sent` PHP Warning

= 4.0.3 - 5-5-2021 =
* Updated: Multiple usability and cosmetic improvements

= 4.0.2.1 - 9-10-2020 =
* Updated: MainWP Dashboard 4.1 compatiblity

= 4.0.2 - 1-20-2020 =
* Fixed: issue with fetching comments from child sites

= 4.0.1 - 9-23-2019 =
* Fixed: issue with selecting comments in the Comments table
* Fixed: an issue with triggering Comments management actions
* Fixed: incorrect response message after spamming a comment
* Fixed: trash comment action in the Recent Comments widget
* Added: option to hide the extension widget on the Overview page
* Added: saveState property to the sites list
* Added: colReorder property to the sites list
* Added: horizontal scroll to the sites list
* Updated: multiple cosmetic updates

= 4.0 - 8-27-2019 =
* Updated: extension UI/UX redesign
* Updated: support for the MainWP 4.0

= 1.3 = 8-11-2017
* Fixed: multiple translation issues
* Fixed: incorrect URLs
* Added: help tab and content
* Updated: general extension style
* Updated: CSS markup
* Removed: unused files
* Removed: unused code

= 1.2 = 7-22-2016
* Fixed: A few smaller usability issues
* Updated: Extension CSS and Layout to match overall MainWP UI

= 1.1 = 2-17-2016
* Added: Support for WP-CLI
* Added: Support for the new API management
* Updated: Refactored code to meet WordPress coding standards
* Updated: "Check for updates now" link is not vidible if extension is not activated

= 1.0 = 12-11-2015
* Fixed: MainWP Dashboard 3.0 compatibility issue
* Updated: Icon URI

= 0.0.7 = 4-17-2015
* Updated: Quick start guide layout

= 0.0.6 =
* Fixed: The wpMandrill plugin conflict

= 0.0.3 =
* Fix submenu link

= 0.0.2 =
* Wordpress 3.9 changes

= 0.0.1 =
* First version

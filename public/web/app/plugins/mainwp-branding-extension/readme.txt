=== Child Branding Extension ===
Plugin Name: MainWP White Label Extension
Plugin URI: https://mainwp.com
Description: The MainWP White Label extension allows you to alter the details of the MainWP Child Plugin to reflect your companies brand or completely hide the plugin from the installed plugins list.
Version: 4.1.1
Author: MainWP
Author URI: https://mainwp.com
Documentation URI: https://kb.mainwp.com/docs/category/mainwp-extensions/white-label/


== Installation ==
1. Please install plugin "MainWP Dashboard" and active it before install Branding Extension plugin (get the MainWP Dashboard plugin from url:https://mainwp.com/)
2. Upload the `mainwp-branding-extension` folder to the `/wp-content/plugins/` directory
3. Activate the Branding Extension plugin through the 'Plugins' menu in WordPress

== Screenshots ==
1. Enable or Disable extension on the "Extensions" page in the dashboard

== Changelog ==

= 4.4.1 - 12-8-2021 =
* Updated: PHP 8 compatibility

= 4.1 - 9-28-2021 =
* Added: Progress bar to the progress indicator modal
* Added: Info messages to each extension page
* Added: Process response for each indicator icon in the progress modal
* Added: Tooltips to all settings fields
* Updated: Extension name changed to MainWP White Label Extension
* Updated: Renamed page navigation menu items
* Updated: Reworded error messages
* Updated: Moved the Save Settings button to left
* Updated: Form field labels letter capitalization
* Updated: Moved the "Visually hide the MainWP Child plugin" option to the top of the page

= 4.0.2.1 - 9-10-2020 =
* Updated: MainWP Dashboard 4.1 compatibility

= 4.0.2 - 8-28-2020 =
* Fixed: Replaced .live() with .on() to fix jQuery version compatibility

= 4.0.1 - 5-5-2020 =
* Fixed: JSON encoding issue

= 4.0 - 8-27-2019 =
* Updated: extension UI/UX redesign
* Updated: support for the MainWP 4.0

= 2.1.1 - 6-29-2018 =
* Fixed: an issue with creating missing database tables
* Improved: support for the PHP 7.2

= 2.1 - 5-25-2018 =
* Added: the new "Custom login image URL" option

= 2.0 - 2-28-2018 =
* Fixed: multiple translation issues
* Added: the mainwp_branding_settings_before_save_to_sites register_activation_hook
* Updated: general extension style

= 1.1 - 2-17-2016 =
* Fixed: Bug with setting a custom login page logo on some setups
* Added: Support for the new Add Site process
* Added: Support for WP-CLI
* Added: An auto update warning if the extension is not activated
* Added: Support for the new API management
* Added: New option "Disable theme switching"
* Updated: Branding options moved to a separate tab
* Updated: "Check for updates now" link is not visible if extension is not activated

= 1.0 - 10-16-2015 =
* Updated: Refactored code to meet WordPress coding standards

= 0.1.0 =
* Updated: Quick start guide layout

= 0.0.9 =
* Fixed: Potential Security issue - Internal Code Audit

= 0.0.8 =
* Fixed: Hiding the MainWP Restore (Clone) page
* Fixed: Hiding the MainWP Settings page
* Added: Hiding the MainWP Server Information page
* Tweaked: Features order in the Child Site Remove / Disable Functions box

= 0.0.7 =
* Fixed: Download Failed issue caused by the .htaccess file
* Fixed: Message in the Branding Settings box

= 0.0.6 =
* Added: Support for the API Manager

= 0.0.5 =

* Added: Reset Button
* Added: Hide Nag Updates feature
* Added: Hide Screen Options feature
* Added: Hide Help Box feature
* Added: Hide Post Meta Boxes features
* Added: Hide Page Meta Boxes feature
* Added: Additional Plugin info
* Added: Toggle functionality to all option boxes (with remembering the opened/closed state)
* Updated: CSS/HTML Layout
* Updated: Options tooltips
* Fixed: 1 PHP Warning
* Fixed: Child plugin options not completely hidden

= 0.0.4 =
* Typo fixed
* Added redirection to the Extensions page after activating the extension
* Contact Form ("From Email") edited to identify current user (requires the child plugin 1.0.0)

= 0.0.3 =
* CSS Update

= 0.0.2 =
* Add more WordPress Branding Options section
- Added ability to remove the Permalinks Menu
- Added ability to Rebrand WordPress backend on child sites
   -- Change Login Image
   -- Change Favicon
   -- Remove WordPress Widgets
   -- Add Global Footer Content
   -- Add Admin Footer Content
   -- Change Site Generator
   -- Custom Login CSS
   -- Custom Admin CSS
   -- Text Replacement
- Added ability to display the Support button in Top Admin Bar and/or the Admin Menu

= 0.0.1 =
* First version

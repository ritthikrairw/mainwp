=== MainWP Lighthouse Extension ===
/**
 * Plugin Name: MainWP Lighthouse Extension
 * Plugin URI: https://mainwp.com
 * Description: MainWP Lighthouse Extension is used for measuring the quality of your websites. It uses the Google PageSpeed Insights API to audit performance, accessibility and search engine optimization of your WordPress sites.
 * Version: 4.0.2
 * Author: MainWP
 * Author URI: https://mainwp.com
 * Documentation URI: https://mainwp.com/help/category/mainwp-extensions/lighthouse/
 */

== Installation ==
1. Please install plugin the MainWP Dashboard and active it before install MainWP Lighthouse Extension plugin (get the MainWP Dashboard plugin from url:https://mainwp.com/)
2. Upload the `mainwp-lighthouse-extension` folder to the `/wp-content/plugins/` directory
3. Activate the MainWP Lighthouse Extension plugin through the 'Plugins' menu in WordPress

== Screenshots ==
1. Enable or Disable extension on the "Extensions" page in the dashboard

== Changelog ==

= 4.0.2 - 6-21-2022 =
- Added: WP Nonce verification to the Go to WP Admin request for additional security

= 4.0.1 - 6-1-2022 =
* Added: Responsive feature to the audits table
* Fixed: An issue with running audits via MainWP REST API
* Fixed: Lighthouse report page layout on mobile device
* Updated: Bulk actions menu size on the Extension page
* Updated: Match notification and audit schedule

= 4.0 - 12-20-2021 =
* Updated: PHP 8 compatibility

= 4.0-beta2 - 11-18-2021 =
* Fixed: Multiple typos in info messages and option labels
* Fixed: An issue with rendering extension widget on 3 column layout
* Fixed: An issue with loading site ID in URLs
* Fixed: An issue with showing audit details items
* Fixed: Multiple incorrect URLs in info messages
* Fixed: Incorrect table headers in the extension widget
* Added: All audits filter in audit report page
* Added: Option to hide/show columns in the Lighthouse dashboard page
* Added: Option to show/hide the Lighthouse widget on the Overview page
* Updated: PHP 8 compatibility
* Updated: Failed audit icon
* Updated: Running audit process indicator to show more details

= 4.0-beta1 - 11-10-2021 =
* Beta 1 release

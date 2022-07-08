=== MainWP Pro Reports Extension ===
Plugin Name: MainWP Pro Reports Extension
Plugin URI: https://mainwp.com
Description: MainWP Pro Reports Extension allows you to generate pro reports for your child sites. Requires MainWP Dashboard.
Version: 4.0.10
Author: MainWP
Author URI: https://mainwp.com

== Installation ==

1. Please install plugin "MainWP Dashboard" and active it before install MainWP Pro Reports Extension plugin (get the MainWP Dashboard plugin from url:https://mainwp.com/)
2. Upload the `mainwp-pro-reports-extension` folder to the `/wp-content/plugins/` directory
3. Activate the MainWP Pro Reports Extension plugin through the 'Plugins' menu in WordPress

== Screenshots ==
1. Enable or Disable extension on the "Extensions" page in the dashboard

== Changelog ==

= 4.0.10 - 6-21-2022 =
* Added: WP Nonce verification to the Go to WP Admin request for additional security

= 4.0.9 - 5-6-2022 =
* Fixed: An issue with displaying Domain Monitor data in reports
* Fixed: An issue with displaying Lighthouse data in reports


= 4.0.8 - 2-25-2022 =
* Updated: php-css-parser library

= 4.0.7 - 12-20-2021 =
* Updated: DOMPDF library
* Updated: PHP 8 compatibility
* Updated: Support for the Lighthouse extension tokens

= 4.0.6 - 6-11-2021 =
* Updated: General performance improvements

= 4.0.5 - 4-28-2021 =
* Fixed: An issue with sending reports for specific setups
* Added: 'mainwp_pro_reports_get_tokens_value' hook for extra logging
* Added: 'mainwp-reports-ga-chart-format-date' hook to allow date format change on GA chart
* Added: Support for Virusdie tokens
* Updated: General performance improvements

= 4.0.4 - 1-15-2021 =
* Updated: Support for default WP datepicker

= 4.0.3 - 12-11-2020 =
* Fixed: An issue with sending scheduled reports
* Fixed: An issue with selecting a date range
* Fixed: An issue with a few incorrect tokens in default report templates
* Added: mainwp_pro_reports_filter_report_content hook

= 4.0.2.1 - 9-10-2020 =
* Updated: MainWP Dashboard 4.1 compatiblity

= 4.0.2 - 8-28-2020 =
* Fixed: An issue with displaying WooCommerce Top Seller product in reports

= 4.0.1 - 7-28-2020 =
* Fixed: an error with sending scheduled reports
* Fixed: compatibility with the Sucuri scan hook
* Added: support to fetch Site ID in report templates
* Added: mainwp_pro_reports_generate_report_content hook to support tokens in MainWP Dashboard notifications
* Added: mainwp_pro_reports_get_site_tokens hook to support tokens in MainWP Dashboard notifications
* Added: mainwp_pro_reports_generate_content hook to support tokens in MainWP Dashboard notifications
* Added: mainwp_pro_reports_fetch_remote_post_data hook for fetching post data
* Added: mainwp_pro_reports_send_local_time hook to allow sending reports in loca
* Updated: send email process to send reports at localtime.
* Updated: DOMPDF library version

= 4.0 - 1-22-2020 =
* Initial release

= 4.0-beta1 - 12-9-2019 =
* Beta1 release

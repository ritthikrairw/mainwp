<?php

/**
 * MainWP Database Installer
 *
 * Installs Database.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

/**
 * Class MainWP_AUM_DB_Install
 *
 * Installs Database.
 */
class MainWP_AUM_DB_Install extends MainWP_AUM_DB_Base {

	/**
	 * Private variable to hold the database version info.
	 *
	 * @var string DB version info.
	 */
	protected $current_db_version = '7.9';

	/**
	 * Private static variable to hold the single instance of the class.
	 *
	 * @static
	 *
	 * @var mixed Default null
	 */
	private static $instance = null;

	/**
	 * Create public static instance.
	 *
	 * @static
	 *
	 * @return MainWP_DB
	 */
	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		self::$instance->test_connection();

		return self::$instance;
	}

	/**
	 * Method: install()
	 *
	 * Installs Database tables.
	 */
	public function install() {

		global $wpdb;

		$currentVersion = get_option( 'mainwp_aum_current_db_version' );

		if ( $currentVersion == $this->current_db_version ) {
			return; }

		$charset_collate = $wpdb->get_charset_collate();

		$sql = array();

		$tbl = 'CREATE TABLE `' . $this->table_name( 'monitor_urls' ) . '` (
`url_id` int(11) NOT NULL AUTO_INCREMENT,
`url_name` varchar(256) NOT NULL,
`url_address` varchar(100) NOT NULL,
`monitor_id` varchar(100) NOT NULL,
`dashboard` tinyint(1) NOT NULL DEFAULT 0,
`service` varchar(20) NOT NULL default "",
`lastupdate` int(11) NOT NULL DEFAULT 0,
 KEY idx_service_monitor_id (`service`, monitor_id)';
		if ( '' == $currentVersion || version_compare( $currentVersion, '4.3', '<' ) ) {
					$tbl .= ',
PRIMARY KEY  (`url_id`)  '; }
		$tbl .= ') ' . $charset_collate;

		$sql[] = $tbl;

		$tbl = 'CREATE TABLE `' . $this->table_name( 'url_options' ) . '` (
`option_id` int(11) NOT NULL AUTO_INCREMENT,
`url_id` int(11) NOT NULL,
`option_name` varchar(200) NOT NULL,
`option_value` longtext NOT NULL DEFAULT ""';

		if ( '' == $currentVersion || version_compare( $currentVersion, '4.2', '<' ) ) {
					$tbl .= ',
PRIMARY KEY  (`option_id`)  '; }
		$tbl .= ') ' . $charset_collate;

		$sql[] = $tbl;

		$tbl = 'CREATE TABLE `' . $this->table_name( 'stats_uptimerobot' ) . '` (
`event_id` int(11) NOT NULL AUTO_INCREMENT,
`url_id` int(11) NOT NULL,
`type` tinyint(3) NOT NULL DEFAULT 0,
`duration` int(11) NOT NULL,
`code` int(5) NOT NULL DEFAULT 0,
`detail` varchar(256) NOT NULL,
`event_datetime_gmt` datetime NOT NULL';
		if ( '' == $currentVersion ) {
					$tbl .= ',
PRIMARY KEY  (`event_id`)  '; }
		$tbl .= ') ' . $charset_collate;

		$sql[] = $tbl;

		$tbl = 'CREATE TABLE `' . $this->table_name( 'stats_site24x7' ) . '` (
`event_id` int(11) NOT NULL AUTO_INCREMENT,
`url_id` int(11) NOT NULL,
`status` tinyint(3) NOT NULL DEFAULT 0,
`duration` int(11) NOT NULL DEFAULT 0,
`response_time` int(11) NOT NULL DEFAULT 0,
`reason` text NOT NULL,
`last_polled_datetime_gmt` datetime NOT NULL';

		if ( '' == $currentVersion || version_compare( $currentVersion, '5.2', '<' ) ) {
			$tbl .= ',
	PRIMARY KEY  (`event_id`) ';
		}
		$tbl .= ') ' . $charset_collate;

		$sql[] = $tbl;

		$tbl = 'CREATE TABLE `' . $this->table_name( 'stats_nodeping' ) . '` (
`event_id` int(11) NOT NULL AUTO_INCREMENT,
`url_id` int(11) NOT NULL,
`status` tinyint(3) NOT NULL DEFAULT 0,
`response` varchar(100) NOT NULL,
`location` text NOT NULL DEFAULT "",
`runtime` int(11) NOT NULL DEFAULT 0,
`result` tinyint(3) NOT NULL DEFAULT 1,
`message` text NOT NULL,
`check_timestamp` bigint(20) unsigned NOT NULL DEFAULT 0';

		if ( '' == $currentVersion || version_compare( $currentVersion, '5.7', '<' ) ) {
				$tbl .= ',
			PRIMARY KEY  (`event_id`) ';
		}
				$tbl .= ') ' . $charset_collate;

				$sql[] = $tbl;

		$tbl = 'CREATE TABLE `' . $this->table_name( 'stats_betteruptime' ) . '` (
		`event_id` int(11) NOT NULL AUTO_INCREMENT,
		`url_id` int(11) NOT NULL,		
		`incident_id` int(11) NOT NULL,
		`incident_name` varchar(256) NOT NULL,
		`started_at` int(11) NOT NULL DEFAULT 0,
		`resolved_at` int(11) NOT NULL DEFAULT 0,
		`acknowledged_at` int(11) NOT NULL DEFAULT 0,		
		`cause` text NOT NULL';

		if ( '' == $currentVersion || version_compare( $currentVersion, '7.0', '<' ) ) {
				$tbl .= ',
			PRIMARY KEY  (`event_id`) ';
		}
		$tbl .= ') ' . $charset_collate;

		$sql[] = $tbl;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		$this->check_update( $currentVersion );

		update_option( 'mainwp_aum_current_db_version', $this->current_db_version );
	}

	/**
	 * Method: check_update()
	 *
	 * Checks for database udpate.
	 *
	 * @param string $version Version number.
	 */
	public function check_update( $version = false ) {
		if ( empty( $version ) || version_compare( $version, '6.8', '<' ) ) {
			$old_aum_options = get_option( 'advanced_uptime_monitor_extension' ); // old version options.

			if ( is_array( $old_aum_options ) ) {
				$save_opts = array();
				if ( ! empty( $old_aum_options['api_key'] ) ) {
					$save_opts['api_key'] = $old_aum_options['api_key'];
				}
				if ( ! empty( $old_aum_options['list_notification_contact'] ) ) {
					$save_opts['list_notification_contact'] = $old_aum_options['list_notification_contact'];
				}
				if ( ! empty( $old_aum_options['uptime_default_notification_contact_id'] ) ) {
					$save_opts['uptime_default_notification_contact_id'] = $old_aum_options['uptime_default_notification_contact_id'];
				}

				$enabled_service = get_option( 'mainwp_aum_enabled_service', false );
				if ( ! empty( $save_opts ) ) {
					MainWP_AUM_UptimeRobot_API::instance()->update_options( $save_opts );
					if ( empty( $enabled_service ) ) {
						update_option( 'mainwp_aum_enabled_service', 'uptimerobot' );
					}
				}
			}

			delete_option( 'advanced_uptime_monitor_extension' );

		} elseif ( ! empty( $version ) ) {
			global $wpdb;
		}
	}

}

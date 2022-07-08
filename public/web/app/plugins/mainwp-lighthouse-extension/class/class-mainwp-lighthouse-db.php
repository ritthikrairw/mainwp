<?php
namespace MainWP\Extensions\Lighthouse;

class MainWP_Lighthouse_DB {

	private $mainwp_lighthouse_db_version = '3.3';
	private $table_prefix;

	/**
	 * Static variable to hold the single instance of the class.
	 *
	 * @static
	 *
	 * @var mixed Default null
	 */
	private static $instance = null;

	/**
	 * Get Instance
	 *
	 * Creates public static instance.
	 *
	 * @static
	 *
	 * @return MainWP_Lighthouse_DB
	 */
	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	// Constructor
	function __construct() {
		global $wpdb;
		$this->table_prefix = $wpdb->prefix . 'mainwp_';
	}

	function init() {

		$this->install();
	}

	function table_name( $suffix ) {
		return $this->table_prefix . $suffix;
	}

	// Support old & new versions of WordPress (3.9+)
	public static function use_mysqli() {
		/** @var $wpdb wpdb */
		if ( ! function_exists( 'mysqli_connect' ) ) {
			return false;
		}

		global $wpdb;
		return ( $wpdb->dbh instanceof mysqli );
	}

	// Installs new DB
	function install() {
		global $wpdb;
		$currentVersion = get_site_option( 'mainwp_lighthouse_db_version' );
		if ( $currentVersion == $this->mainwp_lighthouse_db_version ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();
		$sql             = array();

		$tbl = 'CREATE TABLE `' . $this->table_name( 'lighthouse' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`URL` text NULL,
`site_id` int(11) NOT NULL,
`response_code` int(10) DEFAULT NULL,
`desktop_score` int(10) DEFAULT NULL,
`desktop_seo_score` int(10) DEFAULT NULL,
`desktop_accessibility_score` int(10) DEFAULT NULL,
`desktop_best_practices_score` int(10) DEFAULT NULL,
`desktop_lab_data` longtext,
`desktop_others_data` longtext,
`desktop_last_modified` int(11) NOT NULL DEFAULT 0,
`mobile_score` int(10) DEFAULT NULL,
`mobile_seo_score` int(10) DEFAULT NULL,
`mobile_accessibility_score` int(10) DEFAULT NULL,
`mobile_best_practices_score` int(10) DEFAULT NULL,
`mobile_lab_data` longtext,
`mobile_others_data` longtext,
`mobile_last_modified` int(11) NOT NULL DEFAULT 0,
`type` varchar(200) DEFAULT NULL,
`ignored` tinyint(1) DEFAULT 0,
`blacklist` tinyint(1) DEFAULT 0,
`force_recheck` int(1) NOT NULL,
`last_alert` int(11) DEFAULT 0,
`strategy` varchar(32) NOT NULL DEFAULT "",
`use_schedule` tinyint(1) NOT NULL DEFAULT 0,
`settings` text NOT NULL DEFAULT "",
`status` tinyint(1) DEFAULT 0,
`override` tinyint(1) NOT NULL DEFAULT 0';
		if ( '' == $currentVersion ) {
			$tbl .= ',
PRIMARY KEY  (`id`)  '; }
		$tbl  .= ') ' . $charset_collate;
		$sql[] = $tbl;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		update_option( 'mainwp_lighthouse_db_version', $this->mainwp_lighthouse_db_version );

		$this->check_update( $currentVersion, $this->mainwp_lighthouse_db_version );
	}

	public function check_update( $currentVersion, $newVersion ) {
		global $wpdb;
		// change columns.
		if ( version_compare( $currentVersion, '2.8', '<' ) && version_compare( $currentVersion, '1.8', '>' ) ) {
			$wpdb->query( 'ALTER TABLE ' . $this->table_name( 'lighthouse' ) . ' CHANGE COLUMN `desktop_last_modified` `desktop_last_modified` int(11) NOT NULL DEFAULT 0' );
			$wpdb->query( 'ALTER TABLE ' . $this->table_name( 'lighthouse' ) . ' CHANGE COLUMN `mobile_last_modified` `mobile_last_modified` int(11) NOT NULL DEFAULT 0' );
		}

		if ( version_compare( $currentVersion, '2.9', '<' ) && version_compare( $currentVersion, '2.2', '>' ) ) {
			$wpdb->query( 'ALTER TABLE ' . $this->table_name( 'lighthouse' ) . ' CHANGE COLUMN ID id  int(11) NOT NULL AUTO_INCREMENT' );
		}

		if ( version_compare( $currentVersion, '3.2', '<' ) && version_compare( $currentVersion, '2.8', '>' ) ) {
			$local_timestamp = MainWP_Lighthouse_Utility::get_timestamp();
			$today_end   = strtotime( date("Y-m-d 23:59:59", $local_timestamp ) ) ; // phpcs:ignore -- to localtime.
			if ( wp_next_scheduled( 'mainwp_lighthouse_cron_alert' ) ) {
				wp_clear_scheduled_hook( 'mainwp_lighthouse_cron_alert' );
			}
		}

		if ( version_compare( $currentVersion, '3.3', '<' ) && version_compare( $currentVersion, '3.0', '>' ) ) {
			$local_timestamp = MainWP_Lighthouse_Utility::get_timestamp();
			$today_end   = strtotime( date("Y-m-d 23:59:59", $local_timestamp ) ) ; // phpcs:ignore -- to localtime.
			if ( wp_next_scheduled( 'mainwp_lighthouse_action_cron_start' ) ) {
				wp_clear_scheduled_hook( 'mainwp_lighthouse_action_cron_start' );
			}
		}
	}

	public function update_lighthouse( $setting ) {
		/** @var $wpdb wpdb */
		global $wpdb;
		$id      = isset( $setting['id'] ) ? $setting['id'] : 0;
		$site_id = isset( $setting['site_id'] ) ? $setting['site_id'] : '';
		if ( ! empty( $id ) ) {
			$wpdb->update( $this->table_name( 'lighthouse' ), $setting, array( 'id' => intval( $id ) ) );
			return $this->get_lighthouse_by( 'id', $id );
		} elseif ( ! empty( $site_id ) ) {
			$current = $this->get_lighthouse_by( 'site_id', $site_id );
			if ( ! empty( $current ) ) {
				$wpdb->update( $this->table_name( 'lighthouse' ), $setting, array( 'site_id' => $site_id ) );
				return $this->get_lighthouse_by( 'site_id', $site_id );
			} else {
				if ( $wpdb->insert( $this->table_name( 'lighthouse' ), $setting ) ) {
					return $this->get_lighthouse_by( 'id', $wpdb->insert_id );
				}
			}
		} else {
			unset( $setting['id'] );
			if ( $wpdb->insert( $this->table_name( 'lighthouse' ), $setting ) ) {
				return $this->get_lighthouse_by( 'id', $wpdb->insert_id );
			}
		}
		return false;
	}

	public function delete_lighthouse( $by = 'id', $value = null ) {
		global $wpdb;

		if ( empty( $value ) ) {
			return false;
		}

		if ( 'id' !== $by && 'site_id' !== $by ) {
			return false;
		}

		if ( 'id' == $by ) {
			if ( $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'lighthouse' ) . ' WHERE id=%d ', $value ) ) ) {
				return true;
			}
		} else {
			if ( $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'lighthouse' ) . ' WHERE site_id=%d ', $value ) ) ) {
				return true;
			}
		}
		return false;
	}

	public function get_lighthouse_by( $by = 'id', $value = null, $obj = OBJECT ) {
		global $wpdb;

		if ( 'all' == $by ) {
			$sql = 'SELECT * FROM ' . $this->table_name( 'lighthouse' ) . ' WHERE 1 = 1';
			return $wpdb->get_results( $sql );
		}

		$sql = '';
		if ( 'id' == $by && is_numeric( $value ) ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'lighthouse' ) . ' WHERE `id`=%d ', $value );
		} elseif ( 'site_id' == $by && is_numeric( $value ) ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'lighthouse' ) . " WHERE `site_id` = '%d' ", $value );
		} elseif ( 'id' == $by && is_array( $value ) ) {
			$site_ids = array_map( 'intval', $value );
			$site_ids = array_filter( $site_ids );
			if ( empty( $site_ids ) ) {
				$site_ids = implode( ',', $site_ids );
				$sql      = 'SELECT * FROM ' . $this->table_name( 'lighthouse' ) . ' WHERE `site_id` IN (' . $site_ids . ')';
			}
		}

		$result = null;
		if ( ! empty( $sql ) ) {
			if ( OBJECT == $obj ) {
				$result = $wpdb->get_row( $sql, OBJECT );
				if ( is_object( $result ) && property_exists( $result, 'settings' ) ) {
					$result->settings = json_decode( $result->settings, true );
				}
			} else {
				$result = $wpdb->get_row( $sql, ARRAY_A );
				if ( is_array( $result ) && isset( $result['settings'] ) ) {
					$result['settings'] = json_decode( $result['settings'], true );
				}
			}
		}
		return $result;
	}

	public function get_site_settings_by( $by = 'id', $value = null, $field = '', $default = '' ) {

		$settings = $this->get_lighthouse_by( $by, $value, ARRAY_A );
		if ( 'all' == $field ) {
			return $settings;
		}
		if ( isset( $settings[ $field ] ) ) {
			return $settings[ $field ];
		}
		return $default;
	}

	public function get_lighthouse_info( $by, $value, $strategy ) {
		global $wpdb;
		$existing_url_info = false;

		$where = '';

		if ( 'id' == $by ) {
			$where = "id =  $value";
		} elseif ( 'site_id' == $by ) {
			$where = "site_id =  $value";
		}

		if ( ! empty( $where ) ) {
			$existing_url_info = $wpdb->get_row(
				"SELECT {$strategy}_last_modified, force_recheck, id, site_id, settings, override
				FROM " . $this->table_name( 'lighthouse' ) . '
				WHERE ' . $where
			);
			return $existing_url_info;
		}
		return false;
	}

	public function save_bad_request( $item_id, $message = true ) {
		global $wpdb;
		if ( $item_id ) {
			if ( $wpdb->update( $this->table_name( 'lighthouse' ), array( 'blacklist' => 1 ), array( 'id' => $item_id ) ) ) {
				if ( $message ) {
					MainWP_Lighthouse_Utility::get_instance()->set_option( 'new_ignored_items', true );
				}
			}
		}
	}

	public function get_existing_urls( $excl_blacklist = false ) {
		global $wpdb;
		$reports = $wpdb->get_results(
			'
				SELECT URL, site_id, id
				FROM ' . $this->table_name( 'lighthouse' ) . '
				WHERE ' . ( $excl_blacklist ? 'blacklist = 0 AND' : '' ) . ' ( ( use_schedule = 1 AND override = 1 ) OR ( override = 0 ) )  
			',
			ARRAY_A
		);
		return $reports;
	}

	public function start_schedule_recheck_all_pages() {

		global $wpdb;

		$site_ids = array();
		$websites = MainWP_Lighthouse_Admin::get_websites();
		if ( is_array( $websites ) ) {
			foreach ( $websites as $website ) {
				$site_ids[] = $website['id'];
			}
		}

		$count = 0;

		if ( is_array( $site_ids ) && ! empty( $site_ids ) ) {

			$count = count( $site_ids );

			$x            = 1;
			$where_clause = '';
			foreach ( $site_ids as $site_id ) {
				if ( $x < $count ) {
					$where_clause .= 'site_id = ' . $site_id . ' OR ';
				} else {
					$where_clause .= 'site_id = ' . $site_id;
				}
				$x++;
			}

			$wpdb->query(
				'
				UPDATE ' . $this->table_name( 'lighthouse' ) . " SET force_recheck = 1
				WHERE $where_clause
			"
			);
		}

		do_action( 'mainwp_lighthouse_run' );

		return array(
			'count'   => $count,
			'message' => $count . ' ' . __( 'URLs have been scheduled for a recheck. Depending on the number of URLs to check, this may take a while to complete.', 'mainwp-lighthouse-extension' ),
		);
	}

	protected function escape( $data ) {
		/** @var $wpdb wpdb */
		global $wpdb;
		if ( function_exists( 'esc_sql' ) ) {
			return esc_sql( $data );
		} else {
			return $wpdb->escape( $data );
		}
	}

	public function query( $sql ) {
		if ( null == $sql ) {
			return false;
		}
		/** @var $wpdb wpdb */
		global $wpdb;
		$result = @self::_query( $sql, $wpdb->dbh );

		if ( ! $result || ( 0 == @self::num_rows( $result ) ) ) {
			return false;
		}
		return $result;
	}

	public static function _query( $query, $link ) {
		if ( self::use_mysqli() ) {
			return mysqli_query( $link, $query );
		} else {
			return mysql_query( $query, $link );
		}
	}

	public static function fetch_object( $result ) {
		if ( self::use_mysqli() ) {
			return mysqli_fetch_object( $result );
		} else {
			return mysql_fetch_object( $result );
		}
	}

	public static function free_result( $result ) {
		if ( self::use_mysqli() ) {
			return mysqli_free_result( $result );
		} else {
			return mysql_free_result( $result );
		}
	}

	public static function data_seek( $result, $offset ) {
		if ( self::use_mysqli() ) {
			return mysqli_data_seek( $result, $offset );
		} else {
			return mysql_data_seek( $result, $offset );
		}
	}

	public static function fetch_array( $result, $result_type = null ) {
		if ( self::use_mysqli() ) {
			return mysqli_fetch_array( $result, ( null == $result_type ? MYSQLI_BOTH : $result_type ) );
		} else {
			return mysql_fetch_array( $result, ( null == $result_type ? MYSQL_BOTH : $result_type ) );
		}
	}

	public static function num_rows( $result ) {
		if ( self::use_mysqli() ) {
			return mysqli_num_rows( $result );
		} else {
			return mysql_num_rows( $result );
		}
	}

	public static function is_result( $result ) {
		if ( self::use_mysqli() ) {
			return ( $result instanceof mysqli_result );
		} else {
			return is_resource( $result );
		}
	}

	public function get_results_result( $sql ) {
		if ( null == $sql ) {
			return null; }
		/** @var $wpdb wpdb */
		global $wpdb;
		return $wpdb->get_results( $sql, OBJECT_K );
	}
}

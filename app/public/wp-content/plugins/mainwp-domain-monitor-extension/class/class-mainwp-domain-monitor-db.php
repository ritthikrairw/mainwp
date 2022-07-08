<?php
namespace MainWP\Extensions\Domain_Monitor;

class MainWP_Domain_Monitor_DB {

	private $mainwp_domain_monitor_db_version = '1.7';
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
	 * @return MainWP_Domain_Monitor_DB
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
		return ( $wpdb->dbh instanceof \mysqli );
	}

	// Installs new DB
	function install() {
		global $wpdb;
		$currentVersion = get_site_option( 'mainwp_domain_monitor_db_version' );

		$rslt = $this->get_instance()->query( "SHOW TABLES LIKE '" . $this->table_name( 'domain_monitor' ) . "'" );
		
		if ( 0 === self::num_rows( $rslt ) ) {
			$currentVersion = '';
		}

		if ( $currentVersion == $this->mainwp_domain_monitor_db_version ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();
		$sql             = array();

		$tbl = 'CREATE TABLE `' . $this->table_name( 'domain_monitor' ) . '` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`URL` text NULL,
			`site_id` int(11) NOT NULL,
			`last_alert` int(11) DEFAULT 0,
			`domain_name` text NULL,
			`registry_domain_id` text NULL,
			`registrar_whois_server` text NULL,
			`registrar_url` text NULL,
			`updated_date` text NULL,
			`creation_date` text NULL,
			`expiry_date` text NULL,
			`registrar` text NULL,
			`registrar_iana_id` text NULL,
			`registrar_abuse_contact_email` text NULL,
			`registrar_abuse_contact_phone` text NULL,
			`dnssec` text NULL,
			`domain_status_1` text NULL,
			`domain_status_2` text NULL,
			`domain_status_3` text NULL,
			`domain_status_4` text NULL,
			`domain_status_5` text NULL,
			`domain_status_6` text NULL,
			`name_server_1` text NULL,
			`name_server_2` text NULL,
			`name_server_3` text NULL,
			`name_server_4` text NULL,
			`overwrite` tinyint(1) NOT NULL DEFAULT 0,
			`settings` text NOT NULL DEFAULT "",
			`last_check` int(11) NOT NULL DEFAULT 0';
		if ( '' == $currentVersion ) {
			$tbl .= ', PRIMARY KEY  (`id`)  ';
		}
		$tbl  .= ') ' . $charset_collate;
		$sql[] = $tbl;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		update_option( 'mainwp_domain_monitor_db_version', $this->mainwp_domain_monitor_db_version );

		$this->check_update( $currentVersion, $this->mainwp_domain_monitor_db_version );
	}

	public function check_update( $currentVersion, $newVersion ) {
		global $wpdb;

	}

	/**
	 * Update Domain Monitor
	 *
	 * Updates the domain monitor data.
	 *
	 * @param array $setting Data to update.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function update_domain_monitor( $setting ) {
		/** @var $wpdb wpdb */
		global $wpdb;
		$id      = isset( $setting['id'] ) ? $setting['id'] : 0;
		$site_id = isset( $setting['site_id'] ) ? $setting['site_id'] : '';
		if ( ! empty( $id ) ) {
			if ( $wpdb->update( $this->table_name( 'domain_monitor' ), $setting, array( 'id' => intval( $id ) ) ) ) {
				return $this->get_domain_monitor_by( 'id', $id );
			}
		} elseif ( ! empty( $site_id ) ) {
			$current = $this->get_domain_monitor_by( 'site_id', $site_id );
			if ( ! empty( $current ) ) {
				if ( $wpdb->update( $this->table_name( 'domain_monitor' ), $setting, array( 'site_id' => $site_id ) ) ) {
					return $this->get_domain_monitor_by( 'site_id', $site_id );
				}
			} else {
				if ( $wpdb->insert( $this->table_name( 'domain_monitor' ), $setting ) ) {
					return $this->get_domain_monitor_by( 'id', $wpdb->insert_id );
				}
			}
		} else {
			unset( $setting['id'] );
			if ( $wpdb->insert( $this->table_name( 'domain_monitor' ), $setting ) ) {
				return $this->get_domain_monitor_by( 'id', $wpdb->insert_id );
			}
		}
		return false;
	}

	/**
	 * Delete Domain Monitor
	 *
	 * Deleted the domain monitor data when child site is removed from the MainWP Dashboard.
	 *
	 * @param string $by     Sets delete by method.
	 * @param int    $value  "by" value.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete_domain_monitor( $by = 'id', $value = null ) {
		global $wpdb;

		if ( empty( $value ) ) {
			return false;
		}

		if ( 'id' !== $by && 'site_id' !== $by ) {
			return false;
		}

		if ( 'id' == $by ) {
			if ( $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'domain_monitor' ) . ' WHERE id=%d ', $value ) ) ) {
				return true;
			}
		} else {
			if ( $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'domain_monitor' ) . ' WHERE site_id=%d ', $value ) ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get Domain Monitor
	 *
	 * Gets the domain monitor data.
	 *
	 * @param string $by     Sets delete by method.
	 * @param int    $value  "by" value.
	 * @param bool   $obj    Object or array?
	 *
	 * @return array Domains data.
	 */
	public function get_domain_monitor_by( $by = 'id', $value = null, $obj = OBJECT ) {
		global $wpdb;

		if ( 'all' == $by ) {
			$sql = 'SELECT * FROM ' . $this->table_name( 'domain_monitor' ) . ' WHERE 1 = 1';
			return $wpdb->get_results( $sql );
		}

		$sql = '';
		if ( 'id' == $by && is_numeric( $value ) ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'domain_monitor' ) . ' WHERE `id`=%d ', $value );
		} elseif ( 'site_id' == $by && is_numeric( $value ) ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'domain_monitor' ) . " WHERE `site_id` = '%d' ", $value );
		} elseif ( 'id' == $by && is_array( $value ) ) {
			$site_ids = array_map( 'intval', $value );
			$site_ids = array_filter( $site_ids );
			if ( empty( $site_ids ) ) {
				$site_ids = implode( ',', $site_ids );
				$sql      = 'SELECT * FROM ' . $this->table_name( 'domain_monitor' ) . ' WHERE `site_id` IN (' . $site_ids . ')';
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

	/**
	 * Get Domain Monitor Domains
	 *
	 * Gets the domain monitor data.
	 *
	 * @param bool   $obj    Object or array?
	 *
	 * @return array Domains data.
	 */
	public function get_domain_monitor_domains( $obj = OBJECT ) {
		global $wpdb;

		$sql = 'SELECT domain_name, expiry_date FROM ' . $this->table_name( 'domain_monitor' ) . ' WHERE 1 = 1';

		return $wpdb->get_results( $sql );
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

		if ( ! $result || ( false === @self::num_rows( $result ) ) ) {
			return false;
		}
		return $result;
	}

	public static function _query( $query, $link ) {
		if ( self::use_mysqli() ) {
			return \mysqli_query( $link, $query );
		} else {
			return \mysql_query( $query, $link );
		}
	}

	public static function fetch_object( $result ) {
		if ( self::use_mysqli() ) {
			return \mysqli_fetch_object( $result );
		} else {
			return \mysql_fetch_object( $result );
		}
	}

	public static function free_result( $result ) {
		if ( self::use_mysqli() ) {
			return \mysqli_free_result( $result );
		} else {
			return mysql_free_result( $result );
		}
	}

	public static function data_seek( $result, $offset ) {
		if ( self::use_mysqli() ) {
			return \mysqli_data_seek( $result, $offset );
		} else {
			return \mysql_data_seek( $result, $offset );
		}
	}

	public static function fetch_array( $result, $result_type = null ) {
		if ( self::use_mysqli() ) {
			return \mysqli_fetch_array( $result, ( null == $result_type ? MYSQLI_BOTH : $result_type ) );
		} else {
			return \mysql_fetch_array( $result, ( null == $result_type ? MYSQL_BOTH : $result_type ) );
		}
	}

	public static function num_rows( $result ) {
		if ( ! self::is_result( $result ) ) {
			return false;
		}		
		if ( self::use_mysqli() ) {
			return \mysqli_num_rows( $result );
		} else {
			return \mysql_num_rows( $result );
		}
	}

	public static function is_result( $result ) {
		if ( is_bool( $result ) ) {
			return $result;
		}
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

<?php
class MainWP_Staging_DB {

	private $mainwp_staging_db_version = '1.1';
	// Singleton
	private static $instance = null;
	private $table_prefix;

	static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	// Constructor
	function __construct() {
		global $wpdb;
		$this->table_prefix = $wpdb->prefix . 'mainwp_';
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
		$currentVersion = get_option( 'mainwp_staging_db_version' );
		if ( $currentVersion == $this->mainwp_staging_db_version ) {
			return; }
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = array();

		$tbl = 'CREATE TABLE `' . $this->table_name( 'wp_staging' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`site_id` int(11) NOT NULL,
`override` tinyint(1) NOT NULL DEFAULT 0,
`settings` longtext NOT NULL DEFAULT ""';
		if ( '' == $currentVersion ) {
					$tbl .= ',
PRIMARY KEY  (`id`)  '; }
		$tbl  .= ') ' . $charset_collate;
		$sql[] = $tbl;

		$tbl = 'CREATE TABLE `' . $this->table_name( 'wp_staging_sites' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`site_id` int(11) NOT NULL,
`clone_id` text NOT NULL,
`clone_url` text NOT NULL,
`staging_site_id` int(11) NOT NULL DEFAULT 0';
		if ( '' == $currentVersion ) {
					$tbl .= ',
PRIMARY KEY  (`id`) '; }
		$tbl  .= ') ' . $charset_collate;
		$sql[] = $tbl;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}
		update_option( 'mainwp_staging_db_version', $this->mainwp_staging_db_version );
	}

	public function get_staging( $site_ids = array() ) {
		global $wpdb;
		$str_ids = '';
		if ( is_array( $site_ids ) && count( $site_ids ) > 0 ) {
			$str_ids = implode( ',', $site_ids );
		}
		if ( ! empty( $str_ids ) ) {
			$sql = 'SELECT * FROM ' . $this->table_name( 'wp_staging' ) . ' WHERE `site_id` IN (' . $str_ids . ') ';
		} else {
			$sql = 'SELECT * FROM ' . $this->table_name( 'wp_staging' ) . ' WHERE 1 ';
		}
		return $wpdb->get_results( $sql );
	}

	public function get_staging_data( $site_ids = array() ) {
		$results = $this->get_staging( $site_ids );
		$return  = array();
		if ( count( $results ) > 0 ) {
			foreach ( $results as $item ) {
				$data                     = array();
				$data['override']         = $item->override;
				$return[ $item->site_id ] = $data;
			}
			return $return;
		}
		return $return;
	}

	public function get_setting_by( $by = 'id', $value = null ) {
		global $wpdb;

		$sql = '';
		if ( 'id' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'wp_staging' ) . ' WHERE `id`=%d ', $value );
		} elseif ( 'site_id' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'wp_staging' ) . ' WHERE `site_id` = %d ', $value );
		}

		$result = null;
		if ( ! empty( $sql ) ) {
			$result = $wpdb->get_row( $sql );
		}

		return $result;
	}

	public function get_count_stagings_of_sites() {
		global $wpdb;

		$sql = 'SELECT site_id, count(staging_site_id) as count FROM ' .
				$this->table_name( 'wp_staging_sites' ) .
				' WHERE staging_site_id != 0 GROUP BY site_id';

		$result = $wpdb->get_results( $sql );

		$data = array();
		if ( $result ) {
			foreach ( $result as $val ) {
				$data[ $val->site_id ] = $val->count;
			}
		}
		return $data;
	}

	public function get_stagings_of_site( $siteId ) {
		global $wpdb;
		if ( empty( $siteId ) ) {
			return false;
		}
		$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'wp_staging_sites' ) . ' WHERE `site_id` = %d ', $siteId );
		return $wpdb->get_results( $sql );
	}

	public function get_staging_site( $siteId, $cloneid ) {
		if ( empty( $siteId ) || empty( $cloneid ) ) {
			return false;
		}
		global $wpdb;
		$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'wp_staging_sites' ) . ' WHERE `site_id` = %d AND `clone_id` = %s', $siteId, $cloneid );
		return $wpdb->get_row( $sql );
	}

	public function update_staging_site( $data = array() ) {
		global $wpdb;

		if ( ! isset( $data['site_id'] ) || ! isset( $data['clone_id'] ) || empty( $data['site_id'] ) || empty( $data['clone_id'] ) ) {
			return false;
		}

		$siteid  = $data['site_id'];
		$cloneid = $data['clone_id'];

		$current = $this->get_staging_site( $siteid, $cloneid );
		if ( $current ) {
			unset( $data['site_id'] );
			unset( $data['clone_id'] );
			if ( $wpdb->update( $this->table_name( 'wp_staging_sites' ), $data, array( 'id' => $current->id ) ) ) {
				return $this->get_staging_site( $siteid, $cloneid );
			}
		} else {
			if ( $wpdb->insert( $this->table_name( 'wp_staging_sites' ), $data ) ) {
				return $this->get_staging_site( $siteid, $cloneid );
			}
		}
		return false;
	}

	public function update_setting( $setting ) {
		global $wpdb;
		if ( isset( $setting['id'] ) ) {
			$id = $setting['id'];
			unset( $setting['id'] );
			if ( $wpdb->update( $this->table_name( 'wp_staging' ), $setting, array( 'id' => intval( $id ) ) ) ) {
				return $this->get_setting_by( 'id', $id );
			}
		} elseif ( isset( $setting['site_id'] ) ) {
			$site_id = intval( $setting['site_id'] ); // site_id = 0 for general
			$current = $this->get_setting_by( 'site_id', $site_id );

			if ( $current ) {
				unset( $setting['site_id'] );
				if ( $wpdb->update( $this->table_name( 'wp_staging' ), $setting, array( 'site_id' => $site_id ) ) ) {
					return $this->get_setting_by( 'site_id', $site_id );
				}
			} else {
				if ( $wpdb->insert( $this->table_name( 'wp_staging' ), $setting ) ) {
					return $this->get_setting_by( 'id', $wpdb->insert_id );
				}
			}
		} elseif ( $wpdb->insert( $this->table_name( 'wp_staging' ), $setting ) ) {
			return $this->get_setting_by( 'id', $wpdb->insert_id );
		}

		return false;
	}

	public function get_setting_fields_by( $by = 'id', $value = null ) {
		$site_settings = $this->get_setting_by( $by, $value );
		$settings      = array();
		if ( $site_settings ) {
			$settings = unserialize( $site_settings->settings );
		}
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		return $settings;
	}

	public function delete_settings_by( $by = 'id', $value = null ) {
		global $wpdb;

		$sql = '';
		if ( 'id' == $by ) {
			$sql = $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'wp_staging' ) . ' WHERE `id`=%d ', $value );
		} elseif ( 'site_id' == $by ) {
			$sql = $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'wp_staging' ) . ' WHERE `site_id` = %d ', $value );
		}

		if ( ! empty( $sql ) ) {
			return $this->query( $sql );
		}

		return false;
	}

	public function delete_staging_site( $siteid, $cloneid = false, $staging_site_id = false ) {
		global $wpdb;

		if ( empty( $siteid ) && empty( $staging_site_id ) ) {
			return false;
		}

		if ( $staging_site_id ) {
			$sql = $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'wp_staging_sites' ) . ' WHERE `staging_site_id`=%d ', $staging_site_id );
		} else {
			$sql_clone = '';
			if ( ! empty( $cloneid ) ) {
				$sql_clone = " AND clone_id = '" . $cloneid . "'";
			}
			$sql = $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'wp_staging_sites' ) . ' WHERE `site_id`=%d ' . $sql_clone, $siteid );
		}
		return $this->query( $sql );
	}


	protected function escape( $data ) {

		/** @var $wpdb wpdb */
		global $wpdb;
		if ( function_exists( 'esc_sql' ) ) {
			return esc_sql( $data );
		} else {
			return $wpdb->escape( $data ); }
	}

	public function query( $sql ) {

		if ( null == $sql ) {
			return false; }
		/** @var $wpdb wpdb */
		global $wpdb;
		$result = @self::_query( $sql, $wpdb->dbh );

		if ( ! $result || ( @self::num_rows( $result ) == 0 ) ) {
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
		if ( is_bool( $result ) ) {
			return $result;
		}

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

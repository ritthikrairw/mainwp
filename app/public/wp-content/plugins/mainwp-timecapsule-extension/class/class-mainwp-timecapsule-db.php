<?php

class MainWP_TimeCapsule_DB {

	private $mainwp_wp_time_capsule_db_version = '1.4';
	private $table_prefix;
	// Singleton
	private static $instance = null;

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new MainWP_TimeCapsule_DB();
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
			return false; }

		global $wpdb;
		return ( $wpdb->dbh instanceof mysqli );
	}

	// Installs new DB
	function install() {
		global $wpdb;
		$currentVersion = get_site_option( 'mainwp_wp_time_capsule_db_version' );
		if ( $currentVersion == $this->mainwp_wp_time_capsule_db_version ) {
			return; }

		$charset_collate = $wpdb->get_charset_collate();
		$sql             = array();

		$tbl = 'CREATE TABLE `' . $this->table_name( 'wp_time_capsule' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`site_id` int(11) NOT NULL,
`lastbackup_time` int(11) NOT NULL,
`backups_count` int(11) NOT NULL,
`override` tinyint(1) NOT NULL DEFAULT 0,
`settings` longtext NOT NULL DEFAULT ""';
		if ( '' == $currentVersion ) {
			$tbl .= ',
PRIMARY KEY  (`id`)  '; }
		$tbl  .= ') ' . $charset_collate;
		$sql[] = $tbl;

		$result_capsule_sites = $this->query( "SHOW TABLES LIKE '" . $this->table_name( 'wp_time_capsule_sites' ) . "'" );

		$tbl = 'CREATE TABLE `' . $this->table_name( 'wp_time_capsule_sites' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`site_id` int(11) NOT NULL,
`clone_id` text NOT NULL,
`clone_url` text NOT NULL,
`staging_site_id` int(11) NOT NULL DEFAULT 0';
		if ( '' == $currentVersion || empty( $result_capsule_sites ) ) {
					$tbl .= ',
PRIMARY KEY  (`id`) '; }
		$tbl  .= ') ' . $charset_collate;
		$sql[] = $tbl;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}
		update_option( 'mainwp_wp_time_capsule_db_version', $this->mainwp_wp_time_capsule_db_version );
	}

	public function update_settings_fields( $site_id, $data ) {
		if ( ! is_array( $data ) ) {
			return false;
		} else {
			$current = $this->get_data_by( 'site_id', $site_id );
		}

		if ( empty( $current ) ) {
			$settings = array();
		} else {
			$settings = unserialize( base64_decode( $current->settings ) );
			if ( ! is_array( $settings ) ) {
				$settings = array();
			}
		}
		foreach ( $data as $key => $value ) {
			$settings[ $key ] = $value;
		}
		$update = array(
			'settings' => base64_encode( serialize( $settings ) ),
			'site_id'  => $site_id,
		);
		return $this->update_data( $update, true );
	}

	public function get_settings( $site_id ) {

		$current = $this->get_data_by( 'site_id', $site_id );
		if ( empty( $current ) ) {
			$settings = array();
		} else {
			$settings = unserialize( base64_decode( $current->settings ) );
			if ( ! is_array( $settings ) ) {
				$settings = array();
			}
		}

		return $settings;
	}

	public function delete_settings_fields( $site_id, $fields ) {

		if ( ! is_array( $fields ) ) {
			return false;
		} else {
			$current = $this->get_data_by( 'site_id', $site_id );
		}

		if ( empty( $current ) ) {
			return false;
		} else {
			$settings = unserialize( base64_decode( $current->settings ) );
			if ( ! is_array( $settings ) ) {
				$settings = array();
			}
		}

		foreach ( $fields as $key ) {
			if ( isset( $settings[ $key ] ) ) {
				unset( $settings[ $key ] );
			}
		}
		$update = array(
			'settings' => base64_encode( serialize( $settings ) ),
			'site_id'  => $site_id,
		);
		return $this->update_data( $update, true );
	}

	public function update_data( $data, $for_site = true ) {
		/** @var $wpdb wpdb */
		global $wpdb;
		$id = isset( $data['id'] ) ? $data['id'] : 0;
		if ( $id ) {
			if ( $wpdb->update( $this->table_name( 'wp_time_capsule' ), $data, array( 'id' => intval( $id ) ) ) ) {
				return $this->get_data_by( 'id', $id ); }
		} elseif ( $for_site ) {
			$site_id = (int) $data['site_id'];
			$current = $this->get_data_by( 'site_id', $site_id );
			if ( $current ) {
				if ( $wpdb->update( $this->table_name( 'wp_time_capsule' ), $data, array( 'site_id' => intval( $site_id ) ) ) ) {
					return $this->get_data_by( 'site_id', $site_id );
				}
			} else {
				if ( $wpdb->insert( $this->table_name( 'wp_time_capsule' ), $data ) ) {
					return $this->get_data_by( 'id', $wpdb->insert_id ); }
			}
		} elseif ( $wpdb->insert( $this->table_name( 'wp_time_capsule' ), $data ) ) {
			return $this->get_data_by( 'id', $wpdb->insert_id );
		}
		return false;
	}


	public function delete_data( $by = 'id', $value = null ) {
		global $wpdb;
		if ( empty( $by ) ) {
			return null; }
		$sql = '';
		if ( 'id' == $by ) {
			$sql = $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'wp_time_capsule' ) . ' WHERE `id`=%d ', $value );
		} elseif ( 'site_id' == $by ) {
			$sql = $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'wp_time_capsule' ) . ' WHERE `site_id` = %d ', $value );
		}

		if ( ! empty( $sql ) ) {
			$wpdb->query( $sql ); }

		return true;
	}

	public function get_data_by( $by = 'id', $value = null, $fields = '', $output = OBJECT ) {
		global $wpdb;

		if ( empty( $by ) ) {
			return null;
		}

		if ( empty( $fields ) ) {
			$fields = '*';
		}

		$sql = '';
		if ( 'id' == $by ) {
			$sql = $wpdb->prepare( "SELECT {$fields} FROM " . $this->table_name( 'wp_time_capsule' ) . ' WHERE `id`=%d ', $value );
		} elseif ( 'site_id' == $by ) {
			$sql = $wpdb->prepare( "SELECT {$fields} FROM " . $this->table_name( 'wp_time_capsule' ) . ' WHERE `site_id` = %d ', $value );
		}

		if ( ! empty( $sql ) ) {
			return $wpdb->get_row( $sql, $output );
		}

		return null;
	}

	public function get_data_of_sites( $site_ids = array(), $output = ARRAY_A ) {
		global $wpdb;
		$str_ids = '';
		if ( is_array( $site_ids ) && count( $site_ids ) > 0 ) {
			$str_ids = implode( ',', $site_ids );
		}
		if ( ! empty( $str_ids ) ) {
			$sql = 'SELECT * FROM ' . $this->table_name( 'wp_time_capsule' ) .
					' WHERE `site_id` IN (' . $str_ids . ') ';
		} else {
			$sql = 'SELECT * FROM ' . $this->table_name( 'wp_time_capsule' ) .
					' WHERE 1 ';

		}
		return $wpdb->get_results( $sql, $output );
	}

	public function get_settings_of_sites( $site_ids = array() ) {
		$data_sites = $this->get_data_of_sites( $site_ids );
		if ( count( $data_sites ) > 0 ) {
			$return = array();
			foreach ( $data_sites as $data ) {
				$settings = unserialize( base64_decode( $data['settings'] ) );
				if ( ! is_array( $settings ) ) {
					$settings = array();
				}
				$return[ $data['site_id'] ] = $settings;
			}
			return $return;
		}
		return array();
	}

	public function get_timecapsule_of_sites( $site_ids = array() ) {
		$data_sites = $this->get_data_of_sites( $site_ids );
		$return     = array();
		if ( count( $data_sites ) > 0 ) {
			foreach ( $data_sites as $data ) {
				$return[ $data['site_id'] ] = $data;
			}
		}
		return $return;
	}

	public function get_staging_site( $siteId ) {
		if ( empty( $siteId ) ) {
			return false;
		}

		global $wpdb;
		$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'wp_time_capsule_sites' ) . ' WHERE `site_id` = %d', $siteId );
		return $wpdb->get_row( $sql );
	}

	public function update_staging_site( $data = array() ) {
		global $wpdb;

		if ( ! isset( $data['site_id'] ) || empty( $data['site_id'] ) ) {
			return false;
		}

		$siteid = $data['site_id'];

		$current = $this->get_staging_site( $siteid );
		if ( $current ) {
			unset( $data['site_id'] );
			if ( $wpdb->update( $this->table_name( 'wp_time_capsule_sites' ), $data, array( 'id' => $current->id ) ) ) {
				return $this->get_staging_site( $siteid );
			}
		} else {
			if ( $wpdb->insert( $this->table_name( 'wp_time_capsule_sites' ), $data ) ) {
				return $this->get_staging_site( $siteid );
			}
		}
		return false;
	}

	public function delete_staging_site( $siteid ) {
		global $wpdb;
		if ( empty( $siteid ) ) {
			return false;
		}
		$sql = $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'wp_time_capsule_sites' ) . ' WHERE `site_id`=%d ', $siteid );
		return $this->query( $sql );
	}

	public function get_count_stagings_of_sites() {
		global $wpdb;

		$sql = 'SELECT site_id, count(staging_site_id) as count FROM ' .
				$this->table_name( 'wp_time_capsule_sites' ) .
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


	protected function escape( $data ) {
		global $wpdb;
		if ( function_exists( 'esc_sql' ) ) {
			return esc_sql( $data );
		} else {
			return $wpdb->escape( $data );
		}
	}

	public function query( $sql ) {
		if ( null == $sql ) {
			return false; }
		/** @var $wpdb wpdb */
		global $wpdb;
		$result = @self::_query( $sql, $wpdb->dbh );

		if ( ! $result || ( @self::num_rows( $result ) == 0 ) ) {
			return false; }
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

<?php

class MainWP_Wordfence_DB {
	private $mainwp_wordfence_db_version = '2.1';
	private $table_prefix;

	// Singleton
	private static $instance = null;

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new MainWP_Wordfence_DB();
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
		if ( ! function_exists( 'mysqli_connect' ) ) {
			return false;
		}

		global $wpdb;

		return ( $wpdb->dbh instanceof mysqli );
	}

	// Installs new DB
	function install() {
		global $wpdb;
		$currentVersion = get_site_option( 'mainwp_wordfence_db_version' );
		if ( $currentVersion == $this->mainwp_wordfence_db_version ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();
		$sql             = array();

		$tbl = 'CREATE TABLE `' . $this->table_name( 'wordfence' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`site_id` int(11) NOT NULL,
`status` tinyint(1) DEFAULT 0,
`apiKey` text NOT NULL,
`isPaid` tinyint(1) DEFAULT 0,
`isHidden` tinyint(1) DEFAULT 0,
`isNginx` tinyint(1) DEFAULT 0,
`lastscan` int(11) DEFAULT 0,
`todayAttBlocked` int UNSIGNED NOT NULL DEFAULT 0,
`weekAttBlocked` int UNSIGNED NOT NULL DEFAULT 0,
`monthAttBlocked` int UNSIGNED NOT NULL DEFAULT 0,
`extra_settings` text NOT NULL,
`settings` text NOT NULL,
`cacheType` VARCHAR(10),
`override` tinyint(1) NOT NULL DEFAULT 0';
		if ( '' == $currentVersion ) {
			$tbl .= ',
PRIMARY KEY  (`id`)  ';
		}
		$tbl  .= ') ' . $charset_collate;
		$sql[] = $tbl;

		error_reporting( 0 ); // make sure to disable any error output
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		// global $wpdb;
		// echo $wpdb->last_error;
		// exit();
		update_option( 'mainwp_wordfence_db_version', $this->mainwp_wordfence_db_version );
	}

	public function update_setting( $setting ) {
		global $wpdb;
		$id      = isset( $setting['id'] ) ? $setting['id'] : 0;
		$site_id = isset( $setting['site_id'] ) ? $setting['site_id'] : 0;

		if ( $id ) {
			if ( $wpdb->update( $this->table_name( 'wordfence' ), $setting, array( 'id' => intval( $id ) ) ) ) {
				return $this->get_setting_by( 'id', $id );
			}
		} elseif ( $site_id ) {
			$current = $this->get_setting_by( 'site_id', $site_id );
			// error_log(print_r($current, true));
			if ( $current ) {
				// error_log(print_r($setting, true));
				if ( $wpdb->update( $this->table_name( 'wordfence' ), $setting, array( 'site_id' => intval( $site_id ) ) ) ) {
					return $this->get_setting_by( 'site_id', $site_id );
				}
			} else {
				if ( $wpdb->insert( $this->table_name( 'wordfence' ), $setting ) ) {
					return $this->get_setting_by( 'id', $wpdb->insert_id );
				}
			}
		} elseif ( $wpdb->insert( $this->table_name( 'wordfence' ), $setting ) ) {
			return $this->get_setting_by( 'id', $wpdb->insert_id );
		}

		return false;
	}

	public function update_setting_fields_values_by( $by, $value, $data ) {
		$current_settings = $this->get_setting_fields_by( $by, $value );

		foreach ( $data as $key => $val ) {
			if ( in_array( $key, MainWP_Wordfence_Config::$options_filter ) ) {
				$current_settings[ $key ] = $val;
			}
		}
		$this->update_setting_fields_by( $by, $value, $current_settings );
	}

	public function update_setting_fields_by( $by, $value, $data ) {
		$id      = ( 'id' == $by ) ? $value : 0;
		$site_id = ( 'site_id' == $by ) ? $value : 0;

		if ( $id ) {
			$current = $this->get_setting_by( 'id', $id );
		} elseif ( $site_id ) {
			$current = $this->get_setting_by( 'site_id', $site_id );
		} else {
			return false;
		}

		if ( empty( $current ) ) {
			// add new
			if ( $site_id ) {
				$update = array(
					'site_id'  => $site_id,
					'settings' => serialize( $data ),
				);
				return $this->update_setting( $update );
			} else {
				return false;
			}
		}

		$new_setting = unserialize( $current->settings );

		if ( ! is_array( $new_setting ) ) {
			$new_setting = array(); }

		foreach ( $data as $key => $value ) {
			$new_setting[ $key ] = $value;
		}

		return $this->update_setting(
			array(
				'site_id'  => $current->site_id,
				'settings' => serialize( $new_setting ),
			)
		);
	}

	public function update_extra_settings_fields_values_by( $site_id, $data ) {
		$current_settings = $this->get_extra_settings_by( 'site_id', $site_id );
		foreach ( $data as $key => $val ) {
			$current_settings[ $key ] = $val;
		}
		$this->update_extra_settings_fields_by( $site_id, $current_settings );
	}

	public function update_extra_settings_fields_by( $site_id, $data ) {
		if ( empty( $site_id ) ) {
				return;
		}

			$current = $this->get_extra_settings_by( 'site_id', $site_id );

		if ( empty( $current ) ) {
			if ( $site_id ) {
				$update = array(
					'site_id'        => $site_id,
					'extra_settings' => base64_encode( serialize( $data ) ),
				);
				return $this->update_setting( $update );
			} else {
				return false;
			}
		}

		$new_extra_settings = $current;

		if ( ! is_array( $new_extra_settings ) ) {
			$new_extra_settings = array(); }

		foreach ( $data as $key => $value ) {
			$new_extra_settings[ $key ] = $value;
		}

		return $this->update_setting(
			array(
				'site_id'        => $site_id,
				'extra_settings' => base64_encode( serialize( $new_extra_settings ) ),
			)
		);
	}

	public function get_extra_settings_by( $by = 'id', $value = null, $output = OBJECT ) {
			$site_settings = self::get_instance()->get_setting_by( $by, $value );
		$extra_settings    = array();
		if ( $site_settings ) {
			$extra_settings = unserialize( base64_decode( $site_settings->extra_settings ) );
		}
		if ( ! is_array( $extra_settings ) ) {
			$extra_settings = array();
		}
		return $extra_settings;
	}

	public function get_setting_fields_by( $by = 'id', $value = null ) {
		$site_settings = self::get_instance()->get_setting_by( $by, $value );
		$settings      = array();
		if ( $site_settings ) {
			$settings = unserialize( $site_settings->settings );
		}
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		return $settings;
	}
	public function get_setting_by( $by = 'id', $value = null ) {
		global $wpdb;

		if ( empty( $by ) || empty( $value ) ) {
			return null;
		}

		$sql = '';
		if ( 'id' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'wordfence' ) . ' WHERE `id`=%d ', $value );
		} elseif ( 'site_id' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'wordfence' ) . ' WHERE `site_id` = %d ', $value );
		}

		$setting = null;
		if ( ! empty( $sql ) ) {
			$setting = $wpdb->get_row( $sql );
		}

		return $setting;
	}

	public function get_settings() {
		global $wpdb;
		$sql = 'SELECT * FROM ' . $this->table_name( 'wordfence' ) . ' WHERE 1 ';
		return $wpdb->get_results( $sql );
	}
	
	public function get_count_wfc() {
		global $wpdb;
		$sql = 'SELECT count(*) FROM ' . $this->table_name( 'wordfence' ) . ' WHERE 1 ';
		return $wpdb->get_results( $sql );
	}

	public function get_settings_fields( $fields = array(), $site_ids = array() ) {

		global $wpdb;
		$sql_site_ids = '';
		$str_site_ids = '';

		if ( is_array( $site_ids ) && count( $site_ids ) > 0 ) {
			$sql_site_ids = implode( ',', $site_ids );
			$sql_site_ids = ' AND `site_id` IN (' . $sql_site_ids . ')';
		}

		if ( is_array( $fields ) && count( $fields ) > 0 ) {
			$sql_fields = implode( ',', $fields );
		} else {
			$sql_fields = '*';
		}

		$sql = 'SELECT ' . $sql_fields . ' FROM ' . $this->table_name( 'wordfence' ) . ' WHERE 1 ' . $sql_site_ids;
		return $wpdb->get_results( $sql );
	}

	public function delete_wordfence( $site_ids ) {
		global $wpdb;
		if ( empty( $site_ids ) ) {
			return false;
		}
		$sql = $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'wordfence' ) . ' WHERE `site_id` IN ( %s ) ', implode( ',', $site_ids ) );
		return $wpdb->query( $sql );
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
			return false;
		}

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
			return null;
		}

		global $wpdb;

		return $wpdb->get_results( $sql, OBJECT_K );
	}
}

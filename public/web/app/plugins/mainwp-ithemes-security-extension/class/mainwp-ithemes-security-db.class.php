<?php
class MainWP_IThemes_Security_DB {

	private $mainwp_ithemes_security_db_version = '1.4';
	private $table_prefix;

	// Singleton
	private static $instance = null;

	static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new MainWP_IThemes_Security_DB();
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
		$currentVersion = get_site_option( 'mainwp_ithemes_security_db_version' );
		if ( $currentVersion == $this->mainwp_ithemes_security_db_version ) {
			return; }

		$charset_collate = $wpdb->get_charset_collate();
		$sql             = array();

		$tbl = 'CREATE TABLE `' . $this->table_name( 'ithemes_security' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`site_id` int(11) NOT NULL,
`site_status` longtext NOT NULL DEFAULT "",
`settings` longtext NOT NULL DEFAULT "",
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

		// global $wpdb;
		// echo $wpdb->last_error;
		// exit();
		update_option( 'mainwp_ithemes_security_db_version', $this->mainwp_ithemes_security_db_version );
	}

	public function update_setting_fields_by( $by, $value, $data ) {
		$id      = ( 'id' == $by ) ? $value : 0;
		$site_id = ( 'site_id' == $by ) ? $value : 0;

		if ( $id ) {
			$current = $this->get_site_setting_by( 'id', $id );
		} elseif ( $site_id ) {
			$current = $this->get_site_setting_by( 'site_id', $site_id );
		} else {
			return false;
		}

		if ( empty( $current ) ) {
			if ( $site_id ) {
				$update = array(
					'site_id'  => $site_id,
					'settings' => base64_encode( serialize( $data ) ),
				);
				return $this->update_setting( $update );
			} else {
				return false;
			}
		}

		$new_setting = unserialize( base64_decode( $current->settings ) );

		if ( ! is_array( $new_setting ) ) {
			$new_setting = array(); }

		foreach ( $data as $key => $value ) {
			$new_setting[ $key ] = $value;
		}

		return $this->update_setting(
			array(
				'site_id'  => $current->site_id,
				'settings' => base64_encode( serialize( $new_setting ) ),
			)
		);
	}

	public function update_site_status_fields_by( $by, $value, $data ) {
		$id      = ( 'id' == $by ) ? $value : 0;
		$site_id = ( 'site_id' == $by ) ? $value : 0;

		if ( 'id' == $by ) {
			$current = $this->get_site_setting_by( 'id', $id );
		} elseif ( 'site_id' == $by ) {
			$current = $this->get_site_setting_by( 'site_id', $site_id );
		} else {
			return false;
		}

		if ( empty( $current ) ) {
			if ( $site_id ) {
				$update = array(
					'site_id'     => $site_id,
					'site_status' => base64_encode( serialize( $data ) ),
				);
				return $this->update_setting( $update );
			} else {
				return false;
			}
		}

		$new_site_status = unserialize( base64_decode( $current->site_status ) );

		if ( ! is_array( $new_site_status ) ) {
			$new_site_status = array();
		}

		foreach ( $data as $key => $value ) {
			$new_site_status[ $key ] = $value;
		}

		return $this->update_setting(
			array(
				'site_id'     => $current->site_id,
				'site_status' => base64_encode( serialize( $new_site_status ) ),
			)
		);
	}

	public function update_setting_module_fields_by( $by, $value, $module, $data ) {
		if ( empty( $module ) ) {
			return;
		}

		$id      = ( 'id' == $by ) ? $value : 0;
		$site_id = ( 'site_id' == $by ) ? $value : 0;

		if ( $id ) {
			$current = $this->get_site_setting_by( 'id', $id );
		} elseif ( $site_id ) {
			$current = $this->get_site_setting_by( 'site_id', $site_id );
		} else {
			return false;
		}

		if ( empty( $current ) ) {
			if ( $site_id ) {
				$update = array(
					'site_id'  => $site_id,
					'settings' => base64_encode( serialize( array( $module => $data ) ) ),
				);
				return $this->update_setting( $update );
			} else {
				return false;
			}
		}

		$new_setting = unserialize( base64_decode( $current->settings ) );

		if ( ! is_array( $new_setting ) ) {
			$new_setting = array(); }

		foreach ( $data as $key => $value ) {
			$new_setting[ $module ][ $key ] = $value;
		}

		return $this->update_setting(
			array(
				'site_id'  => $current->site_id,
				'settings' => base64_encode( serialize( $new_setting ) ),
			)
		);
	}



	public function delete_setting( $by = 'id', $value = null ) {
		global $wpdb;
		if ( empty( $by ) ) {
			return null; }
		$sql = '';
		if ( 'id' == $by ) {
			$sql = $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'ithemes_security' ) . ' WHERE `id`=%d ', $value );
		} elseif ( 'site_id' == $by ) {
			$sql = $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'ithemes_security' ) . ' WHERE `site_id` = %d ', $value );
		}

		if ( ! empty( $sql ) ) {
			$wpdb->query( $sql ); }

		return true;
	}

	public function update_setting( $setting ) {

		 /** @var $wpdb wpdb */
		global $wpdb;
		$id      = isset( $setting['id'] ) ? $setting['id'] : 0;
		$site_id = isset( $setting['site_id'] ) ? $setting['site_id'] : 0;

		if ( $id ) {
			if ( $wpdb->update( $this->table_name( 'ithemes_security' ), $setting, array( 'id' => intval( $id ) ) ) ) {
				return $this->get_site_setting_by( 'id', $id ); }
		} elseif ( $site_id ) {
			$current = $this->get_site_setting_by( 'site_id', $site_id );
			if ( $current ) {
				if ( $wpdb->update( $this->table_name( 'ithemes_security' ), $setting, array( 'site_id' => intval( $site_id ) ) ) ) {
					return $this->get_site_setting_by( 'site_id', $site_id );
				}
			} else {
				if ( $wpdb->insert( $this->table_name( 'ithemes_security' ), $setting ) ) {
					return $this->get_site_setting_by( 'id', $wpdb->insert_id ); }
			}
		} elseif ( $wpdb->insert( $this->table_name( 'ithemes_security' ), $setting ) ) {
			return $this->get_site_setting_by( 'id', $wpdb->insert_id );
		}
		return false;
	}

	public function get_site_setting_by( $by = 'id', $value = null, $output = OBJECT ) {
		global $wpdb;

		if ( empty( $by ) || empty( $value ) ) {
			return null; }

		$sql = '';
		if ( 'id' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'ithemes_security' ) . ' WHERE `id`=%d ', $value );
		} elseif ( 'site_id' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'ithemes_security' ) . ' WHERE `site_id` = %d ', $value );
		}

		$setting = null;
		if ( ! empty( $sql ) ) {
			$setting = $wpdb->get_row( $sql, $output ); }
		return $setting;
	}

	public function get_setting_fields_by( $by = 'id', $value = null ) {
		$itheme_site  = self::get_instance()->get_site_setting_by( $by, $value );
		$all_settings = array();
		if ( $itheme_site ) {
			$all_settings = unserialize( base64_decode( $itheme_site->settings ) );
		}
		return $all_settings;
	}

	public function get_settings( $site_ids = array() ) {
		global $wpdb;
		if ( ! is_array( $site_ids ) || count( $site_ids ) <= 0 ) {
			return array(); }
		$str_site_ids = implode( ',', $site_ids );
		$sql          = 'SELECT * FROM ' . $this->table_name( 'ithemes_security' ) . ' WHERE `site_id` IN (' . $str_site_ids . ') ';
		return $wpdb->get_results( $sql );
	}

	public function get_settings_field_array() {
		global $wpdb;
		$sql     = 'SELECT override, site_status, site_id FROM ' . $this->table_name( 'ithemes_security' ) . ' WHERE 1';
		$results = $wpdb->get_results( $sql );
		$return  = array();
		if ( is_array( $results ) ) {
			foreach ( $results as $val ) {
				$return[ $val->site_id ]['override']    = $val->override;
				$return[ $val->site_id ]['site_status'] = unserialize( base64_decode( $val->site_status ) );
			}
		}

		return $return;
	}


	public function get_status_fields_by( $by = 'id', $value = null ) {
		$val    = self::get_instance()->get_site_setting_by( $by, $value );
		$status = array();
		if ( $val ) {
			$status = unserialize( base64_decode( $val->site_status ) );
		}
		return $status;
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

		if ( is_bool( $result ) ) {
			return $result;
		}

		if ( self::use_mysqli() ) {
			return mysqli_fetch_object( $result );
		} else {
			return mysql_fetch_object( $result );
		}
	}

	public static function free_result( $result ) {

		if ( is_bool( $result ) ) {
			return $result;
		}

		if ( self::use_mysqli() ) {
			return mysqli_free_result( $result );
		} else {
			return mysql_free_result( $result );
		}
	}

	public static function data_seek( $result, $offset ) {

		if ( is_bool( $result ) ) {
			return $result;
		}

		if ( self::use_mysqli() ) {
			return mysqli_data_seek( $result, $offset );
		} else {
			return mysql_data_seek( $result, $offset );
		}
	}

	public static function fetch_array( $result, $result_type = null ) {

		if ( is_bool( $result ) ) {
			return $result;
		}

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
}

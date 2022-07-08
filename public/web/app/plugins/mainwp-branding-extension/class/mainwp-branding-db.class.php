<?php

class MainWP_Branding_DB {
	private static $instance = null;
	//Singleton
	private $mainwp_branding_db_version = '2.5';
	private $table_prefix;

  private $wpdb;

	function __construct() {

		global $wpdb;

    $this->wpdb         = &$wpdb;
		$this->table_prefix = $wpdb->prefix . 'mainwp_';
	}

	//Constructor

	static function get_instance() {

		if ( null == MainWP_Branding_DB::$instance ) {
			MainWP_Branding_DB::$instance = new MainWP_Branding_DB();
		}

		return MainWP_Branding_DB::$instance;
	}

	function install() {

		global $wpdb;
		$currentVersion = get_site_option( 'mainwp_branding_db_version' );
		if ( $currentVersion == $this->mainwp_branding_db_version ) {
			return;
		}

    $result_child_branding = $this->query( "SHOW TABLES LIKE '" . $this->table_name( 'child_branding' ) . "'" );

		$charset_collate = $wpdb->get_charset_collate();
		$sql             = array();

		$tbl = 'CREATE TABLE `' . $this->table_name( 'child_branding' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`site_id` int(11) NOT NULL DEFAULT 0,
`plugin_header` text NOT NULL,
`hide_child_plugin` tinyint(1) NOT NULL DEFAULT 0,
`disable_theme_plugin_change` tinyint(1) NOT NULL DEFAULT 0,
`show_support_button` tinyint(1) NOT NULL DEFAULT 0,
`support_email` text NOT NULL,
`support_message` text NOT NULL,
`remove_restore` tinyint(1) NOT NULL DEFAULT 0,
`remove_setting` tinyint(1) NOT NULL DEFAULT 0,
`remove_server_info` tinyint(1) NOT NULL DEFAULT 0,
`remove_wp_tools` tinyint(1) NOT NULL DEFAULT 0,
`remove_wp_setting` tinyint(1) NOT NULL DEFAULT 0,
`button_contact_label` varchar(64) NOT NULL DEFAULT "",
`send_email_message` varchar(512) NOT NULL DEFAULT "",
`extra_settings` text NOT NULL,
`override` tinyint(1) NOT NULL DEFAULT 0';
		if ( '' == $currentVersion || empty($result_child_branding) ) {
			$tbl .= ',
PRIMARY KEY  (`id`)  ';
		}
		$tbl .= ') ' . $charset_collate;
		$sql[] = $tbl;

		error_reporting( 0 ); // make sure to disable any error output
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}


    $this->check_update( $currentVersion );
		update_option( 'mainwp_branding_db_version', $this->mainwp_branding_db_version );
	}

	function table_name( $suffix ) {
		return $this->table_prefix . $suffix;
	}

  function check_update( $version ) {
    global $wpdb;

    if ( !empty( $version ) ) {
      if ( version_compare( $version, '2.0', '<' ) ) {
        $sql =  'SELECT * FROM ' . $this->table_name( 'child_branding' ) . ' WHERE 1 ';
        $brandings = $this->query( $sql );

        while ( $brandings && ( $branding = @self::fetch_object( $brandings ) ) ) {
          $website = apply_filters( 'mainwp_getwebsitesbyurl', $branding->site_url );
          if ( $website ) {
            $website = current($website);
            $update = array(
              'id' => $branding->id,
              'site_id' => $website->id
            );
              $this->update_branding( $update );
            } else {
              $this->wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'child_branding' ) . ' WHERE id = %d', $branding->id ) );
            }
        }
        @self::free_result( $brandings );
      }

      if ( version_compare( $version, '2.2', '<' ) ) {
        $suppress = $this->wpdb->suppress_errors();
        $this->wpdb->query( 'ALTER TABLE ' . $this->table_name( 'child_branding' ) . ' DROP COLUMN site_url');
        $this->wpdb->suppress_errors( $suppress );
      }
    }
  }

	public function update_branding( $branding ) {
		global $wpdb;
		$id = isset( $branding['id'] ) && $branding['id'] ? $branding['id'] : null;

		if ( $id ) {
			if ( $wpdb->update( $this->table_name( 'child_branding' ), $branding, array( 'id' => intval( $id ) ) ) ) {
				return $this->get_branding_by( 'id', $id );
			}
		} else if ( $wpdb->insert( $this->table_name( 'child_branding' ), $branding ) ) {
			return $this->get_branding_by( 'id', $wpdb->insert_id );
		}

		return false;
	}

	public function get_branding_by( $by = 'id', $value = null ) {
		global $wpdb;

		if ( empty( $by ) || empty( $value ) ) {
			return null;
		}

		$sql = '';

		if ( 'id' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'child_branding' ) . ' WHERE `id`=%d ', $value );
		} else if ( 'site_id' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'child_branding' ) . " WHERE `site_id` = %d ", $value );
		} else if ( 'site_url' == $by ) { // not used any more
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'child_branding' ) . " WHERE `site_url` = '%s' ", $value );
		}

		$branding = null;

		if ( ! empty( $sql ) ) {
			$branding = $wpdb->get_row( $sql );
		}

		return $branding;
	}

  public function delete_branding( $siteid ) {
		global $wpdb;
    if ( $siteid ) {
      $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'child_branding' ) . ' WHERE site_id = %d', $siteid ) );
      return true;
    }
    return false;
	}


	//Support old & new versions of wordpress (3.9+)
	public static function use_mysqli() {
		/** @var $this ->wpdb wpdb */
		if ( ! function_exists( 'mysqli_connect' ) ) {
			return false;
		}

		return ( self::$instance->wpdb->dbh instanceof mysqli );
	}

	public static function ping( $link ) {
		if ( self::use_mysqli() ) {
			return mysqli_ping( $link );
		} else {
			return mysql_ping( $link );
		}
	}

	public static function _query( $query, $link ) {
		if ( self::use_mysqli() ) {
			return mysqli_query( $link, $query );
		} else {
			return mysql_query( $query, $link );
		}
	}


	public function query( $sql ) {
		if ( $sql == null ) {
			return false;
		}

		$result = @self::_query( $sql, $this->wpdb->dbh );

		if ( ! $result || ( @self::num_rows( $result ) == 0 ) ) {
			return false;
		}

		return $result;
	}

	protected function escape( $data ) {
		if ( function_exists( 'esc_sql' ) ) {
			return esc_sql( $data );
		} else {
			return $this->wpdb->escape( $data );
		}
	}

	public static function fetch_object( $result ) {
		if ( $result === false ) {
			return false;
		}

		if ( self::use_mysqli() ) {
			return mysqli_fetch_object( $result );
		} else {
			return mysql_fetch_object( $result );
		}
	}

	public static function free_result( $result ) {
		if ( $result === false ) {
			return false;
		}

		if ( self::use_mysqli() ) {
			return mysqli_free_result( $result );
		} else {
			return mysql_free_result( $result );
		}
	}

	public static function data_seek( $result, $offset ) {
		if ( $result === false ) {
			return false;
		}

		if ( self::use_mysqli() ) {
			return mysqli_data_seek( $result, $offset );
		} else {
			return mysql_data_seek( $result, $offset );
		}
	}

	public static function fetch_array( $result, $result_type = null ) {
		if ( $result === false ) {
			return false;
		}

		if ( self::use_mysqli() ) {
			return mysqli_fetch_array( $result, ( $result_type == null ? MYSQLI_BOTH : $result_type ) );
		} else {
			return mysql_fetch_array( $result, ( $result_type == null ? MYSQL_BOTH : $result_type ) );
		}
	}

	public static function num_rows( $result ) {
		if ( $result === false ) {
			return 0;
		}

		if ( self::use_mysqli() ) {
			return mysqli_num_rows( $result );
		} else {
			return mysql_num_rows( $result );
		}
	}

	public static function is_result( $result ) {
		if ( $result === false ) {
			return false;
		}

		if ( self::use_mysqli() ) {
			return ( $result instanceof mysqli_result );
		} else {
			return is_resource( $result );
		}
	}
}

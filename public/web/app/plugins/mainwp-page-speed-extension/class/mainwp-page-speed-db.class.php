<?php

class MainWP_Page_Speed_DB {

	private $mainwp_pagespeed_db_version = '2.17';
	private $table_prefix;
	//Singleton
	private static $instance = null;

	static function get_instance() {
		if ( null == MainWP_Page_Speed_DB::$instance ) {
			MainWP_Page_Speed_DB::$instance = new MainWP_Page_Speed_DB();
		}
		return MainWP_Page_Speed_DB::$instance;
	}

	//Constructor
	function __construct() {
		global $wpdb;
		$this->table_prefix = $wpdb->prefix . 'mainwp_';
	}

	function table_name( $suffix ) {
		return $this->table_prefix . $suffix;
	}

	//Support old & new versions of wordpress (3.9+)
	public static function use_mysqli() {
		/** @var $wpdb wpdb */
		if ( ! function_exists( 'mysqli_connect' ) ) {
			return false;
		}

		global $wpdb;
		return ( $wpdb->dbh instanceof mysqli );
	}

	//Installs new DB
	function install() {
		global $wpdb;
		$currentVersion = get_site_option( 'mainwp_pagespeed_db_version' );
		if ( $currentVersion == $this->mainwp_pagespeed_db_version ) {
			return;
        }

		$charset_collate = $wpdb->get_charset_collate();
		$sql = array();

		$tbl = 'CREATE TABLE `' . $this->table_name( 'pagespeed' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`site_id` int(11) NOT NULL,
`settings` text NOT NULL DEFAULT "",
`bad_api_key` tinyint(1) NOT NULL DEFAULT 0,
`desktop_last_checked` int(11) NOT NULL DEFAULT 0,
`mobile_last_checked` int(11) NOT NULL DEFAULT 0,
`desktop_total_pages` int(11) NOT NULL DEFAULT 0,
`mobile_total_pages` int(11) NOT NULL DEFAULT 0,
`score_desktop` tinyint(4) NOT NULL DEFAULT 0,
`score_mobile` tinyint(4) NOT NULL DEFAULT 0,
`last_alert` int(11) NOT NULL DEFAULT 0,
`strategy` varchar(32) NOT NULL DEFAULT "",
`override_noti` tinyint(1) NOT NULL DEFAULT 0,
`hide_plugin` tinyint(1) NOT NULL DEFAULT 0,
`status` tinyint(1) DEFAULT 0,
`override` tinyint(1) NOT NULL DEFAULT 0';
		if ( '' == $currentVersion ) {
			$tbl .= ',
PRIMARY KEY  (`id`)  '; }
		$tbl .= ') ' . $charset_collate;
		$sql[] = $tbl;

		error_reporting( 0 ); // make sure to disable any error output
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		update_option( 'mainwp_pagespeed_db_version', $this->mainwp_pagespeed_db_version );

    $this->check_update($currentVersion, $this->mainwp_pagespeed_db_version);
	}

  public function check_update( $currentVersion, $newVersion ) {
    global $wpdb;
    if ( !empty( $currentVersion ) && version_compare( $currentVersion, '2.16', '<=' ) ) {
      $pagespeeds = MainWP_Page_Speed_DB::get_instance()->get_page_speed_by( 'all' );
      if ( is_array( $pagespeeds ) ) {

        foreach( $pagespeeds as $val ) {
          if ( property_exists( $val, 'site_url' ) && $val->site_url ) {
            $website = apply_filters( 'mainwp_getwebsitesbyurl', $val->site_url );

            if ( $website && is_array( $website ) )
              $website = current( $website );

            $fixed_ok = false;

            if ( $website && is_object( $website ) ) {
                $update = array(
                  'id' => $val->id,
                  'site_id' => $website->id,
                );

                MainWP_Page_Speed_DB::get_instance()->update_page_speed( $update );
                $fixed_ok = true;
            }

            if ( is_object( $website ) && !$fixed_ok ){
              $sql = "DELETE FROM " . $this->table_name( 'pagespeed' ) . " WHERE id = " . $val->id;
              $wpdb->query( $sql );
            }
          }
        }
        $wpdb->query( 'ALTER TABLE ' . $this->table_name( 'pagespeed' ) . ' DROP COLUMN site_url' );
      }
    }
  }

  public function update_page_speed( $setting ) {
		/** @var $wpdb wpdb */
		global $wpdb;
		$id = isset( $setting['id'] ) ? $setting['id'] : 0;
        $site_id = isset( $setting['site_id'] ) ? $setting['site_id'] : '';
		if ( ! empty( $id ) ) {
			if ( $wpdb->update( $this->table_name( 'pagespeed' ), $setting, array( 'id' => intval( $id ) ) ) ) {
				return $this->get_page_speed_by( 'id', $id );
			}
		} else if ( ! empty( $site_id ) ) {
			$current = $this->get_page_speed_by( 'site_id', $site_id );
			if ( ! empty( $current ) ) {
				if ( $wpdb->update( $this->table_name( 'pagespeed' ), $setting, array( 'site_id' => $site_id ) ) ) {
					return $this->get_page_speed_by( 'site_id', $site_id );
        }
			} else {
				if ( $wpdb->insert( $this->table_name( 'pagespeed' ), $setting ) ) {
					return $this->get_page_speed_by( 'id', $wpdb->insert_id );
        }
			}
		} else {
			unset( $setting['id'] );
			if ( $wpdb->insert( $this->table_name( 'pagespeed' ), $setting ) ) {
				return $this->get_page_speed_by( 'id', $wpdb->insert_id );
			}
		}
		return false;
	}

	public function empty_page_speeds_score( $site_id = null ) {
		global $wpdb;
		if ( empty( $site_id ) ) {
			$sql = $wpdb->prepare( 'UPDATE ' . $this->table_name( 'pagespeed' ) . ' SET score_desktop = 0, score_mobile = 0, desktop_total_pages = 0, mobile_total_pages = 0, desktop_last_checked = 0, mobile_last_checked = 0  WHERE `override`=%d ', 0 );
		} else {
			$sql = $wpdb->prepare( 'UPDATE ' . $this->table_name( 'pagespeed' ) . " SET score_desktop = 0, score_mobile = 0, desktop_total_pages = 0, mobile_total_pages = 0, desktop_last_checked = 0, mobile_last_checked = 0  WHERE `site_id` = '%s' ", $site_id );
		}

		$wpdb->query( $sql );

		return true;
	}

	public function get_page_speed_by( $by = 'id', $value = null ) {
		global $wpdb;
		if ( empty( $by ) ) {
			return null;
    }

		$sql = '';
		if ( 'id' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'pagespeed' ) . ' WHERE `id`=%d ', $value );
		} else if ( 'site_id' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'pagespeed' ) . " WHERE `site_id` = '%d' ", $value );
		} else if ( 'all' == $by ) {
			$sql = 'SELECT * FROM ' . $this->table_name( 'pagespeed' ) . ' WHERE 1 = 1';
			return $wpdb->get_results( $sql );
		}
		$setting = null;
		if ( ! empty( $sql ) ) {
			$setting = $wpdb->get_row( $sql );
    }
		return $setting;
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

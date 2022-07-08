<?php

class MainWP_WooCommerce_Status_DB {

	private $mainwp_woocommerce_status_db_version = '1.7';
	private $table_prefix;
	private static $instance = null;

	static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new MainWP_WooCommerce_Status_DB();
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

		$currentVersion = get_site_option( 'mainwp_woocommerce_status_db_version' );

		if ( $currentVersion == $this->mainwp_woocommerce_status_db_version ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();
		$sql             = array();

		$tbl = 'CREATE TABLE `' . $this->table_name( 'woo_com_status' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`site_id` int(11) NOT NULL DEFAULT 0,
`status` longtext NOT NULL DEFAULT "",
`need_db_update` tinyint(1) NOT NULL DEFAULT 0,
`active` tinyint(1) NOT NULL DEFAULT 0';
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

		$this->check_update( $currentVersion );

		update_option( 'mainwp_woocommerce_status_db_version', $this->mainwp_woocommerce_status_db_version );
	}

	public function check_update( $current_version ) {
		global $wpdb;

		if ( version_compare( '1.0', $current_version, '=' ) ) {
			$wpdb->query( 'DELETE FROM ' . $this->table_name( 'woo_com_status' ) . " WHERE status = ''" );
		}

		if ( version_compare( '1.7', $current_version, '>' ) ) {

			global $mainWPWooCommerceStatusExtensionActivator;

			$websites  = apply_filters( 'mainwp_getsites', $mainWPWooCommerceStatusExtensionActivator->get_child_file(), $mainWPWooCommerceStatusExtensionActivator->get_child_key(), null );
			$sites_ids = array();
			if ( is_array( $websites ) ) {
				foreach ( $websites as $website ) {
					$sites_ids[] = $website['id'];
				}
			}

			$option     = array(
				'plugin_upgrades' => true,
				'plugins'         => true,
			);
			$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPWooCommerceStatusExtensionActivator->get_child_file(), $mainWPWooCommerceStatusExtensionActivator->get_child_key(), $sites_ids, array(), $option );

			$installed_sites = array();
			foreach ( $dbwebsites as $website ) {
				$plugins = json_decode( $website->plugins, 1 );
				if ( is_array( $plugins ) ) {
					foreach ( $plugins as $plugin ) {
						if ( 'woocommerce/woocommerce.php' == $plugin['slug'] ) {
							$installed_sites[] = $website->id;
							break;
						}
					}
				}
			}
			$wc_status = self::get_instance()->get_status_by( 'all_status' );
			foreach ( $wc_status as $wc_st ) {
				if ( ! in_array( $wc_st->site_id, $installed_sites ) || ! in_array( $wc_st->site_id, $sites_ids ) ) {
					self::get_instance()->delete_status_by( 'site_id', $wc_st->site_id );
				}
			}
		}

	}

	public function update_status( $status ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		$id      = isset( $status['id'] ) ? $status['id'] : 0;
		$site_id = isset( $status['site_id'] ) ? $status['site_id'] : 0;

		if ( ! empty( $id ) ) {
			if ( $wpdb->update( $this->table_name( 'woo_com_status' ), $status, array( 'id' => intval( $id ) ) ) ) {
				return $this->get_status_by( 'id', $id );
			}
		} elseif ( ! empty( $site_id ) ) {
			$current = $this->get_status_by( 'site_id', $site_id );
			if ( ! empty( $current ) ) {
				if ( $wpdb->update( $this->table_name( 'woo_com_status' ), $status, array( 'site_id' => $site_id ) ) ) {
					return $this->get_status_by( 'site_id', $site_id );
				}
			} else {
				if ( $wpdb->insert( $this->table_name( 'woo_com_status' ), $status ) ) {
					return $this->get_status_by( 'id', $wpdb->insert_id );
				}
			}
		} else {
			unset( $status['id'] );
			if ( $wpdb->insert( $this->table_name( 'woo_com_status' ), $status ) ) {
				return $this->get_status_by( 'id', $wpdb->insert_id );
			}
		}
		return false;
	}

	public function get_status_by( $by = 'id', $value = null ) {
		global $wpdb;

		if ( empty( $by ) ) {
			return null;
		}

		$sql = '';

		if ( 'id' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'woo_com_status' ) . ' WHERE `id`=%d ', $value );
		} elseif ( 'site_id' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'woo_com_status' ) . ' WHERE `site_id` = %d ', $value );
		} elseif ( 'all' == $by ) {
			$sql = 'SELECT * FROM ' . $this->table_name( 'woo_com_status' ) . ' WHERE active = 1';
			return $wpdb->get_results( $sql );
		} elseif ( 'all_status' == $by ) {
			$sql = 'SELECT * FROM ' . $this->table_name( 'woo_com_status' ) . ' WHERE 1';
			return $wpdb->get_results( $sql );
		}

		$status = null;

		if ( ! empty( $sql ) ) {
			$status = $wpdb->get_row( $sql );
		}

		return $status;
	}

	public function delete_status_by( $by = 'id', $value = null ) {
		global $wpdb;

		if ( empty( $by ) ) {
			return null;
		}

		$sql = '';

		if ( 'id' == $by ) {
			$sql = $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'woo_com_status' ) . ' WHERE `id`=%d ', $value );
		} elseif ( 'site_id' == $by ) {
			$sql = $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'woo_com_status' ) . ' WHERE `site_id` = %d ', $value );
		}

		if ( ! empty( $sql ) ) {
			$status = $wpdb->query( $sql );
		}

		return;
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
}

<?php
/** MainWP Sucuri Database. */

/**
 * Class MainWP_Sucuri_DB.
 */
class MainWP_Sucuri_DB {

	/** @var string MainWP Sucuri DB Version. */
	private $mainwp_sucuri_db_version = '1.4';


	/**
	 * Public static variable to hold the single instance of MainWP_Sucuri_DB.
	 *
	 * @var mixed Default null
	 */
	private static $instance = null;

	/** @var string DB Table Prefix. */
	private $table_prefix;

	/**
	 * Create a public static instance of MainWP_Sucuri_DB.
	 *
	 * @return MainWP_Sucuri_DB|mixed|null
	 */
	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new MainWP_Sucuri_DB();
		}
		return self::$instance;
	}

	/**
	 * MainWP_Sucuri_DB constructor.
	 */
	function __construct() {

		/** @global object $wpdb wpdb */
		global $wpdb;

		$this->table_prefix = $wpdb->prefix . 'mainwp_';
	}

	/**
	 * Database table suffix.
	 *
	 * @param string $suffix Table suffix.
	 * @return string Table prefix with appended suffix.
	 */
	function table_name( $suffix ) {
		return $this->table_prefix . $suffix;
	}

	/**
	 * Use Mysqli.
	 *
	 * Support old & new versions of WordPress (3.9+)
	 *
	 * @return bool|object Return FALSE or instance of mysqli.
	 */
	public static function use_mysqli() {

		if ( ! function_exists( 'mysqli_connect' ) ) {
			return false;
		}

		/** @global object $wpdb wpdb */
		global $wpdb;

		return ( $wpdb->dbh instanceof mysqli );
	}

	// Installs new DB

	/**
	 * Install MainWP Sucuri Extension database.
	 *
	 * @uses $wpdb::get_charset_collate()
	 * @uses MainWP_Sucuri_DB::table_name()
	 * @uses MainWP_Sucuri_DB::check_update()
	 */
	function install() {

		/** @global object $wpdb wpdb */
		global $wpdb;

		$currentVersion = get_site_option( 'mainwp_sucuri_db_version' );

		if ( $currentVersion == $this->mainwp_sucuri_db_version ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();
		$sql             = array();

		$tbl = 'CREATE TABLE `' . $this->table_name( 'sucuri' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`site_id` int(11) NOT NULL DEFAULT 0,
`lastscan` int(11) NOT NULL DEFAULT 0,
`remind` varchar(10) NOT NULL,
`lastremind` int(11) NOT NULL DEFAULT 0';
		if ( '' == $currentVersion ) {
			$tbl .= ',
PRIMARY KEY  (`id`)  '; }
		$tbl  .= ') ' . $charset_collate;
		$sql[] = $tbl;

		$tbl = 'CREATE TABLE `' . $this->table_name( 'sucuri_report' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`site_id` int(11) NOT NULL DEFAULT 0,
`timescan` int(11) NOT NULL,
`data` text NOT NULL';
		if ( '' == $currentVersion ) {
			$tbl .= ',
PRIMARY KEY  (`id`)  '; }
		$tbl  .= ') ' . $charset_collate;
		$sql[] = $tbl;

		error_reporting( 0 ); // make sure to disable any error output
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		$this->check_update( $currentVersion );

		update_option( 'mainwp_sucuri_db_version', $this->mainwp_sucuri_db_version );
	}

	/**
	 * Check for Sucuri updates.
	 *
	 * @param $version Plugin version.
	 */
	public function check_update( $version ) {

		/** @global object $wpdb wpdb */
		global $wpdb;

		if ( ! empty( $version ) ) {
			if ( version_compare( $version, '1.4', '<' ) ) {

				$sql    = 'SELECT * FROM ' . $this->table_name( 'sucuri' ) . ' WHERE 1 = 1';
				$result = $wpdb->get_results( $sql );
				if ( is_array( $result ) ) {
					foreach ( $result as $item ) {
						  $website = apply_filters( 'mainwp_getwebsitesbyurl', $item->site_url );
						if ( $website ) {
							$website = current( $website );
							self::get_instance()->update_sucuri(
								array(
									'id'      => $item->id,
									'site_id' => $website->id,
								)
							);
						}
					}
				}

				$sql    = 'SELECT * FROM ' . $this->table_name( 'sucuri_report' ) . ' WHERE 1 = 1';
				$result = $wpdb->get_results( $sql );

				if ( is_array( $result ) ) {
					foreach ( $result as $item ) {
						  $website = apply_filters( 'mainwp_getwebsitesbyurl', $item->site_url );
						if ( $website ) {
							$website = current( $website );
							self::get_instance()->save_report(
								array(
									'id'      => $item->id,
									'site_id' => $website->id,
								)
							);
						}
					}
				}

				$wpdb->query( 'ALTER TABLE ' . $this->table_name( 'sucuri' ) . ' DROP COLUMN site_url' );
				$wpdb->query( 'ALTER TABLE ' . $this->table_name( 'sucuri_report' ) . ' DROP COLUMN site_url' );

				$wpdb->query( 'DELETE FROM ' . $this->table_name( 'sucuri' ) . ' WHERE site_id = 0' );
				$wpdb->query( 'DELETE FROM ' . $this->table_name( 'sucuri_report' ) . ' WHERE site_id = 0' );

			}
		}
	}

	/**
	 * Update Sucuri plugin.
	 *
	 * @param $sucuri
	 * @return bool|int Return FASLE on failure and Plugin ID on success.
	 *
	 * @uses $wpdb->update()
	 * @uses MainWP_Sucuri_DB::table_name()
	 * @uses MainWP_Sucuri_DB::get_sucuri_by()
	 */
	public function update_sucuri( $sucuri ) {

		/** @global object $wpdb wpdb */
		global $wpdb;

		$id = false;
		if ( isset( $sucuri['id'] ) ) {
			$id = intval( $sucuri['id'] );
		}

		if ( $id ) {
			if ( $wpdb->update( $this->table_name( 'sucuri' ), $sucuri, array( 'id' => intval( $id ) ) ) ) {
				return $this->get_sucuri_by( 'id', $id ); }
		} else {
			if ( $wpdb->insert( $this->table_name( 'sucuri' ), $sucuri ) ) {
				return $this->get_sucuri_by( 'id', $wpdb->insert_id );
			}
		}
		return false;
	}

	/**
	 * Update Sucuri by site ID.
	 *
	 * @param int   $site_id Child Site ID.
	 * @param array $sucuri Sucuri array.
	 * @return bool Retern FASLE on failure and Child Site ID on success.
	 *
	 * @uses MainWP_Sucuri_DB::get_sucuri_by()
	 * @uses MainWP_Sucuri_DB::update_sucuri()
	 */
	public function update_sucuri_by_site_id( $site_id, $sucuri ) {
		$current = $this->get_sucuri_by( 'site_id', $site_id );
		if ( $current ) {
			$sucuri['id'] = $current->id;
		} else {
			$sucuri['site_id'] = $site_id;
			$sucuri['id']      = 0; // will create sucuri in db.
		}
		return $this->update_sucuri( $sucuri );
	}

	/**
	 * Update Sucuri by Chile Site URL.
	 *
	 * @param string $site_url Child site URL.
	 * @param array  $sucuri Sucuri array.
	 * @return bool Return FASLE on failure and Child Site DI on success.
	 *
	 * @deprecated not used.
	 */
	public function update_sucuri_by_site_url( $site_url, $sucuri ) {
		$current = $this->get_sucuri_by( 'site_url', $site_url );
		if ( $current ) {
			$sucuri['id'] = $current->id;
		} else {
			$sucuri['site_url'] = $site_url;
			$sucuri['id']       = 0; // will create sucuri in db
		}
		return $this->update_sucuri( $sucuri );
	}

	/**
	 * Get Sucuri by Table ID.
	 *
	 * @param string $by Site ID
	 * @param null   $value Value to search for.
	 * @return bool TRUE on success. FALSE on failure.
	 *
	 * @uses $wpdb::prepare()
	 * @uses $wpdb::cd.get_row()
	 * @uses MainWP_Sucuri_DB::$this->table_name()
	 */
	public function get_sucuri_by( $by = 'id', $value = null ) {

		/** @global object $wpdb wpdb */
		global $wpdb;

		if ( empty( $value ) ) {
			return false;
		}

		if ( 'id' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'sucuri' ) . ' WHERE `id` = %d ', $value );
			return $wpdb->get_row( $sql );
		} elseif ( 'site_id' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'sucuri' ) . ' WHERE `site_id` = %d ', $value );
			return $wpdb->get_row( $sql );
		} elseif ( 'site_url' == $by ) { // not used.
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'sucuri' ) . " WHERE `site_url` = '%s' ", $value );
			return $wpdb->get_row( $sql );
		}
		return false;
	}

	/**
	 * Remove Sucuri report.
	 *
	 * @param $sId Sucuri IDs.
	 * @return bool TRUE on success. FALSE on failure.
	 *
	 * @uses $wpdb::query()
	 * @uses $wpdb::prepare()
	 * @uses MainWP_Sucuri_DB::table_name()
	 *
	 * @deprecated Not used.
	 */
	public function remove_sucuri( $sId ) {

		/** @global object $wpdb wpdb */
		global $wpdb;

		if ( $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'sucuri' ) . ' WHERE id = %d', $sId ) ) ) {
			return true; }
		return false;
	}

	/**
	 * Delete Sucuri by Child Site URL.
	 *
	 * @param string $url Child Site URL.
	 * @return bool TRUE on success. FALSE on failure.
	 *
	 * @deprecated Not used.
	 */
	public function delete_sucuri_by_site_url( $url ) {

		/** @global object $wpdb wpdb */
		global $wpdb;

		$sucuri = $this->get_sucuri_by( 'site_url', $url );
		if ( $sucuri ) {
			$this->remove_sucuri( $sucuri->id );
			return true;
		}
		return false;
	}

	/**
	 * Delete Sucuri by Child Site ID.
	 *
	 * @param int $site_id Child Site ID.
	 * @return bool TRUE on success. FALSE on failure.
	 *
	 * @uses $wpdb::query()
	 * @uses $wpdb::prepare()
	 * @uses MainWP_Sucuri_DB::table_name()
	 */
	public function delete_sucuri_by_site_id( $site_id ) {

		/** @global object $wpdb wpdb */
		global $wpdb;

		if ( $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'sucuri' ) . ' WHERE site_id = %d', $site_id ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Save Sucuri report.
	 *
	 * @param array $report Sucuri report array.
	 *
	 * @return bool|array  Return FALSE on failure or Sucuri Report on success.
	 *
	 * @uses $wpdb::update()
	 * @uses MainWP_Sucuri_DB::table_name()
	 * @uses MainWP_Sucuri_DB::get_report_by()
	 * @uses $wpdb::insert_id()
	 */
	public function save_report( $report ) {

		/** @global object $wpdb wpdb */
		global $wpdb;

		$id = 0;
		if ( isset( $report['id'] ) ) {
			$id = $report['id'];
		}

		if ( $id ) {
			if ( $wpdb->update( $this->table_name( 'sucuri_report' ), $report, array( 'id' => intval( $id ) ) ) ) {
				return $this->get_report_by( 'id', $id ); }
		} else {
			if ( $wpdb->insert( $this->table_name( 'sucuri_report' ), $report ) ) {
				return $this->get_report_by( 'id', $wpdb->insert_id );
			}
		}
		return false;
	}

	/**
	 * Get last Scan.
	 *
	 * @param $site_ids Child Site IDs
	 * @return bool|string $result Return FALSE on failure and last scan time on success.
	 *
	 * @uses $wpdb::get_results()
	 */
	public function get_last_scan( $site_ids ) {

		if ( empty( $site_ids ) ) {
			return false;
		}

		/** @global object $wpdb wpdb */
		global $wpdb;

		$str_ids = implode( ',', $site_ids );

		$last_scan = 'SELECT scan.*
                FROM ' . $this->table_name( 'sucuri_report' ) . ' scan
                LEFT JOIN ' . $this->table_name( 'sucuri_report' ) . ' scan2
                ON scan.site_id = scan2.site_id AND scan.timescan < scan2.timescan
                WHERE scan2.timescan IS NULL';

		$sql = 'SELECT * FROM (' . $last_scan . ') last ' .
				' WHERE `site_id` IN (' . $str_ids . ')';

		$result = $wpdb->get_results( $sql );

		return $result;
	}

	/**
	 * Get Sucuri report by ID, Child Site ID, Child Site URL or TimeScan.
	 *
	 * @param string $by SQL search by.
	 * @param null   $value Value to search for.
	 *
	 * @return bool|mixed $sucuri Return Sucuri report.
	 *
	 * @uses $wpdb::prepare()
	 * @uses $wpdb::get_row()
	 * @uses $wpdb::get_results()
	 * @uses MainWP_Sucuri_DB::table_name()
	 */
	public function get_report_by( $by = 'id', $value = null ) {

		/** @global object $wpdb wpdb */
		global $wpdb;

		if ( empty( $value ) ) {
			return false; }
		if ( 'id' == $by ) {
			$sql    = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'sucuri_report' ) . ' WHERE `id` = %d ', $value );
			$sucuri = $wpdb->get_row( $sql );
			return $sucuri;
		} elseif ( 'site_id' == $by ) {
			$sql    = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'sucuri_report' ) . ' WHERE `site_id` = %d ORDER BY timescan DESC', $value );
			$sucuri = $wpdb->get_results( $sql );
			return $sucuri;
		}
		// not used
		elseif ( 'site_url' == $by ) {
			$sql    = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'sucuri_report' ) . " WHERE `site_url` = '%s' ORDER BY timescan DESC", $value );
			$sucuri = $wpdb->get_results( $sql );
			return $sucuri;
		} elseif ( 'timescan' == $by ) {
			$sql    = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'sucuri_report' ) . ' WHERE `timescan` = %d LIMIT 1', $value );
			$sucuri = $wpdb->get_row( $sql );
			return $sucuri;
		}
		return false;
	}

	/**
	 * Remove report by ID.
	 *
	 * @param string $by Report ID.
	 * @param string $value Value to search.
	 * @return bool TRUE|FALSE.
	 *
	 * @uses $wpdb::query()
	 * @uses $wpdb::prepare()
	 * @uses MainWP_Sucuri_DB::table_name()
	 */
	public function remove_report_by( $by = 'id', $value = false ) {

		/** @global object $wpdb wpdb */
		global $wpdb;
		if ( 'id' == $by && ! empty( $value ) ) {
			if ( $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'sucuri_report' ) . ' WHERE id = %d', $value ) ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Escape data.
	 *
	 * @param array $data Data to escape.
	 * @return array Return escaped data.
	 *
	 * @uses $wpdb::escape()
	 */
	protected function escape( $data ) {

		/** @global object $wpdb wpdb */
		global $wpdb;

		if ( function_exists( 'esc_sql' ) ) {
			return esc_sql( $data );
		} else {
			return $wpdb->escape( $data ); }
	}

	/**
	 * SQL Query.
	 *
	 * @param string $sql SQL query string.
	 * @return bool|mysqli_result|resource $result Return FALSE on failure & query results on success.
	 *
	 * @uses MainWP_Sucuri_DB::_query()
	 * @uses MainWP_Sucuri_DB::num_rows()
	 */
	public function query( $sql ) {
		if ( null == $sql ) {
			return false; }

		/** @global object $wpdb wpdb */
		global $wpdb;

		$result = @self::_query( $sql, $wpdb->dbh );

		if ( ! $result || ( @self::num_rows( $result ) == 0 ) ) {
			return false; }
		return $result;
	}

	/**
	 * MySQLi Query.
	 *
	 * @param string $query Query string.
	 * @param string $link Query link.
	 *
	 * @return bool|mysqli_result|resource FALSE on failure & query results on success.
	 */
	public static function _query( $query, $link ) {
		if ( self::use_mysqli() ) {
			return mysqli_query( $link, $query );
		} else {
			return mysql_query( $query, $link );
		}
	}

	/**
	 * Return the current row of a result set, then print each field's value.
	 *
	 * @param string $result Required. Specifies a result set identifier.
	 * @return object|stdClass|null Return fetched object or Null.
	 *
	 * @uses MainWP_Sucuri_DB::use_mysqli()
	 */
	public static function fetch_object( $result ) {
		if ( self::use_mysqli() ) {
			return mysqli_fetch_object( $result );
		} else {
			return mysql_fetch_object( $result );
		}
	}

	/**
	 * Fetch rows from a result-set, then free the memory associated with the result.
	 *
	 * @param string $result Required. Specifies a result set identifier.
	 * @return object|null Return fetched object or Null.
	 *
	 * @uses MainWP_Sucuri_DB::use_mysqli()
	 */
	public static function free_result( $result ) {
		if ( self::use_mysqli() ) {
			return mysqli_free_result( $result );
		} else {
			return mysql_free_result( $result );
		}
	}

	/**
	 * Seek to row offset in the result-set.
	 *
	 * @param string $result Required. Specifies a result set identifier.
	 * @param int    $offset Required. Specifies the field offset. Must be between 0 and the total number of rows - 1
	 * @return bool TRUE on success. FALSE on failure.
	 *
	 * @uses MainWP_Sucuri_DB::use_mysqli()
	 */
	public static function data_seek( $result, $offset ) {
		if ( self::use_mysqli() ) {
			return mysqli_data_seek( $result, $offset );
		} else {
			return mysql_data_seek( $result, $offset );
		}
	}

	/**
	 * Fetch a result row as a numeric array and as an associative array.
	 *
	 * @param string $result Required. Specifies a result set identifier.
	 * @param null   $result_type Optional. Specifies what type of array that should be produced.
	 * @return array|false|null Returns an array of strings that corresponds to the fetched row. NULL if there are no more rows in result-set.
	 * @uses MainWP_Sucuri_DB::use_mysqli()
	 */
	public static function fetch_array( $result, $result_type = null ) {
		if ( self::use_mysqli() ) {
			return mysqli_fetch_array( $result, ( null == $result_type ? MYSQLI_BOTH : $result_type ) );
		} else {
			return mysql_fetch_array( $result, ( null == $result_type ? MYSQL_BOTH : $result_type ) );
		}
	}

	/**
	 * Return the number of rows in a result set.
	 *
	 * @param string $result Required. Specifies a result set identifier.
	 * @return false|int Returns the number of rows in the result set or FALSE on failure.
	 *
	 * @uses MainWP_Sucuri_DB::use_mysqli()
	 */
	public static function num_rows( $result ) {
		if ( self::use_mysqli() ) {
			return mysqli_num_rows( $result );
		} else {
			return mysql_num_rows( $result );
		}
	}

	/**
	 * Is result.
	 *
	 * @param string $result Required. Specifies a result set identifier.
	 * @return bool|mysqli_result|resource Return FALSE on failure, Instance of mysqli_result or resource.
	 *
	 * @uses MainWP_Sucuri_DB::use_mysqli()
	 */
	public static function is_result( $result ) {
		if ( self::use_mysqli() ) {
			return ( $result instanceof mysqli_result );
		} else {
			return is_resource( $result );
		}
	}

	/**
	 * Executes a SQL query and returns the entire SQL result.
	 *
	 * @param string $sql SQL query string.
	 * @return array|object|null Database query results.
	 *
	 * @uses $wpdb::get_results()
	 */
	public function get_results_result( $sql ) {
		if ( null == $sql ) {
			return null; }

		/** @global object $wpdb wpdb */
		global $wpdb;

		return $wpdb->get_results( $sql, OBJECT_K );
	}
}

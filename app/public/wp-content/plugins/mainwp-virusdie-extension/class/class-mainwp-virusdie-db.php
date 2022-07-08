<?php
/**
 * MainWP Virusdie DB
 *
 * This file handles DB interactions.
 *
 * @package MainWP/Extensions
 */

namespace MainWP\Extensions\Virusdie;

/**
 * Class MainWP_Virusdie_DB
 */
class MainWP_Virusdie_DB {

	// phpcs:disable WordPress.DB.RestrictedFunctions, WordPress.DB.PreparedSQL.NotPrepared, Generic.Metrics.CyclomaticComplexity -- This is the only way to achieve desired results, pull request solutions appreciated.

	/**
	 * Version DB string.
	 *
	 * @var string MainWP Virusdie DB Version.
	 * */
	private $mainwp_virusdie_db_version = '2.3';


	/**
	 * Public static variable to hold the single instance of MainWP_Virusdie_DB.
	 *
	 * @var mixed Default null
	 */
	private static $instance = null;

	/**
	 * Table prefix
	 *
	 * @var string
	 */
	private $table_prefix;

	/**
	 * Create a public static instance of MainWP_Virusdie_DB.
	 *
	 * @return MainWP_Virusdie_DB|mixed|null
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new MainWP_Virusdie_DB();
		}
		return self::$instance;
	}

	/**
	 * MainWP_Virusdie_DB constructor.
	 */
	public function __construct() {

		/**
		 * WP DB Object
		 *
		 * @global object
		 */
		global $wpdb;

		$this->table_prefix = $wpdb->prefix . 'mainwp_';
	}

	/**
	 * Database table suffix.
	 *
	 * @param string $suffix Table suffix.
	 *
	 * @return string Table prefix with appended suffix.
	 */
	public function table_name( $suffix ) {
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

		/**
		 * WP DB Object
		 *
		 * @global object
		 */
		global $wpdb;

		return ( $wpdb->dbh instanceof mysqli );
	}

	/**
	 * Installs MainWP Virusdie Extension database.
	 *
	 * @return void
	 */
	public function install() {

		/**
		 * WP DB Object
		 *
		 * @global object
		 */
		global $wpdb;

		$currentVersion = get_site_option( 'mainwp_virusdie_db_version' );

		if ( $currentVersion == $this->mainwp_virusdie_db_version ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();
		$sql             = array();

		$tbl = 'CREATE TABLE `' . $this->table_name( 'virusdie' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`site_id` int(11) NOT NULL DEFAULT 0,
`domain` text NOT NULL DEFAULT "",
`virusdie_item_id` int(11) NOT NULL DEFAULT 0,
`autocleanup` tinyint(1) NOT NULL DEFAULT 0,
`firewall` varchar(32) NOT NULL DEFAULT "",
`last_scandate` int(11) NOT NULL DEFAULT 0';
		if ( '' == $currentVersion ) {
			$tbl .= ',
PRIMARY KEY  (`id`)  '; }
		$tbl  .= ') ' . $charset_collate;
		$sql[] = $tbl;

		$tbl = 'CREATE TABLE `' . $this->table_name( 'virusdie_report' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`virusdie_item_id` int(11) NOT NULL DEFAULT 0,
`cure` tinyint NOT NULL DEFAULT 0,
`detectedfiles` int(11) NOT NULL DEFAULT 0,
`incurablefiles` int(11) NOT NULL DEFAULT 0,
`malicious` int(11) NOT NULL DEFAULT 0,
`checkeddirs` int(11) NOT NULL DEFAULT 0,
`checkedfiles` int(11) NOT NULL DEFAULT 0,
`suspicious` int(11) NOT NULL DEFAULT 0,
`treatedfiles` int(11) NOT NULL DEFAULT 0,
`deletedfiles` int(11) NOT NULL DEFAULT 0,
`checkedbytes` int(11) NOT NULL DEFAULT 0,
`status` text NOT NULL DEFAULT "",
`scandate` int(11) NOT NULL DEFAULT 0';

		if ( '' == $currentVersion ) {
			$tbl .= ',
PRIMARY KEY  (`id`)  '; }
		$tbl  .= ') ' . $charset_collate;
		$sql[] = $tbl;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		$this->check_update( $currentVersion );

		update_option( 'mainwp_virusdie_db_version', $this->mainwp_virusdie_db_version );
	}

	/**
	 * Check for Virusdie updates.
	 *
	 * @param string $version Plugin version.
	 */
	public function check_update( $version ) {
		/**
		 * WP DB Object
		 *
		 * @global object
		 */
		global $wpdb;
	}

	/**
	 * Update Virusdie by site ID.
	 *
	 * @param array $virusdie Virusdie array.
	 *
	 * @return mixed Retern FASLE on failure and Child Site ID on success.
	 */
	public function update_virusdie( $virusdie ) {

		$virusdie_id = isset( $virusdie['virusdie_item_id'] ) ? intval( $virusdie['virusdie_item_id'] ) : 0;
		$current     = null;

		if ( $virusdie_id ) {
			$current = $this->get_virusdie_by( 'virusdie_item_id', $virusdie_id );
			if ( $current && empty( $current->site_id ) ) {
				$website = apply_filters( 'mainwp_getwebsitesbyurl', $current->domain );
				if ( $website ) {
					$website             = current( $website );
					$virusdie['site_id'] = $website->id;
				}
			}
		}

		if ( empty( $current ) && empty( $virusdie['site_id'] ) ) {
			$domain = isset( $virusdie['domain'] ) ? $virusdie['domain'] : false;
			if ( $domain ) {
				$website = apply_filters( 'mainwp_getwebsitesbyurl', $domain );
				if ( $website ) {
					$website             = current( $website );
					$virusdie['site_id'] = $website->id;
				}
			}
		}

		if ( empty( $current ) && ! empty( $virusdie['site_id'] ) ) {
			$current = $this->get_virusdie_by( 'site_id', $virusdie['site_id'] );
		}

		if ( empty( $current ) && ! empty( $virusdie['domain'] ) ) {
			$current = $this->get_virusdie_by( 'domain', $virusdie['domain'] );
		}

		global $wpdb;

		if ( $current ) {
			$wpdb->update( $this->table_name( 'virusdie' ), $virusdie, array( 'id' => intval( $current->id ) ) );
			return $this->get_virusdie_by( 'id', $current->id );
		} else {
			if ( $wpdb->insert( $this->table_name( 'virusdie' ), $virusdie ) ) {
				return $this->get_virusdie_by( 'id', $wpdb->insert_id );
			}
		}
		return false;
	}

	/**
	 * Update Virusdie site id by domain.
	 *
	 * @param string $domain Virusdie domain.
	 * @param int $site_id Site id.
	 *
	 * @return mixed Retern FASLE on failure and Child Site ID on success.
	 */
	public function update_virusdie_site_id( $domain, $site_id ) {
		global $wpdb;
		$current = $this->get_virusdie_by( 'domain', $domain );
		if ( $current && $site_id != $current->site_id ) {
			$wpdb->update( $this->table_name( 'virusdie' ), array( 'site_id' => $site_id ), array( 'id' => intval( $current->id ) ) );
			return $this->get_virusdie_by( 'id', $current->id );
		}
		return false;
	}

	/**
	 * Gets the last scan.
	 *
	 * @param int $virusdie_id Virusdie site ID.
	 *
	 * @return array $lastscan Last scan data array.
	 */
	public function get_lastscan( $virusdie_id ) {
		$params = array(
			'by_value' => $virusdie_id,
		);

		$results = self::get_instance()->get_report_by( 'virusdie_item_id', $params );

		$lastscan = false;

		if ( $results ) {
			$lastscan = current( $results );
		}

		return $lastscan;
	}

	/**
	 * Gets the Virusdie item.
	 *
	 * @param string $by    Site ID.
	 * @param string $value Value to search for.
	 *
	 * @return mixed Returs the query result.
	 */
	public function get_virusdie_by( $by = 'id', $value = false ) {

		/**
		 * WP DB Object
		 *
		 * @global object
		 */
		global $wpdb;

		if ( 'id' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'virusdie' ) . ' WHERE `id` = %d ', $value );
			return $wpdb->get_row( $sql );
		} elseif ( 'virusdie_item_id' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'virusdie' ) . ' WHERE `virusdie_item_id` = %d ', $value );
			return $wpdb->get_row( $sql );
		} elseif ( 'site_id' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'virusdie' ) . ' WHERE `site_id` = %d ', $value );
			return $wpdb->get_row( $sql );
		} elseif ( 'domain' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'virusdie' ) . ' WHERE `domain` = %s ', $value );
			return $wpdb->get_row( $sql );
		} elseif ( 'all' == $by ) {
			return $wpdb->get_results( 'SELECT * FROM ' . $this->table_name( 'virusdie' ) . ' WHERE 1 ' );
		}
		return false;
	}

	/**
	 * Removes Virusdie report.
	 *
	 * @param int $id Virusdie item ID.
	 *
	 * @return bool TRUE on success. FALSE on failure.
	 */
	public function remove_virusdie( $id ) {

		/**
		 * WP DB Object
		 *
		 * @global object
		 */
		global $wpdb;

		if ( $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'virusdie' ) . ' WHERE id = %d', $id ) ) ) {
			return true;
		}
		return false;
	}


	/**
	 * Deletes Virusdie item by Child Site ID.
	 *
	 * @param int $site_id Child Site ID.
	 *
	 * @return bool TRUE on success. FALSE on failure.
	 */
	public function delete_virusdie_by_site_id( $site_id ) {

		/**
		 * WP DB Object
		 *
		 * @global object
		 */
		global $wpdb;

		if ( $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'virusdie' ) . ' WHERE site_id = %d', $site_id ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Saves Virusdie scan report.
	 *
	 * @param array $data Virusdie report array.
	 *
	 * @return bool|array  Return FALSE on failure or Virusdie Report on success.
	 */
	public function save_report( $data ) {
		/**
		 * WP DB Object
		 *
		 * @global object
		 */
		global $wpdb;

		$id = 0;

		if ( isset( $data['id'] ) ) {
			$id = $data['id'];
		}

		if ( $id ) {
			if ( $wpdb->update( $this->table_name( 'virusdie_report' ), $data, array( 'id' => intval( $id ) ) ) ) {
				$params = array(
					'by_value' => $id,
				);
				return self::get_instance()->get_report_by( 'id', $params );
			}
		} else {
			if ( $wpdb->insert( $this->table_name( 'virusdie_report' ), $data ) ) {
				$params = array(
					'by_value' => $wpdb->insert_id,
				);
				return self::get_instance()->get_report_by( 'id', $params );
			}
		}
		return false;
	}

	/**
	 * Gets last Scan.
	 *
	 * @param array $site_ids Child Site IDs.
	 *
	 * @return bool|string $result Return FALSE on failure and last scan time on success.
	 */
	public function get_last_scan( $site_ids ) {

		if ( empty( $site_ids ) ) {
			return false;
		}

		/**
		 * WP DB Object
		 *
		 * @global object
		 */
		global $wpdb;

		$str_ids = implode( ',', $site_ids );

		$last_scan = 'SELECT scan.*
                FROM ' . $this->table_name( 'virusdie_report' ) . ' scan
                LEFT JOIN ' . $this->table_name( 'virusdie_report' ) . ' scan2
                ON scan.site_id = scan2.site_id AND scan.scandate < scan2.scandate
                WHERE scan2.scandate IS NULL';

		$sql = 'SELECT * FROM (' . $last_scan . ') last ' .
				' WHERE `site_id` IN (' . $str_ids . ')';

		$result = $wpdb->get_results( $sql );

		return $result;
	}

	/**
	 * Gets Virusdie report by ID, Child Site ID, Child Site URL or Timestamp.
	 *
	 * @param string $by     By value.
	 * @param array  $params Parametters.
	 *
	 * @return bool|mixed $virusdie Return Virusdie report.
	 */
	public function get_report_by( $by, $params = array() ) {

		$value = isset( $params['by_value'] ) ? $params['by_value'] : '';
		$limit = isset( $params['limit'] ) ? $params['limit'] : 0;

		if ( empty( $value ) ) {
			return false;
		}

		/**
		 * WP DB Object
		 *
		 * @global object
		 */
		global $wpdb;

		if ( ! empty( $limit ) ) {
			$limit = 'LIMIT ' . intval( $limit );
		} else {
			$limit = '';
		}

		if ( 'id' == $by ) {
			$sql      = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'virusdie_report' ) . ' WHERE `id` = %d', $value );
			$virusdie = $wpdb->get_row( $sql );
			return $virusdie;
		} elseif ( 'virusdie_item_id' == $by ) {
			if ( isset( $params['start_date'] ) && isset( $params['end_date'] ) ) {
				$start_date = isset( $params['start_date'] ) ? $params['start_date'] : 0;
				$end_date   = isset( $params['end_date'] ) ? $params['end_date'] : 0;
				if ( empty( $start_date ) || empty( $end_date ) ) {
					return false;
				}
				$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'virusdie_report' ) . ' WHERE `virusdie_item_id` = %d AND `scandate` > %d AND `scandate` < %d ORDER BY scandate DESC ' . $limit, $value, $start_date, $end_date );
			} else {
				$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'virusdie_report' ) . ' WHERE `virusdie_item_id` = %d ORDER BY scandate DESC ' . $limit, $value );
			}
			$virusdies = $wpdb->get_results( $sql );
			return $virusdies;
		}

		return false;
	}

	/**
	 * Removes report by ID.
	 *
	 * @param string $by    Report ID.
	 * @param string $value Value to search.
	 *
	 * @return bool TRUE on success. FALSE on failure.
	 */
	public function remove_report_by( $by = 'id', $value = false ) {

		/**
		 * WP DB Object
		 *
		 * @global object
		 */
		global $wpdb;
		if ( 'id' == $by && ! empty( $value ) ) {
			if ( $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'virusdie_report' ) . ' WHERE id = %d', $value ) ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Escape data.
	 *
	 * @param array $data Data to escape.
	 *
	 * @return array Returns escaped data.
	 */
	protected function escape( $data ) {

		/**
		 * WP DB Object
		 *
		 * @global object
		 */
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
	 *
	 * @return mixed $result Return FALSE on failure & query results on success.
	 */
	public function query( $sql ) {
		if ( null == $sql ) {
			return false; }

			/**
			 * WP DB Object
			 *
			 * @global object
			 */
		global $wpdb;

		$result = self::m_query( $sql, $wpdb->dbh );

		if ( ! $result || ( 0 == self::num_rows( $result ) ) ) {
			return false; }
		return $result;
	}

	/**
	 * MySQLi Query.
	 *
	 * @param string $query Query string.
	 * @param string $link  Query link.
	 *
	 * @return mixed FALSE on failure & query results on success.
	 */
	public static function m_query( $query, $link ) {
		if ( self::use_mysqli() ) {
			return mysqli_query( $link, $query );
		} else {
			return mysql_query( $query, $link );
		}
	}

	/**
	 * Returns the current row of a result set, then print each field's value.
	 *
	 * @param string $result Required. Specifies a result set identifier.
	 *
	 * @return mixed Return fetched object or Null.
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
	 *
	 * @return object Return fetched object or Null.
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
	 * @param int    $offset Required. Specifies the field offset. Must be between 0 and the total number of rows - 1.
	 *
	 * @return bool TRUE on success. FALSE on failure.
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
	 * @param string $result      Required. Specifies a result set identifier.
	 * @param null   $result_type Optional. Specifies what type of array that should be produced.
	 *
	 * @return mixed Returns an array of strings that corresponds to the fetched row. NULL if there are no more rows in result-set.
	 */
	public static function fetch_array( $result, $result_type = null ) {
		if ( self::use_mysqli() ) {
			return mysqli_fetch_array( $result, ( null == $result_type ? MYSQLI_BOTH : $result_type ) );
		} else {
			return mysql_fetch_array( $result, ( null == $result_type ? MYSQL_BOTH : $result_type ) );
		}
	}

	/**
	 * Returns the number of rows in a result set.
	 *
	 * @param string $result Required. Specifies a result set identifier.
	 *
	 * @return int Returns the number of rows in the result set or FALSE on failure.
	 */
	public static function num_rows( $result ) {
		if ( self::use_mysqli() ) {
			return mysqli_num_rows( $result );
		} else {
			return mysql_num_rows( $result );
		}
	}

	/**
	 * Checks if there are results.
	 *
	 * @param string $result Required. Specifies a result set identifier.
	 *
	 * @return mixed Return FALSE on failure, Instance of mysqli_result or resource.
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
	 *
	 * @return mixed Database query results.
	 */
	public function get_results_result( $sql ) {
		if ( null == $sql ) {
			return null; }

			/**
			 * WP DB Object
			 *
			 * @global object
			 */
		global $wpdb;

		return $wpdb->get_results( $sql, OBJECT_K );
	}
}

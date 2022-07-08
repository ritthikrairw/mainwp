<?php
class Keyword_Links_DB
{
	//Config
	private $mainwp_keyword_links_db_version = '1.5';
	//Singleton
	private static $instance = null;
	private $table_prefix;

	static function get_instance() {

		if ( null == Keyword_Links_DB::$instance ) {
			Keyword_Links_DB::$instance = new Keyword_Links_DB();
		}
		return Keyword_Links_DB::$instance;
	}

	//Constructor
	function __construct() {

		global $wpdb;
		$this->table_prefix = $wpdb->prefix . 'mainwp_';
	}

	function table_name( $suffix ) {

		return $this->table_prefix . $suffix;
	}

	//Installs new DB
	function install() {

		global $wpdb;
		$currentVersion = get_site_option( 'mainwp_keyword_links_db_version' );
		if ( $currentVersion == $this->mainwp_keyword_links_db_version ) { return; }

		if ( version_compare( $currentVersion,'1.0', '<=' ) ) {
			$currentVersion = 0; // To fix DB
		}
		if ( version_compare( $currentVersion,'1.2', '=' ) ) {
			$wpdb->query( 'ALTER TABLE ' . $this->table_name( 'keyword_links_link' ) . ' DROP COLUMN specific_sites' );
			$wpdb->query( 'ALTER TABLE ' . $this->table_name( 'keyword_links_link' ) . ' DROP COLUMN specific_groups' );
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = array();
		$tbl = 'CREATE TABLE `' . $this->table_name( 'keyword_links_link' ) . '` (
`id` INT NOT NULL AUTO_INCREMENT,
`name` VARCHAR(512), 
`destination_url` VARCHAR(512),
`cloak_path` VARCHAR(512),
`keyword` text NOT NULL,
`sites` text NOT NULL,
`groups` text NOT NULL,
`type` tinyint(1) NOT NULL,
`exact_match` tinyint(1) NOT NULL DEFAULT 1,
`case_sensitive` tinyint(1) NOT NULL DEFAULT 1,
`link_target` VARCHAR(512),
`link_rel` VARCHAR(512),
`link_class` VARCHAR(512)';
		if ( '' == $currentVersion ) {
					$tbl .= ',
PRIMARY KEY  (`id`)  '; }
		$tbl .= ') ' . $charset_collate;

		$sql[] = $tbl;

		$tbl = 'CREATE TABLE `' . $this->table_name( 'keyword_links_group' ) . '` (
`id` INT NOT NULL AUTO_INCREMENT,
`name` VARCHAR(512)';
		if ( '' == $currentVersion ) {
					$tbl .= ',
PRIMARY KEY  (`id`)  '; }
		$tbl .= ') ' . $charset_collate;
		$sql[] = $tbl;

		$tbl = 'CREATE TABLE `' . $this->table_name( 'keyword_links_link_group' ) . '` (
`id` INT NOT NULL AUTO_INCREMENT,                                          
`group_id` INT,               
`link_id` INT';
		if ( '' == $currentVersion ) {
					$tbl .= ',
PRIMARY KEY  (`id`)  '; }
		$tbl .= ') ' . $charset_collate;
		$sql[] = $tbl;

		$tbl = 'CREATE TABLE `' . $this->table_name( 'keyword_links_statistic' ) . '` (
`id` INT NOT NULL AUTO_INCREMENT,
`link_id` INT,
`date` DATETIME,
`ip` CHAR(15), 
`type` VARCHAR(32),
`referer` VARCHAR(512)';
		if ( '' == $currentVersion ) {
					$tbl .= ',
PRIMARY KEY  (`id`) '; }
		$tbl .= ') ' . $charset_collate;

		$sql[] = $tbl;

		error_reporting( 0 ); // make sure to disable any error output
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}
		//        global $wpdb;
		//        echo $wpdb->last_error;
		//        exit();

		update_option( 'mainwp_keyword_links_db_version', $this->mainwp_keyword_links_db_version );
	}

	public function get_links_by( $by, $value = null ) {
		global $wpdb;
		if ( 'id' == $by && ! empty( $value ) ) {
			return $wpdb->get_row( 'SELECT * FROM ' . $this->table_name( 'keyword_links_link' ) . ' WHERE id = ' . $value ); } else if ( 'all' == $by ) {
			return $wpdb->get_results( sprintf( 'SELECT * FROM `%s` ORDER BY name', $this->table_name( 'keyword_links_link' ) ) ); }
			return array();
	}

	public function get_data_row( $table, $by, $value ) {
		global $wpdb;
		if ( 'id' == $by ) {
			return $wpdb->get_row( 'SELECT * FROM ' . $table . ' WHERE id = ' . $value ); }
		return false;
	}

	public function add_statistic( $link_id, $timestamp, $remote_addr, $referer, $type = 'click' ) {
		global $wpdb;
		$date = date( 'Y-m-d H:i:s', $timestamp );
		if ( $link_id ) {
			if ( $wpdb->insert( $this->table_name( 'keyword_links_statistic' ), array( 'link_id' => $link_id, 'date' => $date, 'ip' => $this->escape( $remote_addr ), 'referer' => $this->escape( $referer ), 'type' => $type ) ) ) {
				return $wpdb->insert_id;
			}
		}
		return false;
	}

	//Support old & new versions of wordpress (3.9+)
	public static function use_mysqli() {

		/** @var $wpdb wpdb */
		if ( ! function_exists( 'mysqli_connect' ) ) { return false; }

		global $wpdb;
		return ($wpdb->dbh instanceof mysqli);
	}


	public function query( $sql ) {

		if ( null == $sql ) { return false; }

		/** @var $wpdb wpdb */
		global $wpdb;
		$result = @self::_query( $sql, $wpdb->dbh );

		if ( ! $result || (0 == @self::num_rows( $result )) ) { return false; }
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
			return mysqli_fetch_array( $result, (null == $result_type ? MYSQLI_BOTH : $result_type) );
		} else {
			return mysql_fetch_array( $result, (null == $result_type ? MYSQL_BOTH : $result_type) );
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
			return ($result instanceof mysqli_result);
		} else {
			return is_resource( $result );
		}
	}

	protected function escape( $data ) {

		/** @var $wpdb wpdb */
		global $wpdb;

		if ( function_exists( 'esc_sql' ) ) { return esc_sql( $data ); } else { return $wpdb->escape( $data ); }
	}
}

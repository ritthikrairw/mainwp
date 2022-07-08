<?php
class MainWP_Extract_Urls_DB {
	private $mainwp_extract_urls_db_version = '1.0';
	private static $instance                = null;
	private $table_prefix;

	static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new MainWP_Extract_Urls_DB();
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
		$currentVersion = get_site_option( 'mainwp_extract_urls_db_version' );
		if ( $currentVersion == $this->mainwp_extract_urls_db_version ) {
			return; }
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = array();

		$tbl = 'CREATE TABLE `' . $this->table_name( 'extract_urls_template' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`title` text NOT NULL,
`format_output` text NOT NULL,
`separator` varchar(32) NOT NULL';
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

		update_option( 'mainwp_extract_urls_db_version', $this->mainwp_extract_urls_db_version );
	}

	public function update_template( $template ) {

		 /** @var $wpdb wpdb */
		global $wpdb;
		$id = null;
		if ( isset( $template['id'] ) ) {
			$id = $template['id']; }
		if ( $id ) {
			if ( $wpdb->update( $this->table_name( 'extract_urls_template' ), $template, array( 'id' => intval( $id ) ) ) ) {
				return $this->get_template_by( 'id', $id );
			}
		} elseif ( $wpdb->insert( $this->table_name( 'extract_urls_template' ), $template ) ) {
			return $this->get_template_by( 'id', $wpdb->insert_id );
		}
		return false;
	}

	public function get_template_by( $by = 'id', $value = null ) {
		global $wpdb;

		if ( empty( $by ) || ( 'all' !== $by && empty( $value ) ) ) {
			return null;
		}

		$sql = '';

		if ( 'id' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'extract_urls_template' ) . ' WHERE `id`=%d ', $value );
		} elseif ( 'all' == $by ) {
			$sql = 'SELECT * FROM ' . $this->table_name( 'extract_urls_template' ) . ' WHERE 1 = 1 ORDER BY title';
			return $wpdb->get_results( $sql );
		}

		$template = null;

		if ( ! empty( $sql ) ) {
			$template = $wpdb->get_row( $sql );
		}

		return $template;
	}

	public function delete_template( $id ) {

		/** @var $wpdb wpdb */
		global $wpdb;

		if ( empty( $id ) ) {
			return false;
		}

		if ( $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'extract_urls_template' ) . ' WHERE id=%d ', $id ) ) ) {
			return true;
		}

		return false;
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
			return null; }
		/** @var $wpdb wpdb */
		global $wpdb;
		return $wpdb->get_results( $sql, OBJECT_K );
	}
}

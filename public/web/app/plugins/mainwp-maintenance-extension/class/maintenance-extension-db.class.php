<?php
class Maintenance_Extension_DB {
	// Config
	private $mainwp_maintenance_extension_db_version = '1.5';
	// Singleton
	private static $instance = null;
	private $table_prefix;

	static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new Maintenance_Extension_DB();
		}
		return self::$instance;
	}

	// Constructor
	function __construct() {
		global $wpdb;
		$this->table_prefix = $wpdb->prefix . 'mainwp_';
	}

	private function table_name( $suffix ) {
		return $this->table_prefix . $suffix;
	}

	// Installs new DB
	function install() {
		$currentVersion = get_site_option( 'mainwp_maintenance_extension_db_version' );
		$rslt           = self::get_instance()->query( "SHOW TABLES LIKE '" . $this->table_name( 'maintenance_schedule' ) . "'" );

		if ( self::num_rows( $rslt ) == 0 ) {
			$currentVersion = false;
		}

		if ( $currentVersion == $this->mainwp_maintenance_extension_db_version ) {
			return; }

		$sql = array();
		$tbl = 'CREATE TABLE `' . $this->table_name( 'maintenance_schedule' ) . '` (
`id` int NOT NULL AUTO_INCREMENT,
`wpid`  int(11) NOT NULL,
`title` varchar(512),
`options` varchar(512),
`revisions` int(4) NOT NULL,
`perform` smallint NOT NULL DEFAULT 1,
`schedule` varchar(10),
`sites` text NOT NULL,
`groups` text NOT NULL,
`last_run` int(11) NOT NULL,
`recurring_day` VARCHAR(10) DEFAULT NULL,
`recurring_hour` tinyint NOT NULL DEFAULT 0,
`schedule_nextsend` int(11) NOT NULL,
`schedule_lastsend` int(11) NOT NULL,
`completed_sites` text NOT NULL,
`last_run_manually` int(11) NOT NULL,
`completed` int(11) NOT NULL';
		if ( '' == $currentVersion ) {
					$tbl .= ',
PRIMARY KEY  (`id`)  '; }
		$tbl .= ')';

		$sql[] = $tbl;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		$this->check_update( $currentVersion );
		update_option( 'mainwp_maintenance_extension_db_version', $this->mainwp_maintenance_extension_db_version );
	}

	function check_update( $version ) {
		global $wpdb;
		if ( ! empty( $version ) ) {
			if ( version_compare( $version, '1.3', '<' ) ) {
				$wpdb->query( 'ALTER TABLE ' . $this->table_name( 'maintenance_schedule' ) . ' DROP COLUMN `userid`' );
			}

			if ( version_compare( $version, '1.5', '<' ) ) {
				$wpdb->query( 'ALTER TABLE ' . $this->table_name( 'maintenance_schedule' ) . ' DROP COLUMN `last`' );
			}
		}
	}

	public function remove_maintenance_task( $id ) {
		global $wpdb;
		$wpdb->query( 'DELETE FROM ' . $this->table_name( 'maintenance_schedule' ) . ' WHERE id = ' . $id );
	}

	public function get_maintenance_task_by_id( $id ) {
		/** @var $wpdb wpdb */
		global $wpdb;
		return $wpdb->get_row( 'SELECT * FROM ' . $this->table_name( 'maintenance_schedule' ) . ' WHERE id= ' . $id );
	}

	public function get_maintenance_tasks( $orderby = 'title' ) {
		/** @var $wpdb wpdb */
		global $wpdb;
		return $wpdb->get_results( 'SELECT * FROM ' . $this->table_name( 'maintenance_schedule' ) . ' WHERE 1 = 1 ORDER BY ' . $orderby, OBJECT );
	}

	public function update_task( $id, $values ) {
		global $wpdb;
		if ( empty( $id ) ) {
			$values['last_run'] = 0; // new task
			if ( $wpdb->insert( $this->table_name( 'maintenance_schedule' ), $values ) ) {
				return $this->get_maintenance_task_by_id( $wpdb->insert_id );
			}
			return false;
		}

		return $wpdb->update( $this->table_name( 'maintenance_schedule' ), $values, array( 'id' => $id ) );
	}

	public function update_maintenance_task_values( $id, $values ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		if ( ! is_array( $values ) ) {
			return false;
		}

		return $wpdb->update( $this->table_name( 'maintenance_schedule' ), $values, array( 'id' => $id ) );
	}

	// not used any more
	public function update_maintenance_run( $id ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		if ( empty( $id ) ) {
			return false;
		}

		return $wpdb->update( $this->table_name( 'maintenance_schedule' ), array( 'last_run' => time() ), array( 'id' => $id ) );
	}

	public function update_completed_sites( $id, $completedSites ) {
		global $wpdb;
		return $wpdb->update( $this->table_name( 'maintenance_schedule' ), array( 'completed_sites' => json_encode( $completedSites ) ), array( 'id' => $id ) );
	}

	public function get_scheduled_to_start() {
		global $wpdb;
		$sql = 'SELECT ch.* FROM ' . $this->table_name( 'maintenance_schedule' ) . ' ch WHERE '
			// . " WHERE (ch.schedule = 'weekly' || ch.schedule = 'monthly' || ch.schedule = 'yearly') AND ch.perform = 1 AND ch.recurring_day <> '' "
		. ' ch.schedule_nextsend < ' . time(); // this conditional to check time to send scheduled reports
		return $wpdb->get_results( $sql );
	}

	public function get_scheduled_to_continue() {
		global $wpdb;
		$sql = 'SELECT ch.* FROM ' . $this->table_name( 'maintenance_schedule' ) . ' ch WHERE '
				// . " (ch.schedule = 'weekly' || ch.schedule = 'monthly' || ch.schedule = 'yearly') AND ch.perform = 1 AND ch.recurring_day <> '' AND "
		. ' ch.completed < ch.schedule_lastsend '; // do not send if completed > schedule_lastsend
		// echo $sql;
		return $wpdb->get_results( $sql );
	}

	public function update_schedule_with_values( $id, $values ) {

		if ( ! is_array( $values ) ) {
			return false;
		}

		global $wpdb;
		return $wpdb->update( $this->table_name( 'maintenance_schedule' ), $values, array( 'id' => $id ) );
	}


	public function update_schedule_start( $id ) {
		global $wpdb;
		return $wpdb->update(
			$this->table_name( 'maintenance_schedule' ),
			array(
				'schedule_lastsend' => time(),
				'completed_sites'   => json_encode( array() ),
			),
			array( 'id' => $id )
		);
		return false;
	}

	public function update_schedule_completed( $id ) {
		global $wpdb;
		return $wpdb->update( $this->table_name( 'maintenance_schedule' ), array( 'completed' => time() ), array( 'id' => $id ) );
	}

	public function update_last_manually_run_time( $id ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		if ( empty( $id ) ) {
			return false;
		}

		return $wpdb->update( $this->table_name( 'maintenance_schedule' ), array( 'last_run_manually' => time() ), array( 'id' => $id ) );
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
		if ( empty( $result ) ) {
			return 0;
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



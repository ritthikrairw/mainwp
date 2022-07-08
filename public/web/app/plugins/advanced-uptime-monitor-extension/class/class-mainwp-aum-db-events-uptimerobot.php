<?php
/**
 * MainWP Uptime Robot Events Database Controller
 *
 * This file handles all interactions with the DB.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

/**
 * Class MainWP_AUM_DB
 */
class MainWP_AUM_DB_Events_UptimeRobot extends MainWP_AUM_DB_Base {

	// phpcs:disable WordPress.DB.RestrictedFunctions, WordPress.DB.PreparedSQL.NotPrepared, Generic.Metrics.CyclomaticComplexity -- This is the only way to achieve desired results, pull request solutions appreciated.

	/**
	 * Private static variable to hold the single instance of the class.
	 *
	 * @static
	 *
	 * @var mixed Default null
	 */
	private static $instance = null;

	/**
	 * Create public static instance.
	 *
	 * @static
	 *
	 * @return MainWP_AUM_DB
	 */
	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Method: get_last_events()
	 *
	 * Gets last events.
	 *
	 * @param int    $url_id   URL ID.
	 * @param string $lasttime Last time..
	 *
	 * @return bool True on success, false on failure.
	 */
	public function get_last_events( $url_id, $lasttime = false ) {
		$where = '';
		if ( $lasttime ) {
			$date  = gmdate( 'Y-m-d H:i:s', $lasttime );
			$where = ' AND event_datetime_gmt <= STR_TO_DATE(' . $this->wpdb->prepare( '%s', $date ) . ",'%Y-%m-%d %H:%i:%s') ";
		}
		return $this->wpdb->get_row( 'SELECT * FROM ' . $this->table_name( 'stats_uptimerobot' ) . ' WHERE url_id = ' . $url_id . ' ' . $where . ' ORDER BY event_datetime_gmt DESC LIMIT 1' );
	}

	/**
	 * Method: get_events()
	 *
	 * Gets events.
	 *
	 * @param string $where    Query string part.
	 * @param string $limit    Limit.
	 * @param string $order_by Order by string.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function get_events( $where, $limit = false, $order_by = '', $ids = array() ) {

		if ( ! empty( $where ) ) {
			$where = ' AND ' . $where;
		}

		$and_ids = '';
		if ( ! empty( $ids ) ) {
			$and_ids = ' AND url_id IN (' . implode( ',', $ids ) . ') ';
		}

		if ( ! empty( $limit ) ) {
			$limit = ' LIMIT 0, ' . intval( $limit );
		} else {
			$limit = '';
		}

		if ( empty( $order_by ) ) {
			$order_by = ' event_datetime_gmt ASC ';
		}

		$sql = 'SELECT * FROM ' . $this->table_name( 'stats_uptimerobot' ) . ' WHERE 1 ' . $where . $and_ids . ' ORDER BY  ' . $order_by . $limit;

		return $this->wpdb->get_results( $sql );
	}

	/**
	 * Method: delete_events()
	 *
	 * Deletes events.
	 *
	 * @param int $id Event ID.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete_events( $id ) {
		if ( empty( $id ) ) {
			return false;
		}
		$nr = $this->wpdb->query( $this->wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'stats_uptimerobot' ) . ' WHERE url_id=%d', $id ) );
		return $nr;
	}

	/**
	 * Method: insert_event()
	 *
	 * Inserts event.
	 *
	 * @param array $data Data to insert.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function insert_event( $data ) {
		if ( ! is_array( $data ) ) {
			return false;
		}
		$this->wpdb->insert( $this->table_name( 'stats_uptimerobot' ), $data );
		return $this->wpdb->insert_id;
	}

	/**
	 * Method: update_event()
	 *
	 * Updates event.
	 *
	 * @param int   $event_id Event ID.
	 * @param array $fields   Fields containig values to update.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function update_event( $event_id, $fields ) {
		if ( count( $fields ) > 0 ) {
			return $this->wpdb->update( $this->table_name( 'stats_uptimerobot' ), $fields, array( 'event_id' => $event_id ) );
		}
		return false;
	}

	/**
	 * Method: get_cond_events()
	 *
	 * Gets cond events.
	 *
	 * @param  array $options Options.
	 * @return string SQL Query.
	 */
	public function get_cond_events( $options = array() ) {

		$conds      = isset( $options['conds'] ) ? $options['conds'] : array();
		$per_page   = isset( $options['per_page'] ) ? $options['per_page'] : false;
		$page       = isset( $options['page'] ) ? $options['page'] : false;
		$order_by   = isset( $options['order_by'] ) ? $options['order_by'] : ' event_datetime_gmt DESC ';
		$count_only = isset( $options['count_only'] ) ? $options['count_only'] : false;

		$where = '';
		if ( ! empty( $conds ) ) {
			$where = ' AND ' . $this->get_where_sql( $conds );
		}

		if ( ! empty( $order_by ) ) {
			$order_by = $this->escape( $order_by );
		}

		$limit = '';
		if ( ! empty( $per_page ) ) {
			$offset = ( $page - 1 ) * $per_page;
			if ( 0 > $offset ) {
				$offset = 0;
			}
			$limit = ' LIMIT ' . $this->escape( $offset ) . ', ' . $this->escape( $per_page );
		}

		if ( $count_only ) {
			$sql = 'SELECT count(event_id) FROM ' . $this->table_name( 'stats_uptimerobot' ) . ' WHERE 1 ' . $where . ' ORDER BY ' . $order_by . $limit;
			return $this->wpdb->get_var( $sql );
		} else {
			$sql = 'SELECT * FROM ' . $this->table_name( 'stats_uptimerobot' ) . ' WHERE 1 ' . $where . ' ORDER BY ' . $order_by . $limit;
			return $this->wpdb->get_results( $sql );
		}
	}

}

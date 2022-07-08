<?php
/**
 * MainWP Betteruptime Events Database Controller
 *
 * This file handles all interactions with the DB.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

/**
 * Class MainWP_AUM_DB_Events_BetterUptime
 */
class MainWP_AUM_DB_Events_BetterUptime extends MainWP_AUM_DB_Base {

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
	 * @return MainWP_AUM_DB_Events_BetterUptime
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
	public function get_last_events( $url_id = false, $lasttime = false ) {
		$where = '';

		if ( ! empty( $lasttime ) ) {
			$where .= $this->wpdb->prepare( ' AND started_at > %d ', $lasttime );
		}

		if ( ! empty( $url_id ) ) {
			$where .= $this->wpdb->prepare( ' AND url_id = %d ', $url_id );
		}

		return $this->wpdb->get_row( 'SELECT * FROM ' . $this->table_name( 'stats_betteruptime' ) . ' WHERE 1 ' . $where . ' ORDER BY started_at DESC LIMIT 1' );
	}

	/**
	 * Method: get_event()
	 *
	 * Gets event.
	 *
	 * @param int $url_id   site id.
	 * @param int $incident_id   incident id.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function get_site_event( $url_id, $incident_id ) {

		$where_sql = $this->wpdb->prepare( ' AND bt.url_id = %d AND bt.incident_id = %d  ', $url_id, $incident_id );

		$sql = 'SELECT * FROM ' . $this->table_name( 'stats_betteruptime' ) . ' bt '
		. ' WHERE 1 ' . $where_sql
		. ' LIMIT 1';

		return $this->wpdb->get_row( $sql );
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
	public function get_events( $where, $limit = false, $order_by = '' ) {

		if ( ! empty( $where ) ) {
			$where = ' AND ' . $where;
		}

		if ( ! empty( $limit ) ) {
			$limit = ' LIMIT 0, ' . intval( $limit );
		} else {
			$limit = '';
		}

		if ( empty( $order_by ) ) {
			$order_by = ' started_at DESC ';
		}

		$sql = 'SELECT * FROM ' . $this->table_name( 'stats_betteruptime' ) . ' WHERE 1 ' . $where . ' ORDER BY  ' . $order_by . $limit;

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
		$nr = $this->wpdb->query( $this->wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'stats_betteruptime' ) . ' WHERE url_id=%d', $id ) );
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

		if ( ! is_array( $data ) || ! isset( $data['url_id'] ) || ! isset( $data['incident_id'] ) ) {
			return false;
		}

		$current = false;

		if ( isset( $data['incident_id'] ) ) {
			$current = $this->get_site_event( $data['url_id'], $data['incident_id'] );
		}

		if ( $current ) {
			$this->wpdb->update( $this->table_name( 'stats_betteruptime' ), $data, array( 'event_id' => $current->event_id ) );
			return $current->event_id;
		} else {
			$this->wpdb->insert( $this->table_name( 'stats_betteruptime' ), $data );
			return $this->wpdb->insert_id;
		}
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
			return $this->wpdb->update( $this->table_name( 'stats_betteruptime' ), $fields, array( 'event_id' => $event_id ) );
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
		$order_by   = isset( $options['order_by'] ) ? $options['order_by'] : ' started_at DESC ';
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
			$sql = 'SELECT count(event_id) FROM ' . $this->table_name( 'stats_betteruptime' ) . ' WHERE 1 ' . $where . ' ORDER BY ' . $order_by . $limit;
			return $this->wpdb->get_var( $sql );
		} else {
			$sql = 'SELECT * FROM ' . $this->table_name( 'stats_betteruptime' ) . ' WHERE 1 ' . $where . ' ORDER BY ' . $order_by . $limit;
			return $this->wpdb->get_results( $sql );
		}
	}

}

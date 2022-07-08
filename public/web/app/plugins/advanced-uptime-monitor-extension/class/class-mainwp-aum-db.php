<?php
/**
 * MainWP Database Controller
 *
 * This file handles all interactions with the DB.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

/**
 * Class MainWP_AUM_DB
 *
 * Handles all interactions with the DB.
 */
class MainWP_AUM_DB extends MainWP_AUM_DB_Base {

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

		self::$instance->test_connection();

		return self::$instance;
	}

	/**
	 * Gets wp_options database table view.
	 *
	 * @param array $fields Extra option fields.
	 * @param bool  $default Whether or not to get default option fields.
	 *
	 * @return array wp_options view.
	 */
	public function get_option_view( $options = array(), $service = 'uptimerobot' ) {

		$fields    = isset( $options['fields'] ) ? $options['fields'] : false;
		$default   = isset( $options['default'] ) ? $options['default'] : false;
		$with_prop = isset( $options['with_properties'] ) ? $options['with_properties'] : false;

		$view = '(SELECT inturls.url_id,';

		if ( empty( $fields ) || $default ) {
			$view .= ' (SELECT monitor_interval.option_value FROM ' . $this->table_name( 'url_options' ) . ' monitor_interval WHERE  monitor_interval.url_id = inturls.url_id AND monitor_interval.option_name = "monitor_interval" LIMIT 1) AS monitor_interval,
			        (SELECT monitor_type.option_value FROM ' . $this->table_name( 'url_options' ) . ' monitor_type WHERE  monitor_type.url_id = inturls.url_id AND monitor_type.option_name = "monitor_type" LIMIT 1) AS monitor_type,
					(SELECT http_username.option_value FROM ' . $this->table_name( 'url_options' ) . ' http_username WHERE  http_username.url_id = inturls.url_id AND http_username.option_name = "http_username" LIMIT 1) AS http_username,
					(SELECT http_password.option_value FROM ' . $this->table_name( 'url_options' ) . ' http_password WHERE  http_password.url_id = inturls.url_id AND http_password.option_name = "http_password" LIMIT 1) AS http_password,';

			if ( 'site24x7' == $service ) {
				$view .= '(SELECT last_polled_datetime_gmt.option_value FROM ' . $this->table_name( 'url_options' ) . ' last_polled_datetime_gmt WHERE  last_polled_datetime_gmt.url_id = inturls.url_id AND last_polled_datetime_gmt.option_name = "last_polled_datetime_gmt" LIMIT 1) AS last_polled_datetime_gmt,';
				$view .= '(SELECT response_time.option_value FROM ' . $this->table_name( 'url_options' ) . ' response_time WHERE  response_time.url_id = inturls.url_id AND response_time.option_name = "response_time" LIMIT 1) AS response_time,';
			}

			if ( 'uptimerobot' == $service || 'site24x7' == $service || 'betteruptime' == $service ) {
				$view .= '(SELECT status.option_value FROM ' . $this->table_name( 'url_options' ) . ' status WHERE  status.url_id = inturls.url_id AND status.option_name = "status" LIMIT 1) AS status,';
			}

			if ( 'nodeping' == $service || 'site24x7' == $service ) {
				$view .= '(SELECT state.option_value FROM ' . $this->table_name( 'url_options' ) . ' state WHERE  state.url_id = inturls.url_id AND state.option_name = "state" LIMIT 1) AS state,';
			}

			if ( 'nodeping' == $service ) {
				$view .= '(SELECT enable.option_value FROM ' . $this->table_name( 'url_options' ) . ' enable WHERE  enable.url_id = inturls.url_id AND enable.option_name = "enable" LIMIT 1) AS enable,';
			}
		}

		if ( $with_prop ) {
			if ( 'uptimerobot' == $service ) {
				$view .= '(SELECT monitor_subtype.option_value FROM ' . $this->table_name( 'url_options' ) . ' monitor_subtype WHERE  monitor_subtype.url_id = inturls.url_id AND monitor_subtype.option_name = "monitor_subtype" LIMIT 1) AS monitor_subtype,
				(SELECT keyword_type.option_value FROM ' . $this->table_name( 'url_options' ) . ' keyword_type WHERE  keyword_type.url_id = inturls.url_id AND keyword_type.option_name = "keyword_type" LIMIT 1) AS keyword_type,
				(SELECT keyword_value.option_value FROM ' . $this->table_name( 'url_options' ) . ' keyword_value WHERE  keyword_value.url_id = inturls.url_id AND keyword_value.option_name = "keyword_value" LIMIT 1) AS keyword_value,
				(SELECT alltimeuptimeratio.option_value FROM ' . $this->table_name( 'url_options' ) . ' alltimeuptimeratio WHERE  alltimeuptimeratio.url_id = inturls.url_id AND alltimeuptimeratio.option_name = "alltimeuptimeratio" LIMIT 1) AS alltimeuptimeratio,';
			} elseif ( 'site24x7' == $service ) {
				$view .= '(SELECT monitor_timeout.option_value FROM ' . $this->table_name( 'url_options' ) . ' monitor_timeout WHERE  monitor_timeout.url_id = inturls.url_id AND monitor_timeout.option_name = "timeout" LIMIT 1) AS timeout,
				(SELECT location_profile_id.option_value FROM ' . $this->table_name( 'url_options' ) . ' location_profile_id WHERE  location_profile_id.url_id = inturls.url_id AND location_profile_id.option_name = "location_profile_id" LIMIT 1) AS location_profile_id,
				(SELECT notification_profile_id.option_value FROM ' . $this->table_name( 'url_options' ) . ' notification_profile_id WHERE  notification_profile_id.url_id = inturls.url_id AND notification_profile_id.option_name = "notification_profile_id" LIMIT 1) AS notification_profile_id,
				(SELECT threshold_profile_id.option_value FROM ' . $this->table_name( 'url_options' ) . ' threshold_profile_id WHERE  threshold_profile_id.url_id = inturls.url_id AND threshold_profile_id.option_name = "threshold_profile_id" LIMIT 1) AS threshold_profile_id,
				(SELECT user_group_ids.option_value FROM ' . $this->table_name( 'url_options' ) . ' user_group_ids WHERE  user_group_ids.url_id = inturls.url_id AND user_group_ids.option_name = "user_group_ids" LIMIT 1) AS user_group_ids,
				(SELECT reports_today.option_value FROM ' . $this->table_name( 'url_options' ) . ' reports_today WHERE  reports_today.url_id = inturls.url_id AND reports_today.option_name = "reports_today" LIMIT 1) AS reports_today,
				(SELECT monitor_group_ids.option_value FROM ' . $this->table_name( 'url_options' ) . ' monitor_group_ids WHERE  monitor_group_ids.url_id = inturls.url_id AND monitor_group_ids.option_name = "monitor_group_ids" LIMIT 1) AS monitor_group_ids,
				(SELECT dependent_monitors.option_value FROM ' . $this->table_name( 'url_options' ) . ' dependent_monitors WHERE  dependent_monitors.url_id = inturls.url_id AND dependent_monitors.option_name = "dependent_monitors" LIMIT 1) AS dependent_monitors,
				(SELECT http_method.option_value FROM ' . $this->table_name( 'url_options' ) . ' http_method WHERE  http_method.url_id = inturls.url_id AND http_method.option_name = "http_method" LIMIT 1) AS http_method,';
			}
			// elseif ( 'nodeping' == $service ) {
			// going to remove those options query
			// $view .= '(SELECT notify_contacts.option_value FROM ' . $this->table_name( 'url_options' ) . ' notify_contacts WHERE  notify_contacts.url_id = inturls.url_id AND notify_contacts.option_name = "notify_contacts" LIMIT 1) AS notify_contacts,
			// (SELECT description.option_value FROM ' . $this->table_name( 'url_options' ) . ' description WHERE  description.url_id = inturls.url_id AND description.option_name = "description" LIMIT 1) AS description,
			// (SELECT dependency.option_value FROM ' . $this->table_name( 'url_options' ) . ' dependency WHERE  dependency.url_id = inturls.url_id AND dependency.option_name = "dependency" LIMIT 1) AS dependency,
			// (SELECT region.option_value FROM ' . $this->table_name( 'url_options' ) . ' region WHERE  region.url_id = inturls.url_id AND region.option_name = "region" LIMIT 1) AS region,
			// (SELECT location.option_value FROM ' . $this->table_name( 'url_options' ) . ' location WHERE  location.url_id = inturls.url_id AND location.option_name = "location" LIMIT 1) AS location,
			// (SELECT sensitivity.option_value FROM ' . $this->table_name( 'url_options' ) . ' sensitivity WHERE  sensitivity.url_id = inturls.url_id AND sensitivity.option_name = "sensitivity" LIMIT 1) AS sensitivity,';
			// }
			// elseif ( 'betteruptime' == $service ) {
			// $view .= '(SELECT request_timeout.option_value FROM ' . $this->table_name( 'url_options' ) . ' request_timeout WHERE  request_timeout.url_id = inturls.url_id AND request_timeout.option_name = "request_timeout" LIMIT 1) AS request_timeout,
			// (SELECT check_frequency.option_value FROM ' . $this->table_name( 'url_options' ) . ' check_frequency WHERE  check_frequency.url_id = inturls.url_id AND check_frequency.option_name = "check_frequency" LIMIT 1) AS check_frequency,
			// (SELECT required_keyword.option_value FROM ' . $this->table_name( 'url_options' ) . ' required_keyword WHERE  required_keyword.url_id = inturls.url_id AND required_keyword.option_name = "required_keyword" LIMIT 1) AS required_keyword,
			// (SELECT port.option_value FROM ' . $this->table_name( 'url_options' ) . ' port WHERE  port.url_id = inturls.url_id AND port.option_name = "port" LIMIT 1) AS port,
			// (SELECT recovery_period.option_value FROM ' . $this->table_name( 'url_options' ) . ' recovery_period WHERE  recovery_period.url_id = inturls.url_id AND recovery_period.option_name = "recovery_period" LIMIT 1) AS recovery_period,
			// (SELECT confirmation_period.option_value FROM ' . $this->table_name( 'url_options' ) . ' confirmation_period WHERE  confirmation_period.url_id = inturls.url_id AND confirmation_period.option_name = "confirmation_period" LIMIT 1) AS confirmation_period,
			// (SELECT team_wait.option_value FROM ' . $this->table_name( 'url_options' ) . ' team_wait WHERE  team_wait.url_id = inturls.url_id AND team_wait.option_name = "team_wait" LIMIT 1) AS team_wait,
			// (SELECT call_mo.option_value FROM ' . $this->table_name( 'url_options' ) . ' call_mo WHERE  call_mo.url_id = inturls.url_id AND call_mo.option_name = "call" LIMIT 1) AS call_mo,
			// (SELECT sms.option_value FROM ' . $this->table_name( 'url_options' ) . ' sms WHERE  sms.url_id = inturls.url_id AND sms.option_name = "sms" LIMIT 1) AS sms,
			// (SELECT email.option_value FROM ' . $this->table_name( 'url_options' ) . ' email WHERE  email.url_id = inturls.url_id AND email.option_name = "email" LIMIT 1) AS email,
			// (SELECT push.option_value FROM ' . $this->table_name( 'url_options' ) . ' push WHERE  push.url_id = inturls.url_id AND push.option_name = "push" LIMIT 1) AS push,
			// (SELECT maintenance_from.option_value FROM ' . $this->table_name( 'url_options' ) . ' maintenance_from WHERE  maintenance_from.url_id = inturls.url_id AND maintenance_from.option_name = "maintenance_from" LIMIT 1) AS maintenance_from,
			// (SELECT maintenance_to.option_value FROM ' . $this->table_name( 'url_options' ) . ' maintenance_to WHERE  maintenance_to.url_id = inturls.url_id AND maintenance_to.option_name = "maintenance_to" LIMIT 1) AS maintenance_to,
			// (SELECT domain_expiration.option_value FROM ' . $this->table_name( 'url_options' ) . ' domain_expiration WHERE  domain_expiration.url_id = inturls.url_id AND domain_expiration.option_name = "domain_expiration" LIMIT 1) AS domain_expiration,
			// (SELECT verify_ssl.option_value FROM ' . $this->table_name( 'url_options' ) . ' verify_ssl WHERE  verify_ssl.url_id = inturls.url_id AND verify_ssl.option_name = "verify_ssl" LIMIT 1) AS verify_ssl,
			// (SELECT ssl_expiration.option_value FROM ' . $this->table_name( 'url_options' ) . ' ssl_expiration WHERE  ssl_expiration.url_id = inturls.url_id AND ssl_expiration.option_name = "ssl_expiration" LIMIT 1) AS ssl_expiration,
			// (SELECT request_body.option_value FROM ' . $this->table_name( 'url_options' ) . ' request_body WHERE  request_body.url_id = inturls.url_id AND request_body.option_name = "request_body" LIMIT 1) AS request_body,
			// (SELECT request_headers.option_value FROM ' . $this->table_name( 'url_options' ) . ' request_headers WHERE  request_headers.url_id = inturls.url_id AND request_headers.option_name = "request_headers" LIMIT 1) AS request_headers,
			// (SELECT follow_redirects.option_value FROM ' . $this->table_name( 'url_options' ) . ' follow_redirects WHERE  follow_redirects.url_id = inturls.url_id AND follow_redirects.option_name = "follow_redirects" LIMIT 1) AS follow_redirects,
			// (SELECT last_checked_at.option_value FROM ' . $this->table_name( 'url_options' ) . ' last_checked_at WHERE  last_checked_at.url_id = inturls.url_id AND last_checked_at.option_name = "last_checked_at" LIMIT 1) AS last_checked_at,
			// (SELECT http_method.option_value FROM ' . $this->table_name( 'url_options' ) . ' http_method WHERE  http_method.url_id = inturls.url_id AND http_method.option_name = "http_method" LIMIT 1) AS http_method,';
			// }
		}

		// new options field data, to fix timeout saving issue.
		if ( 'nodeping' == $service || 'betteruptime' == $service ) {
			$view .= '(SELECT field_options_data.option_value FROM ' . $this->table_name( 'url_options' ) . ' field_options_data WHERE  field_options_data.url_id = inturls.url_id AND field_options_data.option_name = "field_options_data" LIMIT 1) AS field_options_data,';
		}

		if ( is_array( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( empty( $field ) ) {
					continue;
				}
				$view .= '(SELECT ' . $this->escape( $field ) . '.option_value FROM ' . $this->table_name( 'url_options' ) . ' ' . $this->escape( $field ) . ' WHERE  ' . $this->escape( $field ) . '.url_id = inturls.url_id AND ' . $this->escape( $field ) . '.option_name = "' . $this->escape( $field ) . '" LIMIT 1) AS ' . $this->escape( $field ) . ',';
			}
		}

		$view = rtrim( $view, ',' );

		$view .= ' FROM ' . $this->table_name( 'monitor_urls' ) . ' inturls)';

		return $view;
	}

	/**
	 * Method get_monitor_urls().
	 *
	 * Gets monitor URLs
	 *
	 * @return bool True on succes, false on failure.
	 */
	public function get_monitor_urls( $service = 'uptimerobot', $options = array() ) {

		if ( ! in_array( $service, array( 'uptimerobot', 'betteruptime', 'site24x7', 'nodeping' ) ) ) {
			return array();
		}

		$conds          = isset( $options['conds'] ) ? $options['conds'] : array();
		$per_page       = isset( $options['per_page'] ) ? $options['per_page'] : false;
		$page           = isset( $options['page'] ) ? $options['page'] : false;
		$order_by       = isset( $options['order_by'] ) ? $options['order_by'] : 'url_name ASC';
		$count_only     = isset( $options['count_only'] ) ? $options['count_only'] : false;
		$fields         = isset( $options['fields'] ) ? $options['fields'] : false;
		$with_prop      = isset( $options['with_properties'] ) ? $options['with_properties'] : false;
		$monitor_ids_in = isset( $options['monitor_ids_in'] ) ? $options['monitor_ids_in'] : false;
		$default        = isset( $options['default'] ) && $options['default'] ? true : false;

		$filter_searching = isset( $options['searching'] ) ? $options['searching'] : '';

		if ( ! empty( $monitor_ids_in ) ) {
			if ( is_array( $monitor_ids_in ) ) {
				$url_imonitor_ids_inds_in = implode( ',', $monitor_ids_in );
			}
			$monitor_ids_in = ' AND (' . $monitor_ids_in . ') ';
		} else {
			$monitor_ids_in = '';
		}

		$where = '';
		if ( ! empty( $conds ) ) {
			$where = ' AND ' . $this->get_where_sql( $conds );
		}

		$limit = '';
		if ( ! empty( $per_page ) ) {
			$offset = ( $page - 1 ) * $per_page;
			if ( 0 > $offset ) {
				$offset = 0;
			}
			$limit = ' LIMIT ' . $this->escape( $offset ) . ', ' . $this->escape( $per_page );
		}

		if ( ! empty( $order_by ) ) {
			$order_by = $this->escape( $order_by );
		}

		$where       .= ' AND mo.service = "' . $this->escape( $service ) . '" ';
		$where_option = '';
		if ( 'betteruptime' == $service && ! empty( $filter_searching ) ) {
			$escape_searching = $this->escape( $filter_searching );
			$where_option    .= ' AND ( urls_optionview.status LIKE "%' . $escape_searching . '%"  OR mo.url_name LIKE "%' . $escape_searching . '%" OR mo.url_address LIKE "%' . $escape_searching . '%"  ) ';
		}

		$opts = array(
			'fields'          => $fields,
			'default'         => $default,
			'with_properties' => $with_prop,
		);

		$data = array();
		if ( $count_only ) {
			$sql  = 'SELECT count(mo.url_id) FROM ' . $this->table_name( 'monitor_urls' ) . ' mo
			LEFT JOIN ' . $this->get_option_view( $opts, $service ) . ' urls_optionview ON mo.url_id = urls_optionview.url_id ' . $where_option . '
			WHERE 1 ' . $where . $monitor_ids_in . ' ORDER BY ' . $order_by . $limit;
			$data = $this->wpdb->get_var( $sql ); // count only.
		} else {
			$sql  = 'SELECT mo.*, urls_optionview.* FROM ' . $this->table_name( 'monitor_urls' ) . ' mo 
			LEFT JOIN ' . $this->get_option_view( $opts, $service ) . ' urls_optionview ON mo.url_id = urls_optionview.url_id  ' . $where_option . '
			WHERE 1 ' . $where . $monitor_ids_in . ' ORDER BY ' . $order_by . $limit;
			$data = $this->wpdb->get_results( $sql );
			MainWP_AUM_Main_Controller::instance()->map_options_monitor_fields( $service, $data );
		}
		return $data;
	}

	/**
	 * Method get_monitor_by().
	 *
	 * Gets monitor by
	 *
	 * @return bool True on succes, false on failure.
	 */
	public function get_monitor_by( $by = 'url_id', $value = '', $service = 'uptimerobot', $obj = OBJECT, $with_options = true ) {

		if ( 'url_id' == $by ) {
			$where_sql = $this->wpdb->prepare( ' AND mo.url_id = %d ', $value );
		} elseif ( 'url_address' == $by && ! empty( $value ) && ! empty( $service ) ) {
			$url_address = str_replace( array( 'https://www.', 'http://www.', 'https://', 'http://', 'www.', '/' ), array( '', '', '', '', '', '' ), $value );
			$where_sql   = $this->wpdb->prepare( "  AND replace(replace(replace(replace(replace(replace(mo.url_address, 'https://www.',''), 'http://www.',''), 'https://', ''), 'http://', ''), 'www.', ''), '/', '' ) = %s AND mo.service = %s ", $url_address, $service );
		} elseif ( 'url_address_like' == $by && ! empty( $value ) && ! empty( $service ) ) {
			// if get by url_address are not found then may try to get by url_address_like.
			$url_address = str_replace( array( 'https://www.', 'http://www.', 'https://', 'http://', 'www.', '/' ), array( '', '', '', '', '', '' ), $value );
			$where_sql   = "  AND replace(replace(replace(replace(replace(replace(mo.url_address, 'https://www.',''), 'http://www.',''), 'https://', ''), 'http://', ''), 'www.', ''), '/', '' ) LIKE  '%" . $url_address . "%' ";
			$where_sql  .= $this->wpdb->prepare( ' AND mo.service = %s ', $service );
		} elseif ( 'monitor_id' == $by && ! empty( $service ) ) {
			$where_sql = $this->wpdb->prepare( '  AND mo.monitor_id = %d AND mo.service = %s ', $value, $service );
		}

		if ( empty( $where_sql ) ) {
			return false;
		}

		$sql = $this->get_sql_monitor_url_by( $where_sql, $service, $with_options );

		if ( ! empty( $sql ) ) {
			$data      = $this->wpdb->get_row( $sql, $obj );
			$temp_data = array( $data );
			MainWP_AUM_Main_Controller::instance()->map_options_monitor_fields( $service, $temp_data );
			$data = current( $temp_data );
			return $data;
		}

		return false;
	}

	/**
	 * Method get_sql_monitor_url_by().
	 *
	 * Get monitor by ID.
	 *
	 * @return string SQL query string.
	 */
	public function get_sql_monitor_url_by( $where_sql, $service, $with_options = true ) {

		$options = array(
			'fields'          => false,
			'default'         => false,
			'with_properties' => true,
		);

		$sql = 'SELECT * FROM ' . $this->table_name( 'monitor_urls' ) . ' mo ';
		if ( $with_options ) {
			$sql .= ' JOIN ' . $this->get_option_view( $options, $service ) . ' urls_optionview ON mo.url_id = urls_optionview.url_id ';
		}
		$sql .= ' WHERE 1 ' . $where_sql
		. ' LIMIT 1';
		return $sql;
	}

	/**
	 * Method insert_monitor_url().
	 *
	 * Inserts monitor URL.
	 *
	 * @return bool True on succes, false on failure.
	 */
	public function insert_monitor_url( $data ) {
		return $this->update_monitor_url( $data, false );
	}

	/**
	 * Method update_monitor_url().
	 *
	 * Updates Monitor URL options.
	 *
	 * @param int $id URL ID.
	 *
	 * @return bool True on succes, false on failure.
	 */
	public function update_monitor_url( $data, $id ) {

		if ( ! is_array( $data ) ) {
			return false;
		}

		if ( $id ) {
			$value = $this->wpdb->get_var( $this->wpdb->prepare( 'SELECT url_id FROM ' . $this->table_name( 'monitor_urls' ) . ' WHERE url_id = %d ', $id ) );
			if ( null !== $value ) {
				$this->wpdb->update( $this->table_name( 'monitor_urls' ), $data, array( 'url_id' => $id ) );
				return $id;
			}
		} else {
			$this->wpdb->insert( $this->table_name( 'monitor_urls' ), $data );
			return $this->wpdb->insert_id;
		}
		return false;
	}

	/**
	 * Method save_monitor_url_field_options_data().
	 *
	 * Updates Monitor URL options.
	 *
	 * @param int $url_id URL ID.
	 *
	 * @return bool True on succes, false on failure.
	 */
	public function save_monitor_url_field_options_data( $url_id, $values ) {
		$current     = $this->get_monitor_url_option( $url_id, 'field_options_data' );
		$fields_data = array();
		if ( $current ) {
			$current = json_decode( $current, true );
			if ( ! is_array( $current ) ) {
				$current = array();
			}
			foreach ( $values as $idx => $val ) {
				if ( null === $val ) {
					continue;
				}
				$current[ $idx ] = $val;
			}
			$fields_data = $current;
		} else {
			foreach ( $values as $idx => $val ) {
				if ( null === $val ) {
					continue;
				}
				$fields_data[ $idx ] = $val;
			}
		}
		$this->save_monitor_url_option_field( $url_id, 'field_options_data', wp_json_encode( $fields_data ) );
	}

	/**
	 * Method save_monitor_url_options().
	 *
	 * Updates Monitor URL options.
	 *
	 * @param int $url_id URL ID.
	 *
	 * @return bool True on succes, false on failure.
	 */
	public function save_monitor_url_options( $url_id, $values, $service = '' ) {
		if ( ! is_array( $values ) ) {
			return false;
		}
		if ( 'nodeping' == $service || 'betteruptime' == $service ) {
			$current     = $this->get_monitor_url_option( $url_id, 'field_options_data' );
			$fields_data = array();
			if ( $current ) {
				$current = json_decode( $current, true );
				if ( ! is_array( $current ) ) {
					$current = array();
				}
				foreach ( $values as $idx => $val ) {
					if ( null === $val ) {
						continue;
					}
					$current[ $idx ] = $val;
				}
				$fields_data = $current;
			} else {
				foreach ( $values as $idx => $val ) {
					if ( null === $val ) {
						continue;
					}
					$fields_data[ $idx ] = $val;
				}
			}
			$this->save_monitor_url_option_field( $url_id, 'field_options_data', wp_json_encode( $fields_data ) );
		} else {
			foreach ( $values as $opt => $val ) {
				if ( null === $val ) {
					continue;
				}
				$this->save_monitor_url_option_field( $url_id, $opt, $val );
			}
		}
	}


	/**
	 * Method get_monitor_url_option().
	 *
	 * Get Monitor URL option by option name.
	 *
	 * @param int    $url_id URL ID.
	 * @param string $option_name option name.
	 *
	 * @return mixed Option value.
	 */
	public function get_monitor_url_option( $url_id, $option_name ) {
		$option_value = $this->wpdb->get_var( $this->wpdb->prepare( 'SELECT option_value FROM ' . $this->table_name( 'url_options' ) . ' WHERE url_id = %d AND option_name = %s ', $url_id, $option_name ) );
		return $option_value;
	}

	/**
	 * Method save_monitor_url_option_field().
	 *
	 * Updates Monitor URL options.
	 *
	 * @param int $url_id URL ID.
	 *
	 * @return bool True on succes, false on failure.
	 */
	public function save_monitor_url_option_field( $url_id, $option_name, $option_value ) {
		$insert = false;
		$id     = $this->wpdb->get_var( $this->wpdb->prepare( 'SELECT option_id FROM ' . $this->table_name( 'url_options' ) . ' WHERE url_id = %d AND option_name = %s ', $url_id, $option_name ) );
		if ( null !== $id ) {
			$this->wpdb->update(
				$this->table_name( 'url_options' ),
				array(
					'option_value' => $option_value,
				),
				array(
					'option_id' => $id,
				)
			);
			return $id;
		} else {
			$insert = true;
		}

		if ( $insert ) {
			$data = array(
				'url_id'       => $url_id,
				'option_name'  => $option_name,
				'option_value' => $option_value,
			);
			$this->wpdb->insert( $this->table_name( 'url_options' ), $data );
			return $this->wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Method delete_db_monitor().
	 *
	 * Deletes DB monitors..
	 *
	 * @return bool True on succes, false on failure.
	 */
	public function delete_db_monitor( $by = 'url_id', $value = '', $service = false ) {
		if ( 'url_id' == $by ) {
			$this->delete_url_options( $value );
			return $this->wpdb->query( $this->wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'monitor_urls' ) . ' WHERE url_id=%d', $value ) );
		} elseif ( 'monitor_id' == $by && ! empty( $service ) ) {
			$current = $this->get_monitor_by( 'monitor_id', $value, $service );
			if ( $current ) {
				$this->delete_url_options( $current->url_id );
			}
			return $this->wpdb->query( $this->wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'monitor_urls' ) . ' WHERE monitor_id=%s AND service=%s', $value, $service ) );
		}
		return false;
	}


	/**
	 * Method delete_url_options().
	 *
	 * Deletes URL options.
	 *
	 * @return bool True on succes, false on failure.
	 */
	public function delete_url_options( $url_id ) {
		return $this->wpdb->query( $this->wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'url_options' ) . ' WHERE url_id=%d', $url_id ) );
	}

}

<?php

/**
 * Log tables for Authentication Module
 *
 * @package    iThemes-Security
 * @subpackage Authentication
 * @since      4.0
 */
final class MainWP_ITSEC_Logger_All_Logs extends MainWP_ITSEC_WP_List_Table {

	function __construct() {

		parent::__construct(
			array(
				'singular' => 'itsec_raw_log_item',
				'plural'   => 'itsec_raw_log_items',
				'ajax'     => true
			)
		);

	}

	/**
	 * Define type column
	 *
	 * @param array $item array of row data
	 *
	 * @return string formatted output
	 *
	 **/
	function column_time( $item ) {

		return $item['time'];

	}

	/**
	 * Define function column
	 *
	 * @param array $item array of row data
	 *
	 * @return string formatted output
	 *
	 **/
	function column_function( $item ) {

		return $item['function'];

	}

	/**
	 * Define priority column
	 *
	 * @param array $item array of row data
	 *
	 * @return string formatted output
	 *
	 **/
	function column_priority( $item ) {

		return $item['priority'];

	}

	/**
	 * Define host column
	 *
	 * @param array $item array of row data
	 *
	 * @return string formatted output
	 *
	 **/
	function column_host( $item ) {

		$r = array();
		if ( ! is_array( $item['host'] ) ) {
			$item['host'] = array( $item['host'] );
		}
		foreach ( $item['host'] as $host ) {
			$r[] = '<a href="http://ip-adress.com/ip_tracer/' . filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) . '" target="_blank">' . filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) . '</a>';
		}
		$return = implode( '<br />', $r );

		return $return;

	}

	/**
	 * Define username column
	 *
	 * @param array $item array of row data
	 *
	 * @return string formatted output
	 *
	 **/
	function column_user( $item ) {

		if ( $item['user_id'] != 0 ) {
			return '<a href="/wp-admin/user-edit.php?user_id=' . $item['user_id'] . '" target="_blank">' . $item['user'] . '</a>';
		} else {
			return $item['user'];
		}

	}

	/**
	 * Define url column
	 *
	 * @param array $item array of row data
	 *
	 * @return string formatted output
	 *
	 **/
	function column_url( $item ) {

		return $item['url'];

	}

	/**
	 * Define referrer column
	 *
	 * @param array $item array of row data
	 *
	 * @return string formatted output
	 *
	 **/
	function column_referrer( $item ) {

		return $item['referrer'];

	}

	/**
	 * Define data column
	 *
	 * @param array $item array of row data
	 *
	 * @return string formatted output
	 *
	 **/
	function column_data( $item ) {

		global $mainwp_itsec_logger;

		$raw_data = maybe_unserialize( $item['data'] );

		if ( is_array( $raw_data ) && sizeof( $raw_data ) > 0 ) {

			$data = $mainwp_itsec_logger->print_array( $raw_data, true );

		} elseif ( ! is_array( $raw_data ) ) {

			$data = sanitize_text_field( $raw_data );

		} else {

			$data = '';

		}

		if ( strlen( $data ) > 1 ) {

			$content = '<div class="itsec-all-log-dialog" id="itsec-log-all-row-' . $item['id'] . '" style="display:none;">';
			$content .= $data;
			$content .= '</div>';

			$content .= '<a href="itsec-log-all-row-' . $item['id'] . '" class="dialog">' . __( 'Details', 'l10n-mainwp-ithemes-security-extension' ) . '</a>';

			return $content;

		} else {

			return '';

		}

	}

	/**
	 * Define Columns
	 *
	 * @return array array of column titles
	 */
	public function get_columns() {

		return array(
			'function' => __( 'Function', 'l10n-mainwp-ithemes-security-extension' ),
			'priority' => __( 'Priority', 'l10n-mainwp-ithemes-security-extension' ),
			'time'     => __( 'Time', 'l10n-mainwp-ithemes-security-extension' ),
			'host'     => __( 'Host', 'l10n-mainwp-ithemes-security-extension' ),
			'user'     => __( 'User', 'l10n-mainwp-ithemes-security-extension' ),
			'url'      => __( 'URL', 'l10n-mainwp-ithemes-security-extension' ),
			'referrer' => __( 'Referrer', 'l10n-mainwp-ithemes-security-extension' ),
			'data'     => __( 'Data', 'l10n-mainwp-ithemes-security-extension' ),
		);

	}

	/**
	 * Prepare data for table
	 *
	 * @return void
	 */
	public function prepare_items() {

		global $mainwp_itsec_logger, $wpdb;

		$columns               = $this->get_columns();
		$hidden                = array();
		$this->_column_headers = array( $columns, $hidden, false );
		$per_page              = 20; //20 items per page
		$current_page          = $this->get_pagenum();
		$total_items           = 0; //$wpdb->get_var( "SELECT COUNT(*) FROM `" . $wpdb->base_prefix . "itsec_log`;" );

		$items = $mainwp_itsec_logger->get_events( 'all', array(), $per_page, ( ( $current_page - 1 ) * $per_page ), 'log_date' );

		$table_data = array();

		$count = 0;

//		foreach ( $items as $item ) { //loop through and group 404s
//
//			$table_data[ $count ]['id']       = $count;
//			$table_data[ $count ]['function'] = sanitize_text_field( $item['log_function'] );
//			$table_data[ $count ]['priority'] = sanitize_text_field( $item['log_priority'] );
//			$table_data[ $count ]['time']     = sanitize_text_field( $item['log_date'] );
//			$table_data[ $count ]['host']     = sanitize_text_field( $item['log_host'] );
//			$table_data[ $count ]['user']     = sanitize_text_field( $item['log_username'] );
//			$table_data[ $count ]['user_id']  = sanitize_text_field( $item['log_user'] );
//			$table_data[ $count ]['url']      = sanitize_text_field( $item['log_url'] );
//			$table_data[ $count ]['referrer'] = sanitize_text_field( $item['log_referrer'] );
//			$table_data[ $count ]['data']     = sanitize_text_field( $item['log_data'] );
//
//			$count ++;
//
//		}

		$this->items = $table_data;

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page )
			)
		);

	}

}
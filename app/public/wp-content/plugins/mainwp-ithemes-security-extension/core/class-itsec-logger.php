<?php

/**
 * Handles the writing, maintenance and display of log files
 *
 * @package iThemes-Security
 * @since   4.0
 */
final class MainWP_ITSEC_Logger {

	private
		$version = 1,	
		$log_file,
		$logger_displays,
		$logger_modules,
		$module_path;

	function __construct() {

		global $mainwp_itsec_globals;

		$this->logger_modules  = array(); //array to hold information on modules using this feature
		$this->logger_displays = array(); //array to hold metabox information
		$this->module_path     = MainWP_ITSEC_Lib::get_module_path( __FILE__ );

		add_action( 'plugins_loaded', array( $this, 'register_modules' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) ); //enqueue scripts for admin page
	}

	/**
	 * Add Logger Admin Javascript
	 *
	 * @since 4.3
	 *
	 * @return void
	 */
	public function admin_script() {
		global $mainwp_itsec_globals;
		if ( MainWP_IThemes_Security::is_itheme_page('logs')) {
			wp_enqueue_script( 'mainwp_itsec_logger', plugins_url( 'js/admin-logs.js', __FILE__ ), array( 'jquery' ), $this->version, true );
		}
	}

	/**
	 * Gets events from the logs for a specified module
	 *
	 * @param string $module    module or type of events to fetch
	 * @param array  $params    array of extra query parameters
	 * @param int    $limit     the maximum number of rows to retrieve
	 * @param int    $offset    the offset of the data
	 * @param string $order     order by column
	 * @param bool   $direction false for descending or true for ascending
	 *
	 * @return bool|mixed false on error, null if no events or array of events
	 */
	public function get_events( $module, $params = array(), $limit = null, $offset = null, $order = null, $direction = false ) {
		return array();
	}

	/**
	 * Logs events sent by other modules or systems
	 *
	 * @param string $module   the module requesting the log entry
	 * @param int    $priority the priority of the log entry (1-10)
	 * @param array  $data     extra data to log (non-indexed data would be good here)
	 * @param string $host     the remote host triggering the event
	 * @param string $username the username triggering the event
	 * @param string $user     the user id triggering the event
	 * @param string $url      the url triggering the event
	 * @param string $referrer the referrer to the url (if applicable)
	 *
	 * @return void
	 */
	public function log_event( $module, $priority = 5, $data = array(), $host = '', $username = '', $user = '', $url = '', $referrer = '' ) {

	}

	/**
	 * A better print array function to display array data in the logs
	 *
	 * @since 4.2
	 *
	 * @param array $array_items array to print or return
	 * @param bool  $return      true to return the data false to echo it
	 */
	public function print_array( $array_items, $return = true ) {

		$items = '';

		//make sure we're working with an array
		if ( ! is_array( $array_items ) ) {
			return false;
		}

		if ( sizeof( $array_items ) > 0 ) {

			$items .= '<ul>';

			foreach ( $array_items as $key => $item ) {

				if ( is_array( $item ) ) {

					$items .= '<li>';

					if ( ! is_numeric( $key ) ) {
						$items .= '<h3>' . $key . '</h3>';
					}

					$items .= $this->print_array( $item, true ) . PHP_EOL;

					$items .= '</li>';

				} else {

					if ( strlen( trim( $item ) ) > 0 ) {
						$items .= '<li><h3>' . $key . ' = ' . $item . '</h3></li>' . PHP_EOL;
					}

				}

			}

			$items .= '</ul>';

		}

		return $items;

	}

	/**
	 * Register modules that will use the logger service
	 *
	 * @return void
	 */
	public function register_modules() {
		$this->logger_modules  = apply_filters( 'mainwp_itsec_logger_modules', $this->logger_modules );
		$this->logger_displays = apply_filters( 'mainwp_itsec_logger_displays', $this->logger_displays );
	}


	/**
	 * Sanitizes strings in a given array recursively
	 *
	 * @param  array $array     array to sanitize
	 * @param  bool  $to_string true if output should be string or false for array output
	 *
	 * @return mixed             sanitized array or string
	 */
	private function sanitize_array( $array, $to_string = false ) {

		$sanitized_array = array();
		$string          = '';

		//Loop to sanitize each piece of data
		foreach ( $array as $key => $value ) {

			if ( is_array( $value ) ) {

				if ( $to_string === false ) {
					$sanitized_array[ esc_sql( $key ) ] = $this->sanitize_array( $value );
				} else {
					$string .= esc_sql( $key ) . '=' . $this->sanitize_array( $value, true );
				}

			} else {

				$sanitized_array[ esc_sql( $key ) ] = esc_sql( $value );

				$string .= esc_sql( $key ) . '=' . esc_sql( $value );

			}

		}

		if ( $to_string === false ) {
			return $sanitized_array;
		} else {
			return $string;
		}

	}
}
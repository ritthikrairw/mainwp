<?php
/**
 * MainWP Better Uptime API
 *
 * Handles the Better Uptime API.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

/**
 * Class MainWP_AUM_Api
 *
 * Handles the Better Uptime API.
 */
class MainWP_AUM_BetterUptime_API extends MainWP_AUM_Settings_Base {

	/**
	 * ID of the class extending the settings API. Used in option names.
	 *
	 * @var string
	 */
	public static $setting_id = 'betteruptime';

	private $api_betteruptime_url = 'https://betteruptime.com/api/v2/';

	private $redirect_uri = '';

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
	 * @return MainWP_DB
	 */
	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Method __construct()
	 *
	 * Contructor.
	 *
	 * @param string $api_key API Key.
	 */
	public function __construct( $api_key = null ) {
		$this->redirect_uri = admin_url( 'admin.php?page=Extensions-Advanced-Uptime-Monitor-Extension' );
	}

	/**
	 * Returns the name of the option in the WP DB.
	 *
	 * @return string
	 */
	protected static function get_settings_id() {
		return self::$setting_id;
	}

	/**
	 * Returns the name of the option in the WP DB.
	 *
	 * @return string
	 */
	public function get_redirect_uri() {
		return $this->redirect_uri;
	}

	/**
	 * Method: get_api_token()
	 *
	 * Gets API token.
	 *
	 * @return string API Token.
	 */
	public function get_api_token() {
		return $this->get_option( 'api_token' );
	}

	/**
	 * New monitors of any type can be created using this method
	 *
	 * @param array $params Parameters.
	 * @param int   $updated_id ID.
	 *
	 * $params can have the following keys:
	 * url - optional
	 * monitor_type - optional
	 * check_frequency - optional (optional for port monitoring).
	 * request_timeout - optional
	 * monitor_group_id - optional
	 * http_method - optional
	 * auth_username - optional
	 * auth_password - optional
	 */
	public function api_edit_monitor( $params = array(), $monitor_id = false ) {

		$hearder = array();

		$method  = 'POST';
		$api_act = 'monitors';
		if ( ! empty( $monitor_id ) ) { // to edit/update.
			$method   = 'PATCH';
			$api_act .= '/' . $monitor_id;
		}
		$hearder[] = 'Content-Type: application/json';
		if ( ! empty( $params ) ) {
			$params = wp_json_encode( $params );
		}
		return $this->fetch_remote_url( $api_act, $params, $hearder, $method );
	}

	/**
	 * Deletes monitor.
	 *
	 * @param int $monitor_id Monitor ID.
	 *
	 * @return string URL.
	 */
	public function delete_monitor( $monitor_id ) {
		$hearder = array();

		return $this->fetch_remote_url( 'monitors/' . $monitor_id, array(), $hearder, 'DELETE' );
	}

	/**
	 * Gets all monitor.
	 *
	 * @return string URL.
	 */
	public function get_all_monitors( $offset_page = 0 ) {
		$hearder    = array();
		$get_params = array();
		if ( $offset_page ) {
			$get_params['page'] = $offset_page;
		}
		$return = $this->fetch_remote_url( 'monitors', $get_params, $hearder, 'GET' );
		return $return;
	}

	/**
	 * Method: get_uptime()
	 *
	 * Gets uptime.
	 *
	 * @param string $monitor_id Monitor ID.
	 * @param int    $days Day number.
	 *
	 * @return array Response data.
	 */
	public function get_uptime( $monitor_id, $params ) {

		if ( ! is_array( $params ) || ! isset( $params['start_date'] ) ) {
			return false;
		}

		$hearder    = array();
		$get_params = array(
			'from' => $params['start_date'],
			'to'   => $params['end_date'],
		);
		$return     = $this->fetch_remote_url( 'monitors/' . $monitor_id . '/sla', $get_params, $hearder, 'GET' );
		return $return;
	}

	/**
	 * Gets incidents.
	 *
	 * @param int    $monitor_id Monitor ID.
	 * @param string $date       Date.
	 *
	 * @return string URL.
	 */
	public function get_last_incidents( $monitor_id = false, $date = false ) {

		$hearder = array();

		$params = array();

		if ( ! empty( $date ) ) {
			$date           = gmdate( 'Y-m-d' );
			$params['from'] = $date;
		}

		if ( ! empty( $monitor_id ) ) {
			$params['monitor_id'] = $monitor_id;
		}

		$str_params = MainWP_AUM_Main::get_instance()->build_params_string( $params );

		return $this->fetch_remote_url( 'incidents' . $str_params, array(), $hearder, 'GET' );
	}


	/**
	 * Gets monitor incidents.
	 *
	 * @param int    $monitor_id Monitor ID.
	 * @param string $date       Date.
	 *
	 * @return string URL.
	 */
	public function get_monitor_incidents( $monitor_id, $date = false ) {
		$hearder = array();

		$str_params = '';
		if ( ! empty( $date ) ) {
			$date       = gmdate( 'Y-m-d' );
			$str_params = '?from=' . $date;
		}

		return $this->fetch_remote_url( 'incidents/' . $monitor_id . $str_params, array(), $hearder, 'GET' );
	}


	/**
	 * Returns the authorization request url
	 *
	 * @return string url.
	 */
	public function get_request_url( $api_act, $params = array() ) {

		$url = $this->api_betteruptime_url . $api_act;

		$str_params = MainWP_AUM_Main::get_instance()->build_params_string( $params );

		return $url . $str_params;
	}

	/**
	 * Returns the result of the API calls.
	 *
	 * @param mixed $url required
	 */
	private function fetch_remote_url( $api_act, $post_data = array(), $header = array(), $method = 'POST' ) {

		$url      = $this->api_betteruptime_url . $api_act;
		$header[] = 'Authorization: Bearer ' . $this->get_api_token();

		$str_params = '';
		if ( 'GET' == $method && ! empty( $post_data ) ) {
			$str_params = MainWP_AUM_Main::get_instance()->build_params_string( $post_data );
		}
		$url .= $str_params;

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_ENCODING, '' );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 240 );
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
		if ( ! empty( $header ) ) {
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
		}
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_data );
		$ssl_verifyhost = apply_filters( 'mainwp_aum_verify_certificate', true );
		if ( ! $ssl_verifyhost ) {
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		}
		$data = curl_exec( $ch );
		if ( 'resource' === gettype( $ch ) ) {
			curl_close( $ch );
		}
		return $data;
	}

}

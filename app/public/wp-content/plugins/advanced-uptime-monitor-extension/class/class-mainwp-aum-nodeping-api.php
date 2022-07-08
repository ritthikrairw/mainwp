<?php
/**
 * MainWP AUM NodePing API
 *
 * MainWP AUM NodePing API
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

/**
 * Class MainWP_AUM_NodePing_API
 *
 * MainWP AUM NodePing API
 */
class MainWP_AUM_NodePing_API extends MainWP_AUM_Settings_Base {

	public static $setting_id = 'nodeping';

	private $api_url = 'https://api.nodeping.com/api/1/';

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
	 * @return MainWP_AUM_NodePing_API
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
	 */
	public function __construct() {
	}

	/**
	 * Method: get_settings_id()
	 *
	 * Return the name of the option in the WP DB.
	 *
	 * @return string Setting ID.
	 */
	protected static function get_settings_id() {
		return self::$setting_id;
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
	 * Method: api_edit_monitor()
	 *
	 * Edits monitor via API call.
	 *
	 * @param array $params     Reques parameters.
	 * @param int   $updated_id ID.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function api_edit_monitor( $params = array(), $updated_id = false ) {
		$method = 'POST';
		if ( ! empty( $updated_id ) ) {
			$method       = 'PUT';
			$params['id'] = $updated_id;
		}
		return $this->fetch_remote_url( 'checks', $params, $method );
	}

	/**
	 * Method: enable_disable_monitor()
	 *
	 * Enables/Disables monitor via API call.
	 *
	 * @param string $address Address.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function enable_disable_monitor( $address ) {
		$post_data = array(
			'target' => $address,
		);
		return $this->fetch_remote_url( 'checks', $post_data, 'PUT' );
	}

	/**
	 * Method: delete_monitor()
	 *
	 * Deletes monitor via API call.
	 *
	 * @param int $monitor_id Monitor ID.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete_monitor( $monitor_id ) {
		$post_data = array(
			'id' => $monitor_id,
		);
		return $this->fetch_remote_url( 'checks', $post_data, 'DELETE' );
	}

	/**
	 * Method: get_monitor_results()
	 *
	 * Gets monitor results via API call.
	 *
	 * @param int    $monitor_id Monitor ID.
	 * @param string $start_time Start time.
	 * @param string $end_time   End time.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function get_monitor_results( $monitor_id, $start_time = false, $end_time = false ) {
		$post_data = array(
			'id'    => $monitor_id,
			'clean' => true,
			'limit' => 100,
		);
		if ( ! empty( $start_time ) && ! empty( $end_time ) && $start_time < $end_time ) {
			$post_data['start'] = $start_time;
			$post_data['end']   = $end_time;
		}
		return $this->fetch_remote_url( 'results', $post_data, 'GET' );
	}

	/**
	 * Method: get_contacts()
	 *
	 * Gets contacts via API call.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function get_contacts() {
		return $this->fetch_remote_url( 'contacts', array(), 'GET' );
	}

	/**
	 * Method: get_all_monitors()
	 *
	 * Gets all monitors via API call.
	 *
	 * @param array $monitor_ids Monitor IDs.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function get_all_monitors( $monitor_ids = array() ) {
		$post_data = array(
			'lastresult' => true,
		);
		if ( is_array( $monitor_ids ) && ! empty( $monitor_ids ) ) {
			$monitor_ids     = implode( ',', $monitor_ids );
			$post_data['id'] = $monitor_ids;
		}
		return $this->fetch_remote_url( 'checks', $post_data, 'GET' );
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
	public function get_uptime( $monitor_id, $days ) {
		if ( ! in_array( $days, array( 0, 7, 15, 30, 45, 60 ) ) ) {
			return false;
		}
		$post_data = array(
			'interval' => 'days',
		);
		if ( empty( $days ) ) {
			$date               = date( 'Y-m-d', time() - $days * 60 * 60 * 24 );
			$post_data['start'] = $date;
		}
		return $this->fetch_remote_url( 'results/uptime/' . $monitor_id, $post_data, 'GET' );
	}

	/**
	 * Method: fetch_remote_url()
	 *
	 * Returns the result of the API calls.
	 *
	 * @param string $api_resource API Resource.
	 * @param array  $post_data    Post data.
	 * @param string $method       Method.
	 *
	 * @return array API response.
	 */
	private function fetch_remote_url( $api_resource, $post_data = array(), $method = 'POST' ) {

		$url = $this->api_url . $api_resource;

		if ( is_array( $post_data ) ) {
			$post_data['token'] = $this->get_api_token();
		}

		$header = array( 'Content-Type: application/json' );

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_ENCODING, '' );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 240 );
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, wp_json_encode( $post_data, true ) );

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

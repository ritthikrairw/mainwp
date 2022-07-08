<?php
/**
 * MainWP Site24x7 API
 *
 * Handles the Site24x7 API.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

/**
 * Class MainWP_AUM_Api
 *
 * Handles the Site24x7 API.
 */
class MainWP_AUM_Site24x7_API extends MainWP_AUM_Settings_Base {

	/**
	 * ID of the class extending the settings API. Used in option names.
	 *
	 * @var string
	 */
	public static $setting_id = 'site24x7';

	private $api_endpoints = array(
		'us' => 'https://accounts.zoho.com/',
		'eu' => 'https://accounts.zoho.eu/',
		'cn' => 'https://accounts.zoho.com.cn/',
		'in' => 'https://accounts.zoho.in/',
		'au' => 'https://accounts.zoho.com.au/',
	);

	private $api_admin_url = 'https://accounts.zoho.com/';

	private $api_site247_url = 'https://www.site24x7.com/';

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
		$enp = $this->get_option( 'endpoint' );
		if ( isset( $this->api_endpoints[ $enp ] ) ) {
			$this->api_admin_url = $this->api_endpoints[ $enp ];
		}
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
	 * Returns the authorization request url
	 *
	 * @return string URL.
	 */
	public function get_access_and_refresh_token() {
		$params = array(
			'client_id'     => $this->get_option( 'client_id' ),
			'client_secret' => $this->get_option( 'client_secret' ),
			'redirect_uri'  => $this->redirect_uri,
			'code'          => $this->get_option( 'code' ),
			'grant_type'    => 'authorization_code',
			'api_admin'     => true,
		);
		return $this->fetch_remote_url( 'oauth/v2/token', $params );
	}

	/**
	 * Returns the authorization request url
	 *
	 * @return string URL.
	 */
	public function get_access_token_from_refresh_token( $scope ) {
		$params = array(
			'client_id'     => $this->get_option( 'client_id' ),
			'client_secret' => $this->get_option( 'client_secret' ),
			'redirect_uri'  => $this->redirect_uri,
			'refresh_token' => $this->get_scope_auth_option( 'refresh_token', $scope ),
			'grant_type'    => 'refresh_token',
			'api_admin'     => true,
		);
		return $this->fetch_remote_url( 'oauth/v2/token', $params );
	}


	/**
	 * Returns the authorization request url
	 *
	 * @return string url.
	 */
	public function authorization_request_url( $scope ) {

		$scopes = array(
			'admin'      => 'Site24x7.Admin.All',
			'reports'    => 'Site24x7.Reports.All',
			'operations' => 'Site24x7.Operations.All',
		);

		$params = array(
			'client_id'     => $this->get_option( 'client_id' ),
			'response_type' => 'code',
			'scope'         => isset( $scopes[ $scope ] ) ? $scopes[ $scope ] : '',
			'redirect_uri'  => $this->redirect_uri,
			'access_type'   => 'offline',
			'prompt'        => 'consent',
			'api_admin'     => true,
		);
		return $this->get_request_url( 'oauth/v2/auth', $params );
	}

	/**
	 * Returns the scope auth options.
	 *
	 * @return string Auth option.
	 */
	public function get_scope_auth_option( $option = false, $scope = 'report', $default = false ) {
		$auth_settings = $this->get_option( 'auth_settings', array() );

		if ( ! is_array( $auth_settings ) ) {
			$auth_settings = array();
		}
		if ( empty( $option ) ) {
			return $auth_settings;
		}
		return ( isset( $auth_settings[ $scope ] ) && isset( $auth_settings[ $scope ][ $option ] ) ) ? $auth_settings[ $scope ][ $option ] : $default;
	}

	/**
	 * Returns the authorization request url
	 *
	 * @return string Access token.
	 */
	public function get_access_token( $scope = 'report' ) {
		return $this->get_scope_auth_option( 'access_token', $scope );
	}

	/**
	 * New monitors of any type can be created using this method
	 *
	 * @param array $params Parameters.
	 * @param int   $updated_id ID.
	 *
	 * $params can have the following keys:
	 * display_name - required
	 * website - required
	 * type - required
	 * check_frequency - optional (required for port monitoring).
	 * timeout - required
	 * location_profile_id - required
	 * notification_profile_id - required
	 * threshold_profile_id - required
	 * notification_profile_id - required
	 * user_group_ids - required
	 * http_method - required
	 * auth_user - optional
	 * auth_pass - optional
	 */
	public function api_edit_monitor( $params = array(), $updated_id = false ) {

		if ( empty( $params['display_name'] ) || empty( $params['website'] ) || empty( $params['type'] ) ) {
			throw new \Exception( 'Required key "display_name", "website" or "type" not specified', 3 );
		}

		$hearder = array(
			'Content-Type: application/json;charset=UTF-8',
			'Accept: application/json; version=2.1',
			'Authorization: Zoho-oauthtoken ' . $this->get_access_token( 'admin' ),
		);

		$params = wp_json_encode( $params );

		$method  = 'POST';
		$api_act = 'api/monitors';
		if ( ! empty( $updated_id ) ) {
			$method   = 'PUT';
			$api_act .= '/' . $updated_id;
		}
		return $this->fetch_remote_url( $api_act, $params, $hearder, $method );
	}

	/**
	 * Activates monitor.
	 *
	 * @param int $monitor_id Monitor ID.
	 *
	 * @return string URL.
	 */
	public function activate_monitor( $monitor_id ) {
		$hearder = array(
			'Accept: application/json; version=2.1',
			'Authorization: Zoho-oauthtoken ' . $this->get_access_token( 'operations' ),
		);
		return $this->fetch_remote_url( 'api/monitors/activate/' . $monitor_id, array(), $hearder, 'PUT' );
	}

	/**
	 * Suspends monitor.
	 *
	 * @param int $monitor_id Monitor ID.
	 *
	 * @return string URL.
	 */
	public function suspend_monitor( $monitor_id ) {
		$hearder = array(
			'Accept: application/json; version=2.1',
			'Authorization: Zoho-oauthtoken ' . $this->get_access_token( 'operations' ),
		);
		return $this->fetch_remote_url( 'api/monitors/suspend/' . $monitor_id, array(), $hearder, 'PUT' );
	}

	/**
	 * Deletes monitor.
	 *
	 * @param int $monitor_id Monitor ID.
	 *
	 * @return string URL.
	 */
	public function delete_monitor( $monitor_id ) {
		$hearder = array(
			'Accept: application/json; version=2.1',
			'Authorization: Zoho-oauthtoken ' . $this->get_access_token( 'admin' ),
		);

		return $this->fetch_remote_url( 'api/monitors/' . $monitor_id, array(), $hearder, 'DELETE' );
	}

	/**
	 * Gets all monitor.
	 *
	 * @return string URL.
	 */
	public function get_all_monitors() {
		$hearder = array(
			'Accept: application/json; version=2.0',
			'Authorization: Zoho-oauthtoken ' . $this->get_access_token( 'admin' ),
		);
		return $this->fetch_remote_url( 'api/monitors', array(), $hearder, 'GET' );
	}

	 /**
	  * Gets monitors grouops.
	  *
	  * @return string URL.
	  */
	public function get_monitors_groups() {
		$hearder = array(
			'Accept: application/json; version=2.0',
			'Authorization: Zoho-oauthtoken ' . $this->get_access_token( 'admin' ),
		);
		return $this->fetch_remote_url( 'api/monitor_groups', array(), $hearder, 'GET' );
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
	public function get_uptime( $monitor_id, $params = array() ) {
		$hearder = array(
			'Accept: application/json; version=2.0',
			'Authorization: Zoho-oauthtoken ' . $this->get_access_token(),
		);
		if ( empty( $params ) ) {
			$params = array( 'period' => 2 ); // Last 7 Days.
		}
		$str_params = MainWP_AUM_Main::get_instance()->build_params_string( $params );

		return $this->fetch_remote_url( 'api/reports/availability_summary/' . $monitor_id . $str_params, array(), $hearder, 'GET' );
	}

	/**
	 * Gets log reports date.
	 *
	 * @param int    $monitor_id Monitor ID.
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 *
	 * @return string URL.
	 */
	public function get_log_reports_date( $monitor_id, $start_date, $end_date ) {
		$hearder = array(
			'Accept: application/json; version=2.0',
			'Authorization: Zoho-oauthtoken ' . $this->get_access_token(),
		);
		return $this->fetch_remote_url( 'api/reports/log_reports/' . $monitor_id . '?start_date=' . $start_date . '&end_date=' . $end_date, array(), $hearder, 'GET' );
	}

	/**
	 * Gets monitor status.
	 *
	 * @return string URL.
	 */
	public function get_monitors_status() {
		$hearder    = array(
			'Accept: application/json; version=2.0',
			'Authorization: Zoho-oauthtoken ' . $this->get_access_token(),
		);
		$params     = array(
			'apm_required'       => false,
			'group_required'     => false,
			'locations_required' => false,
		);
		$str_params = MainWP_AUM_Main::get_instance()->build_params_string( $params );
		return $this->fetch_remote_url( 'api/current_status' . $str_params, array(), $hearder, 'GET' );
	}

	/**
	 * Gets current moniotr status.
	 *
	 * @param int $monitor_id Monitor ID.
	 *
	 * @return string URL.
	 */
	public function get_current_monitor_status( $monitor_id ) {
		if ( empty( $monitor_id ) ) {
			return false;
		}
		$hearder = array(
			'Accept: application/json; version=2.0',
			'Authorization: Zoho-oauthtoken ' . $this->get_access_token(),
		);
		return $this->fetch_remote_url( 'api/current_status/' . $monitor_id, array(), $hearder, 'GET' );
	}

	/**
	 * Gets top 100 reports.
	 *
	 * @return string URL.
	 */
	public function get_top_100_reports() {
		$hearder    = array(
			'Accept: application/json; version=2.0',
			'Authorization: Zoho-oauthtoken ' . $this->get_access_token(),
		);
		$type       = 'URL';
		$str_params = '?limit=10&period=3'; // last 24h.
		return $this->fetch_remote_url( 'api/reports/top_n/' . $type . $str_params, array(), $hearder, 'GET' );
	}

	/**
	 * Gets log reports.
	 *
	 * @param int    $monitor_id Monitor ID.
	 * @param string $date       Date.
	 *
	 * @return string URL.
	 */
	public function get_log_reports( $monitor_id, $date = false ) {
		if ( empty( $monitor_id ) ) {
			return false;
		}
		$hearder = array(
			'Accept: application/json; version=2.0',
			'Authorization: Zoho-oauthtoken ' . $this->get_access_token(),
		);
		if ( empty( $date ) ) {
			$date = gmdate( 'Y-m-d' );
		}
		$str_params = '?date=' . $date;
		return $this->fetch_remote_url( 'api/reports/log_reports/' . $monitor_id . $str_params, array(), $hearder, 'GET' );
	}

	/**
	 * Gets location profiles.
	 *
	 * @return string URL.
	 */
	public function get_location_profiles() {
		$hearder = array(
			'Accept: application/json; version=2.0',
			'Authorization: Zoho-oauthtoken ' . $this->get_access_token( 'admin' ),
		);
		return $this->fetch_remote_url( 'api/location_profiles', array(), $hearder, 'GET' );
	}

	/**
	 * Gets the location template.
	 *
	 * @return string url.
	 */
	public function get_location_template() {
		$hearder = array(
			'Accept: application/json; version=2.0',
			'Authorization: Zoho-oauthtoken ' . $this->get_access_token( 'admin' ),
		);
		return $this->fetch_remote_url( 'api/location_template', array(), $hearder, 'GET' );
	}

	/**
	 * Gets the threshold profiles.
	 *
	 * @return string url.
	 */
	public function get_threshold_profiles() {
		$hearder = array(
			'Accept: application/json; version=2.1',
			'Authorization: Zoho-oauthtoken ' . $this->get_access_token( 'admin' ),
		);
		return $this->fetch_remote_url( 'api/threshold_profiles', array(), $hearder, 'GET' );
	}

	/**
	 * Gets the notification profiles.
	 *
	 * @return string URL.
	 */
	public function get_notification_profiles() {
		$hearder = array(
			'Accept: application/json; version=2.0',
			'Authorization: Zoho-oauthtoken ' . $this->get_access_token( 'admin' ),
		);
		return $this->fetch_remote_url( 'api/notification_profiles', array(), $hearder, 'GET' );
	}

	/**
	 * Lists User Alert Groups.
	 *
	 * @return string URL.
	 */
	public function get_user_groups() {
		$hearder = array(
			'Accept: application/json; version=2.0',
			'Authorization: Zoho-oauthtoken ' . $this->get_access_token( 'admin' ),
		);
		return $this->fetch_remote_url( 'api/user_groups', array(), $hearder, 'GET' );
	}

	/**
	 * Returns the authorization request url
	 *
	 * @return string url.
	 */
	public function get_request_url( $api_act, $params = array() ) {
		if ( is_array( $params ) && isset( $params['api_admin'] ) && $params['api_admin'] ) {
			$url = $this->api_admin_url . $api_act;
		} else {
			$url = $this->api_site247_url . $api_act;
		}

		if ( is_array( $params ) && isset( $params['api_admin'] ) ) {
			unset( $params['api_admin'] );
		}

		$str_params = MainWP_AUM_Main::get_instance()->build_params_string( $params );

		return $url . $str_params;
	}

	/**
	 * Returns the result of the API calls.
	 *
	 * @param mixed $url required
	 */
	private function fetch_remote_url( $api_act, $post_data = array(), $header = array(), $method = 'POST' ) {
		if ( is_array( $post_data ) && isset( $post_data['api_admin'] ) && $post_data['api_admin'] ) {
			$url = $this->api_admin_url . $api_act;
		} else {
			$url = $this->api_site247_url . $api_act;
		}

		if ( is_array( $post_data ) && isset( $post_data['api_admin'] ) ) {
			unset( $post_data['api_admin'] );
		}

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

	/**
	 * Generates expired access tokens.
	 */
	public function auto_gen_expired_access_tokens() {
		if ( $this->check_access_expires() ) {
			$lasttime = $this->get_option( 'lasttime_generate_access', 0 );
			if ( ! empty( $lasttime ) && ( $lasttime < time() - MINUTE_IN_SECONDS * 15 ) ) {
				$this->gen_access_tokens();
			}
		}
	}

	/**
	 * Generates access tokens.
	 *
	 * @return string Access token.
	 */
	public function gen_access_tokens() {
		$gen_access = array(
			'admin',
			'report',
			'operations',
		);
		$generated  = false;
		foreach ( $gen_access as $scope ) {
			$result = $this->get_access_token_from_refresh_token( $scope );

			if ( ! empty( $result ) ) {
				$data = json_decode( $result, true );
				if ( is_array( $data ) && isset( $data['access_token'] ) ) {
					$opts = array(
						'access_token' => $data['access_token'],
						'api_domain'   => isset( $data['api_domain'] ) ? $data['api_domain'] : '',
						'token_type'   => $data['token_type'],
						'expires_time' => time() + $data['expires_in'],
					);

					$auth_settings = $this->get_option( 'auth_settings', array() );
					$current       = isset( $auth_settings[ $scope ] ) ? $auth_settings[ $scope ] : array();

					$opts['refresh_token']   = isset( $current['refresh_token'] ) ? $current['refresh_token'] : '';
					$auth_settings[ $scope ] = $opts;

					$this->update_options( array( 'auth_settings' => $auth_settings ) );
					$generated = true;
				}
			}
		}
		if ( $generated ) {
			$this->update_options( array( 'lasttime_generate_access' => time() ) );
		}
		return $generated;
	}

	/**
	 * Checks access tokens expiration.
	 *
	 * @return bool true|false.
	 */
	public function check_access_expires() {
		$refresh_token_report = $this->get_scope_auth_option( 'refresh_token', 'report' );
		if ( ! empty( $refresh_token_report ) ) {
			$expires_admin      = $this->get_scope_auth_option( 'expires_time', 'admin', 0 );
			$expires_report     = $this->get_scope_auth_option( 'expires_time', 'report', 0 );
			$expires_operations = $this->get_scope_auth_option( 'expires_time', 'operations', 0 );
			if ( ( time() > $expires_admin ) || ( time() > $expires_report ) || ( time() > $expires_operations ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Verifies if API response is valid.
	 *
	 * @param array $res Response.
	 *
	 * @return bool True|False.
	 */
	public function is_valid_api_response( $res ) {
		$data = @json_decode( $res );
		if ( ! is_array( $data ) ) {
			return false;
		} elseif ( isset( $data['error_code'] ) ) {
			if ( 1120 == $data['error_code'] ) {
				return false;
			} else {
				return false;
			}
		}
		return true;
	}

}

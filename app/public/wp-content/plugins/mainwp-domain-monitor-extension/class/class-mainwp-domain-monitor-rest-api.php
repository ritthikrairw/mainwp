<?php
/**
 * MainWP Domain Monitor REST API
 *
 * This class handles the REST API
 *
 * @package MainWP/Extensions
 */

namespace MainWP\Extensions\Domain_Monitor;

/**
 * Class Rest_Api
 *
 * @package MainWP/Extensions
 */
class Rest_Api {

	// phpcs:disable Generic.Metrics.CyclomaticComplexity -- complexity.

	/**
	 * Protected variable to hold the API version.
	 *
	 * @var string API version
	 */
	protected $api_version = '1';

	/**
	 * Protected static variable to hold the single instance of the class.
	 *
	 * @var mixed Default null
	 */
	private static $instance = null;

	/**
	 * Method instance()
	 *
	 * Create public static instance.
	 *
	 * @static
	 * @return self::$instance
	 */
	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Method init()
	 *
	 * Adds an action to create the rest API endpoints if activated in the plugin settings.
	 */
	public function init() {
		// only activate the api if enabled in the plugin settings.
		if ( get_option( 'mainwp_enable_rest_api' ) ) {
			// check to see whether activated or not.
			$activated = get_option( 'mainwp_enable_rest_api' );

			if ( $activated ) {
				// run API.
				add_action( 'rest_api_init', array( &$this, 'mainwp_register_routes' ) );
			}
		}
	}

	/**
	 * Method mainwp_rest_api_init()
	 *
	 * Creates the necessary endpoints for the api.
	 * Note, for a request to be successful the URL query parameters consumer_key and consumer_secret need to be set and correct.
	 */
	public function mainwp_register_routes() {
		// Create an array which holds all the endpoints. Method can be GET, POST, PUT, DELETE.
		$endpoints = array(
			array(
				'route'    => 'domain-monitor',
				'method'   => 'GET',
				'callback' => 'domain_check',
			),
			array(
				'route'    => 'domain-monitor',
				'method'   => 'GET',
				'callback' => 'domain_profile',
			),
		);
		// loop through the endpoints.
		foreach ( $endpoints as $endpoint ) {
			$function_name = str_replace( '-', '_', $endpoint['callback'] );
			register_rest_route(
				'mainwp/v' . $this->api_version,
				'/' . $endpoint['route'] . '/' . $endpoint['callback'],
				array(
					'methods'             => $endpoint['method'],
					'callback'            => array( &$this, 'domain_monitor_rest_api_' . $function_name . '_callback' ),
					'permission_callback' => '__return_true',
				)
			);
		}
	}

	/**
	 * Method mainwp_authentication_error()
	 *
	 * Common error message when consumer key and secret are wrong.
	 *
	 * @return array $response Array with an error message explaining that the credentials are wrong.
	 */
	public function mainwp_authentication_error() {

		$data = array( 'ERROR' => __( 'Incorrect or missing consumer key and/or secret. If the issue persists please reset your authentication details from the MainWP > Settings > REST API page, on your MainWP Dashboard site.', 'mainwp' ) );

		$response = new \WP_REST_Response( $data );
		$response->set_status( 401 );

		return $response;
	}

	/**
	 * Method mainwp_missing_data_error()
	 *
	 * Common error message when data is missing from the request.
	 *
	 * @return array $response Array with an error message explaining details are missing.
	 */
	public function mainwp_missing_data_error() {

		$data = array( 'ERROR' => __( 'Required parameter is missing.', 'mainwp' ) );

		$response = new \WP_REST_Response( $data );
		$response->set_status( 400 );

		return $response;
	}

	/**
	 * Method mainwp_invalid_data_error()
	 *
	 * Common error message when data in request is ivalid.
	 *
	 * @return array $response Array with an error message explaining details are missing.
	 */
	public function mainwp_invalid_data_error() {

		$data = array( 'ERROR' => __( 'Required parameter data is is not valid.', 'mainwp' ) );

		$response = new \WP_REST_Response( $data );
		$response->set_status( 400 );

		return $response;
	}

	/**
	 * Method mainwp_run_process_success()
	 *
	 * Common error message when data is missing from the request.
	 *
	 * @return array $response Array with an error message explaining details are missing.
	 */
	public function mainwp_run_process_success() {

		$data = array( 'SUCCESS' => __( 'Process ran.', 'mainwp' ) );

		$response = new \WP_REST_Response( $data );
		$response->set_status( 200 );

		return $response;
	}

	/**
	 * Method domain_monitor_rest_api_domain_check_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: domain_check
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/domain-monitor/domain_check
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function domain_monitor_rest_api_domain_check_callback( $request ) {
		// first validate the request.
		if ( apply_filters( 'mainwp_rest_api_validate', false, $request ) ) {
			// get parameters.
			if ( null != $request['site_id'] ) {
				$website_id = $request['site_id'];

				$website = MainWP_Domain_Monitor_Admin::get_websites( $website_id );

				if ( $website && is_array( $website ) ) {
					$website = current( $website );
				}

				$error = '';
				$data  = array();

				if ( empty( $website ) ) {
					$data['error'] = __( 'Site not found.', 'mainwp-domain-monitor-extension' );
				} else {
					$site_url    = $website['url'];
					$domain      = MainWP_Domain_Monitor_Utility::get_domain( MainWP_Domain_Monitor_Utility::get_nice_url( $site_url ) );
					$domain_site = MainWP_Domain_Monitor_DB::get_instance()->get_domain_monitor_by( 'site_id', $website_id );
					$id          = isset( $domain_site->id ) ? $domain_site->id : 0;
					if ( 0 < $id ) {
						$update = true;
					}
					$message = MainWP_Domain_Monitor_Core::lookup_domain( $domain, $id, $website_id, $site_url, $update );

					if ( empty( $message ) ) {
						$data['error'] = __( 'An undefined error occured.', 'mainwp-domain-monitor-extension' );
					} else {
						$data['message'] = $message;
					}
				}

				$response = new \WP_REST_Response( $data );
				$response->set_status( 200 );
			} else {
				// throw missing data error.
				$response = $this->mainwp_missing_data_error();
			}
		} else {
			// throw common error.
			$response = $this->mainwp_authentication_error();
		}
		return $response;
	}

	/**
	 * Method domain_monitor_rest_api_domain_profile_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: domain_profile
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/domain-monitor/domain_profile
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function domain_monitor_rest_api_domain_profile_callback( $request ) {
		// first validate the request.
		if ( apply_filters( 'mainwp_rest_api_validate', false, $request ) ) {
			$data = array();
			// get parameters.
			if ( null != $request['site_id'] ) {
				$website_id = $request['site_id'];
				$websites   = MainWP_Domain_Monitor_Admin::get_websites( $website_id );
			} elseif ( null != $request['all'] ) {
				$websites = MainWP_Domain_Monitor_Admin::get_websites();
			} else {
				// throw missing data error.
				$response = $this->mainwp_missing_data_error();
				return $response;
			}

			if ( empty( $websites ) ) {
				$data['error'] = __( 'Sites not found.', 'mainwp-domain-monitor-extension' );
			} else {
				$data = MainWP_Domain_Monitor_Admin::handle_rest_api_sites_domain_profiles( $websites );
			}
			$response = new \WP_REST_Response( $data );
			$response->set_status( 200 );
		} else {
			// throw common error.
			$response = $this->mainwp_authentication_error();
		}
		return $response;
	}

}

// End of class.

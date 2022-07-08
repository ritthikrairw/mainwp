<?php
/**
 * MainWP REST API
 *
 * This class handles the REST API
 *
 * @package MainWP/Extensions
 */

namespace MainWP\Extensions\Virusdie;

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
				'route'    => 'virusdie',
				'method'   => 'GET',
				'callback' => 'scan',
			),
			array(
				'route'    => 'virusdie',
				'method'   => 'GET',
				'callback' => 'last-scan',
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
					'callback'            => array( &$this, 'virusdie_rest_api_' . $function_name . '_callback' ),
					'permission_callback' => '__return_true',
				)
			);
		}
	}

	/**
	 * Method mainwp_rest_api_init()
	 *
	 * Makes sure the correct consumer key and secret are entered.
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return bool Whether the api credentials are valid.
	 */
	public function mainwp_validate_request( $request ) {

		// users entered consumer key and secret.
		$consumer_key    = $request['consumer_key'];
		$consumer_secret = $request['consumer_secret'];

		// data stored in database.
		$consumer_key_option    = get_option( 'mainwp_rest_api_consumer_key' );
		$consumer_secret_option = get_option( 'mainwp_rest_api_consumer_secret' );

		if ( wp_check_password( $consumer_key, $consumer_key_option ) && wp_check_password( $consumer_secret, $consumer_secret_option ) ) {
			if ( ! defined( 'MAINWP_REST_API' ) ) {
				define( 'MAINWP_REST_API', true );
			}
			return true;
		} else {
			return false;
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
	 * Method virusdie_rest_api_scan_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: scan
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/virusdie/scan
	 * API Method: POST
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function virusdie_rest_api_scan_callback( $request ) {
		// first validate the request.
		if ( $this->mainwp_validate_request( $request ) ) {
			// get parameters.
			if ( null != $request['site_id'] ) {
				$website_id = $request['site_id'];

				/**
				 * Extension object
				 *
				 * @global object
				 */
				global $mainWPVirusdieExtensionActivator;
				$website = apply_filters( 'mainwp_getsites', $mainWPVirusdieExtensionActivator->get_child_file(), $mainWPVirusdieExtensionActivator->get_child_key(), $website_id );
				if ( $website && is_array( $website ) ) {
					$website = current( $website );
				}

				$error = '';
				$data  = array();

				if ( empty( $website ) ) {
					$error = 'Site not found.';
				} else {
					$virusdie = MainWP_Virusdie_DB::get_instance()->get_virusdie_by( 'site_id', $website_id );
					if ( ! empty( $virusdie ) ) {
						$results = MainWP_Virusdie_API::instance()->scan_site( $virusdie->domain );
						if ( is_array( $results ) && empty( $results['error'] ) ) {
							$data['message'] = __( 'Scan started.', 'mainwp-virusdie-extension' );
						} elseif ( ! empty( $results['error'] ) ) {
							$error = $results['error'];
						}
					} else {
						$error = __( 'Site not found in the Virusdie account.', 'mainwp-virusdie-extension' );
					}
				}

				if ( empty( $data ) ) {
					$data['error'] = $error;
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
	 * Method virusdie_rest_api_last_scan_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: unignore-update
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/virusdie/last-scan
	 * API Method: POST
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function virusdie_rest_api_last_scan_callback( $request ) {

		// first validate the request.
		if ( $this->mainwp_validate_request( $request ) ) {
			// get parameters.
			if ( null != $request['site_id'] ) {

				$website_id = $request['site_id'];

				/**
				 * Extension object
				 *
				 * @global object
				 */
				global $mainWPVirusdieExtensionActivator;
				$website = apply_filters( 'mainwp_getsites', $mainWPVirusdieExtensionActivator->get_child_file(), $mainWPVirusdieExtensionActivator->get_child_key(), $website_id );
				if ( $website && is_array( $website ) ) {
					$website = current( $website );
				}

				$error = '';
				$data  = array();

				if ( empty( $website ) ) {
					$error = __( 'Site not found.', 'mainwp-virusdie-extension' );
				} else {
					$virusdie = MainWP_Virusdie_DB::get_instance()->get_virusdie_by( 'site_id', $website_id );
					if ( $virusdie ) {
						$last_report = MainWP_Virusdie_DB::get_instance()->get_lastscan( $virusdie->virusdie_item_id );
						if ( ! empty( $last_report ) ) {
							$data = $last_report;
						} else {
							$error = __( 'Report not found.', 'mainwp-virusdie-extension' );
						}
					} else {
						$error = __( 'Site not found in the Virusdie account.', 'mainwp-virusdie-extension' );
					}
				}

				if ( empty( $data ) ) {
					$data['error'] = $error;
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
}

// End of class.
